<?php

namespace Tests\Feature\Homepage;

use App\Services\Homepage\AlertingService;
use App\Services\Homepage\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonitoringAlertingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock external services
        Http::fake();
        Mail::fake();
        Log::fake();
    }

    public function test_uptime_monitoring_detects_down_endpoints(): void
    {
        // Mock HTTP responses to simulate down endpoints
        Http::fake([
            config('app.url') . '/' => Http::response('', 500),
            config('app.url') . '/health-check/homepage' => Http::response('', 503),
        ]);

        $monitoring = app(MonitoringService::class);
        $results = $monitoring->checkUptime();

        // Assert that endpoints are detected as down
        $this->assertEquals('down', $results['homepage']['status']);
        $this->assertEquals('down', $results['health_check']['status']);
        $this->assertEquals(500, $results['homepage']['status_code']);
        $this->assertEquals(503, $results['health_check']['status_code']);
    }

    public function test_performance_alert_is_sent_when_threshold_exceeded(): void
    {
        $alerting = app(AlertingService::class);
        
        // Test critical performance alert
        $alerting->sendPerformanceAlert('page_load', 'homepage_load_time', 6000, 'critical');
        
        // Assert alert was logged
        Log::assertLogged('alert', function ($message, $context) {
            return str_contains($message, 'Performance Alert: homepage_load_time') &&
                   $context['severity'] === 'critical' &&
                   $context['details']['value'] === 6000;
        });
    }

    public function test_error_alert_is_sent_with_proper_rate_limiting(): void
    {
        $alerting = app(AlertingService::class);
        
        // Send first alert
        $alerting->sendErrorAlert('test_error', 'critical', 1, 'Test error message');
        
        // Send second alert immediately (should be rate limited)
        $alerting->sendErrorAlert('test_error', 'critical', 2, 'Test error message 2');
        
        // Assert only one alert was processed (due to rate limiting)
        Log::assertLoggedTimes('alert', 1);
    }

    public function test_conversion_monitoring_detects_low_conversion_rates(): void
    {
        // Create test data with low conversion rate
        $this->createTestAnalyticsData([
            'page_views' => 1000,
            'conversions' => 10, // 1% conversion rate (below 2% threshold)
        ]);

        $monitoring = app(MonitoringService::class);
        $monitoring->monitorConversionMetrics();

        // Assert conversion alert was logged
        Log::assertLogged('alert', function ($message, $context) {
            return str_contains($message, 'Conversion Alert: conversion_rate') &&
                   $context['type'] === 'conversion';
        });
    }

    public function test_security_monitoring_detects_suspicious_activity(): void
    {
        $monitoring = app(MonitoringService::class);
        
        // Simulate suspicious IP activity
        Cache::put('suspicious_activity_192.168.1.100', 101, 300);
        
        $monitoring->monitorSecurityThreats();
        
        // Assert security alert was logged
        Log::assertLogged('error', function ($message, $context) {
            return str_contains($message, 'suspicious_activity') &&
                   isset($context['ip_address']);
        });
    }

    public function test_alert_notification_email_is_sent(): void
    {
        $alerting = app(AlertingService::class);
        
        // Configure alert email
        config(['services.monitoring.alert_email' => 'admin@example.com']);
        
        $alertData = [
            'type' => 'test',
            'severity' => 'critical',
            'title' => 'Test Alert',
            'message' => 'This is a test alert',
            'details' => [
                'test' => true,
                'environment' => 'testing',
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['test', 'homepage'],
        ];
        
        $alerting->sendAlert($alertData);
        
        // Assert email was sent
        Mail::assertSent(\App\Mail\Homepage\AlertNotification::class, function ($mail) {
            return $mail->alertData['title'] === 'Test Alert' &&
                   $mail->alertData['severity'] === 'critical';
        });
    }

    public function test_slack_alert_is_sent_when_configured(): void
    {
        $alerting = app(AlertingService::class);
        
        // Configure Slack webhook
        config(['services.monitoring.slack_webhook' => 'https://hooks.slack.com/test']);
        
        $alertData = [
            'type' => 'uptime',
            'severity' => 'critical',
            'title' => 'Homepage Down',
            'message' => 'Homepage is not responding',
            'details' => [
                'endpoint' => 'homepage',
                'status_code' => 500,
                'environment' => 'testing',
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['uptime', 'homepage'],
        ];
        
        $alerting->sendAlert($alertData);
        
        // Assert HTTP request was made to Slack
        Http::assertSent(function ($request) {
            return $request->url() === 'https://hooks.slack.com/test' &&
                   isset($request->data()['text']) &&
                   str_contains($request->data()['text'], 'Homepage Down');
        });
    }

    public function test_sentry_integration_captures_alerts(): void
    {
        // Mock Sentry
        $sentryMock = $this->createMock(\Sentry\State\HubInterface::class);
        $sentryMock->expects($this->once())
                   ->method('withScope')
                   ->willReturnCallback(function ($callback) {
                       $scope = $this->createMock(\Sentry\State\Scope::class);
                       $scope->expects($this->atLeastOnce())->method('setTag');
                       $scope->expects($this->once())->method('setLevel');
                       $scope->expects($this->once())->method('setContext');
                       $callback($scope);
                   });
        
        $sentryMock->expects($this->once())
                   ->method('captureMessage')
                   ->with('Test Sentry Alert');
        
        app()->instance('sentry', $sentryMock);
        
        $alerting = app(AlertingService::class);
        
        $alertData = [
            'type' => 'error',
            'severity' => 'error',
            'title' => 'Test Sentry Alert',
            'message' => 'Testing Sentry integration',
            'details' => [
                'error_type' => 'test_error',
                'environment' => 'testing',
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['test', 'sentry'],
        ];
        
        $alerting->sendAlert($alertData);
    }

    public function test_monitoring_dashboard_returns_comprehensive_data(): void
    {
        $monitoring = app(MonitoringService::class);
        $dashboardData = $monitoring->getDashboardData();
        
        // Assert all required sections are present
        $this->assertArrayHasKey('uptime', $dashboardData);
        $this->assertArrayHasKey('conversion_metrics', $dashboardData);
        $this->assertArrayHasKey('recent_errors', $dashboardData);
        $this->assertArrayHasKey('performance_summary', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
        $this->assertArrayHasKey('system_health', $dashboardData);
    }

    public function test_health_check_endpoint_returns_proper_status(): void
    {
        $response = $this->get('/health-check/homepage');
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database',
                'cache',
                'storage',
                'homepage_assets',
                'homepage_routes',
            ],
            'version',
            'environment',
        ]);
    }

    public function test_monitoring_command_runs_successfully(): void
    {
        $this->artisan('homepage:monitor --uptime')
             ->expectsOutput('Starting homepage monitoring...')
             ->expectsOutput('Running uptime check...')
             ->expectsOutput('✓ uptime check completed')
             ->expectsOutput('Homepage monitoring completed successfully')
             ->assertExitCode(0);
    }

    /**
     * Create test analytics data for conversion monitoring.
     */
    private function createTestAnalyticsData(array $data): void
    {
        // Create homepage_analytics_events table if it doesn't exist
        if (!\Schema::hasTable('homepage_analytics_events')) {
            \Schema::create('homepage_analytics_events', function ($table) {
                $table->id();
                $table->string('event_type');
                $table->string('session_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->text('url')->nullable();
                $table->text('referer')->nullable();
                $table->json('event_data')->nullable();
                $table->timestamps();
            });
        }

        // Insert page view events
        for ($i = 0; $i < $data['page_views']; $i++) {
            \DB::table('homepage_analytics_events')->insert([
                'event_type' => 'page_view',
                'session_id' => 'session_' . $i,
                'ip_address' => '192.168.1.' . ($i % 255),
                'user_agent' => 'Test User Agent',
                'url' => 'https://example.com/',
                'event_data' => json_encode(['test' => true]),
                'created_at' => now()->subDays(rand(1, 7)),
                'updated_at' => now(),
            ]);
        }

        // Insert conversion events
        for ($i = 0; $i < $data['conversions']; $i++) {
            \DB::table('homepage_analytics_events')->insert([
                'event_type' => 'conversion',
                'session_id' => 'session_' . $i,
                'ip_address' => '192.168.1.' . ($i % 255),
                'user_agent' => 'Test User Agent',
                'url' => 'https://example.com/',
                'event_data' => json_encode(['conversion_type' => 'signup']),
                'created_at' => now()->subDays(rand(1, 7)),
                'updated_at' => now(),
            ]);
        }
    }
}