<?php

use App\Http\Controllers\Api\AchievementCelebrationController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\AlumniDirectoryController;
use App\Http\Controllers\Api\CareerTimelineController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\JobMatchingController;
use App\Http\Controllers\Api\MentorshipController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostEngagementController;
use App\Http\Controllers\Api\ReunionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SkillsController;
use App\Http\Controllers\Api\SpeakerBureauController;
use App\Http\Controllers\Api\StudentAlumniStoryController;
use App\Http\Controllers\Api\StudentCareerGuidanceController;
use App\Http\Controllers\Api\StudentMentorshipController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\TimelineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test route for social rate limiting
Route::post('/test/social-action', function (Request $request) {
    return response()->json(['message' => 'Action completed']);
})->middleware(['auth:sanctum', 'social.rate_limit:post_interaction']);

// PWA Health Check
Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'message' => 'Alumni Platform API is online',
    ]);
});

// CRM Webhook routes (no auth required for webhooks)
Route::prefix('webhooks/crm')->group(function () {
    Route::post('hubspot', [App\Http\Controllers\Api\CrmWebhookController::class, 'hubspot']);
    Route::post('salesforce', [App\Http\Controllers\Api\CrmWebhookController::class, 'salesforce']);
    Route::post('pipedrive', [App\Http\Controllers\Api\CrmWebhookController::class, 'pipedrive']);
    Route::post('{provider}', [App\Http\Controllers\Api\CrmWebhookController::class, 'generic']);
});

// Statistics API routes
Route::prefix('statistics')->group(function () {
    Route::get('health', [App\Http\Controllers\Api\StatisticsController::class, 'health']);
    Route::get('platform-metrics', [App\Http\Controllers\Api\StatisticsController::class, 'platformMetrics']);
    Route::post('batch', [App\Http\Controllers\Api\StatisticsController::class, 'batch']);
    Route::get('{id}', [App\Http\Controllers\Api\StatisticsController::class, 'show']);
    
    // Admin routes
    Route::middleware(['auth:sanctum', 'can:manage-statistics'])->group(function () {
        Route::delete('cache', [App\Http\Controllers\Api\StatisticsController::class, 'clearCache']);
    });
});

// Homepage Navigation
Route::get('/homepage-navigation', [\App\Http\Controllers\Api\HomepageNavigationController::class, 'index']);

// PWA Push Notification routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('push/vapid-key', function () {
        return response()->json([
            'publicKey' => config('services.vapid.public_key', 'demo-key-for-development'),
        ]);
    });

    Route::post('push/subscribe', function (Request $request) {
        // In a real implementation, you'd save the subscription to the database
        // For now, just return success
        return response()->json(['success' => true]);
    });

    Route::post('push/unsubscribe', function (Request $request) {
        // In a real implementation, you'd remove the subscription from the database
        // For now, just return success
        return response()->json(['success' => true]);
    });
});

// Post routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::post('posts/drafts', [PostController::class, 'saveDraft']);
    Route::get('posts/drafts', [PostController::class, 'getDrafts']);
    Route::get('posts/scheduled', [PostController::class, 'getScheduledPosts']);
});

// Media upload routes (with stricter rate limiting)
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
    // Likes
    Route::post('posts/{post}/like', [PostEngagementController::class, 'like']);

    // Comments
    Route::post('posts/{post}/comment', [PostEngagementController::class, 'comment']);

    // Sharing
    Route::post('posts/{post}/share', [PostEngagementController::class, 'share']);

    // Reactions
    Route::post('posts/{post}/reaction', [PostEngagementController::class, 'reaction']);

    // Stats
    Route::get('posts/{post}/stats', [PostEngagementController::class, 'stats']);
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

// Alumni Map routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('alumni/map-data', [\App\Http\Controllers\Api\AlumniMapController::class, 'getMapData']);
    Route::post('alumni/map-clusters', [\App\Http\Controllers\Api\AlumniMapController::class, 'getClusters']);
    Route::get('alumni/map-stats', [\App\Http\Controllers\Api\AlumniMapController::class, 'getStats']);
    Route::post('alumni/nearby', [\App\Http\Controllers\Api\AlumniMapController::class, 'getNearby']);
    Route::post('user/location-privacy', [\App\Http\Controllers\Api\AlumniMapController::class, 'updateLocationPrivacy']);
    Route::post('user/location', [\App\Http\Controllers\Api\AlumniMapController::class, 'updateLocation']);
    Route::get('geocode/reverse', [\App\Http\Controllers\Api\AlumniMapController::class, 'reverseGeocode']);
});

// Alumni Recommendations routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('recommendations', [\App\Http\Controllers\Api\RecommendationController::class, 'index']);
    Route::post('recommendations/{userId}/dismiss', [\App\Http\Controllers\Api\RecommendationController::class, 'dismiss']);
    Route::post('recommendations/{userId}/feedback', [\App\Http\Controllers\Api\RecommendationController::class, 'feedback']);
    Route::post('recommendations/refresh', [\App\Http\Controllers\Api\RecommendationController::class, 'refresh']);
});

// Advanced Search routes
Route::middleware(['auth:sanctum', 'api.rate_limit:search'])->group(function () {
    Route::post('search', [SearchController::class, 'search']);
    Route::get('search/suggestions', [SearchController::class, 'suggestions']);
    Route::post('saved-searches', [SearchController::class, 'saveSearch']);
    Route::get('saved-searches', [SearchController::class, 'getSavedSearches']);
    Route::put('saved-searches/{savedSearch}', [SearchController::class, 'updateSavedSearch']);
    Route::delete('saved-searches/{savedSearch}', [SearchController::class, 'deleteSavedSearch']);
    Route::post('saved-searches/{savedSearch}/run', [SearchController::class, 'runSavedSearch']);
    Route::get('search/analytics', [SearchController::class, 'getSearchAnalytics']);
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

// Skills Development routes
Route::middleware('auth:sanctum')->group(function () {
    // Skills management
    Route::get('users/{userId}/skills', [SkillsController::class, 'getUserSkills']);
    Route::post('users/skills', [SkillsController::class, 'addSkill']);
    Route::post('skills/endorse', [SkillsController::class, 'endorseSkill']);
    Route::get('skills/search', [SkillsController::class, 'searchSkills']);

    // Skill suggestions and analysis
    Route::get('skills/suggestions', [SkillsController::class, 'getSkillSuggestions']);
    Route::get('skills/{skillId}/progression', [SkillsController::class, 'getSkillProgression']);
    Route::get('skills/{skillId}/recommendations', [SkillsController::class, 'getLearningRecommendations']);
    Route::get('skills/gap-analysis', [SkillsController::class, 'getSkillsGapAnalysis']);

    // Learning resources
    Route::get('learning-resources', [SkillsController::class, 'getResources']);
    Route::post('learning-resources', [SkillsController::class, 'createLearningResource']);
    Route::post('learning-resources/{resource}/rate', [SkillsController::class, 'rateResource']);
});

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

// Fundraising Campaign routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fundraising-campaigns', App\Http\Controllers\Api\FundraisingCampaignController::class);
    Route::get('fundraising-campaigns/{campaign}/analytics', [App\Http\Controllers\Api\FundraisingCampaignController::class, 'analytics']);
    Route::get('fundraising-campaigns/{campaign}/share', [App\Http\Controllers\Api\FundraisingCampaignController::class, 'share']);

    // Comprehensive Fundraising Analytics
    Route::prefix('fundraising-analytics')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'dashboard']);
        Route::get('giving-patterns', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'givingPatterns']);
        Route::get('campaigns/{campaign}/performance', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'campaignPerformance']);
        Route::get('donor-analytics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'donorAnalytics']);
        Route::get('predictive-analytics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'predictiveAnalytics']);
        Route::get('roi-metrics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'roiMetrics']);
        Route::get('donor-engagement', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'donorEngagement']);
        Route::get('trends', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'trends']);
        Route::post('export', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'export']);
    });
});

