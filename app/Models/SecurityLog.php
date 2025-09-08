<?php
// ABOUTME: SecurityLog model for tracking security events with schema-based tenant isolation
// ABOUTME: Handles security logging, threat detection, and audit trails within tenant context

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\TenantContextService;

/**
 * SecurityLog Model
 *
 * Tracks security events related to template operations with tenant isolation
 */
class SecurityLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'security_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'tenant_id', // Removed for schema-based tenancy
        'user_id',
        'event_type',
        'event_category',
        'severity',
        'description',
        'template_id',
        'resource_type',
        'resource_id',
        'ip_address',
        'user_agent',
        'request_path',
        'request_method',
        'metadata',
        'validation_errors',
        'threat_patterns',
        'resolution_status',
        'resolution_notes',
        'occurred_at',
        'resolved_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'validation_errors' => 'array',
        'threat_patterns' => 'array',
        'occurred_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * Event type constants
     */
    const EVENT_TYPE_TEMPLATE_VIOLATION = 'template_violation';
    const EVENT_TYPE_SCRIPT_INJECTION = 'script_injection';
    const EVENT_TYPE_XSS_ATTEMPT = 'xss_attempt';
    const EVENT_TYPE_MALICIOUS_INPUT = 'malicious_input';
    const EVENT_TYPE_TENANT_ISOLATION_BREACH = 'tenant_isolation_breach';
    const EVENT_TYPE_RATE_LIMIT_EXCEEDED = 'rate_limit_exceeded';
    const EVENT_TYPE_FILE_UPLOAD_THREAT = 'file_upload_threat';
    const EVENT_TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const EVENT_TYPE_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    const EVENT_TYPE_TEMPLATE_MODIFICATION = 'template_modification';

    /**
     * Event category constants
     */
    const CATEGORY_INPUT_VALIDATION = 'input_validation';
    const CATEGORY_XSS_PREVENTION = 'xss_prevention';
    const CATEGORY_ACCESS_CONTROL = 'access_control';
    const CATEGORY_FILE_SECURITY = 'file_security';
    const CATEGORY_TENANT_SECURITY = 'tenant_security';
    const CATEGORY_RATE_LIMITING = 'rate_limiting';
    const CATEGORY_THREAT_DETECTION = 'threat_detection';

    /**
     * Severity constants
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    /**
     * Resolution status constants
     */
    const STATUS_OPEN = 'open';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_FALSE_POSITIVE = 'false_positive';
    const STATUS_IGNORED = 'ignored';

    /**
     * Get the user that owns the security log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the template associated with this security log.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /**
     * Get the current tenant context
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    /**
     * Scope to filter by tenant (legacy compatibility)
     */
    public function scopeForTenant($query, $tenantId = null)
    {
        // In schema-based tenancy, tenant context is automatically applied
        return $query;
    }

    /**
     * The model's "booted" method.
     */
    protected static function booted(): void
    {
        // Apply tenant context global scope
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound(TenantContextService::class)) {
                $tenantContext = app(TenantContextService::class);
                if ($tenantContext->hasTenantContext()) {
                    // In schema-based tenancy, queries are automatically scoped to current schema
                    // No additional filtering needed
                }
            }
        });
    }

    /**
     * Scope to filter by event type
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by severity
     */
    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter unresolved events
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolution_status', '!=', self::STATUS_RESOLVED);
    }

    /**
     * Scope to filter critical severity events
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope to filter recent events
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('occurred_at', '>=', now()->subHours($hours));
    }

    /**
     * Create a security log entry
     *
     * @param array $attributes
     * @return static
     */
    public static function log(array $attributes): self
    {
        return static::create(array_merge($attributes, [
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_OPEN,
        ]));
    }

    /**
     * Log template security violation
     *
     * @param int|null $userId
     * @param int|null $templateId
     * @param array $violations
     * @param array $additionalData
     * @return static
     */
    public static function logTemplateViolation(
        ?int $userId,
        ?int $templateId,
        array $violations,
        array $additionalData = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => self::EVENT_TYPE_TEMPLATE_VIOLATION,
            'event_category' => self::CATEGORY_INPUT_VALIDATION,
            'severity' => self::getSeverityFromViolations($violations),
            'description' => 'Template validation failed with security violations',
            'template_id' => $templateId,
            'validation_errors' => $violations,
            'metadata' => $additionalData,
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_OPEN,
        ]);
    }

    /**
     * Log XSS attempt
     *
     * @param int|null $userId
     * @param int|null $templateId
     * @param array $threatDetails
     * @return static
     */
    public static function logXssAttempt(
        ?int $userId,
        ?int $templateId,
        array $threatDetails
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => self::EVENT_TYPE_XSS_ATTEMPT,
            'event_category' => self::CATEGORY_XSS_PREVENTION,
            'severity' => self::SEVERITY_HIGH,
            'description' => 'Potential XSS attack detected in template',
            'template_id' => $templateId,
            'threat_patterns' => $threatDetails,
            'metadata' => $threatDetails,
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_OPEN,
        ]);
    }

    /**
     * Log tenant isolation breach
     *
     * @param int|null $userId
     * @param string $attemptedSchema
     * @param array $additionalData
     * @return static
     */
    public static function logTenantIsolationBreach(
        ?int $userId,
        string $attemptedSchema,
        array $additionalData = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => self::EVENT_TYPE_TENANT_ISOLATION_BREACH,
            'event_category' => self::CATEGORY_TENANT_SECURITY,
            'severity' => self::SEVERITY_CRITICAL,
            'description' => "Tenant isolation breach attempted: tried to access schema {$attemptedSchema}",
            'metadata' => array_merge($additionalData, [
                'attempted_schema' => $attemptedSchema,
                'breach_type' => 'cross_schema_access'
            ]),
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_OPEN,
        ]);
    }

    /**
     * Log unauthorized access attempt
     *
     * @param int|null $userId
     * @param string $resourceType
     * @param int|string $resourceId
     * @param array $additionalData
     * @return static
     */
    public static function logUnauthorizedAccess(
        ?int $userId,
        string $resourceType,
        $resourceId,
        array $additionalData = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => self::EVENT_TYPE_UNAUTHORIZED_ACCESS,
            'event_category' => self::CATEGORY_ACCESS_CONTROL,
            'severity' => self::SEVERITY_HIGH,
            'description' => "Unauthorized access attempt to {$resourceType}:{$resourceId}",
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'metadata' => $additionalData,
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_OPEN,
        ]);
    }

    /**
     * Log rate limit exceeded
     *
     * @param int|null $userId
     * @param string $operationType
     * @param array $additionalData
     * @return static
     */
    public static function logRateLimitExceeded(
        ?int $userId,
        string $operationType,
        array $additionalData = []
    ): self {
        return static::create([
            'user_id' => $userId,
            'event_type' => self::EVENT_TYPE_RATE_LIMIT_EXCEEDED,
            'event_category' => self::CATEGORY_RATE_LIMITING,
            'severity' => self::SEVERITY_MEDIUM,
            'description' => "Rate limit exceeded for operation: {$operationType}",
            'metadata' => array_merge($additionalData, [
                'operation_type' => $operationType
            ]),
            'occurred_at' => now(),
            'resolution_status' => self::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Mark event as resolved
     *
     * @param string|null $notes
     * @return bool
     */
    public function markResolved(?string $notes = null): bool
    {
        $this->update([
            'resolution_status' => self::STATUS_RESOLVED,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark event as false positive
     *
     * @param string|null $notes
     * @return bool
     */
    public function markFalsePositive(?string $notes = null): bool
    {
        $this->update([
            'resolution_status' => self::STATUS_FALSE_POSITIVE,
            'resolution_notes' => $notes,
            'resolved_at' => now(),
        ]);

        return true;
    }

    /**
     * Get severity level from violations array
     *
     * @param array $violations
     * @return string
     */
    protected static function getSeverityFromViolations(array $violations): string
    {
        $hasCritical = false;
        $hasHigh = false;

        foreach ($violations as $violation) {
            $severity = $violation['severity'] ?? self::SEVERITY_LOW;

            if ($severity === self::SEVERITY_CRITICAL) {
                $hasCritical = true;
            } elseif ($severity === self::SEVERITY_HIGH) {
                $hasHigh = true;
            }
        }

        if ($hasCritical) {
            return self::SEVERITY_CRITICAL;
        } elseif ($hasHigh) {
            return self::SEVERITY_HIGH;
        }

        return self::SEVERITY_MEDIUM;
    }

    /**
     * Get security statistics for current tenant
     *
     * @return array
     */
    public static function getSecurityStats(): array
    {
        $query = static::query();

        return [
            'total_events' => $query->count(),
            'critical_events' => (clone $query)->bySeverity(self::SEVERITY_CRITICAL)->count(),
            'high_severity_events' => (clone $query)->bySeverity(self::SEVERITY_HIGH)->count(),
            'unresolved_events' => (clone $query)->unresolved()->count(),
            'recent_events_24h' => (clone $query)->recent(24)->count(),
            'most_common_event_types' => static::getMostCommonEventTypes(),
            'events_by_category' => static::getEventsByCategory(),
        ];
    }

    /**
     * Get most common event types for current tenant
     *
     * @param int $limit
     * @return array
     */
    protected static function getMostCommonEventTypes(int $limit = 10): array
    {
        return static::query()
            ->select('event_type', \DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get events grouped by category for current tenant
     *
     * @return array
     */
    protected static function getEventsByCategory(): array
    {
        return static::query()
            ->select('event_category', \DB::raw('COUNT(*) as count'))
            ->groupBy('event_category')
            ->pluck('count', 'event_category')
            ->toArray();
    }

    /**
     * Get threat patterns for current tenant within date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public static function getThreatPatterns(string $startDate, string $endDate): array
    {
        return static::query()
            ->inDateRange($startDate, $endDate)
            ->whereNotNull('threat_patterns')
            ->select('threat_patterns', 'event_type', 'occurred_at')
            ->get()
            ->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'patterns' => $event->threat_patterns,
                    'occurred_at' => $event->occurred_at,
                ];
            })
            ->toArray();
    }

    /**
     * Clean up old resolved events
     *
     * @param int $daysOld
     * @return int
     */
    public static function cleanupOldEvents(int $daysOld = 90): int
    {
        return static::where('resolution_status', self::STATUS_RESOLVED)
            ->where('resolved_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Generate security report for current tenant
     *
     * @param int $days
     * @return array
     */
    public static function generateSecurityReport(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $query = static::query()->where('occurred_at', '>=', $startDate);

        return [
            'period_days' => $days,
            'generated_at' => now(),
            'statistics' => static::getSecurityStats(),
            'critical_events' => $query->bySeverity(self::SEVERITY_CRITICAL)->get(),
            'top_threats' => static::getMostCommonEventTypes(),
            'unresolved_events' => $query->unresolved()->get(),
            'threat_patterns' => static::getThreatPatterns($startDate->toDateString(), now()->toDateString()),
        ];
    }
}