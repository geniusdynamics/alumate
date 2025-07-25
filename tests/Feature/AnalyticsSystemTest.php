<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Employer;
use App\Models\KpiDefinition;
use App\Models\KpiValue;
use App\Models\PredictionModel;
use App\Models\Prediction;
use App\Models\CustomReport;
use App\Models\ReportExecution;
use App\Models\AnalyticsSnapshot;
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
        Job::factory()->count(5)->create(['course_id' => $course->id]);
        
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
                        ['field' => 'employment_status->status', 'operator' => '=', 'value' => 'employed']
                    ]
                ],
                'denominator' => [
                    'model' => 'App\\Models\\Graduate',
                    'filters' => []
                ]
            ],
            'target_type' => 'minimum',
            'target_value' => 80.0,
            'is_active' => true,
        ]);

        // Create test graduates
        Graduate::factory()->count(8)->create([
            'employment_status' => ['status' => 'employed']
        ]);
        Graduate::factory()->count(2)->create([
            'employment_status' => ['status' => 'unemployed']
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
    public function it_can_generate_predictions()
    {
        // Create prediction model
        $model = PredictionModel::create([
            'name' => 'Test Job Placement Predictor',
            'type' => 'job_placement',
            'description' => 'Test prediction model',
            'features' => ['gpa', 'skills_count'],
            'model_config' => [
                'feature_weights' => ['gpa' => 0.5, 'skills_count' => 0.5],
                'max_score' => 100,
            ],
            'accuracy' => 0.75,
            'is_active' => true,
        ]);

        // Create test graduate
        $graduate = Graduate::factory()->create([
            'gpa' => 3.5,
            'skills' => ['PHP', 'JavaScript', 'Laravel'],
        ]);

        $prediction = $model->predict($graduate);
        
        $this->assertInstanceOf(Prediction::class, $prediction);
        $this->assertEquals($model->id, $prediction->prediction_model_id);
        $this->assertEquals(get_class($graduate), $prediction->subject_type);
        $this->assertEquals($graduate->id, $prediction->subject_id);
        $this->assertIsFloat($prediction->prediction_score);
        $this->assertIsArray($prediction->prediction_data);
    }

    /** @test */
    public function it_can_create_custom_report()
    {
        $reportData = [
            'name' => 'Test Employment Report',
            'description' => 'Test report description',
            'type' => 'employment',
            'filters' => ['employment_status' => 'employed'],
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
        // Create test data
        $course = Course::factory()->create(['name' => 'Computer Science']);
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'employed'],
        ]);

        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name', 'course_name', 'employment_status'],
        ]);

        $execution = $this->reportBuilderService->executeReport($report);
        
        $this->assertInstanceOf(ReportExecution::class, $execution);
        $this->assertEquals('completed', $execution->status);
        $this->assertIsArray($execution->result_data);
        $this->assertArrayHasKey('data', $execution->result_data);
        $this->assertCount(5, $execution->result_data['data']);
    }

    /** @test */
    public function it_can_preview_custom_report()
    {
        // Create test data
        $course = Course::factory()->create();
        Graduate::factory()->count(3)->create(['course_id' => $course->id]);

        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name', 'course_name'],
        ]);

        $preview = $this->reportBuilderService->getReportPreview($report);
        
        $this->assertIsArray($preview);
        $this->assertArrayHasKey('data', $preview);
        $this->assertArrayHasKey('preview_info', $preview);
        $this->assertLessThanOrEqual(100, count($preview['data'])); // Preview limit
    }

    /** @test */
    public function it_can_export_analytics_data()
    {
        // Create test data
        Graduate::factory()->count(3)->create([
            'employment_status' => ['status' => 'employed']
        ]);

        $data = $this->analyticsService->exportAnalyticsData('employment', [], 'json');
        
        $this->assertIsString($data);
        $decodedData = json_decode($data, true);
        $this->assertIsArray($decodedData);
    }

    /** @test */
    public function it_can_get_employment_analytics()
    {
        // Create test data
        $course = Course::factory()->create();
        Graduate::factory()->count(8)->create([
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
        $this->assertArrayHasKey('by_year', $analytics);
        
        // Check employment rate calculation
        $this->assertEquals(80.0, $analytics['summary']['employment_rate']);
    }

    /** @test */
    public function it_can_get_course_analytics()
    {
        // Create test data
        $course = Course::factory()->create();
        Graduate::factory()->count(5)->create([
            'course_id' => $course->id,
            'employment_status' => ['status' => 'employed'],
        ]);

        $analytics = $this->analyticsService->getCourseAnalytics($course->id);
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('performance', $analytics);
        $this->assertArrayHasKey('outcomes', $analytics);
    }

    /** @test */
    public function it_can_get_job_market_analytics()
    {
        // Create test data
        $employer = Employer::factory()->create();
        Job::factory()->count(3)->create(['employer_id' => $employer->id]);

        $analytics = $this->analyticsService->getJobMarketAnalytics();
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('market_overview', $analytics);
        $this->assertArrayHasKey('demand_analysis', $analytics);
    }

    /** @test */
    public function kpi_can_determine_status_correctly()
    {
        $kpi = KpiDefinition::create([
            'name' => 'Test KPI',
            'key' => 'test_kpi',
            'description' => 'Test KPI',
            'category' => 'test',
            'calculation_method' => 'percentage',
            'calculation_config' => [],
            'target_type' => 'minimum',
            'target_value' => 80.0,
            'warning_threshold' => 70.0,
            'is_active' => true,
        ]);

        // Test good status
        KpiValue::create([
            'kpi_definition_id' => $kpi->id,
            'measurement_date' => now()->toDateString(),
            'value' => 85.0,
        ]);
        $this->assertEquals('good', $kpi->fresh()->getStatus());

        // Test warning status
        KpiValue::where('kpi_definition_id', $kpi->id)->update(['value' => 65.0]);
        $this->assertEquals('warning', $kpi->fresh()->getStatus());

        // Test poor status
        KpiValue::where('kpi_definition_id', $kpi->id)->update(['value' => 75.0]);
        $this->assertEquals('poor', $kpi->fresh()->getStatus());
    }

    /** @test */
    public function prediction_model_can_determine_retraining_need()
    {
        $model = PredictionModel::create([
            'name' => 'Test Model',
            'type' => 'test',
            'description' => 'Test model',
            'features' => [],
            'model_config' => ['retraining_interval' => 30],
            'is_active' => true,
            'last_trained_at' => now()->subDays(35), // Older than interval
        ]);

        $this->assertTrue($model->needsRetraining());

        // Update to recent training
        $model->update(['last_trained_at' => now()->subDays(15)]);
        $this->assertFalse($model->needsRetraining());
    }

    /** @test */
    public function report_execution_tracks_status_correctly()
    {
        $report = CustomReport::create([
            'user_id' => $this->user->id,
            'name' => 'Test Report',
            'type' => 'employment',
            'filters' => [],
            'columns' => ['graduate_name'],
        ]);

        $execution = ReportExecution::create([
            'custom_report_id' => $report->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
            'parameters' => [],
        ]);

        $this->assertTrue($execution->isPending());
        $this->assertFalse($execution->isProcessing());
        $this->assertFalse($execution->isCompleted());

        $execution->markAsStarted();
        $this->assertTrue($execution->isProcessing());

        $execution->markAsCompleted(['test' => 'data']);
        $this->assertTrue($execution->isCompleted());
        $this->assertNotNull($execution->completed_at);
    }
}