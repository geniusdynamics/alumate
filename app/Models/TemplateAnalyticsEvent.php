<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TemplateAnalyticsEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'landing_page_id',
        'event_type',
        'event_data',
        'user_identifier',
        'user_agent',
        'ip_address',
        'referrer_url',
        'session_id',
        'conversion_value',
        'geo_location',
        'device_info',
        'timestamp',
        'created_by',
        'is_compliant',
        'consent_given',
        'data_retention_until',
        'analytics_version',
    ];

    protected $casts = [
        'event_data' => 'array',
        'geo_location' => 'array',
        'device_info' => 'array',
        'timestamp' => 'datetime',
        'conversion_value' => 'decimal:2',
        'is_compliant' => 'boolean',
        'consent_given' => 'boolean',
        'data_retention_until' => 'datetime',
    ];

    /**
     * Analytics event types
     */
    public const EVENT_TYPES = [
        'page_view',
        'click',
        'form_submit',
        'conversion',
        'scroll',
        'time_on_page',
        'exit',
        'engagement',
        'cta_click',
        'social_share',
        'download',
        'video_play',
        'video_complete',
    ];

    /**
     * Device types for analytics
     */
    public const DEVICE_TYPES = [
        'desktop',
        'tablet',
        'mobile',
        'smartphone',
        'feature_phone',
    ];

    /**
     * Browser types
     */
    public const BROWSER_TYPES = [
        'chrome',
        'firefox',
        'safari',
        'edge',
        'opera',
        'ie',
        'unknown',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });

        // Set timestamp on creating if not provided
        static::creating(function ($event) {
            if (empty($event->timestamp)) {
                $event->timestamp = now();
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to specific template
     */
    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope query to specific landing page
     */
    public function scopeForLandingPage($query, int $landingPageId)
    {
        return $query->where('landing_page_id', $landingPageId);
    }

    /**
     * Scope query by event type
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope query for date range
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('timestamp', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope query for today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('timestamp', today());
    }

    /**
     * Scope query for specific session
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope query for conversions only
     */
    public function scopeConversions($query)
    {
        return $query->where('event_type', 'conversion');
    }

    /**
     * Get the tenant that owns the event
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the template associated with this event
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the landing page associated with this event
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    /**
     * Get the user who created this event (for server-side events)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Parse user agent to get device information
     */
    public function parseUserAgent(): array
    {
        if (empty($this->user_agent)) {
            return ['type' => 'unknown', 'browser' => 'unknown'];
        }

        $userAgent = strtolower($this->user_agent);

        $deviceType = $this->detectDeviceType($userAgent);
        $browser = $this->detectBrowser($userAgent);

        return [
            'type' => $deviceType,
            'browser' => $browser,
            'is_mobile' => in_array($deviceType, ['mobile', 'smartphone', 'tablet']),
            'is_desktop' => $deviceType === 'desktop',
        ];
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType(string $userAgent): string
    {
        $mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'blackberry', 'opera mini'];
        $tabletKeywords = ['tablet', 'ipad', 'android.*tablet'];

        if (preg_match('/(' . implode('|', $tabletKeywords) . ')/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/(' . implode('|', $mobileKeywords) . ')/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent
     */
    private function detectBrowser(string $userAgent): string
    {
        $browsers = [
            'chrome' => ['chrome'],
            'firefox' => ['firefox', 'fxios'],
            'safari' => ['safari'],
            'edge' => ['edge', 'edg'],
            'opera' => ['opera', 'opr'],
            'ie' => ['msie', 'trident'],
        ];

        foreach ($browsers as $browser => $keywords) {
            if (preg_match('/(' . implode('|', $keywords) . ')/i', $userAgent)) {
                return $browser;
            }
        }

        return 'unknown';
    }

    /**
     * Get formatted event data
     */
    public function getFormattedEventData(): array
    {
        $data = $this->event_data ?? [];

        // Add computed fields
        $data['parsed_user_agent'] = $this->parseUserAgent();
        $data['is_conversion_event'] = $this->event_type === 'conversion';
        $data['has_conversion_value'] = !empty($this->conversion_value);

        return $data;
    }

    /**
     * Check if this is a conversion event
     */
    public function isConversion(): bool
    {
        return $this->event_type === 'conversion';
    }

    /**
     * Get conversion value (0 if not a conversion)
     */
    public function getConversionValue(): float
    {
        return $this->conversion_value ?? 0.0;
    }

    /**
     * Get time on page from event data (for time_on_page events)
     */
    public function getTimeOnPage(): int
    {
        if ($this->event_type !== 'time_on_page') {
            return 0;
        }

        $data = $this->event_data ?? [];
        return $data['duration_seconds'] ?? 0;
    }

    /**
     * Get scroll depth from event data (for scroll events)
     */
    public function getScrollDepth(): int
    {
        if ($this->event_type !== 'scroll') {
            return 0;
        }

        $data = $this->event_data ?? [];
        return $data['depth_percent'] ?? 0;
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'template_id' => 'required|exists:templates,id',
            'landing_page_id' => 'nullable|exists:landing_pages,id',
            'event_type' => 'required|string|in:' . implode(',', self::EVENT_TYPES),
            'event_data' => 'nullable|array',
            'user_identifier' => 'nullable|string|max:255',
            'user_agent' => 'nullable|string|max:1000',
            'ip_address' => 'nullable|ip',
            'referrer_url' => 'nullable|url|max:2000',
            'session_id' => 'nullable|string|max:255',
            'conversion_value' => 'nullable|numeric|min:0|max:999999.99',
            'geo_location' => 'nullable|array',
            'device_info' => 'nullable|array',
            'timestamp' => 'nullable|date',
            'created_by' => 'nullable|exists:users,id',
            'is_compliant' => 'boolean',
            'consent_given' => 'boolean',
            'data_retention_until' => 'nullable|date',
            'analytics_version' => 'nullable|string|max:10',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        // No unique constraints for analytics events as they can be duplicated
        return $rules;
    }

    /**
     * Check if data can still be retained based on retention policy
     */
    public function canRetainData(): bool
    {
        return !$this->data_retention_until || now()->lessThan($this->data_retention_until);
    }

    /**
     * Anonymize personal data for GDPR compliance
     */
    public function anonymize(): void
    {
        $this->update([
            'ip_address' => null,
            'user_agent' => 'anonymized',
            'user_identifier' => null,
            'is_compliant' => false,
            'geo_location' => null,
        ]);
    }

    /**
     * Scope query to GDPR compliant events only
     */
    public function scopeGdprCompliant($query)
    {
        return $query->where('is_compliant', true)->where('consent_given', true);
    }

    /**
     * Scope query to events that can still be retained
     */
    public function scopeRetainable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('data_retention_until')
              ->orWhere('data_retention_until', '>', now());
        });
    }

    /**
     * Get GDPR compliance status
     */
    public function getGdprStatus(): array
    {
        return [
            'is_compliant' => $this->is_compliant,
            'consent_given' => $this->consent_given,
            'can_retain_data' => $this->canRetainData(),
            'data_retention_until' => $this->data_retention_until,
            'analytics_version' => $this->analytics_version,
        ];
    }
}