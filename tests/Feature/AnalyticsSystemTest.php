<?php

namespace Tests\Feature;

use App\Models\AnalyticsSnapshot;
use App\Models\Course;
use App\Models\CustomReport;
use App\Models\Employer;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\Prediction;
use App\Models\PredictionModel;
use App\Models\ReportExecution;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ReportBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AnalyticsSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $analyticsService;

    protected $reportBuilderService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyticsService = app(AnalyticsService::class);
        $this->reportBuilderService = app(ReportBuilderService::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_access_analytics_dashboard()
    {
        $response = $this->get(route('analytics.dashboard'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Analytics/Dashboard'));
    }

    /** @test */
    public function it_can_generate_daily_analytics_snapshot()
    {
        // Create test data
        $course = Course::factory()->create();
        Graduate::factory()->count(10)->create(['course_id' => $course->id]);
        Job::factory()->count(5)->create();

        $snapshot = $this->analyticsService->generateDailySnapshot();

        $this->assertInstanceOf(AnalyticsSnapshot::class, $snapshot);
        $this->assertEquals('daily', $snapshot->type);
        $this->assertIsArray($snapshot->data);
        $this->assertArrayHasKey('overview', $snapshot->data);
        $this->assertArrayHasKey('employment', $snapshot->data);
    }

    /** @test */
    public function it_can_calculate_kpi_values()
    {
        // Create KPI definition
        $kpi = KpiDefinition::create([
            'name' => 'Test Employment Rate',
            'key' => 'test_employment_rate',
            'description' => 'Test KPI for employment rate',
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
            'warning_threshold' => 70.0,
            'is_active' => true,
        ]);

        // Create test graduates
        Graduate::factory()->count(8)->create([
            'employment_status' => ['status' => 'employed'],
        ]);
        Graduate::factory()->count(2)->create([
            'employment_status' => ['status' => 'unemployed'],
        ]);

        $results = $this->analyticsService->calculateKpiValues();

        $this->assertArrayHasKey('test_employment_rate', $results);
        $this->assertEquals(80.0, $results['test_employment_rate']);

        // Check that KPI value was stored
        $kpiValue = KpiValue::where('kpi_definition_id', $kpi->id)->first();
        $this->assertNotNull($kpiValue);
        $this->assertEquals(80.0, $kpiValue->value);
    }

    /** @test */
    public function it_can_create_custom_report()
    {
        $reportData = [
            'name' => 'Test Employment Report',
            'description' => 'Test report for employment data',
            'type' => 'employment',
            'filters' => ['course_id' => 1],
            'columns' => ['graduate_name', 'course_name', 'employment_status'],
            'is_scheduled' => false,
            'is_public' => false,
        ];

        $response = $this->post(route('analytics.reports.create'), $reportData);

        $response->assertRedirect(route('analytics.reports'));
        $this->assertDatabaseHas('custom_reports', [
            'name' => 'Test Employment Report',
            'type' => 'employment',
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_execute_custom_report()
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(5)->create(['course_id' => $course->id]);

        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'description' => 'Test report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name', 'course_name'],
        ]);

        $execution = $this->reportBuilderService->executeReport($report);

        $this->assertInstanceOf(ReportExecution::class, $execution);
        $this->assertEquals('completed', $execution->status);
        $this->assertNotNull($execution->result_data);
        $this->assertArrayHasKey('data', $execution->result_data);
    }

    /** @test */
    public function it_can_generate_report_preview()
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(3)->create(['course_id' => $course->id]);

        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Preview Test Report',
            'description' => 'Test report for preview',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name', 'course_name'],
        ]);

        $preview = $this->reportBuilderService->getReportPreview($report);

        $this->assertIsArray($preview);
        $this->assertArrayHasKey('data', $preview);
        $this->assertArrayHasKey('preview_info', $preview);
        $this->assertTrue($preview['preview_info']['is_preview']);
    }

    /** @test */
    public function it_can_create_prediction_model()
    {
        $modelData = [
            'name' => 'Test Job Placement Predictor',
            'type' => 'job_placement',
            'description' => 'Test prediction model',
            'features' => ['gpa', 'skills_count'],
            'model_config' => [
                'feature_weights' => ['gpa' => 0.6, 'skills_count' => 0.4],
                'max_score' => 100,
            ],
            'is_active' => true,
        ];

        $model = PredictionModel::create($modelData);

        $this->assertInstanceOf(PredictionModel::class, $model);
        $this->assertEquals('job_placement', $model->type);
        $this->assertTrue($model->is_active);
    }

    /** @test */
    public function it_can_generate_predictions()
    {
        $course = Course::factory()->create();
        $graduate = Graduate::factory()->create([
            'course_id' => $course->id,
            'gpa' => 3.5,
            'skills' => ['PHP', 'Laravel', 'JavaScript'],
        ]);

        $model = PredictionModel::create([
            'name' => 'Test Predictor',
            'type' => 'job_placement',
            'description' => 'Test model',
            'features' => ['gpa', 'skills_count'],
            'model_config' => [
                'feature_weights' => ['gpa' => 0.6, 'skills_count' => 0.4],
                'max_score' => 100,
            ],
            'is_active' => true,
        ]);

        $prediction = $model->predict($graduate);

        $this->assertInstanceOf(Prediction::class, $prediction);
        $this->assertEquals($graduate->id, $prediction->subject_id);
        $this->assertEquals(get_class($graduate), $prediction->subject_type);
        $this->assertGreaterThanOrEqual(0, $prediction->prediction_score);
        $this->assertLessThanOrEqual(1, $prediction->prediction_score);
    }

    /** @test */
    public function it_can_get_employment_analytics()
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'employed'],
        ]);
        Graduate::factory()->count(3)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'unemployed'],
        ]);

        $analytics = $this->analyticsService->getEmploymentAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('summary', $analytics);
        $this->assertArrayHasKey('by_course', $analytics);
        $this->assertArrayHasKey('by_year', $analytics);

        $this->assertEquals(8, $analytics['summary']['total_graduates']);
        $this->assertEquals(5, $analytics['summary']['employed_count']);
        $this->assertEquals(62.5, $analytics['summary']['employment_rate']);
    }

    /** @test */
    public function it_can_get_job_market_analytics()
    {
        $employer = Employer::factory()->create();
        Job::factory()->count(10)->create(['employer_id' => $employer->id]);

        $analytics = $this->analyticsService->getJobMarketAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('market_overview', $analytics);
        $this->assertArrayHasKey('demand_analysis', $analytics);
        $this->assertArrayHasKey('salary_trends', $analytics);
    }

    /** @test */
    public function it_can_export_analytics_data()
    {
        $course = Course::factory()->create();
        Graduate::factory()->count(3)->create(['course_id' => $course->id]);

        $exportData = $this->analyticsService->exportAnalyticsData('employment', [], 'json');

        $this->assertIsString($exportData);
        $decodedData = json_decode($exportData, true);
        $this->assertIsArray($decodedData);
    }

    /** @test */
    public function it_can_access_kpis_page()
    {
        KpiDefinition::factory()->count(3)->create();

        $response = $this->get(route('analytics.kpis'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Analytics/Kpis'));
    }

    /** @test */
    public function it_can_access_predictions_page()
    {
        PredictionModel::factory()->count(2)->create();

        $response = $this->get(route('analytics.predictions'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Analytics/Predictions'));
    }

    /** @test */
    public function it_can_access_reports_page()
    {
        CustomReport::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->get(route('analytics.reports'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Analytics/Reports'));
    }

    /** @test */
    public function it_can_calculate_kpis_via_api()
    {
        KpiDefinition::factory()->create([
            'key' => 'test_kpi',
            'calculation_method' => 'count',
            'calculation_config' => [
                'query' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => [],
                ],
            ],
        ]);

        Graduate::factory()->count(5)->create();

        $response = $this->post(route('analytics.calculate-kpis'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_generate_predictions_via_api()
    {
        PredictionModel::factory()->create();
        Graduate::factory()->count(3)->create();

        $response = $this->post(route('analytics.generate-predictions'));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function it_can_export_data_via_api()
    {
        Graduate::factory()->count(3)->create();

        $response = $this->post(route('analytics.export'), [
            'type' => 'employment',
            'format' => 'json',
            'filters' => [],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function it_validates_report_creation_data()
    {
        $response = $this->post(route('analytics.reports.create'), [
            'name' => '', // Required field missing
            'type' => 'invalid_type',
            'columns' => 'not_an_array',
        ]);

        $response->assertSessionHasErrors(['name', 'type', 'columns']);
    }

    /** @test */
    public function it_prevents_unauthorized_report_execution()
    {
        $otherUser = User::factory()->create();
        $report = CustomReport::create([
            'user_id' => $otherUser->id,
            'name' => 'Private Report',
            'description' => 'Private report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name'],
            'is_public' => false,
        ]);

        $response = $this->post(route('analytics.reports.execute', $report->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_allows_public_report_execution()
    {
        $otherUser = User::factory()->create();
        $report = CustomReport::create([
            'user_id' => $otherUser->id,
            'name' => 'Public Report',
            'description' => 'Public report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name'],
            'is_public' => true,
        ]);

        Graduate::factory()->count(2)->create();

        $response = $this->post(route('analytics.reports.execute', $report->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }
}
