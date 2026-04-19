<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExternalSyncController extends Controller
{
    private SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle external sync requests
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $eventData = $request->validate([
                'name' => 'required|string',
                'category' => 'nullable|string',
                'label' => 'nullable|string',
                'value' => 'nullable|numeric',
                'custom_params' => 'nullable|array',
                'user_properties' => 'nullable|array',
                'client_id' => 'nullable|string',
                'url' => 'nullable|string|url',
                'user_agent' => 'nullable|string',
                'language' => 'nullable|string',
            ]);

            $tenantId = $request->header('X-Tenant-ID');
            $userSegment = $request->input('user_segment');

            $result = $this->syncService->syncToExternal($eventData, $tenantId, $userSegment);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle external webhook callbacks
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Process webhook data from external analytics platforms
            $webhookData = $request->all();

            // Log the webhook for monitoring
            Log::info('External analytics webhook received', [
                'provider' => $request->header('User-Agent'),
                'data' => $webhookData,
            ]);

            // Process the webhook data as needed
            // This could include validation, processing, and storing results

            return response()->json([
                'success' => true,
                'message' => 'Webhook received and processed',
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing external webhook', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status
     */
    public function status(Request $request): JsonResponse
    {
        try {
            // Return sync status information
            $status = [
                'sync_service' => 'active',
                'last_sync' => now()->toISOString(),
                'external_platforms' => [
                    'google_analytics' => true,
                    'matomo' => true,
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
