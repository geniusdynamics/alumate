<?php

namespace App\Services;

use App\Models\TaxReceipt;
use Barryvdh\DomPDF\Facade\Pdf;

class TaxReceiptPdfService
{
    public function generatePdf(TaxReceipt $taxReceipt): string
    {
        $data = $this->preparePdfData($taxReceipt);

        $pdf = Pdf::loadView('pdf.tax-receipt', $data);

        // Set PDF options
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Arial',
        ]);

        return $pdf->output();
    }

    private function preparePdfData(TaxReceipt $taxReceipt): array
    {
        $donations = $taxReceipt->getDonationRecords();

        return [
            'receipt' => $taxReceipt,
            'donations' => $donations,
            'organization' => $this->getOrganizationInfo(),
            'generated_date' => now()->format('F j, Y'),
            'total_amount_words' => $this->numberToWords($taxReceipt->total_amount),
        ];
    }

    private function getOrganizationInfo(): array
    {
        return [
            'name' => config('app.organization_name', 'Alumni Platform'),
            'address' => config('app.organization_address', '123 University Ave'),
            'city' => config('app.organization_city', 'City'),
            'state' => config('app.organization_state', 'State'),
            'zip' => config('app.organization_zip', '12345'),
            'phone' => config('app.organization_phone', '(555) 123-4567'),
            'email' => config('app.organization_email', 'info@alumni.org'),
            'tax_id' => config('app.organization_tax_id', '12-3456789'),
            'charity_registration' => config('app.charity_registration', 'REG123456'),
        ];
    }

    private function numberToWords(float $amount): string
    {
        $formatter = new \NumberFormatter('en', \NumberFormatter::SPELLOUT);
        $dollars = floor($amount);
        $cents = round(($amount - $dollars) * 100);

        $dollarsWords = $formatter->format($dollars);

        if ($cents > 0) {
            $centsWords = $formatter->format($cents);

            return ucfirst($dollarsWords).' dollars and '.$centsWords.' cents';
        }

        return ucfirst($dollarsWords).' dollars';
    }
}