// Campaign Donation routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('campaign-donations', App\Http\Controllers\Api\CampaignDonationController::class);
    Route::get('campaigns/{campaign}/donations', [App\Http\Controllers\Api\CampaignDonationController::class, 'campaignDonations']);
    Route::get('user/donations', [App\Http\Controllers\Api\CampaignDonationController::class, 'userDonations']);
    Route::post('campaign-donations/{donation}/refund', [App\Http\Controllers\Api\CampaignDonationController::class, 'refund']);
});

// Recurring Donation routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('recurring-donations', App\Http\Controllers\Api\RecurringDonationController::class)->only(['index', 'show', 'update']);
    Route::post('recurring-donations/{recurringDonation}/cancel', [App\Http\Controllers\Api\RecurringDonationController::class, 'cancel']);
    Route::post('recurring-donations/{recurringDonation}/pause', [App\Http\Controllers\Api\RecurringDonationController::class, 'pause']);
    Route::post('recurring-donations/{recurringDonation}/resume', [App\Http\Controllers\Api\RecurringDonationController::class, 'resume']);
    Route::get('user/recurring-donations', [App\Http\Controllers\Api\RecurringDonationController::class, 'userRecurringDonations']);
    Route::get('admin/recurring-donations/due', [App\Http\Controllers\Api\RecurringDonationController::class, 'dueForPayment']);
});

// Tax Receipt routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tax-receipts', App\Http\Controllers\Api\TaxReceiptController::class)->only(['index', 'show']);
    Route::post('tax-receipts/generate', [App\Http\Controllers\Api\TaxReceiptController::class, 'generate']);
    Route::get('tax-receipts/{taxReceipt}/download', [App\Http\Controllers\Api\TaxReceiptController::class, 'download']);
    Route::post('tax-receipts/{taxReceipt}/resend', [App\Http\Controllers\Api\TaxReceiptController::class, 'resend']);
    Route::get('user/tax-receipts', [App\Http\Controllers\Api\TaxReceiptController::class, 'userTaxReceipts']);
    Route::post('admin/tax-receipts/generate-year', [App\Http\Controllers\Api\TaxReceiptController::class, 'generateForYear']);
});

// Peer Fundraiser routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('peer-fundraisers', App\Http\Controllers\Api\PeerFundraiserController::class);
    Route::get('campaigns/{campaign}/peer-fundraisers', [App\Http\Controllers\Api\PeerFundraiserController::class, 'campaignPeerFundraisers']);
    Route::get('user/peer-fundraisers', [App\Http\Controllers\Api\PeerFundraiserController::class, 'userPeerFundraisers']);
    Route::get('peer-fundraisers/{peerFundraiser}/share', [App\Http\Controllers\Api\PeerFundraiserController::class, 'share']);
});

// Scholarship routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('scholarships', App\Http\Controllers\Api\ScholarshipController::class);
    Route::get('scholarships/{scholarship}/impact-report', [App\Http\Controllers\Api\ScholarshipController::class, 'impactReport']);
    Route::get('user/donor-updates', [App\Http\Controllers\Api\ScholarshipController::class, 'donorUpdates']);

    // Scholarship Applications
    Route::apiResource('scholarships.applications', App\Http\Controllers\Api\ScholarshipApplicationController::class);
    Route::post('scholarships/{scholarship}/applications/{application}/review', [App\Http\Controllers\Api\ScholarshipApplicationController::class, 'review']);
    Route::post('scholarships/{scholarship}/applications/{application}/award', [App\Http\Controllers\Api\ScholarshipApplicationController::class, 'award']);

    // Scholarship Recipients
    Route::apiResource('scholarships.recipients', App\Http\Controllers\Api\ScholarshipRecipientController::class)->only(['index', 'show', 'update']);
    Route::get('scholarship-recipients/success-stories', [App\Http\Controllers\Api\ScholarshipRecipientController::class, 'successStories']);
});

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

// Success Stories routes
Route::middleware('auth:sanctum')->group(function () {
    // Public routes (can be viewed by authenticated users)
    Route::get('success-stories', [App\Http\Controllers\Api\SuccessStoryController::class, 'index']);
    Route::get('success-stories/featured', [App\Http\Controllers\Api\SuccessStoryController::class, 'featured']);
    Route::get('success-stories/recommended', [App\Http\Controllers\Api\SuccessStoryController::class, 'recommended']);
    Route::get('success-stories/demographics', [App\Http\Controllers\Api\SuccessStoryController::class, 'byDemographics']);
    Route::get('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'show']);

    // Interaction routes
    Route::post('success-stories/{successStory}/share', [App\Http\Controllers\Api\SuccessStoryController::class, 'share']);
    Route::post('success-stories/{successStory}/like', [App\Http\Controllers\Api\SuccessStoryController::class, 'like']);

    // CRUD routes (user must own the story or be admin)
    Route::post('success-stories', [App\Http\Controllers\Api\SuccessStoryController::class, 'store']);
    Route::put('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'update']);
    Route::delete('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'destroy']);

    // Admin routes
    Route::get('admin/success-stories/analytics', [App\Http\Controllers\Api\SuccessStoryController::class, 'analytics']);
    Route::post('admin/success-stories/{successStory}/toggle-feature', [App\Http\Controllers\Api\SuccessStoryController::class, 'toggleFeature']);
});

// Achievement routes
Route::middleware('auth:sanctum')->group(function () {
    // Achievement browsing and details
    Route::get('achievements', [AchievementController::class, 'index']);
    Route::get('achievements/{achievement}', [AchievementController::class, 'show']);
    Route::get('achievements/leaderboard', [AchievementController::class, 'leaderboard']);

    // User achievements
    Route::get('user/achievements', [AchievementController::class, 'userAchievements']);
    Route::get('users/{user}/achievements', [AchievementController::class, 'userAchievements']);
    Route::post('achievements/check', [AchievementController::class, 'checkAchievements']);
    Route::post('user-achievements/{userAchievement}/toggle-featured', [AchievementController::class, 'toggleFeatured']);

    // Achievement celebrations
    Route::get('achievement-celebrations', [AchievementCelebrationController::class, 'index']);
    Route::post('achievement-celebrations', [AchievementCelebrationController::class, 'create']);
    Route::post('achievement-celebrations/{celebration}/congratulations', [AchievementCelebrationController::class, 'congratulate']);
    Route::delete('achievement-celebrations/{celebration}/congratulations', [AchievementCelebrationController::class, 'removeCongratulation']);
    Route::get('achievement-celebrations/{celebration}/congratulations', [AchievementCelebrationController::class, 'congratulations']);
});

// Student Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/profile', [StudentProfileController::class, 'show']);
    Route::post('student/profile', [StudentProfileController::class, 'store']);
    Route::put('student/profile', [StudentProfileController::class, 'update']);
    Route::get('student/profile/completion', [StudentProfileController::class, 'completion']);
    Route::get('student/profile/statistics', [StudentProfileController::class, 'statistics']);
    Route::get('student/courses', [StudentProfileController::class, 'courses']);
});

// Testimonial Management routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('testimonials', App\Http\Controllers\Api\TestimonialController::class);
    
    // Testimonial rotation and display
    Route::get('testimonials-rotation', [App\Http\Controllers\Api\TestimonialController::class, 'rotation']);
    
    // Moderation actions
    Route::post('testimonials/{testimonial}/approve', [App\Http\Controllers\Api\TestimonialController::class, 'approve']);
    Route::post('testimonials/{testimonial}/reject', [App\Http\Controllers\Api\TestimonialController::class, 'reject']);
});

