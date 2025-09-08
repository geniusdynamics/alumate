<?php

namespace App\Services;

use App\Models\EmailAnalytics;
use App\Models\Tenant;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Email Analytics Service
 *
 * Core service for email performance tracking, analytics, and reporting.
 * Handles email metrics, funnel analysis, A/B testing, and attribution tracking.
 */
class EmailAnalyticsService
{
    const CACHE_PREFIX = 'email_analytics_';
    const CACHE_DURATION = 1800; // 30 minutes

    protected TenantContextService $tenantContext;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
    }

    /**
     * Track email delivery event
     */
    public function trackDelivery(int $emailAnalyticsId, array $metadata = []): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordDelivery();
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email open event
     */
    public function trackOpen(int $emailAnalyticsId, array $metadata = []): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordOpen($metadata);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email click event
     */
    public function trackClick(int $emailAnalyticsId, string $url, array $metadata = []): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordClick($url, $metadata);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email conversion event
     */
    public function trackConversion(int $emailAnalyticsId, string $type, float $value = 0.00, array $metadata = []): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordConversion($type, $value, $metadata);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email bounce event
     */
    public function trackBounce(int $emailAnalyticsId, string $reason): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordBounce($reason);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email complaint event
     */
    public function trackComplaint(int $emailAnalyticsId, string $reason): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordComplaint($reason);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Track email unsubscribe event
     */
    public function trackUnsubscribe(int $emailAnalyticsId): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        $analytics->recordUnsubscribe();
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Get email performance metrics
     */
    public function getEmailPerformanceMetrics(array $filters = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . 'performance_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailAnalytics::query();

            // Apply date filters
            if (isset($filters['start_date'])) {
                $query->where('send_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('send_date', '<=', $filters['end_date']);
            }

            // Apply campaign filter
            if (isset($filters['campaign_id'])) {
                $query->where('email_campaign_id', $filters['campaign_id']);
            }

            // Apply template filter
            if (isset($filters['template_id'])) {
                $query->where('email_template_id', $filters['template_id']);
            }

            $totalSent = (clone $query)->count();
            $totalDelivered = (clone $query)->whereNotNull('delivered_at')->count();
            $totalOpened = (clone $query)->whereNotNull('opened_at')->count();
            $totalClicked = (clone $query)->whereNotNull('clicked_at')->count();
            $totalConverted = (clone $query)->whereNotNull('converted_at')->count();
            $totalBounced = (clone $query)->whereNotNull('bounced_at')->count();
            $totalComplaints = (clone $query)->whereNotNull('complained_at')->count();
            $totalUnsubscribes = (clone $query)->whereNotNull('unsubscribed_at')->count();

            return [
                'total_sent' => $totalSent,
                'total_delivered' => $totalDelivered,
                'total_opened' => $totalOpened,
                'total_clicked' => $totalClicked,
                'total_converted' => $totalConverted,
                'total_bounced' => $totalBounced,
                'total_complaints' => $totalComplaints,
                'total_unsubscribes' => $totalUnsubscribes,
                'delivery_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0,
                'open_rate' => $totalDelivered > 0 ? round(($totalOpened / $totalDelivered) * 100, 2) : 0,
                'click_rate' => $totalOpened > 0 ? round(($totalClicked / $totalOpened) * 100, 2) : 0,
                'conversion_rate' => $totalClicked > 0 ? round(($totalConverted / $totalClicked) * 100, 2) : 0,
                'bounce_rate' => $totalSent > 0 ? round(($totalBounced / $totalSent) * 100, 2) : 0,
                'complaint_rate' => $totalSent > 0 ? round(($totalComplaints / $totalSent) * 100, 2) : 0,
                'unsubscribe_rate' => $totalSent > 0 ? round(($totalUnsubscribes / $totalSent) * 100, 2) : 0,
            ];
        });
    }

    /**
     * Get funnel analytics from email open to final conversion
     */
    public function getFunnelAnalytics(array $filters = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . 'funnel_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailAnalytics::query();

            // Apply filters
            if (isset($filters['start_date'])) {
                $query->where('send_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('send_date', '<=', $filters['end_date']);
            }
            if (isset($filters['campaign_id'])) {
                $query->where('email_campaign_id', $filters['campaign_id']);
            }

            $funnel = [
                'sent' => (clone $query)->count(),
                'delivered' => (clone $query)->whereNotNull('delivered_at')->count(),
                'opened' => (clone $query)->whereNotNull('opened_at')->count(),
                'clicked' => (clone $query)->whereNotNull('clicked_at')->count(),
                'converted' => (clone $query)->whereNotNull('converted_at')->count(),
            ];

            // Calculate drop-off rates
            $funnel['delivered_rate'] = $funnel['sent'] > 0 ? round(($funnel['delivered'] / $funnel['sent']) * 100, 2) : 0;
            $funnel['opened_rate'] = $funnel['delivered'] > 0 ? round(($funnel['opened'] / $funnel['delivered']) * 100, 2) : 0;
            $funnel['clicked_rate'] = $funnel['opened'] > 0 ? round(($funnel['clicked'] / $funnel['opened']) * 100, 2) : 0;
            $funnel['converted_rate'] = $funnel['clicked'] > 0 ? round(($funnel['converted'] / $funnel['clicked']) * 100, 2) : 0;

            // Calculate average time between stages
            $funnel['avg_time_to_open'] = $this->calculateAverageTimeToStage($query, 'opened_at', 'delivered_at');
            $funnel['avg_time_to_click'] = $this->calculateAverageTimeToStage($query, 'clicked_at', 'opened_at');
            $funnel['avg_time_to_convert'] = $this->calculateAverageTimeToStage($query, 'converted_at', 'clicked_at');

            return $funnel;
        });
    }

    /**
     * Generate engagement reports and trend analysis
     */
    public function generateEngagementReport(array $filters = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . 'engagement_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailAnalytics::query();

            // Apply filters
            if (isset($filters['start_date'])) {
                $query->where('send_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('send_date', '<=', $filters['end_date']);
            }

            // Daily engagement trends
            $dailyTrends = $query->select(
                DB::raw('DATE(send_date) as date'),
                DB::raw('COUNT(*) as sent'),
                DB::raw('COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as opened'),
                DB::raw('COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as clicked'),
                DB::raw('COUNT(CASE WHEN converted_at IS NOT NULL THEN 1 END) as converted')
            )
            ->groupBy(DB::raw('DATE(send_date)'))
            ->orderBy('date')
            ->get();

            // Device breakdown
            $deviceBreakdown = $query->whereNotNull('device_type')
                ->select('device_type', DB::raw('COUNT(*) as count'))
                ->groupBy('device_type')
                ->get();

            // Browser breakdown
            $browserBreakdown = $query->whereNotNull('browser')
                ->select('browser', DB::raw('COUNT(*) as count'))
                ->groupBy('browser')
                ->get();

            // Geographic distribution
            $geographicData = $query->whereNotNull('location')
                ->select('location', DB::raw('COUNT(*) as count'))
                ->groupBy('location')
                ->orderByDesc('count')
                ->limit(20)
                ->get();

            return [
                'daily_trends' => $dailyTrends,
                'device_breakdown' => $deviceBreakdown,
                'browser_breakdown' => $browserBreakdown,
                'geographic_distribution' => $geographicData,
                'engagement_score' => $this->calculateOverallEngagementScore($query),
                'generated_at' => now(),
            ];
        });
    }

    /**
     * Get A/B testing results for email variants
     */
    public function getABTestResults(array $filters = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . 'ab_test_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($filters) {
            $query = EmailAnalytics::whereNotNull('ab_test_variant');

            // Apply filters
            if (isset($filters['start_date'])) {
                $query->where('send_date', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $query->where('send_date', '<=', $filters['end_date']);
            }
            if (isset($filters['campaign_id'])) {
                $query->where('email_campaign_id', $filters['campaign_id']);
            }

            $variants = $query->select(
                'ab_test_variant',
                DB::raw('COUNT(*) as sent'),
                DB::raw('COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as opened'),
                DB::raw('COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as clicked'),
                DB::raw('COUNT(CASE WHEN converted_at IS NOT NULL THEN 1 END) as converted'),
                DB::raw('SUM(conversion_value) as total_value')
            )
            ->groupBy('ab_test_variant')
            ->get();

            $results = [];
            foreach ($variants as $variant) {
                $results[$variant->ab_test_variant] = [
                    'sent' => $variant->sent,
                    'opened' => $variant->opened,
                    'clicked' => $variant->clicked,
                    'converted' => $variant->converted,
                    'total_value' => $variant->total_value ?? 0,
                    'open_rate' => $variant->sent > 0 ? round(($variant->opened / $variant->sent) * 100, 2) : 0,
                    'click_rate' => $variant->opened > 0 ? round(($variant->clicked / $variant->opened) * 100, 2) : 0,
                    'conversion_rate' => $variant->clicked > 0 ? round(($variant->converted / $variant->clicked) * 100, 2) : 0,
                    'avg_conversion_value' => $variant->converted > 0 ? round($variant->total_value / $variant->converted, 2) : 0,
                ];
            }

            return [
                'variants' => $results,
                'winner' => $this->determineABTestWinner($results),
                'confidence_level' => $this->calculateABTestConfidence($results),
                'generated_at' => now(),
            ];
        });
    }

    /**
     * Handle attribution tracking from email clicks to landing page conversions
     */
    public function trackAttribution(int $emailAnalyticsId, string $conversionType, array $metadata = []): bool
    {
        $analytics = EmailAnalytics::find($emailAnalyticsId);
        if (!$analytics) {
            return false;
        }

        // Check if conversion already exists
        if ($analytics->isConverted()) {
            return false;
        }

        $analytics->recordConversion($conversionType, $metadata['value'] ?? 0.00, $metadata);
        $this->clearAnalyticsCache();

        return true;
    }

    /**
     * Get real-time analytics updates
     */
    public function getRealTimeAnalytics(int $minutes = 5): array
    {
        $since = Carbon::now()->subMinutes($minutes);

        $recentActivity = EmailAnalytics::query()
            ->where('updated_at', '>=', $since)
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get();

        $stats = [
            'opens_last_' . $minutes . '_minutes' => $recentActivity->whereNotNull('opened_at')->where('opened_at', '>=', $since)->count(),
            'clicks_last_' . $minutes . '_minutes' => $recentActivity->whereNotNull('clicked_at')->where('clicked_at', '>=', $since)->count(),
            'conversions_last_' . $minutes . '_minutes' => $recentActivity->whereNotNull('converted_at')->where('converted_at', '>=', $since)->count(),
            'bounces_last_' . $minutes . '_minutes' => $recentActivity->whereNotNull('bounced_at')->where('bounced_at', '>=', $since)->count(),
            'complaints_last_' . $minutes . '_minutes' => $recentActivity->whereNotNull('complained_at')->where('complained_at', '>=', $since)->count(),
        ];

        return [
            'stats' => $stats,
            'recent_activity' => $recentActivity->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'recipient_email' => $activity->recipient_email,
                    'status' => $activity->delivery_status,
                    'last_action' => $activity->updated_at,
                    'subject_line' => $activity->subject_line,
                ];
            }),
            'timestamp' => now(),
        ];
    }

    /**
     * Generate automated reports
     */
    public function generateAutomatedReport(string $period = 'daily', array $filters = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . 'report_' . $tenantId . '_' . $period . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($period, $filters) {
            $dateRange = $this->getDateRangeForPeriod($period);

            $filters = array_merge($filters, [
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
            ]);

            return [
                'period' => $period,
                'date_range' => $dateRange,
                'performance_metrics' => $this->getEmailPerformanceMetrics($filters),
                'funnel_analytics' => $this->getFunnelAnalytics($filters),
                'engagement_report' => $this->generateEngagementReport($filters),
                'ab_test_results' => $this->getABTestResults($filters),
                'recommendations' => $this->generateRecommendations($filters),
                'generated_at' => now(),
            ];
        });
    }

    /**
     * Calculate average time between stages
     */
    private function calculateAverageTimeToStage($query, string $stageColumn, string $previousStageColumn): ?float
    {
        $results = $query->whereNotNull($stageColumn)
            ->whereNotNull($previousStageColumn)
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, {$previousStageColumn}, {$stageColumn})) as avg_minutes")
            ->first();

        return $results ? round($results->avg_minutes, 2) : null;
    }

    /**
     * Calculate overall engagement score
     */
    private function calculateOverallEngagementScore($query): float
    {
        $stats = $query->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN opened_at IS NOT NULL THEN 1 END) as opened,
            COUNT(CASE WHEN clicked_at IS NOT NULL THEN 1 END) as clicked,
            COUNT(CASE WHEN converted_at IS NOT NULL THEN 1 END) as converted
        ')->first();

        if ($stats->total == 0) {
            return 0;
        }

        $openRate = ($stats->opened / $stats->total) * 100;
        $clickRate = $stats->opened > 0 ? ($stats->clicked / $stats->opened) * 100 : 0;
        $conversionRate = $stats->clicked > 0 ? ($stats->converted / $stats->clicked) * 100 : 0;

        // Weighted score: 40% open rate, 40% click rate, 20% conversion rate
        return round(($openRate * 0.4) + ($clickRate * 0.4) + ($conversionRate * 0.2), 2);
    }

    /**
     * Determine A/B test winner
     */
    private function determineABTestWinner(array $variants): ?string
    {
        if (count($variants) < 2) {
            return null;
        }

        $winner = null;
        $bestScore = 0;

        foreach ($variants as $variant => $data) {
            $score = ($data['open_rate'] * 0.3) + ($data['click_rate'] * 0.4) + ($data['conversion_rate'] * 0.3);
            if ($score > $bestScore) {
                $bestScore = $score;
                $winner = $variant;
            }
        }

        return $winner;
    }

    /**
     * Calculate A/B test confidence level
     */
    private function calculateABTestConfidence(array $variants): float
    {
        // Simplified confidence calculation
        // In production, use statistical significance testing
        if (count($variants) < 2) {
            return 0;
        }

        $totalSent = array_sum(array_column($variants, 'sent'));
        return $totalSent > 1000 ? 95.0 : ($totalSent > 100 ? 80.0 : 50.0);
    }

    /**
     * Get date range for reporting period
     */
    private function getDateRangeForPeriod(string $period): array
    {
        $end = Carbon::now();

        return match ($period) {
            'hourly' => [
                'start' => $end->copy()->subHour(),
                'end' => $end,
            ],
            'daily' => [
                'start' => $end->copy()->subDay(),
                'end' => $end,
            ],
            'weekly' => [
                'start' => $end->copy()->subWeek(),
                'end' => $end,
            ],
            'monthly' => [
                'start' => $end->copy()->subMonth(),
                'end' => $end,
            ],
            default => [
                'start' => $end->copy()->subDay(),
                'end' => $end,
            ],
        };
    }

    /**
     * Generate recommendations based on analytics data
     */
    private function generateRecommendations(array $filters): array
    {
        $metrics = $this->getEmailPerformanceMetrics($filters);
        $recommendations = [];

        if ($metrics['open_rate'] < 20) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Open rate is below average. Consider improving subject lines and sender reputation.',
                'priority' => 'high',
            ];
        }

        if ($metrics['click_rate'] < 2) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'Click rate is low. Review call-to-action buttons and link relevance.',
                'priority' => 'high',
            ];
        }

        if ($metrics['bounce_rate'] > 5) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Bounce rate is high. Clean your email list and verify addresses.',
                'priority' => 'critical',
            ];
        }

        if ($metrics['complaint_rate'] > 0.1) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Complaint rate is elevated. Review content and sending practices.',
                'priority' => 'critical',
            ];
        }

        return $recommendations;
    }

    /**
     * Clear analytics cache for current tenant
     */
    private function clearAnalyticsCache(): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKeys = [
            self::CACHE_PREFIX . 'performance_' . $tenantId,
            self::CACHE_PREFIX . 'funnel_' . $tenantId,
            self::CACHE_PREFIX . 'engagement_' . $tenantId,
            self::CACHE_PREFIX . 'ab_test_' . $tenantId,
            self::CACHE_PREFIX . 'report_' . $tenantId,
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}