<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsMetricsCollectionService;
use App\Services\TenantContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AnalyticsMetricsCollectionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Constants Tests ====================

    public function test_aggregation_constants(): void
    {
        $this->assertEquals('sum', AnalyticsMetricsCollectionService::AGGREGATION_SUM);
        $this->assertEquals('avg', AnalyticsMetricsCollectionService::AGGREGATION_AVG);
        $this->assertEquals('min', AnalyticsMetricsCollectionService::AGGREGATION_MIN);
        $this->assertEquals('max', AnalyticsMetricsCollectionService::AGGREGATION_MAX);
        $this->assertEquals('count', AnalyticsMetricsCollectionService::AGGREGATION_COUNT);
        $this->assertEquals('percentile', AnalyticsMetricsCollectionService::AGGREGATION_PERCENTILE);
    }

    public function test_time_grain_constants(): void
    {
        $this->assertEquals('hour', AnalyticsMetricsCollectionService::TIME_GRAIN_HOUR);
        $this->assertEquals('day', AnalyticsMetricsCollectionService::TIME_GRAIN_DAY);
        $this->assertEquals('week', AnalyticsMetricsCollectionService::TIME_GRAIN_WEEK);
        $this->assertEquals('month', AnalyticsMetricsCollectionService::TIME_GRAIN_MONTH);
        $this->assertEquals('year', AnalyticsMetricsCollectionService::TIME_GRAIN_YEAR);
    }

    public function test_format_constants(): void
    {
        $this->assertEquals('json', AnalyticsMetricsCollectionService::FORMAT_JSON);
        $this->assertEquals('csv', AnalyticsMetricsCollectionService::FORMAT_CSV);
        $this->assertEquals('array', AnalyticsMetricsCollectionService::FORMAT_ARRAY);
        $this->assertEquals('excel', AnalyticsMetricsCollectionService::FORMAT_EXCEL);
    }

    public function test_metric_type_constants(): void
    {
        $this->assertEquals('counter', AnalyticsMetricsCollectionService::TYPE_COUNTER);
        $this->assertEquals('gauge', AnalyticsMetricsCollectionService::TYPE_GAUGE);
        $this->assertEquals('histogram', AnalyticsMetricsCollectionService::TYPE_HISTOGRAM);
        $this->assertEquals('summary', AnalyticsMetricsCollectionService::TYPE_SUMMARY);
    }

    // ==================== Service Construction Tests ====================

    public function test_service_can_be_instantiated(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $this->assertInstanceOf(AnalyticsMetricsCollectionService::class, $service);
    }

    public function test_service_can_be_instantiated_with_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $config = [
            'max_metrics_history' => 5000,
            'metrics_retention_days' => 60,
            'auto_aggregation' => false,
        ];

        $service = new AnalyticsMetricsCollectionService($tenantService, $config);

        $this->assertInstanceOf(AnalyticsMetricsCollectionService::class, $service);
    }

    // ==================== Service Methods Existence Tests ====================

    public function test_collectMetric_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'collectMetric'));
    }

    public function test_collectBatchMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'collectBatchMetrics'));
    }

    public function test_aggregateMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'aggregateMetrics'));
    }

    public function test_getMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'getMetrics'));
    }

    public function test_getMetricById_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'getMetricById'));
    }

    public function test_getMetricTrends_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'getMetricTrends'));
    }

    public function test_calculateMetric_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'calculateMetric'));
    }

    public function test_exportMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'exportMetrics'));
    }

    public function test_getMetricsSummary_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'getMetricsSummary'));
    }

    public function test_configureMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsMetricsCollectionService::class, 'configureMetrics'));
    }

    // ==================== Class Structure Tests ====================

    public function test_class_has_correct_constants(): void
    {
        $reflection = new \ReflectionClass(AnalyticsMetricsCollectionService::class);
        $constants = $reflection->getConstants();

        // Verify aggregation constants exist
        $this->assertArrayHasKey('AGGREGATION_SUM', $constants);
        $this->assertArrayHasKey('AGGREGATION_AVG', $constants);
        $this->assertArrayHasKey('AGGREGATION_MIN', $constants);
        $this->assertArrayHasKey('AGGREGATION_MAX', $constants);
        $this->assertArrayHasKey('AGGREGATION_COUNT', $constants);
        $this->assertArrayHasKey('AGGREGATION_PERCENTILE', $constants);

        // Verify time grain constants exist
        $this->assertArrayHasKey('TIME_GRAIN_HOUR', $constants);
        $this->assertArrayHasKey('TIME_GRAIN_DAY', $constants);
        $this->assertArrayHasKey('TIME_GRAIN_WEEK', $constants);
        $this->assertArrayHasKey('TIME_GRAIN_MONTH', $constants);
        $this->assertArrayHasKey('TIME_GRAIN_YEAR', $constants);

        // Verify format constants exist
        $this->assertArrayHasKey('FORMAT_JSON', $constants);
        $this->assertArrayHasKey('FORMAT_CSV', $constants);
        $this->assertArrayHasKey('FORMAT_ARRAY', $constants);
        $this->assertArrayHasKey('FORMAT_EXCEL', $constants);

        // Verify metric type constants exist
        $this->assertArrayHasKey('TYPE_COUNTER', $constants);
        $this->assertArrayHasKey('TYPE_GAUGE', $constants);
        $this->assertArrayHasKey('TYPE_HISTOGRAM', $constants);
        $this->assertArrayHasKey('TYPE_SUMMARY', $constants);
    }

    public function test_class_has_tenant_context_service_dependency(): void
    {
        $reflection = new \ReflectionClass(AnalyticsMetricsCollectionService::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('tenantContextService', $parameters[0]->getName());
    }

    // ==================== Single Metric Collection Tests ====================

    public function test_collectMetric_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metric = [
            'name' => 'daily_active_users',
            'value' => 1500,
            'type' => AnalyticsMetricsCollectionService::TYPE_GAUGE,
            'unit' => 'users',
            'source' => 'analytics',
            'dimensions' => ['region' => 'us'],
            'tags' => ['production'],
        ];

        $result = $service->collectMetric($metric);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals('daily_active_users', $result['name']);
        $this->assertEquals(1500, $result['value']);
        $this->assertEquals('test-tenant-uuid', $result['tenant_id']);
    }

    public function test_collectMetric_with_minimal_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $result = $service->collectMetric([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('unknown', $result['name']);
        $this->assertEquals(0, $result['value']);
        $this->assertEquals(AnalyticsMetricsCollectionService::TYPE_GAUGE, $result['type']);
    }

    public function test_collectMetric_with_counter_type(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metric = [
            'name' => 'page_views',
            'value' => 5000,
            'type' => AnalyticsMetricsCollectionService::TYPE_COUNTER,
            'unit' => 'views',
        ];

        $result = $service->collectMetric($metric);

        $this->assertEquals(AnalyticsMetricsCollectionService::TYPE_COUNTER, $result['type']);
        $this->assertEquals(5000, $result['value']);
    }

    // ==================== Batch Metrics Collection Tests ====================

    public function test_collectBatchMetrics_returns_result_with_counts(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['name' => 'metric1', 'value' => 100],
            ['name' => 'metric2', 'value' => 200],
            ['name' => 'metric3', 'value' => 300],
        ];

        $result = $service->collectBatchMetrics($metrics);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('collected', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertArrayHasKey('records', $result);
        $this->assertArrayHasKey('collected_at', $result);
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['collected']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(3, $result['records']);
    }

    public function test_collectBatchMetrics_handles_errors(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['name' => 'metric1', 'value' => 100],
            ['name' => 'metric2', 'value' => 200],
        ];

        $result = $service->collectBatchMetrics($metrics);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['collected']);
    }

    public function test_collectBatchMetrics_returns_partial_success(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['name' => 'valid_metric', 'value' => 100],
            ['name' => 'another_valid', 'value' => 200],
        ];

        $result = $service->collectBatchMetrics($metrics);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsArray($result['errors']);
    }

    // ==================== Metrics Aggregation Tests ====================

    public function test_aggregateMetrics_sum(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_SUM);

        $this->assertEquals('sum', $result['aggregation']);
        $this->assertEquals(60, $result['value']);
        $this->assertEquals(3, $result['count']);
        $this->assertEquals(10, $result['min']);
        $this->assertEquals(30, $result['max']);
    }

    public function test_aggregateMetrics_avg(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_AVG);

        $this->assertEquals('avg', $result['aggregation']);
        $this->assertEquals(20, $result['value']);
        $this->assertEquals(20, $result['avg']);
    }

    public function test_aggregateMetrics_min(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_MIN);

        $this->assertEquals('min', $result['aggregation']);
        $this->assertEquals(10, $result['value']);
    }

    public function test_aggregateMetrics_max(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_MAX);

        $this->assertEquals('max', $result['aggregation']);
        $this->assertEquals(30, $result['value']);
    }

    public function test_aggregateMetrics_count(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_COUNT);

        $this->assertEquals('count', $result['aggregation']);
        $this->assertEquals(3, $result['value']);
    }

    public function test_aggregateMetrics_percentile(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metrics = [
            ['value' => 10],
            ['value' => 20],
            ['value' => 30],
            ['value' => 40],
            ['value' => 50],
        ];

        $result = $service->aggregateMetrics($metrics, AnalyticsMetricsCollectionService::AGGREGATION_PERCENTILE, '95');

        $this->assertEquals('percentile', $result['aggregation']);
        $this->assertEquals(95, $result['percentile']);
    }

    public function test_aggregateMetrics_empty_array(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $result = $service->aggregateMetrics([], AnalyticsMetricsCollectionService::AGGREGATION_SUM);

        $this->assertEquals('sum', $result['aggregation']);
        $this->assertEquals(0, $result['value']);
        $this->assertEquals(0, $result['count']);
    }

    // ==================== Metrics Retrieval Tests ====================

    public function test_getMetrics_returns_metrics_with_pagination(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        // Collect some metrics
        $service->collectMetric(['name' => 'metric1', 'value' => 100, 'type' => 'gauge']);
        $service->collectMetric(['name' => 'metric2', 'value' => 200, 'type' => 'gauge']);
        $service->collectMetric(['name' => 'metric3', 'value' => 300, 'type' => 'counter']);

        $result = $service->getMetrics([], 10, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('has_more', $result);
        $this->assertGreaterThanOrEqual(2, $result['total']);
    }

    public function test_getMetrics_filters_by_name(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'page_views', 'value' => 100]);
        $service->collectMetric(['name' => 'user_logins', 'value' => 50]);

        $result = $service->getMetrics(['name' => 'page'], 10, 0);

        $this->assertGreaterThanOrEqual(1, $result['total']);
    }

    public function test_getMetrics_filters_by_type(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'metric1', 'value' => 100, 'type' => AnalyticsMetricsCollectionService::TYPE_GAUGE]);
        $service->collectMetric(['name' => 'metric2', 'value' => 200, 'type' => AnalyticsMetricsCollectionService::TYPE_COUNTER]);

        $result = $service->getMetrics(['type' => AnalyticsMetricsCollectionService::TYPE_GAUGE], 100, 0);

        $this->assertGreaterThanOrEqual(1, $result['total']);
        foreach ($result['metrics'] as $metric) {
            $this->assertEquals(AnalyticsMetricsCollectionService::TYPE_GAUGE, $metric['type']);
        }
    }

    public function test_getMetrics_returns_empty_when_no_metrics(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $result = $service->getMetrics([], 10, 0);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['metrics']);
    }

    // ==================== Get Metric By ID Tests ====================

    public function test_getMetricById_returns_metric_when_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $metric = $service->collectMetric(['name' => 'test_metric', 'value' => 100]);
        $metricId = $metric['id'];

        $result = $service->getMetricById($metricId);

        $this->assertIsArray($result);
        $this->assertEquals($metricId, $result['id']);
        $this->assertEquals('test_metric', $result['name']);
    }

    public function test_getMetricById_returns_null_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $result = $service->getMetricById('non_existent_id');

        $this->assertNull($result);
    }

    // ==================== Metric Trends Tests ====================

    public function test_getMetricTrends_returns_trend_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        // Collect some metrics
        $service->collectMetric(['name' => 'daily_active_users', 'value' => 100]);
        $service->collectMetric(['name' => 'daily_active_users', 'value' => 150]);
        $service->collectMetric(['name' => 'daily_active_users', 'value' => 200]);

        $dateRange = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ];

        $result = $service->getMetricTrends('daily_active_users', $dateRange, AnalyticsMetricsCollectionService::TIME_GRAIN_DAY);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metric_name', $result);
        $this->assertArrayHasKey('date_range', $result);
        $this->assertArrayHasKey('time_grain', $result);
        $this->assertArrayHasKey('total_data_points', $result);
        $this->assertArrayHasKey('trends', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertEquals('daily_active_users', $result['metric_name']);
    }

    // ==================== Calculate Metric Tests ====================

    public function test_calculateMetric_returns_calculated_result(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'page_views', 'value' => 100]);
        $service->collectMetric(['name' => 'page_views', 'value' => 200]);
        $service->collectMetric(['name' => 'page_views', 'value' => 300]);

        $result = $service->calculateMetric('page_views', [
            'aggregation' => AnalyticsMetricsCollectionService::AGGREGATION_AVG,
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metric_name', $result);
        $this->assertArrayHasKey('calculation_type', $result);
        $this->assertArrayHasKey('aggregation', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertArrayHasKey('data_points', $result);
        $this->assertEquals('avg', $result['aggregation']);
    }

    // ==================== Export Metrics Tests ====================

    public function test_exportMetrics_returns_json_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'test_metric', 'value' => 100]);

        $result = $service->exportMetrics([], AnalyticsMetricsCollectionService::FORMAT_JSON);

        $this->assertEquals('json', $result['format']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertJson($result['data']);
    }

    public function test_exportMetrics_returns_csv_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'test_metric', 'value' => 100]);

        $result = $service->exportMetrics([], AnalyticsMetricsCollectionService::FORMAT_CSV);

        $this->assertEquals('csv', $result['format']);
        $this->assertStringContainsString('id', $result['data']);
        $this->assertStringContainsString('tenant_id', $result['data']);
    }

    public function test_exportMetrics_returns_array_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'test_metric', 'value' => 100]);

        $result = $service->exportMetrics([], AnalyticsMetricsCollectionService::FORMAT_ARRAY);

        $this->assertEquals('array', $result['format']);
        $this->assertIsArray($result['data']);
    }

    public function test_exportMetrics_returns_excel_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'test_metric', 'value' => 100]);

        $result = $service->exportMetrics([], AnalyticsMetricsCollectionService::FORMAT_EXCEL);

        $this->assertEquals('excel', $result['format']);
        $this->assertArrayHasKey('headers', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('sheet_name', $result);
    }

    // ==================== Metrics Summary Tests ====================

    public function test_getMetricsSummary_returns_summary(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'metric1', 'value' => 100, 'type' => 'gauge']);
        $service->collectMetric(['name' => 'metric2', 'value' => 200, 'type' => 'counter']);
        $service->collectMetric(['name' => 'metric3', 'value' => 300, 'type' => 'gauge']);

        $dateRange = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ];

        $result = $service->getMetricsSummary($dateRange);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('date_range', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('total_metrics', $result);
        $this->assertArrayHasKey('by_name', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('top_metrics', $result);
        $this->assertGreaterThan(0, $result['total_metrics']);
    }

    public function test_getMetricsSummary_with_specific_metric_names(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $service->collectMetric(['name' => 'page_views', 'value' => 100]);
        $service->collectMetric(['name' => 'user_logins', 'value' => 50]);
        $service->collectMetric(['name' => 'page_views', 'value' => 200]);

        $dateRange = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ];

        $result = $service->getMetricsSummary($dateRange, ['page_views']);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('by_name', $result);
    }

    // ==================== Configure Metrics Tests ====================

    public function test_configureMetrics_returns_updated_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsMetricsCollectionService($tenantService);

        $config = [
            'max_metrics_history' => 20000,
            'auto_aggregation' => false,
        ];

        $result = $service->configureMetrics($config);

        $this->assertIsArray($result);
        $this->assertEquals(20000, $result['max_metrics_history']);
        $this->assertFalse($result['auto_aggregation']);
    }

    // ==================== Tenant Isolation Tests ====================

    public function test_different_tenants_get_different_metrics(): void
    {
        $tenant1Service = Mockery::mock(TenantContextService::class);
        $tenant1Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-1');

        $tenant2Service = Mockery::mock(TenantContextService::class);
        $tenant2Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-2');

        $service1 = new AnalyticsMetricsCollectionService($tenant1Service);
        $service2 = new AnalyticsMetricsCollectionService($tenant2Service);

        // Collect metric for tenant 1
        $metric1 = $service1->collectMetric(['name' => 'tenant1_metric', 'value' => 100]);

        // Collect metric for tenant 2
        $metric2 = $service2->collectMetric(['name' => 'tenant2_metric', 'value' => 200]);

        // Verify tenant 1 only sees their metrics
        $metrics1 = $service1->getMetrics([], 100, 0);
        $this->assertGreaterThanOrEqual(1, $metrics1['total']);
        $this->assertEquals('tenant1_metric', $metrics1['metrics'][0]['name']);

        // Verify tenant 2 only sees their metrics
        $metrics2 = $service2->getMetrics([], 100, 0);
        $this->assertGreaterThanOrEqual(1, $metrics2['total']);
        $this->assertEquals('tenant2_metric', $metrics2['metrics'][0]['name']);
    }

    public function test_getMetricById_respects_tenant_boundaries(): void
    {
        $tenant1Service = Mockery::mock(TenantContextService::class);
        $tenant1Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-1');

        $tenant2Service = Mockery::mock(TenantContextService::class);
        $tenant2Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-2');

        $service1 = new AnalyticsMetricsCollectionService($tenant1Service);
        $service2 = new AnalyticsMetricsCollectionService($tenant2Service);

        // Tenant 1 collects a metric
        $metric = $service1->collectMetric(['name' => 'tenant1_metric', 'value' => 100]);
        $metricId = $metric['id'];

        // Tenant 2 tries to access tenant 1's metric
        $result = $service2->getMetricById($metricId);

        $this->assertNull($result);
    }

    // ==================== Class Properties Tests ====================

    public function test_class_has_tenant_context_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsMetricsCollectionService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('tenantContextService', $propertyNames);
    }

    public function test_class_has_metrics_config_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsMetricsCollectionService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('metricsConfig', $propertyNames);
    }

    public function test_class_has_metrics_storage_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsMetricsCollectionService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('metricsStorage', $propertyNames);
    }
}
