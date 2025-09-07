<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user for authentication
        $this->user = $this->createUserWithRole('graduate');
    }

    public function test_career_analytics_routes_are_accessible()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/filter-options');

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
                ],
            ]);
    }

    public function test_career_analytics_overview_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/overview');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_alumni',
                    'employment_rate',
                    'average_salary',
                    'tracking_rate',
                    'top_industries',
                    'top_employers',
                    'geographic_distribution',
                ],
            ]);
    }

    public function test_career_analytics_salary_analysis_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/salary-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overall_statistics',
                    'progression_by_years',
                    'industry_comparison',
                    'percentile_distribution',
                    'growth_trends',
                ],
            ]);
    }

    public function test_career_analytics_program_effectiveness_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/program-effectiveness');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_career_analytics_industry_placement_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/industry-placement');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_career_analytics_demographic_outcomes_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/demographic-outcomes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_career_analytics_career_path_analysis_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/career-path-analysis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'path_distribution',
                    'success_metrics',
                    'progression_patterns',
                    'leadership_development',
                ],
            ]);
    }

    public function test_career_analytics_trend_analysis_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/trend-analysis');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_career_analytics_snapshots_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/career-analytics/snapshots');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_career_analytics_requires_authentication()
    {
        $response = $this->getJson('/api/career-analytics/overview');

        $response->assertStatus(401);
    }

    public function test_career_analytics_export_endpoint()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/career-analytics/export', [
                'format' => 'csv',
                'data_type' => 'overview',
                'filters' => [],
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
