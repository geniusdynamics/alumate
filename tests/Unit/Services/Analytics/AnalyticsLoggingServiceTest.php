<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsLoggingService;
use App\Services\TenantContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AnalyticsLoggingServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Constants Tests ====================

    public function test_type_constants(): void
    {
        $this->assertEquals('event', AnalyticsLoggingService::TYPE_EVENT);
        $this->assertEquals('query', AnalyticsLoggingService::TYPE_QUERY);
        $this->assertEquals('metric', AnalyticsLoggingService::TYPE_METRIC);
        $this->assertEquals('performance', AnalyticsLoggingService::TYPE_PERFORMANCE);
        $this->assertEquals('error', AnalyticsLoggingService::TYPE_ERROR);
    }

    public function test_severity_constants(): void
    {
        $this->assertEquals('debug', AnalyticsLoggingService::SEVERITY_DEBUG);
        $this->assertEquals('info', AnalyticsLoggingService::SEVERITY_INFO);
        $this->assertEquals('warning', AnalyticsLoggingService::SEVERITY_WARNING);
        $this->assertEquals('error', AnalyticsLoggingService::SEVERITY_ERROR);
        $this->assertEquals('critical', AnalyticsLoggingService::SEVERITY_CRITICAL);
    }

    public function test_format_constants(): void
    {
        $this->assertEquals('json', AnalyticsLoggingService::FORMAT_JSON);
        $this->assertEquals('csv', AnalyticsLoggingService::FORMAT_CSV);
        $this->assertEquals('array', AnalyticsLoggingService::FORMAT_ARRAY);
    }

    // ==================== Service Construction Tests ====================

    public function test_service_can_be_instantiated(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $this->assertInstanceOf(AnalyticsLoggingService::class, $service);
    }

    public function test_service_can_be_instantiated_with_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $config = [
            'max_log_history' => 5000,
            'log_retention_days' => 60,
            'log_slow_queries' => false,
        ];

        $service = new AnalyticsLoggingService($tenantService, $config);

        $this->assertInstanceOf(AnalyticsLoggingService::class, $service);
    }

    // ==================== Service Methods Existence Tests ====================

    public function test_logEvent_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'logEvent'));
    }

    public function test_logQuery_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'logQuery'));
    }

    public function test_logMetric_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'logMetric'));
    }

    public function test_logPerformance_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'logPerformance'));
    }

    public function test_logError_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'logError'));
    }

    public function test_getLogs_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'getLogs'));
    }

    public function test_getLogById_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'getLogById'));
    }

    public function test_exportLogs_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'exportLogs'));
    }

    public function test_getLogSummary_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'getLogSummary'));
    }

    public function test_searchLogs_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'searchLogs'));
    }

    public function test_clearLogs_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'clearLogs'));
    }

    public function test_configureLogging_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsLoggingService::class, 'configureLogging'));
    }

    // ==================== Class Structure Tests ====================

    public function test_class_has_correct_constants(): void
    {
        $reflection = new \ReflectionClass(AnalyticsLoggingService::class);
        $constants = $reflection->getConstants();

        // Verify type constants exist
        $this->assertArrayHasKey('TYPE_EVENT', $constants);
        $this->assertArrayHasKey('TYPE_QUERY', $constants);
        $this->assertArrayHasKey('TYPE_METRIC', $constants);
        $this->assertArrayHasKey('TYPE_PERFORMANCE', $constants);
        $this->assertArrayHasKey('TYPE_ERROR', $constants);

        // Verify severity constants exist
        $this->assertArrayHasKey('SEVERITY_DEBUG', $constants);
        $this->assertArrayHasKey('SEVERITY_INFO', $constants);
        $this->assertArrayHasKey('SEVERITY_WARNING', $constants);
        $this->assertArrayHasKey('SEVERITY_ERROR', $constants);
        $this->assertArrayHasKey('SEVERITY_CRITICAL', $constants);

        // Verify format constants exist
        $this->assertArrayHasKey('FORMAT_JSON', $constants);
        $this->assertArrayHasKey('FORMAT_CSV', $constants);
        $this->assertArrayHasKey('FORMAT_ARRAY', $constants);
    }

    public function test_class_has_tenant_context_service_dependency(): void
    {
        $reflection = new \ReflectionClass(AnalyticsLoggingService::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('tenantContextService', $parameters[0]->getName());
    }

    // ==================== Event Logging Tests ====================

    public function test_logEvent_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $event = [
            'name' => 'user_login',
            'category' => 'authentication',
            'data' => ['user_id' => 123],
            'user_id' => 123,
        ];

        $result = $service->logEvent($event);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('category', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_EVENT, $result['type']);
        $this->assertEquals('user_login', $result['name']);
        $this->assertEquals('test-tenant-uuid', $result['tenant_id']);
    }

    public function test_logEvent_with_minimal_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $result = $service->logEvent([]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals('unknown', $result['name']);
        $this->assertEquals('general', $result['category']);
    }

    // ==================== Query Logging Tests ====================

    public function test_logQuery_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $query = [
            'query' => 'SELECT * FROM users',
            'execution_time_ms' => 150,
            'result_count' => 100,
            'source' => 'user_service',
        ];

        $result = $service->logQuery($query);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('execution_time_ms', $result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_QUERY, $result['type']);
        $this->assertEquals(150, $result['execution_time_ms']);
    }

    public function test_logQuery_with_minimal_data(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $result = $service->logQuery([]);

        $this->assertIsArray($result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_QUERY, $result['type']);
        $this->assertEquals('', $result['query']);
        $this->assertEquals(0, $result['execution_time_ms']);
    }

    // ==================== Metric Logging Tests ====================

    public function test_logMetric_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $metric = [
            'name' => 'daily_active_users',
            'value' => 1500,
            'unit' => 'users',
            'source' => 'analytics',
        ];

        $result = $service->logMetric($metric);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('value', $result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_METRIC, $result['type']);
        $this->assertEquals('daily_active_users', $result['name']);
        $this->assertEquals(1500, $result['value']);
    }

    public function test_logMetric_with_change_percentage(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $metric = [
            'name' => 'revenue',
            'value' => 10000,
            'previous_value' => 8000,
            'change_percentage' => 25,
            'unit' => 'currency',
        ];

        $result = $service->logMetric($metric);

        $this->assertEquals(25, $result['change_percentage']);
        $this->assertEquals(10000, $result['value']);
        $this->assertEquals(8000, $result['previous_value']);
    }

    // ==================== Performance Logging Tests ====================

    public function test_logPerformance_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $performance = [
            'operation' => 'data_processing',
            'duration_ms' => 2500,
            'memory_usage_mb' => 128,
            'query_count' => 15,
        ];

        $result = $service->logPerformance($performance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('operation', $result);
        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_PERFORMANCE, $result['type']);
        $this->assertEquals('data_processing', $result['operation']);
        $this->assertEquals(2500, $result['duration_ms']);
    }

    public function test_logPerformance_with_cache_stats(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $performance = [
            'operation' => 'report_generation',
            'duration_ms' => 500,
            'cache_hits' => 10,
            'cache_misses' => 2,
        ];

        $result = $service->logPerformance($performance);

        $this->assertEquals(10, $result['cache_hits']);
        $this->assertEquals(2, $result['cache_misses']);
    }

    // ==================== Error Logging Tests ====================

    public function test_logError_returns_array_with_id(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $error = [
            'message' => 'Database connection failed',
            'code' => 500,
            'severity' => AnalyticsLoggingService::SEVERITY_ERROR,
            'category' => 'database',
        ];

        $result = $service->logError($error);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('severity', $result);
        $this->assertEquals(AnalyticsLoggingService::TYPE_ERROR, $result['type']);
        $this->assertEquals('Database connection failed', $result['message']);
        $this->assertEquals(AnalyticsLoggingService::SEVERITY_ERROR, $result['severity']);
    }

    public function test_logError_with_trace_and_context(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $error = [
            'message' => 'Query execution failed',
            'file' => '/app/Services/DatabaseService.php',
            'line' => 42,
            'severity' => AnalyticsLoggingService::SEVERITY_WARNING,
            'category' => 'query',
            'context' => ['query' => 'SELECT * FROM table'],
        ];

        $result = $service->logError($error);

        $this->assertEquals('/app/Services/DatabaseService.php', $result['file']);
        $this->assertEquals(42, $result['line']);
        $this->assertEquals('query', $result['category']);
    }

    // ==================== Log Querying Tests ====================

    public function test_getLogs_returns_logs_with_pagination(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        // Log some events
        $service->logEvent(['name' => 'event1', 'category' => 'test']);
        $service->logEvent(['name' => 'event2', 'category' => 'test']);
        $service->logEvent(['name' => 'event3', 'category' => 'other']);

        $result = $service->getLogs(['category' => 'test'], 10, 0);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('logs', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('has_more', $result);
        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['logs']);
    }

    public function test_getLogs_filters_by_type(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        // Log different types
        $service->logEvent(['name' => 'test_event']);
        $service->logQuery(['query' => 'SELECT * FROM users']);
        $service->logMetric(['name' => 'test_metric']);

        $result = $service->getLogs(['type' => AnalyticsLoggingService::TYPE_EVENT], 100, 0);

        $this->assertEquals(1, $result['total']);
        $this->assertEquals(AnalyticsLoggingService::TYPE_EVENT, $result['logs'][0]['type']);
    }

    public function test_getLogs_filters_by_date_range(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'old_event']);

        $dateRange = [
            'since' => now()->subHour()->toIso8601String(),
            'until' => now()->addHour()->toIso8601String(),
        ];

        $result = $service->getLogs($dateRange, 100, 0);

        $this->assertGreaterThanOrEqual(1, $result['total']);
    }

    public function test_getLogs_returns_empty_when_no_logs(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $result = $service->getLogs([], 10, 0);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['logs']);
    }

    // ==================== Get Log By ID Tests ====================

    public function test_getLogById_returns_log_when_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $event = $service->logEvent(['name' => 'test_event', 'category' => 'test']);
        $logId = $event['id'];

        $result = $service->getLogById($logId);

        $this->assertIsArray($result);
        $this->assertEquals($logId, $result['id']);
        $this->assertEquals('test_event', $result['name']);
    }

    public function test_getLogById_returns_null_when_not_found(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $result = $service->getLogById('non_existent_id');

        $this->assertNull($result);
    }

    // ==================== Export Logs Tests ====================

    public function test_exportLogs_returns_json_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'test_event']);

        $result = $service->exportLogs([], AnalyticsLoggingService::FORMAT_JSON);

        $this->assertEquals('json', $result['format']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertJson($result['data']);
    }

    public function test_exportLogs_returns_csv_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'test_event']);

        $result = $service->exportLogs([], AnalyticsLoggingService::FORMAT_CSV);

        $this->assertEquals('csv', $result['format']);
        $this->assertStringContainsString('id', $result['data']);
        $this->assertStringContainsString('tenant_id', $result['data']);
    }

    public function test_exportLogs_returns_array_format(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'test_event']);

        $result = $service->exportLogs([], AnalyticsLoggingService::FORMAT_ARRAY);

        $this->assertEquals('array', $result['format']);
        $this->assertIsArray($result['data']);
    }

    // ==================== Log Summary Tests ====================

    public function test_getLogSummary_returns_summary(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        // Log various types
        $service->logEvent(['name' => 'event1', 'category' => 'test']);
        $service->logEvent(['name' => 'event2', 'category' => 'test']);
        $service->logQuery(['query' => 'SELECT 1', 'execution_time_ms' => 100]);
        $service->logError(['message' => 'Error occurred']);

        $dateRange = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ];

        $result = $service->getLogSummary($dateRange);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tenant_id', $result);
        $this->assertArrayHasKey('date_range', $result);
        $this->assertArrayHasKey('total_logs', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_severity', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertGreaterThan(0, $result['total_logs']);
    }

    public function test_getLogSummary_includes_top_events(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        // Log multiple events with the same name
        $service->logEvent(['name' => 'page_view', 'category' => 'engagement']);
        $service->logEvent(['name' => 'page_view', 'category' => 'engagement']);
        $service->logEvent(['name' => 'page_view', 'category' => 'engagement']);
        $service->logEvent(['name' => 'click', 'category' => 'engagement']);

        $dateRange = [
            'start' => now()->subDay()->toIso8601String(),
            'end' => now()->addDay()->toIso8601String(),
        ];

        $result = $service->getLogSummary($dateRange);

        $this->assertArrayHasKey('top_events', $result);
        $this->assertNotEmpty($result['top_events']);
        $this->assertEquals('page_view', $result['top_events'][0]['name']);
        $this->assertEquals(3, $result['top_events'][0]['count']);
    }

    // ==================== Search Logs Tests ====================

    public function test_searchLogs_returns_results(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'user_login', 'category' => 'authentication']);
        $service->logEvent(['name' => 'user_logout', 'category' => 'authentication']);

        $result = $service->searchLogs('login');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals('login', $result['query']);
        $this->assertEquals(1, $result['total']);
    }

    public function test_searchLogs_with_filters(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'user_login', 'category' => 'authentication']);
        $service->logEvent(['name' => 'admin_login', 'category' => 'authentication']);

        $result = $service->searchLogs('login', ['category' => 'authentication']);

        $this->assertEquals(2, $result['total']);
    }

    // ==================== Clear Logs Tests ====================

    public function test_clearLogs_returns_success_result(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'test_event']);

        $result = $service->clearLogs(['type' => AnalyticsLoggingService::TYPE_EVENT]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('cleared_count', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['cleared_count']);
    }

    public function test_clearLogs_with_date_range(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $service->logEvent(['name' => 'old_event']);

        $result = $service->clearLogs([
            'older_than' => now()->addDay()->toIso8601String(),
        ]);

        $this->assertTrue($result['success']);
    }

    // ==================== Configure Logging Tests ====================

    public function test_configureLogging_returns_updated_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsLoggingService($tenantService);

        $config = [
            'max_log_history' => 20000,
            'log_slow_queries' => false,
        ];

        $result = $service->configureLogging($config);

        $this->assertIsArray($result);
        $this->assertEquals(20000, $result['max_log_history']);
        $this->assertFalse($result['log_slow_queries']);
    }

    // ==================== Tenant Isolation Tests ====================

    public function test_different_tenants_get_different_logs(): void
    {
        $tenant1Service = Mockery::mock(TenantContextService::class);
        $tenant1Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-1');

        $tenant2Service = Mockery::mock(TenantContextService::class);
        $tenant2Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-2');

        $service1 = new AnalyticsLoggingService($tenant1Service);
        $service2 = new AnalyticsLoggingService($tenant2Service);

        // Log event for tenant 1
        $event1 = $service1->logEvent(['name' => 'tenant1_event', 'category' => 'test']);

        // Log event for tenant 2
        $event2 = $service2->logEvent(['name' => 'tenant2_event', 'category' => 'test']);

        // Verify tenant 1 only sees their events
        $logs1 = $service1->getLogs(['category' => 'test'], 100, 0);
        $this->assertEquals(1, $logs1['total']);
        $this->assertEquals('tenant1_event', $logs1['logs'][0]['name']);

        // Verify tenant 2 only sees their events
        $logs2 = $service2->getLogs(['category' => 'test'], 100, 0);
        $this->assertEquals(1, $logs2['total']);
        $this->assertEquals('tenant2_event', $logs2['logs'][0]['name']);
    }

    // ==================== Class Properties Tests ====================

    public function test_class_has_tenant_context_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsLoggingService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('tenantContextService', $propertyNames);
    }

    public function test_class_has_log_config_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsLoggingService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('logConfig', $propertyNames);
    }

    public function test_class_has_log_storage_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsLoggingService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('logStorage', $propertyNames);
    }
}
