<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AttributionTouch Model
 *
 * Represents a user touchpoint in the attribution journey for marketing analytics.
 * Tracks interactions across different channels and sources to enable attribution modeling.
 */
class AttributionTouch extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_id',
        'event_type',
        'source',
        'medium',
        'campaign',
        'value',
        'timestamp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the tenant that owns the attribution touch.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that owns the attribution touch.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include attribution touches for a specific tenant.
     */
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include attribution touches for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include attribution touches within a date range.
     */
    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include attribution touches from a specific source.
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to only include attribution touches from specific sources.
     */
    public function scopeBySources($query, array $sources)
    {
        return $query->whereIn('source', $sources);
    }

    /**
     * Scope a query to only include attribution touches of a specific event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to order attribution touches by timestamp (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('timestamp', 'desc');
    }

    /**
     * Scope a query to only include attribution touches with positive value.
     */
    public function scopeWithValue($query)
    {
        return $query->where('value', '>', 0);
    }

    /**
     * Get the formatted touch summary.
     */
    public function getTouchSummaryAttribute(): string
    {
        $parts = [];

        if ($this->source) {
            $parts[] = $this->source;
        }

        if ($this->medium) {
            $parts[] = $this->medium;
        }

        if ($this->campaign) {
            $parts[] = $this->campaign;
        }

        return implode(' / ', array_filter($parts)) ?: 'Unknown';
    }

    /**
     * Check if the touch has a positive value.
     */
    public function hasValue(): bool
    {
        return $this->value > 0;
    }

    /**
     * Get the touch value formatted as currency.
     */
    public function getFormattedValueAttribute(): string
    {
        return '$'.number_format((float) $this->value, 2);
    }
}
