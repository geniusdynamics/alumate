<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Tests\TestReportGenerator;

class RunComprehensiveTests extends Command
{
    protected $signature = 'test:comprehensive 
                           {--suite=all : Test suite to run (all, unit, integration, feature, e2e, performance, security)}
                           {--coverage : Generate coverage report}
                           {--report : Generate comprehensive test report}
                           {--parallel : Run tests in parallel}
                           {--stop-on-failure : Stop on first failure}';

    protected $description = 'Run comprehensive test suite with reporting and coverage analysis';

    protected TestReportGenerator $reportGenerator;

    public function __construct(TestReportGenerator $reportGenerator)
    {
        parent::__construct();
        $this->reportGenerator = $reportGenerator;
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Comprehensive Test Suite');
        $this->newLine();

        $suite = $this->option('suite');
        $withCoverage = $this->option('coverage');
        $generateReport = $this->option('report');
        $parallel = $this->option('parallel');
        $stopOnFailure = $this->option('stop-on-failure');

        // Prepare test environment
        $this->prepareTestEnvironment();

        // Run test suites
        $results = [];

        if ($suite === 'all') {
            $results = $this->runAllTestSuites($withCoverage, $parallel, $stopOnFailure);
        } else {
            $results[$suite] = $this->runTestSuite($suite, $withCoverage, $parallel, $stopOnFailure);
        }

        // Display results
        $this->displayResults($results);

        // Generate comprehensive report if requested
        if ($generateReport) {
            $this->generateComprehensiveReport();
        }

        // Determine exit code
        $hasFailures = collect($results)->contains(function ($result) {
            return $result['exit_code'] !== 0;
        });

        return $hasFailures ? 1 : 0;
    }

    protected function prepareTestEnvironment(): void
    {
        $this->info('📋 Preparing test environment...');

        // Ensure test database is fresh
        $this->call('migrate:fresh', ['--env' => 'testing']);

        // Clear caches
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        // Ensure reports directory exists
        if (! is_dir(base_path('tests/reports'))) {
            mkdir(base_path('tests/reports'), 0755, true);
        }

        $this->info('✅ Test environment prepared');
        $this->newLine();
    }

    protected function runAllTestSuites(bool $withCoverage, bool $parallel, bool $stopOnFailure): array
    {
        $suites = [
            'unit' => 'Unit Tests',
            'integration' => 'Integration Tests',
            'feature' => 'Feature Tests',
            'e2e' => 'End-to-End Tests',
            'performance' => 'Performance Tests',
            'security' => 'Security Tests',
        ];

        $results = [];

        foreach ($suites as $suite => $description) {
            $this->info("🧪 Running {$description}...");
            $results[$suite] = $this->runTestSuite($suite, $withCoverage, $parallel, $stopOnFailure);

            if ($stopOnFailure && $results[$suite]['exit_code'] !== 0) {
                $this->error("❌ Stopping due to failure in {$description}");
                break;
            }

            $this->newLine();
        }

        return $results;
    }

    protected function runTestSuite(string $suite, bool $withCoverage, bool $parallel, bool $stopOnFailure): array
    {
        $command = $this->buildTestCommand($suite, $withCoverage, $parallel, $stopOnFailure);

        $startTime = microtime(true);
        $result = Process::run($command);
        $endTime = microtime(true);

        $executionTime = round($endTime - $startTime, 2);

        return [
            'suite' => $suite,
            'command' => $command,
            'exit_code' => $result->exitCode(),
            'output' => $result->output(),
            'error_output' => $result->errorOutput(),
            'execution_time' => $executionTime,
            'success' => $result->successful(),
        ];
    }

    protected function buildTestCommand(string $suite, bool $withCoverage, bool $parallel, bool $stopOnFailure): string
    {
        $command = ['vendor/bin/phpunit'];

        // Add test suite filter
        switch ($suite) {
            case 'unit':
                $command[] = '--testsuite=Unit';
                break;
            case 'integration':
                $command[] = '--testsuite=Integration';
                break;
            case 'feature':
                $command[] = '--testsuite=Feature';
                break;
            case 'e2e':
                $command[] = '--testsuite=EndToEnd';
                break;
            case 'performance':
                $command[] = '--testsuite=Performance';
                break;
            case 'security':
                $command[] = '--testsuite=Security';
                break;
        }

        // Add coverage options
        if ($withCoverage) {
            $command[] = '--coverage-html=tests/reports/coverage';
            $command[] = '--coverage-xml=tests/reports/coverage.xml';
            $command[] = '--coverage-clover=tests/reports/clover.xml';
        }

        // Add parallel execution
        if ($parallel) {
            $command[] = '--parallel';
        }

        // Add stop on failure
        if ($stopOnFailure) {
            $command[] = '--stop-on-failure';
        }

        // Add logging options
        $command[] = '--log-junit=tests/reports/junit.xml';
        $command[] = '--testdox-html=tests/reports/testdox.html';

        return implode(' ', $command);
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Test Results Summary');
        $this->line(str_repeat('=', 50));

        $totalTime = 0;
        $totalTests = 0;
        $totalFailures = 0;

        foreach ($results as $suite => $result) {
            $status = $result['success'] ? '✅' : '❌';
            $time = $result['execution_time'];
            $totalTime += $time;

            $this->line("{$status} {$suite}: {$time}s");

            // Parse test counts from output
            if (preg_match('/Tests: (\d+)/', $result['output'], $matches)) {
                $testCount = (int) $matches[1];
                $totalTests += $testCount;
            }

            if (preg_match('/Failures: (\d+)/', $result['output'], $matches)) {
                $failureCount = (int) $matches[1];
                $totalFailures += $failureCount;
            }

            // Show errors if any
            if (! $result['success'] && ! empty($result['error_output'])) {
                $this->error("Error output for {$suite}:");
                $this->line($result['error_output']);
                $this->newLine();
            }
        }

        $this->line(str_repeat('=', 50));
        $this->info("Total Tests: {$totalTests}");
        $this->info("Total Failures: {$totalFailures}");
        $this->info("Total Time: {$totalTime}s");

        $successRate = $totalTests > 0 ? round((($totalTests - $totalFailures) / $totalTests) * 100, 2) : 0;
        $this->info("Success Rate: {$successRate}%");
    }

