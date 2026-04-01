<?php

use Illuminate\Support\Facades\Route;

// Email Marketing routes
Route::middleware('auth:sanctum')->group(function () {
    // Email Campaigns
    Route::apiResource('email-campaigns', App\Http\Controllers\Api\EmailCampaignController::class);
    Route::post('email-campaigns/{campaign}/send', [App\Http\Controllers\Api\EmailCampaignController::class, 'send']);
    Route::post('email-campaigns/{campaign}/schedule', [App\Http\Controllers\Api\EmailCampaignController::class, 'schedule']);
    Route::post('email-campaigns/{campaign}/ab-test', [App\Http\Controllers\Api\EmailCampaignController::class, 'createAbTest']);
    Route::post('email-campaigns/{campaign}/preview', [App\Http\Controllers\Api\EmailCampaignController::class, 'preview']);
    Route::get('email-campaigns/{campaign}/recipients', [App\Http\Controllers\Api\EmailCampaignController::class, 'recipients']);

    // Email Templates
    Route::get('email-templates', [App\Http\Controllers\Api\EmailCampaignController::class, 'templates']);

    // Email Automation
    Route::get('email-automation-rules', [App\Http\Controllers\Api\EmailCampaignController::class, 'automationRules']);
    Route::post('email-automation-rules', [App\Http\Controllers\Api\EmailCampaignController::class, 'createAutomationRule']);

    // Email Analytics
    Route::get('email-campaigns/analytics', [App\Http\Controllers\Api\EmailCampaignController::class, 'analytics']);
});

// Email Sequence Management routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('email-sequences')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\EmailSequenceController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\EmailSequenceController::class, 'store']);
    Route::get('/{sequence}', [App\Http\Controllers\Api\EmailSequenceController::class, 'show']);
    Route::put('/{sequence}', [App\Http\Controllers\Api\EmailSequenceController::class, 'update']);
    Route::delete('/{sequence}', [App\Http\Controllers\Api\EmailSequenceController::class, 'destroy']);
    Route::post('/{sequence}/duplicate', [App\Http\Controllers\Api\EmailSequenceController::class, 'duplicate']);
    Route::post('/{sequence}/toggle-active', [App\Http\Controllers\Api\EmailSequenceController::class, 'toggleActive']);
    Route::get('/{sequence}/emails', [App\Http\Controllers\Api\EmailSequenceController::class, 'getEmails']);
    Route::post('/{sequence}/emails', [App\Http\Controllers\Api\EmailSequenceController::class, 'addEmail']);
    Route::put('/{sequence}/emails/{email}', [App\Http\Controllers\Api\EmailSequenceController::class, 'updateEmail']);
    Route::delete('/{sequence}/emails/{email}', [App\Http\Controllers\Api\EmailSequenceController::class, 'removeEmail']);
    Route::get('/{sequence}/enrollments', [App\Http\Controllers\Api\EmailSequenceController::class, 'getEnrollments']);
    Route::post('/{sequence}/enroll', [App\Http\Controllers\Api\EmailSequenceController::class, 'enroll']);
    Route::delete('/{sequence}/unenroll/{userId}', [App\Http\Controllers\Api\EmailSequenceController::class, 'unenroll']);
});
