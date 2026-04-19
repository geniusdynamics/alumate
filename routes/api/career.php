<?php

use App\Http\Controllers\Api\CareerTimelineController;
use App\Http\Controllers\Api\JobMatchingController;
use App\Http\Controllers\Api\SkillsController;
use Illuminate\Support\Facades\Route;

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

// Job Matching routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('jobs/recommendations', [JobMatchingController::class, 'getRecommendations']);
    Route::get('jobs/{jobId}', [JobMatchingController::class, 'getJobDetails']);
    Route::get('jobs/{jobId}/connections', [JobMatchingController::class, 'getJobConnections']);
    Route::post('jobs/{jobId}/apply', [JobMatchingController::class, 'apply']);
    Route::get('applications', [JobMatchingController::class, 'getApplications']);
    Route::post('jobs/{jobId}/request-introduction', [JobMatchingController::class, 'requestIntroduction']);
});

// Job routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('jobs/{job}/save', [App\Http\Controllers\Api\JobController::class, 'save']);
    Route::delete('jobs/{job}/save', [App\Http\Controllers\Api\JobController::class, 'unsave']);
    Route::get('jobs/{job}', [App\Http\Controllers\Api\JobController::class, 'show']);
});

// Skills Development routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/{userId}/skills', [SkillsController::class, 'getUserSkills']);
    Route::post('users/skills', [SkillsController::class, 'addSkill']);
    Route::post('skills/endorse', [SkillsController::class, 'endorseSkill']);
    Route::get('skills/search', [SkillsController::class, 'searchSkills']);
    Route::get('skills/suggestions', [SkillsController::class, 'getSkillSuggestions']);
    Route::get('skills/{skillId}/progression', [SkillsController::class, 'getSkillProgression']);
    Route::get('skills/{skillId}/recommendations', [SkillsController::class, 'getLearningRecommendations']);
    Route::get('skills/gap-analysis', [SkillsController::class, 'getSkillsGapAnalysis']);
    Route::get('learning-resources', [SkillsController::class, 'getResources']);
    Route::post('learning-resources', [SkillsController::class, 'createLearningResource']);
    Route::post('learning-resources/{resource}/rate', [SkillsController::class, 'rateResource']);
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
