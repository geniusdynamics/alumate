<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration Test Suite Runner
 * Executes all integration tests and validates system readiness
 */
class TestSuiteRunner extends TestCase
{
    use RefreshDatabase;

    public function test_run_complete_integration_suite(): void
    {
        // Run all integration test suites
        $this->artisan('test', [
            '--testsuite' => 'Feature',
            '--filter' => 'Integration',
        ])->assertExitCode(0);

        // Validate system components
        $this->assertTrue($this->validateSystemComponents());

        // Check performance benchmarks
        $this->assertTrue($this->validatePerformanceBenchmarks());

        // Verify mobile responsiveness
        $this->assertTrue($this->validateMobileResponsiveness());
    }

    private function validateSystemComponents(): bool
    {
        // Check database tables exist
        $requiredTables = [
            'landing_pages', 'homepage_content', 'leads',
            'crm_integrations', 'landing_page_analytics',
            'landing_page_submissions', 'ab_tests',
        ];

        foreach ($requiredTables as $table) {
            if (! $this->app['db']->getSchemaBuilder()->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    private function validatePerformanceBenchmarks(): bool
    {
        // Test page load times
        $start = microtime(true);
        $this->get('/');
        $homePageTime = (microtime(true) - $start) * 1000;

        return $homePageTime < 3000; // 3 second requirement
    }

    private function validateMobileResponsiveness(): bool
    {
        $mobileUserAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15';

        $response = $this->withHeaders([
            'User-Agent' => $mobileUserAgent,
        ])->get('/');

        return $response->status() === 200;
    }
}
