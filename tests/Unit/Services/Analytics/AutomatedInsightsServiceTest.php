<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AutomatedInsightsService;
use App\Services\CacheService;
use App\Services\TenantContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AutomatedInsightsService
 *
 * @covers \App\Services\Analytics\AutomatedInsightsService
 */
class AutomatedInsightsServiceTest extends TestCase
{
    private AutomatedInsightsService $service;
    private Mockery\MockInterface $mockCacheService;
    private Mockery\MockInterface $mockTenantContextService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock services
        $this->mockCacheService = Mockery::mock(CacheService::class);
        $this->mockTenantContextService = Mockery::mock(TenantContextService::class);

        // Set up the cache service mock to pass through closures
        $this->mockCacheService
            ->shouldReceive('remember')
            ->andReturnUsing(function ($key, $callback, $ttl) {
                return $callback();
            });

        // Set up tenant context mock
        $this->mockTenantContextService
            ->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-id');

        // Create service with mocks
        $this->service = new AutomatedInsightsService(
            $this->mockCacheService,
            $this->mockTenantContextService,
            null,
            null
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test scoring an insight with critical severity
     */
    public function test_score_insight_critical_severity(): void
    {
        $insight = [
            'type' => 'trend',
            'severity' => AutomatedInsightsService::SEVERITY_CRITICAL,
            'message' => 'Test critical insight',
            'impact' => 'high',
            'confidence' => 0.9,
            'detected_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'actionable' => true,
            'recommendation' => 'Test recommendation',
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('factors', $result);
        $this->assertArrayHasKey('insight', $result);
        $this->assertGreaterThan(50, $result['score']);
        $this->assertEquals(40, $result['factors']['severity']);
    }

    /**
     * Test scoring an insight with positive severity
     */
    public function test_score_insight_positive_severity(): void
    {
        $insight = [
            'type' => 'trend',
            'severity' => AutomatedInsightsService::SEVERITY_POSITIVE,
            'message' => 'Test positive insight',
            'impact' => 'low',
            'confidence' => 0.5,
            'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'actionable' => true,
            'recommendation' => 'Test recommendation',
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertArrayHasKey('score', $result);
        // Positive severity adds less points but other factors contribute
        $this->assertLessThanOrEqual(70, $result['score']);
        // Verify positive severity gets the lowest severity score
        $this->assertEquals(5, $result['factors']['severity']);
    }

    /**
     * Test scoring an insight with missing optional fields
     */
    public function test_score_insight_missing_optional_fields(): void
    {
        $insight = [
            'type' => 'anomaly',
            'message' => 'Test insight without optional fields',
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('insight', $result);
        $this->assertIsNumeric($result['score']);
    }

    /**
     * Test scoring insight with default severity
     */
    public function test_score_insight_default_severity(): void
    {
        $insight = [
            'message' => 'Test insight with default severity',
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertEquals(20, $result['factors']['severity']);
    }

    /**
     * Test prioritizing empty insights array
     */
    public function test_prioritize_insights_empty(): void
    {
        $result = $this->service->prioritizeInsights([]);

        $this->assertArrayHasKey('prioritized', $result);
        $this->assertArrayHasKey('grouped', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertEmpty($result['prioritized']);
        $this->assertEquals(0, $result['summary']['total_insights']);
    }

    /**
     * Test prioritizing multiple insights
     */
    public function test_prioritize_insights_multiple(): void
    {
        $insights = [
            [
                'type' => 'trend',
                'severity' => AutomatedInsightsService::SEVERITY_LOW,
                'message' => 'Low priority insight',
                'impact' => 'low',
                'confidence' => 0.5,
            ],
            [
                'type' => 'anomaly',
                'severity' => AutomatedInsightsService::SEVERITY_CRITICAL,
                'message' => 'Critical insight',
                'impact' => 'high',
                'confidence' => 0.9,
                'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'actionable' => true,
                'recommendation' => 'Test',
            ],
            [
                'type' => 'correlation',
                'severity' => AutomatedInsightsService::SEVERITY_HIGH,
                'message' => 'High priority insight',
                'impact' => 'high',
                'confidence' => 0.8,
                'detected_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'actionable' => true,
                'recommendation' => 'Test',
            ],
        ];

        $result = $this->service->prioritizeInsights($insights);

        $this->assertCount(3, $result['prioritized']);
        $this->assertEquals(
            AutomatedInsightsService::SEVERITY_CRITICAL,
            $result['prioritized'][0]['insight']['severity']
        );
        $this->assertEquals(
            AutomatedInsightsService::SEVERITY_HIGH,
            $result['prioritized'][1]['insight']['severity']
        );
        $this->assertGreaterThan(
            $result['prioritized'][1]['score'],
            $result['prioritized'][0]['score']
        );
    }

    /**
     * Test prioritizing insights includes summary
     */
    public function test_prioritize_insights_summary(): void
    {
        $insights = [
            [
                'type' => 'trend',
                'severity' => AutomatedInsightsService::SEVERITY_CRITICAL,
                'message' => 'Critical insight',
                'impact' => 'high',
                'confidence' => 0.9,
            ],
            [
                'type' => 'trend',
                'severity' => AutomatedInsightsService::SEVERITY_POSITIVE,
                'message' => 'Positive insight',
                'impact' => 'low',
                'confidence' => 0.7,
            ],
        ];

        $result = $this->service->prioritizeInsights($insights);

        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals(2, $result['summary']['total_insights']);
        $this->assertEquals(1, $result['summary']['critical_count']);
        $this->assertEquals(1, $result['summary']['positive_count']);
        $this->assertArrayHasKey('avg_score', $result['summary']);
    }

    /**
     * Test detecting patterns with empty data
     */
    public function test_detect_patterns_empty_data(): void
    {
        $result = $this->service->detectPatterns([]);

        $this->assertArrayHasKey('patterns', $result);
        $this->assertArrayHasKey('detected_at', $result);
        $this->assertArrayHasKey('data_points', $result);
        $this->assertEquals(0, $result['data_points']);
    }

    /**
     * Test detecting patterns with valid data
     */
    public function test_detect_patterns_valid_data(): void
    {
        $data = [
            ['date' => '2024-01-01', 'event_name' => 'page_view', 'count' => 100, 'unique_users' => 50],
            ['date' => '2024-01-02', 'event_name' => 'page_view', 'count' => 120, 'unique_users' => 60],
            ['date' => '2024-01-03', 'event_name' => 'login', 'count' => 80, 'unique_users' => 40],
        ];

        $result = $this->service->detectPatterns($data);

        $this->assertArrayHasKey('patterns', $result);
        $this->assertArrayHasKey('temporal', $result['patterns']);
        $this->assertArrayHasKey('behavioral', $result['patterns']);
        $this->assertArrayHasKey('seasonal', $result['patterns']);
    }

    /**
     * Test detecting temporal patterns
     */
    public function test_detect_temporal_patterns(): void
    {
        $data = [
            ['date' => '2024-01-01', 'event_name' => 'page_view', 'count' => 100],
            ['date' => '2024-01-02', 'event_name' => 'page_view', 'count' => 150],
            ['date' => '2024-01-03', 'event_name' => 'page_view', 'count' => 200],
        ];

        $result = $this->service->detectPatterns($data);

        $this->assertArrayHasKey('temporal', $result['patterns']);
        $this->assertArrayHasKey('peak_day', $result['patterns']['temporal']);
        $this->assertArrayHasKey('low_day', $result['patterns']['temporal']);
    }

    /**
     * Test detecting behavioral patterns
     */
    public function test_detect_behavioral_patterns(): void
    {
        $data = [
            ['date' => '2024-01-01', 'event_name' => 'page_view', 'count' => 100, 'unique_users' => 50],
            ['date' => '2024-01-02', 'event_name' => 'login', 'count' => 80, 'unique_users' => 40],
            ['date' => '2024-01-03', 'event_name' => 'purchase', 'count' => 20, 'unique_users' => 15],
        ];

        $result = $this->service->detectPatterns($data);

        $this->assertArrayHasKey('behavioral', $result['patterns']);
        $this->assertArrayHasKey('event_distribution', $result['patterns']['behavioral']);
        $this->assertArrayHasKey('top_events', $result['patterns']['behavioral']);
    }

    /**
     * Test insight constants are defined correctly
     */
    public function test_insight_type_constants(): void
    {
        $this->assertEquals('trend', AutomatedInsightsService::INSIGHT_TYPE_TREND);
        $this->assertEquals('anomaly', AutomatedInsightsService::INSIGHT_TYPE_ANOMALY);
        $this->assertEquals('correlation', AutomatedInsightsService::INSIGHT_TYPE_CORRELATION);
        $this->assertEquals('predictive', AutomatedInsightsService::INSIGHT_TYPE_PREDICTIVE);
        $this->assertEquals('retention', AutomatedInsightsService::INSIGHT_TYPE_RETENTION);
        $this->assertEquals('engagement', AutomatedInsightsService::INSIGHT_TYPE_ENGAGEMENT);
        $this->assertEquals('conversion', AutomatedInsightsService::INSIGHT_TYPE_CONVERSION);
    }

    /**
     * Test severity constants are defined correctly
     */
    public function test_severity_constants(): void
    {
        $this->assertEquals('critical', AutomatedInsightsService::SEVERITY_CRITICAL);
        $this->assertEquals('high', AutomatedInsightsService::SEVERITY_HIGH);
        $this->assertEquals('medium', AutomatedInsightsService::SEVERITY_MEDIUM);
        $this->assertEquals('low', AutomatedInsightsService::SEVERITY_LOW);
        $this->assertEquals('positive', AutomatedInsightsService::SEVERITY_POSITIVE);
    }

    /**
     * Test scoring insight with different confidence levels
     */
    public function test_score_insight_confidence_levels(): void
    {
        $baseInsight = [
            'type' => 'test',
            'severity' => AutomatedInsightsService::SEVERITY_MEDIUM,
            'message' => 'Test',
            'impact' => 'medium',
            'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'actionable' => true,
            'recommendation' => 'Test',
        ];

        $highConfidenceInsight = array_merge($baseInsight, ['confidence' => 0.95]);
        $lowConfidenceInsight = array_merge($baseInsight, ['confidence' => 0.3]);

        $highConfidence = $this->service->scoreInsight($highConfidenceInsight);
        $lowConfidence = $this->service->scoreInsight($lowConfidenceInsight);

        $this->assertGreaterThan($lowConfidence['factors']['confidence'], $highConfidence['factors']['confidence']);
    }

    /**
     * Test scoring insight with recency factor
     */
    public function test_score_insight_recency_factor(): void
    {
        $baseInsight = [
            'type' => 'test',
            'severity' => AutomatedInsightsService::SEVERITY_MEDIUM,
            'message' => 'Test',
            'impact' => 'medium',
            'confidence' => 0.5,
            'actionable' => true,
            'recommendation' => 'Test',
        ];

        $recent = $this->service->scoreInsight(array_merge($baseInsight, ['detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))]));
        $older = $this->service->scoreInsight(array_merge($baseInsight, ['detected_at' => date('Y-m-d H:i:s', strtotime('-3 days'))]));

        $this->assertGreaterThan($older['factors']['recency'] ?? 0, $recent['factors']['recency']);
    }

    /**
     * Test prioritizing insights groups by severity
     */
    public function test_prioritize_insights_groups_by_severity(): void
    {
        $insights = [
            ['type' => 'test', 'severity' => 'low', 'message' => 'Low'],
            ['type' => 'test', 'severity' => 'critical', 'message' => 'Critical'],
            ['type' => 'test', 'severity' => 'high', 'message' => 'High'],
            ['type' => 'test', 'severity' => 'medium', 'message' => 'Medium'],
            ['type' => 'test', 'severity' => 'positive', 'message' => 'Positive'],
        ];

        $result = $this->service->prioritizeInsights($insights);

        $this->assertArrayHasKey('grouped', $result);
        $this->assertArrayHasKey('critical', $result['grouped']);
        $this->assertArrayHasKey('high', $result['grouped']);
        $this->assertArrayHasKey('medium', $result['grouped']);
        $this->assertArrayHasKey('low', $result['grouped']);
        $this->assertArrayHasKey('positive', $result['grouped']);
        $this->assertCount(1, $result['grouped']['critical']);
        $this->assertCount(1, $result['grouped']['high']);
    }

    /**
     * Test service can be instantiated with dependencies
     */
    public function test_service_instantiation(): void
    {
        $service = new AutomatedInsightsService();

        $this->assertInstanceOf(AutomatedInsightsService::class, $service);
    }

    /**
     * Test detect patterns handles missing event_name
     */
    public function test_detect_patterns_missing_event_name(): void
    {
        $data = [
            ['date' => '2024-01-01', 'count' => 100],
            ['date' => '2024-01-02', 'count' => 150],
        ];

        $result = $this->service->detectPatterns($data);

        $this->assertArrayHasKey('patterns', $result);
        $this->assertArrayHasKey('behavioral', $result['patterns']);
    }

    /**
     * Test scoring insight handles missing recommendation
     */
    public function test_score_insight_missing_recommendation(): void
    {
        $insight = [
            'type' => 'test',
            'severity' => AutomatedInsightsService::SEVERITY_HIGH,
            'message' => 'Test without recommendation',
            'impact' => 'medium',
            'confidence' => 0.8,
            'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'actionable' => false,
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertEquals(0, $result['factors']['actionability']);
    }

    /**
     * Test score insight handles missing actionability field
     */
    public function test_score_insight_handles_missing_actionability(): void
    {
        $insight = [
            'type' => 'test',
            'severity' => AutomatedInsightsService::SEVERITY_MEDIUM,
            'message' => 'Test',
            'impact' => 'medium',
            'confidence' => 0.5,
            'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ];

        $result = $this->service->scoreInsight($insight);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('factors', $result);
    }

    /**
     * Test detect seasonal patterns
     */
    public function test_detect_seasonal_patterns(): void
    {
        $data = [
            ['date' => '2024-01-01', 'count' => 100],
            ['date' => '2024-01-08', 'count' => 120],
            ['date' => '2024-01-15', 'count' => 150],
            ['date' => '2024-01-22', 'count' => 110],
        ];

        $result = $this->service->detectPatterns($data);

        $this->assertArrayHasKey('seasonal', $result['patterns']);
        $this->assertArrayHasKey('weekly_totals', $result['patterns']['seasonal']);
        $this->assertArrayHasKey('week_changes', $result['patterns']['seasonal']);
    }
}
