<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SalaryProgression;
use App\Models\CareerPath;
use App\Models\CareerTimeline;
use App\Models\EducationHistory;
use App\Services\CareerOutcomeAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CareerOutcomeAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    private CareerOutcomeAnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = app(CareerOutcomeAnalyticsService::class);
    }

    public function test_can_generate_overview_metrics()
    {
        // Create test data
        $users = User::factory(10)->create();
        
        foreach ($users as $user) {
            EducationHistory::factory()->create([
                'graduate_id' => $user->id,
                'end_year' => 2020,
                'degree' => 'Computer Science',
            ]);

            CareerTimeline::factory()->create([
                'user_id' => $user->id,
                'is_current' => true,
                'industry' => 'Technology',
            ]);

            SalaryProgression::factory()->create([
                'user_id' => $user->id,
                'effective_date' => now()->subMonths(6),
                'years_since_graduation' => 3,
            ]);
        }

        $overview = $this->analyticsService->getOverviewMetrics([
            'graduation_year' => '2020'
        ]);

        $this->assertArrayHasKey('total_alumni', $overview);
        $this->assertArrayHasKey('employment_rate', $overview);
        $this->assertArrayHasKey('average_salary', $overview);
        $this->assertArrayHasKey('tracking_rate', $overview);
        $this->assertEquals(10, $overview['total_alumni']);
        $this->assertGreaterThan(0, $overview['employment_rate']);
    }

    public function test_can_generate_salary_analysis()
    {
        // Create test data
        $users = User::factory(5)->create();
        
        foreach ($users as $user) {
            SalaryProgression::factory(3)->create([
                'user_id' => $user->id,
                'industry' => 'Technology',
            ]);
        }

        $analysis = $this->analyticsService->getSalaryAnalysis([
            'industry' => 'Technology'
        ]);

        $this->assertArrayHasKey('overall_statistics', $analysis);
        $this->assertArrayHasKey('progression_by_years', $analysis);
        $this->assertArrayHasKey('industry_comparison', $analysis);
    }

    public function test_can_generate_career_path_analysis()
    {
        // Create test data
        $users = User::factory(8)->create();
        
        foreach ($users as $user) {
            CareerPath::factory()->create([
                'user_id' => $user->id,
            ]);
        }

        $analysis = $this->analyticsService->getCareerPathAnalysis();

        $this->assertArrayHasKey('path_distribution', $analysis);
        $this->assertArrayHasKey('success_metrics', $analysis);
        $this->assertArrayHasKey('progression_patterns', $analysis);
        $this->assertArrayHasKey('leadership_development', $analysis);
    }

    public function test_api_returns_comprehensive_analytics()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create some test data
        EducationHistory::factory(3)->create();
        SalaryProgression::factory(5)->create();
        CareerPath::factory(3)->create();

        $response = $this->getJson('/api/career-analytics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'overview',
                        'program_effectiveness',
                        'salary_analysis',
                        'industry_placement',
                        'demographic_outcomes',
                        'career_paths',
                        'trends',
                    ]
                ]);
    }

    public function test_api_returns_filter_options()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/career-analytics/filter-options');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'graduation_years',
                        'programs',
                        'departments',
                        'industries',
                        'demographic_types',
                        'career_path_types',
                        'trend_types',
                    ]
                ]);
    }

    public function test_can_generate_program_effectiveness()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create test data
        $graduates = User::factory(5)->create();
        foreach ($graduates as $graduate) {
            EducationHistory::factory()->create([
                'graduate_id' => $graduate->id,
                'degree' => 'Computer Science',
                'end_year' => '2020',
            ]);

            CareerTimeline::factory()->create([
                'user_id' => $graduate->id,
                'start_date' => '2020-07-01',
                'is_current' => true,
            ]);
        }

        $response = $this->postJson('/api/career-analytics/program-effectiveness/generate', [
            'program' => 'Computer Science',
            'graduation_year' => '2020',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'program_name',
                        'graduation_year',
                        'total_graduates',
                        'employment_rate_6_months',
                        'employment_rate_1_year',
                        'employment_rate_2_years',
                    ]
                ]);
    }

    public function test_can_generate_snapshot()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/career-analytics/generate-snapshot', [
            'period_type' => 'yearly',
            'period_start' => '2023-01-01',
            'period_end' => '2023-12-31',
            'graduation_year' => '2020',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'period_type',
                        'period_start',
                        'period_end',
                        'metrics',
                        'total_graduates',
                        'tracked_graduates',
                    ]
                ]);
    }

    public function test_salary_progression_model_calculations()
    {
        $salaryProgression = SalaryProgression::factory()->create([
            'salary' => 80000,
            'salary_type' => 'annual',
        ]);

        $this->assertEquals(80000, $salaryProgression->annualized_salary);
        $this->assertEquals('80,000 USD', $salaryProgression->formatted_salary);
    }

    public function test_career_path_model_calculations()
    {
        $careerPath = CareerPath::factory()->create([
            'total_job_changes' => 3,
            'promotions_count' => 2,
            'salary_growth_rate' => 15.5,
        ]);

        $this->assertIsFloat($careerPath->job_stability_score);
        $this->assertIsFloat($careerPath->career_velocity);
        $this->assertEquals('Linear Career Progression', $careerPath->path_type_display);
    }
}