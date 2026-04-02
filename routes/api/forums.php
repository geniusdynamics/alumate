<?php

use Illuminate\Support\Facades\Route;

// Discussion Forums routes
Route::middleware('auth:sanctum')->group(function () {
    // Forum management
    Route::apiResource('forums', App\Http\Controllers\Api\ForumController::class);

    // Topic management
    Route::apiResource('forums.topics', App\Http\Controllers\Api\ForumTopicController::class);
    Route::post('forums/{forum}/topics/{topic}/subscribe', [App\Http\Controllers\Api\ForumTopicController::class, 'toggleSubscription']);

    // Post management
    Route::apiResource('topics.posts', App\Http\Controllers\Api\ForumPostController::class)->except(['index']);
    Route::post('posts/{post}/like', [App\Http\Controllers\Api\ForumPostController::class, 'toggleLike']);
    Route::post('posts/{post}/solution', [App\Http\Controllers\Api\ForumPostController::class, 'markAsSolution']);

    // Forum search and discovery
    Route::get('forums/search', [App\Http\Controllers\Api\ForumSearchController::class, 'search']);
    Route::get('forums/tags', [App\Http\Controllers\Api\ForumSearchController::class, 'getTags']);
    Route::get('forums/tags/{tag}/topics', [App\Http\Controllers\Api\ForumSearchController::class, 'getTopicsByTag']);

    // Forum moderation
    Route::post('forums/moderate/{type}/{id}', [App\Http\Controllers\Api\ForumModerationController::class, 'moderate']);
    Route::get('forums/moderation/pending', [App\Http\Controllers\Api\ForumModerationController::class, 'getPending']);
    Route::get('forums/analytics', [App\Http\Controllers\Api\ForumAnalyticsController::class, 'getStatistics']);
});
