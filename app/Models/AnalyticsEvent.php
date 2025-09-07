<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnalyticsEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'event_type',
        'event_name',
        'user_id',
        'properties',
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

    protected $casts = [
        'properties' => 'array',
        'occurred_at' => 'datetime',
        'is_compliant' => 'boolean',
        'consent_given' => 'boolean',
        'data_retention_until' => 'datetime',
    ];

    /**
     * Get the tenant this analytics event belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who triggered this analytics event
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope compliant data only
     */
    public function scopeCompliant($query)
    {
        return $query->where('is_compliant', true);
    }

    /**
     * Check if data can be retained
     */
    public function canRetainData(): bool
    {
        return !$this->data_retention_until || now()->lessThan($this->data_retention_until);
    }

    /**
     * Mark data for anonymization
     */
    public function anonymize(): void
    {
        $this->update([
            'ip_address' => null,
            'user_agent' => 'anonymized',
            'is_compliant' => false,
        ]);
    }
}
