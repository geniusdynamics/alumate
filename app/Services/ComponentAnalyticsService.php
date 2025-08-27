<?php

namespace App\Services;

use App\Models\ComponentAnalytic;
use App\Models\ComponentInstance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComponentAnalyticsService
{
    /**
     * Cache duration for analytics data (in minutes)
     */
    protected const CACHE_DURATION = 30;

    /**
     * Privacy compliance settings
     */
    protected const DATA_RETENTION_DAYS = 365;
    protected const ANONYMIZE_AFTER_DAYS = 90;

    /**
     * A/B testing configuration
     */
    protected const DEFAULT_VARIANTS = ['A', 'B'];
    protected const MINIMUM_SAMPLE_SIZE = 100;

    /**
     * Record a component view event
     */
    public function recordView(
        int $componentInstanceId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $additionalData = []
    ): ComponentAnalytic {
        $data = array_merge([
            'timestamp' => now()->toISOString(),
            'user_agent' => $this->getRequestData('userAgent'),
            'ip_address' => $this->getAnonymizedIp($this->getRequestData('ip')),
            'referrer' => $this->getRequestData('referer'),
            'viewport_width' => $additionalData['viewport_width'] ?? null,
            'viewport_height' => $additionalData['viewport_height'] ?? null,
            'scroll_depth' => $additionalData['scroll_depth'] ?? 0,
        ], $additionalData);

        return ComponentAnalytic::recordView($componentInstanceId, $userId, $sessionId, $data);
    }

    /**
     * Record a component click/interaction event
     */
    public function recordClick(
        int $componentInstanceId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $additionalData = []
    ): ComponentAnalytic {
        $data = array_merge([
            'timestamp' => now()->toISOString(),
            'user_agent' => $this->getRequestData('userAgent'),
            'ip_address' => $this->getAnonymizedIp($this->getRequestData('ip')),
            'element_id' => $additionalData['element_id'] ?? null,
            'element_class' => $additionalData['element_class'] ?? null,
            'click_x' => $additionalData['click_x'] ?? null,
            'click_y' => $additionalData['click_y'] ?? null,
        ], $additionalData);

        return ComponentAnalytic::recordClick($componentInstanceId, $userId, $sessionId, $data);
    }

    /**
     * Record a conversion event
     */
    public function recordConversion(
        int $componentInstanceId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $additionalData = []
    ): ComponentAnalytic {
        $data = array_merge([
            'timestamp' => now()->toISOString(),
            'user_agent' => $this->getRequestData('userAgent'),
            'ip_address' => $this->getAnonymizedIp($this->getRequestData('ip')),
            'conversion_value' => $additionalData['conversion_value'] ?? null,
            'conversion_type' => $additionalData['conversion_type'] ?? 'general',
            'funnel_step' => $additionalData['funnel_step'] ?? 1,
        ], $additionalData);

        return ComponentAnalytic::recordConversion($componentInstanceId, $userId, $sessionId, $data);
    }

    /**
     * Record a form submission event
     */
    public function recordFormSubmit(
        int $componentInstanceId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $additionalData = []
    ): ComponentAnalytic {
        $data = array_merge([
            'timestamp' => now()->toISOString(),
            'user_agent' => $this->getRequestData('userAgent'),
            'ip_address' => $this->getAnonymizedIp($this->getRequestData('ip')),
            'form_id' => $additionalData['form_id'] ?? null,
            'fields_count' => $additionalData['fields_count'] ?? 0,
            'completion_time' => $additionalData['completion_time'] ?? null,
            'validation_errors' => $additionalData['validation_errors'] ?? 0,
        ], $additionalData);

        return ComponentAnalytic::recordFormSubmit($componentInstanceId, $userId, $sessionId, $data);
    }

    /**
     * Assign A/B testing variant to a user/session
     */
    public function assignVariant(
        int $componentInstanceId,
        ?int $userId = null,
        ?string $sessionId = null,
        array $variants = null
    ): string {
        $variants = $variants ?? self::DEFAULT_VARIANTS;
        
        // Use consistent assignment based on user ID or session ID
        $identifier = $userId ? "user_{$userId}" : "session_{$sessionId}";
        $hash = crc32($identifier . $componentInstanceId);
        $variantIndex = abs($hash) % count($variants);
        
        return $variants[$variantIndex];
    }

    /**
     * Record A/B testing variant event
     */
    public function recordVariantEvent(
        int $componentInstanceId,
        string $eventType,
        string $variant,
        ?int $userId = null,
        ?string $sessionId = null,
        array $additionalData = []
    ): ComponentAnalytic {
        $data = array_merge($additionalData, [
            'variant' => $variant,
            'test_id' => $additionalData['test_id'] ?? "test_{$componentInstanceId}",
            'timestamp' => now()->toISOString(),
        ]);

        return ComponentAnalytic::recordVariantEvent(
            $componentInstanceId,
            $eventType,
            $variant,
            $userId,
            $sessionId,
            $data
        );
    }

    /**
     * Get comprehensive analytics data for a component instance
     */
    public function getComponentAnalytics(
        int $componentInstanceId,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $cacheKey = "component_analytics_{$componentInstanceId}_" . 
                   ($startDate ? $startDate->format('Y-m-d') : 'all') . '_' .
                   ($endDate ? $endDate->format('Y-m-d') : 'all');

        if (!app()->bound('cache')) {
            return $this->calculateAnalyticsData($componentInstanceId, $startDate, $endDate);
        }

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($componentInstanceId, $startDate, $endDate) {
            return $this->calculateAnalyticsData($componentInstanceId, $startDate, $endDate);
        });
    }

    /**
     * Calculate analytics data without caching
     */
    protected function calculateAnalyticsData(int $componentInstanceId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = ComponentAnalytic::where('component_instance_id', $componentInstanceId);

        if ($startDate && $endDate) {
            $query->forDateRange($startDate, $endDate);
        }

        $analytics = $query->get();

        return [
            'summary' => $this->calculateSummaryMetrics($analytics),
            'event_counts' => $this->calculateEventCounts($analytics),
            'conversion_metrics' => $this->calculateConversionMetrics($analytics),
            'engagement_metrics' => $this->calculateEngagementMetrics($analytics),
            'variant_performance' => $this->calculateVariantPerformance($analytics),
            'time_series' => $this->calculateTimeSeriesData($analytics, $startDate, $endDate),
            'user_behavior' => $this->calculateUserBehaviorMetrics($analytics),
        ];
    }

    /**
     * Get A/B testing performance data
     */
    public function getVariantPerformance(int $componentInstanceId): Collection
    {
        $cacheKey = "variant_performance_{$componentInstanceId}";

        if (!app()->bound('cache')) {
            return ComponentAnalytic::getVariantPerformance($componentInstanceId);
        }

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($componentInstanceId) {
            return ComponentAnalytic::getVariantPerformance($componentInstanceId);
        });
    }

    /**
     * Get the best performing variant for A/B testing
     */
    public function getBestPerformingVariant(int $componentInstanceId, string $metric = 'conversion_rate'): ?array
    {
        return ComponentAnalytic::getBestPerformingVariant($componentInstanceId, $metric);
    }

    /**
     * Calculate conversion funnel analysis
     */
    public function getConversionFunnel(
        int $componentInstanceId,
        array $funnelSteps = ['view', 'click', 'conversion']
    ): array {
        $cacheKey = "conversion_funnel_{$componentInstanceId}_" . md5(serialize($funnelSteps));

        if (!app()->bound('cache')) {
            return $this->calculateFunnelData($componentInstanceId, $funnelSteps);
        }

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($componentInstanceId, $funnelSteps) {
            return $this->calculateFunnelData($componentInstanceId, $funnelSteps);
        });
    }

    /**
     * Calculate funnel data without caching
     */
    protected function calculateFunnelData(int $componentInstanceId, array $funnelSteps): array
    {
        $funnelData = [];
        $previousCount = null;

        foreach ($funnelSteps as $index => $step) {
            $count = ComponentAnalytic::where('component_instance_id', $componentInstanceId)
                ->where('event_type', $step)
                ->count();

            $dropoffRate = $previousCount ? (($previousCount - $count) / $previousCount) * 100 : 0;
            $conversionRate = $index === 0 ? 100 : (($count / $funnelData[0]['count']) * 100);

            $funnelData[] = [
                'step' => $step,
                'count' => $count,
                'conversion_rate' => round($conversionRate, 2),
                'dropoff_rate' => round($dropoffRate, 2),
            ];

            $previousCount = $count;
        }

        return [
            'funnel_steps' => $funnelData,
            'overall_conversion_rate' => end($funnelData)['conversion_rate'],
            'total_dropoff' => $funnelData[0]['count'] - end($funnelData)['count'],
        ];
    }

    /**
     * Get real-time metrics with caching
     */
    public function getRealTimeMetrics(int $componentInstanceId): array
    {
        $cacheKey = "realtime_metrics_{$componentInstanceId}";

        if (!app()->bound('cache')) {
            return $this->calculateRealTimeData($componentInstanceId);
        }

        return Cache::remember($cacheKey, 5, function () use ($componentInstanceId) { // 5-minute cache for real-time
            return $this->calculateRealTimeData($componentInstanceId);
        });
    }

    /**
     * Calculate real-time data without caching
     */
    protected function calculateRealTimeData(int $componentInstanceId): array
    {
        $today = now()->startOfDay();
        $thisHour = now()->startOfHour();

        return [
            'views_today' => ComponentAnalytic::where('component_instance_id', $componentInstanceId)
                ->where('event_type', 'view')
                ->where('created_at', '>=', $today)
                ->count(),
            'views_this_hour' => ComponentAnalytic::where('component_instance_id', $componentInstanceId)
                ->where('event_type', 'view')
                ->where('created_at', '>=', $thisHour)
                ->count(),
            'conversions_today' => ComponentAnalytic::where('component_instance_id', $componentInstanceId)
                ->where('event_type', 'conversion')
                ->where('created_at', '>=', $today)
                ->count(),
            'active_sessions' => ComponentAnalytic::where('component_instance_id', $componentInstanceId)
                ->where('created_at', '>=', now()->subMinutes(30))
                ->distinct('session_id')
                ->count('session_id'),
            'last_updated' => now()->toISOString(),
        ];
    }

    /**
     * Generate analytics report for multiple components
     */
    public function generateReport(
        array $componentInstanceIds,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now();

        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate),
            ],
            'components' => [],
            'summary' => [
                'total_views' => 0,
                'total_clicks' => 0,
                'total_conversions' => 0,
                'average_conversion_rate' => 0,
            ],
        ];

        foreach ($componentInstanceIds as $componentInstanceId) {
            $analytics = $this->getComponentAnalytics($componentInstanceId, $startDate, $endDate);
            $report['components'][$componentInstanceId] = $analytics;

            // Add to summary
            $report['summary']['total_views'] += $analytics['event_counts']['view'] ?? 0;
            $report['summary']['total_clicks'] += $analytics['event_counts']['click'] ?? 0;
            $report['summary']['total_conversions'] += $analytics['event_counts']['conversion'] ?? 0;
        }

        // Calculate average conversion rate
        if ($report['summary']['total_views'] > 0) {
            $report['summary']['average_conversion_rate'] = 
                ($report['summary']['total_conversions'] / $report['summary']['total_views']) * 100;
        }

        return $report;
    }

    /**
     * Clean up old analytics data for privacy compliance
     */
    public function cleanupOldData(): int
    {
        $deletedCount = 0;

        // Delete data older than retention period
        $retentionDate = now()->subDays(self::DATA_RETENTION_DAYS);
        $deletedCount += ComponentAnalytic::where('created_at', '<', $retentionDate)->delete();

        // Anonymize data older than anonymization period
        $anonymizeDate = now()->subDays(self::ANONYMIZE_AFTER_DAYS);
        ComponentAnalytic::where('created_at', '<', $anonymizeDate)
            ->whereNotNull('user_id')
            ->update([
                'user_id' => null,
                'data->ip_address' => null,
                'data->user_agent' => null,
            ]);

        if (app()->bound('log')) {
            Log::info('Analytics data cleanup completed', [
                'deleted_records' => $deletedCount,
                'retention_date' => $retentionDate,
                'anonymize_date' => $anonymizeDate,
            ]);
        }

        return $deletedCount;
    }

    /**
     * Clear analytics cache
     */
    public function clearCache(?int $componentInstanceId = null): bool
    {
        if (!app()->bound('cache')) {
            return true;
        }

        if ($componentInstanceId) {
            $patterns = [
                "component_analytics_{$componentInstanceId}_*",
                "variant_performance_{$componentInstanceId}",
                "conversion_funnel_{$componentInstanceId}_*",
                "realtime_metrics_{$componentInstanceId}",
            ];

            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        } else {
            // Clear all analytics cache
            Cache::flush();
        }

        return true;
    }

    /**
     * Calculate summary metrics from analytics data
     */
    protected function calculateSummaryMetrics(Collection $analytics): array
    {
        $totalEvents = $analytics->count();
        $uniqueUsers = $analytics->whereNotNull('user_id')->unique('user_id')->count();
        $uniqueSessions = $analytics->whereNotNull('session_id')->unique('session_id')->count();

        $views = $analytics->where('event_type', 'view')->count();
        $clicks = $analytics->where('event_type', 'click')->count();
        $conversions = $analytics->where('event_type', 'conversion')->count();

        return [
            'total_events' => $totalEvents,
            'unique_users' => $uniqueUsers,
            'unique_sessions' => $uniqueSessions,
            'views' => $views,
            'clicks' => $clicks,
            'conversions' => $conversions,
            'click_through_rate' => $views > 0 ? ($clicks / $views) * 100 : 0,
            'conversion_rate' => $views > 0 ? ($conversions / $views) * 100 : 0,
        ];
    }

    /**
     * Calculate event counts by type
     */
    protected function calculateEventCounts(Collection $analytics): array
    {
        return $analytics->groupBy('event_type')
            ->map(fn($events) => $events->count())
            ->toArray();
    }

    /**
     * Calculate conversion metrics
     */
    protected function calculateConversionMetrics(Collection $analytics): array
    {
        $conversions = $analytics->where('event_type', 'conversion');
        
        $conversionsByType = $conversions->groupBy('data.conversion_type')
            ->map(fn($events) => $events->count());

        $totalValue = $conversions->sum(fn($event) => $event->data['conversion_value'] ?? 0);
        $averageValue = $conversions->count() > 0 ? $totalValue / $conversions->count() : 0;

        return [
            'total_conversions' => $conversions->count(),
            'total_value' => $totalValue,
            'average_value' => $averageValue,
            'conversions_by_type' => $conversionsByType->toArray(),
        ];
    }

    /**
     * Calculate engagement metrics
     */
    protected function calculateEngagementMetrics(Collection $analytics): array
    {
        $sessions = $analytics->whereNotNull('session_id')->groupBy('session_id');
        
        $sessionDurations = $sessions->map(function ($sessionEvents) {
            $first = $sessionEvents->min('created_at');
            $last = $sessionEvents->max('created_at');
            return Carbon::parse($last)->diffInSeconds(Carbon::parse($first));
        });

        $averageSessionDuration = $sessionDurations->average();
        $bounceRate = $sessions->filter(fn($events) => $events->count() === 1)->count() / $sessions->count() * 100;

        return [
            'average_session_duration' => $averageSessionDuration,
            'bounce_rate' => $bounceRate,
            'pages_per_session' => $sessions->map(fn($events) => $events->count())->average(),
        ];
    }

    /**
     * Calculate variant performance for A/B testing
     */
    protected function calculateVariantPerformance(Collection $analytics): array
    {
        $variantEvents = $analytics->whereNotNull('data.variant');
        
        if ($variantEvents->isEmpty()) {
            return [];
        }

        return $variantEvents->groupBy('data.variant')
            ->map(function ($events, $variant) {
                $views = $events->where('event_type', 'view')->count();
                $clicks = $events->where('event_type', 'click')->count();
                $conversions = $events->where('event_type', 'conversion')->count();

                return [
                    'variant' => $variant,
                    'views' => $views,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'click_through_rate' => $views > 0 ? ($clicks / $views) * 100 : 0,
                    'conversion_rate' => $views > 0 ? ($conversions / $views) * 100 : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Calculate time series data for charts
     */
    protected function calculateTimeSeriesData(Collection $analytics, ?Carbon $startDate, ?Carbon $endDate): array
    {
        if (!$startDate || !$endDate) {
            return [];
        }

        $period = $startDate->diffInDays($endDate);
        $groupBy = $period > 30 ? 'Y-m-d' : ($period > 7 ? 'Y-m-d' : 'Y-m-d H:00');

        return $analytics->groupBy(function ($event) use ($groupBy) {
            return Carbon::parse($event->created_at)->format($groupBy);
        })
        ->map(function ($events, $date) {
            return [
                'date' => $date,
                'views' => $events->where('event_type', 'view')->count(),
                'clicks' => $events->where('event_type', 'click')->count(),
                'conversions' => $events->where('event_type', 'conversion')->count(),
            ];
        })
        ->values()
        ->toArray();
    }

    /**
     * Calculate user behavior metrics
     */
    protected function calculateUserBehaviorMetrics(Collection $analytics): array
    {
        $userEvents = $analytics->whereNotNull('user_id')->groupBy('user_id');
        
        return [
            'returning_users' => $userEvents->filter(fn($events) => $events->count() > 1)->count(),
            'new_users' => $userEvents->filter(fn($events) => $events->count() === 1)->count(),
            'average_events_per_user' => $userEvents->map(fn($events) => $events->count())->average(),
        ];
    }

    /**
     * Anonymize IP address for privacy compliance
     */
    protected function getAnonymizedIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        // IPv4: Remove last octet
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }

        // IPv6: Remove last 64 bits
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            for ($i = 4; $i < 8; $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }

        return null;
    }

    /**
     * Safely get request data for testing compatibility
     */
    protected function getRequestData(string $type): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        try {
            $request = request();
            return match ($type) {
                'userAgent' => $request->userAgent(),
                'ip' => $request->ip(),
                'referer' => $request->header('referer'),
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }
}