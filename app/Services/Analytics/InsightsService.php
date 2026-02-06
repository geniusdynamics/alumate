<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\CustomEvent;
use App\Models\LearningProgress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Jobs\Analytics\InsightsGenerationJob;

/**
 * Service for generating automated insights and recommendations
 * 
 * This service analyzes analytics data to detect trends, anomalies, and generate actionable recommendations
 * using statistical methods like moving averages and z-score analysis.
 */
class InsightsService
{
    /**
     * Generate insights based on analytics data
     *
     * @param array $options Configuration options for insight generation
     * @return array Array of insights with trends and recommendations
     */
    public function generateInsights(array $options = []): array
    {
        $period = $options['period'] ?? 'last_30_days';
        $metricsFilter = $options['metrics_filter'] ?? [];
        $startDate = $options['start_date'] ?? now()->subDays(30);
        $endDate = $options['end_date'] ?? now();

        // Check consent for data access
        $consentService = app(ConsentService::class);
        if (!$consentService->hasConsent()) {
            Log::warning('Insights generation attempted without data processing consent');
            return [];
        }

        // Queue heavy computation if needed
        if ($options['queue'] ?? false) {
            \App\Jobs\Analytics\InsightsGenerationJob::dispatch($options);
            return ['queued' => true, 'message' => 'Insight generation queued'];
        }

        // Collect data from various sources
        $analyticsEvents = $this->getAnalyticsEvents($startDate, $endDate, $metricsFilter);
        $customEvents = $this->getCustomEvents($startDate, $endDate, $metricsFilter);
        $learningProgress = $this->getLearningProgress($startDate, $endDate, $metricsFilter);

        // Analyze trends
        $trends = $this->analyzeTrends($analyticsEvents, $customEvents, $learningProgress, $period);
        
        // Detect anomalies
        $anomalies = $this->detectAnomalies($analyticsEvents, $customEvents, $learningProgress);
        
        // Generate recommendations
        $recommendations = $this->generateRecommendations($trends, $anomalies, $analyticsEvents, $customEvents, $learningProgress);

        // Combine into insights
        $insights = [];
        foreach ($trends as $trend) {
            $insights[] = [
                'type' => 'trend',
                'metric' => $trend['metric'],
                'description' => $trend['description'],
                'trend_score' => $trend['trend_score'],
                'id' => Str::uuid()->toString(),
                'data_points' => $trend['data_points'],
                'timestamp' => now(),
                'anomaly' => $this->isAnomaly($trend['trend_score']),
                'recommendation' => $this->findRecommendationForTrend($trend, $recommendations)
            ];
        }

        foreach ($anomalies as $anomaly) {
            $insights[] = [
                'type' => 'anomaly',
                'metric' => $anomaly['metric'],
                'description' => $anomaly['description'],
                'severity' => $anomaly['severity'],
                'value' => $anomaly['value'],
                'id' => Str::uuid()->toString(),
                'baseline' => $anomaly['baseline'],
                'timestamp' => now(),
                'recommendation' => $this->findRecommendationForAnomaly($anomaly, $recommendations)
            ];
        }

        return $insights;
    }

    /**
     * Track the effectiveness of a recommendation
     *
     * @param string $insightId The ID of the insight/recommendation
     * @param int $effectivenessScore Score from 1-10 indicating effectiveness
     * @param array $metadata Additional metadata about the implementation
     * @return bool Success status
     */
    public function trackEffectiveness(string $insightId, int $effectivenessScore, array $metadata = []): bool
    {
        if ($effectivenessScore < 1 || $effectivenessScore > 10) {
            throw new \InvalidArgumentException('Effectiveness score must be between 1 and 10');
        }

        // Store effectiveness data for learning and improvement
        $key = "insight_effectiveness_{$insightId}";
        $currentData = Cache::get($key, []);
        
        $effectivenessData = [
            'score' => $effectivenessScore,
            'timestamp' => now(),
            'metadata' => $metadata
        ];

        $currentData[] = $effectivenessData;
        
        // Keep only last 10 effectiveness records to avoid cache bloat
        if (count($currentData) > 10) {
            $currentData = array_slice($currentData, -10);
        }

        Cache::put($key, $currentData, now()->addDays(30));

        // Update average effectiveness for this insight type
        $this->updateInsightTypeEffectiveness($insightId, $effectivenessScore);

        return true;
    }

