<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageAnalytics extends Model
{
    protected $fillable = [
          'tenant_id',
          'landing_page_id',
          'template_id',
          'event_type',
          'event_name',
          'event_data',
          'session_id',
          'visitor_id',
          'ip_address',
          'user_agent',
          'referrer',
          'utm_data',
          'device_type',
          'browser',
          'os',
          'country',
          'city',
          'event_time',
          'is_compliant',
          'consent_given',
          'data_retention_until',
          'analytics_version',
      ];

    protected $casts = [
        'event_data' => 'array',
        'utm_data' => 'array',
        'event_time' => 'datetime',
        'is_compliant' => 'boolean',
        'consent_given' => 'boolean',
        'data_retention_until' => 'datetime',
    ];

    /**
     * Get the landing page this analytics event belongs to
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Get the tenant this analytics event belongs to
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the template this analytics event belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Scope by tenant
     */
    public function scopeByTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope by template
     */
    public function scopeByTemplate($query, $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope compliant data only
     */
    public function scopeCompliant($query)
    {
        return $query->where('is_compliant', true)->where('consent_given', true);
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
            'visitor_id' => null,
            'country' => null,
            'city' => null,
            'is_compliant' => false,
        ]);
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
        return $query->whereBetween('event_time', [$startDate, $endDate]);
    }
}