    protected function generateComprehensiveReport(): void
    {
        $this->info('📈 Generating comprehensive test report...');

        try {
            $report = $this->reportGenerator->generateComprehensiveReport();

            $this->info('✅ Test report generated successfully');
            $this->line('Report saved to: tests/reports/latest_report.json');

            // Display key metrics
            $this->newLine();
            $this->info('📋 Key Metrics:');
            $this->line("Total Test Files: {$report['summary']['total_test_files']}");
            $this->line("Total Tests: {$report['summary']['total_tests']}");
            $this->line("Coverage: {$report['summary']['coverage_percentage']}%");

            // Display recommendations
            if (! empty($report['recommendations'])) {
                $this->newLine();
                $this->warn('⚠️  Recommendations:');
                foreach ($report['recommendations'] as $recommendation) {
                    $priority = strtoupper($recommendation['priority']);
                    $this->line("[{$priority}] {$recommendation['message']}");
                }
            }

        } catch (\Exception $e) {
            $this->error("Failed to generate test report: {$e->getMessage()}");
        }
    }

    protected function runCodeQualityChecks(): void
    {
        $this->info('🔍 Running code quality checks...');

        // Run PHP CS Fixer
        $this->info('Running PHP CS Fixer...');
        $result = Process::run('vendor/bin/php-cs-fixer fix --dry-run --diff');

        if (! $result->successful()) {
            $this->warn('Code style issues found:');
            $this->line($result->output());
        } else {
            $this->info('✅ Code style is clean');
        }

        // Run PHPStan
        if (file_exists('vendor/bin/phpstan')) {
            $this->info('Running PHPStan...');
            $result = Process::run('vendor/bin/phpstan analyse');

            if (! $result->successful()) {
                $this->warn('Static analysis issues found:');
                $this->line($result->output());
            } else {
                $this->info('✅ Static analysis passed');
            }
        }

        // Run security checks
        if (file_exists('vendor/bin/security-checker')) {
            $this->info('Running security checks...');
            $result = Process::run('vendor/bin/security-checker security:check');

            if (! $result->successful()) {
                $this->error('Security vulnerabilities found:');
                $this->line($result->output());
            } else {
                $this->info('✅ No security vulnerabilities found');
            }
        }
    }

    protected function generatePerformanceReport(): void
    {
        $this->info('⚡ Generating performance report...');

        $performanceData = [
            'database_queries' => $this->analyzeQueryPerformance(),
            'memory_usage' => $this->analyzeMemoryUsage(),
            'response_times' => $this->analyzeResponseTimes(),
            'bottlenecks' => $this->identifyBottlenecks(),
        ];

        file_put_contents(
            base_path('tests/reports/performance_report.json'),
            json_encode($performanceData, JSON_PRETTY_PRINT)
        );

        $this->info('✅ Performance report generated');
    }

    protected function analyzeQueryPerformance(): array
    {
        // Analyze slow queries from test runs
        return [
            'slow_queries' => [],
            'n_plus_one_issues' => [],
            'missing_indexes' => [],
        ];
    }

    protected function analyzeMemoryUsage(): array
    {
        return [
            'peak_memory' => memory_get_peak_usage(true),
            'average_memory' => memory_get_usage(true),
            'memory_leaks' => [],
        ];
    }

    protected function analyzeResponseTimes(): array
    {
        return [
            'average_response_time' => 0,
            'slowest_endpoints' => [],
            'fastest_endpoints' => [],
        ];
    }

    protected function identifyBottlenecks(): array
    {
        return [
            'database_bottlenecks' => [],
            'cpu_intensive_operations' => [],
            'io_bottlenecks' => [],
        ];
    }
}
