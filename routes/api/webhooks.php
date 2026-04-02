<?php

use Illuminate\Support\Facades\Route;

// CRM Webhook routes (no auth required for webhooks)
Route::prefix('webhooks/crm')->group(function () {
    Route::post('hubspot', [App\Http\Controllers\Api\CrmWebhookController::class, 'hubspot']);
    Route::post('salesforce', [App\Http\Controllers\Api\CrmWebhookController::class, 'salesforce']);
    Route::post('pipedrive', [App\Http\Controllers\Api\CrmWebhookController::class, 'pipedrive']);
    Route::post('{provider}', [App\Http\Controllers\Api\CrmWebhookController::class, 'generic']);
});

// Webhook management routes
Route::middleware(['auth:sanctum', 'api.rate_limit:webhook'])->group(function () {
    Route::apiResource('webhooks', App\Http\Controllers\Api\WebhookController::class);
    Route::post('webhooks/{webhook}/test', [App\Http\Controllers\Api\WebhookController::class, 'test']);
    Route::get('webhooks/{webhook}/deliveries', [App\Http\Controllers\Api\WebhookController::class, 'deliveries']);
    Route::post('webhooks/{webhook}/deliveries/{delivery}/retry', [App\Http\Controllers\Api\WebhookController::class, 'retryDelivery']);
    Route::get('webhooks/{webhook}/statistics', [App\Http\Controllers\Api\WebhookController::class, 'statistics']);
    Route::get('webhooks/events', [App\Http\Controllers\Api\WebhookController::class, 'events']);
    Route::post('webhooks/validate-url', [App\Http\Controllers\Api\WebhookController::class, 'validateUrl']);
    Route::post('webhooks/{webhook}/pause', [App\Http\Controllers\Api\WebhookController::class, 'pause']);
    Route::post('webhooks/{webhook}/resume', [App\Http\Controllers\Api\WebhookController::class, 'resume']);
});

// Webhook routes for external monitoring integrations
Route::prefix('webhooks/monitoring')->group(function () {
    Route::post('datadog/metrics', function (\Illuminate\Http\Request $request) {
        \Log::info('Datadog Metrics Webhook', $request->all());
        \Cache::put('datadog_webhook_'.time(), $request->all(), 3600);

        return response()->json(['status' => 'received']);
    });
    Route::post('newrelic/alerts', function (\Illuminate\Http\Request $request) {
        \Log::info('New Relic Alert Webhook', $request->all());
        \Cache::put('newrelic_alert_'.time(), $request->all(), 3600);

        return response()->json(['status' => 'received']);
    });
    Route::post('slack/app-rate-limited', function (\Illuminate\Http\Request $request) {
        \Log::warning('Slack Rate Limit Alert', $request->all());

        return response()->json(['status' => 'received']);
    });
    Route::post('sentry/issues', function (\Illuminate\Http\Request $request) {
        \Log::error('Sentry Issue Webhook', $request->all());

        return response()->json(['status' => 'received']);
    });
});

// Performance Monitoring Webhooks
Route::prefix('webhooks/performance')->group(function () {
    Route::post('web-vitals', function (\Illuminate\Http\Request $request) {
        try {
            $vitals = $request->only(['fid', 'lcp', 'fcp', 'cls', 'ttfb', 'url', 'user_agent', 'timestamp']);
            $vitals['recorded_at'] = now()->toISOString();
            $vitals['user_id'] = auth()->id();
            \Cache::put('web_vitals_'.uniqid(), json_encode($vitals), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });
    Route::post('user-interactions', function (\Illuminate\Http\Request $request) {
        try {
            $interaction = ['type' => $request->input('type'), 'element' => $request->input('element'), 'element_selector' => $request->input('element_selector'), 'page_url' => $request->input('page_url'), 'timestamp' => $request->input('timestamp', now()->toISOString()), 'user_id' => auth()->id(), 'session_id' => session()->getId(), 'metadata' => $request->except(['type', 'element', 'element_selector', 'page_url', 'timestamp'])];
            \Cache::put('interaction_'.uniqid(), json_encode($interaction), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });
    Route::post('page-loads', function (\Illuminate\Http\Request $request) {
        try {
            $loadData = ['page_url' => $request->input('page_url'), 'load_time' => $request->input('load_time'), 'dns_lookup' => $request->input('dns_lookup'), 'tcp_connect' => $request->input('tcp_connect'), 'server_response' => $request->input('server_response'), 'page_parse' => $request->input('page_parse'), 'render_time' => $request->input('render_time'), 'user_agent' => $request->userAgent(), 'user_id' => auth()->id(), 'timestamp' => now()->toISOString()];
            \Cache::put('page_load_'.uniqid(), json_encode($loadData), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });
});
