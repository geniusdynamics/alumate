<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PeerFundraiser extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campaign_id',
        'user_id',
        'title',
        'personal_message',
        'goal_amount',
        'raised_amount',
        'status',
        'social_links',
        'donor_count',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'social_links' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FundraisingCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(CampaignDonation::class, 'peer_fundraiser_id');
    }

    public function getProgressPercentageAttribute(): float
    {
        if (!$this->goal_amount || $this->goal_amount <= 0) {
            return 0;
        }
        
        return min(100, ($this->raised_amount / $this->goal_amount) * 100);
    }

    public function getShareUrlAttribute(): string
    {
        return route('peer-fundraiser.show', $this);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
