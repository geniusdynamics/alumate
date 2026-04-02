<?php

use Illuminate\Support\Facades\Route;

// Landing Page Management routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('landing-pages')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\LandingPageController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\LandingPageController::class, 'store']);
    Route::get('/drafts', [App\Http\Controllers\Api\LandingPageController::class, 'drafts']);
    Route::get('/published', [App\Http\Controllers\Api\LandingPageController::class, 'published']);
    Route::post('/create-from-template', [App\Http\Controllers\Api\LandingPageController::class, 'createFromTemplate']);
    Route::post('/bulk', [App\Http\Controllers\Api\LandingPageController::class, 'bulk']);
    Route::get('/status/{status}', [App\Http\Controllers\Api\LandingPageController::class, 'byStatus']);

    Route::prefix('{landingPage}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\LandingPageController::class, 'show']);
        Route::put('/', [App\Http\Controllers\Api\LandingPageController::class, 'update']);
        Route::delete('/', [App\Http\Controllers\Api\LandingPageController::class, 'destroy']);
        Route::post('/duplicate', [App\Http\Controllers\Api\LandingPageController::class, 'duplicate']);
        Route::post('/publish', [App\Http\Controllers\Api\LandingPageController::class, 'publish']);
        Route::post('/unpublish', [App\Http\Controllers\Api\LandingPageController::class, 'unpublish']);
        Route::post('/archive', [App\Http\Controllers\Api\LandingPageController::class, 'archive']);
        Route::get('/analytics', [App\Http\Controllers\Api\LandingPageController::class, 'analytics']);
    });
});
