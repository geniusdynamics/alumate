<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrivacyUpdateRequest;
use App\Models\User;
use App\Services\Analytics\PrivacyComplianceService;
use App\Services\TenantContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Privacy Controls API Controller
 *
 * Provides RESTful endpoints for privacy controls, consent management,
 * and data rights (export, delete, anonymize) as part of GDPR/CCPA compliance.
 * Implements proper tenant isolation and role-based access control.
 */
class PrivacyController extends Controller
{
    /**
     * Available consent types
     */
    private const CONSENT_TYPES = ['analytics', 'marketing', 'personalization', 'third_party'];

    public function __construct(
        private readonly PrivacyComplianceService $privacyComplianceService,
        private readonly TenantContextService $tenantContextService
    ) {}

    /**
     * Get privacy settings for the current user or a specific user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization - users can view their own, admins can view tenant users
            $targetUserId = $request->input('user_id', $user->id);
            if (!$this->canAccessPrivacyData($user, $targetUserId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to access these privacy settings.',
                ], 403);
            }

            $consentStatus = $this->privacyComplianceService->getConsentStatus($targetUserId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $targetUserId,
                    'consent_status' => $consentStatus,
                    'available_consent_types' => self::CONSENT_TYPES,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve privacy settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve privacy settings',
            ], 500);
        }
    }

    /**
     * Get user privacy settings
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization
            if (!$this->canAccessPrivacyData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to access these privacy settings.',
                ], 403);
            }

            // Verify user exists in tenant context
            $user = $this->verifyUserInTenant($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found in tenant context',
                ], 404);
            }

            $consentStatus = $this->privacyComplianceService->getConsentStatus($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'consent_status' => $consentStatus,
                    'has_analytics_consent' => $consentStatus['analytics']['has_consent'] ?? false,
                    'last_updated' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user privacy settings', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'requester_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve user privacy settings',
            ], 500);
        }
    }

    /**
     * Update user privacy settings
     *
     * @param PrivacyUpdateRequest $request
     * @param int $userId
     * @return JsonResponse
     */
    public function update(PrivacyUpdateRequest $request, int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization
            if (!$this->canModifyPrivacyData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to modify these privacy settings.',
                ], 403);
            }

            // Verify user exists in tenant context
            $user = $this->verifyUserInTenant($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found in tenant context',
                ], 404);
            }

            $validated = $request->validated();
            $results = [];

            // Update consent settings
            if (isset($validated['consent'])) {
                foreach ($validated['consent'] as $consentType => $consented) {
                    if (in_array($consentType, self::CONSENT_TYPES)) {
                        $success = $this->privacyComplianceService->recordConsent(
                            $userId,
                            $consentType,
                            (bool) $consented
                        );
                        $results["consent_{$consentType}"] = $success;
                    }
                }
            }

            Log::info('Privacy settings updated', [
                'user_id' => $userId,
                'updated_by' => $currentUser->id,
                'changes' => array_keys($results),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Privacy settings updated successfully',
                'data' => [
                    'user_id' => $userId,
                    'results' => $results,
                    'updated_consent_status' => $this->privacyComplianceService->getConsentStatus($userId),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update privacy settings', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update privacy settings',
            ], 500);
        }
    }

    /**
     * Record user consent for a specific type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function consent(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            $validated = $request->validate([
                'consent_type' => 'required|string|in:' . implode(',', self::CONSENT_TYPES),
                'consented' => 'required|boolean',
            ]);

            $consentType = $validated['consent_type'];
            $consented = $validated['consented'];

            $result = $this->privacyComplianceService->recordConsent(
                $user->id,
                $consentType,
                $consented
            );

            if ($result) {
                Log::info('Consent recorded', [
                    'user_id' => $user->id,
                    'consent_type' => $consentType,
                    'consented' => $consented,
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $consented
                        ? "Consent granted for {$consentType}"
                        : "Consent declined for {$consentType}",
                    'data' => [
                        'consent_type' => $consentType,
                        'consented' => $consented,
                        'granted_at' => $consented ? now()->toIso8601String() : null,
                        'updated_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to record consent',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to record consent', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to record consent',
            ], 500);
        }
    }

    /**
     * Revoke user consent for a specific type
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revokeConsent(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            $validated = $request->validate([
                'consent_type' => 'required|string|in:' . implode(',', self::CONSENT_TYPES),
            ]);

            $consentType = $validated['consent_type'];

            $result = $this->privacyComplianceService->revokeConsent($user->id, $consentType);

            if ($result) {
                Log::info('Consent revoked', [
                    'user_id' => $user->id,
                    'consent_type' => $consentType,
                    'ip_address' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Consent revoked for {$consentType}",
                    'data' => [
                        'consent_type' => $consentType,
                        'revoked_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to revoke consent',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to revoke consent', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to revoke consent',
            ], 500);
        }
    }

    /**
     * Get consent status for a specific user
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getConsentStatus(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization - users can view their own, admins can view tenant users
            if (!$this->canAccessPrivacyData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to access this consent status.',
                ], 403);
            }

            $consentStatus = $this->privacyComplianceService->getConsentStatus($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'consent_status' => $consentStatus,
                    'has_any_consent' => collect($consentStatus)->contains('has_consent', true),
                    'queried_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get consent status', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get consent status',
            ], 500);
        }
    }

    /**
     * Export user data (GDPR data portability)
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function exportData(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization - users can export their own, admins can export tenant users
            if (!$this->canAccessPrivacyData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to export this data.',
                ], 403);
            }

            // Verify user exists in tenant context
            $user = $this->verifyUserInTenant($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'User not found in tenant context',
                ], 404);
            }

            $exportData = $this->privacyComplianceService->exportData($userId);

            Log::info('User data exported', [
                'user_id' => $userId,
                'exported_by' => $currentUser->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User data exported successfully',
                'data' => $exportData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to export user data', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to export user data',
            ], 500);
        }
    }

    /**
     * Delete user data (GDPR right to be forgotten)
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function deleteData(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization - users can delete their own, super admins can delete any user
            if (!$this->canDeleteUserData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to delete this data.',
                ], 403);
            }

            $result = $this->privacyComplianceService->deleteData($userId);

            if ($result) {
                Log::warning('User data deleted', [
                    'user_id' => $userId,
                    'deleted_by' => $currentUser->id,
                    'tenant_id' => $this->tenantContextService->getCurrentTenantId(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'User data deleted successfully',
                    'data' => [
                        'user_id' => $userId,
                        'deleted_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete user data',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to delete user data', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete user data',
            ], 500);
        }
    }

    /**
     * Anonymize user data (preserve records but remove identifiers)
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function anonymizeData(int $userId): JsonResponse
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            // Check authorization - admins can anonymize user data, users can anonymize their own
            if (!$this->canAnonymizeUserData($currentUser, $userId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'You do not have permission to anonymize this data.',
                ], 403);
            }

            $result = $this->privacyComplianceService->anonymizeData($userId);

            if ($result) {
                Log::info('User data anonymized', [
                    'user_id' => $userId,
                    'anonymized_by' => $currentUser->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'User data anonymized successfully',
                    'data' => [
                        'user_id' => $userId,
                        'anonymized_at' => now()->toIso8601String(),
                    ],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to anonymize user data',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Failed to anonymize user data', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to anonymize user data',
            ], 500);
        }
    }

    /**
     * Check if user can access privacy data for another user
     *
     * @param User $currentUser
     * @param int $targetUserId
     * @return bool
     */
    private function canAccessPrivacyData(User $currentUser, int $targetUserId): bool
    {
        // Users can access their own data
        if ($currentUser->id === $targetUserId) {
            return true;
        }

        // Super admins can access any user's data
        if ($currentUser->is_super_admin) {
            return true;
        }

        // Tenant admins can access tenant users' data
        return $currentUser->hasRoleInCurrentTenant(User::ROLE_TENANT_ADMIN);
    }

    /**
     * Check if user can modify privacy data for another user
     *
     * @param User $currentUser
     * @param int $targetUserId
     * @return bool
     */
    private function canModifyPrivacyData(User $currentUser, int $targetUserId): bool
    {
        // Users can modify their own data
        if ($currentUser->id === $targetUserId) {
            return true;
        }

        // Super admins can modify any user's data
        return $currentUser->is_super_admin;
    }

    /**
     * Check if user can delete data for another user
     *
     * @param User $currentUser
     * @param int $targetUserId
     * @return bool
     */
    private function canDeleteUserData(User $currentUser, int $targetUserId): bool
    {
        // Users can delete their own data
        if ($currentUser->id === $targetUserId) {
            return true;
        }

        // Super admins can delete any user data
        return $currentUser->is_super_admin;
    }

    /**
     * Check if user can anonymize data for another user
     *
     * @param User $currentUser
     * @param int $targetUserId
     * @return bool
     */
    private function canAnonymizeUserData(User $currentUser, int $targetUserId): bool
    {
        // Users can anonymize their own data
        if ($currentUser->id === $targetUserId) {
            return true;
        }

        // Super admins can anonymize any user's data
        return $currentUser->is_super_admin;
    }

    /**
     * Verify user exists in current tenant context
     *
     * @param int $userId
     * @return User|null
     */
    private function verifyUserInTenant(int $userId): ?User
    {
        return User::where('id', $userId)
            ->where('tenant_id', $this->tenantContextService->getCurrentTenantId())
            ->first();
    }
}
