<?php

use Illuminate\Support\Facades\Route;

// Scholarship routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('scholarships', App\Http\Controllers\Api\ScholarshipController::class)->names([
        'index' => 'api.scholarships.index',
        'store' => 'api.scholarships.store',
        'show' => 'api.scholarships.show',
        'update' => 'api.scholarships.update',
        'destroy' => 'api.scholarships.destroy',
    ]);
    Route::get('scholarships/{scholarship}/impact-report', [App\Http\Controllers\Api\ScholarshipController::class, 'impactReport']);
    Route::get('user/donor-updates', [App\Http\Controllers\Api\ScholarshipController::class, 'donorUpdates']);

    // Scholarship Applications
    Route::apiResource('scholarships.applications', App\Http\Controllers\Api\ScholarshipApplicationController::class)->names([
        'index' => 'api.scholarships.applications.index',
        'store' => 'api.scholarships.applications.store',
        'show' => 'api.scholarships.applications.show',
        'update' => 'api.scholarships.applications.update',
        'destroy' => 'api.scholarships.applications.destroy',
    ]);
    Route::post('scholarships/{scholarship}/applications/{application}/review', [App\Http\Controllers\Api\ScholarshipApplicationController::class, 'review']);
    Route::post('scholarships/{scholarship}/applications/{application}/award', [App\Http\Controllers\Api\ScholarshipApplicationController::class, 'award']);

    // Scholarship Recipients
    Route::apiResource('scholarships.recipients', App\Http\Controllers\Api\ScholarshipRecipientController::class)->only(['index', 'show', 'update'])->names([
        'index' => 'api.scholarships.recipients.index',
        'show' => 'api.scholarships.recipients.show',
        'update' => 'api.scholarships.recipients.update',
    ]);
    Route::get('scholarship-recipients/success-stories', [App\Http\Controllers\Api\ScholarshipRecipientController::class, 'successStories']);
});
