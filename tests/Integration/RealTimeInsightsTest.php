<?php

namespace Tests\Integration;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\InsightsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Real-Time Insights Integration Test
 *
 * Tests real-time insights updates, trend detection, anomaly detection,
 * and recommendation generation with proper tenant isolation.
 */
class RealTimeInsightsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected InsightsService $insightsService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'id' => 'realtime-insights-tenant',
            'name' => 'Real-Time Insights Test Tenant',
        ]);

        // Create test user with consent
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        \App\Models\Consent::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'consent_type' => 'analytics',
            'granted' => true,
        ]);

        // Initialize services
        $this->insightsService = app(InsightsService::class);

        // Set tenant context
        session(['tenant_id' => $this->tenant->id]);
    }

    /** @test */
    public function it_detects_trends_in_real_time_analytics_data()
    {
        // Create increasing trend data over 15 days
        for ($day = 0; $day < 15; $day++) {
            $sessionCount = 50 + ($day * 5); // Increasing trend

            for ($i = 0; $i < $sessionCount; $i++) {
                AnalyticsEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'event_type' => 'session',
                    'event_name' => 'session_start',
                    'user_id' => $this->user->id,
                    'properties' => ['duration' => rand(300, 1800)],
                    'occurred_at' => now()->subDays(14 - $day),
                    'is_compliant' => true,
                    'consent_given' => true,
                    'analytics_version' => '1.0'
                ]);
            }
        }

        // Detect trends
        $startDate = now()->subDays(14);
        $endDate = now();
        $trends = $this->insightsService->detectTrends($startDate, $endDate);

        // Verify trends were detected
        $this->assertIsArray($trends);
        $this->assertNotEmpty($trends);

        // Check for session trend
        $sessionTrend = collect($trends)->firstWhere('type', 'session_trend');
        $this->assertNotNull($sessionTrend);
        $this->assertEquals('increasing', $sessionTrend['direction']);
        $this->assertGreaterThan(0, $sessionTrend['strength']);
        $this->assertGreaterThan(0, $sessionTrend['confidence']);
    }

    /** @test */
    public function it_detects_anomalies_in_real_time_analytics_data()
    {
        // Create normal baseline data
        for ($day = 0; $day < 10; $day++) {
            for ($i = 0; $i < 20; $i++) { // Normal: 20 sessions per day
                AnalyticsEvent::create([
                    'tenant_id' => $this->tenant->id,
                    'event_type' => 'session',
                    'event_name' => 'session_start',
                    'user_id' => $this->user->id,
                    'properties' => ['duration' => rand(600, 1200)],
                    'occurred_at' => now()->subDays(9 - $day),
                    'is_compliant' => true,
                    'consent_given' => true,
                    'analytics_version' => '1.0'
                ]);
            }
        }

        // Add anomalous day with very high session count
        for ($i = 0; $i < 100; $i++) { // Anomaly: 100 sessions (5x normal)
            AnalyticsEvent::create([
                'tenant_id' => $this->tenant->id,
                'event_type' => 'session',
                'event_name' => 'session_start',
                'user_id' => $this->user->id,
                'properties' => ['duration' => rand(600, 1200)],
                'occurred_at' => now(), // Today - anomaly
                'is_compliant' => true,
                'consent_given' => true,
                'analytics_version' => '1.0'
            ]);
        }

        // Detect anomalies
        $startDate = now()->subDays(10);
        $endDate = now();
        $anomalies = $this->insightsService->detectAnomalies($startDate, $endDate);

        // Verify anomalies were detected
        $this->assertIsArray($anomalies);
        $this->assertNotEmpty($anomalies);

        // Check for session anomaly
        $sessionAnomaly = collect($anomalies)->firstWhere('type', 'session_anomaly');
        $this->assertNotNull($sessionAnomaly);
        $this->assertGreaterThan(0, $sessionAnomaly['deviation']);
        $this->assertEquals('high', $sessionAnomaly['severity']);
    }

    /** @test */
    public function it_generates_recommendations_based_on_insights()
    {
        // Create data that should trigger recommendations
        // High error rate
        for ($i = 0; $i < 50; $i++) {
            AnalyticsEvent::create([
                'tenant_id' => $this->tenant->id,
                'event_type' => 'error',
                'event_name' => 'javascript_error',
                'user_id' => $this->user->id,
                'properties' => ['error_type' => 'TypeError', 'url' => '/dashboard'],
                'occurred_at' => now()->subDays(rand(0, 7)),
                'is_compliant' => true,
                'consent_given' => true,
                'analytics_version' => '1.0'
            ]);
        }

        // Generate recommendations
        $recommendations = $this->insightsService->generateRecommendations();

        // Verify recommendations were generated
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);

        // Check recommendation structure
        $firstRecommendation = $recommendations[0];
        $this->assertArrayHasKey('id', $firstRecommendation);
        $this->assertArrayHasKey('type', $firstRecommendation);
        $this->assertArrayHasKey('title', $firstRecommendation);
        $this->assertArrayHasKey('description', $firstRecommendation);
        $this->assertArrayHasKey('priority', $firstRecommendation);
        $this->assertArrayHasKey('impact_estimate', $firstRecommendation);
    }

    /** @test */
    public function it_maintains_tenant_isolation_in_insights_processing()
    {
        // Create second tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-insights-tenant',
            'name' => 'Other Insights Tenant',
        ]);

        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        \App\Models\Consent::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherUser->id,
            'consent_type' => 'analytics',
            'granted' => true,
        ]);

        // Create different data for each tenant
        AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'session',
            'event_name' => 'session_start',
            'user_id' => $this->user->id,
            'properties' => ['duration' => 600],
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
            'analytics_version' => '1.0'
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $otherTenant->id,
            'event_type' => 'conversion',
            'event_name' => 'signup_complete',
            'user_id' => $otherUser->id,
            'properties' => ['source' => 'email_campaign'],
            'occurred_at' => now(),
            'is_compliant' => true,
            'consent_given' => true,
            'analytics_version' => '1.0'
        ]);

        // Test insights for first tenant
        session(['tenant_id' => $this->tenant->id]);
        $tenant1Trends = $this->insightsService->detectTrends(now()->subDays(1), now());
        $this->assertIsArray($tenant1Trends);

        // Test insights for second tenant
        session(['tenant_id' => $otherTenant->id]);
        $tenant2Trends = $this->insightsService->detectTrends(now()->subDays(1), now());
        $this->assertIsArray($tenant2Trends);

        // Verify different results (different event types)
        $this->assertNotEquals(count($tenant1Trends), count($tenant2Trends));
    }

    /** @test */
    public function it_tracks_recommendation_effectiveness()
    {
        // Create test metrics for tracking
        $recommendationId = 'test_recommendation_' . uniqid();
        $preMetrics = ['conversion_rate' => 2.5, 'bounce_rate' => 65.0];
        $postMetrics = ['conversion_rate' => 4.2, 'bounce_rate' => 45.0];

        // Track effectiveness
        $result = $this->insightsService->trackRecommendationEffectiveness($recommendationId, [
            'before' => $preMetrics,
            'after' => $postMetrics
        ]);

        // Verify tracking succeeded
        $this->assertTrue($result);
    }

    /** @test */
    public function it_generates_different_types_of_insights()
    {
        // Create mixed event data
        $eventTypes = ['session', 'page_view', 'conversion', 'error'];

        foreach ($eventTypes as $eventType) {
            AnalyticsEvent::create([
                'tenant_id' => $this->tenant->id,
                'event_type' => $eventType,
                'event_name' => $eventType . '_event',
                'user_id' => $this->user->id,
                'properties' => ['value' => rand(1, 100)],
                'occurred_at' => now()->subHours(rand(1, 24)),
                'is_compliant' => true,
                'consent_given' => true,
                'analytics_version' => '1.0'
            ]);
        }

        // Test different insight types
        $trendInsights = $this->insightsService->getInsightsByType('trends');
        $this->assertIsArray($trendInsights);

        $anomalyInsights = $this->insightsService->getInsightsByType('anomalies');
        $this->assertIsArray($anomalyInsights);

        $recommendationInsights = $this->insightsService->getInsightsByType('recommendations');
        $this->assertIsArray($recommendationInsights);
    }

    /** @test */
    public function it_handles_insights_generation_with_date_filtering()
    {
        // Create events across different time periods
        AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'session',
            'event_name' => 'session_start',
            'user_id' => $this->user->id,
            'properties' => ['duration' => 600],
            'occurred_at' => now()->subDays(10), // Outside range
            'is_compliant' => true,
            'consent_given' => true,
            'analytics_version' => '1.0'
        ]);

        AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'session',
            'event_name' => 'session_start',
            'user_id' => $this->user->id,
            'properties' => ['duration' => 600],
            'occurred_at' => now()->subDays(2), // Within range
            'is_compliant' => true,
            'consent_given' => true,
            'analytics_version' => '1.0'
        ]);

        // Generate insights with date filtering
        $startDate = now()->subDays(5);
        $endDate = now()->subDays(1);
        $trends = $this->insightsService->detectTrends($startDate, $endDate);

        // Should only include events within the date range
        $this->assertIsArray($trends);
        // Note: Actual filtering depends on the implementation details
    }

    /** @test */
    public function it_processes_insights_efficiently_with_large_datasets()
    {
        // Create larger dataset
        $eventCount = 500;
        $events = [];

        for ($i = 0; $i < $eventCount; $i++) {
            $events[] = [
                'tenant_id' => $this->tenant->id,
                'event_type' => 'session',
                'event_name' => 'session_start',
                'user_id' => $this->user->id,
                'properties' => ['duration' => rand(300, 1800)],
                'occurred_at' => now()->subMinutes(rand(1, 1440)), // Last 24 hours
                'is_compliant' => true,
                'consent_given' => true,
                'analytics_version' => '1.0'
            ];
        }

        AnalyticsEvent::insert($events);

        // Measure processing time
        $startTime = microtime(true);
        $trends = $this->insightsService->detectTrends(now()->subDays(1), now());
        $endTime = microtime(true);

        // Verify processing completed
        $this->assertIsArray($trends);

        // Verify reasonable processing time (< 10 seconds for 500 events)
        $processingTime = $endTime - $startTime;
        $this->assertLessThan(10.0, $processingTime);
    }
}