<?php

use Illuminate\Support\Facades\Route;

// Component Library routes
Route::middleware(['auth:sanctum'])->prefix('components')->name('components.')->group(function () {
    Route::apiResource('components', App\Http\Controllers\Api\ComponentController::class)->names([
        'index' => 'components.index',
        'store' => 'components.store',
        'show' => 'components.show',
        'update' => 'components.update',
        'destroy' => 'components.destroy',
    ])->parameters(['components' => 'component']);

    Route::post('{component}/duplicate', [App\Http\Controllers\Api\ComponentController::class, 'duplicate'])->name('components.duplicate');
    Route::post('{component}/activate', [App\Http\Controllers\Api\ComponentController::class, 'activate']);
    Route::post('{component}/deactivate', [App\Http\Controllers\Api\ComponentController::class, 'deactivate']);
    Route::get('{component}/preview', [App\Http\Controllers\Api\ComponentController::class, 'preview']);
    Route::get('{component}/usage', [App\Http\Controllers\Api\ComponentController::class, 'usage']);
    Route::get('{component}/versions', [App\Http\Controllers\Api\ComponentController::class, 'versions']);
    Route::post('{component}/versions', [App\Http\Controllers\Api\ComponentController::class, 'createVersion']);
    Route::get('search', [App\Http\Controllers\Api\ComponentController::class, 'search']);
    Route::get('categories/{category}', [App\Http\Controllers\Api\ComponentController::class, 'byCategory']);
    Route::get('types/{type}', [App\Http\Controllers\Api\ComponentController::class, 'byType']);
    Route::get('{component}/instances', [App\Http\Controllers\Api\ComponentController::class, 'instances']);
    Route::post('{component}/instances', [App\Http\Controllers\Api\ComponentController::class, 'createInstance']);
    Route::get('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'showInstance']);
    Route::put('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'updateInstance']);
    Route::delete('instances/{instance}', [App\Http\Controllers\Api\ComponentController::class, 'deleteInstance']);
    Route::post('instances/{instance}/move', [App\Http\Controllers\Api\ComponentController::class, 'moveInstance']);
    Route::post('instances/{instance}/duplicate', [App\Http\Controllers\Api\ComponentController::class, 'duplicateInstance']);
    Route::get('{component}/analytics', [App\Http\Controllers\Api\ComponentController::class, 'analytics']);
    Route::post('{component}/track-view', [App\Http\Controllers\Api\ComponentController::class, 'trackView']);
    Route::post('{component}/track-click', [App\Http\Controllers\Api\ComponentController::class, 'trackClick']);
    Route::post('{component}/track-conversion', [App\Http\Controllers\Api\ComponentController::class, 'trackConversion']);
    Route::post('bulk-activate', [App\Http\Controllers\Api\ComponentController::class, 'bulkActivate']);
    Route::post('bulk-deactivate', [App\Http\Controllers\Api\ComponentController::class, 'bulkDeactivate']);
    Route::post('bulk-delete', [App\Http\Controllers\Api\ComponentController::class, 'bulkDelete']);
    Route::post('import', [App\Http\Controllers\Api\ComponentController::class, 'import']);
    Route::get('{component}/export', [App\Http\Controllers\Api\ComponentController::class, 'export']);
    Route::post('bulk-export', [App\Http\Controllers\Api\ComponentController::class, 'bulkExport']);
});

// Component Theme Management routes
Route::middleware(['auth:sanctum'])->prefix('component-themes')->name('component-themes.')->group(function () {
    Route::apiResource('themes', App\Http\Controllers\Api\ComponentThemeController::class)->names([
        'index' => 'component-themes.index',
        'store' => 'component-themes.store',
        'show' => 'component-themes.show',
        'update' => 'component-themes.update',
        'destroy' => 'component-themes.destroy',
    ])->parameters(['themes' => 'theme']);
    Route::get('grapejs', [App\Http\Controllers\Api\ComponentThemeController::class, 'grapeJSIndex'])->name('component-themes.grapejs');
    Route::post('{theme}/duplicate', [App\Http\Controllers\Api\ComponentThemeController::class, 'duplicate'])->name('component-themes.duplicate');
    Route::post('{theme}/apply', [App\Http\Controllers\Api\ComponentThemeController::class, 'apply']);
    Route::get('{theme}/preview', [App\Http\Controllers\Api\ComponentThemeController::class, 'preview']);
    Route::get('{theme}/usage', [App\Http\Controllers\Api\ComponentThemeController::class, 'usage']);
    Route::get('{theme}/cached', [App\Http\Controllers\Api\ComponentThemeController::class, 'cached']);
    Route::delete('{theme}/cache', [App\Http\Controllers\Api\ComponentThemeController::class, 'clearCache']);
    Route::post('import', [App\Http\Controllers\Api\ComponentThemeController::class, 'import']);
    Route::get('{theme}/export', [App\Http\Controllers\Api\ComponentThemeController::class, 'export']);
    Route::post('validate', [App\Http\Controllers\Api\ComponentThemeController::class, 'validate']);
    Route::post('bulk', [App\Http\Controllers\Api\ComponentThemeController::class, 'bulk']);
});

// Component Version Control and Export System routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('components/{component}/versions', [App\Http\Controllers\Api\ComponentVersionController::class, 'index']);
    Route::post('components/{component}/versions', [App\Http\Controllers\Api\ComponentVersionController::class, 'store']);
    Route::get('components/{component}/versions/{version}', [App\Http\Controllers\Api\ComponentVersionController::class, 'show']);
    Route::post('components/{component}/versions/{version}/restore', [App\Http\Controllers\Api\ComponentVersionController::class, 'restore']);
    Route::post('components/{component}/versions/compare', [App\Http\Controllers\Api\ComponentVersionController::class, 'compare']);
    Route::post('components/{component}/export', [App\Http\Controllers\Api\ComponentVersionController::class, 'export']);
    Route::post('components/import', [App\Http\Controllers\Api\ComponentVersionController::class, 'import']);
    Route::post('components/create-template', [App\Http\Controllers\Api\ComponentVersionController::class, 'createTemplate']);
    Route::get('components/{component}/performance/analyze', [App\Http\Controllers\Api\ComponentVersionController::class, 'analyzePerformance']);
    Route::get('components/{component}/performance/trends', [App\Http\Controllers\Api\ComponentVersionController::class, 'performanceTrends']);
    Route::post('components/{component}/performance/compare', [App\Http\Controllers\Api\ComponentVersionController::class, 'comparePerformance']);
    Route::post('components/{component}/backup', [App\Http\Controllers\Api\ComponentVersionController::class, 'createBackup']);
    Route::get('components/{component}/backups', [App\Http\Controllers\Api\ComponentVersionController::class, 'listBackups']);
    Route::post('components/restore-backup', [App\Http\Controllers\Api\ComponentVersionController::class, 'restoreBackup']);
    Route::post('components/{component}/migrate', [App\Http\Controllers\Api\ComponentVersionController::class, 'migrate']);
});