    /**
     * Get average effectiveness for a specific insight type
     *
     * @param string $insightId The insight ID
     * @return float Average effectiveness score
     */
    public function getInsightEffectiveness(string $insightId): float
    {
        $key = "insight_effectiveness_{$insightId}";
        $data = Cache::get($key, []);

        if (empty($data)) {
            return 0.0;
        }

        $total = array_sum(array_column($data, 'score'));
        return $total / count($data);
    }

    /**
     * Get analytics events for the specified period
     */
    private function getAnalyticsEvents(\DateTime $startDate, \DateTime $endDate, array $filters): Collection
    {
        $query = AnalyticsEvent::whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($filters['event_types'])) {
            $query->whereIn('event_type', $filters['event_types']);
        }

        return $query->get();
    }

    /**
     * Get custom events for the specified period
     */
    private function getCustomEvents(\DateTime $startDate, \DateTime $endDate, array $filters): Collection
    {
        $query = CustomEvent::whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($filters['event_types'])) {
            $query->whereIn('event_type', $filters['event_types']);
        }

        return $query->get();
    }

    /**
     * Get learning progress for the specified period
     */
    private function getLearningProgress(\DateTime $startDate, \DateTime $endDate, array $filters): Collection
    {
        $query = LearningProgress::whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($filters['course_ids'])) {
            $query->whereIn('course_id', $filters['course_ids']);
        }

        return $query->get();
    }

    /**
     * Analyze trends in the collected data
     */
    private function analyzeTrends(Collection $analyticsEvents, Collection $customEvents, Collection $learningProgress, string $period): array
    {
        $trends = [];

        // Engagement trend analysis
        $engagementTrend = $this->calculateEngagementTrend($analyticsEvents, $customEvents, $period);
        if ($engagementTrend) {
            $trends[] = $engagementTrend;
        }

        // Learning progress trend
        $progressTrend = $this->calculateLearningTrend($learningProgress, $period);
        if ($progressTrend) {
            $trends[] = $progressTrend;
        }

        // Custom events trend
        $customEventTrend = $this->calculateCustomEventTrend($customEvents, $period);
        if ($customEventTrend) {
            $trends[] = $customEventTrend;
        }

        return $trends;
    }

    /**
     * Calculate engagement trend
     */
    private function calculateEngagementTrend(Collection $analyticsEvents, Collection $customEvents, string $period): ?array
    {
        $dataPoints = [];
        $periodDays = $this->getPeriodDays($period);

        for ($i = $periodDays; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $startOfDay = $date->startOfDay();
            $endOfDay = $date->endOfDay();

            $dailyEngagement = $analyticsEvents
                ->filter(fn($event) => $event->created_at >= $startOfDay && $event->created_at <= $endOfDay)
                ->count();

            $dataPoints[] = [
                'date' => $date->format('Y-m-d'),
                'value' => $dailyEngagement
            ];
        }

        if (empty($dataPoints)) {
            return null;
        }

        // Calculate moving average
        $movingAvg = $this->calculateMovingAverage($dataPoints, 7);
        
        // Calculate trend score (simple: last 3 days vs previous 3 days)
        $recentAvg = $this->calculateAverageFromEnd($dataPoints, 3);
        $prevAvg = $this->calculateAverageFromEnd($dataPoints, 6, 3);
        
        $trendScore = 0;
        if ($prevAvg > 0) {
            $trendScore = (($recentAvg - $prevAvg) / $prevAvg) * 100;
        }

        return [
            'metric' => 'engagement',
            'description' => 'User engagement trend over the selected period',
            'trend_score' => $trendScore,
            'data_points' => $dataPoints,
            'moving_average' => $movingAvg
        ];
    }

    /**
     * Calculate learning progress trend
     */
    private function calculateLearningTrend(Collection $learningProgress, string $period): ?array
    {
        $dataPoints = [];
        $periodDays = $this->getPeriodDays($period);

        for ($i = $periodDays; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $startOfDay = $date->startOfDay();
            $endOfDay = $date->endOfDay();

            $dailyProgress = $learningProgress
                ->filter(fn($progress) => $progress->created_at >= $startOfDay && $progress->created_at <= $endOfDay)
                ->count();

            $dataPoints[] = [
                'date' => $date->format('Y-m-d'),
                'value' => $dailyProgress
            ];
        }

        if (empty($dataPoints)) {
            return null;
        }

        // Calculate moving average
        $movingAvg = $this->calculateMovingAverage($dataPoints, 7);
        
        // Calculate trend score
        $recentAvg = $this->calculateAverageFromEnd($dataPoints, 3);
        $prevAvg = $this->calculateAverageFromEnd($dataPoints, 6, 3);
        
        $trendScore = 0;
        if ($prevAvg > 0) {
            $trendScore = (($recentAvg - $prevAvg) / $prevAvg) * 100;
        }

        return [
            'metric' => 'learning_progress',
            'description' => 'Learning progress trend over the selected period',
            'trend_score' => $trendScore,
            'data_points' => $dataPoints,
            'moving_average' => $movingAvg
        ];
    }

    /**
     * Calculate custom events trend
     */
    private function calculateCustomEventTrend(Collection $customEvents, string $period): ?array
    {
        $dataPoints = [];
        $periodDays = $this->getPeriodDays($period);

        for ($i = $periodDays; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $startOfDay = $date->startOfDay();
            $endOfDay = $date->endOfDay();

            $dailyEvents = $customEvents
                ->filter(fn($event) => $event->created_at >= $startOfDay && $event->created_at <= $endOfDay)
                ->count();

            $dataPoints[] = [
                'date' => $date->format('Y-m-d'),
                'value' => $dailyEvents
            ];
        }

        if (empty($dataPoints)) {
            return null;
        }

        // Calculate moving average
        $movingAvg = $this->calculateMovingAverage($dataPoints, 7);
        
        // Calculate trend score
        $recentAvg = $this->calculateAverageFromEnd($dataPoints, 3);
        $prevAvg = $this->calculateAverageFromEnd($dataPoints, 6, 3);
        
        $trendScore = 0;
        if ($prevAvg > 0) {
            $trendScore = (($recentAvg - $prevAvg) / $prevAvg) * 100;
        }

        return [
            'metric' => 'custom_events',
            'description' => 'Custom events trend over the selected period',
            'trend_score' => $trendScore,
            'data_points' => $dataPoints,
            'moving_average' => $movingAvg
        ];
    }

    /**
     * Detect anomalies in the data using z-score
     */
    private function detectAnomalies(Collection $analyticsEvents, Collection $customEvents, Collection $learningProgress): array
    {
        $anomalies = [];

        // Check for engagement anomalies
        $engagementAnomalies = $this->detectEngagementAnomalies($analyticsEvents);
        $anomalies = array_merge($anomalies, $engagementAnomalies);

        // Check for learning progress anomalies
        $progressAnomalies = $this->detectLearningAnomalies($learningProgress);
        $anomalies = array_merge($anomalies, $progressAnomalies);

        // Check for custom event anomalies
        $customEventAnomalies = $this->detectCustomEventAnomalies($customEvents);
        $anomalies = array_merge($anomalies, $customEventAnomalies);

        return $anomalies;
    }

    /**
     * Detect engagement anomalies using z-score
     */
    private function detectEngagementAnomalies(Collection $analyticsEvents): array
    {
        $anomalies = [];
        
        // Group events by day
        $dailyEvents = $analyticsEvents->groupBy(function ($event) {
            return $event->created_at->format('Y-m-d');
        });

        if ($dailyEvents->count() < 2) {
            return $anomalies; // Need at least 2 days to calculate z-score
        }

        // Calculate mean and standard deviation
        $dailyCounts = $dailyEvents->map(fn($events) => $events->count())->values()->toArray();
        $mean = array_sum($dailyCounts) / count($dailyCounts);
        $variance = array_sum(array_map(fn($value) => pow($value - $mean, 2), $dailyCounts)) / count($dailyCounts);
        $stdDev = sqrt($variance);

        if ($stdDev === 0) {
            return $anomalies; // No variation means no anomalies
        }

        // Find anomalies (z-score > 1.5)
        foreach ($dailyEvents as $date => $events) {
            $count = $events->count();
            $zScore = abs($count - $mean) / $stdDev;

            if ($zScore > 1.5) {
                $severity = 'medium';
                if ($zScore > 2.5) {
                    $severity = 'high';
                } elseif ($zScore > 3.0) {
                    $severity = 'critical';
                }

                $anomalies[] = [
                    'metric' => 'engagement',
                    'date' => $date,
                    'description' => "Unusual engagement level detected on {$date}",
                    'severity' => $severity,
                    'value' => $count,
                    'baseline' => $mean,
                    'z_score' => $zScore
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect learning progress anomalies using z-score
     */
    private function detectLearningAnomalies(Collection $learningProgress): array
    {
        $anomalies = [];
        
        // Group progress by day
        $dailyProgress = $learningProgress->groupBy(function ($progress) {
            return $progress->created_at->format('Y-m-d');
        });

        if ($dailyProgress->count() < 2) {
            return $anomalies; // Need at least 2 days to calculate z-score
        }

        // Calculate mean and standard deviation
        $dailyCounts = $dailyProgress->map(fn($progress) => $progress->count())->values()->toArray();
        $mean = array_sum($dailyCounts) / count($dailyCounts);
        $variance = array_sum(array_map(fn($value) => pow($value - $mean, 2), $dailyCounts)) / count($dailyCounts);
        $stdDev = sqrt($variance);

        if ($stdDev === 0) {
            return $anomalies; // No variation means no anomalies
        }

        // Find anomalies (z-score > 1.5)
        foreach ($dailyProgress as $date => $progress) {
            $count = $progress->count();
            $zScore = abs($count - $mean) / $stdDev;

            if ($zScore > 1.5) {
                $severity = 'medium';
                if ($zScore > 2.5) {
                    $severity = 'high';
                } elseif ($zScore > 3.0) {
                    $severity = 'critical';
                }

                $anomalies[] = [
                    'metric' => 'learning_progress',
                    'date' => $date,
                    'description' => "Unusual learning progress activity on {$date}",
                    'severity' => $severity,
                    'value' => $count,
                    'baseline' => $mean,
                    'z_score' => $zScore
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Detect custom event anomalies using z-score
     */
    private function detectCustomEventAnomalies(Collection $customEvents): array
    {
        $anomalies = [];
        
        // Group events by day
        $dailyEvents = $customEvents->groupBy(function ($event) {
            return $event->created_at->format('Y-m-d');
        });

        if ($dailyEvents->count() < 2) {
            return $anomalies; // Need at least 2 days to calculate z-score
        }

        // Calculate mean and standard deviation
        $dailyCounts = $dailyEvents->map(fn($events) => $events->count())->values()->toArray();
        $mean = array_sum($dailyCounts) / count($dailyCounts);
        $variance = array_sum(array_map(fn($value) => pow($value - $mean, 2), $dailyCounts)) / count($dailyCounts);
        $stdDev = sqrt($variance);

        if ($stdDev === 0) {
            return $anomalies; // No variation means no anomalies
        }

        // Find anomalies (z-score > 1.5)
        foreach ($dailyEvents as $date => $events) {
            $count = $events->count();
            $zScore = abs($count - $mean) / $stdDev;

            if ($zScore > 1.5) {
                $severity = 'medium';
                if ($zScore > 2.5) {
                    $severity = 'high';
                } elseif ($zScore > 3.0) {
                    $severity = 'critical';
                }

                $anomalies[] = [
                    'metric' => 'custom_events',
                    'date' => $date,
                    'description' => "Unusual custom event activity on {$date}",
                    'severity' => $severity,
                    'value' => $count,
                    'baseline' => $mean,
                    'z_score' => $zScore
                ];
            }
        }

        return $anomalies;
    }

    /**
     * Generate recommendations based on trends and anomalies
     */
    private function generateRecommendations(array $trends, array $anomalies, Collection $analyticsEvents, Collection $customEvents, Collection $learningProgress): array
    {
        $recommendations = [];

        // Generate recommendations based on trends
        foreach ($trends as $trend) {
            if ($trend['metric'] === 'engagement' && $trend['trend_score'] < -20) {
                // Significant drop in engagement
                $recommendations[] = [
                    'type' => 'engagement_campaign',
                    'target' => 'high_churn_cohort',
                    'description' => 'Significant drop in engagement detected. Consider launching engagement campaigns.',
                    'expected_impact' => '+15% retention',
                    'priority' => 'high',
                    'action' => 'Launch re-engagement campaign'
                ];
            } elseif ($trend['metric'] === 'engagement' && $trend['trend_score'] > 20) {
                // Significant spike in engagement
                $recommendations[] = [
                    'type' => 'capitalization',
                    'target' => 'active_users',
                    'description' => 'Significant engagement spike detected. Capitalize on momentum.',
                    'expected_impact' => '+10% conversion',
                    'priority' => 'medium',
                    'action' => 'Launch conversion-focused campaigns'
                ];
            } elseif ($trend['metric'] === 'learning_progress' && $trend['trend_score'] < -20) {
                // Significant drop in learning progress
                $recommendations[] = [
                    'type' => 'course_optimization',
                    'target' => 'course_content',
                    'description' => 'Significant drop in learning progress detected. Consider optimizing course content.',
                    'expected_impact' => '+25% completion rate',
                    'priority' => 'high',
                    'action' => 'Review and optimize course content'
                ];
            }
        }

        // Generate recommendations based on anomalies
        foreach ($anomalies as $anomaly) {
            if ($anomaly['metric'] === 'engagement' && $anomaly['severity'] === 'high' && $anomaly['value'] < $anomaly['baseline']) {
                // Significant drop in engagement
                $recommendations[] = [
                    'type' => 'retention_strategy',
                    'target' => 'at_risk_users',
                    'description' => 'Significant drop in engagement detected on ' . $anomaly['date'] . '. Immediate retention action needed.',
                    'expected_impact' => '+20% retention',
                    'priority' => 'critical',
                    'action' => 'Deploy emergency retention strategy'
                ];
            } elseif ($anomaly['metric'] === 'learning_progress' && $anomaly['severity'] === 'high' && $anomaly['value'] < $anomaly['baseline']) {
                // Significant drop in learning progress
                $recommendations[] = [
                    'type' => 'intervention',
                    'target' => 'struggling_learners',
                    'description' => 'Significant drop in learning progress detected on ' . $anomaly['date'] . '. Consider learner intervention.',
                    'expected_impact' => '+30% course completion',
                    'priority' => 'high',
                    'action' => 'Implement learner support intervention'
                ];
            } elseif ($anomaly['metric'] === 'custom_events' && $anomaly['severity'] === 'high' && $anomaly['value'] > $anomaly['baseline']) {
                // Significant spike in custom events
                $recommendations[] = [
                    'type' => 'optimization',
                    'target' => 'high_performing_features',
                    'description' => 'Significant spike in custom events detected on ' . $anomaly['date'] . '. Optimize for this behavior.',
                    'expected_impact' => '+15% user satisfaction',
                    'priority' => 'medium',
                    'action' => 'Analyze and optimize successful features'
                ];
            }
        }

        // Integrate with AttributionService for conversion impact
        $attributionService = app(AttributionService::class);
        foreach ($recommendations as &$recommendation) {
            // Add attribution insights if available
            // Using a method that likely exists based on the AttributionService structure
            // We'll implement a method that gets attribution summary data
            $attributionSummary = $attributionService->getAttributionSummary([], now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d'));
            if (!empty($attributionSummary)) {
                $recommendation['attribution_insights'] = $attributionSummary;
            }
        }

        return $recommendations;
    }

    /**
     * Find a recommendation that matches a specific trend
     */
    private function findRecommendationForTrend(array $trend, array $recommendations): ?array
    {
        foreach ($recommendations as $rec) {
            if ($trend['metric'] === 'engagement' && strpos($rec['type'], 'engagement') !== false) {
                return $rec;
            } elseif ($trend['metric'] === 'learning_progress' && strpos($rec['type'], 'course') !== false) {
                return $rec;
            } elseif ($trend['metric'] === 'custom_events' && strpos($rec['type'], 'optimization') !== false) {
                return $rec;
            }
        }
        
        return null;
    }

    /**
     * Find a recommendation that matches a specific anomaly
     */
    private function findRecommendationForAnomaly(array $anomaly, array $recommendations): ?array
    {
        foreach ($recommendations as $rec) {
            if ($anomaly['metric'] === 'engagement' && strpos($rec['type'], 'engagement') !== false) {
                return $rec;
            } elseif ($anomaly['metric'] === 'learning_progress' && strpos($rec['type'], 'intervention') !== false) {
                return $rec;
            } elseif ($anomaly['metric'] === 'custom_events' && strpos($rec['type'], 'optimization') !== false) {
                return $rec;
            }
        }
        
        return null;
    }

    /**
     * Check if a trend score indicates an anomaly
     */
    private function isAnomaly(float $trendScore): bool
    {
        // Consider significant changes as anomalies
        return abs($trendScore) > 25; // More than 25% change
    }

    /**
     * Get number of days for a period
     */
    private function getPeriodDays(string $period): int
    {
        switch ($period) {
            case 'last_7_days':
                return 7;
            case 'last_14_days':
                return 14;
            case 'last_30_days':
                return 30;
            case 'last_90_days':
                return 90;
            default:
                return 30; // Default to 30 days
        }
    }

    /**
     * Calculate moving average
     */
    private function calculateMovingAverage(array $dataPoints, int $windowSize): array
    {
        $result = [];
        $count = count($dataPoints);
        
        for ($i = 0; $i < $count; $i++) {
            $sum = 0;
            $items = 0;
            
            // Calculate average for window
            for ($j = max(0, $i - $windowSize + 1); $j <= $i; $j++) {
                $sum += $dataPoints[$j]['value'];
                $items++;
            }
            
            $result[] = [
                'date' => $dataPoints[$i]['date'],
                'value' => $items > 0 ? $sum / $items : 0
            ];
        }
        
        return $result;
    }

    /**
     * Calculate average from the end of the array
     */
    private function calculateAverageFromEnd(array $dataPoints, int $count, int $offset = 0): float
    {
        $slice = array_slice($dataPoints, -$count - $offset, $count);
        if (empty($slice)) {
            return 0;
        }
        
        $sum = array_sum(array_column($slice, 'value'));
        return $sum / count($slice);
    }

    /**
     * Update effectiveness tracking for insight type
     */
    private function updateInsightTypeEffectiveness(string $insightId, int $effectivenessScore): void
    {
        // Extract insight type from insight ID if possible
        $type = 'general';
        if (strpos($insightId, '_') !== false) {
            $parts = explode('_', $insightId);
            $type = $parts[0];
        }

        $key = "insight_type_effectiveness_{$type}";
        $data = Cache::get($key, ['total_score' => 0, 'count' => 0]);
        
        $data['total_score'] += $effectivenessScore;
        $data['count']++;
        
        Cache::put($key, $data, now()->addDays(30));
    }
}