<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignDonation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'donor_id',
        'peer_fundraiser_id',
        'amount',
        'currency',
        'is_anonymous',
        'donor_name',
        'donor_email',
        'message',
        'payment_method',
        'payment_id',
        'status',
        'payment_data',
        'is_recurring',
        'recurring_frequency',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'payment_data' => 'array',
        'is_recurring' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FundraisingCampaign::class, 'campaign_id');
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function peerFundraiser(): BelongsTo
    {
        return $this->belongsTo(PeerFundraiser::class, 'peer_fundraiser_id');
    }

    public function recurringDonation(): BelongsTo
    {
        return $this->belongsTo(RecurringDonation::class, 'recurring_donation_id');
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'donation_id');
    }

    public function acknowledgments(): HasMany
    {
        return $this->hasMany(DonationAcknowledgment::class, 'donation_id');
    }

    public function getDonorDisplayNameAttribute(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        return $this->donor ? $this->donor->name : ($this->donor_name ?? 'Anonymous');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('processed_at', 'desc');
    }
}
