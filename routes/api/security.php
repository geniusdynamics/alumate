<?php

use Illuminate\Support\Facades\Route;

// Security Monitoring Integration
Route::middleware('auth:sanctum')->prefix('security')->group(function () {
    Route::post('audit/initiate', function () {
        $securityService = app(App\Services\SecurityAuditService::class);
        $results = $securityService->performSecurityAudit();
        \Log::info('Security Audit Completed', ['timestamp' => now()->toISOString()]);

        return response()->json(['status' => 'completed', 'results' => $results, 'timestamp' => now()->toISOString()]);
    });

    Route::get('alerts', function () {
        $alerts = \Cache::get('security_alerts', []);

        return response()->json(['status' => 'success', 'alerts' => $alerts, 'count' => count($alerts)]);
    });

    Route::get('compliance', function () {
        $securityService = app(App\Services\SecurityAuditService::class);
        $compliance = $securityService->generateComplianceReport();

        return response()->json(['status' => 'success', 'compliance' => $compliance]);
    });

    Route::get('threats', function () {
        $securityService = app(App\Services\SecurityAuditService::class);
        $threats = $securityService->monitorSuspiciousActivity();

        return response()->json(['status' => 'success', 'threats' => $threats]);
    });
});
