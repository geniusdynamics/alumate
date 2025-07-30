<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TimelineController;
use App\Http\Controllers\Api\PostEngagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AlumniDirectoryController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\CareerTimelineController;
use App\Http\Controllers\Api\MentorshipController;
use App\Http\Controllers\Api\JobMatchingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Post routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::post('posts/media', [PostController::class, 'uploadMedia']);
    Route::post('posts/drafts', [PostController::class, 'saveDraft']);
    Route::get('posts/drafts', [PostController::class, 'getDrafts']);
    Route::get('posts/scheduled', [PostController::class, 'getScheduledPosts']);
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
    // Reactions
    Route::post('posts/{post}/react', [PostEngagementController::class, 'react']);
    Route::post('posts/{post}/unreact', [PostEngagementController::class, 'unreact']);
    
    // Comments
    Route::post('posts/{post}/comment', [PostEngagementController::class, 'comment']);
    Route::get('posts/{post}/comments', [PostEngagementController::class, 'getComments']);
    
    // Sharing
    Route::post('posts/{post}/share', [PostEngagementController::class, 'share']);
    
    // Bookmarks
    Route::post('posts/{post}/bookmark', [PostEngagementController::class, 'bookmark']);
    
    // Stats and users
    Route::get('posts/{post}/stats', [PostEngagementController::class, 'stats']);
    Route::get('posts/{post}/reactions/users', [PostEngagementController::class, 'reactionUsers']);
    
    // Mentions
    Route::get('posts/mentions/search', [PostEngagementController::class, 'searchMentions']);
});

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

// Alumni Directory routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('alumni', [AlumniDirectoryController::class, 'index']);
    Route::get('alumni/filters', [AlumniDirectoryController::class, 'filters']);
    Route::get('alumni/search', [AlumniDirectoryController::class, 'search']);
    Route::get('alumni/{userId}', [AlumniDirectoryController::class, 'show']);
    Route::post('alumni/{userId}/connect', [AlumniDirectoryController::class, 'connect']);
});

// Alumni Recommendations routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'index']);
    Route::post('recommendations/{userId}/dismiss', [\App\Http\Controllers\Api\RecommendationController::class, 'dismiss']);
    Route::post('recommendations/{userId}/feedback', [\App\Http\Controllers\Api\RecommendationController::class, 'feedback']);
    Route::post('recommendations/refresh', [\App\Http\Controllers\Api\RecommendationController::class, 'refresh']);
});

// Advanced Search routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('search', [SearchController::class, 'search']);
    Route::get('search/suggestions', [SearchController::class, 'suggestions']);
    Route::post('search/save', [SearchController::class, 'saveSearch']);
    Route::get('search/saved', [SearchController::class, 'getSavedSearches']);
    Route::delete('search/saved/{searchId}', [SearchController::class, 'deleteSavedSearch']);
    Route::put('search/alerts/{alertId}', [SearchController::class, 'updateSearchAlert']);
});

// Career Timeline routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/{userId}/career', [CareerTimelineController::class, 'index']);
    Route::post('career', [CareerTimelineController::class, 'store']);
    Route::put('career/{id}', [CareerTimelineController::class, 'update']);
    Route::delete('career/{id}', [CareerTimelineController::class, 'destroy']);
    Route::post('milestones', [CareerTimelineController::class, 'addMilestone']);
    Route::put('milestones/{id}', [CareerTimelineController::class, 'updateMilestone']);
    Route::delete('milestones/{id}', [CareerTimelineController::class, 'destroyMilestone']);
    Route::get('career/suggestions', [CareerTimelineController::class, 'suggestions']);
    Route::get('career/options', [CareerTimelineController::class, 'options']);
});

// Mentorship routes
Route::middleware('auth:sanctum')->group(function () {
    // Mentor profile management
    Route::post('mentorships/become-mentor', [MentorshipController::class, 'becomeMentor']);
    Route::get('mentorships/profile', [MentorshipController::class, 'getMentorProfile']);
    Route::put('mentorships/profile', [MentorshipController::class, 'updateMentorProfile']);
    Route::get('mentorships/analytics', [MentorshipController::class, 'getMentorAnalytics']);
    
    // Mentor discovery and matching
    Route::get('mentorships/find-mentors', [MentorshipController::class, 'findMentors']);
    
    // Mentorship requests
    Route::post('mentorships/request', [MentorshipController::class, 'requestMentorship']);
    Route::post('mentorships/requests/{requestId}/accept', [MentorshipController::class, 'acceptRequest']);
    Route::post('mentorships/requests/{requestId}/decline', [MentorshipController::class, 'declineRequest']);
    Route::get('mentorships', [MentorshipController::class, 'getMentorships']);
    
    // Session management
    Route::post('mentorships/sessions', [MentorshipController::class, 'scheduleSession']);
    Route::get('mentorships/sessions/upcoming', [MentorshipController::class, 'getUpcomingSessions']);
    Route::post('mentorships/sessions/{sessionId}/complete', [MentorshipController::class, 'completeSession']);
});

// Job Matching routes
Route::middleware('auth:sanctum')->group(function () {
    // Job recommendations and details
    Route::get('jobs/recommendations', [JobMatchingController::class, 'getRecommendations']);
    Route::get('jobs/{jobId}', [JobMatchingController::class, 'getJobDetails']);
    Route::get('jobs/{jobId}/connections', [JobMatchingController::class, 'getJobConnections']);
    
    // Job applications
    Route::post('jobs/{jobId}/apply', [JobMatchingController::class, 'apply']);
    Route::get('applications', [JobMatchingController::class, 'getApplications']);
    
    // Introduction requests
    Route::post('jobs/{jobId}/request-introduction', [JobMatchingController::class, 'requestIntroduction']);
});