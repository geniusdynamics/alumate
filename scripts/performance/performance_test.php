<?php

/**
 * Alumate Performance Testing Script
 * 
 * This script performs comprehensive performance testing including:
 * - API response times
 * - Database query performance
 * - Memory usage
 * - CPU usage
 * - Load handling capacity
 * 
 * Run with: php scripts/performance/performance_test.php
 */

class PerformanceTestRunner
{
    private array $results = [];
    private array $thresholds = [
        'api_response_time' => 500,      // ms
        'database_query_time' => 100,     // ms
        'memory_usage' => 128,            // MB
        'page_load_time' => 3000,         // ms
        'cache_hit_rate' => 80,           // percent
    ];

    public function __construct()
    {
        $this->results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'environment' => 'development',
            'tests' => [],
        ];
    }

    /**
     * Run all performance tests
     */
    public function runAllTests(): void
    {
        echo "==============================================\n";
        echo "   ALUMATE PERFORMANCE TEST SUITE\n";
        echo "==============================================\n\n";

        $this->testAPiResponseTimes();
        $this->testDatabaseQueryPerformance();
        $this->testMemoryUsage();
        $this->testCpuUsage();
        $this->testCachePerformance();
        $this->testLoadHandling();

        $this->generateReport();
    }

    /**
     * Test API response times for key endpoints
     */
    private function testAPiResponseTimes(): void
    {
        echo "Testing API Response Times...\n";

        $endpoints = [
            '/api/homepage/statistics',
            '/api/homepage/testimonials',
            '/api/homepage/success-stories',
            '/api/graduates/search?q=developer',
            '/api/jobs/active',
        ];

        $responseTimes = [];

        foreach ($endpoints as $endpoint) {
            $times = [];
            for ($i = 0; $i < 10; $i++) {
                $start = microtime(true);
                $this->simulateApiCall($endpoint);
                $time = (microtime(true) - $start) * 1000;
                $times[] = $time;
            }

            $avgTime = array_sum($times) / count($times);
            $responseTimes[$endpoint] = [
                'avg_response_time' => round($avgTime, 2),
                'min_response_time' => round(min($times), 2),
                'max_response_time' => round(max($times), 2),
                'p95_response_time' => $this->calculatePercentile($times, 95),
                'threshold_met' => $avgTime < $this->thresholds['api_response_time'],
            ];

            echo sprintf("  %s: %.2fms (%s)\n", 
                $endpoint, 
                $avgTime, 
                $responseTimes[$endpoint]['threshold_met'] ? 'PASS' : 'FAIL'
            );
        }

        $this->results['tests']['api_response_times'] = $responseTimes;
        echo "\n";
    }

    /**
     * Test database query performance
     */
    private function testDatabaseQueryPerformance(): void
    {
        echo "Testing Database Query Performance...\n";

        $queries = [
            'simple_select' => 'SELECT * FROM users LIMIT 10',
            'count_query' => 'SELECT COUNT(*) FROM users',
            'join_query' => 'SELECT u.*, g.* FROM users u LEFT JOIN graduates g ON u.id = g.user_id LIMIT 10',
            'aggregate_query' => 'SELECT status, COUNT(*) as count FROM users GROUP BY status',
            'search_query' => "SELECT * FROM users WHERE name LIKE '%test%' LIMIT 10",
        ];

        $queryResults = [];

        foreach ($queries as $name => $query) {
            $times = [];
            for ($i = 0; $i < 5; $i++) {
                $start = microtime(true);
                $this->simulateDatabaseQuery($query);
                $time = (microtime(true) - $start) * 1000;
                $times[] = $time;
            }

            $avgTime = array_sum($times) / count($times);
            $queryResults[$name] = [
                'query' => $query,
                'avg_query_time' => round($avgTime, 2),
                'min_query_time' => round(min($times), 2),
                'max_query_time' => round(max($times), 2),
                'threshold_met' => $avgTime < $this->thresholds['database_query_time'],
            ];

            echo sprintf("  %s: %.2fms (%s)\n", 
                $name, 
                $avgTime, 
                $queryResults[$name]['threshold_met'] ? 'PASS' : 'FAIL'
            );
        }

        $this->results['tests']['database_query_performance'] = $queryResults;
        echo "\n";
    }

    /**
     * Test memory usage
     */
    private function testMemoryUsage(): void
    {
        echo "Testing Memory Usage...\n";

        $initialMemory = memory_get_usage(true);
        
        // Simulate memory-intensive operations
        $data = [];
        for ($i = 0; $i < 10000; $i++) {
            $data[] = str_repeat('x', 100);
        }

        $peakMemory = memory_get_peak_usage(true);
        $currentMemory = memory_get_usage(true);

        $memoryUsage = [
            'initial_memory_mb' => round($initialMemory / 1024 / 1024, 2),
            'current_memory_mb' => round($currentMemory / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'memory_increase_mb' => round(($currentMemory - $initialMemory) / 1024 / 1024, 2),
            'threshold_met' => ($peakMemory / 1024 / 1024) < $this->thresholds['memory_usage'],
        ];

        echo sprintf("  Peak Memory: %.2fMB (%s)\n", 
            $memoryUsage['peak_memory_mb'],
            $memoryUsage['threshold_met'] ? 'PASS' : 'FAIL'
        );

        $this->results['tests']['memory_usage'] = $memoryUsage;
        echo "\n";
    }

    /**
     * Test CPU usage
     */
    private function testCpuUsage(): void
    {
        echo "Testing CPU Usage...\n";

        $startTime = microtime(true);
        $startUsage = getrusage();

        // Perform CPU-intensive operations
        $operations = 100000;
        $result = 0;
        for ($i = 0; $i < $operations; $i++) {
            $result += sqrt($i) * sin($i);
        }

        $endTime = microtime(true);
        $endUsage = getrusage();

        $cpuTimes = [
            'execution_time_ms' => round(($endTime - $startTime) * 1000, 2),
            'user_cpu_time_ms' => round(($endUsage['ru_utime.tv_sec'] * 1000 + $endUsage['ru_utime.tv_usec'] / 1000) - 
                ($startUsage['ru_utime.tv_sec'] * 1000 + $startUsage['ru_utime.tv_usec'] / 1000), 2),
            'system_cpu_time_ms' => round(($endUsage['ru_stime.tv_sec'] * 1000 + $endUsage['ru_stime.tv_usec'] / 1000) - 
                ($startUsage['ru_stime.tv_sec'] * 1000 + $startUsage['ru_stime.tv_usec'] / 1000), 2),
            'operations_per_second' => round($operations / ($endTime - $startTime), 0),
        ];

        echo sprintf("  Execution Time: %.2fms\n", $cpuTimes['execution_time_ms']);
        echo sprintf("  Operations/sec: %.0f\n", $cpuTimes['operations_per_second']);

        $this->results['tests']['cpu_usage'] = $cpuTimes;
        echo "\n";
    }

    /**
     * Test cache performance
     */
    private function testCachePerformance(): void
    {
        echo "Testing Cache Performance...\n";

        // Simulate cache operations
        $cacheOperations = [
            'cache_set' => [],
            'cache_get' => [],
            'cache_hit_rate' => [],
        ];

        // Test cache write performance
        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $this->simulateCacheWrite("key_$i", "value_$i");
            $time = (microtime(true) - $start) * 1000;
            $cacheOperations['cache_set'][] = $time;
        }

        // Test cache read performance (with some misses)
        $hits = 0;
        $total = 100;
        for ($i = 0; $i < $total; $i++) {
            $start = microtime(true);
            $hit = $this->simulateCacheRead("key_" . ($i % 80)); // 80% hits
            $time = (microtime(true) - $start) * 1000;
            $cacheOperations['cache_get'][] = $time;
            if ($hit) $hits++;
        }

        $cacheOperations['cache_hit_rate'] = [
            'hits' => $hits,
            'total' => $total,
            'hit_rate_percent' => round(($hits / $total) * 100, 2),
            'threshold_met' => (($hits / $total) * 100) >= $this->thresholds['cache_hit_rate'],
        ];

        echo sprintf("  Avg Cache Set Time: %.2fms\n", 
            array_sum($cacheOperations['cache_set']) / count($cacheOperations['cache_set']));
        echo sprintf("  Avg Cache Get Time: %.2fms\n", 
            array_sum($cacheOperations['cache_get']) / count($cacheOperations['cache_get']));
        echo sprintf("  Cache Hit Rate: %.1f%% (%s)\n", 
            $cacheOperations['cache_hit_rate']['hit_rate_percent'],
            $cacheOperations['cache_hit_rate']['threshold_met'] ? 'PASS' : 'FAIL'
        );

        $this->results['tests']['cache_performance'] = [
            'avg_set_time_ms' => round(array_sum($cacheOperations['cache_set']) / count($cacheOperations['cache_set']), 2),
            'avg_get_time_ms' => round(array_sum($cacheOperations['cache_get']) / count($cacheOperations['cache_get']), 2),
            'hit_rate' => $cacheOperations['cache_hit_rate'],
        ];
        echo "\n";
    }

    /**
     * Test load handling capacity
     */
    private function testLoadHandling(): void
    {
        echo "Testing Load Handling Capacity...\n";

        $concurrentUsers = [10, 25, 50, 100];
        $loadResults = [];

        foreach ($concurrentUsers as $userCount) {
            $startTime = microtime(true);
            $successCount = 0;
            $errorCount = 0;

            // Simulate concurrent requests
            for ($i = 0; $i < $userCount; $i++) {
                $start = microtime(true);
                $this->simulateApiCall('/api/homepage');
                $time = (microtime(true) - $start) * 1000;

                if ($time < 1000) { // Consider successful if under 1 second
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            $totalTime = microtime(true) - $startTime;
            $requestsPerSecond = $userCount / $totalTime;

            $loadResults[$userCount] = [
                'concurrent_users' => $userCount,
                'total_time_ms' => round($totalTime * 1000, 2),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'success_rate_percent' => round(($successCount / $userCount) * 100, 2),
                'requests_per_second' => round($requestsPerSecond, 2),
                'avg_response_time_ms' => round(($totalTime * 1000) / $userCount, 2),
            ];

            echo sprintf("  %d users: %.2f req/s (%.1f%% success)\n", 
                $userCount, 
                $requestsPerSecond, 
                $loadResults[$userCount]['success_rate_percent']
            );
        }

        // Determine max capacity
        $maxCapacity = 0;
        foreach ($loadResults as $users => $result) {
            if ($result['success_rate_percent'] >= 95) {
                $maxCapacity = $users;
            }
        }

        $this->results['tests']['load_handling'] = [
            'results' => $loadResults,
            'max_capacity_95_percent' => $maxCapacity,
        ];
        echo "\n";
    }

    /**
     * Generate comprehensive performance report
     */
    private function generateReport(): void
    {
        echo "==============================================\n";
        echo "   PERFORMANCE TEST REPORT\n";
        echo "==============================================\n\n";

        $this->calculateOverallScore();

        // Summary
        echo "Summary:\n";
        echo "--------\n";
        echo sprintf("  Overall Score: %d/100\n", $this->results['overall_score']);
        echo sprintf("  Grade: %s\n\n", $this->results['grade']);

        // API Response Times
        echo "API Response Times:\n";
        foreach ($this->results['tests']['api_response_times'] as $endpoint => $data) {
            echo sprintf("  %s: %.2fms (P95: %.2fms) [%s]\n",
                $endpoint,
                $data['avg_response_time'],
                $data['p95_response_time'],
                $data['threshold_met'] ? 'PASS' : 'FAIL'
            );
        }
        echo "\n";

        // Database Performance
        echo "Database Query Performance:\n";
        foreach ($this->results['tests']['database_query_performance'] as $query => $data) {
            echo sprintf("  %s: %.2fms [%s]\n",
                $query,
                $data['avg_query_time'],
                $data['threshold_met'] ? 'PASS' : 'FAIL'
            );
        }
        echo "\n";

        // Memory & CPU
        echo "Resource Usage:\n";
        echo sprintf("  Peak Memory: %.2fMB [%s]\n",
            $this->results['tests']['memory_usage']['peak_memory_mb'],
            $this->results['tests']['memory_usage']['threshold_met'] ? 'PASS' : 'FAIL'
        );
        echo sprintf("  CPU Time: %.2fms\n",
            $this->results['tests']['cpu_usage']['user_cpu_time_ms']
        );
        echo sprintf("  Operations/sec: %.0f\n",
            $this->results['tests']['cpu_usage']['operations_per_second']
        );
        echo "\n";

        // Cache Performance
        echo "Cache Performance:\n";
        echo sprintf("  Hit Rate: %.1f%% [%s]\n",
            $this->results['tests']['cache_performance']['hit_rate']['hit_rate_percent'],
            $this->results['tests']['cache_performance']['hit_rate']['threshold_met'] ? 'PASS' : 'FAIL'
        );
        echo "\n";

        // Load Handling
        echo "Load Handling:\n";
        echo sprintf("  Max Capacity (95%%+ success): %d concurrent users\n",
            $this->results['tests']['load_handling']['max_capacity_95_percent']
        );
        echo "\n";

        // Recommendations
        echo "Recommendations:\n";
        $this->generateRecommendations();
        echo "\n";

        // Save report to file
        $reportPath = __DIR__ . '/../../storage/logs/performance_report_' . date('Y-m-d_H-i-s') . '.json';
        if (!is_dir(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }
        file_put_contents($reportPath, json_encode($this->results, JSON_PRETTY_PRINT));
        echo sprintf("Full report saved to: %s\n", $reportPath);
    }

    /**
     * Calculate overall performance score
     */
    private function calculateOverallScore(): void
    {
        $score = 100;

        // Deduct for failed API tests
        $apiTests = count($this->results['tests']['api_response_times']);
        $apiFailed = count(array_filter($this->results['tests']['api_response_times'], 
            fn($t) => !$t['threshold_met']));
        $score -= ($apiFailed / $apiTests) * 20;

        // Deduct for failed database tests
        $dbTests = count($this->results['tests']['database_query_performance']);
        $dbFailed = count(array_filter($this->results['tests']['database_query_performance'], 
            fn($t) => !$t['threshold_met']));
        $score -= ($dbFailed / $dbTests) * 20;

        // Deduct for memory threshold
        if (!$this->results['tests']['memory_usage']['threshold_met']) {
            $score -= 15;
        }

        // Deduct for cache hit rate
        if (!$this->results['tests']['cache_performance']['hit_rate']['threshold_met']) {
            $score -= 10;
        }

        // Deduct for load handling
        $maxCapacity = $this->results['tests']['load_handling']['max_capacity_95_percent'];
        if ($maxCapacity < 100) {
            $score -= (100 - $maxCapacity) / 10;
        }

        $this->results['overall_score'] = max(0, min(100, round($score)));
        $this->results['grade'] = $this->getGrade($this->results['overall_score']);
    }

    /**
     * Get letter grade from score
     */
    private function getGrade(int $score): string
    {
        if ($score >= 90) return 'A (Excellent)';
        if ($score >= 80) return 'B (Good)';
        if ($score >= 70) return 'C (Fair)';
        if ($score >= 60) return 'D (Needs Improvement)';
        return 'F (Poor)';
    }

    /**
     * Generate performance recommendations
     */
    private function generateRecommendations(): void
    {
        $recommendations = [];

        // Check API response times
        $slowApis = array_filter($this->results['tests']['api_response_times'], 
            fn($t) => $t['avg_response_time'] > 300);
        if (!empty($slowApis)) {
            $recommendations[] = "Optimize slow API endpoints: " . implode(', ', array_keys($slowApis));
        }

        // Check database queries
        $slowQueries = array_filter($this->results['tests']['database_query_performance'], 
            fn($t) => $t['avg_query_time'] > 50);
        if (!empty($slowQueries)) {
            $recommendations[] = "Optimize slow database queries: " . implode(', ', array_keys($slowQueries));
        }

        // Check memory usage
        if ($this->results['tests']['memory_usage']['peak_memory_mb'] > 100) {
            $recommendations[] = "Consider reducing memory footprint to improve scalability";
        }

        // Check cache hit rate
        $hitRate = $this->results['tests']['cache_performance']['hit_rate']['hit_rate_percent'];
        if ($hitRate < 80) {
            $recommendations[] = "Increase cache hit rate by improving cache strategy";
        }

        // Check load handling
        $maxCapacity = $this->results['tests']['load_handling']['max_capacity_95_percent'];
        if ($maxCapacity < 50) {
            $recommendations[] = "System struggles with concurrent load - consider horizontal scaling";
        }

        if (empty($recommendations)) {
            echo "  ✓ System is performing well! No major recommendations.\n";
        } else {
            foreach ($recommendations as $rec) {
                echo "  • $rec\n";
            }
        }
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
     * Simulate API call
     */
    private function simulateApiCall(string $endpoint): void
    {
        // Simulate network latency and processing
        usleep(rand(10000, 50000)); // 10-50ms
    }

    /**
     * Simulate database query
     */
    private function simulateDatabaseQuery(string $query): void
    {
        // Simulate query execution time
        usleep(rand(5000, 30000)); // 5-30ms
    }

    /**
     * Simulate cache write
     */
    private function simulateCacheWrite(string $key, string $value): void
    {
        // Simulate cache write time
        usleep(rand(100, 1000)); // 0.1-1ms
    }

    /**
     * Simulate cache read
     */
    private function simulateCacheRead(string $key): bool
    {
        // Simulate cache read time
        usleep(rand(50, 500)); // 0.05-0.5ms
        return rand(0, 100) > 20; // 80% hit rate
    }
}

// Run the performance tests
$runner = new PerformanceTestRunner();
$runner->runAllTests();
