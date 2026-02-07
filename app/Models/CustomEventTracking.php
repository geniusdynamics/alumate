<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CustomEventTracking Model
 *
 * Tracks individual instances of custom events with their data and context.
 * Extends the analytics system for detailed event tracking and analysis.
 */
class CustomEventTracking extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'custom_event_tracking';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'event_name',
        'event_data',
        'context',
        'user_id',
        'session_id',
        'user_agent',
        'ip_address',
        'referrer',
        'page_url',
        'occurred_at',
        'is_compliant',
        'consent_given',
        'data_retention_until',
        'analytics_version',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_data' => 'array',
        'context' => 'array',
        'occurred_at' => 'datetime',
        'is_compliant' => 'boolean',
        'consent_given' => 'boolean',
        'data_retention_until' => 'datetime',
    ];

    /**
     * Get the tenant that owns this custom event tracking record.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user associated with this custom event tracking record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the session associated with this custom event tracking record.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionRecording::class, 'session_id');
    }

    /**
     * Scope a query to only include custom event tracking records for a specific tenant.
     */
    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope a query to only include custom event tracking records for a specific event name.
     */
    public function scopeByEventName($query, string $eventName)
    {
        return $query->where('event_name', $eventName);
    }

    /**
     * Scope a query to only include custom event tracking records within a date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include custom event tracking records for a specific user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include custom event tracking records for a specific session.
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope a query to only include compliant data.
     */
    public function scopeCompliant($query)
    {
        return $query->where('is_compliant', true);
    }

    /**
     * Check if data can be retained.
     */
    public function canRetainData(): bool
    {
        return ! $this->data_retention_until || now()->lessThan($this->data_retention_until);
    }

    /**
     * Mark data for anonymization.
     */
    public function anonymize(): void
    {
        $this->update([
            'ip_address' => null,
            'user_agent' => 'anonymized',
            'is_compliant' => false,
        ]);
    }

    /**
     * Get event data value by key.
     */
    public function getEventData(string $key, $default = null)
    {
        return data_get($this->event_data, $key, $default);
    }

    /**
     * Get context value by key.
     */
    public function getContext(string $key, $default = null)
    {
        return data_get($this->context, $key, $default);
    }
}