// Component Library routes
Route::middleware(['auth:sanctum'])->prefix('components')->name('components.')->group(function () {
    // Standard CRUD operations
    Route::apiResource('', App\Http\Controllers\Api\ComponentController::class);

    // Component operations
    Route::post('{component}/duplicate', [App\Http\Controllers\Api\ComponentController::class, 'duplicate']);
    Route::post('{component}/activate', [App\Http\Controllers\Api\ComponentController::class, 'activate']);
    Route::post('{component}/deactivate', [App\Http\Controllers\Api\ComponentController::class, 'deactivate']);
    Route::get('{component}/preview', [App\Http\Controllers\Api\ComponentController::class, 'preview']);
    Route::get('{component}/usage', [App\Http\Controllers\Api\ComponentController::class, 'usage']);
    Route::get('{component}/versions', [App\Http\Controllers\Api\ComponentController::class, 'versions']);
    Route::post('{component}/versions', [App\Http\Controllers\Api\ComponentController::class, 'createVersion']);

    // Component search and filtering
    Route::get('search', [App\Http\Controllers\Api\ComponentController::class, 'search']);
    Route::get('categories/{category}', [App\Http\Controllers\Api\ComponentController::class, 'byCategory']);
    Route::get('types/{type}', [App\Http\Controllers\Api\ComponentController::class, 'byType']);

    // Component instances
    Route::get('{component}/instances', [App\Http\Controllers\Api\ComponentController::class, 'instances']);
    Route::post('{component}/instances', [App\Http\Controllers\Api\ComponentController::class, 'createInstance']);
    Route::get('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'showInstance']);
    Route::put('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'updateInstance']);
    Route::delete('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'deleteInstance']);
    Route::post('instances/{instance}/move', [App\Http\Controllers\Api\ComponentController::class, 'moveInstance']);
    Route::post('instances/{instance}/duplicate', [App\Http\Controllers\Api\ComponentController::class, 'duplicateInstance']);

    // Component analytics
    Route::get('{component}/analytics', [App\Http\Controllers\Api\ComponentController::class, 'analytics']);
    Route::post('{component}/track-view', [App\Http\Controllers\Api\ComponentController::class, 'trackView']);
    Route::post('{component}/track-click', [App\Http\Controllers\Api\ComponentController::class, 'trackClick']);
    Route::post('{component}/track-conversion', [App\Http\Controllers\Api\ComponentController::class, 'trackConversion']);

    // Bulk operations
    Route::post('bulk-activate', [App\Http\Controllers\Api\ComponentController::class, 'bulkActivate']);
    Route::post('bulk-deactivate', [App\Http\Controllers\Api\ComponentController::class, 'bulkDeactivate']);
    Route::post('bulk-delete', [App\Http\Controllers\Api\ComponentController::class, 'bulkDelete']);

    // Import/Export
    Route::post('import', [App\Http\Controllers\Api\ComponentController::class, 'import']);
    Route::get('{component}/export', [App\Http\Controllers\Api\ComponentController::class, 'export']);
    Route::post('bulk-export', [App\Http\Controllers\Api\ComponentController::class, 'bulkExport']);
});

// Component Theme Management routes
Route::middleware(['auth:sanctum'])->prefix('component-themes')->name('component-themes.')->group(function () {
    // Standard CRUD operations
    Route::apiResource('', App\Http\Controllers\Api\ComponentThemeController::class);

    // GrapeJS integration endpoints
    Route::get('grapejs', [App\Http\Controllers\Api\ComponentThemeController::class, 'grapeJSIndex']);

    // Theme operations
    Route::post('{theme}/duplicate', [App\Http\Controllers\Api\ComponentThemeController::class, 'duplicate']);
    Route::post('{theme}/apply', [App\Http\Controllers\Api\ComponentThemeController::class, 'apply']);
    Route::get('{theme}/preview', [App\Http\Controllers\Api\ComponentThemeController::class, 'preview']);
    Route::get('{theme}/usage', [App\Http\Controllers\Api\ComponentThemeController::class, 'usage']);
    Route::get('{theme}/cached', [App\Http\Controllers\Api\ComponentThemeController::class, 'cached']);
    Route::delete('{theme}/cache', [App\Http\Controllers\Api\ComponentThemeController::class, 'clearCache']);

    // Import/Export operations
    Route::post('import', [App\Http\Controllers\Api\ComponentThemeController::class, 'import']);
    Route::get('{theme}/export', [App\Http\Controllers\Api\ComponentThemeController::class, 'export']);

    // Validation and bulk operations
    Route::post('validate', [App\Http\Controllers\Api\ComponentThemeController::class, 'validate']);
    Route::post('bulk', [App\Http\Controllers\Api\ComponentThemeController::class, 'bulk']);
    Route::post('testimonials/{testimonial}/approve', [App\Http\Controllers\Api\TestimonialController::class, 'approve']);
    Route::post('testimonials/{testimonial}/reject', [App\Http\Controllers\Api\TestimonialController::class, 'reject']);
    Route::post('testimonials/{testimonial}/archive', [App\Http\Controllers\Api\TestimonialController::class, 'archive']);
    Route::post('testimonials/{testimonial}/featured', [App\Http\Controllers\Api\TestimonialController::class, 'setFeatured']);

    // Analytics and tracking
    Route::post('testimonials/{testimonial}/track-click', [App\Http\Controllers\Api\TestimonialController::class, 'trackClick']);
    Route::get('testimonials-analytics', [App\Http\Controllers\Api\TestimonialController::class, 'analytics']);

    // Filtering and options
    Route::get('testimonials-filter-options', [App\Http\Controllers\Api\TestimonialController::class, 'filterOptions']);

    // Import/Export
    Route::get('testimonials-export', [App\Http\Controllers\Api\TestimonialController::class, 'export']);
    Route::post('testimonials-import', [App\Http\Controllers\Api\TestimonialController::class, 'import']);
});

// Student-Alumni Story Discovery routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/alumni-stories', [StudentAlumniStoryController::class, 'index']);
    Route::get('student/alumni-stories/recommended', [StudentAlumniStoryController::class, 'recommended']);
    Route::get('student/alumni-stories/career-path', [StudentAlumniStoryController::class, 'byCareerPath']);
    Route::get('student/alumni-stories/same-course', [StudentAlumniStoryController::class, 'fromSameCourse']);
    Route::get('student/alumni-stories/recent-graduates', [StudentAlumniStoryController::class, 'recentGraduates']);
    Route::get('student/alumni-stories/career-insights', [StudentAlumniStoryController::class, 'careerInsights']);
    Route::post('student/alumni-stories/{story}/connect', [StudentAlumniStoryController::class, 'connect']);
    Route::get('student/connections', [StudentAlumniStoryController::class, 'connections']);
});

// Student Mentorship routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/mentors', [StudentMentorshipController::class, 'getAlumniMentors']);
    Route::get('student/mentors/recommended', [StudentMentorshipController::class, 'getRecommendedMentors']);
    Route::get('student/mentors/same-course', [StudentMentorshipController::class, 'getMentorsFromSameCourse']);
    Route::get('student/mentors/career-specific', [StudentMentorshipController::class, 'getCareerSpecificMentors']);
    Route::post('student/mentorship/request', [StudentMentorshipController::class, 'requestMentorship']);
});

