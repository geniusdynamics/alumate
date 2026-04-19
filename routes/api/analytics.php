<?php

use Illuminate\Support\Facades\Route;

// Public Analytics Tracking routes (no auth required for client-side tracking)
Route::prefix('analytics')->group(function () {
    Route::post('track', [App\Http\Controllers\Api\AnalyticsTrackingController::class, 'track']);
    Route::post('track-template-usage', [App\Http\Controllers\Api\AnalyticsTrackingController::class, 'trackTemplateUsage']);
    Route::get('pixel/{landingPageId}', [App\Http\Controllers\Api\AnalyticsTrackingController::class, 'pixel']);
});

// External Analytics Sync routes
Route::prefix('analytics/external-sync')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\ExternalSyncController::class, 'sync']);
    Route::post('/webhook', [App\Http\Controllers\Api\ExternalSyncController::class, 'webhook']);
    Route::get('/status', [App\Http\Controllers\Api\ExternalSyncController::class, 'status']);
});

// Analytics routes
Route::prefix('analytics')->group(function () {
    // Event tracking endpoints
    Route::post('events', [App\Http\Controllers\Api\AnalyticsController::class, 'storeEvents'])->middleware(['auth:sanctum', 'throttle:analytics_events']);
    Route::post('conversion', [App\Http\Controllers\AnalyticsController::class, 'storeConversion']);
    Route::post('error', [App\Http\Controllers\AnalyticsController::class, 'storeError']);
    Route::post('metrics', [App\Http\Controllers\AnalyticsController::class, 'getMetrics']);
    Route::post('reports/{reportType}', [App\Http\Controllers\AnalyticsController::class, 'generateReport']);
    Route::post('export', [App\Http\Controllers\AnalyticsController::class, 'exportData']);
    Route::post('conversion-report', [App\Http\Controllers\AnalyticsController::class, 'getConversionReport']);

    // Cohort analysis endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('cohorts/create', [App\Http\Controllers\AnalyticsController::class, 'createCohort']);
        Route::get('cohorts/{cohortId}', [App\Http\Controllers\AnalyticsController::class, 'getCohort']);
        Route::post('cohorts/compare', [App\Http\Controllers\AnalyticsController::class, 'compareCohorts']);
        Route::get('cohorts', [App\Http\Controllers\AnalyticsController::class, 'listCohorts']);
    });

    // New Cohort API endpoints
    Route::middleware(['auth:sanctum', 'throttle:cohort'])->group(function () {
        Route::apiResource('cohorts', App\Http\Controllers\Analytics\CohortController::class)->parameters(['cohorts' => 'cohortId']);
        Route::post('cohorts/compare', [App\Http\Controllers\Analytics\CohortController::class, 'compare']);
    });

    // Cohort Analysis API endpoints
    Route::middleware(['auth:sanctum', 'throttle:cohort_analysis'])->prefix('cohort-analysis')->group(function () {
        Route::get('/', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'index']);
        Route::post('/', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'store']);
        Route::get('/{id}', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'show']);
        Route::put('/{id}', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'destroy']);
        Route::get('/{id}/retention', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'retention']);
        Route::get('/{id}/engagement', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'engagement']);
        Route::get('/{id}/conversion', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'conversion']);
        Route::post('/compare', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'compare']);
        Route::get('/{id}/trends', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'trends']);
        Route::get('/{id}/insights', [App\Http\Controllers\Analytics\CohortAnalysisController::class, 'insights']);
    });

    // Attribution analysis endpoints
    Route::middleware(['throttle:analytics_attribution'])->group(function () {
        Route::post('attribution/track-touch', [App\Http\Controllers\AnalyticsController::class, 'trackTouchpoint']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('attribution/models/{userId}', [App\Http\Controllers\AnalyticsController::class, 'getUserAttribution']);
        Route::get('attribution/channels', [App\Http\Controllers\AnalyticsController::class, 'getChannelPerformance']);
        Route::get('attribution/budget-recommendations', [App\Http\Controllers\AnalyticsController::class, 'getBudgetRecommendations']);
    });

    // New Attribution API endpoints
    Route::middleware(['auth:sanctum', 'throttle:attribution'])->group(function () {
        Route::get('analytics/attribution', [App\Http\Controllers\Analytics\AttributionController::class, 'index']);
        Route::post('analytics/attribution/touches', [App\Http\Controllers\Analytics\AttributionController::class, 'store']);
        Route::get('analytics/attribution/{userId?}', [App\Http\Controllers\Analytics\AttributionController::class, 'show']);
        Route::get('analytics/attribution-summary', [App\Http\Controllers\Analytics\AttributionController::class, 'summary']);
    });

    // Attribution Analysis API endpoints
    Route::middleware(['auth:sanctum', 'throttle:attribution_analysis'])->prefix('analytics/attribution-analysis')->group(function () {
        Route::get('/touchpoints', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'index']);
        Route::post('/touchpoints', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'store']);
        Route::get('/touchpoints/{id}', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'show']);
        Route::get('/calculate/{userId}', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'calculate']);
        Route::get('/compare/{userId}', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'compareModels']);
        Route::get('/channels/performance', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'channelPerformance']);
        Route::get('/budget/recommendations', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'budgetRecommendations']);
        Route::get('/conversion-path/{userId}', [App\Http\Controllers\Analytics\AttributionAnalysisController::class, 'conversionPath']);
    });

    // Custom Event endpoints
    Route::middleware(['throttle:analytics_custom_events', 'auth:sanctum'])->group(function () {
        Route::post('custom-events/define', [App\Http\Controllers\AnalyticsController::class, 'defineCustomEvent']);
        Route::post('custom-events/track', [App\Http\Controllers\AnalyticsController::class, 'trackCustomEvent'])->middleware('throttle:analytics_tracking');
        Route::get('custom-events', [App\Http\Controllers\AnalyticsController::class, 'listCustomEvents']);
        Route::get('custom-events/{eventName}/analysis', [App\Http\Controllers\AnalyticsController::class, 'getEventAnalysis']);

        // New Custom Event System routes
        Route::get('analytics/custom-events/definitions', [App\Http\Controllers\Analytics\CustomEventController::class, 'index']);
        Route::post('analytics/custom-events/definitions', [App\Http\Controllers\Analytics\CustomEventController::class, 'storeDefinition']);
        Route::get('analytics/custom-events/definitions/{id}', [App\Http\Controllers\Analytics\CustomEventController::class, 'show']);
        Route::put('analytics/custom-events/definitions/{id}', [App\Http\Controllers\Analytics\CustomEventController::class, 'update']);
        Route::delete('analytics/custom-events/definitions/{id}', [App\Http\Controllers\Analytics\CustomEventController::class, 'destroy']);
        Route::post('analytics/custom-events/track', [App\Http\Controllers\Analytics\CustomEventController::class, 'track']);
        Route::get('analytics/custom-events/{definitionId}/analyze', [App\Http\Controllers\Analytics\CustomEventController::class, 'analyze']);
        Route::get('analytics/custom-events/{definitionId}/analytics', [App\Http\Controllers\Analytics\CustomEventController::class, 'analytics']);
        Route::get('analytics/custom-events/{definitionId}/behavior-flow', [App\Http\Controllers\Analytics\CustomEventController::class, 'behaviorFlow']);
        Route::get('analytics/custom-events/{definitionId}/optimization-suggestions', [App\Http\Controllers\Analytics\CustomEventController::class, 'optimizationSuggestions']);
        Route::get('analytics/custom-events/user/{userId}/behavior-flow', [App\Http\Controllers\Analytics\CustomEventController::class, 'behaviorFlowByUser']);
        Route::post('analytics/custom-events/funnel', [App\Http\Controllers\Analytics\CustomEventController::class, 'funnel']);
    });

    // Matomo Analytics endpoints
    Route::middleware(['throttle:analytics_matomo'])->group(function () {
        Route::post('matomo/track', [App\Http\Controllers\AnalyticsController::class, 'trackMatomoEvent']);
        Route::post('matomo/sync-goals', [App\Http\Controllers\AnalyticsController::class, 'syncMatomoGoals']);
        Route::get('matomo/segments', [App\Http\Controllers\AnalyticsController::class, 'getMatomoSegments']);
    });

    // Data Synchronization endpoints
    Route::middleware(['auth:sanctum', 'throttle:analytics_sync'])->group(function () {
        Route::post('sync/run', [App\Http\Controllers\AnalyticsController::class, 'runSync']);
        Route::get('sync/status', [App\Http\Controllers\AnalyticsController::class, 'getSyncStatus']);
    });

    // External Platform Integration endpoints
    Route::middleware(['auth:sanctum', 'throttle:analytics_external'])->prefix('external-integrations')->group(function () {
        Route::get('unified-data', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getUnifiedData']);
        Route::post('sync-events', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'syncEvents']);
        Route::get('sync-status', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getSyncStatus']);
        Route::get('discrepancies', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getDiscrepancies']);
        Route::post('resolve-discrepancies', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'resolveDiscrepancies']);
        Route::get('validate-configuration', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'validateConfiguration']);
        Route::post('google-analytics/goals', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'createGoogleAnalyticsGoal']);
        Route::post('google-analytics/audiences', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'createGoogleAnalyticsAudience']);
        Route::post('google-analytics/sync-goals', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'syncGoogleAnalyticsGoals']);
        Route::post('google-analytics/export-segments', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'exportGoogleAnalyticsSegments']);
        Route::post('google-analytics/report', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getGoogleAnalyticsReport']);
        Route::get('google-analytics/realtime', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getGoogleAnalyticsRealtimeData']);
        Route::post('matomo/goals', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'createMatomoGoal']);
        Route::post('matomo/segments', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'createMatomoSegment']);
        Route::post('matomo/sync-data', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'syncMatomoData']);
        Route::post('matomo/report', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getMatomoReport']);
        Route::get('matomo/realtime', [App\Http\Controllers\Analytics\ExternalIntegrationController::class, 'getMatomoRealtimeData']);
    });

    // Insights endpoints
    Route::middleware(['auth:sanctum', 'throttle:insights'])->prefix('insights')->group(function () {
        Route::get('/', [App\Http\Controllers\Analytics\InsightsController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\Analytics\InsightsController::class, 'show']);
        Route::post('/', [App\Http\Controllers\Analytics\InsightsController::class, 'store']);
        Route::put('/{id}', [App\Http\Controllers\Analytics\InsightsController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\Analytics\InsightsController::class, 'destroy']);
        Route::post('/generate', [App\Http\Controllers\Analytics\InsightsController::class, 'generate']);
        Route::post('/export', [App\Http\Controllers\Analytics\InsightsController::class, 'export']);
        Route::post('/{id}/dismiss', [App\Http\Controllers\Analytics\InsightsController::class, 'dismiss']);
        Route::post('/{insightId}/feedback', [App\Http\Controllers\Analytics\InsightsController::class, 'trackFeedback']);
    });

    // Learning analytics endpoints
    Route::middleware(['auth:sanctum', 'throttle:learning', \App\Http\Middleware\ConsentMiddleware::class])->group(function () {
        Route::get('learning', [App\Http\Controllers\Analytics\LearningController::class, 'index']);
        Route::post('learning', [App\Http\Controllers\Analytics\LearningController::class, 'storeInteraction']);
        Route::get('learning/{userId}/{courseId}', [App\Http\Controllers\Analytics\LearningController::class, 'show']);
        Route::patch('learning/{userId}', [App\Http\Controllers\Analytics\LearningController::class, 'updateProgress']);

        Route::prefix('learning-analytics')->group(function () {
            Route::get('/', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'index']);
            Route::get('/user/{userId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'show']);
            Route::post('/progress/track', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'trackProgress']);
            Route::get('/progress/{userId}/{courseId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'getProgress']);
            Route::get('/outcomes/{userId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'analyzeOutcomes']);
            Route::get('/metrics/{userId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'getMetrics']);
            Route::post('/compare', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'comparePerformance']);
            Route::get('/predict/{userId}/{courseId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'predictCompletion']);
            Route::get('/recommendations/{userId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'getRecommendations']);
            Route::post('/activity/track', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'trackActivity']);
            Route::get('/certification/{userId}/{courseId}', [App\Http\Controllers\Analytics\LearningAnalyticsController::class, 'verifyCertification']);
        });
    });
});

