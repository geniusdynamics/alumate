<?php
// ABOUTME: Eloquent model for super_admin_analytics table in hybrid tenancy architecture
// ABOUTME: Manages cross-tenant analytics and reporting for super administrators

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SuperAdminAnalytics extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'super_admin_analytics';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'metric_type',
        'metric_name',
        'metric_value',
        'metric_data',
        'aggregation_period',
        'period_start',
        'period_end',
        'calculated_at',
        'metadata',
        'tags',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metric_value' => 'decimal:2',
        'metric_data' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'calculated_at' => 'datetime',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Available metric types.
     */
    public const METRIC_TYPES = [
        'user_metrics' => 'User Metrics',
        'enrollment_metrics' => 'Enrollment Metrics',
        'course_metrics' => 'Course Metrics',
        'financial_metrics' => 'Financial Metrics',
        'engagement_metrics' => 'Engagement Metrics',
        'performance_metrics' => 'Performance Metrics',
        'system_metrics' => 'System Metrics',
        'tenant_metrics' => 'Tenant Metrics',
        'security_metrics' => 'Security Metrics',
        'compliance_metrics' => 'Compliance Metrics',
    ];

    /**
     * Available aggregation periods.
     */
    public const AGGREGATION_PERIODS = [
        'hourly' => 'Hourly',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
        'real_time' => 'Real Time',
        'custom' => 'Custom',
    ];

    /**
     * Predefined metric names by type.
     */
    public const METRIC_NAMES = [
        'user_metrics' => [
            'total_users',
            'active_users',
            'new_registrations',
            'user_retention_rate',
            'multi_tenant_users',
            'user_login_frequency',
            'user_session_duration',
            'inactive_users',
            'user_growth_rate',
            'user_churn_rate',
        ],
        'enrollment_metrics' => [
            'total_enrollments',
            'new_enrollments',
            'enrollment_completion_rate',
            'enrollment_dropout_rate',
            'average_enrollment_duration',
            'enrollments_per_course',
            'enrollments_per_user',
            'cross_tenant_enrollments',
            'enrollment_trends',
            'enrollment_satisfaction',
        ],
        'course_metrics' => [
            'total_courses',
            'active_courses',
            'course_completion_rate',
            'average_course_rating',
            'course_popularity',
            'course_creation_rate',
            'global_vs_tenant_courses',
            'course_utilization',
            'course_revenue',
            'course_effectiveness',
        ],
        'financial_metrics' => [
            'total_revenue',
            'revenue_per_tenant',
            'revenue_per_user',
            'revenue_per_course',
            'payment_success_rate',
            'refund_rate',
            'average_transaction_value',
            'monthly_recurring_revenue',
            'customer_lifetime_value',
            'cost_per_acquisition',
        ],
        'engagement_metrics' => [
            'daily_active_users',
            'weekly_active_users',
            'monthly_active_users',
            'session_frequency',
            'page_views',
            'feature_usage',
            'content_interaction',
            'social_engagement',
            'mobile_vs_desktop',
            'peak_usage_times',
        ],
        'performance_metrics' => [
            'system_uptime',
            'response_time',
            'error_rate',
            'database_performance',
            'api_performance',
            'cache_hit_rate',
            'resource_utilization',
            'scalability_metrics',
            'load_balancing_efficiency',
            'cdn_performance',
        ],
        'system_metrics' => [
            'total_tenants',
            'active_tenants',
            'storage_usage',
            'bandwidth_usage',
            'api_calls',
            'background_jobs',
            'email_delivery',
            'file_uploads',
            'search_queries',
            'export_requests',
        ],
        'tenant_metrics' => [
            'tenant_growth_rate',
            'tenant_churn_rate',
            'tenant_health_score',
            'tenant_activity_level',
            'tenant_feature_adoption',
            'tenant_support_tickets',
            'tenant_satisfaction',
            'tenant_revenue_contribution',
            'tenant_user_growth',
            'tenant_data_usage',
        ],
        'security_metrics' => [
            'failed_login_attempts',
            'security_incidents',
            'password_strength',
            'two_factor_adoption',
            'suspicious_activities',
            'data_breach_attempts',
            'access_violations',
            'privilege_escalations',
            'audit_compliance',
            'vulnerability_assessments',
        ],
        'compliance_metrics' => [
            'gdpr_compliance',
            'data_retention_compliance',
            'audit_trail_completeness',
            'consent_management',
            'data_export_requests',
            'data_deletion_requests',
            'privacy_policy_acceptance',
            'regulatory_reporting',
            'compliance_violations',
            'certification_status',
        ],
    ];

    /**
     * Get the tenant associated with this metric.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the metric type display name.
     */
    public function getMetricTypeDisplayNameAttribute(): string
    {
        return self::METRIC_TYPES[$this->metric_type] ?? ucfirst(str_replace('_', ' ', $this->metric_type));
    }

    /**
     * Get the aggregation period display name.
     */
    public function getAggregationPeriodDisplayNameAttribute(): string
    {
        return self::AGGREGATION_PERIODS[$this->aggregation_period] ?? ucfirst(str_replace('_', ' ', $this->aggregation_period));
    }

    /**
     * Get formatted metric value.
     */
    public function getFormattedMetricValueAttribute(): string
    {
        if (is_null($this->metric_value)) {
            return 'N/A';
        }

        // Format based on metric type
        if (str_contains($this->metric_name, 'rate') || str_contains($this->metric_name, 'percentage')) {
            return number_format($this->metric_value, 2) . '%';
        }

        if (str_contains($this->metric_name, 'revenue') || str_contains($this->metric_name, 'value')) {
            return '$' . number_format($this->metric_value, 2);
        }

        if (str_contains($this->metric_name, 'time') || str_contains($this->metric_name, 'duration')) {
            return $this->formatDuration($this->metric_value);
        }

        return number_format($this->metric_value);
    }

    /**
     * Format duration in seconds to human readable format.
     */
    protected function formatDuration(float $seconds): string
    {
        if ($seconds < 60) {
            return round($seconds, 2) . 's';
        }

        if ($seconds < 3600) {
            return round($seconds / 60, 2) . 'm';
        }

        if ($seconds < 86400) {
            return round($seconds / 3600, 2) . 'h';
        }

        return round($seconds / 86400, 2) . 'd';
    }

    /**
     * Get trend data from metric_data.
     */
    public function getTrendDataAttribute(): array
    {
        return $this->metric_data['trend'] ?? [];
    }

    /**
     * Get comparison data from metric_data.
     */
    public function getComparisonDataAttribute(): array
    {
        return $this->metric_data['comparison'] ?? [];
    }

    /**
     * Get breakdown data from metric_data.
     */
    public function getBreakdownDataAttribute(): array
    {
        return $this->metric_data['breakdown'] ?? [];
    }

    /**
     * Check if metric has improved compared to previous period.
     */
    public function hasImproved(): bool
    {
        $comparison = $this->comparison_data;
        
        if (!isset($comparison['previous_value']) || !isset($comparison['change_percentage'])) {
            return false;
        }

        // For metrics where higher is better
        $higherIsBetter = [
            'total_users', 'active_users', 'new_registrations', 'user_retention_rate',
            'total_enrollments', 'enrollment_completion_rate', 'total_revenue',
            'course_completion_rate', 'average_course_rating', 'system_uptime',
            'cache_hit_rate', 'tenant_satisfaction', 'two_factor_adoption'
        ];

        // For metrics where lower is better
        $lowerIsBetter = [
            'user_churn_rate', 'enrollment_dropout_rate', 'refund_rate',
            'error_rate', 'response_time', 'failed_login_attempts',
            'security_incidents', 'tenant_churn_rate'
        ];

        $changePercentage = $comparison['change_percentage'];

        if (in_array($this->metric_name, $higherIsBetter)) {
            return $changePercentage > 0;
        }

        if (in_array($this->metric_name, $lowerIsBetter)) {
            return $changePercentage < 0;
        }

        // Default: assume higher is better
        return $changePercentage > 0;
    }

    /**
     * Get the health status of this metric.
     */
    public function getHealthStatusAttribute(): string
    {
        $value = $this->metric_value;
        
        if (is_null($value)) {
            return 'unknown';
        }

        // Define thresholds for different metrics
        $thresholds = [
            'user_retention_rate' => ['excellent' => 90, 'good' => 70, 'warning' => 50],
            'enrollment_completion_rate' => ['excellent' => 85, 'good' => 70, 'warning' => 50],
            'course_completion_rate' => ['excellent' => 80, 'good' => 65, 'warning' => 45],
            'system_uptime' => ['excellent' => 99.9, 'good' => 99.5, 'warning' => 99.0],
            'payment_success_rate' => ['excellent' => 98, 'good' => 95, 'warning' => 90],
            'user_churn_rate' => ['excellent' => 5, 'good' => 10, 'warning' => 20], // Lower is better
            'error_rate' => ['excellent' => 0.1, 'good' => 0.5, 'warning' => 1.0], // Lower is better
        ];

        if (!isset($thresholds[$this->metric_name])) {
            return 'unknown';
        }

        $threshold = $thresholds[$this->metric_name];
        $isLowerBetter = in_array($this->metric_name, ['user_churn_rate', 'error_rate']);

        if ($isLowerBetter) {
            if ($value <= $threshold['excellent']) return 'excellent';
            if ($value <= $threshold['good']) return 'good';
            if ($value <= $threshold['warning']) return 'warning';
            return 'critical';
        } else {
            if ($value >= $threshold['excellent']) return 'excellent';
            if ($value >= $threshold['good']) return 'good';
            if ($value >= $threshold['warning']) return 'warning';
            return 'critical';
        }
    }

    /**
     * Scope to filter by metric type.
     */
    public function scopeMetricType($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    /**
     * Scope to filter by metric name.
     */
    public function scopeMetricName($query, string $metricName)
    {
        return $query->where('metric_name', $metricName);
    }

    /**
     * Scope to filter by tenant.
     */
    public function scopeByTenant($query, string $tenantId = null)
    {
        if ($tenantId) {
            return $query->where('tenant_id', $tenantId);
        }
        return $query;
    }

    /**
     * Scope to filter global metrics (no tenant).
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('tenant_id');
    }

    /**
     * Scope to filter by aggregation period.
     */
    public function scopeAggregationPeriod($query, string $period)
    {
        return $query->where('aggregation_period', $period);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, Carbon $startDate = null, Carbon $endDate = null)
    {
        if ($startDate) {
            $query->where('period_start', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('period_end', '<=', $endDate);
        }
        return $query;
    }

    /**
     * Scope to filter active metrics.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get latest metrics.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('calculated_at', 'desc');
    }

    /**
     * Scope to get metrics with specific tags.
     */
    public function scopeWithTags($query, array $tags)
    {
        return $query->where(function ($q) use ($tags) {
            foreach ($tags as $tag) {
                $q->orWhereJsonContains('tags', $tag);
            }
        });
    }

    /**
     * Calculate user metrics for a tenant or globally.
     */
    public static function calculateUserMetrics(string $tenantId = null, string $period = 'daily'): array
    {
        $metrics = [];
        $periodStart = self::getPeriodStart($period);
        $periodEnd = self::getPeriodEnd($period);

        // Base query for users
        $userQuery = $tenantId ? 
            DB::table('users')->where('tenant_id', $tenantId) :
            DB::table('global_users');

        // Total users
        $totalUsers = $userQuery->count();
        $metrics['total_users'] = $totalUsers;

        // Active users (logged in within the period)
        $activeUsers = $userQuery
            ->where('last_login_at', '>=', $periodStart)
            ->count();
        $metrics['active_users'] = $activeUsers;

        // New registrations
        $newRegistrations = $userQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();
        $metrics['new_registrations'] = $newRegistrations;

        // Multi-tenant users (only for global metrics)
        if (!$tenantId) {
            $multiTenantUsers = DB::table('user_tenant_memberships')
                ->select('global_user_id')
                ->groupBy('global_user_id')
                ->havingRaw('COUNT(DISTINCT tenant_id) > 1')
                ->count();
            $metrics['multi_tenant_users'] = $multiTenantUsers;
        }

        // User retention rate (users who were active in both current and previous period)
        $previousPeriodStart = self::getPreviousPeriodStart($period);
        $previousPeriodEnd = $periodStart;
        
        $currentActiveUsers = $userQuery
            ->where('last_login_at', '>=', $periodStart)
            ->pluck('id');
        
        $previousActiveUsers = $userQuery
            ->whereBetween('last_login_at', [$previousPeriodStart, $previousPeriodEnd])
            ->pluck('id');
        
        $retainedUsers = $currentActiveUsers->intersect($previousActiveUsers)->count();
        $retentionRate = $previousActiveUsers->count() > 0 ? 
            ($retainedUsers / $previousActiveUsers->count()) * 100 : 0;
        $metrics['user_retention_rate'] = $retentionRate;

        return $metrics;
    }

    /**
     * Calculate enrollment metrics for a tenant or globally.
     */
    public static function calculateEnrollmentMetrics(string $tenantId = null, string $period = 'daily'): array
    {
        $metrics = [];
        $periodStart = self::getPeriodStart($period);
        $periodEnd = self::getPeriodEnd($period);

        // Base query for enrollments
        $enrollmentQuery = $tenantId ? 
            DB::table('enrollments')->where('tenant_id', $tenantId) :
            DB::table('enrollments');

        // Total enrollments
        $totalEnrollments = $enrollmentQuery->count();
        $metrics['total_enrollments'] = $totalEnrollments;

        // New enrollments in period
        $newEnrollments = $enrollmentQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();
        $metrics['new_enrollments'] = $newEnrollments;

        // Enrollment completion rate
        $completedEnrollments = $enrollmentQuery
            ->where('status', 'completed')
            ->count();
        $completionRate = $totalEnrollments > 0 ? 
            ($completedEnrollments / $totalEnrollments) * 100 : 0;
        $metrics['enrollment_completion_rate'] = $completionRate;

        // Enrollment dropout rate
        $droppedEnrollments = $enrollmentQuery
            ->where('status', 'dropped')
            ->count();
        $dropoutRate = $totalEnrollments > 0 ? 
            ($droppedEnrollments / $totalEnrollments) * 100 : 0;
        $metrics['enrollment_dropout_rate'] = $dropoutRate;

        // Cross-tenant enrollments (only for global metrics)
        if (!$tenantId) {
            $crossTenantEnrollments = DB::table('enrollments as e1')
                ->join('enrollments as e2', 'e1.user_id', '=', 'e2.user_id')
                ->where('e1.tenant_id', '!=', 'e2.tenant_id')
                ->distinct('e1.user_id')
                ->count();
            $metrics['cross_tenant_enrollments'] = $crossTenantEnrollments;
        }

        return $metrics;
    }

    /**
     * Calculate financial metrics for a tenant or globally.
     */
    public static function calculateFinancialMetrics(string $tenantId = null, string $period = 'daily'): array
    {
        $metrics = [];
        $periodStart = self::getPeriodStart($period);
        $periodEnd = self::getPeriodEnd($period);

        // Base query for payments
        $paymentQuery = $tenantId ? 
            DB::table('payments')->where('tenant_id', $tenantId) :
            DB::table('payments');

        // Total revenue in period
        $totalRevenue = $paymentQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->sum('amount');
        $metrics['total_revenue'] = $totalRevenue;

        // Payment success rate
        $totalPayments = $paymentQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();
        $successfulPayments = $paymentQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->count();
        $successRate = $totalPayments > 0 ? 
            ($successfulPayments / $totalPayments) * 100 : 0;
        $metrics['payment_success_rate'] = $successRate;

        // Refund rate
        $refunds = $paymentQuery
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('status', 'refunded')
            ->count();
        $refundRate = $successfulPayments > 0 ? 
            ($refunds / $successfulPayments) * 100 : 0;
        $metrics['refund_rate'] = $refundRate;

        // Average transaction value
        $avgTransactionValue = $successfulPayments > 0 ? 
            $totalRevenue / $successfulPayments : 0;
        $metrics['average_transaction_value'] = $avgTransactionValue;

        return $metrics;
    }

    /**
     * Store calculated metrics.
     */
    public static function storeMetrics(
        array $metrics,
        string $metricType,
        string $tenantId = null,
        string $period = 'daily',
        array $metadata = []
    ): void {
        $periodStart = self::getPeriodStart($period);
        $periodEnd = self::getPeriodEnd($period);
        $calculatedAt = now();

        foreach ($metrics as $metricName => $metricValue) {
            // Check if metric already exists for this period
            $existing = self::where('tenant_id', $tenantId)
                ->where('metric_type', $metricType)
                ->where('metric_name', $metricName)
                ->where('aggregation_period', $period)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->first();

            if ($existing) {
                // Update existing metric
                $existing->update([
                    'metric_value' => $metricValue,
                    'calculated_at' => $calculatedAt,
                    'metadata' => array_merge($existing->metadata ?? [], $metadata),
                ]);
            } else {
                // Create new metric
                self::create([
                    'tenant_id' => $tenantId,
                    'metric_type' => $metricType,
                    'metric_name' => $metricName,
                    'metric_value' => $metricValue,
                    'aggregation_period' => $period,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'calculated_at' => $calculatedAt,
                    'metadata' => $metadata,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Get dashboard data for super admin.
     */
    public static function getDashboardData(string $period = 'daily'): array
    {
        $data = [];

        // Key metrics overview
        $keyMetrics = self::active()
            ->aggregationPeriod($period)
            ->whereIn('metric_name', [
                'total_users', 'active_users', 'total_enrollments', 
                'total_revenue', 'total_tenants', 'system_uptime'
            ])
            ->latest()
            ->limit(100)
            ->get()
            ->groupBy('metric_name');

        $data['key_metrics'] = $keyMetrics;

        // Tenant performance comparison
        $tenantMetrics = self::active()
            ->aggregationPeriod($period)
            ->whereNotNull('tenant_id')
            ->whereIn('metric_name', ['total_users', 'total_revenue', 'active_users'])
            ->with('tenant')
            ->latest()
            ->get()
            ->groupBy(['tenant_id', 'metric_name']);

        $data['tenant_performance'] = $tenantMetrics;

        // Trends (last 30 days)
        $trends = self::active()
            ->where('period_start', '>=', now()->subDays(30))
            ->whereIn('metric_name', ['total_users', 'total_revenue', 'active_users'])
            ->orderBy('period_start')
            ->get()
            ->groupBy('metric_name');

        $data['trends'] = $trends;

        // Health status summary
        $healthMetrics = self::active()
            ->aggregationPeriod($period)
            ->whereIn('metric_name', [
                'user_retention_rate', 'enrollment_completion_rate',
                'system_uptime', 'payment_success_rate'
            ])
            ->latest()
            ->get();

        $healthSummary = [];
        foreach ($healthMetrics as $metric) {
            $status = $metric->health_status;
            $healthSummary[$status] = ($healthSummary[$status] ?? 0) + 1;
        }

        $data['health_summary'] = $healthSummary;

        return $data;
    }

    /**
     * Get period start date.
     */
    protected static function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'hourly' => now()->startOfHour(),
            'daily' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            'quarterly' => now()->startOfQuarter(),
            'yearly' => now()->startOfYear(),
            default => now()->startOfDay(),
        };
    }

    /**
     * Get period end date.
     */
    protected static function getPeriodEnd(string $period): Carbon
    {
        return match ($period) {
            'hourly' => now()->endOfHour(),
            'daily' => now()->endOfDay(),
            'weekly' => now()->endOfWeek(),
            'monthly' => now()->endOfMonth(),
            'quarterly' => now()->endOfQuarter(),
            'yearly' => now()->endOfYear(),
            default => now()->endOfDay(),
        };
    }

    /**
     * Get previous period start date.
     */
    protected static function getPreviousPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'hourly' => now()->subHour()->startOfHour(),
            'daily' => now()->subDay()->startOfDay(),
            'weekly' => now()->subWeek()->startOfWeek(),
            'monthly' => now()->subMonth()->startOfMonth(),
            'quarterly' => now()->subQuarter()->startOfQuarter(),
            'yearly' => now()->subYear()->startOfYear(),
            default => now()->subDay()->startOfDay(),
        };
    }

    /**
     * Generate comprehensive analytics report.
     */
    public static function generateReport(
        string $tenantId = null,
        string $period = 'monthly',
        array $metricTypes = []
    ): array {
        if (empty($metricTypes)) {
            $metricTypes = array_keys(self::METRIC_TYPES);
        }

        $report = [
            'generated_at' => now(),
            'tenant_id' => $tenantId,
            'period' => $period,
            'metrics' => [],
        ];

        foreach ($metricTypes as $metricType) {
            $metrics = self::active()
                ->metricType($metricType)
                ->byTenant($tenantId)
                ->aggregationPeriod($period)
                ->latest()
                ->get();

            $report['metrics'][$metricType] = $metrics->toArray();
        }

        return $report;