<?php

namespace Tests\Unit\Services;

use App\Models\EmailAnalytics;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Template;
use App\Services\EmailAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

/**
 * Unit tests for EmailAnalyticsService
 */
class EmailAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailAnalyticsService $service;
    private Tenant $tenant;
    private User $user;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        // Mock cache to avoid external dependencies
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });
        Cache::shouldReceive('forget')->andReturn(true);

        $this->service = new EmailAnalyticsService();
    }

    /**
     * Test service instantiation
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(EmailAnalyticsService::class, $this->service);
    }

    /**
     * Test tracking email delivery
     */
    public function test_track_delivery_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $result = $this->service->trackDelivery($analytics->id);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->delivered_at);
        $this->assertEquals('delivered', $analytics->delivery_status);
    }

    /**
     * Test tracking email delivery with invalid ID
     */
    public function test_track_delivery_invalid_id()
    {
        $result = $this->service->trackDelivery(999999);

        $this->assertFalse($result);
    }

    /**
     * Test tracking email open
     */
    public function test_track_open_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $metadata = [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'device_type' => 'desktop',
        ];

        $result = $this->service->trackOpen($analytics->id, $metadata);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->opened_at);
        $this->assertEquals('opened', $analytics->delivery_status);
        $this->assertEquals('192.168.1.1', $analytics->ip_address);
        $this->assertEquals('desktop', $analytics->device_type);
    }

    /**
     * Test tracking email click
     */
    public function test_track_click_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $url = 'https://example.com/page';
        $metadata = [
            'referrer_url' => 'https://example.com/email',
        ];

        $result = $this->service->trackClick($analytics->id, $url, $metadata);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->clicked_at);
        $this->assertEquals('clicked', $analytics->delivery_status);
        $this->assertEquals('https://example.com/email', $analytics->referrer_url);
    }

    /**
     * Test tracking email conversion
     */
    public function test_track_conversion_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $conversionType = 'purchase';
        $value = 99.99;
        $metadata = ['source' => 'email_campaign'];

        $result = $this->service->trackConversion($analytics->id, $conversionType, $value, $metadata);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->converted_at);
        $this->assertEquals('converted', $analytics->delivery_status);
        $this->assertEquals('purchase', $analytics->conversion_type);
        $this->assertEquals(99.99, $analytics->conversion_value);
        $this->assertEquals(5, $analytics->funnel_stage);
    }

    /**
     * Test tracking email bounce
     */
    public function test_track_bounce_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $reason = 'Mailbox full';

        $result = $this->service->trackBounce($analytics->id, $reason);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->bounced_at);
        $this->assertEquals('bounced', $analytics->delivery_status);
        $this->assertEquals('Mailbox full', $analytics->bounce_reason);
    }

    /**
     * Test tracking email complaint
     */
    public function test_track_complaint_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $reason = 'Spam';

        $result = $this->service->trackComplaint($analytics->id, $reason);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->complained_at);
        $this->assertEquals('complaint', $analytics->delivery_status);
        $this->assertEquals('Spam', $analytics->complaint_reason);
    }

    /**
     * Test tracking email unsubscribe
     */
    public function test_track_unsubscribe_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        $result = $this->service->trackUnsubscribe($analytics->id);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->unsubscribed_at);
        $this->assertEquals('unsubscribed', $analytics->delivery_status);
    }

    /**
     * Test getting email performance metrics
     */
    public function test_get_email_performance_metrics()
    {
        // Create test data
        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'delivered_at' => now(),
        ]);

        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        EmailAnalytics::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'clicked_at' => now(),
            'opened_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(10),
        ]);

        $metrics = $this->service->getEmailPerformanceMetrics($this->tenant->id);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_sent', $metrics);
        $this->assertArrayHasKey('delivery_rate', $metrics);
        $this->assertArrayHasKey('open_rate', $metrics);
        $this->assertArrayHasKey('click_rate', $metrics);
        $this->assertEquals(17, $metrics['total_sent']); // 10 + 5 + 2
        $this->assertEquals(7, $metrics['total_opened']); // 5 + 2
        $this->assertEquals(2, $metrics['total_clicked']);
    }

    /**
     * Test getting funnel analytics
     */
    public function test_get_funnel_analytics()
    {
        // Create test data for funnel
        EmailAnalytics::factory()->count(100)->create(['tenant_id' => $this->tenant->id]);
        EmailAnalytics::factory()->count(80)->create([
            'tenant_id' => $this->tenant->id,
            'delivered_at' => now(),
        ]);
        EmailAnalytics::factory()->count(60)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);
        EmailAnalytics::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'clicked_at' => now(),
            'opened_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(10),
        ]);
        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'converted_at' => now(),
            'clicked_at' => now()->subMinutes(5),
            'opened_at' => now()->subMinutes(10),
            'delivered_at' => now()->subMinutes(15),
        ]);

        $funnel = $this->service->getFunnelAnalytics($this->tenant->id);

        $this->assertIsArray($funnel);
        $this->assertArrayHasKey('sent', $funnel);
        $this->assertArrayHasKey('delivered', $funnel);
        $this->assertArrayHasKey('opened', $funnel);
        $this->assertArrayHasKey('clicked', $funnel);
        $this->assertArrayHasKey('converted', $funnel);
        $this->assertEquals(265, $funnel['sent']); // 100 + 80 + 60 + 20 + 5
        $this->assertEquals(165, $funnel['delivered']); // 80 + 60 + 20 + 5
        $this->assertEquals(85, $funnel['opened']); // 60 + 20 + 5
        $this->assertEquals(25, $funnel['clicked']); // 20 + 5
        $this->assertEquals(5, $funnel['converted']);
    }

    /**
     * Test generating engagement report
     */
    public function test_generate_engagement_report()
    {
        EmailAnalytics::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'device_type' => 'desktop',
        ]);

        EmailAnalytics::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'device_type' => 'mobile',
        ]);

        $report = $this->service->generateEngagementReport($this->tenant->id);

        $this->assertIsArray($report);
        $this->assertArrayHasKey('daily_trends', $report);
        $this->assertArrayHasKey('device_breakdown', $report);
        $this->assertArrayHasKey('browser_breakdown', $report);
        $this->assertArrayHasKey('engagement_score', $report);
    }

    /**
     * Test getting A/B test results
     */
    public function test_get_ab_test_results()
    {
        // Create A/B test data
        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'A',
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'B',
            'opened_at' => now(),
            'delivered_at' => now()->subMinutes(5),
        ]);

        EmailAnalytics::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'A',
            'clicked_at' => now(),
            'opened_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(10),
        ]);

        EmailAnalytics::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'ab_test_variant' => 'B',
            'clicked_at' => now(),
            'opened_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(10),
        ]);

        $results = $this->service->getABTestResults($this->tenant->id);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('variants', $results);
        $this->assertArrayHasKey('A', $results['variants']);
        $this->assertArrayHasKey('B', $results['variants']);
        $this->assertEquals(100, $results['variants']['A']['sent']);
        $this->assertEquals(100, $results['variants']['B']['sent']);
        $this->assertEquals(20, $results['variants']['A']['clicked']);
        $this->assertEquals(30, $results['variants']['B']['clicked']);
    }

    /**
     * Test getting real-time analytics
     */
    public function test_get_real_time_analytics()
    {
        // Create recent activity
        EmailAnalytics::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'opened_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        EmailAnalytics::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'clicked_at' => now()->subMinutes(1),
            'updated_at' => now()->subMinutes(1),
        ]);

        $analytics = $this->service->getRealTimeAnalytics($this->tenant->id, 5);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('stats', $analytics);
        $this->assertArrayHasKey('recent_activity', $analytics);
        $this->assertEquals(5, $analytics['stats']['opens_last_5_minutes']);
        $this->assertEquals(3, $analytics['stats']['clicks_last_5_minutes']);
    }

    /**
     * Test generating automated report
     */
    public function test_generate_automated_report()
    {
        EmailAnalytics::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => now(),
        ]);

        $report = $this->service->generateAutomatedReport($this->tenant->id, 'daily');

        $this->assertIsArray($report);
        $this->assertArrayHasKey('performance_metrics', $report);
        $this->assertArrayHasKey('funnel_analytics', $report);
        $this->assertArrayHasKey('engagement_report', $report);
        $this->assertArrayHasKey('ab_test_results', $report);
        $this->assertEquals('daily', $report['period']);
    }

    /**
     * Test tracking attribution
     */
    public function test_track_attribution_success()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
            'clicked_at' => now(),
        ]);

        $result = $this->service->trackAttribution($analytics->id, 'signup', ['source' => 'landing_page']);

        $this->assertTrue($result);

        $analytics->refresh();
        $this->assertNotNull($analytics->converted_at);
        $this->assertEquals('signup', $analytics->conversion_type);
    }

    /**
     * Test tracking attribution for already converted email
     */
    public function test_track_attribution_already_converted()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
            'converted_at' => now(),
        ]);

        $result = $this->service->trackAttribution($analytics->id, 'purchase');

        $this->assertFalse($result);
    }

    /**
     * Test cache clearing
     */
    public function test_cache_clearing_on_tracking()
    {
        $analytics = EmailAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'recipient_id' => $this->user->id,
        ]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('email_analytics_performance_' . $this->tenant->id)
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('email_analytics_funnel_' . $this->tenant->id)
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('email_analytics_engagement_' . $this->tenant->id)
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('email_analytics_ab_test_' . $this->tenant->id)
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('email_analytics_report_' . $this->tenant->id)
            ->andReturn(true);

        $this->service->trackDelivery($analytics->id);
    }

    /**
     * Test filtering by date range
     */
    public function test_date_range_filtering()
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $startDate->copy()->subDays(1), // Before range
        ]);

        EmailAnalytics::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $startDate->copy()->addDays(1), // Within range
        ]);

        EmailAnalytics::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'send_date' => $endDate->copy()->addDays(1), // After range
        ]);

        $metrics = $this->service->getEmailPerformanceMetrics($this->tenant->id, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        $this->assertEquals(20, $metrics['total_sent']);
    }

    /**
     * Test filtering by campaign
     */
    public function test_campaign_filtering()
    {
        EmailAnalytics::factory()->count(15)->create([
            'tenant_id' => $this->tenant->id,
            'email_campaign_id' => 1,
        ]);

        EmailAnalytics::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'email_campaign_id' => 2,
        ]);

        $metrics = $this->service->getEmailPerformanceMetrics($this->tenant->id, [
            'campaign_id' => 1,
        ]);

        $this->assertEquals(15, $metrics['total_sent']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}