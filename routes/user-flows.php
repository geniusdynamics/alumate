<?php

use Illuminate\Support\Facades\Route;

// User Flow Integration API Routes
Route::middleware(['auth'])->prefix('api/user-flows')->name('api.user-flows.')->group(function () {

    // Social Flow Endpoints
    Route::prefix('social')->name('social.')->group(function () {
        Route::post('posts', [\App\Http\Controllers\Api\PostController::class, 'store'])->name('posts.store');
        Route::put('posts/{post}', [\App\Http\Controllers\Api\PostController::class, 'update'])->name('posts.update');
        Route::delete('posts/{post}', [\App\Http\Controllers\Api\PostController::class, 'destroy'])->name('posts.destroy');
        Route::post('posts/{post}/react', [\App\Http\Controllers\Api\PostEngagementController::class, 'react'])->name('posts.react');
        Route::post('posts/{post}/comments', [\App\Http\Controllers\Api\PostEngagementController::class, 'comment'])->name('posts.comment');
        Route::post('posts/{post}/share', [\App\Http\Controllers\Api\PostEngagementController::class, 'share'])->name('posts.share');
        Route::post('posts/{post}/bookmark', [\App\Http\Controllers\Api\PostEngagementController::class, 'bookmark'])->name('posts.bookmark');
        Route::post('posts/media', [\App\Http\Controllers\Api\PostController::class, 'uploadMedia'])->name('posts.media');
        Route::post('posts/drafts', [\App\Http\Controllers\Api\PostController::class, 'saveDraft'])->name('posts.drafts');
    });

    // Alumni Networking Flow Endpoints
    Route::prefix('alumni')->name('alumni.')->group(function () {
        Route::post('connections/request', [\App\Http\Controllers\Api\ConnectionController::class, 'request'])->name('connections.request');
        Route::post('connections/{connection}/accept', [\App\Http\Controllers\Api\ConnectionController::class, 'accept'])->name('connections.accept');
        Route::post('connections/{connection}/decline', [\App\Http\Controllers\Api\ConnectionController::class, 'decline'])->name('connections.decline');
        Route::delete('connections/{connection}', [\App\Http\Controllers\Api\ConnectionController::class, 'remove'])->name('connections.remove');
        Route::get('recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'alumni'])->name('recommendations');
        Route::get('search', [\App\Http\Controllers\Api\SearchController::class, 'alumni'])->name('search');
        Route::get('{alumni}/profile', [\App\Http\Controllers\Api\AlumniDirectoryController::class, 'profile'])->name('profile');
    });

    // Career Services Flow Endpoints
    Route::prefix('career')->name('career.')->group(function () {
        Route::post('timeline', [\App\Http\Controllers\Api\CareerTimelineController::class, 'store'])->name('timeline.store');
        Route::put('timeline/{timeline}', [\App\Http\Controllers\Api\CareerTimelineController::class, 'update'])->name('timeline.update');
        Route::delete('timeline/{timeline}', [\App\Http\Controllers\Api\CareerTimelineController::class, 'destroy'])->name('timeline.destroy');
        Route::post('milestones', [\App\Http\Controllers\Api\CareerTimelineController::class, 'storeMilestone'])->name('milestones.store');
        Route::post('goals', [\App\Http\Controllers\Api\CareerController::class, 'storeGoal'])->name('goals.store');
        Route::put('goals/{goal}', [\App\Http\Controllers\Api\CareerController::class, 'updateGoal'])->name('goals.update');
        Route::delete('goals/{goal}', [\App\Http\Controllers\Api\CareerController::class, 'destroyGoal'])->name('goals.destroy');
        Route::post('goals/{goal}/complete', [\App\Http\Controllers\Api\CareerController::class, 'completeGoal'])->name('goals.complete');
    });

    // Job Matching Flow Endpoints
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('recommendations', [\App\Http\Controllers\Api\JobMatchingController::class, 'recommendations'])->name('recommendations');
        Route::post('{job}/save', [\App\Http\Controllers\Api\JobController::class, 'save'])->name('save');
        Route::delete('{job}/unsave', [\App\Http\Controllers\Api\JobController::class, 'unsave'])->name('unsave');
        Route::post('{job}/apply', [\App\Http\Controllers\Api\JobController::class, 'apply'])->name('apply');
        Route::get('{job}/insights', [\App\Http\Controllers\Api\JobMatchingController::class, 'insights'])->name('insights');
        Route::get('{job}/connections', [\App\Http\Controllers\Api\JobMatchingController::class, 'connections'])->name('connections');
        Route::post('introductions/request', [\App\Http\Controllers\Api\JobMatchingController::class, 'requestIntroduction'])->name('introductions.request');
    });

    // Events Flow Endpoints
    Route::prefix('events')->name('events.')->group(function () {
        Route::post('{event}/register', [\App\Http\Controllers\Api\EventController::class, 'register'])->name('register');
        Route::delete('{event}/unregister', [\App\Http\Controllers\Api\EventController::class, 'unregister'])->name('unregister');
        Route::post('{event}/favorite', [\App\Http\Controllers\Api\EventController::class, 'favorite'])->name('favorite');
        Route::delete('{event}/unfavorite', [\App\Http\Controllers\Api\EventController::class, 'unfavorite'])->name('unfavorite');
        Route::post('{event}/feedback', [\App\Http\Controllers\Api\EventFollowUpController::class, 'submitFeedback'])->name('feedback');
        Route::get('{event}/connections', [\App\Http\Controllers\Api\EventFollowUpController::class, 'getConnections'])->name('connections');
        Route::post('{event}/networking', [\App\Http\Controllers\Api\EventFollowUpController::class, 'requestNetworking'])->name('networking');
    });

    // Mentorship Flow Endpoints
    Route::prefix('mentorship')->name('mentorship.')->group(function () {
        Route::post('become-mentor', [\App\Http\Controllers\Api\MentorshipController::class, 'becomeMentor'])->name('become-mentor');
        Route::post('request', [\App\Http\Controllers\Api\MentorshipController::class, 'requestMentorship'])->name('request');
        Route::post('{request}/accept', [\App\Http\Controllers\Api\MentorshipController::class, 'acceptRequest'])->name('accept');
        Route::post('{request}/decline', [\App\Http\Controllers\Api\MentorshipController::class, 'declineRequest'])->name('decline');
        Route::post('sessions', [\App\Http\Controllers\Api\MentorshipController::class, 'scheduleSession'])->name('schedule-session');
        Route::get('recommendations', [\App\Http\Controllers\Api\MentorshipController::class, 'getMentorRecommendations'])->name('recommendations');
    });

    // Reunion Flow Endpoints
    Route::prefix('reunions')->name('reunions.')->group(function () {
        Route::post('{reunion}/rsvp', [\App\Http\Controllers\Api\ReunionController::class, 'rsvp'])->name('rsvp');
        Route::post('{reunion}/favorite', [\App\Http\Controllers\Api\ReunionController::class, 'favorite'])->name('favorite');
        Route::delete('{reunion}/unfavorite', [\App\Http\Controllers\Api\ReunionController::class, 'unfavorite'])->name('unfavorite');
        Route::post('{reunion}/memories', [\App\Http\Controllers\Api\ReunionController::class, 'addMemory'])->name('memories.add');
        Route::get('{reunion}/photos', [\App\Http\Controllers\Api\ReunionController::class, 'getPhotos'])->name('photos');
    });

    // Skills Development Flow Endpoints
    Route::prefix('skills')->name('skills.')->group(function () {
        Route::post('endorse', [\App\Http\Controllers\Api\SkillsController::class, 'endorse'])->name('endorse');
        Route::post('add', [\App\Http\Controllers\Api\SkillsController::class, 'addSkill'])->name('add');
        Route::delete('{skill}', [\App\Http\Controllers\Api\SkillsController::class, 'removeSkill'])->name('remove');
        Route::get('recommendations', [\App\Http\Controllers\Api\SkillsController::class, 'getRecommendations'])->name('recommendations');
        Route::get('assessment', [\App\Http\Controllers\Api\SkillsController::class, 'getAssessment'])->name('assessment');
        Route::post('assessment', [\App\Http\Controllers\Api\SkillsController::class, 'submitAssessment'])->name('assessment.submit');
    });

    // Success Stories Flow Endpoints
    Route::prefix('stories')->name('stories.')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\SuccessStoryController::class, 'store'])->name('store');
        Route::put('{story}', [\App\Http\Controllers\Api\SuccessStoryController::class, 'update'])->name('update');
        Route::delete('{story}', [\App\Http\Controllers\Api\SuccessStoryController::class, 'destroy'])->name('destroy');
        Route::post('{story}/feature', [\App\Http\Controllers\Api\SuccessStoryController::class, 'feature'])->name('feature');
        Route::post('{story}/share', [\App\Http\Controllers\Api\SuccessStoryController::class, 'share'])->name('share');
    });

    // Achievement Flow Endpoints
    Route::prefix('achievements')->name('achievements.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AchievementController::class, 'index'])->name('index');
        Route::post('celebrate', [\App\Http\Controllers\Api\AchievementCelebrationController::class, 'celebrate'])->name('celebrate');
        Route::get('milestones', [\App\Http\Controllers\Api\AchievementController::class, 'getMilestones'])->name('milestones');
        Route::post('milestones/detect', [\App\Http\Controllers\Api\AchievementController::class, 'detectMilestones'])->name('milestones.detect');
    });

    // Notification Flow Endpoints
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index'])->name('index');
        Route::post('{notification}/mark-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'getUnreadCount'])->name('unread-count');
        Route::put('preferences', [\App\Http\Controllers\Api\NotificationController::class, 'updatePreferences'])->name('preferences.update');
    });

    // Dashboard Integration Endpoints
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('feed', [\App\Http\Controllers\Api\DashboardController::class, 'getFeed'])->name('feed');
        Route::get('recommendations', [\App\Http\Controllers\Api\DashboardController::class, 'getRecommendations'])->name('recommendations');
        Route::get('activity', [\App\Http\Controllers\Api\DashboardController::class, 'getActivity'])->name('activity');
        Route::get('insights', [\App\Http\Controllers\Api\DashboardController::class, 'getInsights'])->name('insights');
        Route::post('quick-actions', [\App\Http\Controllers\Api\DashboardController::class, 'executeQuickAction'])->name('quick-actions');
    });

    // Search Integration Endpoints
    Route::prefix('search')->name('search.')->group(function () {
        Route::get('global', [\App\Http\Controllers\Api\SearchController::class, 'globalSearch'])->name('global');
        Route::get('alumni', [\App\Http\Controllers\Api\SearchController::class, 'searchAlumni'])->name('alumni');
        Route::get('jobs', [\App\Http\Controllers\Api\SearchController::class, 'searchJobs'])->name('jobs');
        Route::get('events', [\App\Http\Controllers\Api\SearchController::class, 'searchEvents'])->name('events');
        Route::get('posts', [\App\Http\Controllers\Api\SearchController::class, 'searchPosts'])->name('posts');
        Route::post('save', [\App\Http\Controllers\Api\SearchController::class, 'saveSearch'])->name('save');
        Route::get('saved', [\App\Http\Controllers\Api\SearchController::class, 'getSavedSearches'])->name('saved');
    });
});

// Real-time Integration Routes
Route::middleware(['auth'])->prefix('api/realtime')->name('api.realtime.')->group(function () {
    Route::post('connect', [\App\Http\Controllers\Api\RealTimeController::class, 'connect'])->name('connect');
    Route::post('disconnect', [\App\Http\Controllers\Api\RealTimeController::class, 'disconnect'])->name('disconnect');
    Route::get('status', [\App\Http\Controllers\Api\RealTimeController::class, 'getStatus'])->name('status');
    Route::post('broadcast', [\App\Http\Controllers\Api\RealTimeController::class, 'broadcast'])->name('broadcast');
});

// Analytics Integration Routes
Route::middleware(['auth'])->prefix('api/analytics')->name('api.analytics.')->group(function () {
    Route::post('track-event', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackEvent'])->name('track-event');
    Route::post('track-page-view', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackPageView'])->name('track-page-view');
    Route::post('track-user-action', [\App\Http\Controllers\Api\AnalyticsController::class, 'trackUserAction'])->name('track-user-action');
    Route::get('user-insights', [\App\Http\Controllers\Api\AnalyticsController::class, 'getUserInsights'])->name('user-insights');
});
