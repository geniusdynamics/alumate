<?php

use Illuminate\Support\Facades\Route;

// Subscription and Billing routes
Route::middleware('auth:sanctum')->prefix('subscriptions')->group(function () {
    Route::get('/plans', [App\Http\Controllers\SubscriptionController::class, 'plans']);
    Route::get('/current', [App\Http\Controllers\SubscriptionController::class, 'show']);
    Route::post('/', [App\Http\Controllers\SubscriptionController::class, 'store']);
    Route::post('/change-plan', [App\Http\Controllers\SubscriptionController::class, 'changePlan']);
    Route::post('/cancel', [App\Http\Controllers\SubscriptionController::class, 'cancel']);
    Route::post('/resume', [App\Http\Controllers\SubscriptionController::class, 'resume']);
    Route::post('/payment-method', [App\Http\Controllers\SubscriptionController::class, 'updatePaymentMethod']);
    Route::get('/preview-change', [App\Http\Controllers\SubscriptionController::class, 'previewChange']);
    Route::get('/invoices', [App\Http\Controllers\SubscriptionController::class, 'invoices']);
    Route::get('/invoices/{invoice}/download', [App\Http\Controllers\SubscriptionController::class, 'downloadInvoice']);
    Route::get('/stripe-key', [App\Http\Controllers\SubscriptionController::class, 'getStripeKey']);
});

// Stripe webhook route (public)
Route::post('/webhooks/stripe', [App\Http\Controllers\WebhookController::class, 'handleStripe']);
