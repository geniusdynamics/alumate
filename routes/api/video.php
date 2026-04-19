<?php

use Illuminate\Support\Facades\Route;

// Video Calling Integration routes
Route::middleware('auth:sanctum')->group(function () {
    // Video Calls Management
    Route::apiResource('video-calls', App\Http\Controllers\Api\VideoCallController::class);
    Route::post('video-calls/{call}/join', [App\Http\Controllers\Api\VideoCallController::class, 'join']);
    Route::post('video-calls/{call}/leave', [App\Http\Controllers\Api\VideoCallController::class, 'leave']);
    Route::post('video-calls/{call}/end', [App\Http\Controllers\Api\VideoCallController::class, 'end']);
    Route::get('video-calls/upcoming', [App\Http\Controllers\Api\VideoCallController::class, 'upcoming']);
    Route::get('video-calls/active', [App\Http\Controllers\Api\VideoCallController::class, 'active']);

    // Coffee Chat System
    Route::get('coffee-chat/suggestions', [App\Http\Controllers\Api\CoffeeChatController::class, 'suggestions']);
    Route::post('coffee-chat/request', [App\Http\Controllers\Api\CoffeeChatController::class, 'request']);
    Route::post('coffee-chat/{coffeeChatRequest}/respond', [App\Http\Controllers\Api\CoffeeChatController::class, 'respond']);
    Route::get('coffee-chat/my-requests', [App\Http\Controllers\Api\CoffeeChatController::class, 'myRequests']);
    Route::get('coffee-chat/received-requests', [App\Http\Controllers\Api\CoffeeChatController::class, 'receivedRequests']);
    Route::get('coffee-chat/ai-matches', [App\Http\Controllers\Api\CoffeeChatController::class, 'aiMatches']);
});
