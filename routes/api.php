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
use App\Http\Controllers\Api\SkillsController;
use App\Http\Controllers\Api\EventsController;
use App\Http\Controllers\Api\ReunionController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\AchievementCelebrationController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\StudentAlumniStoryController;
use App\Http\Controllers\Api\StudentMentorshipController;
use App\Http\Controllers\Api\SpeakerBureauController;
use App\Http\Controllers\Api\StudentCareerGuidanceController;

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
    Route::apiResource('events', EventsController::class);
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
    Route::get('student/mentorships', [StudentMentorshipController::class, 'getStudentMentorships']);
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
    Route::post('speakers/{speaker}/book', [SpeakerBureauController::class, 'requestBooking']);
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
    Route::get('student/career/networking-recommendations', [StudentCareerGuidanceController::class, 'getNetworkingRecommendations']);
    Route::get('student/career/timeline-examples', [StudentCareerGuidanceController::class, 'getCareerTimelineExamples']);
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