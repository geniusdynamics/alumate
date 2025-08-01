<?php

namespace App\Jobs;

use App\Models\TaxReceipt;
use App\Services\TaxReceiptPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateTaxReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private TaxReceipt $taxReceipt
    ) {}

    public function handle(TaxReceiptPdfService $pdfService): void
    {
        try {
            Log::info('Generating tax receipt PDF', [
                'receipt_id' => $this->taxReceipt->id,
                'receipt_number' => $this->taxReceipt->receipt_number,
            ]);

            // Generate PDF
            $pdfContent = $pdfService->generatePdf($this->taxReceipt);

            // Store PDF file
            $filename = "tax-receipts/{$this->taxReceipt->tax_year}/{$this->taxReceipt->receipt_number}.pdf";
            Storage::disk('private')->put($filename, $pdfContent);

            // Update receipt record
            $this->taxReceipt->update([
                'pdf_path' => $filename,
                'status' => 'generated',
            ]);

            Log::info('Tax receipt PDF generated successfully', [
                'receipt_id' => $this->taxReceipt->id,
                'pdf_path' => $filename,
            ]);

            // Optionally send the receipt via email
            if ($this->taxReceipt->donor_email) {
                $this->sendReceiptByEmail();
            }

        } catch (\Exception $e) {
            Log::error('Failed to generate tax receipt PDF', [
                'receipt_id' => $this->taxReceipt->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function sendReceiptByEmail(): void
    {
        try {
            // Queue email sending job
            SendTaxReceiptEmailJob::dispatch($this->taxReceipt);

            Log::info('Tax receipt email queued', [
                'receipt_id' => $this->taxReceipt->id,
                'recipient' => $this->taxReceipt->donor_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue tax receipt email', [
                'receipt_id' => $this->taxReceipt->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}