<?php

use App\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

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