// Component Library Bridge routes for GrapeJS integration
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('components/bridge')->group(function () {
        Route::get('initialize', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'initialize']);
        Route::get('categories', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getCategories']);
        Route::get('search', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'searchComponents']);
        Route::post('track-usage', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'trackUsage']);
        Route::post('track-rating', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'trackRating']);
        Route::get('usage-stats/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getUsageStats']);
        Route::get('most-used', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getMostUsed']);
        Route::get('recently-used', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getRecentlyUsed']);
        Route::get('trending', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getTrending']);
        Route::get('analytics', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getAnalytics']);
        Route::get('documentation/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getDocumentation']);
        Route::get('tooltip/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getTooltip']);
        Route::get('validate/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'validateComponent']);
        Route::get('grapeJS-data/{componentId}', [App\Http\Controllers\Api\ComponentLibraryBridgeController::class, 'getGrapeJSData']);
    });
});

// Component Analytics Tracking
Route::middleware('auth:sanctum')->prefix('components/analytics')->group(function () {
    Route::post('view', function (\Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);
            $componentService->recordView($request->input('component_instance_id'), auth()->id(), session()->getId(), [
                'view_duration' => $request->input('view_duration'),
                'scroll_depth' => $request->input('scroll_depth'),
                'interactions' => $request->input('interactions', 0),
            ]);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });
    Route::post('click', function (\Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);
            $componentService->recordClick($request->input('component_instance_id'), auth()->id(), session()->getId(), [
                'element_id' => $request->input('element_id'),
                'element_class' => $request->input('element_class'),
                'click_x' => $request->input('click_x'),
                'click_y' => $request->input('click_y'),
                'element_text' => $request->input('element_text'),
            ]);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });
    Route::post('conversion', function (\Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);
            $componentService->recordConversion($request->input('component_instance_id'), auth()->id(), session()->getId(), [
                'conversion_type' => $request->input('conversion_type'),
                'conversion_value' => $request->input('conversion_value'),
                'funnel_step' => $request->input('funnel_step'),
                'source_url' => $request->input('source_url'),
            ]);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });
    Route::post('form-submit', function (\Illuminate\Http\Request $request) {
        try {
            $componentService = app(App\Services\ComponentAnalyticsService::class);
            $componentService->recordFormSubmit($request->input('component_instance_id'), auth()->id(), session()->getId(), [
                'form_id' => $request->input('form_id'),
                'fields_count' => $request->input('fields_count'),
                'completion_time' => $request->input('completion_time'),
                'validation_errors' => $request->input('validation_errors'),
                'form_type' => $request->input('form_type'),
            ]);

            return response()->json(['status' => 'recorded']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    });
});
