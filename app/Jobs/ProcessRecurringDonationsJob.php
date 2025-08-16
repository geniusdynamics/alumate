<?php

namespace App\Jobs;

use App\Models\RecurringDonation;
use App\Services\DonationProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRecurringDonationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(DonationProcessingService $donationService): void
    {
        Log::info('Processing recurring donations');

        $recurringDonations = RecurringDonation::dueForPayment()
            ->with(['campaign', 'donor'])
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($recurringDonations as $recurringDonation) {
            try {
                $donation = $donationService->processRecurringPayment($recurringDonation);

                if ($donation) {
                    $processed++;
                    Log::info('Recurring donation processed successfully', [
                        'recurring_donation_id' => $recurringDonation->id,
                        'donation_id' => $donation->id,
                        'amount' => $donation->amount,
                    ]);
                } else {
                    $failed++;
                    Log::warning('Recurring donation processing failed', [
                        'recurring_donation_id' => $recurringDonation->id,
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Recurring donation processing error', [
                    'recurring_donation_id' => $recurringDonation->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Recurring donations processing completed', [
            'total_due' => $recurringDonations->count(),
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
