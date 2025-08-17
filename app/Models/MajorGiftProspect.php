<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MajorGiftProspect extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donor_profile_id',
        'prospect_name',
        'description',
        'ask_amount',
        'purpose',
        'stage',
        'probability',
        'expected_close_date',
        'last_activity_date',
        'assigned_officer_id',
        'stakeholders',
        'proposal_details',
        'next_steps',
        'barriers',
        'motivations',
        'actual_amount',
        'close_date',
        'close_notes',
    ];

    protected $casts = [
        'ask_amount' => 'decimal:2',
        'probability' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'expected_close_date' => 'date',
        'last_activity_date' => 'date',
        'close_date' => 'date',
        'stakeholders' => 'array',
        'proposal_details' => 'array',
        'barriers' => 'array',
        'motivations' => 'array',
    ];

    public function donorProfile(): BelongsTo
    {
        return $this->belongsTo(DonorProfile::class);
    }

    public function assignedOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_officer_id');
    }

    // Scopes
    public function scopeByStage($query, $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByOfficer($query, $officerId)
    {
        return $query->where('assigned_officer_id', $officerId);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('stage', ['closed_won', 'closed_lost']);
    }

    public function scopeClosingSoon($query, $days = 30)
    {
        return $query->where('expected_close_date', '<=', now()->addDays($days))
            ->where('expected_close_date', '>=', now())
            ->whereNotIn('stage', ['closed_won', 'closed_lost']);
    }

    public function scopeHighProbability($query, $threshold = 0.7)
    {
        return $query->where('probability', '>=', $threshold);
    }

    public function scopeByProbabilityRange($query, $min, $max)
    {
        return $query->whereBetween('probability', [$min, $max]);
    }

    // Helper methods
    public function getWeightedValueAttribute()
    {
        return $this->ask_amount * $this->probability;
    }

    public function getStageProgressAttribute()
    {
        $stages = [
            'identification' => 10,
            'qualification' => 25,
            'cultivation' => 50,
            'solicitation' => 75,
            'stewardship' => 90,
            'closed_won' => 100,
            'closed_lost' => 0,
        ];

        return $stages[$this->stage] ?? 0;
    }

    public function getIsOverdueAttribute()
    {
        return $this->expected_close_date &&
               $this->expected_close_date < now() &&
               ! in_array($this->stage, ['closed_won', 'closed_lost']);
    }

    public function getDaysUntilCloseAttribute()
    {
        if (! $this->expected_close_date) {
            return null;
        }

        return now()->diffInDays($this->expected_close_date, false);
    }

    public function getProbabilityLabelAttribute()
    {
        return match (true) {
            $this->probability >= 0.8 => 'Very High',
            $this->probability >= 0.6 => 'High',
            $this->probability >= 0.4 => 'Medium',
            $this->probability >= 0.2 => 'Low',
            default => 'Very Low'
        };
    }

    public function moveToNextStage()
    {
        $stages = [
            'identification' => 'qualification',
            'qualification' => 'cultivation',
            'cultivation' => 'solicitation',
            'solicitation' => 'stewardship',
            'stewardship' => 'closed_won',
        ];

        if (isset($stages[$this->stage])) {
            $this->update([
                'stage' => $stages[$this->stage],
                'last_activity_date' => now(),
            ]);
        }
    }

    public function closeAsWon($amount = null, $notes = null)
    {
        $this->update([
            'stage' => 'closed_won',
            'actual_amount' => $amount ?? $this->ask_amount,
            'close_date' => now(),
            'close_notes' => $notes,
            'probability' => 1.0,
        ]);
    }

    public function closeAsLost($notes = null)
    {
        $this->update([
            'stage' => 'closed_lost',
            'close_date' => now(),
            'close_notes' => $notes,
            'probability' => 0.0,
        ]);
    }
}
