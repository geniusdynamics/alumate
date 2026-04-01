<?php

use Illuminate\Support\Facades\Route;

// Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::get('notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'getPreferences']);
    Route::put('notifications/preferences', [\App\Http\Controllers\Api\NotificationController::class, 'updatePreferences']);
    Route::get('notifications/stats', [\App\Http\Controllers\Api\NotificationController::class, 'stats']);
    Route::delete('notifications/{notification}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);
    Route::post('notifications/test', [\App\Http\Controllers\Api\NotificationController::class, 'test']);
});
