<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsDashboardIntegrationService;
use App\Services\Analytics\AnalyticsMetricsCollectionService;
use App\Services\TenantContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AnalyticsDashboardIntegrationServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Constants Tests ====================

    public function test_widget_type_constants(): void
    {
        $this->assertEquals('metric_card', AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD);
        $this->assertEquals('chart', AnalyticsDashboardIntegrationService::WIDGET_TYPE_CHART);
        $this->assertEquals('table', AnalyticsDashboardIntegrationService::WIDGET_TYPE_TABLE);
        $this->assertEquals('gauge', AnalyticsDashboardIntegrationService::WIDGET_TYPE_GAUGE);
        $this->assertEquals('map', AnalyticsDashboardIntegrationService::WIDGET_TYPE_MAP);
        $this->assertEquals('list', AnalyticsDashboardIntegrationService::WIDGET_TYPE_LIST);
    }

    public function test_chart_type_constants(): void
    {
        $this->assertEquals('line', AnalyticsDashboardIntegrationService::CHART_TYPE_LINE);
        $this->assertEquals('bar', AnalyticsDashboardIntegrationService::CHART_TYPE_BAR);
        $this->assertEquals('pie', AnalyticsDashboardIntegrationService::CHART_TYPE_PIE);
        $this->assertEquals('area', AnalyticsDashboardIntegrationService::CHART_TYPE_AREA);
        $this->assertEquals('scatter', AnalyticsDashboardIntegrationService::CHART_TYPE_SCATTER);
    }

    public function test_date_range_preset_constants(): void
    {
        $this->assertEquals('today', AnalyticsDashboardIntegrationService::DATE_RANGE_TODAY);
        $this->assertEquals('yesterday', AnalyticsDashboardIntegrationService::DATE_RANGE_YESTERDAY);
        $this->assertEquals('last_7_days', AnalyticsDashboardIntegrationService::DATE_RANGE_LAST_7_DAYS);
        $this->assertEquals('last_30_days', AnalyticsDashboardIntegrationService::DATE_RANGE_LAST_30_DAYS);
        $this->assertEquals('last_90_days', AnalyticsDashboardIntegrationService::DATE_RANGE_LAST_90_DAYS);
        $this->assertEquals('this_month', AnalyticsDashboardIntegrationService::DATE_RANGE_THIS_MONTH);
        $this->assertEquals('last_month', AnalyticsDashboardIntegrationService::DATE_RANGE_LAST_MONTH);
        $this->assertEquals('this_year', AnalyticsDashboardIntegrationService::DATE_RANGE_THIS_YEAR);
        $this->assertEquals('custom', AnalyticsDashboardIntegrationService::DATE_RANGE_CUSTOM);
    }

    public function test_aggregation_constants(): void
    {
        $this->assertEquals('sum', AnalyticsDashboardIntegrationService::AGGREGATION_SUM);
        $this->assertEquals('avg', AnalyticsDashboardIntegrationService::AGGREGATION_AVG);
        $this->assertEquals('min', AnalyticsDashboardIntegrationService::AGGREGATION_MIN);
        $this->assertEquals('max', AnalyticsDashboardIntegrationService::AGGREGATION_MAX);
        $this->assertEquals('count', AnalyticsDashboardIntegrationService::AGGREGATION_COUNT);
    }

    // ==================== Service Construction Tests ====================

    public function test_service_can_be_instantiated(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->assertInstanceOf(AnalyticsDashboardIntegrationService::class, $service);
    }

    public function test_service_can_be_instantiated_with_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $config = [
            'max_dashboards' => 50,
            'max_widgets_per_dashboard' => 25,
            'cache_ttl' => 1800,
            'auto_refresh' => false,
        ];

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService, $config);

        $this->assertInstanceOf(AnalyticsDashboardIntegrationService::class, $service);
    }

    // ==================== Service Methods Existence Tests ====================

    public function test_getDashboardData_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'getDashboardData'));
    }

    public function test_getWidgetData_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'getWidgetData'));
    }

    public function test_aggregateDashboardData_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'aggregateDashboardData'));
    }

    public function test_refreshDashboard_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'refreshDashboard'));
    }

    public function test_createDashboard_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'createDashboard'));
    }

    public function test_updateDashboard_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'updateDashboard'));
    }

    public function test_deleteDashboard_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'deleteDashboard'));
    }

    public function test_addWidget_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'addWidget'));
    }

    public function test_removeWidget_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'removeWidget'));
    }

    public function test_getDashboardTemplates_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'getDashboardTemplates'));
    }

    public function test_getAllDashboards_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'getAllDashboards'));
    }

    public function test_getDashboardById_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsDashboardIntegrationService::class, 'getDashboardById'));
    }

    // ==================== Class Structure Tests ====================

    public function test_class_has_correct_constants(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $constants = $reflection->getConstants();

        // Verify widget type constants exist
        $this->assertArrayHasKey('WIDGET_TYPE_METRIC_CARD', $constants);
        $this->assertArrayHasKey('WIDGET_TYPE_CHART', $constants);
        $this->assertArrayHasKey('WIDGET_TYPE_TABLE', $constants);
        $this->assertArrayHasKey('WIDGET_TYPE_GAUGE', $constants);
        $this->assertArrayHasKey('WIDGET_TYPE_MAP', $constants);
        $this->assertArrayHasKey('WIDGET_TYPE_LIST', $constants);

        // Verify chart type constants exist
        $this->assertArrayHasKey('CHART_TYPE_LINE', $constants);
        $this->assertArrayHasKey('CHART_TYPE_BAR', $constants);
        $this->assertArrayHasKey('CHART_TYPE_PIE', $constants);
        $this->assertArrayHasKey('CHART_TYPE_AREA', $constants);
        $this->assertArrayHasKey('CHART_TYPE_SCATTER', $constants);

        // Verify date range preset constants exist
        $this->assertArrayHasKey('DATE_RANGE_TODAY', $constants);
        $this->assertArrayHasKey('DATE_RANGE_LAST_30_DAYS', $constants);
        $this->assertArrayHasKey('DATE_RANGE_CUSTOM', $constants);

        // Verify aggregation constants exist
        $this->assertArrayHasKey('AGGREGATION_SUM', $constants);
        $this->assertArrayHasKey('AGGREGATION_AVG', $constants);
        $this->assertArrayHasKey('AGGREGATION_MIN', $constants);
        $this->assertArrayHasKey('AGGREGATION_MAX', $constants);
        $this->assertArrayHasKey('AGGREGATION_COUNT', $constants);
    }

    public function test_class_has_required_dependencies(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('tenantContextService', $parameters[0]->getName());
        $this->assertEquals('metricsCollectionService', $parameters[1]->getName());
        $this->assertEquals('dashboardConfig', $parameters[2]->getName());
    }

    // ==================== Dashboard CRUD Tests ====================

    public function test_createDashboard_returns_dashboard_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = [
            'name' => 'My Dashboard',
            'description' => 'Test dashboard',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Total Users',
                    'metric' => 'total_users',
                ],
            ],
        ];

        $result = $service->createDashboard($dashboard);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('widgets', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertEquals('My Dashboard', $result['name']);
        $this->assertEquals('test-tenant-uuid', $result['tenant_id']);
        $this->assertCount(1, $result['widgets']);
    }

    public function test_createDashboard_generates_unique_ids(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard1 = ['name' => 'Dashboard 1'];
        $dashboard2 = ['name' => 'Dashboard 2'];

        $result1 = $service->createDashboard($dashboard1);
        $result2 = $service->createDashboard($dashboard2);

        $this->assertNotEquals($result1['id'], $result2['id']);
    }

    public function test_createDashboard_without_name_throws_exception(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard name is required');

        $service->createDashboard(['description' => 'No name dashboard']);
    }

    public function test_getDashboardById_returns_dashboard_when_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $created = $service->createDashboard(['name' => 'Test Dashboard']);
        $dashboardId = $created['id'];

        $result = $service->getDashboardById($dashboardId);

        $this->assertIsArray($result);
        $this->assertEquals($dashboardId, $result['id']);
        $this->assertEquals('Test Dashboard', $result['name']);
    }

    public function test_getDashboardById_returns_null_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $result = $service->getDashboardById('non_existent_id');

        $this->assertNull($result);
    }

    public function test_updateDashboard_updates_fields(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $created = $service->createDashboard(['name' => 'Original Name']);
        $dashboardId = $created['id'];

        $updated = $service->updateDashboard($dashboardId, [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);

        $this->assertEquals('Updated Name', $updated['name']);
        $this->assertEquals('Updated description', $updated['description']);
    }

    public function test_updateDashboard_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard not found');

        $service->updateDashboard('non_existent_id', ['name' => 'Updated']);
    }

    public function test_deleteDashboard_returns_true(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $created = $service->createDashboard(['name' => 'To Delete']);
        $dashboardId = $created['id'];

        $result = $service->deleteDashboard($dashboardId);

        $this->assertTrue($result);
        $this->assertNull($service->getDashboardById($dashboardId));
    }

    public function test_deleteDashboard_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard not found');

        $service->deleteDashboard('non_existent_id');
    }

    // ==================== Dashboard Listing Tests ====================

    public function test_getAllDashboards_returns_all_dashboards(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $service->createDashboard(['name' => 'Dashboard 1']);
        $service->createDashboard(['name' => 'Dashboard 2']);
        $service->createDashboard(['name' => 'Dashboard 3']);

        $dashboards = $service->getAllDashboards();

        $this->assertIsArray($dashboards);
        $this->assertCount(3, $dashboards);
    }

    public function test_getAllDashboards_with_active_only(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $service->createDashboard(['name' => 'Active Dashboard', 'is_active' => true]);
        $service->createDashboard(['name' => 'Inactive Dashboard', 'is_active' => false]);

        $activeDashboards = $service->getAllDashboards(true);

        $this->assertCount(1, $activeDashboards);
        $this->assertEquals('Active Dashboard', $activeDashboards[0]['name']);
    }

    // ==================== Widget Management Tests ====================

    public function test_addWidget_adds_widget_to_dashboard(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Test Dashboard']);

        $widget = [
            'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
            'title' => 'New Widget',
            'metric' => 'test_metric',
        ];

        $result = $service->addWidget($dashboard['id'], $widget);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertEquals(AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD, $result['type']);
        $this->assertEquals('New Widget', $result['title']);
    }

    public function test_addWidget_validates_widget_type(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Test Dashboard']);

        $widget = [
            'type' => 'invalid_widget_type',
            'title' => 'Invalid Widget',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid widget type');

        $service->addWidget($dashboard['id'], $widget);
    }

    public function test_removeWidget_removes_widget_from_dashboard(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Test Dashboard']);

        $widget = $service->addWidget($dashboard['id'], [
            'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
            'title' => 'Widget to Remove',
            'metric' => 'test_metric',
        ]);

        $result = $service->removeWidget($dashboard['id'], $widget['id']);

        $this->assertTrue($result);
    }

    public function test_removeWidget_throws_exception_when_widget_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Test Dashboard']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Widget not found');

        $service->removeWidget($dashboard['id'], 'non_existent_widget_id');
    }

    // ==================== Dashboard Data Tests ====================

    public function test_getDashboardData_returns_dashboard_with_widgets(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);
        $metricsService->shouldReceive('calculateMetric')
            ->andReturn(['result' => 100, 'statistics' => []]);
        $metricsService->shouldReceive('getMetricTrends')
            ->andReturn(['trends' => []]);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard([
            'name' => 'Test Dashboard',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Total Users',
                    'metric' => 'total_users',
                ],
            ],
        ]);

        $result = $service->getDashboardData($dashboard['id']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('widgets', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('date_range', $result);
    }

    public function test_getDashboardData_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard not found');

        $service->getDashboardData('non_existent_id');
    }

    // ==================== Widget Data Tests ====================

    public function test_getWidgetData_returns_widget_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);
        $metricsService->shouldReceive('calculateMetric')
            ->andReturn(['result' => 500, 'statistics' => ['count' => 10, 'sum' => 5000]]);
        $metricsService->shouldReceive('getMetricTrends')
            ->andReturn(['trends' => [['period' => '2024-01-01', 'value' => 100]]]);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard([
            'name' => 'Test Dashboard',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_CHART,
                    'title' => 'User Growth',
                    'metric' => 'user_growth',
                    'chart_type' => AnalyticsDashboardIntegrationService::CHART_TYPE_LINE,
                ],
            ],
        ]);

        $widgetId = $dashboard['widgets'][0]['id'];
        $result = $service->getWidgetData($widgetId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('date_range', $result);
        $this->assertEquals(AnalyticsDashboardIntegrationService::WIDGET_TYPE_CHART, $result['type']);
    }

    public function test_getWidgetData_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Widget not found');

        $service->getWidgetData('non_existent_widget_id');
    }

    // ==================== Dashboard Aggregation Tests ====================

    public function test_aggregateDashboardData_returns_aggregated_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);
        $metricsService->shouldReceive('calculateMetric')
            ->andReturn(['result' => 100, 'statistics' => []]);
        $metricsService->shouldReceive('getMetricTrends')
            ->andReturn(['trends' => []]);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard([
            'name' => 'Test Dashboard',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Metric 1',
                    'metric' => 'metric_1',
                ],
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Metric 2',
                    'metric' => 'metric_2',
                ],
            ],
        ]);

        $result = $service->aggregateDashboardData(
            $dashboard['id'],
            [],
            AnalyticsDashboardIntegrationService::AGGREGATION_SUM
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dashboard_id', $result);
        $this->assertArrayHasKey('dashboard_name', $result);
        $this->assertArrayHasKey('aggregation', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('aggregated_value', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertEquals('sum', $result['aggregation']);
        $this->assertCount(2, $result['metrics']);
    }

    public function test_aggregateDashboardData_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard not found');

        $service->aggregateDashboardData('non_existent_id', [], 'sum');
    }

    // ==================== Dashboard Refresh Tests ====================

    public function test_refreshDashboard_returns_refreshed_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);
        $metricsService->shouldReceive('calculateMetric')
            ->andReturn(['result' => 200, 'statistics' => []]);
        $metricsService->shouldReceive('getMetricTrends')
            ->andReturn(['trends' => []]);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard([
            'name' => 'Test Dashboard',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Test Metric',
                    'metric' => 'test_metric',
                ],
            ],
        ]);

        $result = $service->refreshDashboard($dashboard['id']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('refreshed_at', $result);
    }

    public function test_refreshDashboard_throws_exception_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Dashboard not found');

        $service->refreshDashboard('non_existent_id');
    }

    // ==================== Dashboard Templates Tests ====================

    public function test_getDashboardTemplates_returns_templates(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $templates = $service->getDashboardTemplates();

        $this->assertIsArray($templates);
        $this->assertNotEmpty($templates);

        // Check template structure
        foreach ($templates as $template) {
            $this->assertArrayHasKey('id', $template);
            $this->assertArrayHasKey('name', $template);
            $this->assertArrayHasKey('description', $template);
            $this->assertArrayHasKey('widgets', $template);
            $this->assertIsArray($template['widgets']);
        }
    }

    public function test_getDashboardTemplates_contains_expected_templates(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $templates = $service->getDashboardTemplates();
        $templateIds = array_column($templates, 'id');

        $this->assertContains('template_overview', $templateIds);
        $this->assertContains('template_engagement', $templateIds);
        $this->assertContains('template_performance', $templateIds);
        $this->assertContains('template_sales', $templateIds);
    }

    // ==================== Tenant Isolation Tests ====================

    public function test_different_tenants_get_different_dashboards(): void
    {
        $tenant1Service = Mockery::mock(TenantContextService::class);
        $tenant1Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-1');

        $tenant2Service = Mockery::mock(TenantContextService::class);
        $tenant2Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-2');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service1 = new AnalyticsDashboardIntegrationService($tenant1Service, $metricsService);
        $service2 = new AnalyticsDashboardIntegrationService($tenant2Service, $metricsService);

        // Create dashboard for tenant 1
        $dashboard1 = $service1->createDashboard(['name' => 'Tenant 1 Dashboard']);

        // Create dashboard for tenant 2
        $dashboard2 = $service2->createDashboard(['name' => 'Tenant 2 Dashboard']);

        // Verify tenant 1 only sees their dashboards
        $dashboards1 = $service1->getAllDashboards();
        $this->assertCount(1, $dashboards1);
        $this->assertEquals('Tenant 1 Dashboard', $dashboards1[0]['name']);

        // Verify tenant 2 only sees their dashboards
        $dashboards2 = $service2->getAllDashboards();
        $this->assertCount(1, $dashboards2);
        $this->assertEquals('Tenant 2 Dashboard', $dashboards2[0]['name']);

        // Verify they can't access each other's dashboards
        $this->assertNull($service1->getDashboardById($dashboard2['id']));
        $this->assertNull($service2->getDashboardById($dashboard1['id']));
    }

    public function test_createDashboard_sets_tenant_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('unique-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Tenant Dashboard']);

        $this->assertEquals('unique-tenant-uuid', $dashboard['tenant_id']);
    }

    // ==================== Class Properties Tests ====================

    public function test_class_has_tenant_context_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('tenantContextService', $propertyNames);
    }

    public function test_class_has_metrics_service_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('metricsCollectionService', $propertyNames);
    }

    public function test_class_has_dashboard_config_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('dashboardConfig', $propertyNames);
    }

    public function test_class_has_dashboards_storage_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsDashboardIntegrationService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('dashboardsStorage', $propertyNames);
    }

    // ==================== Default Configuration Tests ====================

    public function test_default_dashboard_has_valid_configuration(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard(['name' => 'Test']);

        $this->assertArrayHasKey('configuration', $dashboard);
        $this->assertArrayHasKey('refresh_interval', $dashboard['configuration']);
        $this->assertArrayHasKey('date_range', $dashboard['configuration']);
        $this->assertArrayHasKey('layout', $dashboard['configuration']);
    }

    public function test_default_dashboard_widget_has_position(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $metricsService = Mockery::mock(AnalyticsMetricsCollectionService::class);

        $service = new AnalyticsDashboardIntegrationService($tenantService, $metricsService);

        $dashboard = $service->createDashboard([
            'name' => 'Test',
            'widgets' => [
                [
                    'type' => AnalyticsDashboardIntegrationService::WIDGET_TYPE_METRIC_CARD,
                    'title' => 'Test Widget',
                ],
            ],
        ]);

        $widget = $dashboard['widgets'][0];
        $this->assertArrayHasKey('position', $widget);
        $this->assertArrayHasKey('x', $widget['position']);
        $this->assertArrayHasKey('y', $widget['position']);
        $this->assertArrayHasKey('w', $widget['position']);
        $this->assertArrayHasKey('h', $widget['position']);
    }
}
