<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// User profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
});

// Push notification routes
Route::get('push/vapid-key', function () {
    return response()->json([
        'publicKey' => config('services.vapid.public_key', 'demo-key-for-development'),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('push/subscribe', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true]);
    });

    Route::post('push/unsubscribe', function (\Illuminate\Http\Request $request) {
        return response()->json(['success' => true]);
    });
});

// Onboarding routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('onboarding/state', [App\Http\Controllers\Api\OnboardingController::class, 'getOnboardingState']);
    Route::post('onboarding/state', [App\Http\Controllers\Api\OnboardingController::class, 'updateOnboardingState']);
    Route::get('onboarding/new-features', [App\Http\Controllers\Api\OnboardingController::class, 'getNewFeatures']);
    Route::get('onboarding/profile-completion', [App\Http\Controllers\Api\OnboardingController::class, 'getProfileCompletion']);
    Route::get('onboarding/whats-new', [App\Http\Controllers\Api\OnboardingController::class, 'getWhatsNew']);
    Route::post('onboarding/events', [App\Http\Controllers\Api\OnboardingController::class, 'recordEvent']);
    Route::post('user/interests', [App\Http\Controllers\Api\OnboardingController::class, 'saveUserInterests']);
    Route::get('onboarding/help/{elementId}', [App\Http\Controllers\Api\OnboardingController::class, 'getContextualHelp']);

    // Training & Documentation
    Route::prefix('training')->group(function () {
        Route::get('guides', [\App\Http\Controllers\UserTrainingController::class, 'getUserGuides']);
        Route::get('tutorials', [\App\Http\Controllers\UserTrainingController::class, 'getVideoTutorials']);
        Route::get('onboarding-sequence', [\App\Http\Controllers\UserTrainingController::class, 'getOnboardingSequence']);
        Route::get('faqs', [\App\Http\Controllers\UserTrainingController::class, 'getFAQs']);
        Route::get('progress', [\App\Http\Controllers\UserTrainingController::class, 'getTrainingProgress']);
        Route::post('mark-step-completed', [\App\Http\Controllers\UserTrainingController::class, 'markStepCompleted']);
        Route::get('search', [\App\Http\Controllers\UserTrainingController::class, 'search']);
        Route::post('faq-helpful', [\App\Http\Controllers\UserTrainingController::class, 'markFAQHelpful']);
        Route::post('feedback', [\App\Http\Controllers\UserTrainingController::class, 'submitFeedback']);
    });
});

// Alumni Verification routes
Route::middleware('auth:sanctum')->prefix('verification')->group(function () {
    Route::post('/submit', [App\Http\Controllers\VerificationController::class, 'submit']);
    Route::get('/status', [App\Http\Controllers\VerificationController::class, 'status']);
    Route::post('/upload-document', [App\Http\Controllers\VerificationController::class, 'uploadDocument']);
});

// Admin Verification routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('admin/verification')->group(function () {
    Route::get('/requests', [App\Http\Controllers\Admin\VerificationController::class, 'index']);
    Route::post('/requests/{requestId}/approve', [App\Http\Controllers\Admin\VerificationController::class, 'approve']);
    Route::post('/requests/{requestId}/reject', [App\Http\Controllers\Admin\VerificationController::class, 'reject']);
    Route::post('/bulk-import', [App\Http\Controllers\Admin\VerificationController::class, 'bulkImport']);
    Route::get('/analytics', [App\Http\Controllers\Admin\VerificationController::class, 'analytics']);
});
