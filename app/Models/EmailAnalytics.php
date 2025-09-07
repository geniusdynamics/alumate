<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EmailAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'email_campaign_id',
        'email_template_id',
        'recipient_id',
        'recipient_email',
        'subject_line',
        'send_date',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'converted_at',
        'unsubscribed_at',
        'bounced_at',
        'complained_at',
        'delivery_status',
        'open_count',
        'click_count',
        'conversion_count',
        'bounce_reason',
        'complaint_reason',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'location',
        'referrer_url',
        'conversion_value',
        'conversion_type',
        'funnel_stage',
        'ab_test_variant',
        'tags',
        'custom_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'send_date' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
        'conversion_count' => 'integer',
        'conversion_value' => 'decimal:2',
        'tags' => 'array',
        'custom_data' => 'array',
        'funnel_stage' => 'integer',
    ];

    protected $attributes = [
        'open_count' => 0,
        'click_count' => 0,
        'conversion_count' => 0,
        'conversion_value' => 0.00,
        'funnel_stage' => 1,
        'tags' => '[]',
        'custom_data' => '{}',
    ];

    /**
     * Delivery status constants
     */
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_OPENED = 'opened';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINT = 'complaint';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /**
     * Device type constants
     */
    public const DEVICE_DESKTOP = 'desktop';
    public const DEVICE_MOBILE = 'mobile';
    public const DEVICE_TABLET = 'tablet';

    /**
     * Conversion type constants
     */
    public const CONVERSION_PURCHASE = 'purchase';
    public const CONVERSION_SIGNUP = 'signup';
    public const CONVERSION_DOWNLOAD = 'download';
    public const CONVERSION_CONTACT = 'contact';
    public const CONVERSION_CUSTOM = 'custom';

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
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query by delivery status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('delivery_status', $status);
    }

    /**
     * Scope query by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('send_date', [$startDate, $endDate]);
    }

    /**
     * Scope query for opened emails
     */
    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    /**
     * Scope query for clicked emails
     */
    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    /**
     * Scope query for converted emails
     */
    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }

    /**
     * Scope query for bounced emails
     */
    public function scopeBounced($query)
    {
        return $query->whereNotNull('bounced_at');
    }

    /**
     * Scope query by device type
     */
    public function scopeByDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope query by A/B test variant
     */
    public function scopeByVariant($query, string $variant)
    {
        return $query->where('ab_test_variant', $variant);
    }

    /**
     * Get the tenant that owns the email analytics
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the email campaign
     */
    public function emailCampaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    /**
     * Get the email template
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'email_template_id');
    }

    /**
     * Get the recipient user
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the user who created this record
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this record
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if email was delivered
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * Check if email was opened
     */
    public function isOpened(): bool
    {
        return !is_null($this->opened_at);
    }

    /**
     * Check if email was clicked
     */
    public function isClicked(): bool
    {
        return !is_null($this->clicked_at);
    }

    /**
     * Check if email led to conversion
     */
    public function isConverted(): bool
    {
        return !is_null($this->converted_at);
    }

    /**
     * Check if email bounced
     */
    public function isBounced(): bool
    {
        return !is_null($this->bounced_at);
    }

    /**
     * Check if recipient complained
     */
    public function isComplained(): bool
    {
        return !is_null($this->complained_at);
    }

    /**
     * Check if recipient unsubscribed
     */
    public function isUnsubscribed(): bool
    {
        return !is_null($this->unsubscribed_at);
    }

    /**
     * Get time to open in minutes
     */
    public function getTimeToOpen(): ?int
    {
        if (!$this->isDelivered() || !$this->isOpened()) {
            return null;
        }

        return $this->delivered_at->diffInMinutes($this->opened_at);
    }

    /**
     * Get time to click in minutes
     */
    public function getTimeToClick(): ?int
    {
        if (!$this->isOpened() || !$this->isClicked()) {
            return null;
        }

        return $this->opened_at->diffInMinutes($this->clicked_at);
    }

    /**
     * Get time to convert in minutes
     */
    public function getTimeToConvert(): ?int
    {
        if (!$this->isClicked() || !$this->isConverted()) {
            return null;
        }

        return $this->clicked_at->diffInMinutes($this->converted_at);
    }

    /**
     * Get conversion rate for this email
     */
    public function getConversionRate(): float
    {
        return $this->isClicked() && $this->conversion_count > 0 ? 100.0 : 0.0;
    }

    /**
     * Get click-through rate for this email
     */
    public function getClickThroughRate(): float
    {
        return $this->isOpened() && $this->isClicked() ? 100.0 : 0.0;
    }

    /**
     * Get open rate for this email
     */
    public function getOpenRate(): float
    {
        return $this->isDelivered() && $this->isOpened() ? 100.0 : 0.0;
    }

    /**
     * Record email delivery
     */
    public function recordDelivery(Carbon $deliveredAt = null): void
    {
        $this->update([
            'delivered_at' => $deliveredAt ?: now(),
            'delivery_status' => self::STATUS_DELIVERED,
        ]);
    }

    /**
     * Record email open
     */
    public function recordOpen(array $metadata = []): void
    {
        $updateData = [
            'opened_at' => now(),
            'delivery_status' => self::STATUS_OPENED,
            'open_count' => $this->open_count + 1,
        ];

        if (isset($metadata['ip_address'])) {
            $updateData['ip_address'] = $metadata['ip_address'];
        }

        if (isset($metadata['user_agent'])) {
            $updateData['user_agent'] = $metadata['user_agent'];
            $this->parseUserAgent($metadata['user_agent']);
        }

        if (isset($metadata['location'])) {
            $updateData['location'] = $metadata['location'];
        }

        $this->update($updateData);
    }

    /**
     * Record email click
     */
    public function recordClick(string $url, array $metadata = []): void
    {
        $updateData = [
            'clicked_at' => now(),
            'delivery_status' => self::STATUS_CLICKED,
            'click_count' => $this->click_count + 1,
        ];

        if (isset($metadata['referrer_url'])) {
            $updateData['referrer_url'] = $metadata['referrer_url'];
        }

        $this->update($updateData);

        // Update custom data with click tracking
        $customData = $this->custom_data ?? [];
        $clicks = $customData['clicks'] ?? [];
        $clicks[] = [
            'url' => $url,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata,
        ];
        $customData['clicks'] = $clicks;

        $this->update(['custom_data' => $customData]);
    }

    /**
     * Record conversion
     */
    public function recordConversion(string $type, float $value = 0.00, array $metadata = []): void
    {
        $updateData = [
            'converted_at' => now(),
            'delivery_status' => self::STATUS_CONVERTED,
            'conversion_count' => $this->conversion_count + 1,
            'conversion_type' => $type,
            'conversion_value' => $this->conversion_value + $value,
            'funnel_stage' => 5, // Final stage
        ];

        $this->update($updateData);

        // Update custom data with conversion tracking
        $customData = $this->custom_data ?? [];
        $conversions = $customData['conversions'] ?? [];
        $conversions[] = [
            'type' => $type,
            'value' => $value,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata,
        ];
        $customData['conversions'] = $conversions;

        $this->update(['custom_data' => $customData]);
    }

    /**
     * Record bounce
     */
    public function recordBounce(string $reason): void
    {
        $this->update([
            'bounced_at' => now(),
            'delivery_status' => self::STATUS_BOUNCED,
            'bounce_reason' => $reason,
        ]);
    }

    /**
     * Record complaint
     */
    public function recordComplaint(string $reason): void
    {
        $this->update([
            'complained_at' => now(),
            'delivery_status' => self::STATUS_COMPLAINT,
            'complaint_reason' => $reason,
        ]);
    }

    /**
     * Record unsubscribe
     */
    public function recordUnsubscribe(): void
    {
        $this->update([
            'unsubscribed_at' => now(),
            'delivery_status' => self::STATUS_UNSUBSCRIBED,
        ]);
    }

    /**
     * Parse user agent string to extract device and browser info
     */
    private function parseUserAgent(string $userAgent): void
    {
        // Simple user agent parsing - in production, use a proper library
        $updateData = [];

        if (stripos($userAgent, 'mobile') !== false) {
            $updateData['device_type'] = self::DEVICE_MOBILE;
        } elseif (stripos($userAgent, 'tablet') !== false) {
            $updateData['device_type'] = self::DEVICE_TABLET;
        } else {
            $updateData['device_type'] = self::DEVICE_DESKTOP;
        }

        // Extract browser info
        if (stripos($userAgent, 'chrome') !== false) {
            $updateData['browser'] = 'chrome';
        } elseif (stripos($userAgent, 'firefox') !== false) {
            $updateData['browser'] = 'firefox';
        } elseif (stripos($userAgent, 'safari') !== false) {
            $updateData['browser'] = 'safari';
        } elseif (stripos($userAgent, 'edge') !== false) {
            $updateData['browser'] = 'edge';
        } else {
            $updateData['browser'] = 'other';
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'email_campaign_id' => 'nullable|exists:email_campaigns,id',
            'email_template_id' => 'nullable|exists:templates,id',
            'recipient_id' => 'nullable|exists:users,id',
            'recipient_email' => 'required|email|max:255',
            'subject_line' => 'nullable|string|max:255',
            'send_date' => 'required|date',
            'delivery_status' => ['nullable', 'string', Rule::in([
                self::STATUS_SENT,
                self::STATUS_DELIVERED,
                self::STATUS_OPENED,
                self::STATUS_CLICKED,
                self::STATUS_CONVERTED,
                self::STATUS_BOUNCED,
                self::STATUS_COMPLAINT,
                self::STATUS_UNSUBSCRIBED,
            ])],
            'device_type' => ['nullable', 'string', Rule::in([
                self::DEVICE_DESKTOP,
                self::DEVICE_MOBILE,
                self::DEVICE_TABLET,
            ])],
            'conversion_type' => ['nullable', 'string', Rule::in([
                self::CONVERSION_PURCHASE,
                self::CONVERSION_SIGNUP,
                self::CONVERSION_DOWNLOAD,
                self::CONVERSION_CONTACT,
                self::CONVERSION_CUSTOM,
            ])],
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'referrer_url' => 'nullable|url|max:1000',
            'conversion_value' => 'nullable|numeric|min:0',
            'funnel_stage' => 'nullable|integer|min:1|max:5',
            'ab_test_variant' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'custom_data' => 'nullable|array',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        // Add unique constraints if needed
        return $rules;
    }
}