<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DonorInteraction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donor_profile_id',
        'user_id',
        'type',
        'subject',
        'description',
        'participants',
        'outcome',
        'interaction_date',
        'duration',
        'attachments',
        'follow_up_actions',
        'next_follow_up_date',
        'potential_gift_amount',
        'private_notes',
    ];

    protected $casts = [
        'participants' => 'array',
        'attachments' => 'array',
        'follow_up_actions' => 'array',
        'interaction_date' => 'date',
        'next_follow_up_date' => 'date',
        'duration' => 'datetime',
        'potential_gift_amount' => 'decimal:2',
    ];

    public function donorProfile(): BelongsTo
    {
        return $this->belongsTo(DonorProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    public function scopeNeedsFollowUp($query)
    {
        return $query->where('next_follow_up_date', '<=', now())
                    ->whereNotNull('next_follow_up_date');
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('interaction_date', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = $this->duration->format('H') * 60 + $this->duration->format('i');
        
        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    public function getIsOverdueAttribute()
    {
        return $this->next_follow_up_date && $this->next_follow_up_date < now();
    }
}
