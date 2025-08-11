<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareerTimeline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company',
        'title',
        'start_date',
        'end_date',
        'description',
        'is_current',
        'achievements',
        'location',
        'company_logo_url',
        'industry',
        'employment_type',
    ];

    protected $casts = [
        'achievements' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the user that owns the career timeline entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the duration of this position in months
     */
    public function getDurationInMonthsAttribute(): int
    {
        $endDate = $this->is_current ? now() : $this->end_date;

        return $this->start_date->diffInMonths($endDate);
    }

    /**
     * Get formatted duration string
     */
    public function getFormattedDurationAttribute(): string
    {
        $months = $this->duration_in_months;
        $years = intval($months / 12);
        $remainingMonths = $months % 12;

        if ($years > 0 && $remainingMonths > 0) {
            return "{$years} yr".($years > 1 ? 's' : '')." {$remainingMonths} mo".($remainingMonths > 1 ? 's' : '');
        } elseif ($years > 0) {
            return "{$years} yr".($years > 1 ? 's' : '');
        } else {
            return "{$remainingMonths} mo".($remainingMonths > 1 ? 's' : '');
        }
    }

    /**
     * Check if this position overlaps with another position
     */
    public function overlapsWith(CareerTimeline $other): bool
    {
        $thisEnd = $this->is_current ? now() : $this->end_date;
        $otherEnd = $other->is_current ? now() : $other->end_date;

        return $this->start_date <= $otherEnd && $thisEnd >= $other->start_date;
    }

    /**
     * Scope to get current positions
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope to get positions ordered by start date
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('start_date', 'desc');
    }

    /**
     * Check if this is a promotion from previous position
     */
    public function isPromotionFrom(CareerTimeline $previous): bool
    {
        return $this->company === $previous->company &&
               $this->start_date >= $previous->start_date;
    }
}
