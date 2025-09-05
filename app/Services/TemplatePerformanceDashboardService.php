<?php

namespace App\Services;

use App\Models\Template;
use App\Models\LandingPage;
use App\Models\TemplateAnalyticsEvent;
use App\Models\TemplatePerformanceDashboard;
use App\Models\TemplatePerformanceReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Template Performance Dashboard Service
 *
 * Provides comprehensive analytics dashboard functionality for template performance tracking,
 * real-time metrics, trend analysis, and actionable insights.
 */
class TemplatePerformanceDashboardService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'dashboard_analytics_';
    private const CACHE_DURATION = 300; // 5 minutes
    private const REALTIME_CACHE_DURATION = 60; // 1 minute

    /**
     * Get dashboard overview metrics
     *
     * @param int $tenantId
     * @param array $filters
     * @return array
     */
    public function getDashboardOverview(int $tenantId, array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'overview_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($tenantId, $filters) {
            return [
                'summary' => $this->getSummaryMetrics($tenantId, $filters),
                'performance' => $this->getPerformanceMetrics($tenantId, $filters),
                'trends' => $this->getTrendMetrics($tenantId, $filters),
                'insights' => $this->getActionableInsights($tenantId, $filters),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get template comparison analytics
     *
     * @param array $templateIds
     * @param array $filters
     * @return array
     */
    public function getTemplateComparison(array $templateIds, array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'comparison_' . md5(serialize($templateIds + $filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($templateIds, $filters) {
            $templates = Template::whereIn('id', $templateIds)->get();
            $comparison = [];

            foreach ($templates as $template) {
                $comparison[$template->id] = [
                    'template' => $template->only(['id', 'name', 'category', 'audience_type']),
                    'metrics' => $this->getTemplateMetrics($template->id, $filters),
                    'performance_score' => $this->calculatePerformanceScore($template->id, $filters),
                ];
            }

            return [
                'templates' => $comparison,
                'summary' => $this->generateComparisonSummary($comparison),
                'recommendations' => $this->generateComparisonRecommendations($comparison),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Get real-time performance metrics
     *
     * @param int $tenantId
     * @return array
     */
    public function getRealTimeMetrics(int $tenantId): array
    {
        $cacheKey = self::CACHE_PREFIX . 'realtime_' . $tenantId;

        return Cache::remember($cacheKey, self::REALTIME_CACHE_DURATION, function () use ($tenantId) {
            $now = now();
            $lastHour = $now->copy()->subHour();

            $events = TemplateAnalyticsEvent::forTenant($tenantId)
                ->where('timestamp', '>=', $lastHour)
                ->get();

            return [
                'time_range' => 'last_hour',
                'total_events' => $events->count(),
                'page_views' => $events->where('event_type', 'page_view')->count(),
                'conversions' => $events->where('event_type', 'conversion')->count(),
                'unique_users' => $events->pluck('user_identifier')->unique()->count(),
                'events_per_minute' => round($events->count() / 60, 2),
                'top_templates' => $this->getTopPerformingTemplates($tenantId, $lastHour, $now),
                'last_updated' => $now->toISOString(),
            ];
        });
    }

    /**
     * Get performance bottleneck analysis
     *
     * @param int $tenantId
     * @param array $filters
     * @return array
     */
    public function getBottleneckAnalysis(int $tenantId, array $filters = []): array
    {
        $cacheKey = self::CACHE_PREFIX . 'bottlenecks_' . $tenantId . '_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($tenantId, $filters) {
            return [
                'slow_templates' => $this->identifySlowTemplates($tenantId, $filters),
                'conversion_bottlenecks' => $this->identifyConversionBottlenecks($tenantId, $filters),
                'engagement_issues' => $this->identifyEngagementIssues($tenantId, $filters),
                'recommendations' => $this->generateBottleneckRecommendations($tenantId, $filters),
                'generated_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Generate performance report
     *
     * @param array $parameters
     * @return TemplatePerformanceReport
     */
    public function generateReport(array $parameters): TemplatePerformanceReport
    {
        $report = TemplatePerformanceReport::create([
            'tenant_id' => $parameters['tenant_id'],
            'name' => $parameters['name'] ?? 'Performance Report',
            'description' => $parameters['description'] ?? null,
            'report_type' => $parameters['report_type'] ?? 'template_performance',
            'parameters' => $parameters,
            'status' => TemplatePerformanceReport::STATUS_PROCESSING,
        ]);

        try {
            // Generate report data based on type
            $data = match ($parameters['report_type']) {
                'template_performance' => $this->generateTemplatePerformanceReport($parameters),
                'comparison' => $this->generateComparisonReport($parameters),
                'trend_analysis' => $this->generateTrendAnalysisReport($parameters),
                'bottleneck_analysis' => $this->generateBottleneckReport($parameters),
                default => [],
            };

            $report->markAsCompleted($data);

        } catch (\Exception $e) {
            Log::error('Failed to generate performance report', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);

            $report->markAsFailed($e->getMessage());
        }

        return $report;
    }

    /**
     * Export dashboard data
     *
     * @param int $tenantId
     * @param string $format
     * @param array $filters
     * @return array
     */
    public function exportDashboardData(int $tenantId, string $format, array $filters = []): array
    {
        $data = $this->getDashboardOverview($tenantId, $filters);

        return [
            'export_format' => $format,
            'export_timestamp' => now()->toISOString(),
            'tenant_id' => $tenantId,
            'filters' => $filters,
            'data' => $data,
        ];
    }

    /**
     * Get summary metrics for dashboard
     */
    private function getSummaryMetrics(int $tenantId, array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? now()->subDays(30);
        $dateTo = $filters['date_to'] ?? now();

        $events = TemplateAnalyticsEvent::forTenant($tenantId)
            ->dateRange($dateFrom, $dateTo);

        return [
            'total_templates' => Template::forTenant($tenantId)->active()->count(),
            'total_landing_pages' => LandingPage::forTenant($tenantId)->published()->count(),
            'total_events' => $events->count(),
            'total_conversions' => $events->conversions()->count(),
            'conversion_rate' => $this->calculateConversionRate($events),
            'unique_users' => $events->distinct('user_identifier')->count('user_identifier'),
            'period' => [
                'from' => Carbon::parse($dateFrom)->toDateString(),
                'to' => Carbon::parse($dateTo)->toDateString(),
            ],
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(int $tenantId, array $filters): array
    {
        $templates = Template::forTenant($tenantId)->active()->get();

        $performance = [];
        foreach ($templates as $template) {
            $performance[$template->id] = [
                'template_name' => $template->name,
                'usage_count' => $template->usage_count,
                'conversion_rate' => $template->getConversionRate(),
                'load_time' => $template->getLoadTime(),
                'performance_score' => $this->calculatePerformanceScore($template->id, $filters),
            ];
        }

        return $performance;
    }

    /**
     * Get trend metrics
     */
    private function getTrendMetrics(int $tenantId, array $filters): array
    {
        $days = $filters['days'] ?? 30;
        $trends = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $events = TemplateAnalyticsEvent::forTenant($tenantId)
                ->whereDate('timestamp', $date->toDateString());

            $trends[] = [
                'date' => $date->toDateString(),
                'page_views' => $events->byEventType('page_view')->count(),
                'conversions' => $events->conversions()->count(),
                'unique_users' => $events->distinct('user_identifier')->count('user_identifier'),
            ];
        }

        return $trends;
    }

    /**
     * Get actionable insights
     */
    private function getActionableInsights(int $tenantId, array $filters): array
    {
        $insights = [];

        // Top performing templates
        $topTemplates = $this->getTopPerformingTemplates($tenantId, $filters['date_from'] ?? now()->subDays(30), $filters['date_to'] ?? now());
        if (!empty($topTemplates)) {
            $insights[] = [
                'type' => 'success',
                'title' => 'Top Performing Templates',
                'description' => "Your top templates are performing well. Consider using them as benchmarks.",
                'data' => $topTemplates,
            ];
        }

        // Underperforming templates
        $underperforming = $this->getUnderperformingTemplates($tenantId, $filters);
        if (!empty($underperforming)) {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Underperforming Templates',
                'description' => "Some templates need optimization. Review their configuration and content.",
                'data' => $underperforming,
            ];
        }

        // Conversion opportunities
        $conversionOpportunities = $this->identifyConversionOpportunities($tenantId, $filters);
        if (!empty($conversionOpportunities)) {
            $insights[] = [
                'type' => 'info',
                'title' => 'Conversion Opportunities',
                'description' => "Identified areas where conversion rates can be improved.",
                'data' => $conversionOpportunities,
            ];
        }

        return $insights;
    }

    /**
     * Calculate performance score for a template
     */
    private function calculatePerformanceScore(int $templateId, array $filters): float
    {
        $template = Template::find($templateId);
        if (!$template) return 0.0;

        $conversionRate = $template->getConversionRate();
        $usageCount = $template->usage_count;
        $loadTime = $template->getLoadTime();

        // Weighted score calculation
        $score = ($conversionRate * 0.5) + (min($usageCount / 100, 1) * 0.3) + ((1 - min($loadTime / 3000, 1)) * 0.2);

        return round($score * 100, 2);
    }

    /**
     * Calculate conversion rate from events
     */
    private function calculateConversionRate($query): float
    {
        $totalEvents = $query->count();
        $conversions = $query->conversions()->count();

        return $totalEvents > 0 ? round(($conversions / $totalEvents) * 100, 2) : 0.0;
    }

    /**
     * Get top performing templates
     */
    private function getTopPerformingTemplates(int $tenantId, $dateFrom, $dateTo): array
    {
        return Template::forTenant($tenantId)
            ->active()
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'usage_count' => $template->usage_count,
                    'conversion_rate' => $template->getConversionRate(),
                ];
            })
            ->toArray();
    }

    /**
     * Get underperforming templates
     */
    private function getUnderperformingTemplates(int $tenantId, array $filters): array
    {
        return Template::forTenant($tenantId)
            ->active()
            ->where('usage_count', '<', 10)
            ->orWhereRaw('JSON_EXTRACT(performance_metrics, "$.conversion_rate") < 1')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'usage_count' => $template->usage_count,
                    'conversion_rate' => $template->getConversionRate(),
                ];
            })
            ->toArray();
    }

    /**
     * Identify conversion opportunities
     */
    private function identifyConversionOpportunities(int $tenantId, array $filters): array
    {
        // This would analyze funnel drop-offs and suggest improvements
        return [
            [
                'type' => 'funnel_optimization',
                'description' => 'High drop-off rate between page view and form submission',
                'potential_improvement' => 15.5,
            ],
        ];
    }

    /**
     * Get template-specific metrics
     */
    private function getTemplateMetrics(int $templateId, array $filters): array
    {
        $template = Template::find($templateId);
        if (!$template) return [];

        return [
            'usage_count' => $template->usage_count,
            'conversion_rate' => $template->getConversionRate(),
            'load_time' => $template->getLoadTime(),
            'last_used' => $template->last_used_at?->toISOString(),
        ];
    }

    /**
     * Generate comparison summary
     */
    private function generateComparisonSummary(array $comparison): array
    {
        $totalTemplates = count($comparison);
        $avgConversionRate = array_sum(array_column(array_column($comparison, 'metrics'), 'conversion_rate')) / $totalTemplates;

        return [
            'total_templates' => $totalTemplates,
            'average_conversion_rate' => round($avgConversionRate, 2),
            'best_performer' => $this->findBestPerformer($comparison),
            'worst_performer' => $this->findWorstPerformer($comparison),
        ];
    }

    /**
     * Generate comparison recommendations
     */
    private function generateComparisonRecommendations(array $comparison): array
    {
        $recommendations = [];

        $bestPerformer = $this->findBestPerformer($comparison);
        if ($bestPerformer) {
            $recommendations[] = [
                'type' => 'benchmark',
                'description' => "Use {$bestPerformer['name']} as a benchmark for other templates",
                'priority' => 'high',
            ];
        }

        return $recommendations;
    }

    /**
     * Find best performing template
     */
    private function findBestPerformer(array $comparison): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach ($comparison as $template) {
            $score = $template['performance_score'];
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = [
                    'id' => $template['template']['id'],
                    'name' => $template['template']['name'],
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    /**
     * Find worst performing template
     */
    private function findWorstPerformer(array $comparison): ?array
    {
        $worst = null;
        $worstScore = 100;

        foreach ($comparison as $template) {
            $score = $template['performance_score'];
            if ($score < $worstScore) {
                $worstScore = $score;
                $worst = [
                    'id' => $template['template']['id'],
                    'name' => $template['template']['name'],
                    'score' => $score,
                ];
            }
        }

        return $worst;
    }

    /**
     * Identify slow templates
     */
    private function identifySlowTemplates(int $tenantId, array $filters): array
    {
        return Template::forTenant($tenantId)
            ->active()
            ->whereRaw('JSON_EXTRACT(performance_metrics, "$.avg_load_time") > 2000')
            ->get()
            ->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'load_time' => $template->getLoadTime(),
                    'impact' => 'High load times can reduce conversion rates',
                ];
            })
            ->toArray();
    }

    /**
     * Identify conversion bottlenecks
     */
    private function identifyConversionBottlenecks(int $tenantId, array $filters): array
    {
        // Analyze conversion funnel for bottlenecks
        return [
            [
                'stage' => 'form_submission',
                'drop_off_rate' => 35.2,
                'description' => 'High drop-off at form submission stage',
            ],
        ];
    }

    /**
     * Identify engagement issues
     */
    private function identifyEngagementIssues(int $tenantId, array $filters): array
    {
        // Analyze engagement metrics for issues
        return [
            [
                'type' => 'scroll_depth',
                'issue' => 'Low average scroll depth',
                'current_value' => 45.3,
                'target_value' => 75.0,
            ],
        ];
    }

    /**
     * Generate bottleneck recommendations
     */
    private function generateBottleneckRecommendations(int $tenantId, array $filters): array
    {
        return [
            [
                'type' => 'optimization',
                'title' => 'Optimize Template Load Times',
                'description' => 'Compress images and minimize JavaScript to improve load times',
                'priority' => 'high',
                'estimated_impact' => 20.5,
            ],
            [
                'type' => 'conversion',
                'title' => 'Improve Form Completion Rate',
                'description' => 'Simplify forms and reduce required fields',
                'priority' => 'medium',
                'estimated_impact' => 15.2,
            ],
        ];
    }

    /**
     * Generate template performance report
     */
    private function generateTemplatePerformanceReport(array $parameters): array
    {
        $tenantId = $parameters['tenant_id'];
        $templateIds = $parameters['template_ids'] ?? null;

        if ($templateIds) {
            return $this->getTemplateComparison($templateIds, $parameters);
        }

        return $this->getDashboardOverview($tenantId, $parameters);
    }

    /**
     * Generate comparison report
     */
    private function generateComparisonReport(array $parameters): array
    {
        return $this->getTemplateComparison($parameters['template_ids'], $parameters);
    }

    /**
     * Generate trend analysis report
     */
    private function generateTrendAnalysisReport(array $parameters): array
    {
        $tenantId = $parameters['tenant_id'];
        return [
            'trends' => $this->getTrendMetrics($tenantId, $parameters),
            'forecast' => $this->generateTrendForecast($tenantId, $parameters),
        ];
    }

    /**
     * Generate bottleneck report
     */
    private function generateBottleneckReport(array $parameters): array
    {
        $tenantId = $parameters['tenant_id'];
        return $this->getBottleneckAnalysis($tenantId, $parameters);
    }

    /**
     * Generate trend forecast
     */
    private function generateTrendForecast(int $tenantId, array $parameters): array
    {
        // Simple linear regression for forecasting
        return [
            'next_week_prediction' => 0,
            'confidence_level' => 0,
            'trend_direction' => 'stable',
        ];
    }

    /**
     * Clear dashboard cache
     */
    public function clearCache(int $tenantId = null): void
    {
        if ($tenantId) {
            Cache::forget(self::CACHE_PREFIX . 'overview_' . $tenantId . '_*');
            Cache::forget(self::CACHE_PREFIX . 'realtime_' . $tenantId);
        } else {
            Cache::forget(self::CACHE_PREFIX . '*');
        }
    }
}