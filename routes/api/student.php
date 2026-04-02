<?php

use Illuminate\Support\Facades\Route;

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

// Student Mentorship routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/mentors', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getAlumniMentors']);
    Route::get('student/mentors/recommended', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getRecommendedMentors']);
    Route::get('student/mentors/same-course', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getMentorsFromSameCourse']);
    Route::get('student/mentors/career-specific', [App\Http\Controllers\Api\StudentMentorshipController::class, 'getCareerSpecificMentors']);
    Route::post('student/mentorship/request', [App\Http\Controllers\Api\StudentMentorshipController::class, 'requestMentorship']);
});

// Student Career Guidance routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('student/career/recommendations', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getCareerRecommendations']);
    Route::get('student/career/paths', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getCareerPaths']);
    Route::get('student/career/industry-insights', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getIndustryInsights']);
    Route::get('student/career/salary-insights', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getSalaryInsights']);
    Route::get('student/career/skill-gap-analysis', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getSkillGapAnalysis']);
    Route::get('student/career/job-market-trends', [App\Http\Controllers\Api\StudentCareerGuidanceController::class, 'getJobMarketTrends']);
});
