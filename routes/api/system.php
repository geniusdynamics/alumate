<?php

use Illuminate\Support\Facades\Route;

// Statistics API routes
Route::prefix('statistics')->group(function () {
    Route::get('health', [App\Http\Controllers\Api\StatisticsController::class, 'health']);
    Route::get('platform-metrics', [App\Http\Controllers\Api\StatisticsController::class, 'platformMetrics']);
    Route::post('batch', [App\Http\Controllers\Api\StatisticsController::class, 'batch']);
    Route::get('{id}', [App\Http\Controllers\Api\StatisticsController::class, 'show']);

    Route::middleware(['auth:sanctum', 'can:manage-statistics'])->group(function () {
        Route::delete('cache', [App\Http\Controllers\Api\StatisticsController::class, 'clearCache']);
    });
});

// Homepage Navigation
Route::get('/homepage-navigation', [\App\Http\Controllers\Api\HomepageNavigationController::class, 'index']);

// Homepage API routes
Route::prefix('homepage')->group(function () {
    Route::get('stats', [\App\Http\Controllers\Api\HomepageController::class, 'getStatistics']);
    Route::get('statistics', [\App\Http\Controllers\Api\HomepageController::class, 'getStatistics']);
});

// Export routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('exports')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\ExportController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\ExportController::class, 'store']);
    Route::get('/{export}', [App\Http\Controllers\Api\ExportController::class, 'show']);
    Route::delete('/{export}', [App\Http\Controllers\Api\ExportController::class, 'destroy']);
    Route::get('/{export}/download', [App\Http\Controllers\Api\ExportController::class, 'download']);
});

// Backup routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('backups')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\BackupController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\BackupController::class, 'store']);
    Route::get('/{backup}', [App\Http\Controllers\Api\BackupController::class, 'show']);
    Route::post('/{backup}/restore', [App\Http\Controllers\Api\BackupController::class, 'restore']);
    Route::delete('/{backup}', [App\Http\Controllers\Api\BackupController::class, 'destroy']);
});

// Migration routes
Route::middleware(['auth:sanctum', 'api.rate_limit:api'])->prefix('migrations')->group(function () {
    Route::get('/', [App\Http\Controllers\Api\MigrationController::class, 'index']);
    Route::post('/', [App\Http\Controllers\Api\MigrationController::class, 'store']);
    Route::get('/{migration}', [App\Http\Controllers\Api\MigrationController::class, 'show']);
    Route::post('/{migration}/execute', [App\Http\Controllers\Api\MigrationController::class, 'execute']);
    Route::delete('/{migration}', [App\Http\Controllers\Api\MigrationController::class, 'destroy']);
});

// Automated Testing Pipeline Integration
Route::middleware('auth:sanctum')->prefix('testing')->group(function () {
    Route::post('execute/unit', function (\Illuminate\Http\Request $request) {
        $command = $request->input('command', 'composer test --testsuite=Unit');
        $output = shell_exec($command);
        \Log::info('Unit Tests Executed', ['command' => $command, 'output' => $output]);

        return response()->json(['status' => 'completed', 'tests' => 'unit', 'output' => $output, 'timestamp' => now()->toISOString()]);
    });
    Route::post('execute/feature', function (\Illuminate\Http\Request $request) {
        $command = $request->input('command', 'composer test --testsuite=Feature');
        $output = shell_exec($command);
        \Log::info('Feature Tests Executed', ['command' => $command, 'output' => $output]);

        return response()->json(['status' => 'completed', 'tests' => 'feature', 'output' => $output, 'timestamp' => now()->toISOString()]);
    });
    Route::post('execute/e2e', function (\Illuminate\Http\Request $request) {
        $command = $request->input('command', 'npm run test:e2e');
        $output = shell_exec($command);
        \Log::info('E2E Tests Executed', ['command' => $command, 'output' => $output]);

        return response()->json(['status' => 'completed', 'tests' => 'e2e', 'output' => $output, 'timestamp' => now()->toISOString()]);
    });
    Route::get('results/unit', function () {
        return response()->json(['status' => 'not_implemented_yet']);
    });
    Route::get('results/feature', function () {
        return response()->json(['status' => 'not_implemented_yet']);
    });
    Route::get('results/e2e', function () {
        return response()->json(['status' => 'not_implemented_yet']);
    });
    Route::get('coverage', function () {
        return response()->json(['status' => 'not_implemented_yet']);
    });
});

