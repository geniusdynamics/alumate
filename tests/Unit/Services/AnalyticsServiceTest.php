<?php

namespace Tests\Unit\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\Course;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\KpiDefinition;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = app(AnalyticsService::class);
    }

    public function test_can_generate_daily_snapshot(): void
    {
        // Create test data
        Course::factory()->count(3)->create();
        Graduate::factory()->count(10)->create();
        Job::factory()->count(5)->create();

        $snapshot = $this->analyticsService->generateDailySnapshot();

        $this->assertInstanceOf(AnalyticsSnapshot::class, $snapshot);
        $this->assertEquals('daily', $snapshot->type);
        $this->assertIsArray($snapshot->data);
        $this->assertArrayHasKey('overview', $snapshot->data);
        $this->assertArrayHasKey('employment', $snapshot->data);
        $this->assertArrayHasKey('job_market', $snapshot->data);
    }

    public function test_can_calculate_employment_rate(): void
    {
        // Create graduates with different employment statuses
        Graduate::factory()->count(7)->create(['employment_status' => ['status' => 'employed']]);
        Graduate::factory()->count(3)->create(['employment_status' => ['status' => 'unemployed']]);

        $rate = $this->analyticsService->calculateEmploymentRate();

        $this->assertEquals(70.0, $rate);
    }

    public function test_can_get_employment_analytics(): void
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'employed'],
        ]);
        Graduate::factory()->count(2)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'unemployed'],
        ]);

        $analytics = $this->analyticsService->getEmploymentAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertArrayHasKey('by_course', $analytics);
        $this->assertEquals(7, $analytics['summary']['total_graduates']);
        $this->assertEquals(5, $analytics['summary']['employed_count']);
        $this->assertEquals(71.43, round($analytics['summary']['employment_rate'], 2));
    }

    public function test_can_get_job_market_analytics(): void
    {
        $employer = Employer::factory()->create();
        Job::factory()->count(8)->create(['employer_id' => $employer->id]);
        JobApplication::factory()->count(15)->create();

        $analytics = $this->analyticsService->getJobMarketAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('market_overview', $analytics);
        $this->assertArrayHasKey('demand_analysis', $analytics);
        $this->assertArrayHasKey('salary_trends', $analytics);
    }

    public function test_can_calculate_kpi_values(): void
    {
        // Create KPI definition
        KpiDefinition::create([
            'name' => 'Employment Rate',
            'key' => 'employment_rate',
            'description' => 'Percentage of employed graduates',
            'category' => 'employment',
            'calculation_method' => 'percentage',
            'calculation_config' => [
                'numerator' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => [
                        ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed'],
                    ],
                ],
                'denominator' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => [],
                ],
            ],
            'target_type' => 'minimum',
            'target_value' => 80.0,
            'is_active' => true,
        ]);

        // Create test data
        Graduate::factory()->count(8)->create(['employment_status' => ['status' => 'employed']]);
        Graduate::factory()->count(2)->create(['employment_status' => ['status' => 'unemployed']]);

        $results = $this->analyticsService->calculateKpiValues();

        $this->assertArrayHasKey('employment_rate', $results);
        $this->assertEquals(80.0, $results['employment_rate']);
    }

    public function test_can_export_analytics_data(): void
    {
        Graduate::factory()->count(5)->create();

        $exportData = $this->analyticsService->exportAnalyticsData('employment', [], 'json');

        $this->assertIsString($exportData);
        $decodedData = json_decode($exportData, true);
        $this->assertIsArray($decodedData);
        $this->assertArrayHasKey('data', $decodedData);
    }

    public function test_can_get_course_performance_metrics(): void
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(10)->create(['course_id' => $course->id]);

        $metrics = $this->analyticsService->getCoursePerformanceMetrics($course->id);

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_graduates', $metrics);
        $this->assertArrayHasKey('employment_rate', $metrics);
        $this->assertArrayHasKey('average_salary', $metrics);
        $this->assertArrayHasKey('job_placement_time', $metrics);
    }

    public function test_can_get_employer_analytics(): void
    {
        $employer = Employer::factory()->create();
        Job::factory()->count(5)->create(['employer_id' => $employer->id]);

        $analytics = $this->analyticsService->getEmployerAnalytics($employer->id);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('jobs_posted', $analytics);
        $this->assertArrayHasKey('applications_received', $analytics);
        $this->assertArrayHasKey('hires_made', $analytics);
        $this->assertArrayHasKey('response_rate', $analytics);
    }

    public function test_can_generate_trend_analysis(): void
    {
        // Create historical data
        Graduate::factory()->count(5)->create(['graduation_year' => 2023]);
        Graduate::factory()->count(8)->create(['graduation_year' => 2024]);

        $trends = $this->analyticsService->generateTrendAnalysis('employment', 'yearly');

        $this->assertIsArray($trends);
        $this->assertArrayHasKey('periods', $trends);
        $this->assertArrayHasKey('data', $trends);
        $this->assertArrayHasKey('trend_direction', $trends);
    }

    public function test_can_calculate_placement_success_rate(): void
    {
        $course = Course::factory()->create();

        // Create graduates with job applications
        $graduates = Graduate::factory()->count(10)->create(['course_id' => $course->id]);
        $job = Job::factory()->create();

        // 6 successful placements
        JobApplication::factory()->count(6)->create([
            'job_id' => $job->id,
            'graduate_id' => $graduates->random()->id,
            'status' => 'hired',
        ]);

        $successRate = $this->analyticsService->calculatePlacementSuccessRate($course->id);

        $this->assertEquals(60.0, $successRate);
    }

    public function test_can_get_salary_distribution(): void
    {
        Graduate::factory()->count(5)->create([
            'employment_status' => [
                'status' => 'employed',
                'salary' => 50000,
            ],
        ]);
        Graduate::factory()->count(3)->create([
            'employment_status' => [
                'status' => 'employed',
                'salary' => 75000,
            ],
        ]);

        $distribution = $this->analyticsService->getSalaryDistribution();

        $this->assertIsArray($distribution);
        $this->assertArrayHasKey('ranges', $distribution);
        $this->assertArrayHasKey('average', $distribution);
        $this->assertArrayHasKey('median', $distribution);
    }
}
