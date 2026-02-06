<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Models\CustomEvent;
use App\Models\LearningProgress;
use App\Services\Analytics\ConsentService;
use App\Services\Analytics\InsightsService;
use App\Services\Analytics\AttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class InsightsServiceTest extends TestCase
{
    use RefreshDatabase;

    private InsightsService $insightsService;
    private Mockery\MockInterface $consentServiceMock;
    private Mockery\MockInterface $attributionServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentServiceMock = Mockery::mock(ConsentService::class);
        $this->attributionServiceMock = Mockery::mock(AttributionService::class);

        $this->app->instance(ConsentService::class, $this->consentServiceMock);
        $this->app->instance(AttributionService::class, $this->attributionServiceMock);

        $this->insightsService = new InsightsService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_insights_returns_empty_array_without_consent(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(false);

        $result = $this->insightsService->generateInsights();

        $this->assertEmpty($result);
        Log::shouldHaveReceived('warning')->once()->with('Insights generation attempted without data processing consent');
    }

    public function test_generate_insights_with_no_data_returns_empty_array(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);

        $result = $this->insightsService->generateInsights();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_generate_insights_with_analytics_events(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);

        // Create sample data
        AnalyticsEvent::factory()->count(10)->create([
            'created_at' => now()->subDays(5)
        ]);
        AnalyticsEvent::factory()->count(5)->create([
            'created_at' => now()->subDays(2)
        ]);

        $result = $this->insightsService->generateInsights(['period' => 'last_7_days']);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('type', $result[0]);
        $this->assertArrayHasKey('metric', $result[0]);
        $this->assertArrayHasKey('description', $result[0]);
        $this->assertArrayHasKey('trend_score', $result[0]);
    }

    public function test_generate_insights_detects_engagement_trend(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);

        // Create increasing engagement data
        for ($i = 7; $i >= 0; $i--) {
            $count = $i < 4 ? 5 : 15; // Increase in recent days
            AnalyticsEvent::factory()->count($count)->create([
                'event_type' => 'page_view',
                'created_at' => now()->subDays($i)
            ]);
        }

        $result = $this->insightsService->generateInsights(['period' => 'last_7_days']);

        $this->assertIsArray($result);
        $engagementTrend = collect($result)->firstWhere('type', 'trend');
        $this->assertNotNull($engagementTrend);
        $this->assertEquals('engagement', $engagementTrend['metric']);
        $this->assertGreaterThan(0, $engagementTrend['trend_score']);
    }

    public function test_generate_insights_detects_anomaly_with_z_score(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);

        // Create normal data (10 events per day for 5 days)
        for ($i = 10; $i >= 5; $i--) {
            AnalyticsEvent::factory()->count(10)->create([
                'created_at' => now()->subDays($i)
            ]);
        }

        // Create anomaly (50 events on recent day)
        AnalyticsEvent::factory()->count(50)->create([
            'created_at' => now()->subDay()
        ]);

        $result = $this->insightsService->generateInsights(['period' => 'last_14_days']);

        $this->assertIsArray($result);
        $anomaly = collect($result)->firstWhere('type', 'anomaly');
        $this->assertNotNull($anomaly);
        $this->assertEquals('engagement', $anomaly['metric']);
        $this->assertGreaterThan(1.5, $anomaly['z_score']);
    }

    public function test_generate_insights_creates_recommendations_for_drop_off(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);
        $this->attributionServiceMock->shouldReceive('getAttributionSummary')->once()->andReturn([]);

        // Create dropping engagement data
        for ($i = 7; $i >= 0; $i--) {
            $count = $i < 4 ? 20 : 5; // Drop in recent days
            AnalyticsEvent::factory()->count($count)->create([
                'created_at' => now()->subDays($i)
            ]);
        }

        $result = $this->insightsService->generateInsights(['period' => 'last_7_days']);

        $this->assertIsArray($result);
        $recommendation = collect($result)->first(fn($insight) => isset($insight['recommendation']));
        $this->assertNotNull($recommendation);
        $this->assertArrayHasKey('recommendation', $recommendation);
        $this->assertEquals('engagement_campaign', $recommendation['recommendation']['type']);
    }

    public function test_track_effectiveness_stores_score(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->andReturn(true);

        $insightId = 'test-insight-1';
        $score = 8;
        $metadata = ['implemented' => true];

        $result = $this->insightsService->trackEffectiveness($insightId, $score, $metadata);

        $this->assertTrue($result);

        // Verify cache
        $cachedData = Cache::get("insight_effectiveness_{$insightId}");
        $this->assertIsArray($cachedData);
        $this->assertCount(1, $cachedData);
        $this->assertEquals($score, $cachedData[0]['score']);
        $this->assertEquals($metadata, $cachedData[0]['metadata']);
    }

    public function test_track_effectiveness_throws_exception_for_invalid_score(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Effectiveness score must be between 1 and 10');

        $this->insightsService->trackEffectiveness('test-id', 11);
    }

    public function test_get_insight_effectiveness_returns_average(): void
    {
        $insightId = 'avg-test';
        
        // Store multiple scores
        Cache::put("insight_effectiveness_{$insightId}", [
            ['score' => 8, 'timestamp' => now()],
            ['score' => 6, 'timestamp' => now()],
            ['score' => 9, 'timestamp' => now()],
        ]);

        $average = $this->insightsService->getInsightEffectiveness($insightId);

        $this->assertEquals(7.666666666666667, $average);
    }

    public function test_get_insight_effectiveness_returns_zero_for_no_data(): void
    {
        $average = $this->insightsService->getInsightEffectiveness('non-existent');

        $this->assertEquals(0.0, $average);
    }

    public function test_generate_insights_queued_mode_returns_queued_response(): void
    {
        $this->consentServiceMock->shouldReceive('hasConsent')->once()->andReturn(true);

        $result = $this->insightsService->generateInsights(['queue' => true]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('queued', $result);
        $this->assertTrue($result['queued']);
    }

    public function test_moving_average_calculation(): void
    {
        $dataPoints = [
            ['date' => '2023-01-01', 'value' => 10],
            ['date' => '2023-01-02', 'value' => 20],
            ['date' => '2023-01-03', 'value' => 15],
            ['date' => '2023-01-04', 'value' => 25],
            ['date' => '2023-01-05', 'value' => 30],
        ];

        $reflection = new \ReflectionClass(InsightsService::class);
        $method = $reflection->getMethod('calculateMovingAverage');
        $method->setAccessible(true);

        /** @var InsightsService $service */
        $service = $this->insightsService;
        $result = $method->invoke($service, $dataPoints, 3);

        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals(15, $result[2]['value']); // Average of 10, 20, 15 = 15
        $this->assertEquals(20, $result[3]['value']); // Average of 20, 15, 25 = 20
    }

    public function test_trend_score_calculation(): void
    {
        $dataPoints = [
            ['date' => '2023-01-01', 'value' => 10],
            ['date' => '2023-01-02', 'value' => 12],
            ['date' => '2023-01-03', 'value' => 15],
            ['date' => '2023-01-04', 'value' => 20],
            ['date' => '2023-01-05', 'value' => 25],
            ['date' => '2023-01-06', 'value' => 22],
        ];

        $reflection = new \ReflectionClass(InsightsService::class);
        $method = $reflection->getMethod('calculateAverageFromEnd');
        $method->setAccessible(true);

        /** @var InsightsService $service */
        $service = $this->insightsService;
        $recentAvg = $method->invoke($service, $dataPoints, 3); // Last 3 days: 22, 25, 20 = 22.33
        $prevAvg = $method->invoke($service, $dataPoints, 3, 3); // Previous 3 days: 15, 12, 10 = 12.33

        $reflectionTrend = $reflection->getMethod('calculateEngagementTrend');
        $reflectionTrend->setAccessible(true);
        $trend = $reflectionTrend->invoke($service, collect($dataPoints), collect(), 'last_7_days');

        $this->assertNotNull($trend);
        $this->assertGreaterThan(0, $trend['trend_score']); // Should be positive trend
    }

    public function test_is_anomaly_returns_true_for_significant_change(): void
    {
        $reflection = new \ReflectionClass(InsightsService::class);
        $method = $reflection->getMethod('isAnomaly');
        $method->setAccessible(true);

        /** @var InsightsService $service */
        $service = $this->insightsService;

        $this->assertTrue($method->invoke($service, 30.0)); // >25%
        $this->assertFalse($method->invoke($service, 20.0)); // <25%
    }
}