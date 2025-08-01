<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'donor_id',
        'donor_name',
        'donor_email',
        'donor_address',
        'total_amount',
        'currency',
        'tax_year',
        'receipt_date',
        'donations',
        'status',
        'pdf_path',
        'metadata',
        'generated_at',
        'sent_at',
    ];

    protected $casts = [
        'donor_address' => 'array',
        'total_amount' => 'decimal:2',
        'donations' => 'array',
        'metadata' => 'array',
        'receipt_date' => 'date',
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function getDonationRecords(): array
    {
        $donationIds = collect($this->donations)->pluck('donation_id')->toArray();
        
        return CampaignDonation::whereIn('id', $donationIds)
            ->with('campaign')
            ->get()
            ->toArray();
    }

    public function scopeForTaxYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopeForDonor($query, int $donorId)
    {
        return $query->where('donor_id', $donorId);
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function markAsDownloaded(): void
    {
        $this->update([
            'status' => 'downloaded',
        ]);
    }

    public function getDownloadUrl(): string
    {
        return route('tax-receipts.download', $this);
    }

    public function getFormattedReceiptNumber(): string
    {
        return "TR-{$this->tax_year}-{$this->receipt_number}";
    }
}