// A/B Testing routes
Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('ab-tests')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\AbTestController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\AbTestController::class, 'store']);
    Route::get('{id}', [App\Http\Controllers\Api\AbTestController::class, 'show']);
    Route::put('{id}', [App\Http\Controllers\Api\AbTestController::class, 'update']);
    Route::delete('{id}', [App\Http\Controllers\Api\AbTestController::class, 'destroy']);
    Route::get('{id}/results', [App\Http\Controllers\Api\AbTestController::class, 'results']);
});

// Performance monitoring routes
Route::post('performance/metrics', [App\Http\Controllers\Api\PerformanceController::class, 'storeMetrics']);
Route::get('performance/analytics', [App\Http\Controllers\Api\PerformanceController::class, 'getAnalytics']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('performance/sessions', [App\Http\Controllers\Api\PerformanceController::class, 'storeSessions']);
    Route::get('performance/real-time', [App\Http\Controllers\Api\PerformanceController::class, 'getRealTimeMetrics']);
    Route::get('performance/core-web-vitals', [App\Http\Controllers\Api\PerformanceController::class, 'getCoreWebVitals']);
    Route::get('performance/recommendations', [App\Http\Controllers\Api\PerformanceController::class, 'getRecommendations']);
    Route::get('performance/page', [App\Http\Controllers\Api\PerformanceController::class, 'getPagePerformance']);
});

// Career Outcome Analytics routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('career-analytics', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'index']);
    Route::get('career-analytics/overview', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'overview']);
    Route::get('career-analytics/program-effectiveness', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'programEffectiveness']);
    Route::post('career-analytics/program-effectiveness/generate', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateProgramEffectiveness']);
    Route::get('career-analytics/salary-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'salaryAnalysis']);
    Route::get('career-analytics/industry-placement', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'industryPlacement']);
    Route::post('career-analytics/industry-placement/generate', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateIndustryPlacement']);
    Route::get('career-analytics/demographic-outcomes', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'demographicOutcomes']);
    Route::get('career-analytics/career-path-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'careerPathAnalysis']);
    Route::get('career-analytics/trend-analysis', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'trendAnalysis']);
    Route::post('career-analytics/generate-snapshot', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'generateSnapshot']);
    Route::get('career-analytics/snapshots', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'snapshots']);
    Route::get('career-analytics/filter-options', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'filterOptions']);
    Route::post('career-analytics/export', [App\Http\Controllers\Api\CareerOutcomeAnalyticsController::class, 'export']);
});
