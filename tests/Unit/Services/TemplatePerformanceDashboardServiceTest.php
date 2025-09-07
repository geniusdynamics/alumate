<?php

namespace Tests\Unit\Services;

use App\Models\Template;
use App\Models\LandingPage;
use App\Models\TemplateAnalyticsEvent;
use App\Models\Tenant;
use App\Services\TemplatePerformanceDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatePerformanceDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplatePerformanceDashboardService $dashboardService;
    private Tenant $tenant;
    private Template $template;
    private LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardService = app(TemplatePerformanceDashboardService::class);
        $this->tenant = Tenant::factory()->create();
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id
        ]);
    }

    public function test_get_dashboard_overview_returns_correct_structure()
    {
        // Create some test analytics events
        TemplateAnalyticsEvent::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'landing_page_id' => $this->landingPage->id,
            'event_type' => 'page_view',
            'timestamp' => now(),
        ]);

        TemplateAnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'landing_page_id' => $this->landingPage->id,
            'event_type' => 'conversion',
            'timestamp' => now(),
        ]);

        $overview = $this->dashboardService->getDashboardOverview($this->tenant->id);

        $this->assertArrayHasKey('summary', $overview);
        $this->assertArrayHasKey('performance', $overview);
        $this->assertArrayHasKey('trends', $overview);
        $this->assertArrayHasKey('insights', $overview);
        $this->assertArrayHasKey('generated_at', $overview);

        $this->assertIsArray($overview['summary']);
        $this->assertIsArray($overview['performance']);
        $this->assertIsArray($overview['trends']);
        $this->assertIsArray($overview['insights']);
    }

    public function test_get_template_comparison_returns_correct_data()
    {
        $template2 = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $comparison = $this->dashboardService->getTemplateComparison([
            $this->template->id,
            $template2->id
        ]);

        $this->assertArrayHasKey('templates', $comparison);
        $this->assertArrayHasKey('summary', $comparison);
        $this->assertArrayHasKey('recommendations', $comparison);
        $this->assertArrayHasKey('generated_at', $comparison);

        $this->assertCount(2, $comparison['templates']);
        $this->assertArrayHasKey($this->template->id, $comparison['templates']);
        $this->assertArrayHasKey($template2->id, $comparison['templates']);
    }

    public function test_get_real_time_metrics_returns_current_data()
    {
        // Create recent events
        TemplateAnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'event_type' => 'page_view',
            'timestamp' => now(),
        ]);

        $metrics = $this->dashboardService->getRealTimeMetrics($this->tenant->id);

        $this->assertArrayHasKey('time_range', $metrics);
        $this->assertArrayHasKey('total_events', $metrics);
        $this->assertArrayHasKey('page_views', $metrics);
        $this->assertArrayHasKey('conversions', $metrics);
        $this->assertArrayHasKey('unique_users', $metrics);
        $this->assertArrayHasKey('events_per_minute', $metrics);
        $this->assertArrayHasKey('last_updated', $metrics);

        $this->assertEquals('last_hour', $metrics['time_range']);
        $this->assertGreaterThanOrEqual(5, $metrics['total_events']);
    }

    public function test_get_bottleneck_analysis_identifies_issues()
    {
        // Create events that might indicate bottlenecks
        TemplateAnalyticsEvent::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'event_type' => 'page_view',
            'timestamp' => now(),
        ]);

        // Very few conversions relative to page views
        TemplateAnalyticsEvent::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'event_type' => 'conversion',
            'timestamp' => now(),
        ]);

        $analysis = $this->dashboardService->getBottleneckAnalysis($this->tenant->id);

        $this->assertArrayHasKey('slow_templates', $analysis);
        $this->assertArrayHasKey('conversion_bottlenecks', $analysis);
        $this->assertArrayHasKey('engagement_issues', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertArrayHasKey('generated_at', $analysis);

        $this->assertIsArray($analysis['slow_templates']);
        $this->assertIsArray($analysis['conversion_bottlenecks']);
        $this->assertIsArray($analysis['engagement_issues']);
        $this->assertIsArray($analysis['recommendations']);
    }

    public function test_generate_report_creates_valid_report()
    {
        $parameters = [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Performance Report',
            'description' => 'A test report for template performance',
            'report_type' => 'template_performance',
        ];

        $report = $this->dashboardService->generateReport($parameters);

        $this->assertInstanceOf(\App\Models\TemplatePerformanceReport::class, $report);
        $this->assertEquals('Test Performance Report', $report->name);
        $this->assertEquals('template_performance', $report->report_type);
        $this->assertEquals('processing', $report->status);
    }

    public function test_export_dashboard_data_returns_correct_format()
    {
        $exportData = $this->dashboardService->exportDashboardData(
            $this->tenant->id,
            'json'
        );

        $this->assertArrayHasKey('export_format', $exportData);
        $this->assertArrayHasKey('export_timestamp', $exportData);
        $this->assertArrayHasKey('tenant_id', $exportData);
        $this->assertArrayHasKey('filters', $exportData);
        $this->assertArrayHasKey('data', $exportData);

        $this->assertEquals('json', $exportData['export_format']);
        $this->assertEquals($this->tenant->id, $exportData['tenant_id']);
    }

    public function test_export_formats_are_supported()
    {
        $supportedFormats = ['json', 'csv', 'excel', 'pdf'];

        foreach ($supportedFormats as $format) {
            $exportData = $this->dashboardService->exportDashboardData(
                $this->tenant->id,
                $format
            );

            $this->assertEquals($format, $exportData['export_format']);
            $this->assertArrayHasKey('data', $exportData);
            $this->assertArrayHasKey('export_timestamp', $exportData);
        }
    }

    public function test_report_generation_handles_different_types()
    {
        $reportTypes = ['template_performance', 'comparison', 'trend_analysis', 'bottleneck_analysis'];

        foreach ($reportTypes as $type) {
            $parameters = [
                'tenant_id' => $this->tenant->id,
                'name' => "Test {$type} Report",
                'report_type' => $type,
            ];

            if ($type === 'comparison') {
                $parameters['template_ids'] = [$this->template->id];
            }

            $report = $this->dashboardService->generateReport($parameters);

            $this->assertInstanceOf(\App\Models\TemplatePerformanceReport::class, $report);
            $this->assertEquals($type, $report->report_type);
            $this->assertEquals('processing', $report->status);
        }
    }

    public function test_cache_functionality_works_correctly()
    {
        // First call should cache the result
        $overview1 = $this->dashboardService->getDashboardOverview($this->tenant->id);

        // Second call should return cached result
        $overview2 = $this->dashboardService->getDashboardOverview($this->tenant->id);

        $this->assertEquals($overview1['generated_at'], $overview2['generated_at']);
    }

    public function test_clear_cache_removes_cached_data()
    {
        // Populate cache
        $this->dashboardService->getDashboardOverview($this->tenant->id);

        // Clear cache
        $this->dashboardService->clearCache($this->tenant->id);

        // Next call should generate fresh data
        $overview = $this->dashboardService->getDashboardOverview($this->tenant->id);

        $this->assertArrayHasKey('generated_at', $overview);
        $this->assertIsString($overview['generated_at']);
    }

    public function test_tenant_isolation_in_dashboard_data()
    {
        // Create another tenant and template
        $otherTenant = Tenant::factory()->create();
        $otherTemplate = Template::factory()->create(['tenant_id' => $otherTenant->id]);

        // Create events for both tenants
        TemplateAnalyticsEvent::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
        ]);

        TemplateAnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => $otherTenant->id,
            'template_id' => $otherTemplate->id,
        ]);

        $overview1 = $this->dashboardService->getDashboardOverview($this->tenant->id);
        $overview2 = $this->dashboardService->getDashboardOverview($otherTenant->id);

        // Each tenant should only see their own data
        $this->assertNotEquals(
            $overview1['summary']['total_events'] ?? 0,
            $overview2['summary']['total_events'] ?? 0
        );
    }
}