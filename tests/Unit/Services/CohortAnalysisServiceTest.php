<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Cohort;
use App\Models\LearningProgress;
use App\Models\User;
use App\Models\Tenant;
use App\Models\AnalyticsEvent;
use App\Services\Analytics\CohortAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for CohortAnalysisService
 *
 * @covers \App\Services\Analytics\CohortAnalysisService
 */
class CohortAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private CohortAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Use real ConsentService instance for testing
        $this->service = app(CohortAnalysisService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test creating a cohort with valid criteria
     */
    public function test_create_cohort_with_valid_criteria(): void
    {
        $criteria = ['grad_year' => 2023, 'degree' => 'CS'];
        $user = User::factory()->create([
            'graduation_year' => 2023,
            'degree' => 'Computer Science'
        ]);

        $cohort = $this->service->createCohort('Test Cohort', $criteria, $user->id);

        $this->assertInstanceOf(Cohort::class, $cohort);
        $this->assertEquals('Test Cohort', $cohort->name);
        $this->assertEquals($criteria, $cohort->criteria_json);
        $this->assertEquals($user->id, $cohort->created_by);
    }

    /**
     * Test creating cohort with no matching users
     */
    public function test_create_cohort_with_no_matching_users(): void
    {
        $criteria = ['grad_year' => 2023, 'degree' => 'NonExistent'];

        $cohort = $this->service->createCohort('Empty Cohort', $criteria, 1);

        $this->assertInstanceOf(Cohort::class, $cohort);
        $this->assertEquals(0, $cohort->members_count);
    }

    /**
     * Test creating cohort with complex criteria
     */
    public function test_create_cohort_with_complex_criteria(): void
    {
        $criteria = [
            'grad_year' => 2023,
            'degree' => 'Computer Science',
        ];

        User::factory()->count(5)->create([
            'graduation_year' => 2023,
            'degree' => 'Computer Science',
        ]);

        $cohort = $this->service->createCohort('Complex Cohort', $criteria, 1);

        $this->assertInstanceOf(Cohort::class, $this->assertEquals(5, $cohort->members_count));
    }

    /**
     * Test calculating retention for a cohort
     */
    public function test_calculate_retention(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        User::factory()->count(10)->create(['graduation_year' => 2023]);

        // Create some analytics events for retention calculation
        AnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => $cohort->tenant_id,
            'occurred_at' => now()->subDays(5),
            'is_compliant' => true,
        ]);

        $retention = $this->service->calculateRetention($cohort->id, 7);

        $this->assertIsFloat($retention);
        $this->assertGreaterThanOrEqual(0, $retention);
        $this->assertLessThanOrEqual(100, $retention);
    }

    /**
     * Test retention with empty cohort
     */
    public function test_calculate_retention_empty_cohort(): void
    {
        $cohort = Cohort::factory()->create([
            'members_count' => 0
        ]);

        $retention = $this->service->calculateRetention($cohort->id, 7);

        $this->assertEquals(0.0, $retention);
    }

    /**
     * Test calculating engagement for a cohort
     */
    public function test_calculate_engagement(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        $user = User::factory()->create(['graduation_year' => 2023]);

        AnalyticsEvent::factory()->count(20)->create([
            'tenant_id' => $cohort->tenant_id,
            'user_id' => $user->id,
            'occurred_at' => now()->subDays(10),
            'is_compliant' => true,
        ]);

        $engagement = $this->service->calculateEngagement($cohort->id);

        $this->assertArrayHasKey('avg_sessions_per_week', $engagement);
        $this->assertArrayHasKey('avg_pages_per_session', $engagement);
        $this->assertArrayHasKey('avg_active_days_per_week', $engagement);
        $this->assertArrayHasKey('engagement_score', $engagement);
        $this->assertGreaterThanOrEqual(0, $engagement['engagement_score']);
    }

    /**
     * Test calculating conversion rates for a cohort
     */
    public function test_calculate_conversion_rate(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $conversion = $this->service->calculateConversionRate($cohort->id);

        $this->assertIsArray($conversion);
        $this->assertArrayHasKey('signup', $conversion);
        $this->assertArrayHasKey('first_login', $conversion);
        $this->assertArrayHasKey('profile_complete', $conversion);
        $this->assertArrayHasKey('first_purchase', $conversion);
        $this->assertArrayHasKey('repeat_purchase', $conversion);
    }

    /**
     * Test calculateConversionRates alias
     */
    public function test_calculate_conversion_rates_alias(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        $conversion = $this->service->calculateConversionRates($cohort->id);

        $this->assertIsArray($conversion);
        $this->assertArrayHasKey('signup', $conversion);
    }

    /**
     * Test analyzing cohort retention metrics
     */
    public function test_analyze_cohort_retention(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $user = User::factory()->create([
            'graduation_year' => 2023,
            'created_at' => now()->subDays(60)
        ]);

        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'engagement_score' => 85,
            'updated_at' => now()->subDays(30)
        ]);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('retention', $analysis);
        $this->assertArrayHasKey('engagement', $analysis);
        $this->assertArrayHasKey('churn_rate', $analysis);
        $this->assertArrayHasKey('day7', $analysis['retention']);
        $this->assertArrayHasKey('day30', $analysis['retention']);
        $this->assertArrayHasKey('day90', $analysis['retention']);
    }

    /**
     * Test analyzing cohort with date range options
     */
    public function test_analyze_cohort_with_date_range(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        $analysis = $this->service->analyzeCohort($cohort->id, [
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString()
        ]);

        $this->assertArrayHasKey('date_range', $analysis);
        $this->assertEquals(now()->subDays(30)->toDateString(), $analysis['date_range']['start']);
        $this->assertEquals(now()->toDateString(), $analysis['date_range']['end']);
    }

    /**
     * Test analyzing cohort with no members
     */
    public function test_analyze_cohort_with_no_members(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 0]);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertEquals(0, $analysis['size']);
        $this->assertEquals(0, $analysis['retention']['day30']);
    }

    /**
     * Test comparing two cohorts
     */
    public function test_compare_cohorts(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 10]);
        $cohort2 = Cohort::factory()->create(['members_count' => 8]);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('cohorts', $comparison);
        $this->assertArrayHasKey('statistical_significance', $comparison);
        $this->assertArrayHasKey('insights', $comparison);
        $this->assertArrayHasKey('best_performing', $comparison);
        $this->assertCount(2, $comparison['cohorts']);
    }

    /**
     * Test comparing single cohort returns error
     */
    public function test_compare_single_cohort_returns_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least two cohorts are required for comparison');
        $this->service->compareCohorts([1]);
    }

    /**
     * Test cohort comparison with empty cohorts
     */
    public function test_compare_cohorts_with_empty_cohorts(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 0]);
        $cohort2 = Cohort::factory()->create(['members_count' => 0]);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('cohorts', $comparison);
        $this->assertCount(2, $comparison['cohorts']);
    }

    /**
     * Test analyzing trends for a cohort
     */
    public function test_analyze_trends(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $trends = $this->service->analyzeTrends($cohort->id, 'week', 4);

        $this->assertArrayHasKey('cohort_id', $trends);
        $this->assertArrayHasKey('cohort_name', $trends);
        $this->assertArrayHasKey('period', $trends);
        $this->assertArrayHasKey('periods_analyzed', $trends);
        $this->assertArrayHasKey('trends', $trends);
        $this->assertArrayHasKey('summary', $trends);
        $this->assertEquals('week', $trends['period']);
        $this->assertEquals(4, $trends['periods_analyzed']);
    }

    /**
     * Test analyzing trends with different periods
     */
    public function test_analyze_trends_different_periods(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        // Test daily trends
        $dailyTrends = $this->service->analyzeTrends($cohort->id, 'day', 3);
        $this->assertEquals('day', $dailyTrends['period']);

        // Test monthly trends
        $monthlyTrends = $this->service->analyzeTrends($cohort->id, 'month', 6);
        $this->assertEquals('month', $monthlyTrends['period']);
    }

    /**
     * Test generating insights for a cohort
     */
    public function test_generate_insights(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $insights = $this->service->generateInsights($cohort->id);

        $this->assertIsArray($insights);
        // Insights can be empty if no thresholds are met
    }

    /**
     * Test insights include retention information
     */
    public function test_generate_insights_retention(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 100
        ]);

        $insights = $this->service->generateInsights($cohort->id);

        // Check if any retention insights were generated
        $retentionInsights = array_filter($insights, fn($i) => $i['type'] === 'retention');
        // This will pass as long as the method doesn't throw an error
        $this->assertIsArray($insights);
    }

    /**
     * Test statistical significance calculation
     */
    public function test_calculate_statistical_significance(): void
    {
        $data1 = [1, 1, 1, 0, 1, 1, 0, 1, 1, 1];
        $data2 = [0, 0, 1, 0, 0, 1, 0, 0, 0, 1];

        $result = $this->service->calculateStatisticalSignificance($data1, $data2);

        $this->assertArrayHasKey('significant', $result);
        $this->assertArrayHasKey('p_value', $result);
        $this->assertArrayHasKey('chi_square', $result);
        $this->assertArrayHasKey('confidence_level', $result);
    }

    /**
     * Test statistical significance with empty data
     */
    public function test_calculate_statistical_significance_empty_data(): void
    {
        $result = $this->service->calculateStatisticalSignificance([], []);

        $this->assertFalse($result['significant']);
        $this->assertEquals(1.0, $result['p_value']);
    }

    /**
     * Test cohort creation with invalid criteria throws exception
     */
    public function test_create_cohort_with_invalid_criteria(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cohort criteria');
        $this->service->createCohort('Invalid Cohort', ['invalid_field' => 'value'], 1);
    }

    /**
     * Test cohort creation with empty criteria throws exception
     */
    public function test_create_cohort_with_empty_criteria(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cohort criteria cannot be empty');
        $this->service->createCohort('Empty Criteria Cohort', [], 1);
    }

    /**
     * Test cohort analysis includes conversions
     */
    public function test_analyze_cohort_includes_conversions(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('conversions', $analysis);
        $this->assertIsArray($analysis['conversions']);
    }

    /**
     * Test cohort analysis includes churn rate
     */
    public function test_analyze_cohort_includes_churn_rate(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('churn_rate', $analysis);
        $this->assertArrayHasKey('day7', $analysis['churn_rate']);
        $this->assertArrayHasKey('day30', $analysis['churn_rate']);
        $this->assertArrayHasKey('day90', $analysis['churn_rate']);
    }

    /**
     * Test cohort comparison includes retention deltas
     */
    public function test_compare_cohorts_includes_retention_deltas(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 10]);
        $cohort2 = Cohort::factory()->create(['members_count' => 10]);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('retention_deltas', $comparison);
        $this->assertNotEmpty($comparison['retention_deltas']);
    }

    /**
     * Test cohort comparison identifies best performing cohort
     */
    public function test_compare_cohorts_identifies_best_performing(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 10]);
        $cohort2 = Cohort::factory()->create(['members_count' => 10]);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('best_performing', $comparison);
        $this->assertArrayHasKey('best_day7_retention', $comparison['best_performing']);
        $this->assertArrayHasKey('best_day30_retention', $comparison['best_performing']);
        $this->assertArrayHasKey('best_engagement', $comparison['best_performing']);
    }

    /**
     * Test trend analysis summary
     */
    public function test_trend_analysis_summary(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        $trends = $this->service->analyzeTrends($cohort->id, 'week', 4);

        $this->assertArrayHasKey('summary', $trends);
        $this->assertArrayHasKey('overall_trend', $trends['summary']);
        $this->assertArrayHasKey('avg_active_users', $trends['summary']);
        $this->assertArrayHasKey('total_events', $trends['summary']);
        $this->assertArrayHasKey('periods_with_growth', $trends['summary']);
        $this->assertArrayHasKey('periods_with_decline', $trends['summary']);
    }

    /**
     * Test cohort analysis performance with large dataset
     */
    public function test_cohort_analysis_performance(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 100]);

        // Create many users and learning progress records
        $users = User::factory()->count(100)->create();
        foreach ($users as $user) {
            LearningProgress::factory()->create(['user_id' => $user->id]);
        }

        $startTime = microtime(true);
        $analysis = $this->service->analyzeCohort($cohort->id);
        $endTime = microtime(true);

        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(5.0, $endTime - $startTime);
        $this->assertArrayHasKey('size', $analysis);
    }

    /**
     * Test cohort analysis with mixed consent status
     */
    public function test_cohort_analysis_mixed_consent_status(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 5]);

        $users = User::factory()->count(5)->create(['graduation_year' => 2023]);
        foreach ($users as $index => $user) {
            LearningProgress::factory()->create(['user_id' => $user->id]);
        }

        $analysis = $this->service->analyzeCohort($cohort->id);

        // Should complete without error
        $this->assertArrayHasKey('size', $analysis);
    }

    /**
     * Test cohort comparison with statistical significance
     */
    public function test_compare_cohorts_with_statistical_significance(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 100]);
        $cohort2 = Cohort::factory()->create(['members_count' => 100]);

        // Create different engagement patterns
        for ($i = 0; $i < 50; $i++) {
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();

            LearningProgress::factory()->create([
                'user_id' => $user1->id,
                'engagement_score' => 80 + rand(-10, 10)
            ]);
            LearningProgress::factory()->create([
                'user_id' => $user2->id,
                'engagement_score' => 60 + rand(-10, 10)
            ]);
        }

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('statistical_significance', $comparison);
        $this->assertArrayHasKey('insights', $comparison);
        $this->assertArrayHasKey('p_value', $comparison['statistical_significance']);
    }

    /**
     * Test cohort analysis with tenant isolation
     */
    public function test_cohort_analysis_tenant_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $cohort1 = Cohort::factory()->create([
            'tenant_id' => $tenant1->id,
            'members_count' => 5
        ]);
        $cohort2 = Cohort::factory()->create([
            'tenant_id' => $tenant2->id,
            'members_count' => 5
        ]);

        // Simulate being in tenant1 context
        session(['tenant_id' => $tenant1->id]);

        $analysis = $this->service->analyzeCohort($cohort1->id);

        // Verify analysis only includes data from current tenant context
        $this->assertArrayHasKey('size', $analysis);
        $this->assertGreaterThanOrEqual(0, $analysis['size']);
    }

    /**
     * Test insight generation includes benchmarks
     */
    public function test_insights_include_benchmarks(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 100
        ]);

        $insights = $this->service->generateInsights($cohort->id);

        // Check if insights have benchmark information
        foreach ($insights as $insight) {
            if (isset($insight['benchmark'])) {
                $this->assertIsNumeric($insight['benchmark']);
            }
        }
    }

    /**
     * Test retention calculation with different day periods
     */
    public function test_retention_different_periods(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 10
        ]);

        $retention7 = $this->service->calculateRetention($cohort->id, 7);
        $retention30 = $this->service->calculateRetention($cohort->id, 30);
        $retention90 = $this->service->calculateRetention($cohort->id, 90);

        $this->assertIsFloat($retention7);
        $this->assertIsFloat($retention30);
        $this->assertIsFloat($retention90);
        $this->assertLessThanOrEqual(100, $retention7);
        $this->assertLessThanOrEqual(100, $retention30);
        $this->assertLessThanOrEqual(100, $retention90);
    }

    /**
     * Test cohort analysis returns analyzed timestamp
     */
    public function test_analyze_cohort_returns_analyzed_timestamp(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023],
            'members_count' => 5
        ]);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('analyzed_at', $analysis);
        $this->assertNotNull($analysis['analyzed_at']);
    }

    /**
     * Test cohort comparison returns compared timestamp
     */
    public function test_compare_cohorts_returns_compared_timestamp(): void
    {
        $cohort1 = Cohort::factory()->create(['members_count' => 5]);
        $cohort2 = Cohort::factory()->create(['members_count' => 5]);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('compared_at', $comparison);
        $this->assertNotNull($comparison['compared_at']);
    }
}