// Developer API routes
Route::middleware('auth:sanctum')->prefix('developer')->name('developer.')->group(function () {
    // API Key Management
    Route::post('api-keys', [\App\Http\Controllers\Api\DeveloperController::class, 'generateApiKey'])->name('api-keys.generate');
    Route::get('api-keys', [\App\Http\Controllers\Api\DeveloperController::class, 'getApiKeys'])->name('api-keys.index');
    Route::delete('api-keys/{keyId}', [\App\Http\Controllers\Api\DeveloperController::class, 'revokeApiKey'])->name('api-keys.revoke');

    // Webhook Management
    Route::get('webhook-events', [\App\Http\Controllers\Api\DeveloperController::class, 'getWebhookEvents'])->name('webhook-events');
    Route::post('webhooks/{webhookId}/test', [\App\Http\Controllers\Api\DeveloperController::class, 'testWebhook'])->name('webhooks.test');

    // Documentation & Tools
    Route::get('documentation', [\App\Http\Controllers\Api\DeveloperController::class, 'getApiDocumentation'])->name('documentation');
    Route::post('postman-collection', [\App\Http\Controllers\Api\DeveloperController::class, 'generatePostmanCollection'])->name('postman-collection');
    Route::post('sdk-generator', [\App\Http\Controllers\Api\DeveloperController::class, 'generateSdk'])->name('sdk-generator');
});

// Speaker Bureau routes
Route::middleware('auth:sanctum')->group(function () {
    // Public speaker browsing
    Route::get('speakers', [SpeakerBureauController::class, 'index']);
    Route::get('speakers/featured', [SpeakerBureauController::class, 'featured']);
    Route::get('speakers/by-topic', [SpeakerBureauController::class, 'getByTopic']);
    Route::get('speakers/{speaker}', [SpeakerBureauController::class, 'show']);

    // Speaker profile management
    Route::post('speakers/profile', [SpeakerBureauController::class, 'createProfile']);

    // Booking management
    Route::post('speakers/{speaker}/book', [SpeakerBureauController::class, 'book']);
});

// Webhook routes
Route::middleware(['auth:sanctum', 'api.rate_limit:webhook'])->group(function () {
    Route::apiResource('webhooks', App\Http\Controllers\Api\WebhookController::class);
    Route::post('webhooks/{webhook}/test', [App\Http\Controllers\Api\WebhookController::class, 'test']);
    Route::get('webhooks/{webhook}/deliveries', [App\Http\Controllers\Api\WebhookController::class, 'deliveries']);
    Route::post('webhooks/{webhook}/deliveries/{delivery}/retry', [App\Http\Controllers\Api\WebhookController::class, 'retryDelivery']);
    Route::get('webhooks/{webhook}/statistics', [App\Http\Controllers\Api\WebhookController::class, 'statistics']);
    Route::get('webhooks/events', [App\Http\Controllers\Api\WebhookController::class, 'events']);
    Route::post('webhooks/validate-url', [App\Http\Controllers\Api\WebhookController::class, 'validateUrl']);
    Route::post('webhooks/{webhook}/pause', [App\Http\Controllers\Api\WebhookController::class, 'pause']);
    Route::post('webhooks/{webhook}/resume', [App\Http\Controllers\Api\WebhookController::class, 'resume']);

    // Speaker booking requests
    Route::post('speakers/{speaker}/request-booking', [SpeakerBureauController::class, 'requestBooking']);
    Route::get('speaker/bookings', [SpeakerBureauController::class, 'getSpeakerBookings']);
    Route::get('my-bookings', [SpeakerBureauController::class, 'getUserBookings']);
    Route::post('bookings/{booking}/respond', [SpeakerBureauController::class, 'respondToBooking']);
    Route::post('bookings/{booking}/complete', [SpeakerBureauController::class, 'completeBooking']);
});

// Student Career Guidance routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/career/recommendations', [StudentCareerGuidanceController::class, 'getCareerRecommendations']);
    Route::get('student/career/paths', [StudentCareerGuidanceController::class, 'getCareerPaths']);
    Route::get('student/career/industry-insights', [StudentCareerGuidanceController::class, 'getIndustryInsights']);
    Route::get('student/career/salary-insights', [StudentCareerGuidanceController::class, 'getSalaryInsights']);
    Route::get('student/career/skill-gap-analysis', [StudentCareerGuidanceController::class, 'getSkillGapAnalysis']);
    Route::get('student/career/job-market-trends', [StudentCareerGuidanceController::class, 'getJobMarketTrends']);
});

// Email Marketing routes
Route::middleware('auth:sanctum')->group(function () {
    // Email Campaigns
    Route::apiResource('email-campaigns', App\Http\Controllers\Api\EmailCampaignController::class);
    Route::post('email-campaigns/{campaign}/send', [App\Http\Controllers\Api\EmailCampaignController::class, 'send']);
    Route::post('email-campaigns/{campaign}/schedule', [App\Http\Controllers\Api\EmailCampaignController::class, 'schedule']);
    Route::post('email-campaigns/{campaign}/ab-test', [App\Http\Controllers\Api\EmailCampaignController::class, 'createAbTest']);
    Route::post('email-campaigns/{campaign}/preview', [App\Http\Controllers\Api\EmailCampaignController::class, 'preview']);
    Route::get('email-campaigns/{campaign}/recipients', [App\Http\Controllers\Api\EmailCampaignController::class, 'recipients']);

    // Email Templates
    Route::get('email-templates', [App\Http\Controllers\Api\EmailCampaignController::class, 'templates']);

    // Email Automation
    Route::get('email-automation-rules', [App\Http\Controllers\Api\EmailCampaignController::class, 'automationRules']);
    Route::post('email-automation-rules', [App\Http\Controllers\Api\EmailCampaignController::class, 'createAutomationRule']);

    // Email Analytics
    Route::get('email-campaigns/analytics', [App\Http\Controllers\Api\EmailCampaignController::class, 'analytics']);
});

// Career Outcome Analytics routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('career-analytics', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'index']);
    Route::get('career-analytics/overview', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'overview']);
    Route::get('career-analytics/program-effectiveness', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'programEffectiveness']);
    Route::post('career-analytics/program-effectiveness/generate', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateProgramEffectiveness']);
    Route::get('career-analytics/salary-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'salaryAnalysis']);
    Route::get('career-analytics/industry-placement', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'industryPlacement']);
    Route::post('career-analytics/industry-placement/generate', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateIndustryPlacement']);
    Route::get('career-analytics/demographic-outcomes', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'demographicOutcomes']);
    Route::get('career-analytics/career-path-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'careerPathAnalysis']);
    Route::get('career-analytics/trend-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'trendAnalysis']);
    Route::post('career-analytics/generate-snapshot', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateSnapshot']);
    Route::get('career-analytics/snapshots', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'snapshots']);
    Route::get('career-analytics/filter-options', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'filterOptions']);
    Route::post('career-analytics/export', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'export']);
});

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

    // Forum moderation (admin/moderator only)
    Route::post('forums/moderate/{type}/{id}', [App\Http\Controllers\Api\ForumModerationController::class, 'moderate']);
    Route::get('forums/moderation/pending', [App\Http\Controllers\Api\ForumModerationController::class, 'getPending']);
    Route::get('forums/analytics', [App\Http\Controllers\Api\ForumAnalyticsController::class, 'getStatistics']);
});

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

