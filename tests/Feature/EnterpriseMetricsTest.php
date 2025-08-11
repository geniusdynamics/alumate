<?php

namespace Tests\Feature;

use App\Services\HomepageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected HomepageService $homepageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->homepageService = app(HomepageService::class);
    }

    /** @test */
    public function it_can_get_enterprise_metrics_data()
    {
        $params = [
            'timeframe' => '12_months',
            'metrics' => ['engagement', 'financial'],
        ];

        $result = $this->homepageService->getEnterpriseMetrics($params);

        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('roi_data', $result);
        $this->assertArrayHasKey('summary', $result);

        // Check that metrics are filtered correctly
        $engagementMetrics = array_filter($result['metrics'], fn ($m) => $m['category'] === 'engagement');
        $financialMetrics = array_filter($result['metrics'], fn ($m) => $m['category'] === 'financial');

        $this->assertNotEmpty($engagementMetrics);
        $this->assertNotEmpty($financialMetrics);

        // Check ROI data structure
        $this->assertArrayHasKey('percentage', $result['roi_data']);
        $this->assertArrayHasKey('investment', $result['roi_data']);
        $this->assertArrayHasKey('return', $result['roi_data']);
        $this->assertArrayHasKey('timeframe', $result['roi_data']);
    }

    /** @test */
    public function it_can_get_institutional_comparison_data()
    {
        $params = [
            'institution_id' => 'stanford_university',
        ];

        $result = $this->homepageService->getInstitutionalComparison($params);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('institution_name', $result);
        $this->assertArrayHasKey('before_metrics', $result);
        $this->assertArrayHasKey('after_metrics', $result);
        $this->assertArrayHasKey('before_challenges', $result);
        $this->assertArrayHasKey('after_benefits', $result);
        $this->assertArrayHasKey('timeframe', $result);
        $this->assertArrayHasKey('impact_summary', $result);

        // Verify metrics structure
        foreach ($result['before_metrics'] as $metric) {
            $this->assertArrayHasKey('key', $metric);
            $this->assertArrayHasKey('label', $metric);
            $this->assertArrayHasKey('value', $metric);
            $this->assertArrayHasKey('unit', $metric);
        }

        foreach ($result['after_metrics'] as $metric) {
            $this->assertArrayHasKey('key', $metric);
            $this->assertArrayHasKey('label', $metric);
            $this->assertArrayHasKey('value', $metric);
            $this->assertArrayHasKey('unit', $metric);
        }
    }

    /** @test */
    public function it_can_get_implementation_timeline_data()
    {
        $params = [
            'institution_type' => 'university',
            'alumni_count' => 50000,
            'complexity' => 'standard',
        ];

        $result = $this->homepageService->getImplementationTimeline($params);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('total_duration', $result);
        $this->assertArrayHasKey('phases', $result);

        // Verify phases structure
        foreach ($result['phases'] as $phase) {
            $this->assertArrayHasKey('id', $phase);
            $this->assertArrayHasKey('name', $phase);
            $this->assertArrayHasKey('description', $phase);
            $this->assertArrayHasKey('duration', $phase);
            $this->assertArrayHasKey('deliverables', $phase);
            $this->assertArrayHasKey('dependencies', $phase);
            $this->assertArrayHasKey('milestones', $phase);
            $this->assertArrayHasKey('status', $phase);

            // Verify milestones structure
            foreach ($phase['milestones'] as $milestone) {
                $this->assertArrayHasKey('id', $milestone);
                $this->assertArrayHasKey('name', $milestone);
                $this->assertArrayHasKey('description', $milestone);
                $this->assertArrayHasKey('dueDate', $milestone);
                $this->assertArrayHasKey('status', $milestone);
            }
        }
    }

    /** @test */
    public function it_can_get_success_metrics_tracking_data()
    {
        $params = [
            'institution_id' => 'test_institution',
            'metrics' => ['alumni_engagement', 'event_attendance'],
        ];

        $result = $this->homepageService->getSuccessMetricsTracking($params);

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertArrayHasKey('last_updated', $result);
        $this->assertArrayHasKey('summary', $result);

        // Verify metrics structure
        foreach ($result['metrics'] as $metric) {
            $this->assertArrayHasKey('id', $metric);
            $this->assertArrayHasKey('name', $metric);
            $this->assertArrayHasKey('category', $metric);
            $this->assertArrayHasKey('current_value', $metric);
            $this->assertArrayHasKey('target_value', $metric);
            $this->assertArrayHasKey('unit', $metric);
            $this->assertArrayHasKey('trend', $metric);
            $this->assertArrayHasKey('verified', $metric);
        }

        // Verify insights structure
        foreach ($result['insights'] as $insight) {
            $this->assertArrayHasKey('id', $insight);
            $this->assertArrayHasKey('title', $insight);
            $this->assertArrayHasKey('description', $insight);
            $this->assertArrayHasKey('type', $insight);
        }

        // Verify summary structure
        $this->assertArrayHasKey('metrics_on_track', $result['summary']);
        $this->assertArrayHasKey('metrics_exceeding', $result['summary']);
        $this->assertArrayHasKey('metrics_behind', $result['summary']);
        $this->assertArrayHasKey('average_progress', $result['summary']);
    }

    /** @test */
    public function it_can_get_branded_apps_data()
    {
        $result = $this->homepageService->getBrandedAppsData();

        $this->assertArrayHasKey('featured_apps', $result);
        $this->assertArrayHasKey('customization_options', $result);
        $this->assertArrayHasKey('app_store_integration', $result);
        $this->assertArrayHasKey('development_timeline', $result);

        // Verify featured apps structure
        foreach ($result['featured_apps'] as $app) {
            $this->assertArrayHasKey('id', $app);
            $this->assertArrayHasKey('institution_name', $app);
            $this->assertArrayHasKey('institution_type', $app);
            $this->assertArrayHasKey('screenshots', $app);
            $this->assertArrayHasKey('customizations', $app);
            $this->assertArrayHasKey('engagement_stats', $app);
        }

        // Verify development timeline structure
        $this->assertArrayHasKey('phases', $result['development_timeline']);
        $this->assertArrayHasKey('total_duration', $result['development_timeline']);
        $this->assertArrayHasKey('estimated_cost', $result['development_timeline']);
    }

    /** @test */
    public function enterprise_metrics_api_endpoint_returns_valid_response()
    {
        $response = $this->getJson('/api/homepage/enterprise-metrics?timeframe=12_months&metrics[]=engagement&metrics[]=financial');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'metrics' => [
                        '*' => [
                            'id',
                            'name',
                            'category',
                            'beforeValue',
                            'afterValue',
                            'improvementPercentage',
                            'timeframe',
                            'verified',
                            'unit',
                        ],
                    ],
                    'roi_data' => [
                        'percentage',
                        'investment',
                        'return',
                        'timeframe',
                    ],
                    'summary' => [
                        'total_metrics',
                        'verified_metrics',
                        'average_improvement',
                    ],
                ],
            ]);
    }

    /** @test */
    public function institutional_comparison_api_endpoint_returns_valid_response()
    {
        $response = $this->getJson('/api/homepage/institutional-comparison?institution_id=stanford_university');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'institution_name',
                    'before_metrics',
                    'after_metrics',
                    'before_challenges',
                    'after_benefits',
                    'timeframe',
                    'impact_summary',
                ],
            ]);
    }

    /** @test */
    public function implementation_timeline_api_endpoint_returns_valid_response()
    {
        $response = $this->getJson('/api/homepage/implementation-timeline?institution_type=university&complexity=standard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'total_duration',
                    'phases' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'duration',
                            'deliverables',
                            'dependencies',
                            'milestones',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function success_metrics_tracking_api_endpoint_returns_valid_response()
    {
        $response = $this->getJson('/api/homepage/success-metrics-tracking?institution_id=test_institution');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'title',
                    'metrics' => [
                        '*' => [
                            'id',
                            'name',
                            'category',
                            'current_value',
                            'target_value',
                            'unit',
                            'trend',
                            'verified',
                        ],
                    ],
                    'insights',
                    'last_updated',
                    'summary',
                ],
            ]);
    }

    /** @test */
    public function enterprise_metrics_calculates_improvement_percentages_correctly()
    {
        $result = $this->homepageService->getEnterpriseMetrics();

        foreach ($result['metrics'] as $metric) {
            $expectedImprovement = round((($metric['afterValue'] - $metric['beforeValue']) / $metric['beforeValue']) * 100);
            $this->assertEquals($expectedImprovement, $metric['improvementPercentage'],
                "Improvement percentage calculation is incorrect for metric: {$metric['name']}");
        }
    }

    /** @test */
    public function success_metrics_tracking_calculates_progress_correctly()
    {
        $result = $this->homepageService->getSuccessMetricsTracking();

        foreach ($result['metrics'] as $metric) {
            $expectedProgress = min(100, ($metric['current_value'] / $metric['target_value']) * 100);
            $this->assertGreaterThanOrEqual(0, $expectedProgress);
            $this->assertLessThanOrEqual(100, $expectedProgress);
        }

        // Verify summary calculations
        $summary = $result['summary'];
        $totalMetrics = count($result['metrics']);

        $this->assertEquals($totalMetrics,
            $summary['metrics_on_track'] + $summary['metrics_exceeding'] + $summary['metrics_behind'],
            "Summary metrics count doesn't match total metrics");
    }
}
