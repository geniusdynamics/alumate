<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonorProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'donor_tier',
        'lifetime_giving',
        'largest_gift',
        'capacity_rating',
        'inclination_score',
        'giving_interests',
        'preferred_contact_methods',
        'preferred_contact_frequency',
        'last_contact_date',
        'next_contact_date',
        'assigned_officer_id',
        'notes',
        'wealth_indicators',
        'relationship_connections',
        'is_anonymous',
        'do_not_contact',
    ];

    protected $casts = [
        'lifetime_giving' => 'decimal:2',
        'largest_gift' => 'decimal:2',
        'capacity_rating' => 'decimal:2',
        'inclination_score' => 'decimal:2',
        'giving_interests' => 'array',
        'preferred_contact_methods' => 'array',
        'wealth_indicators' => 'array',
        'relationship_connections' => 'array',
        'last_contact_date' => 'date',
        'next_contact_date' => 'date',
        'is_anonymous' => 'boolean',
        'do_not_contact' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(DonorInteraction::class);
    }

    public function stewardshipPlans(): HasMany
    {
        return $this->hasMany(DonorStewardshipPlan::class);
    }

    public function majorGiftProspects(): HasMany
    {
        return $this->hasMany(MajorGiftProspect::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(CampaignDonation::class, 'user_id', 'user_id');
    }

    // Scopes
    public function scopeByTier($query, $tier)
    {
        return $query->where('donor_tier', $tier);
    }

    public function scopeByOfficer($query, $officerId)
    {
        return $query->where('assigned_officer_id', $officerId);
    }

    public function scopeNeedsContact($query)
    {
        return $query->where('next_contact_date', '<=', now())
                    ->where('do_not_contact', false);
    }

    public function scopeByCapacityRange($query, $min, $max)
    {
        return $query->whereBetween('capacity_rating', [$min, $max]);
    }

    // Helper methods
    public function getEngagementScoreAttribute()
    {
        $recentInteractions = $this->interactions()
            ->where('interaction_date', '>=', now()->subMonths(6))
            ->count();
        
        $givingFrequency = $this->donations()
            ->where('created_at', '>=', now()->subYear())
            ->count();

        return min(100, ($recentInteractions * 10) + ($givingFrequency * 5));
    }

    public function getNextContactDueAttribute()
    {
        return $this->next_contact_date && $this->next_contact_date <= now();
    }

    public function updateLifetimeGiving()
    {
        $total = $this->donations()->sum('amount');
        $largest = $this->donations()->max('amount');
        
        $this->update([
            'lifetime_giving' => $total,
            'largest_gift' => $largest,
        ]);
    }
}