// Onboarding routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('onboarding/state', [App\Http\Controllers\Api\OnboardingController::class, 'getOnboardingState']);
    Route::post('onboarding/state', [App\Http\Controllers\Api\OnboardingController::class, 'updateOnboardingState']);
    Route::get('onboarding/new-features', [App\Http\Controllers\Api\OnboardingController::class, 'getNewFeatures']);
    Route::get('onboarding/profile-completion', [App\Http\Controllers\Api\OnboardingController::class, 'getProfileCompletion']);
    Route::get('onboarding/whats-new', [App\Http\Controllers\Api\OnboardingController::class, 'getWhatsNew']);
    Route::post('onboarding/events', [App\Http\Controllers\Api\OnboardingController::class, 'recordEvent']);
    Route::post('user/interests', [App\Http\Controllers\Api\OnboardingController::class, 'saveUserInterests']);
    Route::get('onboarding/help/{elementId}', [App\Http\Controllers\Api\OnboardingController::class, 'getContextualHelp']);

    // Training & Documentation API routes
    Route::prefix('training')->group(function () {
        Route::get('guides', [\App\Http\Controllers\UserTrainingController::class, 'getUserGuides']);
        Route::get('tutorials', [\App\Http\Controllers\UserTrainingController::class, 'getVideoTutorials']);
        Route::get('onboarding-sequence', [\App\Http\Controllers\UserTrainingController::class, 'getOnboardingSequence']);
        Route::get('faqs', [\App\Http\Controllers\UserTrainingController::class, 'getFAQs']);
        Route::get('progress', [\App\Http\Controllers\UserTrainingController::class, 'getTrainingProgress']);
        Route::post('mark-step-completed', [\App\Http\Controllers\UserTrainingController::class, 'markStepCompleted']);
        Route::get('search', [\App\Http\Controllers\UserTrainingController::class, 'search']);
        Route::post('faq-helpful', [\App\Http\Controllers\UserTrainingController::class, 'markFAQHelpful']);
        Route::post('feedback', [\App\Http\Controllers\UserTrainingController::class, 'submitFeedback']);
    });
});

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

// Alumni Map routes
Route::middleware('auth:sanctum')->group(function () {
    // Map data endpoints
    Route::post('alumni/map', [App\Http\Controllers\Api\AlumniMapController::class, 'getAlumniByLocation']);
    Route::post('alumni/map/clusters', [App\Http\Controllers\Api\AlumniMapController::class, 'getClusters']);
    Route::get('alumni/nearby', [App\Http\Controllers\Api\AlumniMapController::class, 'getNearbyAlumni']);
    Route::get('alumni/map/heatmap', [App\Http\Controllers\Api\AlumniMapController::class, 'getHeatmapData']);
    Route::get('alumni/search', [App\Http\Controllers\Api\AlumniMapController::class, 'searchAlumni']);

    // Regional data endpoints
    Route::get('regions/{region}/stats', [App\Http\Controllers\Api\AlumniMapController::class, 'getRegionalStats']);
    Route::get('regions/{region}/groups', [App\Http\Controllers\Api\AlumniMapController::class, 'getSuggestedGroups']);

    // Filter and location endpoints
    Route::get('alumni/filter-options', [App\Http\Controllers\Api\AlumniMapController::class, 'getFilterOptions']);
    Route::post('alumni/location', [App\Http\Controllers\Api\AlumniMapController::class, 'updateLocation']);
});

// Analytics routes
Route::prefix('analytics')->group(function () {
    // Event tracking endpoints
    Route::post('events', [App\Http\Controllers\AnalyticsController::class, 'storeEvents']);
    Route::post('conversion', [App\Http\Controllers\AnalyticsController::class, 'storeConversion']);
    Route::post('error', [App\Http\Controllers\AnalyticsController::class, 'storeError']);

    // Analytics and reporting endpoints
    Route::post('metrics', [App\Http\Controllers\AnalyticsController::class, 'getMetrics']);
    Route::post('reports/{reportType}', [App\Http\Controllers\AnalyticsController::class, 'generateReport']);
    Route::post('export', [App\Http\Controllers\AnalyticsController::class, 'exportData']);
    Route::post('conversion-report', [App\Http\Controllers\AnalyticsController::class, 'getConversionReport']);
});

// A/B Testing routes
Route::prefix('ab-tests')->group(function () {
    // Public endpoints for test participation
    Route::get('active', [App\Http\Controllers\ABTestController::class, 'getActiveTests']);
    Route::post('assignments', [App\Http\Controllers\ABTestController::class, 'storeAssignment']);
    Route::post('conversions', [App\Http\Controllers\ABTestController::class, 'storeConversion']);

    // Test results and statistics
    Route::get('{testId}/results', [App\Http\Controllers\ABTestController::class, 'getTestResults']);
    Route::get('{testId}/statistics', [App\Http\Controllers\ABTestController::class, 'getTestStatistics']);

    // Admin endpoints for test management (add auth middleware in production)
    Route::get('/', [App\Http\Controllers\ABTestController::class, 'getAllTests']);
    Route::post('/', [App\Http\Controllers\ABTestController::class, 'createTest']);
    Route::patch('{testId}', [App\Http\Controllers\ABTestController::class, 'updateTest']);
    Route::delete('{testId}', [App\Http\Controllers\ABTestController::class, 'deleteTest']);
});

// Performance monitoring routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('performance/metrics', [App\Http\Controllers\Api\PerformanceController::class, 'storeMetrics']);
    Route::post('performance/sessions', [App\Http\Controllers\Api\PerformanceController::class, 'storeSessions']);
    Route::get('performance/analytics', [App\Http\Controllers\Api\PerformanceController::class, 'getAnalytics']);
    Route::get('performance/real-time', [App\Http\Controllers\Api\PerformanceController::class, 'getRealTimeMetrics']);
    Route::get('performance/core-web-vitals', [App\Http\Controllers\Api\PerformanceController::class, 'getCoreWebVitals']);
    Route::get('performance/recommendations', [App\Http\Controllers\Api\PerformanceController::class, 'getRecommendations']);
    Route::get('performance/page', [App\Http\Controllers\Api\PerformanceController::class, 'getPagePerformance']);
});

// Dashboard Widget routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('dashboard/social-activity', [App\Http\Controllers\Api\DashboardController::class, 'socialActivity']);
    Route::get('dashboard/alumni-suggestions', [App\Http\Controllers\Api\DashboardController::class, 'alumniSuggestions']);
    Route::get('dashboard/job-recommendations', [App\Http\Controllers\Api\DashboardController::class, 'jobRecommendations']);
    Route::get('dashboard/upcoming-events', [App\Http\Controllers\Api\DashboardController::class, 'upcomingEvents']);
});

// Connection routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('connections/request', [App\Http\Controllers\Api\ConnectionController::class, 'sendRequest']);
    Route::post('connections/{connection}/accept', [App\Http\Controllers\Api\ConnectionController::class, 'acceptRequest']);
    Route::post('connections/{connection}/decline', [App\Http\Controllers\Api\ConnectionController::class, 'declineRequest']);
    Route::get('connections', [App\Http\Controllers\Api\ConnectionController::class, 'index']);
    Route::get('connections/requests', [App\Http\Controllers\Api\ConnectionController::class, 'requests']);
});

// Job routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('jobs/{job}/save', [App\Http\Controllers\Api\JobController::class, 'save']);
    Route::delete('jobs/{job}/save', [App\Http\Controllers\Api\JobController::class, 'unsave']);
    Route::get('jobs/{job}', [App\Http\Controllers\Api\JobController::class, 'show']);
});

// Event RSVP routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('events/{event}/rsvp', [App\Http\Controllers\Api\EventController::class, 'rsvp']);
    Route::delete('events/{event}/rsvp', [App\Http\Controllers\Api\EventController::class, 'cancelRsvp']);
});

