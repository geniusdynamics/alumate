<?php

use Illuminate\Support\Facades\Route;

// Admin A/B Testing routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('admin')->group(function () {
    Route::apiResource('ab-tests', App\Http\Controllers\Api\Admin\ABTestController::class);
    Route::get('ab-tests-analytics', [App\Http\Controllers\Api\Admin\ABTestController::class, 'analytics']);
});

// Admin Performance routes
Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin/performance')->group(function () {
    Route::get('metrics', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'metrics']);
    Route::get('budget-details', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'getBudgetDetails']);
    Route::post('clear-caches', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'clearCaches']);
    Route::post('optimize-social-graph', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeSocialGraph']);
    Route::post('optimize-timeline', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeTimeline']);
    Route::post('optimize-cdn', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeCdn']);
    Route::post('setup-alerts', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'setupAlerts']);
    Route::post('execute-optimization', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'executeAutomatedOptimization']);
});

// Deployment and Release Monitoring
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('deployment')->group(function () {
    Route::post('status/update', function (\Illuminate\Http\Request $request) {
        $statusUpdate = [
            'deployment_id' => $request->input('deployment_id'),
            'environment' => $request->input('environment', 'production'),
            'status' => $request->input('status'),
            'progress' => $request->input('progress', 0),
            'message' => $request->input('message'),
            'timestamp' => now()->toISOString(),
        ];
        \Cache::put('deployment_status_'.$statusUpdate['deployment_id'], $statusUpdate);
        \Log::info('Deployment Status Update', $statusUpdate);

        return response()->json(['status' => 'recorded', 'deployment' => $statusUpdate]);
    });

    Route::get('history', function () {
        $keys = \Cache::store('redis')->keys('deployment_status_*');
        $deployments = [];
        foreach ($keys as $key) {
            $data = \Cache::get(str_replace('redis:', '', $key));
            if ($data) {
                $deployments[] = $data;
            }
        }
        usort($deployments, fn ($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        return response()->json(['status' => 'success', 'deployments' => array_slice($deployments, 0, 50)]);
    });

    Route::post('rollback/{deploymentId}', function ($deploymentId) {
        \Log::warning('Deployment Rollback Initiated', ['deployment_id' => $deploymentId, 'timestamp' => now()->toISOString(), 'user' => auth()->id()]);
        \Cache::put("rollback_{$deploymentId}", ['deployment_id' => $deploymentId, 'rollback_initiated_at' => now()->toISOString(), 'rollback_by' => auth()->id(), 'rollback_status' => 'initiated']);

        return response()->json(['status' => 'rollback_initiated', 'deployment_id' => $deploymentId, 'rollback_id' => "rollback_{$deploymentId}"]);
    });
});

// Admin Verification routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('admin/verification')->group(function () {
    Route::get('/requests', [App\Http\Controllers\Admin\VerificationController::class, 'index']);
    Route::post('/requests/{requestId}/approve', [App\Http\Controllers\Admin\VerificationController::class, 'approve']);
    Route::post('/requests/{requestId}/reject', [App\Http\Controllers\Admin\VerificationController::class, 'reject']);
    Route::post('/bulk-import', [App\Http\Controllers\Admin\VerificationController::class, 'bulkImport']);
    Route::get('/analytics', [App\Http\Controllers\Admin\VerificationController::class, 'analytics']);
});

// Production Monitoring routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('monitoring')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Api\MonitoringController::class, 'dashboard']);
    Route::get('realtime', [App\Http\Controllers\Api\MonitoringController::class, 'realtime']);
    Route::get('metrics/{type}', [App\Http\Controllers\Api\MonitoringController::class, 'metrics']);
    Route::get('alerts', [App\Http\Controllers\Api\MonitoringController::class, 'alerts']);
    Route::get('reports', [App\Http\Controllers\Api\MonitoringController::class, 'reports']);
    Route::post('cycle', [App\Http\Controllers\Api\MonitoringController::class, 'executeCycle']);
    Route::get('settings', [App\Http\Controllers\Api\MonitoringController::class, 'settings']);
});

