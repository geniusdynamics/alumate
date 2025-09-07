<?php

namespace Tests;

class TestRunner
{
    public static function runComprehensiveTestSuite(): array
    {
        $results = [];

        // Test categories to run
        $testCategories = [
            'Feature Tests' => [
                'tests/Feature/SocialTimelineTest.php',
                'tests/Feature/AlumniDirectoryTest.php',
                'tests/Feature/CareerTimelineTest.php',
                'tests/Feature/JobMatchingTest.php',
            ],
            'End-to-End Tests' => [
                'tests/EndToEnd/UserJourneyTest.php',
            ],
            'Performance Tests' => [
                'tests/Performance/AlumniPlatformPerformanceTest.php',
            ],
            'Accessibility Tests' => [
                'tests/Accessibility/AccessibilityComplianceTest.php',
            ],
            'Integration Tests' => [
                'tests/Integration/SocialPlatformIntegrationTest.php',
            ],
        ];

        foreach ($testCategories as $category => $tests) {
            $results[$category] = [];

            foreach ($tests as $testFile) {
                try {
                    // Run individual test file
                    $output = shell_exec("php artisan test {$testFile} --stop-on-failure 2>&1");

                    $results[$category][$testFile] = [
                        'status' => strpos($output, 'FAILED') === false ? 'PASSED' : 'FAILED',
                        'output' => $output,
                    ];
                } catch (\Exception $e) {
                    $results[$category][$testFile] = [
                        'status' => 'ERROR',
                        'output' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    public static function generateTestReport(array $results): string
    {
        $report = "# Alumni Platform - Comprehensive Test Suite Report\n\n";
        $report .= 'Generated: '.date('Y-m-d H:i:s')."\n\n";

        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $errorTests = 0;

        foreach ($results as $category => $tests) {
            $report .= "## {$category}\n\n";

            foreach ($tests as $testFile => $result) {
                $totalTests++;
                $status = $result['status'];

                switch ($status) {
                    case 'PASSED':
                        $passedTests++;
                        $icon = '✅';
                        break;
                    case 'FAILED':
                        $failedTests++;
                        $icon = '❌';
                        break;
                    case 'ERROR':
                        $errorTests++;
                        $icon = '⚠️';
                        break;
                    default:
                        $icon = '❓';
                }

                $report .= "- {$icon} **{$testFile}**: {$status}\n";

                if ($status !== 'PASSED') {
                    $report .= "  ```\n";
                    $report .= '  '.substr($result['output'], 0, 500)."...\n";
                    $report .= "  ```\n";
                }
            }

            $report .= "\n";
        }

        $report .= "## Summary\n\n";
        $report .= "- **Total Tests**: {$totalTests}\n";
        $report .= "- **Passed**: {$passedTests}\n";
        $report .= "- **Failed**: {$failedTests}\n";
        $report .= "- **Errors**: {$errorTests}\n";
        $report .= '- **Success Rate**: '.round(($passedTests / $totalTests) * 100, 2)."%\n\n";

        $report .= "## Test Coverage Areas\n\n";
        $report .= "### ✅ Implemented Test Areas\n";
        $report .= "- Social Timeline functionality\n";
        $report .= "- Alumni Directory search and filtering\n";
        $report .= "- Career Timeline management\n";
        $report .= "- Job Matching and applications\n";
        $report .= "- End-to-end user journeys\n";
        $report .= "- Performance benchmarking\n";
        $report .= "- Accessibility compliance\n";
        $report .= "- API integration testing\n\n";

        $report .= "### 🔄 Test Quality Features\n";
        $report .= "- Database refresh for isolation\n";
        $report .= "- Factory-based test data\n";
        $report .= "- Event and notification testing\n";
        $report .= "- Permission and authorization testing\n";
        $report .= "- Performance metrics collection\n";
        $report .= "- Accessibility standards validation\n";
        $report .= "- Cross-feature integration testing\n\n";

        return $report;
    }

    public static function runAndReport(): void
    {
        echo "Running Alumni Platform Comprehensive Test Suite...\n\n";

        $results = self::runComprehensiveTestSuite();
        $report = self::generateTestReport($results);

        // Save report to file
        $reportPath = storage_path('logs/test_report_'.date('Y-m-d_H-i-s').'.md');
        file_put_contents($reportPath, $report);

        echo $report;
        echo "\nFull report saved to: {$reportPath}\n";
    }
}
