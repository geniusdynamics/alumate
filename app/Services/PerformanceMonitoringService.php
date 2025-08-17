<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PerformanceMonitoringService
{
    private array $metrics = [];

    private array $thresholds = [
        'response_time' => 3000,      // 3 seconds
        'database_time' => 100,       // 100ms
        'memory_usage' => 128,        // 128MB
        'cache_hit_rate' => 80,       // 80%
        'error_rate' => 1,            // 1%
        'concurrent_users' => 100,    // 100 users
    ];

    public function __construct()
    {
        $this->initializeMetrics();
    }

    /**
     * Start performance monitoring for a request
     */
    public function startMonitoring(Request $request): string
    {
        $monitoringId = uniqid('perf_', true);

        $this->metrics[$monitoringId] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'request_uri' => $request->getRequestUri(),
            'request_method' => $request->getMethod(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'session_id' => $request->session()->getId(),
            'user_id' => auth()->id(),
            'database_queries' => [],
            'cache_operations' => [],
            'external_calls' => [],
            'custom_metrics' => [],
        ];

        // Start database query monitoring
        $this->startDatabaseMonitoring($monitoringId);

        // Start cache monitoring
        $this->startCacheMonitoring($monitoringId);

        return $monitoringId;
    }

    /**
     * End performance monitoring and record metrics
     */
    public function endMonitoring(string $monitoringId): array
    {
        if (! isset($this->metrics[$monitoringId])) {
            return [];
        }

        $metrics = &$this->metrics[$monitoringId];
        $metrics['end_time'] = microtime(true);
        $metrics['end_memory'] = memory_get_usage(true);
        $metrics['peak_memory'] = memory_get_peak_usage(true);

        // Calculate derived metrics
        $metrics['total_time'] = ($metrics['end_time'] - $metrics['start_time']) * 1000; // ms
        $metrics['memory_used'] = $metrics['end_memory'] - $metrics['start_memory'];
        $metrics['database_time'] = array_sum(array_column($metrics['database_queries'], 'time'));
        $metrics['database_query_count'] = count($metrics['database_queries']);
        $metrics['cache_hit_rate'] = $this->calculateCacheHitRate($metrics['cache_operations']);

        // Check performance thresholds
        $metrics['performance_issues'] = $this->checkPerformanceThresholds($metrics);

        // Store metrics
        $this->storeMetrics($monitoringId, $metrics);

        // Log performance issues
        if (! empty($metrics['performance_issues'])) {
            $this->logPerformanceIssues($monitoringId, $metrics);
        }

        // Clean up
        unset($this->metrics[$monitoringId]);

        return $metrics;
    }

    /**
     * Record custom performance metric
     */
    public function recordMetric(string $monitoringId, string $name, $value, array $context = []): void
    {
        if (! isset($this->metrics[$monitoringId])) {
            return;
        }

        $this->metrics[$monitoringId]['custom_metrics'][] = [
            'name' => $name,
            'value' => $value,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Record external API call
     */
    public function recordExternalCall(string $monitoringId, string $service, float $duration, bool $success = true): void
    {
        if (! isset($this->metrics[$monitoringId])) {
            return;
        }

        $this->metrics[$monitoringId]['external_calls'][] = [
            'service' => $service,
            'duration' => $duration,
            'success' => $success,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get real-time performance metrics
     */
    public function getRealTimeMetrics(): array
    {
        return [
            'active_requests' => count($this->metrics),
            'average_response_time' => $this->getAverageResponseTime(),
            'current_memory_usage' => memory_get_usage(true),
            'peak_memory_usage' => memory_get_peak_usage(true),
            'database_connections' => $this->getDatabaseConnectionCount(),
            'cache_hit_rate' => $this->getCurrentCacheHitRate(),
            'error_rate' => $this->getCurrentErrorRate(),
            'concurrent_users' => $this->getConcurrentUserCount(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get performance analytics for a time period
     */
    public function getPerformanceAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "performance_analytics_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";

        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            $metrics = $this->getStoredMetrics($startDate, $endDate);

            return [
                'summary' => $this->calculateSummaryMetrics($metrics),
                'trends' => $this->calculateTrends($metrics),
                'top_slow_requests' => $this->getTopSlowRequests($metrics),
                'error_analysis' => $this->analyzeErrors($metrics),
                'resource_usage' => $this->analyzeResourceUsage($metrics),
                'user_experience' => $this->analyzeUserExperience($metrics),
            ];
        });
    }

    /**
     * Generate performance report
     */
    public function generatePerformanceReport(Carbon $startDate, Carbon $endDate): array
    {
        $analytics = $this->getPerformanceAnalytics($startDate, $endDate);

        return [
            'report_period' => [
                'start_date' => $startDate->toISOString(),
                'end_date' => $endDate->toISOString(),
                'duration_days' => $startDate->diffInDays($endDate),
            ],
            'executive_summary' => $this->generateExecutiveSummary($analytics),
            'detailed_metrics' => $analytics,
            'recommendations' => $this->generateRecommendations($analytics),
            'alerts' => $this->generateAlerts($analytics),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Monitor Core Web Vitals
     */
    public function recordCoreWebVitals(array $vitals): void
    {
        $cacheKey = 'core_web_vitals_'.date('Y-m-d-H');

        $currentVitals = Cache::get($cacheKey, []);
        $currentVitals[] = array_merge($vitals, [
            'timestamp' => microtime(true),
            'user_agent' => request()->userAgent(),
            'viewport' => $vitals['viewport'] ?? null,
        ]);

        Cache::put($cacheKey, $currentVitals, 3600);

        // Check if vitals exceed thresholds
        $this->checkCoreWebVitalsThresholds($vitals);
    }

    /**
     * Get Core Web Vitals summary
     */
    public function getCoreWebVitalsSummary(int $hours = 24): array
    {
        $vitals = [];

        for ($i = 0; $i < $hours; $i++) {
            $cacheKey = 'core_web_vitals_'.date('Y-m-d-H', strtotime("-{$i} hours"));
            $hourlyVitals = Cache::get($cacheKey, []);
            $vitals = array_merge($vitals, $hourlyVitals);
        }

        if (empty($vitals)) {
            return [];
        }

        return [
            'lcp' => $this->calculateVitalStats(array_column($vitals, 'lcp')),
            'fid' => $this->calculateVitalStats(array_column($vitals, 'fid')),
            'cls' => $this->calculateVitalStats(array_column($vitals, 'cls')),
            'fcp' => $this->calculateVitalStats(array_column($vitals, 'fcp')),
            'ttfb' => $this->calculateVitalStats(array_column($vitals, 'ttfb')),
            'sample_size' => count($vitals),
            'time_period' => "{$hours} hours",
        ];
    }

    /**
     * Set up performance alerts
     */
    public function setupPerformanceAlerts(): void
    {
        $alerts = [
            'high_response_time' => [
                'threshold' => $this->thresholds['response_time'],
                'check' => fn () => $this->getAverageResponseTime() > $this->thresholds['response_time'],
            ],
            'high_error_rate' => [
                'threshold' => $this->thresholds['error_rate'],
                'check' => fn () => $this->getCurrentErrorRate() > $this->thresholds['error_rate'],
            ],
            'low_cache_hit_rate' => [
                'threshold' => $this->thresholds['cache_hit_rate'],
                'check' => fn () => $this->getCurrentCacheHitRate() < $this->thresholds['cache_hit_rate'],
            ],
            'high_memory_usage' => [
                'threshold' => $this->thresholds['memory_usage'] * 1024 * 1024,
                'check' => fn () => memory_get_usage(true) > $this->thresholds['memory_usage'] * 1024 * 1024,
            ],
        ];

        foreach ($alerts as $alertName => $alert) {
            if ($alert['check']()) {
                $this->triggerAlert($alertName, $alert['threshold']);
            }
        }
    }

    /**
     * Initialize metrics tracking
     */
    private function initializeMetrics(): void
    {
        // Set up database query listener
        DB::listen(function ($query) {
            foreach ($this->metrics as $monitoringId => &$metrics) {
                $metrics['database_queries'][] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'timestamp' => microtime(true),
                ];
            }
        });
    }

    /**
     * Start database monitoring
     */
    private function startDatabaseMonitoring(string $monitoringId): void
    {
        // Database monitoring is handled by the global DB listener
        // This method can be extended for more specific monitoring
    }

    /**
     * Start cache monitoring
     */
    private function startCacheMonitoring(string $monitoringId): void
    {
        // Mock cache monitoring - in production, you'd hook into cache events
        $this->metrics[$monitoringId]['cache_operations'] = [];
    }

    /**
     * Calculate cache hit rate
     */
    private function calculateCacheHitRate(array $cacheOperations): float
    {
        if (empty($cacheOperations)) {
            return 0;
        }

        $hits = count(array_filter($cacheOperations, fn ($op) => $op['type'] === 'hit'));

        return ($hits / count($cacheOperations)) * 100;
    }

    /**
     * Check performance thresholds
     */
    private function checkPerformanceThresholds(array $metrics): array
    {
        $issues = [];

        if ($metrics['total_time'] > $this->thresholds['response_time']) {
            $issues[] = [
                'type' => 'high_response_time',
                'value' => $metrics['total_time'],
                'threshold' => $this->thresholds['response_time'],
                'severity' => 'high',
            ];
        }

        if ($metrics['database_time'] > $this->thresholds['database_time']) {
            $issues[] = [
                'type' => 'slow_database_queries',
                'value' => $metrics['database_time'],
                'threshold' => $this->thresholds['database_time'],
                'severity' => 'medium',
            ];
        }

        $memoryUsageMB = $metrics['peak_memory'] / 1024 / 1024;
        if ($memoryUsageMB > $this->thresholds['memory_usage']) {
            $issues[] = [
                'type' => 'high_memory_usage',
                'value' => $memoryUsageMB,
                'threshold' => $this->thresholds['memory_usage'],
                'severity' => 'medium',
            ];
        }

        return $issues;
    }

    /**
     * Store metrics in database/cache
     */
    private function storeMetrics(string $monitoringId, array $metrics): void
    {
        // Store in cache for real-time access
        $cacheKey = 'performance_metrics_'.date('Y-m-d-H');
        $hourlyMetrics = Cache::get($cacheKey, []);
        $hourlyMetrics[] = $metrics;
        Cache::put($cacheKey, $hourlyMetrics, 3600);

        // Store in database for long-term analysis (implement as needed)
        // DB::table('performance_metrics')->insert($metrics);
    }

    /**
     * Log performance issues
     */
    private function logPerformanceIssues(string $monitoringId, array $metrics): void
    {
        Log::warning('Performance issues detected', [
            'monitoring_id' => $monitoringId,
            'request_uri' => $metrics['request_uri'],
            'total_time' => $metrics['total_time'],
            'database_time' => $metrics['database_time'],
            'memory_used' => $metrics['memory_used'],
            'issues' => $metrics['performance_issues'],
        ]);
    }

    /**
     * Get average response time
     */
    private function getAverageResponseTime(): float
    {
        $activeTimes = [];
        foreach ($this->metrics as $metric) {
            if (isset($metric['end_time'])) {
                $activeTimes[] = ($metric['end_time'] - $metric['start_time']) * 1000;
            }
        }

        return empty($activeTimes) ? 0 : array_sum($activeTimes) / count($activeTimes);
    }

    /**
     * Get database connection count
     */
    private function getDatabaseConnectionCount(): int
    {
        try {
            return DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get current cache hit rate
     */
    private function getCurrentCacheHitRate(): float
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $info = Redis::info();
                $hits = $info['keyspace_hits'] ?? 0;
                $misses = $info['keyspace_misses'] ?? 0;

                return ($hits + $misses) > 0 ? ($hits / ($hits + $misses)) * 100 : 0;
            }
        } catch (\Exception $e) {
            // Fallback calculation
        }

        return 0;
    }

    /**
     * Get current error rate
     */
    private function getCurrentErrorRate(): float
    {
        // This would need to be implemented based on your error tracking system
        return 0;
    }

    /**
     * Get concurrent user count
     */
    private function getConcurrentUserCount(): int
    {
        // This would need to be implemented based on your session tracking
        return count($this->metrics);
    }

    /**
     * Get stored metrics for analysis
     */
    private function getStoredMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $metrics = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $cacheKey = 'performance_metrics_'.$current->format('Y-m-d-H');
            $hourlyMetrics = Cache::get($cacheKey, []);
            $metrics = array_merge($metrics, $hourlyMetrics);
            $current->addHour();
        }

        return $metrics;
    }

    /**
     * Calculate summary metrics
     */
    private function calculateSummaryMetrics(array $metrics): array
    {
        if (empty($metrics)) {
            return [];
        }

        $responseTimes = array_column($metrics, 'total_time');
        $databaseTimes = array_column($metrics, 'database_time');
        $memoryUsage = array_column($metrics, 'memory_used');

        return [
            'total_requests' => count($metrics),
            'avg_response_time' => array_sum($responseTimes) / count($responseTimes),
            'p95_response_time' => $this->calculatePercentile($responseTimes, 95),
            'p99_response_time' => $this->calculatePercentile($responseTimes, 99),
            'avg_database_time' => array_sum($databaseTimes) / count($databaseTimes),
            'avg_memory_usage' => array_sum($memoryUsage) / count($memoryUsage),
            'error_count' => count(array_filter($metrics, fn ($m) => ! empty($m['performance_issues']))),
        ];
    }

    /**
     * Calculate performance trends
     */
    private function calculateTrends(array $metrics): array
    {
        // Group metrics by hour
        $hourlyGroups = [];
        foreach ($metrics as $metric) {
            $hour = date('Y-m-d H:00:00', $metric['start_time']);
            $hourlyGroups[$hour][] = $metric;
        }

        $trends = [];
        foreach ($hourlyGroups as $hour => $hourlyMetrics) {
            $responseTimes = array_column($hourlyMetrics, 'total_time');
            $trends[] = [
                'hour' => $hour,
                'request_count' => count($hourlyMetrics),
                'avg_response_time' => array_sum($responseTimes) / count($responseTimes),
                'error_count' => count(array_filter($hourlyMetrics, fn ($m) => ! empty($m['performance_issues']))),
            ];
        }

        return $trends;
    }

    /**
     * Get top slow requests
     */
    private function getTopSlowRequests(array $metrics): array
    {
        usort($metrics, fn ($a, $b) => $b['total_time'] <=> $a['total_time']);

        return array_slice(array_map(fn ($m) => [
            'uri' => $m['request_uri'],
            'method' => $m['request_method'],
            'response_time' => $m['total_time'],
            'database_time' => $m['database_time'],
            'memory_used' => $m['memory_used'],
        ], $metrics), 0, 10);
    }

    /**
     * Analyze errors
     */
    private function analyzeErrors(array $metrics): array
    {
        $errorMetrics = array_filter($metrics, fn ($m) => ! empty($m['performance_issues']));

        $errorTypes = [];
        foreach ($errorMetrics as $metric) {
            foreach ($metric['performance_issues'] as $issue) {
                $errorTypes[$issue['type']] = ($errorTypes[$issue['type']] ?? 0) + 1;
            }
        }

        return [
            'total_errors' => count($errorMetrics),
            'error_rate' => (count($errorMetrics) / count($metrics)) * 100,
            'error_types' => $errorTypes,
        ];
    }

    /**
     * Analyze resource usage
     */
    private function analyzeResourceUsage(array $metrics): array
    {
        $memoryUsage = array_column($metrics, 'memory_used');
        $databaseTimes = array_column($metrics, 'database_time');
        $queryCount = array_column($metrics, 'database_query_count');

        return [
            'avg_memory_usage' => array_sum($memoryUsage) / count($memoryUsage),
            'peak_memory_usage' => max($memoryUsage),
            'avg_database_time' => array_sum($databaseTimes) / count($databaseTimes),
            'avg_query_count' => array_sum($queryCount) / count($queryCount),
        ];
    }

    /**
     * Analyze user experience
     */
    private function analyzeUserExperience(array $metrics): array
    {
        $responseTimes = array_column($metrics, 'total_time');

        $goodExperience = count(array_filter($responseTimes, fn ($t) => $t < 1000)); // Under 1s
        $okExperience = count(array_filter($responseTimes, fn ($t) => $t >= 1000 && $t < 3000)); // 1-3s
        $poorExperience = count(array_filter($responseTimes, fn ($t) => $t >= 3000)); // Over 3s

        return [
            'good_experience_rate' => ($goodExperience / count($responseTimes)) * 100,
            'ok_experience_rate' => ($okExperience / count($responseTimes)) * 100,
            'poor_experience_rate' => ($poorExperience / count($responseTimes)) * 100,
        ];
    }

    /**
     * Generate executive summary
     */
    private function generateExecutiveSummary(array $analytics): array
    {
        $summary = $analytics['summary'];

        return [
            'overall_health' => $this->calculateOverallHealth($analytics),
            'key_metrics' => [
                'total_requests' => $summary['total_requests'],
                'avg_response_time' => round($summary['avg_response_time'], 2),
                'error_rate' => round($analytics['error_analysis']['error_rate'], 2),
                'user_satisfaction' => round($analytics['user_experience']['good_experience_rate'], 1),
            ],
            'status' => $this->determineSystemStatus($analytics),
        ];
    }

    /**
     * Generate recommendations
     */
    private function generateRecommendations(array $analytics): array
    {
        $recommendations = [];

        if ($analytics['summary']['avg_response_time'] > $this->thresholds['response_time']) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Average response time exceeds threshold. Consider optimizing database queries and implementing caching.',
            ];
        }

        if ($analytics['error_analysis']['error_rate'] > $this->thresholds['error_rate']) {
            $recommendations[] = [
                'type' => 'reliability',
                'priority' => 'high',
                'message' => 'Error rate is above acceptable threshold. Review error logs and implement fixes.',
            ];
        }

        if ($analytics['user_experience']['poor_experience_rate'] > 10) {
            $recommendations[] = [
                'type' => 'user_experience',
                'priority' => 'medium',
                'message' => 'More than 10% of users are experiencing poor performance. Focus on optimizing critical user paths.',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate alerts
     */
    private function generateAlerts(array $analytics): array
    {
        $alerts = [];

        if ($analytics['summary']['p99_response_time'] > $this->thresholds['response_time'] * 2) {
            $alerts[] = [
                'level' => 'critical',
                'message' => '99th percentile response time is critically high',
                'value' => $analytics['summary']['p99_response_time'],
            ];
        }

        return $alerts;
    }

    /**
     * Check Core Web Vitals thresholds
     */
    private function checkCoreWebVitalsThresholds(array $vitals): void
    {
        $thresholds = [
            'lcp' => 2500,  // 2.5 seconds
            'fid' => 100,   // 100ms
            'cls' => 0.1,    // 0.1
        ];

        foreach ($thresholds as $metric => $threshold) {
            if (isset($vitals[$metric]) && $vitals[$metric] > $threshold) {
                Log::warning('Core Web Vital threshold exceeded', [
                    'metric' => $metric,
                    'value' => $vitals[$metric],
                    'threshold' => $threshold,
                ]);
            }
        }
    }

    /**
     * Calculate vital statistics
     */
    private function calculateVitalStats(array $values): array
    {
        $values = array_filter($values, fn ($v) => $v !== null);

        if (empty($values)) {
            return [];
        }

        sort($values);

        return [
            'avg' => array_sum($values) / count($values),
            'median' => $values[intval(count($values) / 2)],
            'p75' => $this->calculatePercentile($values, 75),
            'p90' => $this->calculatePercentile($values, 90),
            'p95' => $this->calculatePercentile($values, 95),
            'p99' => $this->calculatePercentile($values, 99),
        ];
    }

    /**
     * Calculate percentile
     */
    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;

        return $values[$index] ?? 0;
    }

    /**
     * Calculate overall system health
     */
    private function calculateOverallHealth(array $analytics): string
    {
        $score = 100;

        // Deduct points for performance issues
        if ($analytics['summary']['avg_response_time'] > $this->thresholds['response_time']) {
            $score -= 20;
        }

        if ($analytics['error_analysis']['error_rate'] > $this->thresholds['error_rate']) {
            $score -= 30;
        }

        if ($analytics['user_experience']['poor_experience_rate'] > 10) {
            $score -= 15;
        }

        if ($score >= 90) {
            return 'excellent';
        }
        if ($score >= 75) {
            return 'good';
        }
        if ($score >= 60) {
            return 'fair';
        }

        return 'poor';
    }

    /**
     * Determine system status
     */
    private function determineSystemStatus(array $analytics): string
    {
        if ($analytics['error_analysis']['error_rate'] > 5) {
            return 'critical';
        }

        if ($analytics['summary']['avg_response_time'] > $this->thresholds['response_time'] * 1.5) {
            return 'warning';
        }

        return 'healthy';
    }

    /**
     * Trigger performance alert
     */
    private function triggerAlert(string $alertName, $threshold): void
    {
        Log::alert("Performance alert triggered: {$alertName}", [
            'alert' => $alertName,
            'threshold' => $threshold,
            'timestamp' => now()->toISOString(),
        ]);

        // Here you could integrate with external alerting systems
        // like Slack, PagerDuty, email notifications, etc.
    }
}
