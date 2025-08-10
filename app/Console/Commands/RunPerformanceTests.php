<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\DatabaseOptimizationService;
use App\Services\CachingStrategyService;
use App\Services\PerformanceMonitoringService;

class RunPerformanceTests extends Command
{
    protected $signature = 'performance:test 
                           {--suite=all : Test suite to run (all, load, database, cache, accessibility)}
                           {--report : Generate detailed performance report}
                           {--optimize : Run optimizations after tests}
                           {--concurrent=50 : Number of concurrent users for load testing}
                           {--duration=60 : Duration in seconds for load testing}';

    protected $description = 'Run comprehensive performance tests for the homepage enhancement';

    private DatabaseOptimizationService $dbOptimization;
    private CachingStrategyService $caching;
    private PerformanceMonitoringService $monitoring;
    private array $testResults = [];

    public function __construct(
        DatabaseOptimizationService $dbOptimization,
        CachingStrategyService $caching,
        PerformanceMonitoringService $monitoring
    ) {
        parent::__construct();
        $this->dbOptimization = $dbOptimization;
        $this->caching = $caching;
        $this->monitoring = $monitoring;
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Performance Test Suite for Homepage Enhancement');
        $this->info('=' . str_repeat('=', 60));

        $suite = $this->option('suite');
        $startTime = microtime(true);

        try {
            // Initialize performance monitoring
            $this->initializePerformanceMonitoring();

            // Run test suites based on selection
            switch ($suite) {
                case 'all':
                    $this->runAllTests();
                    break;
                case 'load':
                    $this->runLoadTests();
                    break;
                case 'database':
                    $this->runDatabaseTests();
                    break;
                case 'cache':
                    $this->runCacheTests();
                    break;
                case 'accessibility':
                    $this->runAccessibilityTests();
                    break;
                default:
                    $this->error("Unknown test suite: {$suite}");
                    return 1;
            }

            // Generate report if requested
            if ($this->option('report')) {
                $this->generatePerformanceReport();
            }

            // Run optimizations if requested
            if ($this->option('optimize')) {
                $this->runOptimizations();
            }

            $totalTime = microtime(true) - $startTime;
            $this->displaySummary($totalTime);

            return 0;

        } catch (\Exception $e) {
            $this->error("Performance tests failed: {$e->getMessage()}");
            Log::error('Performance tests failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function initializePerformanceMonitoring(): void
    {
        $this->info('🔧 Initializing performance monitoring...');
        
        // Clear previous test data
        $this->call('cache:clear');
        
        // Warm up caches
        $this->caching->warmCache();
        
        // Create database indexes for optimal performance
        $this->dbOptimization->createOptimizedIndexes();
        
        $this->info('✅ Performance monitoring initialized');
    }

    private function runAllTests(): void
    {
        $this->info('🧪 Running complete performance test suite...');
        
        $this->runDatabaseTests();
        $this->runCacheTests();
        $this->runLoadTests();
        $this->runAccessibilityTests();
        $this->runJavaScriptTests();
    }

    private function runLoadTests(): void
    {
        $this->info('⚡ Running load performance tests...');
        
        $concurrent = $this->option('concurrent');
        $duration = $this->option('duration');
        
        $this->line("Testing with {$concurrent} concurrent users for {$duration} seconds");
        
        // Run PHP load tests
        $exitCode = Artisan::call('test', [
            '--filter' => 'LoadTestRunner',
            '--testsuite' => 'Performance'
        ]);
        
        $this->testResults['load_tests'] = [
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'concurrent_users' => $concurrent,
            'duration' => $duration,
            'output' => Artisan::output()
        ];
        
        if ($exitCode === 0) {
            $this->info('✅ Load tests passed');
        } else {
            $this->error('❌ Load tests failed');
            $this->line(Artisan::output());
        }
    }

    private function runDatabaseTests(): void
    {
        $this->info('🗄️  Running database performance tests...');
        
        // Run database performance tests
        $exitCode = Artisan::call('test', [
            '--filter' => 'DatabasePerformanceTest',
            '--testsuite' => 'Performance'
        ]);
        
        // Get database optimization analysis
        $dbAnalysis = $this->dbOptimization->analyzeQueryPerformance();
        
        $this->testResults['database_tests'] = [
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'analysis' => $dbAnalysis,
            'output' => Artisan::output()
        ];
        
        if ($exitCode === 0) {
            $this->info('✅ Database tests passed');
        } else {
            $this->error('❌ Database tests failed');
        }
        
        // Display database analysis
        $this->displayDatabaseAnalysis($dbAnalysis);
    }

    private function runCacheTests(): void
    {
        $this->info('💾 Running cache performance tests...');
        
        // Get cache metrics before tests
        $cacheMetrics = $this->caching->getCacheMetrics();
        
        // Run homepage performance tests (includes caching)
        $exitCode = Artisan::call('test', [
            '--filter' => 'HomepagePerformanceTest',
            '--testsuite' => 'Performance'
        ]);
        
        $this->testResults['cache_tests'] = [
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'metrics' => $cacheMetrics,
            'output' => Artisan::output()
        ];
        
        if ($exitCode === 0) {
            $this->info('✅ Cache tests passed');
        } else {
            $this->error('❌ Cache tests failed');
        }
        
        // Display cache metrics
        $this->displayCacheMetrics($cacheMetrics);
    }

    private function runAccessibilityTests(): void
    {
        $this->info('♿ Running accessibility compliance tests...');
        
        $exitCode = Artisan::call('test', [
            '--filter' => 'AccessibilityComplianceTest',
            '--testsuite' => 'Performance'
        ]);
        
        $this->testResults['accessibility_tests'] = [
            'status' => $exitCode === 0 ? 'passed' : 'failed',
            'output' => Artisan::output()
        ];
        
        if ($exitCode === 0) {
            $this->info('✅ Accessibility tests passed');
        } else {
            $this->error('❌ Accessibility tests failed');
        }
    }

    private function runJavaScriptTests(): void
    {
        $this->info('🟨 Running JavaScript performance tests...');
        
        // Run Vitest performance tests
        $process = proc_open(
            'npm run test:run -- tests/Js/performance',
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );
        
        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            $exitCode = proc_close($process);
            
            $this->testResults['javascript_tests'] = [
                'status' => $exitCode === 0 ? 'passed' : 'failed',
                'output' => $output,
                'error' => $error
            ];
            
            if ($exitCode === 0) {
                $this->info('✅ JavaScript tests passed');
            } else {
                $this->error('❌ JavaScript tests failed');
                if ($error) {
                    $this->line($error);
                }
            }
        } else {
            $this->warn('⚠️  Could not run JavaScript tests - npm not available');
        }
    }

    private function runOptimizations(): void
    {
        $this->info('🔧 Running performance optimizations...');
        
        // Database optimizations
        $this->line('Optimizing database...');
        $this->dbOptimization->optimizeDatabaseConnection();
        $this->dbOptimization->createOptimizedIndexes();
        
        // Cache optimizations
        $this->line('Optimizing cache...');
        $this->caching->optimizeCacheConfiguration();
        $this->caching->preloadCriticalData();
        
        // Clear and warm caches
        $this->call('cache:clear');
        $this->call('config:cache');
        $this->call('route:cache');
        $this->call('view:cache');
        
        $this->caching->warmCache();
        
        $this->info('✅ Optimizations completed');
    }

    private function generatePerformanceReport(): void
    {
        $this->info('📊 Generating performance report...');
        
        $reportData = [
            'timestamp' => now()->toISOString(),
            'test_configuration' => [
                'suite' => $this->option('suite'),
                'concurrent_users' => $this->option('concurrent'),
                'duration' => $this->option('duration')
            ],
            'test_results' => $this->testResults,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'performance_metrics' => $this->monitoring->getRealTimeMetrics()
        ];
        
        $reportPath = storage_path('logs/performance_report_' . date('Y-m-d_H-i-s') . '.json');
        File::put($reportPath, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $this->info("📄 Performance report saved to: {$reportPath}");
        
        // Generate HTML report
        $this->generateHtmlReport($reportData, $reportPath);
    }

    private function generateHtmlReport(array $reportData, string $jsonPath): void
    {
        $htmlPath = str_replace('.json', '.html', $jsonPath);
        
        $html = $this->buildHtmlReport($reportData);
        File::put($htmlPath, $html);
        
        $this->info("🌐 HTML report saved to: {$htmlPath}");
    }

    private function buildHtmlReport(array $data): string
    {
        $timestamp = $data['timestamp'];
        $testResults = $data['test_results'];
        $metrics = $data['performance_metrics'];
        
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Performance Test Report - {$timestamp}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .metric-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric-card { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #007bff; }
        .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
        .metric-label { color: #666; font-size: 14px; }
        .status-passed { color: #28a745; }
        .status-failed { color: #dc3545; }
        .test-section { margin: 30px 0; }
        .test-section h3 { border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>🚀 Performance Test Report</h1>
            <p>Generated on: {$timestamp}</p>
        </div>
        
        <div class='metric-grid'>
            <div class='metric-card'>
                <div class='metric-value'>{$metrics['active_requests']}</div>
                <div class='metric-label'>Active Requests</div>
            </div>
            <div class='metric-card'>
                <div class='metric-value'>" . number_format($metrics['average_response_time'], 2) . "ms</div>
                <div class='metric-label'>Avg Response Time</div>
            </div>
            <div class='metric-card'>
                <div class='metric-value'>" . number_format($metrics['current_memory_usage'] / 1024 / 1024, 2) . "MB</div>
                <div class='metric-label'>Memory Usage</div>
            </div>
            <div class='metric-card'>
                <div class='metric-value'>{$metrics['concurrent_users']}</div>
                <div class='metric-label'>Concurrent Users</div>
            </div>
        </div>
        
        <div class='test-section'>
            <h3>Test Results Summary</h3>
            " . $this->buildTestResultsHtml($testResults) . "
        </div>
        
        <div class='test-section'>
            <h3>System Information</h3>
            <pre>" . json_encode($data['system_info'], JSON_PRETTY_PRINT) . "</pre>
        </div>
    </div>
</body>
</html>";
    }

    private function buildTestResultsHtml(array $testResults): string
    {
        $html = '<ul>';
        
        foreach ($testResults as $testName => $result) {
            $statusClass = $result['status'] === 'passed' ? 'status-passed' : 'status-failed';
            $statusIcon = $result['status'] === 'passed' ? '✅' : '❌';
            
            $html .= "<li><strong>{$statusIcon} " . ucwords(str_replace('_', ' ', $testName)) . "</strong> - <span class='{$statusClass}'>" . ucfirst($result['status']) . "</span></li>";
        }
        
        $html .= '</ul>';
        
        return $html;
    }

    private function displayDatabaseAnalysis(array $analysis): void
    {
        $this->line('');
        $this->line('📊 Database Performance Analysis:');
        $this->line("Total Queries: {$analysis['total_queries']}");
        $this->line("Average Query Time: " . number_format($analysis['average_time'], 2) . "ms");
        $this->line("Slow Queries: {$analysis['slow_queries_count']}");
        
        if (!empty($analysis['recommendations'])) {
            $this->line('');
            $this->line('💡 Recommendations:');
            foreach ($analysis['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }
    }

    private function displayCacheMetrics(array $metrics): void
    {
        $this->line('');
        $this->line('💾 Cache Performance Metrics:');
        
        foreach ($metrics as $key => $metric) {
            if (is_array($metric) && isset($metric['hit_rate'])) {
                $this->line("{$key}: " . number_format($metric['hit_rate'], 1) . "% hit rate");
            }
        }
    }

    private function displaySummary(float $totalTime): void
    {
        $this->line('');
        $this->info('=' . str_repeat('=', 60));
        $this->info('📋 Performance Test Summary');
        $this->info('=' . str_repeat('=', 60));
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->testResults as $testName => $result) {
            $status = $result['status'] === 'passed' ? '✅ PASSED' : '❌ FAILED';
            $this->line(sprintf('%-30s %s', ucwords(str_replace('_', ' ', $testName)) . ':', $status));
            
            if ($result['status'] === 'passed') {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $this->line('');
        $this->line("Total Tests: " . ($passed + $failed));
        $this->line("Passed: {$passed}");
        $this->line("Failed: {$failed}");
        $this->line("Duration: " . number_format($totalTime, 2) . " seconds");
        
        if ($failed === 0) {
            $this->info('🎉 All performance tests passed!');
        } else {
            $this->warn("⚠️  {$failed} test(s) failed. Check the detailed output above.");
        }
        
        $this->info('=' . str_repeat('=', 60));
    }
}