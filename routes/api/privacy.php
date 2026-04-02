<?php

use Illuminate\Support\Facades\Route;

// Privacy API routes (GDPR/CCPA compliance)
Route::middleware('auth:sanctum')->prefix('privacy')->group(function () {
    Route::post('consent', [App\Http\Controllers\PrivacyController::class, 'updateConsent']);
    Route::delete('data', [App\Http\Controllers\PrivacyController::class, 'deleteUserData']);
    Route::get('report', [App\Http\Controllers\PrivacyController::class, 'getComplianceReport']);
    Route::get('preferences', [App\Http\Controllers\PrivacyController::class, 'getConsentPreferences']);
    Route::get('export', [App\Http\Controllers\PrivacyController::class, 'exportUserData']);

    // Enhanced privacy routes for analytics compliance
    Route::post('consent/grant/{type}', [App\Http\Controllers\Analytics\PrivacyController::class, 'grantConsent']);
    Route::post('consent/revoke/{type}', [App\Http\Controllers\Analytics\PrivacyController::class, 'revokeConsent']);
    Route::get('export', [App\Http\Controllers\Analytics\PrivacyController::class, 'export']);
    Route::post('opt-out-ccpa', [App\Http\Controllers\Analytics\PrivacyController::class, 'optOut']);
    Route::get('audit-logs', [App\Http\Controllers\Analytics\PrivacyController::class, 'auditLogs']);

    // Extended Privacy Controls
    Route::get('settings', [App\Http\Controllers\Analytics\PrivacyController::class, 'index']);
    Route::get('users/{userId}', [App\Http\Controllers\Analytics\PrivacyController::class, 'show']);
    Route::put('users/{userId}', [App\Http\Controllers\Analytics\PrivacyController::class, 'update']);
    Route::post('consent', [App\Http\Controllers\Analytics\PrivacyController::class, 'consent']);
    Route::delete('consent', [App\Http\Controllers\Analytics\PrivacyController::class, 'revokeConsent']);
    Route::get('users/{userId}/consent', [App\Http\Controllers\Analytics\PrivacyController::class, 'getConsentStatus']);
    Route::get('users/{userId}/export', [App\Http\Controllers\Analytics\PrivacyController::class, 'exportData']);
    Route::delete('users/{userId}', [App\Http\Controllers\Analytics\PrivacyController::class, 'deleteData']);
    Route::post('users/{userId}/anonymize', [App\Http\Controllers\Analytics\PrivacyController::class, 'anonymizeData']);
});

// Consent API routes for analytics
Route::middleware('auth:sanctum')->prefix('consent')->group(function () {
    Route::post('grant', function (\Illuminate\Http\Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), ['type' => 'required|in:analytics']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $consentService = app(\App\Services\Analytics\ConsentService::class);
        $success = $consentService->grantConsent(type: $request->input('type'));

        return response()->json(['success' => $success, 'message' => $success ? 'Consent granted successfully' : 'Failed to grant consent']);
    });

    Route::post('revoke', function (\Illuminate\Http\Request $request) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), ['type' => 'required|in:analytics']);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $consentService = app(\App\Services\Analytics\ConsentService::class);
        $success = $consentService->revokeConsent(type: $request->input('type'));

        return response()->json(['success' => $success, 'message' => $success ? 'Consent revoked successfully' : 'Failed to revoke consent']);
    });
});