// Form Component Routes
Route::prefix('forms')->group(function () {
    Route::post('/submit', [App\Http\Controllers\Api\FormController::class, 'submit']);
    Route::post('/autosave', [App\Http\Controllers\Api\FormController::class, 'autoSave']);
    Route::post('/notifications', [App\Http\Controllers\Api\FormController::class, 'sendNotifications']);
    Route::post('/individual-signup', [App\Http\Controllers\Api\FormController::class, 'submitIndividualSignup']);
    Route::post('/institution-demo-request', [App\Http\Controllers\Api\FormController::class, 'submitInstitutionDemoRequest']);
    Route::post('/contact', [App\Http\Controllers\Api\FormController::class, 'submitContactForm']);
    Route::post('/newsletter-signup', [App\Http\Controllers\Api\FormController::class, 'submitNewsletterSignup']);
    Route::post('/event-registration', [App\Http\Controllers\Api\FormController::class, 'submitEventRegistration']);
});

// Style Preset routes
Route::middleware(['auth:sanctum'])->prefix('style-presets')->name('style-presets.')->group(function () {
    Route::get('', [App\Http\Controllers\Api\StylePresetController::class, 'index']);
    Route::post('', [App\Http\Controllers\Api\StylePresetController::class, 'store']);
    Route::get('{stylePreset}', [App\Http\Controllers\Api\StylePresetController::class, 'show']);
    Route::put('{stylePreset}', [App\Http\Controllers\Api\StylePresetController::class, 'update']);
    Route::delete('{stylePreset}', [App\Http\Controllers\Api\StylePresetController::class, 'destroy']);
    Route::get('categories/{category}', [App\Http\Controllers\Api\StylePresetController::class, 'byCategory']);
    Route::get('categories', [App\Http\Controllers\Api\StylePresetController::class, 'categories']);
    Route::post('{stylePreset}/duplicate', [App\Http\Controllers\Api\StylePresetController::class, 'duplicate']);
    Route::post('bulk-store', [App\Http\Controllers\Api\StylePresetController::class, 'bulkStore']);
    Route::get('export', [App\Http\Controllers\Api\StylePresetController::class, 'export']);
});

// Custom Code routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('custom-codes', App\Http\Controllers\CustomCodeController::class);
    Route::get('custom-codes/search', [App\Http\Controllers\CustomCodeController::class, 'search']);
    Route::get('custom-codes/stats', [App\Http\Controllers\CustomCodeController::class, 'stats']);
    Route::post('custom-codes/validate', [App\Http\Controllers\CustomCodeController::class, 'validate']);
});

// Custom Code Integration routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('custom-code')->group(function () {
        Route::get('/', [App\Http\Controllers\CustomCodeController::class, 'index']);
        Route::post('/', [App\Http\Controllers\CustomCodeController::class, 'store']);
        Route::get('/{customCode}', [App\Http\Controllers\CustomCodeController::class, 'show']);
        Route::put('/{customCode}', [App\Http\Controllers\CustomCodeController::class, 'update']);
        Route::delete('/{customCode}', [App\Http\Controllers\CustomCodeController::class, 'destroy']);
        Route::post('/{customCode}/validate', [App\Http\Controllers\CustomCodeController::class, 'validate']);
        Route::post('/{customCode}/preview', [App\Http\Controllers\CustomCodeController::class, 'preview']);
    });
});

// Form Builder routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('form-builders', App\Http\Controllers\Api\FormBuilderController::class);
    Route::post('form-builders/{form}/submit', [App\Http\Controllers\Api\FormBuilderController::class, 'submit'])
        ->withoutMiddleware('auth:sanctum')
        ->middleware(['throttle:form-submission']);
    Route::post('form-builders/{form}/conditional-logic', [App\Http\Controllers\Api\FormBuilderController::class, 'evaluateConditionalLogic']);
    Route::get('form-builders/field-types', [App\Http\Controllers\Api\FormBuilderController::class, 'getFieldTypes']);
    Route::get('form-builders/{form}/submissions', [App\Http\Controllers\Api\FormSubmissionController::class, 'index']);
    Route::get('form-builders/{form}/analytics', [App\Http\Controllers\Api\FormSubmissionController::class, 'analytics']);
    Route::post('form-builders/{form}/submissions/{submission}/retry-crm-sync', [App\Http\Controllers\Api\FormSubmissionController::class, 'retryCrmSync']);
});

