<?php

use Illuminate\Support\Facades\Route;

// Template Management routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('templates')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\TemplateController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\TemplateController::class, 'store']);
    Route::get('/search', [App\Http\Controllers\Api\TemplateController::class, 'search']);
    Route::get('/popular', [App\Http\Controllers\Api\TemplateController::class, 'popular']);
    Route::get('/recent', [App\Http\Controllers\Api\TemplateController::class, 'recent']);
    Route::get('/premium', [App\Http\Controllers\Api\TemplateController::class, 'premium']);
    Route::get('/by-audience', [App\Http\Controllers\Api\TemplateController::class, 'byAudience']);
    Route::get('/categories', [App\Http\Controllers\Api\TemplateController::class, 'categories']);
    Route::get('/preview-options', [App\Http\Controllers\Api\TemplatePreviewController::class, 'previewOptions']);

    Route::prefix('{template}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\TemplateController::class, 'show']);
        Route::put('/', [App\Http\Controllers\Api\TemplateController::class, 'update']);
        Route::delete('/', [App\Http\Controllers\Api\TemplateController::class, 'destroy']);
        Route::post('/duplicate', [App\Http\Controllers\Api\TemplateController::class, 'duplicate']);
        Route::get('/preview', [App\Http\Controllers\Api\TemplateController::class, 'preview']);
        Route::post('/activate', [App\Http\Controllers\Api\TemplateController::class, 'activate']);
        Route::post('/deactivate', [App\Http\Controllers\Api\TemplateController::class, 'deactivate']);
        Route::get('/stats', [App\Http\Controllers\Api\TemplateController::class, 'stats']);

        // Template Preview routes
        Route::prefix('preview')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\TemplatePreviewController::class, 'preview']);
            Route::get('/responsive', [App\Http\Controllers\Api\TemplatePreviewController::class, 'responsivePreview']);
            Route::post('/apply-brand', [App\Http\Controllers\Api\TemplatePreviewController::class, 'applyBrand']);
            Route::get('/assets', [App\Http\Controllers\Api\TemplatePreviewController::class, 'assets']);
            Route::post('/clear-cache', [App\Http\Controllers\Api\TemplatePreviewController::class, 'clearCache']);
        });
    });
});

// Template Analytics routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('templates/analytics', [App\Http\Controllers\Api\TemplateAnalyticsController::class, 'index']);
    Route::get('templates/{template}/analytics', [App\Http\Controllers\Api\TemplateAnalyticsController::class, 'show']);
});
