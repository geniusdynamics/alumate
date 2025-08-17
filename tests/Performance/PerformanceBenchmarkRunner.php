<?php

namespace Tests\Performance;

use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PerformanceBenchmarkRunner extends TestCase
{
    use RefreshDatabase;

    private array $benchmarkResults = [];

    private array $performanceThresholds = [
        'homepage_load_time' => 3000, // 3 seconds
        'api_response_time' => 500,   // 500ms
        'database_query_time' => 100, // 100ms
        'cache_hit_rate' => 80,       // 80%
        'memory_usage' => 128,        // 128MB
        'concurrent_users' => 100,    // 100 concurrent users
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Create comprehensive test data
        $this->createBenchmarkTestData();

        // Initialize benchmark results
        $this->benchmarkResults = [
            'timestamp' => now()->toISOString(),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'benchmarks' => [],
        ];
    }

    public function test_run_complete_performance_benchmark_suite(): void
    {
        echo "\n=== Running Complete Performance Benchmark Suite ===\n";

        // Run all benchmark tests
        $this->runHomepageLoadBenchmark();
        $this->runDatabasePerformanceBenchmark();
        $this->runCachePerformanceBenchmark();
        $this->runAPIResponseBenchmark();
        $this->runMemoryUsageBenchmark();
        $this->runConcurrentUserBenchmark();
        $this->runAssetLoadingBenchmark();
        $this->runSearchPerformanceBenchmark();

        // Generate comprehensive report
        $this->generateBenchmarkReport();

        // Assert overall performance meets requirements
        $this->assertPerformanceRequirements();
    }

    private function runHomepageLoadBenchmark(): void
    {
        echo "Running homepage load benchmark...\n";

        $iterations = 10;
        $loadTimes = [];
        $memoryUsage = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            $response = $this->get('/');
            $response->assertStatus(200);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            $loadTimes[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsage[] = $endMemory - $startMemory;
        }

        $this->benchmarkResults['benchmarks']['homepage_load'] = [
            'iterations' => $iterations,
            'avg_load_time' => array_sum($loadTimes) / count($loadTimes),
            'min_load_time' => min($loadTimes),
            'max_load_time' => max($loadTimes),
            'p95_load_time' => $this->calculatePercentile($loadTimes, 95),
            'avg_memory_usage' => array_sum($memoryUsage) / count($memoryUsage),
            'threshold_met' => array_sum($loadTimes) / count($loadTimes) < $this->performanceThresholds['homepage_load_time'],
        ];

        echo "Homepage load benchmark completed\n";
    }

    private function runDatabasePerformanceBenchmark(): void
    {
        echo "Running database performance benchmark...\n";

        DB::flushQueryLog();

        $queries = [
            'simple_select' => fn () => Graduate::take(10)->get(),
            'complex_join' => fn () => Graduate::with(['course', 'applications.job.employer'])->take(10)->get(),
            'aggregation' => fn () => Graduate::selectRaw('course_id, COUNT(*) as count, AVG(CAST(JSON_EXTRACT(salary_range, "$.max") AS UNSIGNED)) as avg_salary')
                ->groupBy('course_id')->get(),
            'search_query' => fn () => Graduate::where('name', 'LIKE', '%John%')
                ->whereJsonContains('skills', 'PHP')->take(10)->get(),
            'pagination' => fn () => Graduate::paginate(50),
        ];

        $queryResults = [];

        foreach ($queries as $queryName => $queryFunction) {
            $iterations = 5;
            $queryTimes = [];

            for ($i = 0; $i < $iterations; $i++) {
                DB::flushQueryLog();

                $startTime = microtime(true);
                $queryFunction();
                $endTime = microtime(true);

                $queryTime = ($endTime - $startTime) * 1000;
                $queryTimes[] = $queryTime;

                $queryLog = DB::getQueryLog();
                $totalDbTime = array_sum(array_column($queryLog, 'time'));

                $queryResults[$queryName][] = [
                    'total_time' => $queryTime,
                    'db_time' => $totalDbTime,
                    'query_count' => count($queryLog),
                ];
            }

            $avgTime = array_sum($queryTimes) / count($queryTimes);
            $avgDbTime = array_sum(array_column($queryResults[$queryName], 'db_time')) / $iterations;
            $avgQueryCount = array_sum(array_column($queryResults[$queryName], 'query_count')) / $iterations;

            $this->benchmarkResults['benchmarks']['database'][$queryName] = [
                'avg_total_time' => $avgTime,
                'avg_db_time' => $avgDbTime,
                'avg_query_count' => $avgQueryCount,
                'threshold_met' => $avgDbTime < $this->performanceThresholds['database_query_time'],
            ];
        }

        echo "Database performance benchmark completed\n";
    }

    private function runCachePerformanceBenchmark(): void
    {
        echo "Running cache performance benchmark...\n";

        Cache::flush();

        $cacheOperations = [
            'simple_cache' => [
                'set' => fn () => Cache::put('test_key', 'test_value', 3600),
                'get' => fn () => Cache::get('test_key'),
            ],
            'complex_cache' => [
                'set' => fn () => Cache::put('complex_key', $this->getComplexData(), 3600),
                'get' => fn () => Cache::get('complex_key'),
            ],
            'cache_remember' => [
                'operation' => fn () => Cache::remember('remember_key', 3600, fn () => Graduate::take(100)->get()),
            ],
        ];

        foreach ($cacheOperations as $operationName => $operations) {
            $results = [];

            if (isset($operations['set']) && isset($operations['get'])) {
                // Test cache set performance
                $setTimes = [];
                for ($i = 0; $i < 10; $i++) {
                    $startTime = microtime(true);
                    $operations['set']();
                    $setTimes[] = (microtime(true) - $startTime) * 1000;
                }

                // Test cache get performance
                $getTimes = [];
                for ($i = 0; $i < 100; $i++) {
                    $startTime = microtime(true);
                    $operations['get']();
                    $getTimes[] = (microtime(true) - $startTime) * 1000;
                }

                $results = [
                    'avg_set_time' => array_sum($setTimes) / count($setTimes),
                    'avg_get_time' => array_sum($getTimes) / count($getTimes),
                ];
            } else {
                // Test cache remember performance
                $rememberTimes = [];
                for ($i = 0; $i < 5; $i++) {
                    Cache::forget('remember_key');
                    $startTime = microtime(true);
                    $operations['operation']();
                    $rememberTimes[] = (microtime(true) - $startTime) * 1000;
                }

                $results = [
                    'avg_remember_time' => array_sum($rememberTimes) / count($rememberTimes),
                ];
            }

            $this->benchmarkResults['benchmarks']['cache'][$operationName] = $results;
        }

        // Test cache hit rate
        $hitCount = 0;
        $totalRequests = 100;

        for ($i = 0; $i < $totalRequests; $i++) {
            $key = 'hit_test_'.($i % 10); // Create some cache hits

            if (Cache::has($key)) {
                $hitCount++;
            } else {
                Cache::put($key, "value_{$i}", 3600);
            }
        }

        $hitRate = ($hitCount / $totalRequests) * 100;

        $this->benchmarkResults['benchmarks']['cache']['hit_rate'] = [
            'hit_rate' => $hitRate,
            'threshold_met' => $hitRate >= $this->performanceThresholds['cache_hit_rate'],
        ];

        echo "Cache performance benchmark completed\n";
    }

    private function runAPIResponseBenchmark(): void
    {
        echo "Running API response benchmark...\n";

        $apiEndpoints = [
            '/api/homepage/statistics',
            '/api/homepage/testimonials',
            '/api/homepage/success-stories',
            '/api/graduates/search?q=developer',
            '/api/jobs/active',
        ];

        foreach ($apiEndpoints as $endpoint) {
            $responseTimes = [];
            $statusCodes = [];

            for ($i = 0; $i < 10; $i++) {
                $startTime = microtime(true);
                $response = $this->get($endpoint);
                $responseTime = (microtime(true) - $startTime) * 1000;

                $responseTimes[] = $responseTime;
                $statusCodes[] = $response->status();
            }

            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
            $successRate = (count(array_filter($statusCodes, fn ($code) => $code === 200)) / count($statusCodes)) * 100;

            $this->benchmarkResults['benchmarks']['api'][str_replace(['/', '?', '='], ['_', '_', '_'], $endpoint)] = [
                'avg_response_time' => $avgResponseTime,
                'min_response_time' => min($responseTimes),
                'max_response_time' => max($responseTimes),
                'success_rate' => $successRate,
                'threshold_met' => $avgResponseTime < $this->performanceThresholds['api_response_time'],
            ];
        }

        echo "API response benchmark completed\n";
    }

    private function runMemoryUsageBenchmark(): void
    {
        echo "Running memory usage benchmark...\n";

        $initialMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        // Simulate heavy operations
        $operations = [
            'large_dataset_load' => fn () => Graduate::with(['course', 'applications'])->take(1000)->get(),
            'multiple_queries' => function () {
                for ($i = 0; $i < 50; $i++) {
                    Graduate::inRandomOrder()->first();
                }
            },
            'cache_operations' => function () {
                for ($i = 0; $i < 100; $i++) {
                    Cache::put("memory_test_{$i}", str_repeat('x', 1000), 60);
                }
            },
        ];

        $memoryResults = [];

        foreach ($operations as $operationName => $operation) {
            $beforeMemory = memory_get_usage(true);

            $operation();

            $afterMemory = memory_get_usage(true);
            $memoryIncrease = $afterMemory - $beforeMemory;

            $memoryResults[$operationName] = [
                'memory_increase' => $memoryIncrease,
                'memory_increase_mb' => round($memoryIncrease / 1024 / 1024, 2),
            ];
        }

        $finalMemory = memory_get_usage(true);
        $finalPeakMemory = memory_get_peak_usage(true);

        $this->benchmarkResults['benchmarks']['memory'] = [
            'initial_memory' => $initialMemory,
            'final_memory' => $finalMemory,
            'peak_memory' => $finalPeakMemory,
            'memory_increase' => $finalMemory - $initialMemory,
            'memory_increase_mb' => round(($finalMemory - $initialMemory) / 1024 / 1024, 2),
            'peak_memory_mb' => round($finalPeakMemory / 1024 / 1024, 2),
            'operations' => $memoryResults,
            'threshold_met' => ($finalPeakMemory / 1024 / 1024) < $this->performanceThresholds['memory_usage'],
        ];

        echo "Memory usage benchmark completed\n";
    }

    private function runConcurrentUserBenchmark(): void
    {
        echo "Running concurrent user benchmark...\n";

        $concurrentUsers = [10, 25, 50, 100];
        $concurrentResults = [];

        foreach ($concurrentUsers as $userCount) {
            $startTime = microtime(true);
            $responses = [];
            $errors = 0;

            // Simulate concurrent requests
            for ($i = 0; $i < $userCount; $i++) {
                try {
                    $response = $this->get('/');
                    $responses[] = $response->status();
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            $totalTime = microtime(true) - $startTime;
            $successRate = (count(array_filter($responses, fn ($status) => $status === 200)) / $userCount) * 100;
            $requestsPerSecond = $userCount / $totalTime;

            $concurrentResults[$userCount] = [
                'total_time' => $totalTime,
                'success_rate' => $successRate,
                'requests_per_second' => $requestsPerSecond,
                'errors' => $errors,
                'avg_response_time' => ($totalTime / $userCount) * 1000,
            ];
        }

        $this->benchmarkResults['benchmarks']['concurrent_users'] = $concurrentResults;

        echo "Concurrent user benchmark completed\n";
    }

    private function runAssetLoadingBenchmark(): void
    {
        echo "Running asset loading benchmark...\n";

        $assets = [
            '/build/assets/app.js',
            '/build/assets/app.css',
            '/images/logo.png',
            '/images/hero-background.jpg',
        ];

        $assetResults = [];

        foreach ($assets as $asset) {
            $loadTimes = [];

            for ($i = 0; $i < 5; $i++) {
                $startTime = microtime(true);
                $response = $this->get($asset);
                $loadTime = (microtime(true) - $startTime) * 1000;

                $loadTimes[] = $loadTime;
            }

            $avgLoadTime = array_sum($loadTimes) / count($loadTimes);

            $assetResults[basename($asset)] = [
                'avg_load_time' => $avgLoadTime,
                'min_load_time' => min($loadTimes),
                'max_load_time' => max($loadTimes),
            ];
        }

        $this->benchmarkResults['benchmarks']['assets'] = $assetResults;

        echo "Asset loading benchmark completed\n";
    }

    private function runSearchPerformanceBenchmark(): void
    {
        echo "Running search performance benchmark...\n";

        $searchQueries = [
            'simple_name' => 'John',
            'skill_search' => 'PHP',
            'complex_search' => 'Software Engineer PHP Laravel',
            'location_search' => 'New York',
            'empty_search' => '',
        ];

        $searchResults = [];

        foreach ($searchQueries as $queryType => $query) {
            $searchTimes = [];

            for ($i = 0; $i < 5; $i++) {
                $startTime = microtime(true);

                $results = Graduate::where('name', 'LIKE', "%{$query}%")
                    ->orWhereJsonContains('skills', $query)
                    ->take(20)
                    ->get();

                $searchTime = (microtime(true) - $startTime) * 1000;
                $searchTimes[] = $searchTime;
            }

            $avgSearchTime = array_sum($searchTimes) / count($searchTimes);

            $searchResults[$queryType] = [
                'query' => $query,
                'avg_search_time' => $avgSearchTime,
                'min_search_time' => min($searchTimes),
                'max_search_time' => max($searchTimes),
            ];
        }

        $this->benchmarkResults['benchmarks']['search'] = $searchResults;

        echo "Search performance benchmark completed\n";
    }

    private function generateBenchmarkReport(): void
    {
        echo "\n=== Performance Benchmark Report ===\n";

        // Generate detailed report
        $reportPath = storage_path('logs/performance_benchmark_'.date('Y-m-d_H-i-s').'.json');
        File::put($reportPath, json_encode($this->benchmarkResults, JSON_PRETTY_PRINT));

        echo "Detailed report saved to: {$reportPath}\n\n";

        // Display summary
        $this->displayBenchmarkSummary();

        // Log results
        Log::info('Performance benchmark completed', $this->benchmarkResults);
    }

    private function displayBenchmarkSummary(): void
    {
        echo "=== Benchmark Summary ===\n";

        // Homepage performance
        if (isset($this->benchmarkResults['benchmarks']['homepage_load'])) {
            $homepage = $this->benchmarkResults['benchmarks']['homepage_load'];
            echo 'Homepage Load Time: '.number_format($homepage['avg_load_time'], 2).'ms ';
            echo '('.($homepage['threshold_met'] ? 'PASS' : 'FAIL').")\n";
        }

        // Database performance
        if (isset($this->benchmarkResults['benchmarks']['database'])) {
            echo "\nDatabase Performance:\n";
            foreach ($this->benchmarkResults['benchmarks']['database'] as $queryType => $results) {
                echo "  {$queryType}: ".number_format($results['avg_db_time'], 2).'ms ';
                echo '('.($results['threshold_met'] ? 'PASS' : 'FAIL').")\n";
            }
        }

        // Cache performance
        if (isset($this->benchmarkResults['benchmarks']['cache']['hit_rate'])) {
            $cacheHitRate = $this->benchmarkResults['benchmarks']['cache']['hit_rate'];
            echo "\nCache Hit Rate: ".number_format($cacheHitRate['hit_rate'], 1).'% ';
            echo '('.($cacheHitRate['threshold_met'] ? 'PASS' : 'FAIL').")\n";
        }

        // Memory usage
        if (isset($this->benchmarkResults['benchmarks']['memory'])) {
            $memory = $this->benchmarkResults['benchmarks']['memory'];
            echo "\nPeak Memory Usage: ".$memory['peak_memory_mb'].'MB ';
            echo '('.($memory['threshold_met'] ? 'PASS' : 'FAIL').")\n";
        }

        // API performance
        if (isset($this->benchmarkResults['benchmarks']['api'])) {
            echo "\nAPI Response Times:\n";
            foreach ($this->benchmarkResults['benchmarks']['api'] as $endpoint => $results) {
                echo "  {$endpoint}: ".number_format($results['avg_response_time'], 2).'ms ';
                echo '('.($results['threshold_met'] ? 'PASS' : 'FAIL').")\n";
            }
        }

        echo "\n";
    }

    private function assertPerformanceRequirements(): void
    {
        // Assert homepage load time
        if (isset($this->benchmarkResults['benchmarks']['homepage_load'])) {
            $homepageTime = $this->benchmarkResults['benchmarks']['homepage_load']['avg_load_time'];
            $this->assertLessThan(
                $this->performanceThresholds['homepage_load_time'],
                $homepageTime,
                "Homepage load time ({$homepageTime}ms) exceeds threshold ({$this->performanceThresholds['homepage_load_time']}ms)"
            );
        }

        // Assert database query performance
        if (isset($this->benchmarkResults['benchmarks']['database'])) {
            foreach ($this->benchmarkResults['benchmarks']['database'] as $queryType => $results) {
                $this->assertLessThan(
                    $this->performanceThresholds['database_query_time'],
                    $results['avg_db_time'],
                    "Database query '{$queryType}' time ({$results['avg_db_time']}ms) exceeds threshold"
                );
            }
        }

        // Assert cache hit rate
        if (isset($this->benchmarkResults['benchmarks']['cache']['hit_rate'])) {
            $hitRate = $this->benchmarkResults['benchmarks']['cache']['hit_rate']['hit_rate'];
            $this->assertGreaterThan(
                $this->performanceThresholds['cache_hit_rate'],
                $hitRate,
                "Cache hit rate ({$hitRate}%) below threshold ({$this->performanceThresholds['cache_hit_rate']}%)"
            );
        }

        // Assert memory usage
        if (isset($this->benchmarkResults['benchmarks']['memory'])) {
            $peakMemoryMB = $this->benchmarkResults['benchmarks']['memory']['peak_memory_mb'];
            $this->assertLessThan(
                $this->performanceThresholds['memory_usage'],
                $peakMemoryMB,
                "Peak memory usage ({$peakMemoryMB}MB) exceeds threshold ({$this->performanceThresholds['memory_usage']}MB)"
            );
        }

        // Assert API response times
        if (isset($this->benchmarkResults['benchmarks']['api'])) {
            foreach ($this->benchmarkResults['benchmarks']['api'] as $endpoint => $results) {
                $this->assertLessThan(
                    $this->performanceThresholds['api_response_time'],
                    $results['avg_response_time'],
                    "API endpoint '{$endpoint}' response time ({$results['avg_response_time']}ms) exceeds threshold"
                );
            }
        }
    }

    private function calculatePercentile(array $values, int $percentile): float
    {
        sort($values);
        $index = (int) ceil(($percentile / 100) * count($values)) - 1;

        return $values[$index] ?? 0;
    }

    private function getComplexData(): array
    {
        return [
            'graduates' => Graduate::take(100)->get()->toArray(),
            'statistics' => [
                'total_count' => Graduate::count(),
                'employed_count' => Graduate::where('employment_status->status', 'employed')->count(),
                'average_salary' => Graduate::avg('salary_range->max'),
            ],
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'cache_key' => 'complex_data_'.uniqid(),
            ],
        ];
    }

    private function createBenchmarkTestData(): void
    {
        // Create comprehensive test data for benchmarking
        $courses = Course::factory()->count(20)->create();

        Graduate::factory()->count(5000)->create([
            'course_id' => $courses->random()->id,
            'employment_status' => json_encode(['status' => 'employed']),
            'salary_range' => json_encode(['min' => 50000, 'max' => 120000]),
            'skills' => json_encode(['PHP', 'Laravel', 'JavaScript', 'Vue.js', 'MySQL']),
        ]);

        $employers = Employer::factory()->count(200)->create();
        Job::factory()->count(1000)->create([
            'employer_id' => $employers->random()->id,
            'course_id' => $courses->random()->id,
        ]);

        // Create some cached data
        Cache::put('benchmark_test_data', [
            'total_graduates' => 5000,
            'total_jobs' => 1000,
            'total_employers' => 200,
        ], 3600);
    }

    protected function tearDown(): void
    {
        // Clean up benchmark data
        Cache::flush();

        parent::tearDown();
    }
}
