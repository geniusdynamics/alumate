<?php

use App\Http\Controllers\Api\MentorshipController;
use Illuminate\Support\Facades\Route;

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

// Student Mentorship routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/mentors', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getAlumniMentors']);
    Route::get('student/mentors/recommended', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getRecommendedMentors']);
    Route::get('student/mentors/same-course', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getMentorsFromSameCourse']);
    Route::get('student/mentors/career-specific', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getCareerSpecificMentors']);
    Route::post('student/mentorship/request', [App\Http\Controllers\Api\StudentMentorshipController::class, 'requestMentorship']);
});
