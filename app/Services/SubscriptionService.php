<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class SubscriptionService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a new subscription for a tenant.
     */
    public function createSubscription(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $paymentMethodId,
        string $interval = 'monthly'
    ): Subscription {
        return DB::transaction(function () use ($tenant, $plan, $paymentMethodId, $interval) {
            // Create or retrieve Stripe customer
            $customer = $this->getOrCreateCustomer($tenant);

            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $customer->id,
            ]);

            // Set as default payment method
            $this->stripe->customers->update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // Get the Stripe price ID
            $stripePriceId = $interval === 'yearly' && $plan->stripe_product_id
                ? $this->getYearlyPriceId($plan)
                : $plan->stripe_price_id;

            if (! $stripePriceId) {
                throw new Exception('No Stripe price ID configured for this plan');
            }

            // Create Stripe subscription
            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $customer->id,
                'items' => [['price' => $stripePriceId]],
                'trial_period_days' => $plan->trial_days,
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // Get payment method details
            $paymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);

            // Create local subscription record
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_customer_id' => $customer->id,
                'stripe_price_id' => $stripePriceId,
                'status' => $this->mapStripeStatus($stripeSubscription->status),
                'current_period_starts_at' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_ends_at' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'trial_starts_at' => $stripeSubscription->trial_start ? Carbon::createFromTimestamp($stripeSubscription->trial_start) : null,
                'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'payment_method_id' => $paymentMethodId,
                'payment_method_brand' => $paymentMethod->card->brand ?? null,
                'payment_method_last_four' => $paymentMethod->card->last4 ?? null,
            ]);

            // Update tenant
            $tenant->update([
                'subscription_plan' => $plan->slug,
                'subscription_status' => $subscription->status,
            ]);

            return $subscription;
        });
    }

    /**
     * Upgrade or downgrade a subscription.
     */
    public function changePlan(Subscription $subscription, SubscriptionPlan $newPlan, string $interval = 'monthly'): Subscription
    {
        return DB::transaction(function () use ($subscription, $newPlan, $interval) {
            $stripePriceId = $interval === 'yearly' && $newPlan->stripe_product_id
                ? $this->getYearlyPriceId($newPlan)
                : $newPlan->stripe_price_id;

            if (! $stripePriceId) {
                throw new Exception('No Stripe price ID configured for this plan');
            }

            // Retrieve current subscription item
            $stripeSubscription = $this->stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            $subscriptionItemId = $stripeSubscription->items->data[0]->id;

            // Update Stripe subscription
            $updatedSubscription = $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
                'items' => [
                    [
                        'id' => $subscriptionItemId,
                        'price' => $stripePriceId,
                    ],
                ],
                'proration_behavior' => config('services.stripe.prorate', true) ? 'create_prorations' : 'none',
            ]);

            // Update local subscription
            $subscription->update([
                'plan_id' => $newPlan->id,
                'stripe_price_id' => $stripePriceId,
                'status' => $this->mapStripeStatus($updatedSubscription->status),
                'current_period_starts_at' => Carbon::createFromTimestamp($updatedSubscription->current_period_start),
                'current_period_ends_at' => Carbon::createFromTimestamp($updatedSubscription->current_period_end),
            ]);

            // Update tenant
            $subscription->tenant->update([
                'subscription_plan' => $newPlan->slug,
            ]);

            // Reset usage for new plan
            $subscription->resetUsage();

            return $subscription->fresh();
        });
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Subscription $subscription, bool $atPeriodEnd = true): Subscription
    {
        if ($atPeriodEnd) {
            // Cancel at period end
            $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            $subscription->update([
                'cancel_at_period_end' => true,
            ]);
        } else {
            // Cancel immediately
            $this->stripe->subscriptions->cancel($subscription->stripe_subscription_id);

            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => Carbon::now(),
            ]);

            // Update tenant
            $subscription->tenant->update([
                'subscription_status' => 'cancelled',
            ]);
        }

        return $subscription->fresh();
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        $stripeSubscription = $this->stripe->subscriptions->update($subscription->stripe_subscription_id, [
            'cancel_at_period_end' => false,
        ]);

        $subscription->update([
            'cancel_at_period_end' => false,
            'cancelled_at' => null,
            'status' => $this->mapStripeStatus($stripeSubscription->status),
        ]);

        return $subscription->fresh();
    }

    /**
     * Update payment method.
     */
    public function updatePaymentMethod(Subscription $subscription, string $paymentMethodId): Subscription
    {
        // Attach new payment method to customer
        $this->stripe->paymentMethods->attach($paymentMethodId, [
            'customer' => $subscription->stripe_customer_id,
        ]);

        // Set as default
        $this->stripe->customers->update($subscription->stripe_customer_id, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethodId,
            ],
        ]);

        // Get payment method details
        $paymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);

        $subscription->update([
            'payment_method_id' => $paymentMethodId,
            'payment_method_brand' => $paymentMethod->card->brand ?? null,
            'payment_method_last_four' => $paymentMethod->card->last4 ?? null,
        ]);

        return $subscription->fresh();
    }

    /**
     * Handle Stripe webhook for subscription updates.
     */
    public function handleWebhook(string $eventType, array $data): void
    {
        Log::info("Processing Stripe webhook: {$eventType}");

        switch ($eventType) {
            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($data['object']);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($data['object']);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($data['object']);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($data['object']);
                break;

            case 'customer.subscription.trial_will_end':
                $this->handleTrialEnding($data['object']);
                break;
        }
    }

    /**
     * Get or create Stripe customer for tenant.
     */
    private function getOrCreateCustomer(Tenant $tenant): object
    {
        $existingSubscription = Subscription::where('tenant_id', $tenant->id)
            ->whereNotNull('stripe_customer_id')
            ->first();

        if ($existingSubscription) {
            return $this->stripe->customers->retrieve($existingSubscription->stripe_customer_id);
        }

        return $this->stripe->customers->create([
            'name' => $tenant->name,
            'email' => $tenant->getSetting('billing_email') ?? $tenant->getSetting('admin_email'),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
            ],
        ]);
    }

    /**
     * Map Stripe status to local status.
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'active' => Subscription::STATUS_ACTIVE,
            'canceled' => Subscription::STATUS_CANCELLED,
            'incomplete' => Subscription::STATUS_PAST_DUE,
            'incomplete_expired' => Subscription::STATUS_PAST_DUE,
            'past_due' => Subscription::STATUS_PAST_DUE,
            'paused' => Subscription::STATUS_PAUSED,
            'trialing' => Subscription::STATUS_TRIALING,
            'unpaid' => Subscription::STATUS_UNPAID,
            default => Subscription::STATUS_PAST_DUE,
        };
    }

    /**
     * Handle payment succeeded webhook.
     */
    private function handlePaymentSucceeded(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'latest_invoice_id' => $invoice['id'],
            'latest_invoice_pdf' => $invoice['invoice_pdf'] ?? null,
            'latest_invoice_amount' => $invoice['amount_due'] / 100,
            'latest_invoice_paid_at' => Carbon::createFromTimestamp($invoice['status_transitions']['paid_at']),
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        // Reset usage for new period
        $subscription->resetUsage();
    }

    /**
     * Handle payment failed webhook.
     */
    private function handlePaymentFailed(array $invoice): void
    {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (! $subscriptionId) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $subscriptionId)->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => Subscription::STATUS_PAST_DUE,
            'latest_invoice_id' => $invoice['id'],
        ]);

        // TODO: Send payment failed notification
    }

    /**
     * Handle subscription updated webhook.
     */
    private function handleSubscriptionUpdated(array $stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => $this->mapStripeStatus($stripeSubscription['status']),
            'current_period_starts_at' => Carbon::createFromTimestamp($stripeSubscription['current_period_start']),
            'current_period_ends_at' => Carbon::createFromTimestamp($stripeSubscription['current_period_end']),
            'cancel_at_period_end' => $stripeSubscription['cancel_at_period_end'],
            'trial_ends_at' => $stripeSubscription['trial_end'] ? Carbon::createFromTimestamp($stripeSubscription['trial_end']) : null,
        ]);

        // Update tenant status
        $subscription->tenant->update([
            'subscription_status' => $subscription->status,
        ]);
    }

    /**
     * Handle subscription deleted webhook.
     */
    private function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => Carbon::now(),
        ]);

        $subscription->tenant->update([
            'subscription_status' => 'cancelled',
        ]);
    }

    /**
     * Handle trial ending webhook.
     */
    private function handleTrialEnding(array $stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();
        if (! $subscription) {
            return;
        }

        // TODO: Send trial ending notification
    }

    /**
     * Get yearly price ID for a plan.
     */
    private function getYearlyPriceId(SubscriptionPlan $plan): ?string
    {
        if ($plan->stripe_price_id && $plan->interval === 'yearly') {
            return $plan->stripe_price_id;
        }

        // Create yearly price if it doesn't exist
        if ($plan->stripe_product_id && $plan->price_yearly) {
            $price = $this->stripe->prices->create([
                'product' => $plan->stripe_product_id,
                'unit_amount' => (int) ($plan->price_yearly * 100),
                'currency' => $plan->currency,
                'recurring' => ['interval' => 'year'],
            ]);

            $plan->update(['stripe_price_id' => $price->id]);

            return $price->id;
        }

        return null;
    }

    /**
     * Calculate prorated amount for plan change.
     */
    public function calculateProration(Subscription $subscription, SubscriptionPlan $newPlan): array
    {
        try {
            $stripePriceId = $newPlan->stripe_price_id;
            if (! $stripePriceId) {
                throw new Exception('No Stripe price ID for new plan');
            }

            $subscriptionItemId = $this->stripe->subscriptions->retrieve($subscription->stripe_subscription_id)
                ->items->data[0]->id;

            $invoice = $this->stripe->invoices->createPreview($subscription->stripe_customer_id, [
                'subscription_details' => [
                    'items' => [
                        [
                            'id' => $subscriptionItemId,
                            'price' => $stripePriceId,
                        ],
                    ],
                ],
            ]);

            return [
                'proration_date' => Carbon::now()->toDateTimeString(),
                'amount_due' => $invoice->amount_due / 100,
                'subtotal' => $invoice->subtotal / 100,
                'tax' => $invoice->tax / 100,
                'currency' => $invoice->currency,
            ];
        } catch (Exception $e) {
            Log::error('Failed to calculate proration', [
                'subscription_id' => $subscription->id,
                'new_plan_id' => $newPlan->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => 'Could not calculate proration',
                'proration_date' => Carbon::now()->toDateTimeString(),
            ];
        }
    }

    /**
     * Get upcoming invoice for subscription.
     */
    public function getUpcomingInvoice(Subscription $subscription): ?array
    {
        try {
            $invoice = $this->stripe->invoices->upcoming([
                'customer' => $subscription->stripe_customer_id,
                'subscription' => $subscription->stripe_subscription_id,
            ]);

            return [
                'amount_due' => $invoice->amount_due / 100,
                'subtotal' => $invoice->subtotal / 100,
                'tax' => $invoice->tax / 100,
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date ? Carbon::createFromTimestamp($invoice->due_date)->toDateTimeString() : null,
                'period_start' => Carbon::createFromTimestamp($invoice->period_start)->toDateTimeString(),
                'period_end' => Carbon::createFromTimestamp($invoice->period_end)->toDateTimeString(),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get upcoming invoice', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
