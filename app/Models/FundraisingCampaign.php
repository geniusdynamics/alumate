<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FundraisingCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'story',
        'goal_amount',
        'raised_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
        'type',
        'media_urls',
        'settings',
        'allow_peer_fundraising',
        'show_donor_names',
        'allow_anonymous_donations',
        'thank_you_message',
        'created_by',
        'institution_id',
        'donor_count',
        'analytics_data',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'media_urls' => 'array',
        'settings' => 'array',
        'allow_peer_fundraising' => 'boolean',
        'show_donor_names' => 'boolean',
        'allow_anonymous_donations' => 'boolean',
        'analytics_data' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(CampaignDonation::class, 'campaign_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(CampaignUpdate::class, 'campaign_id');
    }

    public function peerFundraisers(): HasMany
    {
        return $this->hasMany(PeerFundraiser::class, 'campaign_id');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->goal_amount <= 0) {
            return 0;
        }

        return min(100, ($this->raised_amount / $this->goal_amount) * 100);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->start_date, $this->end_date);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function scopeByInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
