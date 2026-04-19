<?php

use Illuminate\Support\Facades\Route;

// Messaging System routes
Route::middleware('auth:sanctum')->group(function () {
    // Conversations
    Route::get('conversations', [App\Http\Controllers\Api\ConversationController::class, 'index']);
    Route::get('conversations/{conversationId}', [App\Http\Controllers\Api\ConversationController::class, 'show']);
    Route::post('conversations/direct', [App\Http\Controllers\Api\ConversationController::class, 'createDirect']);
    Route::post('conversations/group', [App\Http\Controllers\Api\ConversationController::class, 'createGroup']);
    Route::post('conversations/circle', [App\Http\Controllers\Api\ConversationController::class, 'createCircle']);

    // Conversation management
    Route::post('conversations/{conversationId}/participants', [App\Http\Controllers\Api\ConversationController::class, 'addParticipant']);
    Route::delete('conversations/{conversationId}/participants/{userId}', [App\Http\Controllers\Api\ConversationController::class, 'removeParticipant']);
    Route::post('conversations/{conversationId}/leave', [App\Http\Controllers\Api\ConversationController::class, 'leave']);
    Route::post('conversations/{conversationId}/archive', [App\Http\Controllers\Api\ConversationController::class, 'archive']);
    Route::post('conversations/{conversationId}/mute', [App\Http\Controllers\Api\ConversationController::class, 'toggleMute']);
    Route::post('conversations/{conversationId}/pin', [App\Http\Controllers\Api\ConversationController::class, 'togglePin']);

    // Messages
    Route::post('messages', [App\Http\Controllers\Api\MessagingController::class, 'sendMessage']);
    Route::post('messages/{messageId}/read', [App\Http\Controllers\Api\MessagingController::class, 'markAsRead']);
    Route::post('conversations/{conversationId}/read', [App\Http\Controllers\Api\MessagingController::class, 'markConversationAsRead']);
    Route::post('messages/typing', [App\Http\Controllers\Api\MessagingController::class, 'typing']);
    Route::get('messages/search', [App\Http\Controllers\Api\MessagingController::class, 'search']);
    Route::put('messages/{messageId}', [App\Http\Controllers\Api\MessagingController::class, 'editMessage']);
    Route::delete('messages/{messageId}', [App\Http\Controllers\Api\MessagingController::class, 'deleteMessage']);
    Route::get('messages/unread-count', [App\Http\Controllers\Api\MessagingController::class, 'getUnreadCount']);
});
