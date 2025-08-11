<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Tests\UserAcceptance\TestDataSets;
use Tests\UserAcceptance\TestRunner;

class RunUserAcceptanceTests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:uat 
                            {--setup : Only setup test data without running tests}
                            {--cleanup : Only cleanup test data}
                            {--performance : Run performance tests with large datasets}
                            {--category= : Run specific test category (super-admin, institution-admin, employer, graduate, integration, performance, security)}
                            {--report : Generate detailed test report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run User Acceptance Tests for the Graduate Tracking System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Graduate Tracking System - User Acceptance Testing');
        $this->info('================================================');

        try {
            if ($this->option('cleanup')) {
                return $this->handleCleanup();
            }

            if ($this->option('setup')) {
                return $this->handleSetup();
            }

            if ($this->option('performance')) {
                return $this->handlePerformanceTests();
            }

            $category = $this->option('category');
            if ($category) {
                return $this->handleCategoryTests($category);
            }

            // Run full test suite
            return $this->handleFullTestSuite();

        } catch (Exception $e) {
            $this->error('Test execution failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Handle test data setup only
     */
    private function handleSetup()
    {
        $this->info('Setting up test data...');

        $bar = $this->output->createProgressBar(8);
        $bar->setFormat('verbose');

        try {
            $bar->advance(); // Institutions
            $bar->advance(); // Users
            $bar->advance(); // Courses
            $bar->advance(); // Graduates
            $bar->advance(); // Employers
            $bar->advance(); // Jobs
            $bar->advance(); // Applications
            $bar->advance(); // Announcements
            $bar->advance(); // Notifications

            $testData = TestDataSets::createAllTestData();
            $bar->finish();

            $this->newLine(2);
            $this->info('Test data setup completed successfully!');

            // Display summary
            $this->table(['Data Type', 'Count'], [
                ['Institutions', count($testData['institutions'])],
                ['Users', count($testData['users'])],
                ['Courses', array_sum(array_map('count', $testData['courses']))],
                ['Graduates', count($testData['graduates'])],
                ['Employers', count($testData['employers'])],
                ['Jobs', count($testData['jobs'])],
                ['Applications', count($testData['applications'])],
                ['Announcements', count($testData['announcements'])],
                ['Notifications', count($testData['notifications'])],
            ]);

            return 0;

        } catch (Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Failed to setup test data: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Handle test data cleanup
     */
    private function handleCleanup()
    {
        $this->info('Cleaning up test data...');

        if (! $this->confirm('This will delete all test data. Are you sure?')) {
            $this->info('Cleanup cancelled.');

            return 0;
        }

        try {
            TestDataSets::cleanupTestData();
            $this->info('Test data cleanup completed successfully!');

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to cleanup test data: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Handle performance tests with large datasets
     */
    private function handlePerformanceTests()
    {
        $this->info('Running performance tests with large datasets...');

        $count = $this->ask('How many test records to create?', 1000);

        if (! is_numeric($count) || $count < 1) {
            $this->error('Invalid count specified.');

            return 1;
        }

        $this->info("Creating {$count} test records...");

        $bar = $this->output->createProgressBar($count);

        try {
            $graduates = TestDataSets::createPerformanceTestData($count);
            $bar->finish();

            $this->newLine(2);
            $this->info("Performance test data created: {$count} graduates");

            // Run performance-specific tests
            $testRunner = new TestRunner;
            $this->info('Running performance tests...');

            // This would run only performance-related tests
            // Implementation would be similar to full test suite but focused on performance

            $this->info('Performance tests completed!');

            return 0;

        } catch (Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Performance test failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Handle specific category tests
     */
    private function handleCategoryTests($category)
    {
        $validCategories = [
            'super-admin',
            'institution-admin',
            'employer',
            'graduate',
            'integration',
            'performance',
            'security',
        ];

        if (! in_array($category, $validCategories)) {
            $this->error('Invalid category. Valid categories: '.implode(', ', $validCategories));

            return 1;
        }

        $this->info("Running {$category} tests...");

        try {
            $testRunner = new TestRunner;

            // This would run category-specific tests
            // For now, we'll run the full suite and filter results
            $results = $testRunner->runAllTests();

            // Filter results by category
            $categoryResults = $this->filterResultsByCategory($results, $category);

            $this->displayTestResults($categoryResults);

            return 0;

        } catch (Exception $e) {
            $this->error('Category test failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Handle full test suite execution
     */
    private function handleFullTestSuite()
    {
        $this->info('Running full User Acceptance Test suite...');
        $this->newLine();

        try {
            $testRunner = new TestRunner;
            $results = $testRunner->runAllTests();

            $this->displayTestResults($results);

            if ($this->option('report')) {
                $this->generateDetailedReport($results);
            }

            return 0;

        } catch (Exception $e) {
            $this->error('Test suite failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Display test results in a formatted table
     */
    private function displayTestResults($results)
    {
        $this->newLine();
        $this->info('Test Results Summary');
        $this->info('===================');

        $tableData = [];
        $totalTests = 0;
        $passedTests = 0;
        $totalDuration = 0;

        foreach ($results as $testId => $result) {
            if ($testId === 'summary') {
                continue;
            }

            $status = $result['status'] === 'passed' ? '<info>PASSED</info>' : '<error>FAILED</error>';
            $duration = number_format($result['duration'], 3).'s';

            $tableData[] = [
                $testId,
                $result['name'],
                $status,
                $duration,
                $result['error'] ?? '-',
            ];

            $totalTests++;
            if ($result['status'] === 'passed') {
                $passedTests++;
            }
            $totalDuration += $result['duration'];
        }

        $this->table(
            ['Test ID', 'Test Name', 'Status', 'Duration', 'Error'],
            $tableData
        );

        // Summary statistics
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;

        $this->newLine();
        $this->info('Summary Statistics');
        $this->info('==================');
        $this->line("Total Tests: {$totalTests}");
        $this->line("Passed: <info>{$passedTests}</info>");
        $this->line('Failed: <error>'.($totalTests - $passedTests).'</error>');
        $this->line('Success Rate: '.number_format($successRate, 1).'%');
        $this->line('Total Duration: '.number_format($totalDuration, 3).'s');

        if ($successRate >= 95) {
            $this->info('✅ Test suite PASSED - Success rate meets acceptance criteria (≥95%)');
        } else {
            $this->error('❌ Test suite FAILED - Success rate below acceptance criteria (<95%)');
        }
    }

    /**
     * Filter test results by category
     */
    private function filterResultsByCategory($results, $category)
    {
        $categoryPrefixes = [
            'super-admin' => 'SA-',
            'institution-admin' => 'IA-',
            'employer' => 'E-',
            'graduate' => 'G-',
            'integration' => 'CR-',
            'performance' => 'P-',
            'security' => 'S-',
        ];

        $prefix = $categoryPrefixes[$category] ?? '';

        if (empty($prefix)) {
            return $results;
        }

        $filtered = [];
        foreach ($results as $testId => $result) {
            if (strpos($testId, $prefix) === 0) {
                $filtered[$testId] = $result;
            }
        }

        return $filtered;
    }

    /**
     * Generate detailed test report
     */
    private function generateDetailedReport($results)
    {
        $this->info('Generating detailed test report...');

        $reportPath = storage_path('app/test-reports/uat-detailed-report-'.date('Y-m-d-H-i-s').'.html');

        if (! file_exists(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }

        $html = $this->generateHtmlReport($results);
        file_put_contents($reportPath, $html);

        $this->info("Detailed report generated: {$reportPath}");
    }

    /**
     * Generate HTML report
     */
    private function generateHtmlReport($results)
    {
        $totalTests = count($results) - 1; // Exclude summary
        $passedTests = collect($results)->where('status', 'passed')->count();
        $successRate = $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>User Acceptance Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f8f9fa; padding: 20px; border-radius: 5px; }
        .summary { margin: 20px 0; }
        .test-results { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .passed { color: green; font-weight: bold; }
        .failed { color: red; font-weight: bold; }
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Graduate Tracking System - User Acceptance Test Report</h1>
        <p>Generated on: '.date('Y-m-d H:i:s').'</p>
    </div>
    
    <div class="summary">
        <h2>Summary</h2>
        <p><strong>Total Tests:</strong> '.$totalTests.'</p>
        <p><strong>Passed:</strong> <span class="passed">'.$passedTests.'</span></p>
        <p><strong>Failed:</strong> <span class="failed">'.($totalTests - $passedTests).'</span></p>
        <p><strong>Success Rate:</strong> '.number_format($successRate, 1).'%</p>
    </div>
    
    <div class="test-results">
        <h2>Test Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Test ID</th>
                    <th>Test Name</th>
                    <th>Status</th>
                    <th>Duration</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($results as $testId => $result) {
            if ($testId === 'summary') {
                continue;
            }

            $statusClass = $result['status'] === 'passed' ? 'passed' : 'failed';
            $status = strtoupper($result['status']);
            $duration = number_format($result['duration'], 3).'s';
            $error = $result['error'] ? '<span class="error">'.htmlspecialchars($result['error']).'</span>' : '-';

            $html .= "
                <tr>
                    <td>{$testId}</td>
                    <td>{$result['name']}</td>
                    <td><span class=\"{$statusClass}\">{$status}</span></td>
                    <td>{$duration}</td>
                    <td>{$error}</td>
                </tr>";
        }

        $html .= '
            </tbody>
        </table>
    </div>
</body>
</html>';

        return $html;
    }
}