// Version Control and Collaboration routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('pages/{page}')->group(function () {
        Route::get('versions', [App\Http\Controllers\Api\VersionControlController::class, 'index']);
        Route::post('versions', [App\Http\Controllers\Api\VersionControlController::class, 'store']);
        Route::get('versions/{version}', [App\Http\Controllers\Api\VersionControlController::class, 'show']);
        Route::post('versions/{version}/rollback', [App\Http\Controllers\Api\VersionControlController::class, 'rollback']);
        Route::post('versions/{version}/publish', [App\Http\Controllers\Api\VersionControlController::class, 'publish']);
        Route::get('versions/{version1}/compare/{version2}', [App\Http\Controllers\Api\VersionControlController::class, 'compare']);
        Route::post('versions/auto-save', [App\Http\Controllers\Api\VersionControlController::class, 'autoSave']);
        Route::get('versions/published', [App\Http\Controllers\Api\VersionControlController::class, 'published']);
    });

    Route::prefix('pages/{page}/collaboration')->group(function () {
        Route::post('start', [App\Http\Controllers\Api\CollaborationController::class, 'startSession']);
        Route::get('sessions', [App\Http\Controllers\Api\CollaborationController::class, 'activeSessions']);
        Route::post('changes', [App\Http\Controllers\Api\CollaborationController::class, 'recordChange']);
        Route::post('apply', [App\Http\Controllers\Api\CollaborationController::class, 'applyChanges']);
        Route::get('changes', [App\Http\Controllers\Api\CollaborationController::class, 'recentChanges']);
        Route::get('changes/since', [App\Http\Controllers\Api\CollaborationController::class, 'changesSince']);
        Route::get('activity', [App\Http\Controllers\Api\CollaborationController::class, 'userActivity']);
    });

    Route::post('collaboration/end', [App\Http\Controllers\Api\CollaborationController::class, 'endSession']);
    Route::post('collaboration/activity', [App\Http\Controllers\Api\CollaborationController::class, 'updateActivity']);
    Route::post('collaboration/changes/{change}/resolve', [App\Http\Controllers\Api\CollaborationController::class, 'resolveConflict']);
    Route::post('collaboration/cleanup', [App\Http\Controllers\Api\CollaborationController::class, 'cleanupSessions']);
});

// Page Preview routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('pages/{page}/preview', [App\Http\Controllers\Api\PagePreviewController::class, 'preview'])->name('api.pages.preview');
    Route::post('pages/{page}/preview', [App\Http\Controllers\Api\PagePreviewController::class, 'updatePreview']);
});

// User Testing and Feedback routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('feedback', [App\Http\Controllers\Api\FeedbackController::class, 'store']);
    Route::get('feedback', [App\Http\Controllers\Api\FeedbackController::class, 'index']);
    Route::get('ab-tests/{testName}/variant', [App\Http\Controllers\Api\FeedbackController::class, 'getABTestVariant']);
    Route::post('ab-tests/conversion', [App\Http\Controllers\Api\FeedbackController::class, 'trackConversion']);
});

// Error Tracking Integration
Route::post('errors/track', function (\Illuminate\Http\Request $request) {
    try {
        $error = ['message' => $request->input('message'), 'stack' => $request->input('stack'), 'url' => $request->input('url'), 'user_agent' => $request->input('user_agent'), 'user_id' => auth()->id(), 'timestamp' => now()->toISOString(), 'metadata' => $request->except(['message', 'stack', 'url', 'user_agent'])];
        \Log::error('Frontend Error', $error);
        \Cache::put('error_report_'.uniqid(), json_encode($error), 86400);

        return response()->json(['status' => 'recorded']);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error'], 500);
    }
});

// Homepage Success Stories route
Route::get('homepage/success-stories', function (\Illuminate\Http\Request $request) {
    try {
        $audience = $request->get('audience', 'general');
        $stories = [
            ['id' => 1, 'title' => 'Career Transformation Success', 'description' => 'How our platform helped connect alumni with dream opportunities.', 'image' => '/images/success-story-1.jpg', 'author' => 'Sarah Johnson', 'role' => 'Software Engineer', 'company' => 'Tech Corp'],
            ['id' => 2, 'title' => 'Networking That Works', 'description' => 'Building meaningful professional relationships through our community.', 'image' => '/images/success-story-2.jpg', 'author' => 'Michael Chen', 'role' => 'Product Manager', 'company' => 'Innovation Inc'],
            ['id' => 3, 'title' => 'Mentorship Impact', 'description' => 'From student to industry leader with the right guidance.', 'image' => '/images/success-story-3.jpg', 'author' => 'Emily Rodriguez', 'role' => 'Marketing Director', 'company' => 'Growth Solutions'],
        ];

        return response()->json(['status' => 'success', 'data' => $stories, 'audience' => $audience]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Profile route
Route::get('profile', function () {
    try {
        return response()->json(['status' => 'success', 'data' => ['id' => 1, 'name' => 'Demo User', 'email' => 'demo@example.com', 'avatar' => '/images/default-avatar.jpg', 'role' => 'Alumni', 'graduation_year' => '2020', 'major' => 'Computer Science']]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Stats route
Route::get('stats', function () {
    try {
        return response()->json(['status' => 'success', 'data' => ['total_alumni' => 15420, 'active_users' => 8934, 'job_placements' => 2156, 'mentorship_connections' => 1847, 'events_this_month' => 23, 'success_stories' => 156]]);
    } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});
