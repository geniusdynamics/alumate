<?php

use Illuminate\Support\Facades\Route;

// Success Stories routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('success-stories', [App\Http\Controllers\Api\SuccessStoryController::class, 'index']);
    Route::get('success-stories/featured', [App\Http\Controllers\Api\SuccessStoryController::class, 'featured']);
    Route::get('success-stories/recommended', [App\Http\Controllers\Api\SuccessStoryController::class, 'recommended']);
    Route::get('success-stories/demographics', [App\Http\Controllers\Api\SuccessStoryController::class, 'byDemographics']);
    Route::get('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'show']);
    Route::post('success-stories/{successStory}/share', [App\Http\Controllers\Api\SuccessStoryController::class, 'share']);
    Route::post('success-stories/{successStory}/like', [App\Http\Controllers\Api\SuccessStoryController::class, 'like']);
    Route::post('success-stories', [App\Http\Controllers\Api\SuccessStoryController::class, 'store']);
    Route::put('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'update']);
    Route::delete('success-stories/{successStory}', [App\Http\Controllers\Api\SuccessStoryController::class, 'destroy']);
    Route::get('admin/success-stories/analytics', [App\Http\Controllers\Api\SuccessStoryController::class, 'analytics']);
    Route::post('admin/success-stories/{successStory}/toggle-feature', [App\Http\Controllers\Api\SuccessStoryController::class, 'toggleFeature']);
});

// Testimonial Management routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('testimonials', App\Http\Controllers\Api\TestimonialController::class);
    Route::get('testimonials-rotation', [App\Http\Controllers\Api\TestimonialController::class, 'rotation']);
    Route::post('testimonials/{testimonial}/approve', [App\Http\Controllers\Api\TestimonialController::class, 'approve']);
    Route::post('testimonials/{testimonial}/reject', [App\Http\Controllers\Api\TestimonialController::class, 'reject']);
    Route::post('testimonials/{testimonial}/archive', [App\Http\Controllers\Api\TestimonialController::class, 'archive']);
    Route::post('testimonials/{testimonial}/featured', [App\Http\Controllers\Api\TestimonialController::class, 'setFeatured']);
    Route::post('testimonials/{testimonial}/track-click', [App\Http\Controllers\Api\TestimonialController::class, 'trackClick']);
    Route::get('testimonials-analytics', [App\Http\Controllers\Api\TestimonialController::class, 'analytics']);
    Route::get('testimonials-filter-options', [App\Http\Controllers\Api\TestimonialController::class, 'filterOptions']);
    Route::get('testimonials-export', [App\Http\Controllers\Api\TestimonialController::class, 'export']);
    Route::post('testimonials-import', [App\Http\Controllers\Api\TestimonialController::class, 'import']);
});

// Achievement routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('achievements', [App\Http\Controllers\Api\AchievementController::class, 'index']);
    Route::get('achievements/{achievement}', [App\Http\Controllers\Api\AchievementController::class, 'show']);
    Route::get('achievements/leaderboard', [App\Http\Controllers\Api\AchievementController::class, 'leaderboard']);
    Route::get('user/achievements', [App\Http\Controllers\Api\AchievementController::class, 'userAchievements']);
    Route::get('users/{user}/achievements', [App\Http\Controllers\Api\AchievementController::class, 'userAchievements']);
    Route::post('achievements/check', [App\Http\Controllers\Api\AchievementController::class, 'checkAchievements']);
    Route::post('user-achievements/{userAchievement}/toggle-featured', [App\Http\Controllers\Api\AchievementController::class, 'toggleFeatured']);

    // Achievement celebrations
    Route::get('achievement-celebrations', [App\Http\Controllers\Api\AchievementCelebrationController::class, 'index']);
    Route::post('achievement-celebrations', [App\Http\Controllers\Api\AchievementCelebrationController::class, 'create']);
    Route::post('achievement-celebrations/{celebration}/congratulations', [App\Http\Controllers\Api\AchievementCelebrationController::class, 'congratulate']);
    Route::delete('achievement-celebrations/{celebration}/congratulations', [App\Http\Controllers\Api\AchievementCelebrationController::class, 'removeCongratulation']);
    Route::get('achievement-celebrations/{celebration}/congratulations', [App\Http\Controllers\Api\AchievementCelebrationController::class, 'congratulations']);
});

// Student Profile routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/profile', [App\Http\Controllers\Api\StudentProfileController::class, 'show']);
    Route::post('student/profile', [App\Http\Controllers\Api\StudentProfileController::class, 'store']);
    Route::put('student/profile', [App\Http\Controllers\Api\StudentProfileController::class, 'update']);
    Route::get('student/profile/completion', [App\Http\Controllers\Api\StudentProfileController::class, 'completion']);
    Route::get('student/profile/statistics', [App\Http\Controllers\Api\StudentProfileController::class, 'statistics']);
    Route::get('student/courses', [App\Http\Controllers\Api\StudentProfileController::class, 'courses']);
});

// Student-Alumni Story Discovery routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/alumni-stories', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'index']);
    Route::get('student/alumni-stories/recommended', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'recommended']);
    Route::get('student/alumni-stories/career-path', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'byCareerPath']);
    Route::get('student/alumni-stories/same-course', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'fromSameCourse']);
    Route::get('student/alumni-stories/recent-graduates', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'recentGraduates']);
    Route::get('student/alumni-stories/career-insights', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'careerInsights']);
    Route::post('student/alumni-stories/{story}/connect', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'connect']);
    Route::get('student/connections', [App\Http\Controllers\Api\StudentAlumniStoryController::class, 'connections']);
});

// Speaker Bureau routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('speakers', [App\Http\Controllers\Api\SpeakerBureauController::class, 'index']);
    Route::get('speakers/featured', [App\Http\Controllers\Api\SpeakerBureauController::class, 'featured']);
    Route::get('speakers/by-topic', [App\Http\Controllers\Api\SpeakerBureauController::class, 'getByTopic']);
    Route::get('speakers/{speaker}', [App\Http\Controllers\Api\SpeakerBureauController::class, 'show']);
    Route::post('speakers/profile', [App\Http\Controllers\Api\SpeakerBureauController::class, 'createProfile']);
    Route::post('speakers/{speaker}/book', [App\Http\Controllers\Api\SpeakerBureauController::class, 'book']);
    Route::post('speakers/{speaker}/request-booking', [App\Http\Controllers\Api\SpeakerBureauController::class, 'requestBooking']);
    Route::get('speaker/bookings', [App\Http\Controllers\Api\SpeakerBureauController::class, 'getSpeakerBookings']);
    Route::get('my-bookings', [App\Http\Controllers\Api\SpeakerBureauController::class, 'getUserBookings']);
    Route::post('bookings/{booking}/respond', [App\Http\Controllers\Api\SpeakerBureauController::class, 'respondToBooking']);
    Route::post('bookings/{booking}/complete', [App\Http\Controllers\Api\SpeakerBureauController::class, 'completeBooking']);
});
