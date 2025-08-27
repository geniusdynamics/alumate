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
