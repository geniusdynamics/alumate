<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A/B Test Event Model
 *
 * Tracks individual events and conversions for A/B testing
 */
class AbTestEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ab_test_id',
        'variant_id',
        'event_type',
        'session_id',
        'event_data',
        'occurred_at'
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime'
    ];

    /**
     * Get the A/B test this event belongs to
     */
    public function abTest(): BelongsTo
    {
        return $this->belongsTo(TemplateAbTest::class, 'ab_test_id');
    }

    /**
     * Scope for specific event types
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for specific variants
     */
    public function scopeForVariant($query, string $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }
}