<?php

namespace App\Jobs;

use App\Models\TaxReceipt;
use App\Mail\TaxReceiptMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTaxReceiptEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private TaxReceipt $taxReceipt
    ) {}

    public function handle(): void
    {
        try {
            if (empty($this->taxReceipt->donor_email)) {
                throw new \Exception('No donor email address available');
            }

            Mail::to($this->taxReceipt->donor_email)
                ->send(new TaxReceiptMail($this->taxReceipt));

            $this->taxReceipt->markAsSent();

            Log::info('Tax receipt email sent successfully', [
                'receipt_id' => $this->taxReceipt->id,
                'recipient' => $this->taxReceipt->donor_email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send tax receipt email', [
                'receipt_id' => $this->taxReceipt->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}