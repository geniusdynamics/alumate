<?php

use Illuminate\Support\Facades\Route;

// Fundraising Campaign routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('fundraising-campaigns', App\Http\Controllers\Api\FundraisingCampaignController::class);
    Route::get('fundraising-campaigns/{campaign}/analytics', [App\Http\Controllers\Api\FundraisingCampaignController::class, 'analytics']);
    Route::get('fundraising-campaigns/{campaign}/share', [App\Http\Controllers\Api\FundraisingCampaignController::class, 'share']);

    // Comprehensive Fundraising Analytics
    Route::prefix('fundraising-analytics')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'dashboard']);
        Route::get('giving-patterns', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'givingPatterns']);
        Route::get('campaigns/{campaign}/performance', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'campaignPerformance']);
        Route::get('donor-analytics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'donorAnalytics']);
        Route::get('predictive-analytics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'predictiveAnalytics']);
        Route::get('roi-metrics', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'roiMetrics']);
        Route::get('donor-engagement', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'donorEngagement']);
        Route::get('trends', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'trends']);
        Route::post('export', [App\Http\Controllers\Api\FundraisingAnalyticsController::class, 'export']);
    });
});

// Campaign Donation routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('campaign-donations', App\Http\Controllers\Api\CampaignDonationController::class);
    Route::get('campaigns/{campaign}/donations', [App\Http\Controllers\Api\CampaignDonationController::class, 'campaignDonations']);
    Route::get('user/donations', [App\Http\Controllers\Api\CampaignDonationController::class, 'userDonations']);
    Route::post('campaign-donations/{donation}/refund', [App\Http\Controllers\Api\CampaignDonationController::class, 'refund']);
});

// Recurring Donation routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('recurring-donations', App\Http\Controllers\Api\RecurringDonationController::class)->only(['index', 'show', 'update']);
    Route::post('recurring-donations/{recurringDonation}/cancel', [App\Http\Controllers\Api\RecurringDonationController::class, 'cancel']);
    Route::post('recurring-donations/{recurringDonation}/pause', [App\Http\Controllers\Api\RecurringDonationController::class, 'pause']);
    Route::post('recurring-donations/{recurringDonation}/resume', [App\Http\Controllers\Api\RecurringDonationController::class, 'resume']);
    Route::get('user/recurring-donations', [App\Http\Controllers\Api\RecurringDonationController::class, 'userRecurringDonations']);
    Route::get('admin/recurring-donations/due', [App\Http\Controllers\Api\RecurringDonationController::class, 'dueForPayment']);
});

// Tax Receipt routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('tax-receipts', App\Http\Controllers\Api\TaxReceiptController::class)->only(['index', 'show']);
    Route::post('tax-receipts/generate', [App\Http\Controllers\Api\TaxReceiptController::class, 'generate']);
    Route::get('tax-receipts/{taxReceipt}/download', [App\Http\Controllers\Api\TaxReceiptController::class, 'download']);
    Route::post('tax-receipts/{taxReceipt}/resend', [App\Http\Controllers\Api\TaxReceiptController::class, 'resend']);
    Route::get('user/tax-receipts', [App\Http\Controllers\Api\TaxReceiptController::class, 'userTaxReceipts']);
    Route::post('admin/tax-receipts/generate-year', [App\Http\Controllers\Api\TaxReceiptController::class, 'generateForYear']);
});

// Peer Fundraiser routes
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('peer-fundraisers', App\Http\Controllers\Api\PeerFundraiserController::class);
    Route::get('campaigns/{campaign}/peer-fundraisers', [App\Http\Controllers\Api\PeerFundraiserController::class, 'campaignPeerFundraisers']);
    Route::get('user/peer-fundraisers', [App\Http\Controllers\Api\PeerFundraiserController::class, 'userPeerFundraisers']);
    Route::get('peer-fundraisers/{peerFundraiser}/share', [App\Http\Controllers\Api\PeerFundraiserController::class, 'share']);
});
