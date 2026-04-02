<?php

use App\Http\Controllers\Api\AlumniDirectoryController;
use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

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
    Route::post('alumni/map', [\App\Http\Controllers\Api\AlumniMapController::class, 'getAlumniByLocation']);
    Route::post('alumni/map/clusters', [\App\Http\Controllers\Api\AlumniMapController::class, 'getClusters']);
    Route::get('alumni/nearby', [\App\Http\Controllers\Api\AlumniMapController::class, 'getNearbyAlumni']);
    Route::get('alumni/map/heatmap', [\App\Http\Controllers\Api\AlumniMapController::class, 'getHeatmapData']);
    Route::get('alumni/search', [\App\Http\Controllers\Api\AlumniMapController::class, 'searchAlumni']);
    Route::get('regions/{region}/stats', [\App\Http\Controllers\Api\AlumniMapController::class, 'getRegionalStats']);
    Route::get('regions/{region}/groups', [\App\Http\Controllers\Api\AlumniMapController::class, 'getSuggestedGroups']);
    Route::get('alumni/filter-options', [\App\Http\Controllers\Api\AlumniMapController::class, 'getFilterOptions']);
    Route::post('alumni/location', [\App\Http\Controllers\Api\AlumniMapController::class, 'updateLocation']);
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
