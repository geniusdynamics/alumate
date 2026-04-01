<?php

use Illuminate\Support\Facades\Route;

// Donor CRM routes
Route::middleware('auth:sanctum')->group(function () {
    // Donor Profile Management
    Route::apiResource('donor-profiles', App\Http\Controllers\Api\DonorProfileController::class);
    Route::get('donor-profiles/dashboard', [App\Http\Controllers\Api\DonorProfileController::class, 'dashboard']);
    Route::get('donor-profiles/contacts-needing-attention', [App\Http\Controllers\Api\DonorProfileController::class, 'contactsNeedingAttention']);
    Route::post('donor-profiles/bulk-update', [App\Http\Controllers\Api\DonorProfileController::class, 'bulkUpdate']);

    // Donor Interactions
    Route::apiResource('donor-interactions', App\Http\Controllers\Api\DonorInteractionController::class);
    Route::get('donor-interactions/follow-up-reminders', [App\Http\Controllers\Api\DonorInteractionController::class, 'followUpReminders']);

    // Donor Stewardship Plans
    Route::apiResource('donor-stewardship-plans', App\Http\Controllers\Api\DonorStewardshipPlanController::class);
    Route::post('donor-stewardship-plans/{donorStewardshipPlan}/milestone-complete', [App\Http\Controllers\Api\DonorStewardshipPlanController::class, 'markMilestoneComplete']);
    Route::get('donor-stewardship-plans/upcoming-asks', [App\Http\Controllers\Api\DonorStewardshipPlanController::class, 'upcomingAsks']);

    // Major Gift Prospects
    Route::apiResource('major-gift-prospects', App\Http\Controllers\Api\MajorGiftProspectController::class);
    Route::post('major-gift-prospects/{majorGiftProspect}/next-stage', [App\Http\Controllers\Api\MajorGiftProspectController::class, 'moveToNextStage']);
    Route::post('major-gift-prospects/{majorGiftProspect}/close-won', [App\Http\Controllers\Api\MajorGiftProspectController::class, 'closeAsWon']);
    Route::post('major-gift-prospects/{majorGiftProspect}/close-lost', [App\Http\Controllers\Api\MajorGiftProspectController::class, 'closeAsLost']);
    Route::get('major-gift-prospects/pipeline', [App\Http\Controllers\Api\MajorGiftProspectController::class, 'pipeline']);
    Route::get('major-gift-prospects/closing-soon', [App\Http\Controllers\Api\MajorGiftProspectController::class, 'closingSoon']);
});