// Analytics routes
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->prefix('analytics')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Api\AnalyticsController::class, 'getDashboardData']);
    Route::get('engagement-metrics', [App\Http\Controllers\Api\AnalyticsController::class, 'getEngagementMetrics']);
    Route::get('alumni-activity', [App\Http\Controllers\Api\AnalyticsController::class, 'getAlumniActivity']);
    Route::get('community-health', [App\Http\Controllers\Api\AnalyticsController::class, 'getCommunityHealth']);
    Route::get('platform-usage', [App\Http\Controllers\Api\AnalyticsController::class, 'getPlatformUsage']);
    Route::get('summary', [App\Http\Controllers\Api\AnalyticsController::class, 'getAnalyticsSummary']);
    Route::post('custom-report', [App\Http\Controllers\Api\AnalyticsController::class, 'generateCustomReport']);
    Route::get('export', [App\Http\Controllers\Api\AnalyticsController::class, 'exportData']);
    Route::get('available-metrics', [App\Http\Controllers\Api\AnalyticsController::class, 'getAvailableMetrics']);
});

// Calendar Integration routes
Route::middleware('auth:sanctum')->group(function () {
    // Calendar connections
    Route::get('calendar/connections', [App\Http\Controllers\Api\CalendarSyncController::class, 'index']);
    Route::post('calendar/connect', [App\Http\Controllers\Api\CalendarSyncController::class, 'connect']);
    Route::post('calendar/connections/{connection}/disconnect', [App\Http\Controllers\Api\CalendarSyncController::class, 'disconnect']);
    Route::post('calendar/connections/{connection}/sync', [App\Http\Controllers\Api\CalendarSyncController::class, 'sync']);
    Route::get('calendar/sync-status', [App\Http\Controllers\Api\CalendarSyncController::class, 'syncStatus']);

    // Availability and scheduling
    Route::get('calendar/availability', [App\Http\Controllers\Api\CalendarSyncController::class, 'availability']);
    Route::post('calendar/find-slots', [App\Http\Controllers\Api\CalendarSyncController::class, 'findSlots']);

    // Event management
    Route::post('calendar/events', [App\Http\Controllers\Api\CalendarSyncController::class, 'createEvent']);
    Route::post('calendar/events/{event}/invites', [App\Http\Controllers\Api\CalendarSyncController::class, 'sendInvites']);

    // Mentorship scheduling
    Route::post('calendar/schedule-mentorship', [App\Http\Controllers\Api\CalendarSyncController::class, 'scheduleMentorship']);
});

// Performance Monitoring routes (Admin only)
Route::middleware(['auth:sanctum', 'role:super-admin'])->prefix('admin/performance')->group(function () {
    Route::get('metrics', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'metrics']);
    Route::get('budget-details', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'getBudgetDetails']);
    Route::post('clear-caches', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'clearCaches']);
    Route::post('optimize-social-graph', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeSocialGraph']);
    Route::post('optimize-timeline', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeTimeline']);
    Route::post('optimize-cdn', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'optimizeCdn']);
    Route::post('setup-alerts', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'setupAlerts']);
    Route::post('execute-optimization', [App\Http\Controllers\Api\Admin\PerformanceController::class, 'executeAutomatedOptimization']);
});

// User Testing and Feedback routes
Route::middleware('auth:sanctum')->group(function () {
    // Feedback routes
    Route::post('feedback', [App\Http\Controllers\Api\FeedbackController::class, 'store']);
    Route::get('feedback', [App\Http\Controllers\Api\FeedbackController::class, 'index']);

    // A/B Testing routes
    Route::get('ab-tests/{testName}/variant', [App\Http\Controllers\Api\FeedbackController::class, 'getABTestVariant']);
    Route::post('ab-tests/conversion', [App\Http\Controllers\Api\FeedbackController::class, 'trackConversion']);
});

// Admin A/B Testing routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('admin')->group(function () {
    Route::apiResource('ab-tests', App\Http\Controllers\Api\Admin\ABTestController::class);
    Route::get('ab-tests-analytics', [App\Http\Controllers\Api\Admin\ABTestController::class, 'analytics']);
});

// Form Component Routes
Route::prefix('forms')->group(function () {
    Route::post('/submit', [App\Http\Controllers\Api\FormController::class, 'submit']);
    Route::post('/autosave', [App\Http\Controllers\Api\FormController::class, 'autoSave']);
    Route::post('/notifications', [App\Http\Controllers\Api\FormController::class, 'sendNotifications']);
    
    // Template-specific form submission routes
    Route::post('/individual-signup', [App\Http\Controllers\Api\FormController::class, 'submitIndividualSignup']);
    Route::post('/institution-demo-request', [App\Http\Controllers\Api\FormController::class, 'submitInstitutionDemoRequest']);
    Route::post('/contact', [App\Http\Controllers\Api\FormController::class, 'submitContactForm']);
    Route::post('/newsletter-signup', [App\Http\Controllers\Api\FormController::class, 'submitNewsletterSignup']);
    Route::post('/event-registration', [App\Http\Controllers\Api\FormController::class, 'submitEventRegistration']);
});

// Component Library Bridge routes for GrapeJS integration
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('components/bridge')->group(function () {
        Route::get('initialize', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'initialize']);
        Route::get('categories', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getCategories']);
        Route::get('search', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'searchComponents']);
        Route::post('track-usage', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'trackUsage']);
        Route::post('track-rating', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'trackRating']);
        Route::get('usage-stats/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getUsageStats']);
        Route::get('most-used', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getMostUsed']);
        Route::get('recently-used', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getRecentlyUsed']);
        Route::get('trending', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getTrending']);
        Route::get('analytics', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getAnalytics']);
        Route::get('documentation/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getDocumentation']);
        Route::get('tooltip/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getTooltip']);
        Route::get('validate/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'validateComponent']);
        Route::get('grapeJS-data/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getGrapeJSData']);
    });
});

// Component Version Control and Export System routes
Route::middleware('auth:sanctum')->group(function () {
    // Component version management
    Route::get('components/{component}/versions', [App\Http\Controllers\Api\ComponentVersionController::class, 'index']);
    Route::post('components/{component}/versions', [App\Http\Controllers\Api\ComponentVersionController::class, 'store']);
    Route::get('components/{component}/versions/{version}', [App\Http\Controllers\Api\ComponentVersionController::class, 'show']);
    Route::post('components/{component}/versions/{version}/restore', [App\Http\Controllers\Api\ComponentVersionController::class, 'restore']);
    Route::post('components/{component}/versions/compare', [App\Http\Controllers\Api\ComponentVersionController::class, 'compare']);

    // Component export/import
    Route::post('components/{component}/export', [App\Http\Controllers\Api\ComponentVersionController::class, 'export']);
    Route::post('components/import', [App\Http\Controllers\Api\ComponentVersionController::class, 'import']);
    Route::post('components/create-template', [App\Http\Controllers\Api\ComponentVersionController::class, 'createTemplate']);

    // Performance analysis
    Route::get('components/{component}/performance/analyze', [App\Http\Controllers\Api\ComponentVersionController::class, 'analyzePerformance']);
    Route::get('components/{component}/performance/trends', [App\Http\Controllers\Api\ComponentVersionController::class, 'performanceTrends']);
    Route::post('components/{component}/performance/compare', [App\Http\Controllers\Api\ComponentVersionController::class, 'comparePerformance']);

    // Backup and recovery
    Route::post('components/{component}/backup', [App\Http\Controllers\Api\ComponentVersionController::class, 'createBackup']);
    Route::get('components/{component}/backups', [App\Http\Controllers\Api\ComponentVersionController::class, 'listBackups']);
    Route::post('components/restore-backup', [App\Http\Controllers\Api\ComponentVersionController::class, 'restoreBackup']);

    // Migration utilities
    Route::post('components/{component}/migrate', [App\Http\Controllers\Api\ComponentVersionController::class, 'migrate']);
    
    // GrapeJS Integration Testing Routes
    Route::get('components/{component}/grapejs-block', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getGrapeJSBlock']);
    Route::get('components/{component}/grapejs-traits/validate', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'validateTraits']);
    Route::get('components/{component}/grapejs-compatibility', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'checkCompatibility']);
    Route::post('components/serialize-to-grapejs', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'serializeToGrapeJS']);
    Route::post('components/deserialize-from-grapejs', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'deserializeFromGrapeJS']);
    Route::post('components/grapejs-performance-test', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'performanceTest']);
    Route::post('components/{component}/grapejs-performance-test', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'componentPerformanceTest']);
    Route::post('components/{component}/grapejs-compatibility/drag-drop', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'testDragDrop']);
    Route::post('components/{component}/grapejs-compatibility/responsive', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'testResponsive']);
    Route::post('components/{component}/grapejs-compatibility/style-manager', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'testStyleManager']);
    Route::post('components/{component}/grapejs-compatibility/backward', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'testBackwardCompatibility']);
    Route::post('components/grapejs-stability-test', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'stabilityTest']);
    Route::post('components/{component}/grapejs-integrity-test', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'integrityTest']);
    Route::post('components/grapejs-regression-test', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'regressionTest']);
    Route::post('components/grapejs-blocks/batch', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getBatchBlocks']);
});

