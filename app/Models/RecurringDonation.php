<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_donation_id',
        'campaign_id',
        'donor_id',
        'donor_name',
        'donor_email',
        'amount',
        'currency',
        'frequency',
        'payment_method',
        'payment_data',
        'status',
        'next_payment_date',
        'last_payment_date',
        'total_payments',
        'total_amount_collected',
        'failed_attempts',
        'started_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_amount_collected' => 'decimal:2',
        'payment_data' => 'array',
        'next_payment_date' => 'date',
        'last_payment_date' => 'date',
        'started_at' => 'date',
        'cancelled_at' => 'date',
    ];

    public function originalDonation(): BelongsTo
    {
        return $this->belongsTo(CampaignDonation::class, 'original_donation_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FundraisingCampaign::class, 'campaign_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(CampaignDonation::class, 'recurring_donation_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueForPayment($query)
    {
        return $query->where('status', 'active')
            ->where('next_payment_date', '<=', now()->toDateString());
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    public function calculateNextPaymentDate(): Carbon
    {
        $lastDate = $this->last_payment_date ?
            Carbon::parse($this->last_payment_date) :
            Carbon::parse($this->started_at);

        return match ($this->frequency) {
            'monthly' => $lastDate->addMonth(),
            'quarterly' => $lastDate->addMonths(3),
            'yearly' => $lastDate->addYear(),
            default => $lastDate->addMonth(),
        };
    }

    public function updateNextPaymentDate(): void
    {
        $this->update([
            'next_payment_date' => $this->calculateNextPaymentDate(),
        ]);
    }

    public function recordSuccessfulPayment(CampaignDonation $donation): void
    {
        $this->update([
            'last_payment_date' => now()->toDateString(),
            'total_payments' => $this->total_payments + 1,
            'total_amount_collected' => $this->total_amount_collected + $donation->amount,
            'failed_attempts' => 0,
        ]);

        $this->updateNextPaymentDate();
    }

    public function recordFailedPayment(): void
    {
        $this->increment('failed_attempts');

        // Cancel after 3 failed attempts
        if ($this->failed_attempts >= 3) {
            $this->cancel('Too many failed payment attempts');
        }
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function pause(): void
    {
        $this->update(['status' => 'paused']);
    }

    public function resume(): void
    {
        $this->update(['status' => 'active']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function getDonorDisplayNameAttribute(): string
    {
        return $this->donor ? $this->donor->name : ($this->donor_name ?? 'Anonymous');
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'yearly' => 'Yearly',
            default => ucfirst($this->frequency),
        };
    }
}
