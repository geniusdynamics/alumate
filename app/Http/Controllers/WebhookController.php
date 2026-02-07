<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebhookController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle Stripe webhooks.
     */
    public function handleStripe(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::error('Stripe webhook secret not configured');

            return response('Webhook secret not configured', 500);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret,
                config('services.stripe.webhook_tolerance', 300)
            );
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response('Invalid signature', 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook error', [
                'error' => $e->getMessage(),
            ]);

            return response('Webhook error', 400);
        }

        // Process the webhook
        try {
            $this->subscriptionService->handleWebhook($event->type, $event->data->toArray());

            return response('Webhook processed', 200);
        } catch (\Exception $e) {
            Log::error('Failed to process Stripe webhook', [
                'event_type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            return response('Webhook processing failed', 500);
        }
    }

    /**
     * Handle Paddle webhooks (for future use).
     */
    public function handlePaddle(Request $request): Response
    {
        // TODO: Implement Paddle webhook handling
        return response('Paddle webhooks not implemented', 501);
    }

    /**
     * Handle PayPal webhooks (for future use).
     */
    public function handlePayPal(Request $request): Response
    {
        // TODO: Implement PayPal webhook handling
        return response('PayPal webhooks not implemented', 501);
    }
}
