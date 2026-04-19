<?php

use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\ReunionController;
use Illuminate\Support\Facades\Route;

// Events routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('events', EventsController::class)->names([
        'index' => 'api.events.index',
        'store' => 'api.events.store',
        'show' => 'api.events.show',
        'update' => 'api.events.update',
        'destroy' => 'api.events.destroy',
    ]);
    Route::post('events/{event}/register', [EventsController::class, 'register']);
    Route::delete('events/{event}/register', [EventsController::class, 'cancelRegistration']);
    Route::post('events/{event}/checkin', [EventsController::class, 'checkIn']);
    Route::get('events/{event}/attendees', [EventsController::class, 'attendees']);
    Route::get('events/{event}/analytics', [EventsController::class, 'analytics']);
    Route::get('events-upcoming', [EventsController::class, 'upcoming']);
    Route::get('events-recommended', [EventsController::class, 'recommended']);

    // Event follow-up and networking routes
    Route::post('events/{event}/feedback', [\App\Http\Controllers\Api\EventFollowUpController::class, 'submitFeedback']);
    Route::get('events/{event}/feedback-analytics', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getFeedbackAnalytics']);
    Route::post('events/{event}/highlights', [\App\Http\Controllers\Api\EventFollowUpController::class, 'createHighlight']);
    Route::get('events/{event}/highlights', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getHighlights']);
    Route::post('highlights/{highlight}/interact', [\App\Http\Controllers\Api\EventFollowUpController::class, 'interactWithHighlight']);
    Route::post('highlights/{highlight}/toggle-feature', [\App\Http\Controllers\Api\EventFollowUpController::class, 'toggleHighlightFeature']);
    Route::post('events/{event}/connections', [\App\Http\Controllers\Api\EventFollowUpController::class, 'createConnection']);
    Route::get('events/{event}/connections', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getConnections']);
    Route::post('events/{event}/generate-recommendations', [\App\Http\Controllers\Api\EventFollowUpController::class, 'generateRecommendations']);
    Route::get('events/{event}/recommendations', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getRecommendations']);
    Route::post('recommendations/{recommendation}/act', [\App\Http\Controllers\Api\EventFollowUpController::class, 'actOnRecommendation']);
    Route::post('recommendations/{recommendation}/viewed', [\App\Http\Controllers\Api\EventFollowUpController::class, 'markRecommendationViewed']);
    Route::get('events/{event}/follow-up-activities', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getFollowUpActivities']);
    Route::get('events/{event}/follow-up-analytics', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getFollowUpAnalytics']);
});

// Reunion routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('reunions', [ReunionController::class, 'index']);
    Route::post('reunions', [ReunionController::class, 'store']);
    Route::get('reunions/milestones', [ReunionController::class, 'milestones']);
    Route::get('reunions/graduation-year/{year}', [ReunionController::class, 'byGraduationYear']);
    Route::get('reunions/{event}', [ReunionController::class, 'show']);
    Route::put('reunions/{event}', [ReunionController::class, 'update']);
    Route::get('reunions/{event}/statistics', [ReunionController::class, 'statistics']);

    // Photo sharing
    Route::get('reunions/{event}/photos', [ReunionController::class, 'photos']);
    Route::post('reunions/{event}/photos', [ReunionController::class, 'uploadPhoto']);
    Route::post('reunion-photos/{photo}/like', [ReunionController::class, 'likePhoto']);
    Route::delete('reunion-photos/{photo}/like', [ReunionController::class, 'unlikePhoto']);
    Route::post('reunion-photos/{photo}/comments', [ReunionController::class, 'commentOnPhoto']);

    // Memory wall
    Route::get('reunions/{event}/memories', [ReunionController::class, 'memories']);
    Route::post('reunions/{event}/memories', [ReunionController::class, 'createMemory']);
    Route::post('reunion-memories/{memory}/like', [ReunionController::class, 'likeMemory']);
    Route::delete('reunion-memories/{memory}/like', [ReunionController::class, 'unlikeMemory']);
    Route::post('reunion-memories/{memory}/comments', [ReunionController::class, 'commentOnMemory']);

    // Committee management
    Route::get('reunions/{event}/committee', [ReunionController::class, 'committeeMembers']);
    Route::post('reunions/{event}/committee', [ReunionController::class, 'addCommitteeMember']);
    Route::delete('reunions/{event}/committee', [ReunionController::class, 'removeCommitteeMember']);
});

// Event RSVP routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('events/{event}/rsvp', [App\Http\Controllers\Api\EventController::class, 'rsvp']);
    Route::delete('events/{event}/rsvp', [App\Http\Controllers\Api\EventController::class, 'cancelRsvp']);
});