// Brand Customizer routes
Route::middleware(['auth:sanctum'])->prefix('brand-customizer')->group(function () {
    Route::get('data', [App\Http\Controllers\Api\BrandCustomizerController::class, 'getData']);
    
    // Logo management
    Route::post('logos', [App\Http\Controllers\Api\BrandCustomizerController::class, 'uploadLogos']);
    Route::post('logos/{logo}/set-primary', [App\Http\Controllers\Api\BrandCustomizerController::class, 'setPrimaryLogo']);
    Route::post('logos/{logo}/optimize', [App\Http\Controllers\Api\BrandCustomizerController::class, 'optimizeLogo']);
    Route::delete('logos/{logo}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteLogo']);
    
    // Color management
    Route::post('colors', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeColor']);
    Route::put('colors/{color}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateColor']);
    Route::delete('colors/{color}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteColor']);
    
    // Font management
    Route::post('fonts/upload', [App\Http\Controllers\Api\BrandCustomizerController::class, 'uploadFonts']);
    Route::post('fonts', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeFont']);
    Route::put('fonts/{font}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateFont']);
    Route::post('fonts/{font}/set-primary', [App\Http\Controllers\Api\BrandCustomizerController::class, 'setPrimaryFont']);
    Route::delete('fonts/{font}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteFont']);
    
    // Template management
    Route::post('templates', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeTemplate']);
    Route::put('templates/{template}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateTemplate']);
    Route::post('templates/{template}/apply', [App\Http\Controllers\Api\BrandCustomizerController::class, 'applyTemplate']);
    Route::post('templates/{template}/duplicate', [App\Http\Controllers\Api\BrandCustomizerController::class, 'duplicateTemplate']);
    
    // Brand consistency
    Route::post('consistency-check', [App\Http\Controllers\Api\BrandCustomizerController::class, 'consistencyCheck']);
    Route::post('auto-fix/{issue}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'autoFixIssue']);
    
    // Guidelines and export
    Route::put('guidelines', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateGuidelines']);
    Route::post('export', [App\Http\Controllers\Api\BrandCustomizerController::class, 'exportAssets']);
// Production Monitoring and Analytics routes
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('monitoring')->group(function () {
    // Main dashboard data
    Route::get('dashboard', [App\Http\Controllers\Api\MonitoringController::class, 'dashboard']);
    Route::get('realtime', [App\Http\Controllers\Api\MonitoringController::class, 'realtime']);

    // Detailed metrics by category
    Route::get('metrics/{type}', [App\Http\Controllers\Api\MonitoringController::class, 'metrics']);

    // Alerts and notifications
    Route::get('alerts', [App\Http\Controllers\Api\MonitoringController::class, 'alerts']);

    // Automated reports
    Route::get('reports', [App\Http\Controllers\Api\MonitoringController::class, 'reports']);

    // Manual monitoring cycle execution
    Route::post('cycle', [App\Http\Controllers\Api\MonitoringController::class, 'executeCycle']);

    // Configuration and settings
    Route::get('settings', [App\Http\Controllers\Api\MonitoringController::class, 'settings']);
});

// Error Tracking Integration routes (public endpoints for client error reporting)
Route::post('errors/track', function (Illuminate\Http\Request $request) {
    try {
        $error = [
            'message' => $request->input('message'),
            'stack' => $request->input('stack'),
            'url' => $request->input('url'),
            'user_agent' => $request->input('user_agent'),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'metadata' => $request->except(['message', 'stack', 'url', 'user_agent'])
        ];

        \Log::error('Frontend Error', $error);

        // Store in monitoring system for tracking
        \Cache::put("error_report_" . uniqid(), json_encode($error), 86400);

        return response()->json(['status' => 'recorded']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error'], 500);
    }
});

// Webhook routes for external monitoring integrations
Route::prefix('webhooks/monitoring')->group(function () {
    // Datadog integrations
    Route::post('datadog/metrics', function (Illuminate\Http\Request $request) {
        \Log::info('Datadog Metrics Webhook', $request->all());
        \Cache::put('datadog_webhook_' . time(), $request->all(), 3600);
        return response()->json(['status' => 'received']);
    });

    // New Relic integrations
    Route::post('newrelic/alerts', function (Illuminate\Http\Request $request) {
        \Log::info('New Relic Alert Webhook', $request->all());
        \Cache::put('newrelic_alert_' . time(), $request->all(), 3600);
        return response()->json(['status' => 'received']);
    });

    // Slack integrations
    Route::post('slack/app-rate-limited', function (Illuminate\Http\Request $request) {
        \Log::warning('Slack Rate Limit Alert', $request->all());
        return response()->json(['status' => 'received']);
    });

    // Sentry integrations
    Route::post('sentry/issues', function (Illuminate\Http\Request $request) {
        \Log::error('Sentry Issue Webhook', $request->all());
        return response()->json(['status' => 'received']);
    });
});

// Performance Monitoring Webhooks
Route::prefix('webhooks/performance')->group(function () {
    // Core Web Vitals collection
    Route::post('web-vitals', function (Illuminate\Http\Request $request) {
        try {
            $vitals = $request->only([
                'fid', 'lcp', 'fcp', 'cls', 'ttfb', 'url', 'user_agent', 'timestamp'
            ]);

            $vitals['recorded_at'] = now()->toISOString();
            $vitals['user_id'] = auth()->id();

            // Store web vitals for analysis
            \Cache::put("web_vitals_" . uniqid(), json_encode($vitals), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });

    // User interaction tracking
    Route::post('user-interactions', function (Illuminate\Http\Request $request) {
        try {
            $interaction = [
                'type' => $request->input('type'), // 'click', 'scroll', 'form_interaction', etc.
                'element' => $request->input('element'),
                'element_selector' => $request->input('element_selector'),
                'page_url' => $request->input('page_url'),
                'timestamp' => $request->input('timestamp', now()->toISOString()),
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'metadata' => $request->except(['type', 'element', 'element_selector', 'page_url', 'timestamp'])
            ];

            // Store interaction data
            \Cache::put("interaction_" . uniqid(), json_encode($interaction), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });

    // Page load performance
    Route::post('page-loads', function (Illuminate\Http\Request $request) {
        try {
            $loadData = [
                'page_url' => $request->input('page_url'),
                'load_time' => $request->input('load_time'),
                'dns_lookup' => $request->input('dns_lookup'),
                'tcp_connect' => $request->input('tcp_connect'),
                'server_response' => $request->input('server_response'),
                'page_parse' => $request->input('page_parse'),
                'render_time' => $request->input('render_time'),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString()
            ];

            // Store page load data
            \Cache::put("page_load_" . uniqid(), json_encode($loadData), 86400);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error'], 500);
        }
    });
});

// Automated Testing Pipeline Integration
Route::middleware('auth:sanctum')->prefix('testing')->group(function () {
    // Test execution endpoints
    Route::post('execute/unit', function (Illuminate\Http\Request $request) {
        // Trigger unit tests and return results
        $command = $request->input('command', 'composer test --testsuite=Unit');
        $output = shell_exec($command);

        \Log::info("Unit Tests Executed", ['command' => $command, 'output' => $output]);

        return response()->json([
            'status' => 'completed',
            'tests' => 'unit',
            'output' => $output,
            'timestamp' => now()->toISOString()
        ]);
    });

    Route::post('execute/feature', function (Illuminate\Http\Request $request) {
        // Trigger feature tests and return results
        $command = $request->input('command', 'composer test --testsuite=Feature');
        $output = shell_exec($command);

        \Log::info("Feature Tests Executed", ['command' => $command, 'output' => $output]);

        return response()->json([
            'status' => 'completed',
            'tests' => 'feature',
            'output' => $output,
            'timestamp' => now()->toISOString()
        ]);
    });

    Route::post('execute/e2e', function (Illuminate\Http\Request $request) {
        // Trigger E2E tests and return results
        $command = $request->input('command', 'npm run test:e2e');
        $output = shell_exec($command);

        \Log::info("E2E Tests Executed", ['command' => $command, 'output' => $output]);

        return response()->json([
            'status' => 'completed',
            'tests' => 'e2e',
            'output' => $output,
            'timestamp' => now()->toISOString()
        ]);
    });

    // Test results and statistics
    Route::get('results/unit', function () {
        // Get latest unit test results
        return response()->json(['status' => 'not_implemented_yet']);
    });

    Route::get('results/feature', function () {
        // Get latest feature test results
        return response()->json(['status' => 'not_implemented_yet']);
    });

    Route::get('results/e2e', function () {
        // Get latest E2E test results
        return response()->json(['status' => 'not_implemented_yet']);
    });

    Route::get('coverage', function () {
        // Get test coverage data
        return response()->json(['status' => 'not_implemented_yet']);
    });
});

// Security Monitoring Integration
Route::middleware('auth:sanctum')->prefix('security')->group(function () {
    // Security audit initiation
    Route::post('audit/initiate', function () {
        // Trigger security audit
        $securityService = app(App\Services\SecurityAuditService::class);
        $results = $securityService->performSecurityAudit();

        \Log::info('Security Audit Completed', ['timestamp' => now()->toISOString()]);

        return response()->json([
            'status' => 'completed',
            'results' => $results,
            'timestamp' => now()->toISOString()
        ]);
    });

    // Security alerts management
    Route::get('alerts', function () {
        $alerts = \Cache::get('security_alerts', []);
        return response()->json([
            'status' => 'success',
            'alerts' => $alerts,
            'count' => count($alerts)
        ]);
    });

    // Security compliance status
    Route::get('compliance', function () {
        $securityService = app(App\Services\SecurityAuditService::class);
        $compliance = $securityService->generateComplianceReport();

        return response()->json([
            'status' => 'success',
            'compliance' => $compliance
        ]);
    });

    // Threat detection status
    Route::get('threats', function () {
        $securityService = app(App\Services\SecurityAuditService::class);
        $threats = $securityService->monitorSuspiciousActivity();

        return response()->json([
            'status' => 'success',
            'threats' => $threats
        ]);
    });
});

// Deployment and Release Monitoring
Route::middleware(['auth:sanctum', 'role:admin|super-admin'])->prefix('deployment')->group(function () {
    // Deployment status tracking
    Route::post('status/update', function (Illuminate\Http\Request $request) {
        $statusUpdate = [
            'deployment_id' => $request->input('deployment_id'),
            'environment' => $request->input('environment', 'production'),
            'status' => $request->input('status'), // 'started', 'completed', 'failed'
            'progress' => $request->input('progress', 0),
            'message' => $request->input('message'),
            'timestamp' => now()->toISOString()
        ];

        // Store deployment status
        \Cache::put("deployment_status_" . $statusUpdate['deployment_id'], $statusUpdate);
        \Log::info("Deployment Status Update", $statusUpdate);

        return response()->json([
            'status' => 'recorded',
            'deployment' => $statusUpdate
        ]);
    });

    // Deployment history
    Route::get('history', function () {
        // Get recent deployment history
        $keys = \Cache::store('redis')->keys('deployment_status_*');
        $deployments = [];

        foreach ($keys as $key) {
            $data = \Cache::get(str_replace('redis:', '', $key));
            if ($data) {
                $deployments[] = $data;
            }
        }

        // Sort by timestamp descending
        usort($deployments, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));

        return response()->json([
            'status' => 'success',
            'deployments' => array_slice($deployments, 0, 50) // Last 50 deployments
        ]);
    });

    // Rollback endpoint
    Route::post('rollback/{deploymentId}', function ($deploymentId) {
        // Handle rollback logic
        \Log::warning("Deployment Rollback Initiated", [
            'deployment_id' => $deploymentId,
            'timestamp' => now()->toISOString(),
            'user' => auth()->id()
        ]);

        // Store rollback action
        \Cache::put("rollback_{$deploymentId}", [
            'deployment_id' => $deploymentId,
            'rollback_initiated_at' => now()->toISOString(),
            'rollback_by' => auth()->id(),
            'rollback_status' => 'initiated'
        ]);

        return response()->json([
            'status' => 'rollback_initiated',
            'deployment_id' => $deploymentId,
            'rollback_id' => "rollback_{$deploymentId}"
        ]);
    });
});

// Component Analytics Tracking (client-side events)
Route::middleware('auth:sanctum')->prefix('components/analytics')->group(function () {
    Route::post('view', function (Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);

            $componentService->recordView(
                $request->input('component_instance_id'),
                auth()->id(),
                session()->getId(),
                [
                    'view_duration' => $request->input('view_duration'),
                    'scroll_depth' => $request->input('scroll_depth'),
                    'interactions' => $request->input('interactions', 0)
                ]
            );

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });

    Route::post('click', function (Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);

            $componentService->recordClick(
                $request->input('component_instance_id'),
                auth()->id(),
                session()->getId(),
                [
                    'element_id' => $request->input('element_id'),
                    'element_class' => $request->input('element_class'),
                    'click_x' => $request->input('click_x'),
                    'click_y' => $request->input('click_y'),
                    'element_text' => $request->input('element_text')
                ]
            );

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });

    Route::post('conversion', function (Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);

            $componentService->recordConversion(
                $request->input('component_instance_id'),
                auth()->id(),
                session()->getId(),
                [
                    'conversion_type' => $request->input('conversion_type'),
                    'conversion_value' => $request->input('conversion_value'),
                    'funnel_step' => $request->input('funnel_step'),
                    'source_url' => $request->input('source_url')
                ]
            );

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });

    Route::post('form-submit', function (Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);

            $componentService->recordFormSubmit(
                $request->input('component_instance_id'),
                auth()->id(),
                session()->getId(),
                [
                    'form_id' => $request->input('form_id'),
                    'fields_count' => $request->input('fields_count'),
                    'completion_time' => $request->input('completion_time'),
                    'validation_errors' => $request->input('validation_errors'),
                    'form_type' => $request->input('form_type')
                ]
            );

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });
});
});
