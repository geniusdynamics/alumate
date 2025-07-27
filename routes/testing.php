<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestingController;

/*
|--------------------------------------------------------------------------
| Testing Routes
|--------------------------------------------------------------------------
|
| These routes are used for User Acceptance Testing functionality.
| They provide interfaces for collecting feedback, bug reports, and
| usability testing data.
|
*/

Route::prefix('testing')->group(function () {
    
    // Feedback form routes
    Route::get('/feedback', [TestingController::class, 'showFeedbackForm'])->name('testing.feedback.form');
    Route::post('/feedback', [TestingController::class, 'submitFeedback'])->name('testing.feedback.submit');
    
    // Feedback management routes (for admins)
    Route::get('/feedback/summary', [TestingController::class, 'getFeedbackSummary'])->name('testing.feedback.summary');
    Route::get('/feedback/report', [TestingController::class, 'generateFeedbackReport'])->name('testing.feedback.report');
    Route::get('/feedback/export', [TestingController::class, 'exportFeedbackCsv'])->name('testing.feedback.export');
    
});