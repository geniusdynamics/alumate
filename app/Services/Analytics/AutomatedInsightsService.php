<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Cohort;
use App\Services\CacheService;
use App\Services\TenantContextService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Automated Insights Generation Service
 *
 * Provides comprehensive automated insights generation for analytics data.
 * Supports pattern detection, insight scoring, prioritization, and various
 * insight types including trends, anomalies, correlations, and predictions.
 */
class AutomatedInsightsService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const INSIGHT_CACHE_TTL = 1800; // 30 minutes for insights

    // Insight type constants
    public const INSIGHT_TYPE_TREND = 'trend';
    public const INSIGHT_TYPE_ANOMALY = 'anomaly';
    public const INSIGHT_TYPE_CORRELATION = 'correlation';
    public const INSIGHT_TYPE_PREDICTIVE = 'predictive';
    public const INSIGHT_TYPE_RETENTION = 'retention';
    public const INSIGHT_TYPE_ENGAGEMENT = 'engagement';
    public const INSIGHT_TYPE_CONVERSION = 'conversion';

    // Severity constants
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_POSITIVE = 'positive';

    // Benchmark thresholds
    private const RETENTION_BENCHMARK_DAY7 = 40;
    private const RETENTION_BENCHMARK_DAY30 = 25;
    private const ENGAGEMENT_BENCHMARK = 50;
    private const CONVERSION_BENCHMARK = 15;

    private CacheService $cacheService;
    private ?TenantContextService $tenantContextService;
    private ?CohortAnalysisService $cohortAnalysisService;
    private ?AttributionService $attributionService;

    public function __construct(
        ?CacheService $cacheService = null,
        ?TenantContextService $tenantContextService = null,
        ?CohortAnalysisService $cohortAnalysisService = null,
        ?AttributionService $attributionService = null
    ) {
        $this->cacheService = $cacheService ?? app(CacheService::class);
        $this->tenantContextService = $tenantContextService;
        $this->cohortAnalysisService = $cohortAnalysisService;
        $this->attributionService = $attributionService;
    }

    /**
     * Generate insights from analytics data
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @param array $metrics Metrics to analyze
     * @return array Generated insights
     */
    public function generateInsights(array $dateRange, array $metrics = []): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "automated_insights_{$tenantId}_" . md5(serialize($dateRange) . serialize($metrics));

            return $this->cacheService->remember($cacheKey, function () use ($dateRange, $metrics) {
                $insights = [];

                // Gather analytics data
                $data = $this->gatherAnalyticsData($dateRange, $metrics);

                // Detect patterns and generate insights
                $trendInsights = $this->getTrendInsights($dateRange);
                $anomalyInsights = $this->getAnomalyInsights($dateRange);
                $correlationInsights = $this->getCorrelationInsights($dateRange);
                $predictiveInsights = $this->getPredictiveInsights($dateRange);

                // Merge all insights
                $insights = array_merge($insights, $trendInsights, $anomalyInsights, $correlationInsights, $predictiveInsights);

                // Score and prioritize insights
                $insights = $this->prioritizeInsights($insights);

                Log::info('Generated automated insights', [
                    'tenant_id' => $this->getCurrentTenantId(),
                    'date_range' => $dateRange,
                    'insight_count' => count($insights),
                ]);

                return $insights;
            }, self::INSIGHT_CACHE_TTL);

        } catch (Exception $e) {
            Log::error('Failed to generate insights', [
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Detect patterns in analytics data
     *
     * @param array $data Analytics data to analyze
     * @return array Detected patterns
     */
    public function detectPatterns(array $data): array
    {
        try {
            $patterns = [];

            // Detect temporal patterns
            $temporalPatterns = $this->detectTemporalPatterns($data);
            $patterns = array_merge($patterns, $temporalPatterns);

            // Detect behavioral patterns
            $behavioralPatterns = $this->detectBehavioralPatterns($data);
            $patterns = array_merge($patterns, $behavioralPatterns);

            // Detect seasonal patterns
            $seasonalPatterns = $this->detectSeasonalPatterns($data);
            $patterns = array_merge($patterns, $seasonalPatterns);

            return [
                'patterns' => $patterns,
                'detected_at' => now(),
                'data_points' => count($data),
            ];

        } catch (Exception $e) {
            Log::error('Failed to detect patterns', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Score an insight by importance
     *
     * @param array $insight Insight to score
     * @return array Insight with score
     */
    public function scoreInsight(array $insight): array
    {
        $score = 0;
        $factors = [];

        // Severity factor (0-40 points)
        $severityScores = [
            self::SEVERITY_CRITICAL => 40,
            self::SEVERITY_HIGH => 30,
            self::SEVERITY_MEDIUM => 20,
            self::SEVERITY_LOW => 10,
            self::SEVERITY_POSITIVE => 5,
        ];
        $severityValue = $severityScores[$insight['severity'] ?? self::SEVERITY_MEDIUM] ?? 20;
        $score += $severityValue;
        $factors['severity'] = $severityValue;

        // Impact factor (0-30 points)
        $impact = $insight['impact'] ?? 'medium';
        $impactScores = [
            'high' => 30,
            'medium' => 20,
            'low' => 10,
        ];
        $impactValue = $impactScores[$impact] ?? 20;
        $score += $impactValue;
        $factors['impact'] = $impactValue;

        // Confidence factor (0-20 points)
        $confidence = $insight['confidence'] ?? 0.5;
        $confidenceValue = min(20, $confidence * 20);
        $score += $confidenceValue;
        $factors['confidence'] = $confidenceValue;

        // Recency factor (0-10 points)
        if (isset($insight['detected_at'])) {
            $hoursAgo = Carbon::parse($insight['detected_at'])->diffInHours(now());
            $recencyValue = max(0, 10 - ($hoursAgo / 24));
            $score += $recencyValue;
            $factors['recency'] = $recencyValue;
        }

        // Actionability factor (0-10 points)
        $hasRecommendation = isset($insight['recommendation']) && !empty($insight['recommendation']);
        $hasAction = isset($insight['actionable']) && $insight['actionable'];
        $actionabilityValue = ($hasRecommendation || $hasAction) ? 10 : 0;
        $score += $actionabilityValue;
        $factors['actionability'] = $actionabilityValue;

        return [
            'insight' => $insight,
            'score' => round(min(100, $score), 2),
            'factors' => $factors,
            'scored_at' => now(),
        ];
    }

    /**
     * Prioritize insights by score
     *
     * @param array $insights Array of insights to prioritize
     * @return array Prioritized insights
     */
    public function prioritizeInsights(array $insights): array
    {
        // Score each insight
        $scoredInsights = array_map(function ($insight) {
            return $this->scoreInsight($insight);
        }, $insights);

        // Sort by score descending
        usort($scoredInsights, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Group by severity for organization
        $grouped = [
            self::SEVERITY_CRITICAL => [],
            self::SEVERITY_HIGH => [],
            self::SEVERITY_MEDIUM => [],
            self::SEVERITY_LOW => [],
            self::SEVERITY_POSITIVE => [],
        ];

        foreach ($scoredInsights as $scoredInsight) {
            $severity = $scoredInsight['insight']['severity'] ?? self::SEVERITY_MEDIUM;
            $grouped[$severity][] = $scoredInsight;
        }

        return [
            'prioritized' => array_values($scoredInsights),
            'grouped' => $grouped,
            'summary' => [
                'total_insights' => count($insights),
                'critical_count' => count($grouped[self::SEVERITY_CRITICAL]),
                'high_count' => count($grouped[self::SEVERITY_HIGH]),
                'medium_count' => count($grouped[self::SEVERITY_MEDIUM]),
                'low_count' => count($grouped[self::SEVERITY_LOW]),
                'positive_count' => count($grouped[self::SEVERITY_POSITIVE]),
                'avg_score' => count($scoredInsights) > 0
                    ? round(array_sum(array_column($scoredInsights, 'score')) / count($scoredInsights), 2)
                    : 0,
            ],
            'prioritized_at' => now(),
        ];
    }

    /**
     * Get trend-based insights
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Trend insights
     */
    public function getTrendInsights(array $dateRange): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "trend_insights_{$tenantId}_" . md5(serialize($dateRange));

            return $this->cacheService->remember($cacheKey, function () use ($dateRange, $tenantId) {
                $insights = [];
                $startDate = Carbon::parse($dateRange['start']);
                $endDate = Carbon::parse($dateRange['end']);

                // Get analytics events for trend analysis
                $events = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $startDate)
                    ->where('occurred_at', '<=', $endDate)
                    ->where('is_compliant', true)
                    ->selectRaw('
                        DATE(occurred_at) as date,
                        event_name,
                        COUNT(*) as count,
                        COUNT(DISTINCT user_id) as unique_users
                    ')
                    ->groupBy('date', 'event_name')
                    ->orderBy('date')
                    ->get();

                if ($events->isEmpty()) {
                    return $insights;
                }

                // Analyze daily trends
                $dailyData = $events->groupBy('date')->map(function ($day) {
                    return [
                        'date' => $day->first()->date,
                        'total_events' => $day->sum('count'),
                        'unique_users' => $day->sum('unique_users'),
                    ];
                })->values();

                // Detect trends in the data
                $trends = $this->analyzeTrendDirection($dailyData);

                // Generate trend insights
                if ($trends['direction'] === 'declining' && abs($trends['change_percent']) > 10) {
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_TREND,
                        'severity' => $trends['change_percent'] < -20 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                        'message' => "Declining usage trend detected with {$trends['change_percent']}% change",
                        'description' => 'Active users have been decreasing over the analyzed period',
                        'recommendation' => 'Review user engagement strategies and identify drop-off points',
                        'metric' => 'active_users',
                        'current_value' => $trends['recent_avg'],
                        'previous_value' => $trends['older_avg'],
                        'change_percent' => round($trends['change_percent'], 2),
                        'data_points' => $dailyData->toArray(),
                        'confidence' => $trends['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                } elseif ($trends['direction'] === 'improving' && $trends['change_percent'] > 10) {
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_TREND,
                        'severity' => self::SEVERITY_POSITIVE,
                        'message' => "Positive growth trend with {$trends['change_percent']}% increase",
                        'description' => 'User engagement metrics show consistent improvement',
                        'recommendation' => 'Continue current strategies and analyze what is driving growth',
                        'metric' => 'active_users',
                        'current_value' => $trends['recent_avg'],
                        'previous_value' => $trends['older_avg'],
                        'change_percent' => round($trends['change_percent'], 2),
                        'data_points' => $dailyData->toArray(),
                        'confidence' => $trends['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                // Detect event-specific trends
                $eventTrends = $this->analyzeEventTrends($events);
                foreach ($eventTrends as $eventTrend) {
                    if ($eventTrend['significant']) {
                        $trendDirection = $eventTrend['trend'] === 'up' ? 'up' : 'down';
                        $insights[] = [
                            'type' => self::INSIGHT_TYPE_TREND,
                            'severity' => $eventTrend['trend'] === 'up' ? self::SEVERITY_POSITIVE : self::SEVERITY_MEDIUM,
                            'message' => "{$eventTrend['event']} events showing {$trendDirection}ward trend",
                            'description' => "{$eventTrend['event']} has changed by {$eventTrend['change_percent']}%",
                            'recommendation' => $eventTrend['trend'] === 'up'
                                ? 'Analyze what drives this positive trend and replicate'
                                : 'Investigate causes for decline and implement improvements',
                            'metric' => $eventTrend['event'],
                            'change_percent' => round($eventTrend['change_percent'], 2),
                            'confidence' => $eventTrend['confidence'],
                            'actionable' => true,
                            'detected_at' => now(),
                        ];
                    }
                }

                return $insights;
            }, self::INSIGHT_CACHE_TTL);

        } catch (Exception $e) {
            Log::error('Failed to generate trend insights', [
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get anomaly-based insights
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Anomaly insights
     */
    public function getAnomalyInsights(array $dateRange): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "anomaly_insights_{$tenantId}_" . md5(serialize($dateRange));

            return $this->cacheService->remember($cacheKey, function () use ($dateRange, $tenantId) {
                $insights = [];
                $startDate = Carbon::parse($dateRange['start']);
                $endDate = Carbon::parse($dateRange['end']);

                // Get analytics events
                $events = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $startDate)
                    ->where('occurred_at', '<=', $endDate)
                    ->where('is_compliant', true)
                    ->selectRaw('
                        DATE(occurred_at) as date,
                        COUNT(*) as event_count,
                        COUNT(DISTINCT user_id) as unique_users
                    ')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                if ($events->isEmpty()) {
                    return $insights;
                }

                // Calculate statistical anomalies
                $anomalies = $this->detectStatisticalAnomalies($events);

                foreach ($anomalies as $anomaly) {
                    $severity = $this->getAnomalySeverity($anomaly['z_score']);
                    $isPositive = $anomaly['z_score'] > 0;
                    $deviationPercent = round($anomaly['deviation'], 2);
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_ANOMALY,
                        'severity' => $severity,
                        'message' => $isPositive
                            ? "Spike detected on {$anomaly['date']} with {$deviationPercent}% above average"
                            : "Drop detected on {$anomaly['date']} with {$deviationPercent}% below average",
                        'description' => "An unusual {$anomaly['type']} pattern was identified",
                        'recommendation' => $isPositive
                            ? 'Analyze what caused this spike and consider replicating the factors'
                            : 'Investigate the cause of this drop and implement preventive measures',
                        'metric' => $anomaly['type'],
                        'date' => $anomaly['date'],
                        'value' => $anomaly['value'],
                        'average' => $anomaly['average'],
                        'deviation' => $deviationPercent,
                        'z_score' => round($anomaly['z_score'], 2),
                        'confidence' => $this->calculateAnomalyConfidence($anomaly['z_score']),
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                // Detect unusual event patterns
                $eventAnomalies = $this->detectEventAnomalies($events, $startDate, $endDate);
                foreach ($eventAnomalies as $eventAnomaly) {
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_ANOMALY,
                        'severity' => self::SEVERITY_MEDIUM,
                        'message' => "Unusual {$eventAnomaly['event']} pattern detected",
                        'description' => $eventAnomaly['description'],
                        'recommendation' => 'Review event triggers and user behavior during this period',
                        'metric' => $eventAnomaly['event'],
                        'data' => $eventAnomaly['data'],
                        'confidence' => $eventAnomaly['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                return $insights;
            }, self::INSIGHT_CACHE_TTL);

        } catch (Exception $e) {
            Log::error('Failed to generate anomaly insights', [
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get correlation-based insights
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Correlation insights
     */
    public function getCorrelationInsights(array $dateRange): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "correlation_insights_{$tenantId}_" . md5(serialize($dateRange));

            return $this->cacheService->remember($cacheKey, function () use ($dateRange) {
                $insights = [];
                $startDate = Carbon::parse($dateRange['start']);
                $endDate = Carbon::parse($dateRange['end']);

                // Get multiple metrics for correlation analysis
                $events = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $startDate)
                    ->where('occurred_at', '<=', $endDate)
                    ->where('is_compliant', true)
                    ->selectRaw('
                        DATE(occurred_at) as date,
                        event_name,
                        COUNT(*) as count
                    ')
                    ->groupBy('date', 'event_name')
                    ->get();

                if ($events->isEmpty()) {
                    return $insights;
                }

                // Pivot data for correlation analysis
                $pivotedData = $this->pivotForCorrelation($events);

                // Calculate correlations between event types
                $correlations = $this->calculateCorrelations($pivotedData);

                foreach ($correlations as $correlation) {
                    if (abs($correlation['coefficient']) >= 0.7 && $correlation['significant']) {
                        $insights[] = [
                            'type' => self::INSIGHT_TYPE_CORRELATION,
                            'severity' => abs($correlation['coefficient']) >= 0.9 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM,
                            'message' => "Strong correlation found between {$correlation['metric1']} and {$correlation['metric2']}",
                            'description' => "Correlation coefficient: " . round($correlation['coefficient'], 2),
                            'recommendation' => $correlation['coefficient'] > 0
                                ? "Consider promoting {$correlation['metric1']} to boost {$correlation['metric2']}"
                                : "These metrics move inversely - investigate the relationship",
                            'metric1' => $correlation['metric1'],
                            'metric2' => $correlation['metric2'],
                            'coefficient' => round($correlation['coefficient'], 2),
                            'p_value' => round($correlation['p_value'], 4),
                            'sample_size' => $correlation['sample_size'],
                            'confidence' => 1 - $correlation['p_value'],
                            'actionable' => true,
                            'detected_at' => now(),
                        ];
                    }
                }

                return $insights;
            }, self::INSIGHT_CACHE_TTL);

        } catch (Exception $e) {
            Log::error('Failed to generate correlation insights', [
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get predictive insights
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Predictive insights
     */
    public function getPredictiveInsights(array $dateRange): array
    {
        try {
            $tenantId = $this->getCurrentTenantId();
            $cacheKey = "predictive_insights_{$tenantId}_" . md5(serialize($dateRange));

            return $this->cacheService->remember($cacheKey, function () use ($dateRange) {
                $insights = [];
                $startDate = Carbon::parse($dateRange['start']);
                $endDate = Carbon::parse($dateRange['end']);

                // Get historical data for predictions
                $events = AnalyticsEvent::byTenant($tenantId)
                    ->where('occurred_at', '>=', $startDate)
                    ->where('occurred_at', '<=', $endDate)
                    ->where('is_compliant', true)
                    ->selectRaw('
                        DATE(occurred_at) as date,
                        COUNT(*) as event_count,
                        COUNT(DISTINCT user_id) as unique_users
                    ')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                if ($events->isEmpty()) {
                    return $insights;
                }

                // Generate predictions using simple forecasting
                $predictions = $this->generatePredictions($events);

                // Prediction insights
                if ($predictions['trend_direction'] === 'declining') {
                    $expectedChange = round($predictions['expected_change'], 2);
                    $severity = $predictions['confidence'] > 0.8 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM;
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_PREDICTIVE,
                        'severity' => $severity,
                        'message' => 'Predicted decline in engagement over next period',
                        'description' => "Based on current trends, we predict a {$expectedChange}% change",
                        'recommendation' => 'Implement proactive engagement strategies now to reverse the trend',
                        'metric' => 'engagement',
                        'expected_change' => $expectedChange,
                        'prediction_period' => $predictions['period'],
                        'confidence' => $predictions['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                } elseif ($predictions['trend_direction'] === 'improving') {
                    $expectedChange = round($predictions['expected_change'], 2);
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_PREDICTIVE,
                        'severity' => self::SEVERITY_POSITIVE,
                        'message' => 'Positive trajectory predicted for engagement',
                        'description' => "Expected growth of {$expectedChange}% in the next period",
                        'recommendation' => 'Prepare resources to handle increased engagement',
                        'metric' => 'engagement',
                        'expected_change' => $expectedChange,
                        'prediction_period' => $predictions['period'],
                        'confidence' => $predictions['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                // Churn prediction insight
                $churnPrediction = $this->predictChurnRisk($events);
                if ($churnPrediction['risk_level'] !== 'low') {
                    $severity = $churnPrediction['risk_level'] === 'high' ? self::SEVERITY_CRITICAL : self::SEVERITY_HIGH;
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_PREDICTIVE,
                        'severity' => $severity,
                        'message' => "{$churnPrediction['risk_level']} churn risk detected",
                        'description' => $churnPrediction['description'],
                        'recommendation' => $churnPrediction['recommendation'],
                        'metric' => 'churn_risk',
                        'risk_score' => round($churnPrediction['risk_score'], 2),
                        'risk_level' => $churnPrediction['risk_level'],
                        'confidence' => $churnPrediction['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                // Conversion prediction
                $conversionPrediction = $this->predictConversion($events);
                if ($conversionPrediction['has_prediction']) {
                    $insights[] = [
                        'type' => self::INSIGHT_TYPE_PREDICTIVE,
                        'severity' => self::SEVERITY_MEDIUM,
                        'message' => 'Conversion rate forecast available',
                        'description' => "Predicted conversion: {$conversionPrediction['predicted_rate']}%",
                        'recommendation' => 'Focus optimization efforts on conversion funnel',
                        'metric' => 'conversion_rate',
                        'predicted_rate' => round($conversionPrediction['predicted_rate'], 2),
                        'confidence' => $conversionPrediction['confidence'],
                        'actionable' => true,
                        'detected_at' => now(),
                    ];
                }

                return $insights;
            }, self::INSIGHT_CACHE_TTL);

        } catch (Exception $e) {
            Log::error('Failed to generate predictive insights', [
                'date_range' => $dateRange,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // Private helper methods

    /**
     * Get current tenant ID
     */
    private function getCurrentTenantId(): string
    {
        if ($this->tenantContextService) {
            return $this->tenantContextService->getCurrentTenantId() ?? 'default';
        }
        return session('tenant_id', 'default');
    }

    /**
     * Gather analytics data for insights
     */
    private function gatherAnalyticsData(array $dateRange, array $metrics): array
    {
        $startDate = Carbon::parse($dateRange['start']);
        $endDate = Carbon::parse($dateRange['end']);
        $tenantId = $this->getCurrentTenantId();

        $query = AnalyticsEvent::byTenant($tenantId)
            ->where('occurred_at', '>=', $startDate)
            ->where('occurred_at', '<=', $endDate)
            ->where('is_compliant', true);

        if (!empty($metrics)) {
            $query->whereIn('event_name', $metrics);
        }

        return $query->selectRaw('
            DATE(occurred_at) as date,
            event_name,
            COUNT(*) as count,
            COUNT(DISTINCT user_id) as unique_users
        ')
            ->groupBy('date', 'event_name')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Detect temporal patterns in data
     */
    private function detectTemporalPatterns(array $data): array
    {
        $patterns = [];

        // Group by day of week
        $byDayOfWeek = [];
        foreach ($data as $entry) {
            $dayOfWeek = Carbon::parse($entry['date'])->dayOfWeek;
            $byDayOfWeek[$dayOfWeek] = ($byDayOfWeek[$dayOfWeek] ?? 0) + ($entry['count'] ?? 0);
        }

        // Find peak and low days
        if (!empty($byDayOfWeek)) {
            $peakDay = array_search(max($byDayOfWeek), $byDayOfWeek);
            $lowDay = array_search(min($byDayOfWeek), $byDayOfWeek);

            $patterns['temporal'] = [
                'peak_day' => Carbon::now()->day($peakDay)->format('l'),
                'low_day' => Carbon::now()->day($lowDay)->format('l'),
                'day_distribution' => $byDayOfWeek,
            ];
        }

        return $patterns;
    }

    /**
     * Detect behavioral patterns in data
     */
    private function detectBehavioralPatterns(array $data): array
    {
        $patterns = [];

        // Group by event type
        $byEventType = [];
        foreach ($data as $entry) {
            $eventType = $entry['event_name'] ?? 'unknown';
            if (!isset($byEventType[$eventType])) {
                $byEventType[$eventType] = [
                    'count' => 0,
                    'unique_users' => 0,
                ];
            }
            $byEventType[$eventType]['count'] += $entry['count'] ?? 0;
            $byEventType[$eventType]['unique_users'] += $entry['unique_users'] ?? 0;
        }

        $patterns['behavioral'] = [
            'event_distribution' => $byEventType,
            'top_events' => array_slice(array_keys($byEventType), 0, 5),
        ];

        return $patterns;
    }

    /**
     * Detect seasonal patterns in data
     */
    private function detectSeasonalPatterns(array $data): array
    {
        $patterns = [];

        // Group by week
        $byWeek = [];
        foreach ($data as $entry) {
            $week = Carbon::parse($entry['date'])->weekOfYear;
            $byWeek[$week] = ($byWeek[$week] ?? 0) + ($entry['count'] ?? 0);
        }

        // Calculate week-over-week changes
        $changes = [];
        $weeks = array_keys($byWeek);
        sort($weeks);
        for ($i = 1; $i < count($weeks); $i++) {
            $prevWeek = $weeks[$i - 1];
            $currWeek = $weeks[$i];
            $change = $byWeek[$currWeek] > 0
                ? (($byWeek[$currWeek] - $byWeek[$prevWeek]) / $byWeek[$prevWeek]) * 100
                : 0;
            $changes["Week {$prevWeek} -> Week {$currWeek}"] = round($change, 2);
        }

        $patterns['seasonal'] = [
            'weekly_totals' => $byWeek,
            'week_changes' => $changes,
        ];

        return $patterns;
    }

    /**
     * Analyze trend direction
     */
    private function analyzeTrendDirection(Collection $data): array
    {
        if ($data->count() < 2) {
            return [
                'direction' => 'stable',
                'change_percent' => 0,
                'recent_avg' => 0,
                'older_avg' => 0,
                'confidence' => 0,
            ];
        }

        $halfPoint = (int) floor($data->count() / 2);
        $recentData = $data->slice($halfPoint);
        $olderData = $data->slice(0, $halfPoint);

        $recentAvg = $recentData->avg('unique_users') ?? 0;
        $olderAvg = $olderData->avg('unique_users') ?? 0;

        $changePercent = $olderAvg > 0
            ? (($recentAvg - $olderAvg) / $olderAvg) * 100
            : 0;

        // Determine direction
        $direction = $this->getTrendDirection($changePercent);

        // Calculate confidence based on consistency
        $consistency = $this->calculateTrendConsistency($data);

        return [
            'direction' => $direction,
            'change_percent' => round($changePercent, 2),
            'recent_avg' => round($recentAvg, 2),
            'older_avg' => round($olderAvg, 2),
            'confidence' => $consistency,
        ];
    }

    /**
     * Get trend direction from change percentage
     */
    private function getTrendDirection(float $changePercent): string
    {
        if ($changePercent > 10) {
            return 'improving';
        }
        if ($changePercent > 0) {
            return 'slight_improvement';
        }
        if ($changePercent < -10) {
            return 'declining';
        }
        if ($changePercent < 0) {
            return 'slight_decline';
        }
        return 'stable';
    }

    /**
     * Calculate trend consistency
     */
    private function calculateTrendConsistency(Collection $data): float
    {
        if ($data->count() < 3) {
            return 0.5;
        }

        $values = $data->pluck('unique_users')->toArray();
        $changes = [];

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $changes[] = abs(($values[$i] - $values[$i - 1]) / $values[$i - 1]);
            }
        }

        if (empty($changes)) {
            return 0.5;
        }

        $avgChange = array_sum($changes) / count($changes);

        // Lower average change = higher consistency
        return max(0, min(1, 1 - min(1, $avgChange)));
    }

    /**
     * Analyze event trends
     */
    private function analyzeEventTrends(Collection $events): array
    {
        $trends = [];
        $eventGroups = $events->groupBy('event_name');

        foreach ($eventGroups as $eventName => $eventData) {
            if ($eventData->count() < 2) {
                continue;
            }

            $firstHalf = $eventData->slice(0, (int) floor($eventData->count() / 2));
            $secondHalf = $eventData->slice((int) floor($eventData->count() / 2));

            $firstAvg = $firstHalf->avg('count') ?? 0;
            $secondAvg = $secondHalf->avg('count') ?? 0;

            $change = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
            $significant = abs($change) > 10;

            $trends[] = [
                'event' => $eventName,
                'trend' => $change > 0 ? 'up' : 'down',
                'change_percent' => round($change, 2),
                'significant' => $significant,
                'confidence' => $this->calculateTrendConsistency($eventData),
            ];
        }

        return $trends;
    }

    /**
     * Detect statistical anomalies
     */
    private function detectStatisticalAnomalies(Collection $data): array
    {
        $anomalies = [];

        $values = $data->pluck('event_count')->toArray();
        $mean = array_sum($values) / count($values);
        $stdDev = $this->calculateStdDev($values, $mean);

        foreach ($data as $entry) {
            $value = $entry['event_count'];
            $zScore = $stdDev > 0 ? ($value - $mean) / $stdDev : 0;

            if (abs($zScore) >= 1.5) {
                $deviation = $mean > 0 ? (($value - $mean) / $mean) * 100 : 0;
                $anomalies[] = [
                    'date' => $entry->date,
                    'type' => 'event_count',
                    'value' => $value,
                    'average' => round($mean, 2),
                    'deviation' => round($deviation, 2),
                    'z_score' => round($zScore, 2),
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Get anomaly severity based on z-score
     */
    private function getAnomalySeverity(float $zScore): string
    {
        if ($zScore > 3 || $zScore < -3) {
            return self::SEVERITY_CRITICAL;
        }
        if ($zScore > 2 || $zScore < -2) {
            return self::SEVERITY_HIGH;
        }
        if ($zScore > 1.5 || $zScore < -1.5) {
            return self::SEVERITY_MEDIUM;
        }
        return self::SEVERITY_LOW;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values, float $mean): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $squaredDiffs = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        return sqrt(array_sum($squaredDiffs) / (count($values) - 1));
    }

    /**
     * Calculate anomaly confidence based on z-score
     */
    private function calculateAnomalyConfidence(float $zScore): float
    {
        // Higher z-score = higher confidence
        $confidence = min(1, abs($zScore) / 4);
        return round($confidence, 2);
    }

    /**
     * Detect event anomalies
     */
    private function detectEventAnomalies(Collection $events, Carbon $startDate, Carbon $endDate): array
    {
        $anomalies = [];

        $eventGroups = $events->groupBy('event_name');

        foreach ($eventGroups as $eventName => $eventData) {
            $values = $eventData->pluck('count')->toArray();

            if (count($values) < 3) {
                continue;
            }

            $mean = array_sum($values) / count($values);
            $stdDev = $this->calculateStdDev($values, $mean);

            // Check for sudden spikes
            $latestValue = end($values);
            $prevValue = $values[count($values) - 2] ?? $mean;

            if ($prevValue > 0 && $stdDev > 0) {
                $spikePercent = (($latestValue - $prevValue) / $prevValue) * 100;

                if ($spikePercent > 50 || $spikePercent < -50) {
                    $spikeType = $spikePercent > 0 ? 'increase' : 'decrease';
                    $absSpikePercent = round(abs($spikePercent), 1);
                    $anomalies[] = [
                        'event' => $eventName,
                        'description' => "Sudden {$spikeType} of {$absSpikePercent}% in {$eventName} events",
                        'data' => [
                            'previous' => $prevValue,
                            'current' => $latestValue,
                            'change_percent' => round($spikePercent, 2),
                        ],
                        'confidence' => min(1, abs($spikePercent) / 100),
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Pivot data for correlation analysis
     */
    private function pivotForCorrelation(Collection $events): array
    {
        $pivoted = [];

        foreach ($events as $entry) {
            $date = $entry->date;
            $eventName = $entry->event_name;
            $count = $entry->count;

            if (!isset($pivoted[$date])) {
                $pivoted[$date] = [];
            }

            $pivoted[$date][$eventName] = $count;
        }

        return $pivoted;
    }

    /**
     * Calculate correlations between metrics
     */
    private function calculateCorrelations(array $data): array
    {
        $correlations = [];
        $dates = array_keys($data);

        if (count($dates) < 3) {
            return $correlations;
        }

        // Get all event types
        $eventTypes = [];
        foreach ($data as $date => $events) {
            foreach ($events as $eventType => $count) {
                $eventTypes[$eventType] = true;
            }
        }
        $eventTypes = array_keys($eventTypes);

        if (count($eventTypes) < 2) {
            return $correlations;
        }

        // Calculate pairwise correlations
        for ($i = 0; $i < count($eventTypes); $i++) {
            for ($j = $i + 1; $j < count($eventTypes); $j++) {
                $metric1 = $eventTypes[$i];
                $metric2 = $eventTypes[$j];

                $values1 = [];
                $values2 = [];

                foreach ($dates as $date) {
                    if (isset($data[$date][$metric1])) {
                        $values1[] = $data[$date][$metric1];
                    }
                    if (isset($data[$date][$metric2])) {
                        $values2[] = $data[$date][$metric2];
                    }
                }

                if (count($values1) >= 3 && count($values2) >= 3) {
                    $correlation = $this->pearsonCorrelation($values1, $values2);
                    $significant = $this->isCorrelationSignificant($values1, $values2);

                    $correlations[] = [
                        'metric1' => $metric1,
                        'metric2' => $metric2,
                        'coefficient' => $correlation,
                        'p_value' => $this->calculateCorrelationPValue($correlation, count($values1)),
                        'significant' => $significant,
                        'sample_size' => count($values1),
                    ];
                }
            }
        }

        return $correlations;
    }

    /**
     * Calculate Pearson correlation coefficient
     */
    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        if ($n < 2) {
            return 0;
        }

        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = array_sum(array_map(fn($i) => $x[$i] * $y[$i], range(0, $n - 1)));
        $sumX2 = array_sum(array_map(fn($v) => $v * $v, $x));
        $sumY2 = array_sum(array_map(fn($v) => $v * $v, $y));

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        return $denominator > 0 ? $numerator / $denominator : 0;
    }

    /**
     * Check if correlation is statistically significant
     */
    private function isCorrelationSignificant(array $x, array $y): bool
    {
        $correlation = $this->pearsonCorrelation($x, $y);
        $pValue = $this->calculateCorrelationPValue($correlation, count($x));
        return $pValue < 0.05;
    }

    /**
     * Calculate p-value for correlation
     */
    private function calculateCorrelationPValue(float $correlation, int $n): float
    {
        if (abs($correlation) >= 1) {
            return 0;
        }

        // Approximate t-statistic
        $t = $correlation * sqrt(($n - 2) / (1 - $correlation * $correlation));

        // Approximate p-value (two-tailed)
        $df = $n - 2;
        // Simplified approximation
        $pValue = exp(-0.5 * $t * $t);

        return min(1, max(0, $pValue));
    }

    /**
     * Generate predictions using simple forecasting
     */
    private function generatePredictions(Collection $data): array
    {
        $values = $data->pluck('unique_users')->toArray();

        if (count($values) < 3) {
            return [
                'trend_direction' => 'stable',
                'expected_change' => 0,
                'period' => '7 days',
                'confidence' => 0.5,
            ];
        }

        // Simple linear regression
        $n = count($values);
        $xSum = 0;
        $ySum = 0;
        $xySum = 0;
        $x2Sum = 0;

        for ($i = 0; $i < $n; $i++) {
            $xSum += $i;
            $ySum += $values[$i];
            $xySum += $i * $values[$i];
            $x2Sum += $i * $i;
        }

        $slope = $n > 0 ? ($n * $xySum - $xSum * $ySum) / ($n * $x2Sum - $xSum * $xSum) : 0;
        $avgY = $ySum / $n;

        // Predict next period (7 days = 7 data points if daily)
        $prediction = $avgY + $slope * $n;
        $changePercent = $avgY > 0 ? (($prediction - $avgY) / $avgY) * 100 : 0;

        // Confidence based on trend consistency
        $consistency = $this->calculateTrendConsistency($data);

        return [
            'trend_direction' => $changePercent > 5 ? 'improving' : ($changePercent < -5 ? 'declining' : 'stable'),
            'expected_change' => round($changePercent, 2),
            'period' => '7 days',
            'confidence' => round($consistency * 0.8 + 0.1, 2),
        ];
    }

    /**
     * Predict churn risk
     */
    private function predictChurnRisk(Collection $data): array
    {
        $values = $data->pluck('unique_users')->toArray();

        if (count($values) < 7) {
            return [
                'risk_level' => 'unknown',
                'risk_score' => 0,
                'description' => 'Insufficient data for churn prediction',
                'recommendation' => 'Collect more data for accurate prediction',
                'confidence' => 0,
            ];
        }

        // Calculate trend over last 7 days vs previous 7 days
        $recentValues = array_slice($values, -7);
        $olderValues = array_slice($values, -14, 7);

        $recentAvg = array_sum($recentValues) / count($recentValues);
        $olderAvg = !empty($olderValues) ? array_sum($olderValues) / count($olderValues) : $recentAvg;

        $declinePercent = $olderAvg > 0
            ? (($olderAvg - $recentAvg) / $olderAvg) * 100
            : 0;

        // Calculate risk score (0-100)
        $riskScore = min(100, max(0, $declinePercent * 2));

        // Determine risk level
        $riskLevel = $this->getRiskLevel($riskScore);

        // Build description and recommendation
        $description = $this->getChurnDescription($riskLevel, $declinePercent);
        $recommendation = $this->getChurnRecommendation($riskLevel);

        return [
            'risk_level' => $riskLevel,
            'risk_score' => round($riskScore, 2),
            'description' => $description,
            'recommendation' => $recommendation,
            'confidence' => round(min(0.9, count($values) / 30), 2),
        ];
    }

    /**
     * Get risk level from risk score
     */
    private function getRiskLevel(float $riskScore): string
    {
        if ($riskScore >= 70) {
            return 'high';
        }
        if ($riskScore >= 40) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Get churn description based on risk level
     */
    private function getChurnDescription(string $riskLevel, float $declinePercent): string
    {
        if ($riskLevel !== 'low') {
            return "User engagement has declined by " . round($declinePercent, 1) . "% over the past week";
        }
        return "User engagement remains stable";
    }

    /**
     * Get churn recommendation based on risk level
     */
    private function getChurnRecommendation(string $riskLevel): string
    {
        switch ($riskLevel) {
            case 'high':
                return 'Immediately implement user re-engagement campaign and analyze churn reasons';
            case 'medium':
                return 'Review recent changes and implement retention strategies';
            default:
                return 'Continue monitoring and maintain current engagement strategies';
        }
    }

    /**
     * Predict conversion rate
     */
    private function predictConversion(Collection $data): array
    {
        $values = $data->pluck('event_count')->toArray();

        if (count($values) < 7) {
            return [
                'has_prediction' => false,
            ];
        }

        // Simple moving average prediction
        $recentValues = array_slice($values, -7);
        $predictedRate = array_sum($recentValues) / count($recentValues);

        // Normalize to percentage (assuming max 100% = max historical value)
        $maxValue = max($values);
        $predictedPercent = $maxValue > 0 ? ($predictedRate / $maxValue) * 100 : 0;

        return [
            'has_prediction' => true,
            'predicted_rate' => round(min(100, $predictedPercent), 2),
            'confidence' => round(min(0.8, count($values) / 30), 2),
        ];
    }
}
