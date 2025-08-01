<?php

namespace App\Mail;

use App\Models\TaxReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TaxReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TaxReceipt $taxReceipt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tax Receipt for {$this->taxReceipt->tax_year} - {$this->taxReceipt->getFormattedReceiptNumber()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tax-receipt',
            with: [
                'receipt' => $this->taxReceipt,
                'donor_name' => $this->taxReceipt->donor_name,
                'tax_year' => $this->taxReceipt->tax_year,
                'total_amount' => number_format($this->taxReceipt->total_amount, 2),
                'receipt_number' => $this->taxReceipt->getFormattedReceiptNumber(),
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->taxReceipt->pdf_path && Storage::disk('private')->exists($this->taxReceipt->pdf_path)) {
            $attachments[] = Attachment::fromStorageDisk('private', $this->taxReceipt->pdf_path)
                ->as("Tax_Receipt_{$this->taxReceipt->tax_year}_{$this->taxReceipt->receipt_number}.pdf")
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}