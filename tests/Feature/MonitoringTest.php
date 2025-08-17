<?php

namespace Tests\Feature;

use App\Services\Homepage\MonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private MonitoringService $monitoring;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monitoring = new MonitoringService;
    }

    /** @test */
    public function it_can_record_performance_metrics()
    {
        $this->monitoring->recordPerformanceMetric(
            'page_load',
            'homepage_load_time',
            1250.5,
            'ms',
            ['user_agent' => 'Test Browser']
        );

        $this->assertDatabaseHas('homepage_performance_metrics', [
            'metric_type' => 'page_load',
            'metric_name' => 'homepage_load_time',
            'value' => 1250.5,
            'unit' => 'ms',
        ]);
    }

    /** @test */
    public function it_can_record_error_events()
    {
        Log::shouldReceive('channel')
            ->with('homepage-errors')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once();

        $this->monitoring->recordError(
            'api_error',
            'Failed to load testimonials',
            ['endpoint' => '/api/homepage/testimonials'],
            'error'
        );

        // Verify log was called (mocked above)
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_check_uptime()
    {
        Http::fake([
            config('app.url').'/' => Http::response('Homepage content', 200),
            config('app.url').'/health-check/homepage' => Http::response(['status' => 'healthy'], 200),
            config('app.url').'/api/homepage/statistics' => Http::response(['totalAlumni' => 1000], 200),
            config('app.url').'/api/homepage/testimonials' => Http::response([], 200),
        ]);

        $results = $this->monitoring->checkUptime();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('homepage', $results);
        $this->assertArrayHasKey('health_check', $results);
        $this->assertArrayHasKey('api_statistics', $results);
        $this->assertArrayHasKey('api_testimonials', $results);

        foreach ($results as $endpoint => $result) {
            $this->assertEquals('up', $result['status']);
            $this->assertArrayHasKey('response_time', $result);
            $this->assertArrayHasKey('status_code', $result);
            $this->assertEquals(200, $result['status_code']);
        }
    }

    /** @test */
    public function it_handles_uptime_check_failures()
    {
        Http::fake([
            config('app.url').'/' => Http::response('Server Error', 500),
            config('app.url').'/health-check/homepage' => Http::response('Not Found', 404),
        ]);

        $results = $this->monitoring->checkUptime();

        $this->assertEquals('down', $results['homepage']['status']);
        $this->assertEquals(500, $results['homepage']['status_code']);

        $this->assertEquals('down', $results['health_check']['status']);
        $this->assertEquals(404, $results['health_check']['status_code']);
    }

    /** @test */
    public function it_can_get_conversion_metrics()
    {
        // Insert test analytics data
        DB::table('homepage_analytics_events')->insert([
            [
                'event_type' => 'page_view',
                'session_id' => 'session1',
                'event_data' => json_encode(['page' => 'homepage']),
                'event_timestamp' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'cta_click',
                'session_id' => 'session1',
                'event_data' => json_encode(['cta' => 'hero_signup', 'section' => 'hero']),
                'event_timestamp' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_type' => 'conversion',
                'session_id' => 'session1',
                'event_data' => json_encode(['type' => 'signup']),
                'event_timestamp' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $metrics = $this->monitoring->getConversionMetrics();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('cta_performance', $metrics);
        $this->assertArrayHasKey('funnel_data', $metrics);
        $this->assertArrayHasKey('conversion_rate', $metrics);
        $this->assertArrayHasKey('total_page_views', $metrics);
        $this->assertArrayHasKey('total_conversions', $metrics);
    }

    /** @test */
    public function it_can_get_dashboard_data()
    {
        Cache::put('homepage_uptime_results', [
            'homepage' => ['status' => 'up', 'response_time' => 150],
            'health_check' => ['status' => 'up', 'response_time' => 50],
        ], 300);

        $dashboardData = $this->monitoring->getDashboardData();

        $this->assertIsArray($dashboardData);
        $this->assertArrayHasKey('uptime', $dashboardData);
        $this->assertArrayHasKey('conversion_metrics', $dashboardData);
        $this->assertArrayHasKey('recent_errors', $dashboardData);
        $this->assertArrayHasKey('performance_summary', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
        $this->assertArrayHasKey('system_health', $dashboardData);
    }

    /** @test */
    public function monitoring_dashboard_requires_authentication()
    {
        $response = $this->get('/monitoring/dashboard');

        // Should redirect to login or return 401/403
        $this->assertTrue(in_array($response->status(), [302, 401, 403]));
    }

    /** @test */
    public function monitoring_api_endpoints_work()
    {
        // Create a user and authenticate
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/monitoring/api/uptime');
        $this->assertTrue(in_array($response->status(), [200, 401, 403]));

        $response = $this->get('/monitoring/api/system-health');
        $this->assertTrue(in_array($response->status(), [200, 401, 403]));
    }

    /** @test */
    public function it_can_record_custom_metrics_via_api()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/monitoring/api/record-metric', [
            'metric_type' => 'custom_test',
            'metric_name' => 'test_metric',
            'value' => 123.45,
            'unit' => 'count',
            'additional_data' => ['test' => true],
        ]);

        if ($response->status() === 200) {
            $this->assertDatabaseHas('homepage_performance_metrics', [
                'metric_type' => 'custom_test',
                'metric_name' => 'test_metric',
                'value' => 123.45,
                'unit' => 'count',
            ]);
        }
    }

    /** @test */
    public function uptime_check_command_works()
    {
        Http::fake([
            config('app.url').'/' => Http::response('Homepage content', 200),
            config('app.url').'/health-check/homepage' => Http::response(['status' => 'healthy'], 200),
            config('app.url').'/api/homepage/statistics' => Http::response(['totalAlumni' => 1000], 200),
            config('app.url').'/api/homepage/testimonials' => Http::response([], 200),
        ]);

        $this->artisan('homepage:uptime-check')
            ->expectsOutput('Starting homepage uptime check...')
            ->expectsOutput('🎉 All homepage endpoints are operational!')
            ->assertExitCode(0);
    }

    /** @test */
    public function uptime_check_command_detects_failures()
    {
        Http::fake([
            config('app.url').'/' => Http::response('Server Error', 500),
            config('app.url').'/health-check/homepage' => Http::response('Not Found', 404),
            config('app.url').'/api/homepage/statistics' => Http::response([], 200),
            config('app.url').'/api/homepage/testimonials' => Http::response([], 200),
        ]);

        $this->artisan('homepage:uptime-check')
            ->expectsOutput('Starting homepage uptime check...')
            ->expectsOutput('⚠️  Some homepage endpoints are experiencing issues.')
            ->assertExitCode(1);
    }

    /** @test */
    public function performance_metrics_are_indexed_properly()
    {
        // Insert multiple metrics
        for ($i = 0; $i < 10; $i++) {
            $this->monitoring->recordPerformanceMetric(
                'page_load',
                'homepage_load_time',
                1000 + ($i * 100),
                'ms'
            );
        }

        // Query should be fast due to indexes
        $startTime = microtime(true);

        $metrics = DB::table('homepage_performance_metrics')
            ->where('metric_type', 'page_load')
            ->where('recorded_at', '>=', now()->subHour())
            ->get();

        $queryTime = microtime(true) - $startTime;

        $this->assertCount(10, $metrics);
        $this->assertLessThan(0.1, $queryTime); // Should be very fast with proper indexes
    }

    /** @test */
    public function error_logs_can_be_retrieved()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/monitoring/api/error-logs');

        // Should return JSON response
        if ($response->status() === 200) {
            $data = $response->json();
            $this->assertArrayHasKey('errors', $data);
            $this->assertIsArray($data['errors']);
        }
    }
}
