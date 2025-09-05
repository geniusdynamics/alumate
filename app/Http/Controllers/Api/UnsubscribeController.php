<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\ComplianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * UnsubscribeController
 *
 * API controller for handling unsubscribe requests and email preference management
 */
class UnsubscribeController extends Controller
{
    public function __construct(
        private ComplianceService $complianceService
    ) {}

    /**
     * Confirm unsubscribe request.
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'categories' => 'sometimes|array',
            'categories.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->complianceService->processUnsubscribe(
            $request->token,
            $request->email,
            $request->categories ?? []
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Get preference center data.
     */
    public function preferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->getCurrentTenant();

        $data = $this->complianceService->getPreferenceCenterData(
            $request->email,
            $tenant
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Update email preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'preferences' => 'required|array',
            'preferences.newsletters' => 'boolean',
            'preferences.promotions' => 'boolean',
            'preferences.announcements' => 'boolean',
            'preferences.events' => 'boolean',
            'preferences.surveys' => 'boolean',
            'frequency_settings' => 'sometimes|array',
            'frequency_settings.newsletters' => ['sometimes', Rule::in(['daily', 'weekly', 'monthly', 'quarterly'])],
            'frequency_settings.promotions' => ['sometimes', Rule::in(['daily', 'weekly', 'monthly', 'quarterly'])],
            'frequency_settings.announcements' => ['sometimes', Rule::in(['immediate', 'daily', 'weekly'])],
            'frequency_settings.events' => ['sometimes', Rule::in(['daily', 'weekly', 'monthly'])],
            'frequency_settings.surveys' => ['sometimes', Rule::in(['monthly', 'quarterly', 'annually'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid preference data.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->getCurrentTenant();

        try {
            $preference = $this->complianceService->createOrUpdatePreferences(
                $request->email,
                $tenant,
                $request->preferences,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully.',
                'data' => [
                    'email' => $preference->email,
                    'preferences' => $preference->preferences,
                    'frequency_settings' => $preference->frequency_settings,
                    'updated_at' => $preference->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences.',
            ], 500);
        }
    }

    /**
     * Initiate double opt-in process.
     */
    public function initiateDoubleOptIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->getCurrentTenant();

        try {
            $preference = $this->complianceService->initiateDoubleOptIn(
                $request->email,
                $tenant,
                $request->user()
            );

            return response()->json([
                'success' => true,
                'message' => 'Double opt-in email sent. Please check your email to confirm.',
                'data' => [
                    'email' => $preference->email,
                    'double_opt_in_initiated_at' => $preference->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate double opt-in process.',
            ], 500);
        }
    }

    /**
     * Confirm double opt-in.
     */
    public function confirmDoubleOptIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->complianceService->confirmDoubleOptIn($request->token);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email address successfully verified.',
            'data' => [
                'email' => $result['preference']->email,
                'verified_at' => $result['preference']->double_opt_in_verified_at,
            ],
        ]);
    }

    /**
     * Generate unsubscribe link for testing/admin purposes.
     */
    public function generateUnsubscribeLink(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->getCurrentTenant();

        $preference = $this->complianceService->createOrUpdatePreferences(
            $request->email,
            $tenant,
            [], // Default preferences
            $request->user()
        );

        $unsubscribeLink = $this->complianceService->generateUnsubscribeLink($preference);

        return response()->json([
            'success' => true,
            'data' => [
                'email' => $preference->email,
                'unsubscribe_link' => $unsubscribeLink,
                'expires_at' => now()->addDays(30)->toISOString(),
            ],
        ]);
    }

    /**
     * Get compliance report.
     */
    public function complianceReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date range.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant = $this->getCurrentTenant();

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : null;
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : null;

        $report = $this->complianceService->generateComplianceReport($tenant, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get current tenant from request.
     */
    private function getCurrentTenant(): Tenant
    {
        // Implementation depends on your tenant resolution strategy
        // This could be from domain, subdomain, or request parameter
        return Tenant::findOrFail(
            request()->header('X-Tenant-ID') ??
            request()->input('tenant_id') ??
            1 // Default tenant for single-tenant setup
        );
    }
}