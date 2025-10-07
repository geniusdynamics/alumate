<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\PrivacyAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Privacy Controller for GDPR/CCPA compliance features
 */
class PrivacyController extends Controller
{
    public function __construct(
        private ConsentService $consentService,
        private PrivacyAuditService $auditService
    ) {}

    /**
     * Grant consent for analytics tracking
     */
    public function grantConsent(Request $request, string $type = 'analytics'): JsonResponse
    {
        $request->validate([
            'criteria' => 'nullable|array',
            'parameters' => 'nullable|array',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $result = $this->consentService->grantConsent($user->id, $type);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => "Consent granted for {$type}",
                'data' => [
                    'type' => $type,
                    'granted_at' => now(),
                ]
            ]);
        }

        return response()->json(['error' => 'Failed to grant consent'], 500);
    }

    /**
     * Revoke consent for analytics tracking
     */
    public function revokeConsent(Request $request, string $type = 'analytics'): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $result = $this->consentService->revokeConsent($user->id, $type);

        if ($result) {
            return response()->json([
                'success' => true,
                'message' => "Consent revoked for {$type}",
                'data' => [
                    'type' => $type,
                    'revoked_at' => now(),
                ]
            ]);
        }

        return response()->json(['error' => 'Failed to revoke consent'], 500);
    }

    /**
     * Handle data export request (GDPR right to portability)
     */
    public function export(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $exportData = $this->consentService->handleDataExportRequest($user);

            return response()->json([
                'success' => true,
                'message' => 'Data export prepared',
                'data' => $exportData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to prepare data export'], 500);
        }
    }

    /**
     * Handle CCPA opt-out request
     */
    public function optOut(Request $request): JsonResponse
    {
        $request->validate([
            'opt_out' => 'required|boolean',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $result = $this->consentService->integrateCCPAOptOut($user, $request->opt_out);

            if ($result) {
                $message = $request->opt_out ? 'Opted out of data selling' : 'Opted back in';
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return response()->json(['error' => 'Failed to process opt-out request'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to process opt-out request'], 500);
        }
    }

    /**
     * Get audit logs for the authenticated user
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $logs = $this->auditService->getAuditLogs(
                $user,
                $request->from,
                $request->to,
                $request->limit ?? 50
            );

            return response()->json([
                'success' => true,
                'data' => $logs,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve audit logs'], 500);
        }
    }
}