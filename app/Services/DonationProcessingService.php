<?php

namespace App\Services;

use App\Models\CampaignDonation;
use App\Models\RecurringDonation;
use App\Models\PaymentTransaction;
use App\Models\DonationAcknowledgment;
use App\Models\TaxReceipt;
use App\Jobs\ProcessRecurringDonationsJob;
use App\Jobs\SendDonationAcknowledgmentJob;
use App\Jobs\GenerateTaxReceiptJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DonationProcessingService
{
    public function __construct(
        private PaymentGatewayService $paymentGateway,
        private FundraisingService $fundraisingService
    ) {}

    public function processDonation(array $donationData, array $paymentData = []): CampaignDonation
    {
        return DB::transaction(function () use ($donationData, $paymentData) {
            // Create the donation record
            $donation = $this->fundraisingService->processDonation($donationData);

            try {
                // Process payment through gateway
                $paymentResult = $this->paymentGateway->processPayment($donation, $paymentData);

                if ($paymentResult['success']) {
                    // Create payment transaction record
                    $this->createPaymentTransaction($donation, $paymentResult);

                    // Update donation status
                    $donation->update([
                        'status' => $paymentResult['status'] === 'completed' ? 'completed' : 'pending',
                        'payment_id' => $paymentResult['payment_id'],
                        'payment_data' => array_merge(
                            $donation->payment_data ?? [],
                            $paymentResult['payment_data'] ?? []
                        ),
                        'processed_at' => $paymentResult['status'] === 'completed' ? now() : null,
                    ]);

                    // Set up recurring donation if needed
                    if ($donation->is_recurring && $paymentResult['status'] === 'completed') {
                        $this->setupRecurringDonation($donation);
                    }

                    // Schedule acknowledgment
                    if ($paymentResult['status'] === 'completed') {
                        $this->scheduleAcknowledgment($donation);
                    }

                    return $donation->fresh();
                } else {
                    // Payment failed
                    $donation->update([
                        'status' => 'failed',
                        'payment_data' => [
                            'error' => $paymentResult['error'] ?? 'Payment failed',
                            'error_code' => $paymentResult['error_code'] ?? null,
                        ],
                    ]);

                    throw new Exception($paymentResult['error'] ?? 'Payment processing failed');
                }
            } catch (Exception $e) {
                // Mark donation as failed
                $donation->update([
                    'status' => 'failed',
                    'payment_data' => [
                        'error' => $e->getMessage(),
                    ],
                ]);

                Log::error('Donation processing failed', [
                    'donation_id' => $donation->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }

    public function processRecurringPayment(RecurringDonation $recurringDonation): ?CampaignDonation
    {
        try {
            // Create new donation record for this recurring payment
            $donationData = [
                'campaign_id' => $recurringDonation->campaign_id,
                'donor_id' => $recurringDonation->donor_id,
                'amount' => $recurringDonation->amount,
                'currency' => $recurringDonation->currency,
                'is_recurring' => false, // This is a recurring payment instance
                'recurring_donation_id' => $recurringDonation->id,
                'donor_name' => $recurringDonation->donor_name,
                'donor_email' => $recurringDonation->donor_email,
                'payment_method' => $recurringDonation->payment_method,
                'status' => 'pending',
            ];

            $donation = CampaignDonation::create($donationData);

            // Process payment using stored payment method
            $paymentResult = $this->paymentGateway->processRecurringPayment($recurringDonation);

            if ($paymentResult['success']) {
                $donation->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'payment_id' => 'recurring_' . uniqid(),
                ]);

                // Update recurring donation record
                $recurringDonation->recordSuccessfulPayment($donation);

                // Update campaign totals
                $this->fundraisingService->updateCampaignTotals($donation->campaign);

                // Schedule acknowledgment
                $this->scheduleAcknowledgment($donation);

                return $donation;
            } else {
                $donation->update(['status' => 'failed']);
                $recurringDonation->recordFailedPayment();

                Log::warning('Recurring payment failed', [
                    'recurring_donation_id' => $recurringDonation->id,
                    'donation_id' => $donation->id,
                ]);

                return null;
            }
        } catch (Exception $e) {
            Log::error('Recurring payment processing failed', [
                'recurring_donation_id' => $recurringDonation->id,
                'error' => $e->getMessage(),
            ]);

            $recurringDonation->recordFailedPayment();
            return null;
        }
    }

    public function refundDonation(CampaignDonation $donation, float $amount = null, string $reason = null): bool
    {
        try {
            $refundAmount = $amount ?? $donation->amount;
            
            // Process refund through payment gateway
            $refundResult = $this->paymentGateway->refundPayment($donation, $refundAmount);

            if ($refundResult['success']) {
                // Create refund transaction record
                $this->createPaymentTransaction($donation, [
                    'success' => true,
                    'payment_id' => $refundResult['refund_id'],
                    'status' => 'completed',
                    'payment_data' => $refundResult,
                ], 'refund');

                // Update donation status
                $donation->update([
                    'status' => $refundAmount >= $donation->amount ? 'refunded' : 'partially_refunded',
                    'payment_data' => array_merge(
                        $donation->payment_data ?? [],
                        [
                            'refund_amount' => $refundAmount,
                            'refund_reason' => $reason,
                            'refunded_at' => now()->toISOString(),
                        ]
                    ),
                ]);

                // Update campaign totals
                $this->fundraisingService->updateCampaignTotals($donation->campaign);

                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Donation refund failed', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function cancelRecurringDonation(RecurringDonation $recurringDonation, string $reason = null): bool
    {
        try {
            // Cancel with payment gateway
            $cancelled = $this->paymentGateway->cancelRecurringPayment($recurringDonation->originalDonation);

            if ($cancelled) {
                $recurringDonation->cancel($reason);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Recurring donation cancellation failed', [
                'recurring_donation_id' => $recurringDonation->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function generateTaxReceipt(int $donorId, int $taxYear): ?TaxReceipt
    {
        // Get all completed donations for the donor in the tax year
        $donations = CampaignDonation::where('donor_id', $donorId)
            ->completed()
            ->whereYear('processed_at', $taxYear)
            ->get();

        if ($donations->isEmpty()) {
            return null;
        }

        $donor = $donations->first()->donor;
        $totalAmount = $donations->sum('amount');

        $receiptData = [
            'receipt_number' => $this->generateReceiptNumber($taxYear),
            'donor_id' => $donorId,
            'donor_name' => $donor->name,
            'donor_email' => $donor->email,
            'donor_address' => $this->getDonorAddress($donor),
            'total_amount' => $totalAmount,
            'currency' => $donations->first()->currency,
            'tax_year' => $taxYear,
            'receipt_date' => now()->toDateString(),
            'donations' => $donations->map(function ($donation) {
                return [
                    'donation_id' => $donation->id,
                    'amount' => $donation->amount,
                    'date' => $donation->processed_at->toDateString(),
                    'campaign' => $donation->campaign->title,
                ];
            })->toArray(),
            'generated_at' => now(),
        ];

        $receipt = TaxReceipt::create($receiptData);

        // Queue PDF generation
        GenerateTaxReceiptJob::dispatch($receipt);

        return $receipt;
    }

    private function createPaymentTransaction(CampaignDonation $donation, array $paymentResult, string $type = 'payment'): PaymentTransaction
    {
        return PaymentTransaction::create([
            'donation_id' => $donation->id,
            'transaction_type' => $type,
            'gateway' => $donation->payment_method,
            'gateway_transaction_id' => $paymentResult['payment_id'],
            'amount' => $type === 'refund' ? 
                ($paymentResult['amount'] ?? $donation->amount) : 
                $donation->amount,
            'currency' => $donation->currency,
            'status' => $paymentResult['status'],
            'gateway_response' => $paymentResult,
            'processed_at' => $paymentResult['status'] === 'completed' ? now() : null,
        ]);
    }

    private function setupRecurringDonation(CampaignDonation $donation): RecurringDonation
    {
        return RecurringDonation::create([
            'original_donation_id' => $donation->id,
            'campaign_id' => $donation->campaign_id,
            'donor_id' => $donation->donor_id,
            'donor_name' => $donation->donor_name,
            'donor_email' => $donation->donor_email,
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'frequency' => $donation->recurring_frequency,
            'payment_method' => $donation->payment_method,
            'payment_data' => $donation->payment_data,
            'next_payment_date' => $this->calculateNextPaymentDate($donation),
            'started_at' => now()->toDateString(),
        ]);
    }

    private function calculateNextPaymentDate(CampaignDonation $donation): string
    {
        $now = now();
        
        return match ($donation->recurring_frequency) {
            'monthly' => $now->addMonth()->toDateString(),
            'quarterly' => $now->addMonths(3)->toDateString(),
            'yearly' => $now->addYear()->toDateString(),
            default => $now->addMonth()->toDateString(),
        };
    }

    private function scheduleAcknowledgment(CampaignDonation $donation): void
    {
        // Don't send acknowledgment for anonymous donations unless they specifically requested it
        if ($donation->is_anonymous && !($donation->payment_data['send_acknowledgment'] ?? false)) {
            return;
        }

        $recipientInfo = [
            'name' => $donation->donor_display_name,
            'email' => $donation->donor_email ?? $donation->donor?->email,
        ];

        // Skip if no email available
        if (empty($recipientInfo['email'])) {
            return;
        }

        $acknowledgment = DonationAcknowledgment::create([
            'donation_id' => $donation->id,
            'type' => 'email',
            'status' => 'pending',
            'recipient_info' => $recipientInfo,
            'template_used' => 'donation_thank_you',
            'personalization_data' => [
                'donation_amount' => $donation->amount,
                'campaign_title' => $donation->campaign->title,
                'donor_name' => $recipientInfo['name'],
                'is_recurring' => $donation->is_recurring,
            ],
            'scheduled_at' => now()->addMinutes(5), // Send after 5 minutes
        ]);

        // Queue the acknowledgment job
        SendDonationAcknowledgmentJob::dispatch($acknowledgment)
            ->delay(now()->addMinutes(5));
    }

    private function generateReceiptNumber(int $taxYear): string
    {
        $lastReceipt = TaxReceipt::where('tax_year', $taxYear)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastReceipt ? 
            (int) substr($lastReceipt->receipt_number, -6) + 1 : 
            1;

        return str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function getDonorAddress($donor): ?array
    {
        // This would typically come from a user profile or address table
        // For now, return null - implement based on your user model structure
        return null;
    }
}