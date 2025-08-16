<?php

namespace App\Services;

use App\Models\CampaignDonation;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    public function processPayment(CampaignDonation $donation, array $paymentData): array
    {
        try {
            switch ($donation->payment_method) {
                case 'stripe':
                    return $this->processStripePayment($donation, $paymentData);
                case 'paypal':
                    return $this->processPayPalPayment($donation, $paymentData);
                case 'bank_transfer':
                    return $this->processBankTransfer($donation, $paymentData);
                default:
                    throw new Exception('Unsupported payment method: '.$donation->payment_method);
            }
        } catch (Exception $e) {
            Log::error('Payment processing failed', [
                'donation_id' => $donation->id,
                'payment_method' => $donation->payment_method,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function processRecurringPayment(CampaignDonation $donation): array
    {
        if (! $donation->is_recurring) {
            throw new Exception('Donation is not set up for recurring payments');
        }

        try {
            switch ($donation->payment_method) {
                case 'stripe':
                    return $this->processStripeRecurring($donation);
                case 'paypal':
                    return $this->processPayPalRecurring($donation);
                default:
                    throw new Exception('Recurring payments not supported for: '.$donation->payment_method);
            }
        } catch (Exception $e) {
            Log::error('Recurring payment processing failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function cancelRecurringPayment(CampaignDonation $donation): bool
    {
        try {
            switch ($donation->payment_method) {
                case 'stripe':
                    return $this->cancelStripeRecurring($donation);
                case 'paypal':
                    return $this->cancelPayPalRecurring($donation);
                default:
                    throw new Exception('Recurring cancellation not supported for: '.$donation->payment_method);
            }
        } catch (Exception $e) {
            Log::error('Recurring payment cancellation failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function refundPayment(CampaignDonation $donation, ?float $amount = null): array
    {
        $refundAmount = $amount ?? $donation->amount;

        try {
            switch ($donation->payment_method) {
                case 'stripe':
                    return $this->refundStripePayment($donation, $refundAmount);
                case 'paypal':
                    return $this->refundPayPalPayment($donation, $refundAmount);
                default:
                    throw new Exception('Refunds not supported for: '.$donation->payment_method);
            }
        } catch (Exception $e) {
            Log::error('Payment refund failed', [
                'donation_id' => $donation->id,
                'refund_amount' => $refundAmount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function processStripePayment(CampaignDonation $donation, array $paymentData): array
    {
        // Initialize Stripe
        \Stripe\Stripe::setApiKey(Config::get('services.stripe.secret'));

        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $donation->amount * 100, // Convert to cents
                'currency' => strtolower($donation->currency),
                'payment_method' => $paymentData['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => route('donations.success', $donation),
                'metadata' => [
                    'donation_id' => $donation->id,
                    'campaign_id' => $donation->campaign_id,
                    'donor_id' => $donation->donor_id,
                ],
            ]);

            // Set up recurring payment if needed
            if ($donation->is_recurring) {
                $this->setupStripeRecurring($donation, $paymentData['payment_method_id']);
            }

            return [
                'success' => true,
                'payment_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'client_secret' => $paymentIntent->client_secret,
                'payment_data' => [
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_payment_method_id' => $paymentData['payment_method_id'],
                ],
            ];
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => $e->getError()->message,
                'error_code' => $e->getError()->code,
            ];
        }
    }

    private function processPayPalPayment(CampaignDonation $donation, array $paymentData): array
    {
        // PayPal SDK integration would go here
        // For now, simulate successful payment

        $paymentId = 'pp_'.uniqid();

        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => 'completed',
            'payment_data' => [
                'paypal_payment_id' => $paymentId,
                'paypal_payer_id' => $paymentData['payer_id'] ?? null,
            ],
        ];
    }

    private function processBankTransfer(CampaignDonation $donation, array $paymentData): array
    {
        // Bank transfer would typically be manual verification
        // Mark as pending for manual processing

        return [
            'success' => true,
            'payment_id' => 'bt_'.uniqid(),
            'status' => 'pending_verification',
            'payment_data' => [
                'bank_reference' => $paymentData['reference'] ?? null,
                'bank_account' => $paymentData['account_number'] ?? null,
            ],
        ];
    }

    private function setupStripeRecurring(CampaignDonation $donation, string $paymentMethodId): void
    {
        $interval = match ($donation->recurring_frequency) {
            'monthly' => 'month',
            'quarterly' => 'month',
            'yearly' => 'year',
            default => 'month',
        };

        $intervalCount = $donation->recurring_frequency === 'quarterly' ? 3 : 1;

        // Create customer
        $customer = \Stripe\Customer::create([
            'payment_method' => $paymentMethodId,
            'email' => $donation->donor_email ?? $donation->donor?->email,
            'name' => $donation->donor_name ?? $donation->donor?->name,
            'metadata' => [
                'donation_id' => $donation->id,
                'donor_id' => $donation->donor_id,
            ],
        ]);

        // Create subscription
        $subscription = \Stripe\Subscription::create([
            'customer' => $customer->id,
            'items' => [[
                'price_data' => [
                    'currency' => strtolower($donation->currency),
                    'product_data' => [
                        'name' => 'Recurring Donation - '.$donation->campaign->title,
                    ],
                    'unit_amount' => $donation->amount * 100,
                    'recurring' => [
                        'interval' => $interval,
                        'interval_count' => $intervalCount,
                    ],
                ],
            ]],
            'metadata' => [
                'donation_id' => $donation->id,
                'campaign_id' => $donation->campaign_id,
            ],
        ]);

        // Update donation with subscription info
        $donation->update([
            'payment_data' => array_merge($donation->payment_data ?? [], [
                'stripe_customer_id' => $customer->id,
                'stripe_subscription_id' => $subscription->id,
            ]),
        ]);
    }

    private function processStripeRecurring(CampaignDonation $donation): array
    {
        $subscriptionId = $donation->payment_data['stripe_subscription_id'] ?? null;

        if (! $subscriptionId) {
            throw new Exception('No subscription ID found for recurring donation');
        }

        try {
            $subscription = \Stripe\Subscription::retrieve($subscriptionId);

            return [
                'success' => true,
                'subscription_status' => $subscription->status,
                'next_payment_date' => $subscription->current_period_end,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception('Failed to process recurring payment: '.$e->getMessage());
        }
    }

    private function processPayPalRecurring(CampaignDonation $donation): array
    {
        // PayPal recurring payment processing would go here
        return [
            'success' => true,
            'subscription_status' => 'active',
        ];
    }

    private function cancelStripeRecurring(CampaignDonation $donation): bool
    {
        $subscriptionId = $donation->payment_data['stripe_subscription_id'] ?? null;

        if (! $subscriptionId) {
            return false;
        }

        try {
            \Stripe\Subscription::update($subscriptionId, [
                'cancel_at_period_end' => true,
            ]);

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Failed to cancel Stripe subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function cancelPayPalRecurring(CampaignDonation $donation): bool
    {
        // PayPal recurring cancellation would go here
        return true;
    }

    private function refundStripePayment(CampaignDonation $donation, float $amount): array
    {
        $paymentIntentId = $donation->payment_data['stripe_payment_intent_id'] ?? null;

        if (! $paymentIntentId) {
            throw new Exception('No Stripe payment intent ID found');
        }

        try {
            $refund = \Stripe\Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => $amount * 100, // Convert to cents
                'metadata' => [
                    'donation_id' => $donation->id,
                    'refund_reason' => 'Donor request',
                ],
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $amount,
                'status' => $refund->status,
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception('Stripe refund failed: '.$e->getMessage());
        }
    }

    private function refundPayPalPayment(CampaignDonation $donation, float $amount): array
    {
        // PayPal refund processing would go here
        return [
            'success' => true,
            'refund_id' => 'pp_refund_'.uniqid(),
            'amount' => $amount,
            'status' => 'completed',
        ];
    }
}
