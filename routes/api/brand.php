<?php

use Illuminate\Support\Facades\Route;

// Brand Customizer routes
Route::middleware(['auth:sanctum'])->prefix('brand-customizer')->group(function () {
    Route::get('data', [App\Http\Controllers\Api\BrandCustomizerController::class, 'getData']);

    // Logo management
    Route::post('logos', [App\Http\Controllers\Api\BrandCustomizerController::class, 'uploadLogos']);
    Route::post('logos/{logo}/set-primary', [App\Http\Controllers\Api\BrandCustomizerController::class, 'setPrimaryLogo']);
    Route::post('logos/{logo}/optimize', [App\Http\Controllers\Api\BrandCustomizerController::class, 'optimizeLogo']);
    Route::delete('logos/{logo}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteLogo']);

    // Color management
    Route::post('colors', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeColor']);
    Route::put('colors/{color}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateColor']);
    Route::delete('colors/{color}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteColor']);

    // Font management
    Route::post('fonts/upload', [App\Http\Controllers\Api\BrandCustomizerController::class, 'uploadFonts']);
    Route::post('fonts', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeFont']);
    Route::put('fonts/{font}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateFont']);
    Route::post('fonts/{font}/set-primary', [App\Http\Controllers\Api\BrandCustomizerController::class, 'setPrimaryFont']);
    Route::delete('fonts/{font}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'deleteFont']);

    // Template management
    Route::post('templates', [App\Http\Controllers\Api\BrandCustomizerController::class, 'storeTemplate']);
    Route::put('templates/{template}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateTemplate']);
    Route::post('templates/{template}/apply', [App\Http\Controllers\Api\BrandCustomizerController::class, 'applyTemplate']);
    Route::post('templates/{template}/duplicate', [App\Http\Controllers\Api\BrandCustomizerController::class, 'duplicateTemplate']);

    // Brand consistency
    Route::post('consistency-check', [App\Http\Controllers\Api\BrandCustomizerController::class, 'consistencyCheck']);
    Route::post('auto-fix/{issue}', [App\Http\Controllers\Api\BrandCustomizerController::class, 'autoFixIssue']);

    // Guidelines and export
    Route::put('guidelines', [App\Http\Controllers\Api\BrandCustomizerController::class, 'updateGuidelines']);
    Route::post('export', [App\Http\Controllers\Api\BrandCustomizerController::class, 'exportAssets']);
});

// Brand Configuration Management routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('brand-configs')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\BrandConfigController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\BrandConfigController::class, 'store']);
    Route::get('/search', [App\Http\Controllers\Api\BrandConfigController::class, 'search']);
    Route::post('/import', [App\Http\Controllers\Api\BrandConfigController::class, 'import']);

    Route::prefix('{brandConfig}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\BrandConfigController::class, 'show']);
        Route::put('/', [App\Http\Controllers\Api\BrandConfigController::class, 'update']);
        Route::delete('/', [App\Http\Controllers\Api\BrandConfigController::class, 'destroy']);
        Route::post('/upload-logo', [App\Http\Controllers\Api\BrandConfigController::class, 'uploadLogo']);
        Route::post('/upload-favicon', [App\Http\Controllers\Api\BrandConfigController::class, 'uploadFavicon']);
        Route::post('/upload-asset', [App\Http\Controllers\Api\BrandConfigController::class, 'uploadCustomAsset']);
        Route::post('/apply-to-template/{templateId}', [App\Http\Controllers\Api\BrandConfigController::class, 'applyToTemplate']);
        Route::post('/preview', [App\Http\Controllers\Api\BrandConfigController::class, 'preview']);
        Route::post('/export', [App\Http\Controllers\Api\BrandConfigController::class, 'export']);
    });
});
