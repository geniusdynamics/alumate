<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostEngagementController;
use App\Http\Controllers\Api\TimelineController;
use Illuminate\Support\Facades\Route;

// Post routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::post('posts/drafts', [PostController::class, 'saveDraft']);
    Route::get('posts/drafts', [PostController::class, 'getDrafts']);
    Route::get('posts/scheduled', [PostController::class, 'getScheduledPosts']);
});

// Media upload routes
Route::middleware(['auth:sanctum', 'api.rate_limit:upload'])->group(function () {
    Route::post('posts/media', [PostController::class, 'uploadMedia']);
});

// Timeline routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('timeline', [TimelineController::class, 'index']);
    Route::get('timeline/refresh', [TimelineController::class, 'refresh']);
    Route::get('timeline/load-more', [TimelineController::class, 'loadMore']);
    Route::get('timeline/circles', [TimelineController::class, 'circles']);
    Route::get('timeline/groups', [TimelineController::class, 'groups']);
});

// Post Engagement routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('posts/{post}/like', [PostEngagementController::class, 'like']);
    Route::post('posts/{post}/comment', [PostEngagementController::class, 'comment']);
    Route::post('posts/{post}/share', [PostEngagementController::class, 'share']);
    Route::post('posts/{post}/reaction', [PostEngagementController::class, 'reaction']);
    Route::get('posts/{post}/stats', [PostEngagementController::class, 'stats']);
});

// Connection routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('connections/request', [App\Http\Controllers\Api\ConnectionController::class, 'sendRequest']);
    Route::post('connections/{connection}/accept', [App\Http\Controllers\Api\ConnectionController::class, 'acceptRequest']);
    Route::post('connections/{connection}/decline', [App\Http\Controllers\Api\ConnectionController::class, 'declineRequest']);
    Route::get('connections', [App\Http\Controllers\Api\ConnectionController::class, 'index']);
    Route::get('connections/requests', [App\Http\Controllers\Api\ConnectionController::class, 'requests']);
});

// Dashboard Widget routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/social-activity', [App\Http\Controllers\Api\DashboardController::class, 'socialActivity']);
    Route::get('dashboard/alumni-suggestions', [App\Http\Controllers\Api\DashboardController::class, 'alumniSuggestions']);
    Route::get('dashboard/job-recommendations', [App\Http\Controllers\Api\DashboardController::class, 'jobRecommendations']);
    Route::get('dashboard/upcoming-events', [App\Http\Controllers\Api\DashboardController::class, 'upcomingEvents']);
});