// Analytics admin routes
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('analytics')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Api\AnalyticsController::class, 'getDashboardData']);
    Route::get('engagement-metrics', [App\Http\Controllers\Api\AnalyticsController::class, 'getEngagementMetrics']);
    Route::get('alumni-activity', [App\Http\Controllers\Api\AnalyticsController::class, 'getAlumniActivity']);
    Route::get('community-health', [App\Http\Controllers\Api\AnalyticsController::class, 'getCommunityHealth']);
    Route::get('platform-usage', [App\Http\Controllers\Api\AnalyticsController::class, 'getPlatformUsage']);
    Route::get('summary', [App\Http\Controllers\Api\AnalyticsController::class, 'getAnalyticsSummary']);
    Route::post('custom-report', [App\Http\Controllers\Api\AnalyticsController::class, 'generateCustomReport']);
    Route::get('export', [App\Http\Controllers\Api\AnalyticsController::class, 'exportData']);
    Route::get('available-metrics', [App\Http\Controllers\Api\AnalyticsController::class, 'getAvailableMetrics']);

    Route::prefix('email')->group(function () {
        Route::get('performance', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailPerformance']);
        Route::get('funnel', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailFunnel']);
        Route::get('engagement', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailEngagement']);
        Route::get('ab-test', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailAbTest']);
        Route::get('realtime', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailRealtime']);
        Route::get('report/{period}', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailReport']);
        Route::post('track', [App\Http\Controllers\Api\AnalyticsController::class, 'trackEmailEvent']);
        Route::get('dashboard', [App\Http\Controllers\Api\AnalyticsController::class, 'getEmailDashboard']);
    });

    Route::get('heatmaps/{pageUrl}', [App\Http\Controllers\Api\AnalyticsController::class, 'getHeatMapData'])->middleware('throttle:analytics_heatmaps');
    Route::post('heatmaps/generate', [App\Http\Controllers\Api\AnalyticsController::class, 'generateHeatMapData'])->middleware('throttle:analytics_heatmaps');
});

// Calendar Integration routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('calendar/connections', [App\Http\Controllers\Api\CalendarSyncController::class, 'index']);
    Route::post('calendar/connect', [App\Http\Controllers\Api\CalendarSyncController::class, 'connect']);
    Route::post('calendar/connections/{connection}/disconnect', [App\Http\Controllers\Api\CalendarSyncController::class, 'disconnect']);
    Route::post('calendar/connections/{connection}/sync', [App\Http\Controllers\Api\CalendarSyncController::class, 'sync']);
    Route::get('calendar/sync-status', [App\Http\Controllers\Api\CalendarSyncController::class, 'syncStatus']);
    Route::get('calendar/availability', [App\Http\Controllers\Api\CalendarSyncController::class, 'availability']);
    Route::post('calendar/find-slots', [App\Http\Controllers\Api\CalendarSyncController::class, 'findSlots']);
    Route::post('calendar/events', [App\Http\Controllers\Api\CalendarSyncController::class, 'createEvent']);
    Route::post('calendar/events/{event}/invites', [App\Http\Controllers\Api\CalendarSyncController::class, 'sendInvites']);
    Route::post('calendar/schedule-mentorship', [App\Http\Controllers\Api\CalendarSyncController::class, 'scheduleMentorship']);
});

// Developer API routes
Route::middleware('auth:sanctum')->prefix('developer')->name('developer.')->group(function () {
    Route::post('api-keys', [\App\Http\Controllers\Api\DeveloperController::class, 'generateApiKey'])->name('api-keys.generate');
    Route::get('api-keys', [\App\Http\Controllers\Api\DeveloperController::class, 'getApiKeys'])->name('api-keys.index');
    Route::delete('api-keys/{keyId}', [\App\Http\Controllers\Api\DeveloperController::class, 'revokeApiKey'])->name('api-keys.revoke');
    Route::get('webhook-events', [\App\Http\Controllers\Api\DeveloperController::class, 'getWebhookEvents'])->name('webhook-events');
    Route::post('webhooks/{webhookId}/test', [\App\Http\Controllers\Api\DeveloperController::class, 'testWebhook'])->name('webhooks.test');
    Route::get('documentation', [\App\Http\Controllers\Api\DeveloperController::class, 'getApiDocumentation'])->name('documentation');
    Route::post('postman-collection', [\App\Http\Controllers\Api\DeveloperController::class, 'generatePostmanCollection'])->name('postman-collection');
    Route::post('sdk-generator', [\App\Http\Controllers\Api\DeveloperController::class, 'generateSdk'])->name('sdk-generator');
});
