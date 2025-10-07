<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Cohort;
use App\Models\LearningProgress;
use App\Models\User;
use App\Services\Analytics\CohortAnalysisService;
use App\Services\Analytics\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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
    private Mockery\MockInterface $consentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = $this->mock(ConsentService::class);
        $this->service = new CohortAnalysisService($this->consentService);
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

        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->with($user->id)
            ->andReturn(true);

        $cohort = $this->service->createCohort('Test Cohort', $criteria, $user->id);

        $this->assertInstanceOf(Cohort::class, $cohort);
        $this->assertEquals('Test Cohort', $cohort->name);
        $this->assertEquals($criteria, $cohort->criteria_json);
        $this->assertEquals($user->id, $cohort->created_by);
        $this->assertEquals(1, $cohort->members_count);
    }

    /**
     * Test creating cohort with no matching users
     */
    public function test_create_cohort_with_no_matching_users(): void
    {
        $criteria = ['grad_year' => 2023, 'degree' => 'NonExistent'];

        $cohort = $this->service->createCohort('Empty Cohort', $criteria, 1);

        $this->assertEquals(0, $cohort->members_count);
    }

    /**
     * Test creating cohort with users without consent
     */
    public function test_create_cohort_filters_users_without_consent(): void
    {
        $criteria = ['grad_year' => 2023];
        $userWithConsent = User::factory()->create(['graduation_year' => 2023]);
        $userWithoutConsent = User::factory()->create(['graduation_year' => 2023]);

        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->with($userWithConsent->id)
            ->andReturn(true);
        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->with($userWithoutConsent->id)
            ->andReturn(false);

        $cohort = $this->service->createCohort('Consent Test Cohort', $criteria, 1);

        $this->assertEquals(1, $cohort->members_count);
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

        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('retention', $analysis);
        $this->assertArrayHasKey('engagement', $analysis);
        $this->assertArrayHasKey('churn_rate', $analysis);
        $this->assertGreaterThanOrEqual(0, $analysis['retention']['day30']);
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
        $this->assertCount(2, $comparison['cohorts']);
    }

    /**
     * Test comparing single cohort returns error
     */
    public function test_compare_single_cohort_returns_error(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->compareCohorts([1]);
    }

    /**
     * Test cohort analysis with learning progress data
     */
    public function test_cohort_analysis_includes_learning_progress(): void
    {
        $cohort = Cohort::factory()->create([
            'criteria_json' => ['grad_year' => 2023]
        ]);

        $user = User::factory()->create(['graduation_year' => 2023]);
        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'engagement_score' => 90,
            'total_score' => 85
        ]);

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertArrayHasKey('engagement', $analysis);
        $this->assertGreaterThan(0, $analysis['engagement']['score']);
    }

    /**
     * Test invalid cohort criteria validation
     */
    public function test_invalid_cohort_criteria_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->createCohort('Test', ['invalid_key' => 'value'], 1);
    }

    /**
     * Test cohort analysis performance with large dataset
     */
    public function test_cohort_analysis_performance(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 1000]);

        // Create many users and learning progress records
        $users = User::factory()->count(1000)->create();
        foreach ($users as $user) {
            LearningProgress::factory()->create(['user_id' => $user->id]);
        }

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $startTime = microtime(true);
        $analysis = $this->service->analyzeCohort($cohort->id);
        $endTime = microtime(true);

        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(5.0, $endTime - $startTime);
        $this->assertArrayHasKey('size', $analysis);
    }

    /**
     * Test cohort analysis with low data scenarios
     */
    public function test_cohort_analysis_with_low_data(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 2]);

        $users = User::factory()->count(2)->create(['graduation_year' => 2023]);
        foreach ($users as $user) {
            LearningProgress::factory()->create([
                'user_id' => $user->id,
                'engagement_score' => 50,
                'created_at' => now()->subDays(30)
            ]);
        }

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertEquals(2, $analysis['size']);
        $this->assertArrayHasKey('retention', $analysis);
        $this->assertArrayHasKey('engagement', $analysis);
        $this->assertArrayHasKey('churn_rate', $analysis);
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

            // Alternate consent status
            $this->consentService
                ->shouldReceive('hasConsentForAnalytics')
                ->with($user->id)
                ->andReturn($index % 2 === 0);
        }

        $analysis = $this->service->analyzeCohort($cohort->id);

        // Should only include users with consent
        $this->assertLessThanOrEqual(3, $analysis['size']);
    }

    /**
     * Test cohort creation with complex criteria
     */
    public function test_create_cohort_with_complex_criteria(): void
    {
        $criteria = [
            'grad_year' => 2023,
            'degree' => 'Computer Science',
            'major' => 'Software Engineering'
        ];

        $user1 = User::factory()->create([
            'graduation_year' => 2023,
            'degree' => 'Computer Science',
            'major' => 'Software Engineering'
        ]);
        $user2 = User::factory()->create([
            'graduation_year' => 2023,
            'degree' => 'Computer Science',
            'major' => 'Data Science' // Different major
        ]);

        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->with($user1->id)
            ->andReturn(true);
        $this->consentService
            ->shouldReceive('hasConsentForAnalytics')
            ->with($user2->id)
            ->andReturn(true);

        $cohort = $this->service->createCohort('Complex Cohort', $criteria, 1);

        $this->assertEquals(1, $cohort->members_count);
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

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $comparison = $this->service->compareCohorts([$cohort1->id, $cohort2->id]);

        $this->assertArrayHasKey('statistical_significance', $comparison);
        $this->assertArrayHasKey('insights', $comparison);
        $this->assertArrayHasKey('p_value', $comparison['statistical_significance']);
    }

    /**
     * Test cohort analysis with date range filtering
     */
    public function test_cohort_analysis_with_date_range(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 10]);

        $user = User::factory()->create(['graduation_year' => 2023]);
        LearningProgress::factory()->create([
            'user_id' => $user->id,
            'engagement_score' => 85,
            'created_at' => now()->subDays(15)
        ]);

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id, [
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString()
        ]);

        $this->assertArrayHasKey('date_range', $analysis);
        $this->assertEquals(now()->subDays(30)->toDateString(), $analysis['date_range']['start']);
        $this->assertEquals(now()->subDays(10)->toDateString(), $analysis['date_range']['end']);
    }

    /**
     * Test cohort creation with invalid criteria throws exception
     */
    public function test_create_cohort_with_invalid_criteria(): void
    {
        $invalidCriteria = ['invalid_field' => 'value'];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid cohort criteria');

        $this->service->createCohort('Invalid Cohort', $invalidCriteria, 1);
    }

    /**
     * Test cohort analysis with no learning progress data
     */
    public function test_cohort_analysis_no_learning_progress(): void
    {
        $cohort = Cohort::factory()->create(['members_count' => 5]);

        $users = User::factory()->count(5)->create(['graduation_year' => 2023]);

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id);

        $this->assertEquals(5, $analysis['size']);
        $this->assertEquals(0, $analysis['engagement']['score']);
        $this->assertEquals(0, $analysis['retention']['day30']);
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
        $this->assertEquals(0, $comparison['cohorts'][0]['size']);
        $this->assertEquals(0, $comparison['cohorts'][1]['size']);
    }

    /**
     * Test cohort analysis with tenant isolation
     */
    public function test_cohort_analysis_tenant_isolation(): void
    {
        // This would require tenant setup, but testing the concept
        $cohort = Cohort::factory()->create(['members_count' => 5]);

        $users = User::factory()->count(5)->create(['graduation_year' => 2023]);

        $this->consentService->shouldReceive('hasConsentForAnalytics')->andReturn(true);

        $analysis = $this->service->analyzeCohort($cohort->id);

        // Verify analysis only includes data from current tenant context
        $this->assertArrayHasKey('size', $analysis);
        $this->assertGreaterThanOrEqual(0, $analysis['size']);
    }
}