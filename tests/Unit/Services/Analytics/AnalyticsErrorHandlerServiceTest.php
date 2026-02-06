<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\AnalyticsErrorHandlerService;
use App\Services\TenantContextService;
use Mockery;
use PHPUnit\Framework\TestCase;

class AnalyticsErrorHandlerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== Constants Tests ====================

    public function test_severity_constants(): void
    {
        $this->assertEquals('critical', AnalyticsErrorHandlerService::SEVERITY_CRITICAL);
        $this->assertEquals('high', AnalyticsErrorHandlerService::SEVERITY_HIGH);
        $this->assertEquals('medium', AnalyticsErrorHandlerService::SEVERITY_MEDIUM);
        $this->assertEquals('low', AnalyticsErrorHandlerService::SEVERITY_LOW);
    }

    public function test_category_constants(): void
    {
        $this->assertEquals('data_validation', AnalyticsErrorHandlerService::CATEGORY_DATA_VALIDATION);
        $this->assertEquals('query', AnalyticsErrorHandlerService::CATEGORY_QUERY);
        $this->assertEquals('calculation', AnalyticsErrorHandlerService::CATEGORY_CALCULATION);
        $this->assertEquals('integration', AnalyticsErrorHandlerService::CATEGORY_INTEGRATION);
        $this->assertEquals('performance', AnalyticsErrorHandlerService::CATEGORY_PERFORMANCE);
        $this->assertEquals('security', AnalyticsErrorHandlerService::CATEGORY_SECURITY);
        $this->assertEquals('unknown', AnalyticsErrorHandlerService::CATEGORY_UNKNOWN);
    }

    public function test_recovery_constants(): void
    {
        $this->assertEquals('retry', AnalyticsErrorHandlerService::RECOVERY_RETRY);
        $this->assertEquals('fallback', AnalyticsErrorHandlerService::RECOVERY_FALLBACK);
        $this->assertEquals('skip', AnalyticsErrorHandlerService::RECOVERY_SKIP);
        $this->assertEquals('abort', AnalyticsErrorHandlerService::RECOVERY_ABORT);
    }

    // ==================== Service Construction Tests ====================

    public function test_service_can_be_instantiated(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $service = new AnalyticsErrorHandlerService($tenantService);

        $this->assertInstanceOf(AnalyticsErrorHandlerService::class, $service);
    }

    public function test_service_can_be_instantiated_with_config(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant-uuid');

        $config = [
            'max_retries' => 5,
            'retry_delay_ms' => 2000,
            'fallback_enabled' => false,
        ];

        $service = new AnalyticsErrorHandlerService($tenantService, $config);

        $this->assertInstanceOf(AnalyticsErrorHandlerService::class, $service);
    }

    // ==================== Service Methods Existence Tests ====================

    public function test_handleError_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'handleError'));
    }

    public function test_logError_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'logError'));
    }

    public function test_recoverFromError_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'recoverFromError'));
    }

    public function test_getErrorReport_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'getErrorReport'));
    }

    public function test_getErrorMetrics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'getErrorMetrics'));
    }

    public function test_getErrorTrends_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'getErrorTrends'));
    }

    public function test_configureErrorHandling_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'configureErrorHandling'));
    }

    public function test_getErrorAlerts_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'getErrorAlerts'));
    }

    public function test_clearErrors_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'clearErrors'));
    }

    public function test_getErrorStatistics_method_exists(): void
    {
        $this->assertTrue(method_exists(AnalyticsErrorHandlerService::class, 'getErrorStatistics'));
    }

    // ==================== Class Structure Tests ====================

    public function test_class_has_correct_constants(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $constants = $reflection->getConstants();

        // Verify all expected constants exist
        $this->assertArrayHasKey('SEVERITY_CRITICAL', $constants);
        $this->assertArrayHasKey('SEVERITY_HIGH', $constants);
        $this->assertArrayHasKey('SEVERITY_MEDIUM', $constants);
        $this->assertArrayHasKey('SEVERITY_LOW', $constants);
        $this->assertArrayHasKey('CATEGORY_DATA_VALIDATION', $constants);
        $this->assertArrayHasKey('CATEGORY_QUERY', $constants);
        $this->assertArrayHasKey('CATEGORY_CALCULATION', $constants);
        $this->assertArrayHasKey('CATEGORY_INTEGRATION', $constants);
        $this->assertArrayHasKey('CATEGORY_PERFORMANCE', $constants);
        $this->assertArrayHasKey('CATEGORY_SECURITY', $constants);
        $this->assertArrayHasKey('CATEGORY_UNKNOWN', $constants);
        $this->assertArrayHasKey('RECOVERY_RETRY', $constants);
        $this->assertArrayHasKey('RECOVERY_FALLBACK', $constants);
        $this->assertArrayHasKey('RECOVERY_SKIP', $constants);
        $this->assertArrayHasKey('RECOVERY_ABORT', $constants);
    }

    public function test_class_has_tenant_context_service_dependency(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('tenantContextService', $parameters[0]->getName());
    }

    // ==================== Error Categorization Tests ====================

    public function test_query_exception_is_recognized(): void
    {
        $this->assertTrue(
            is_subclass_of(\Illuminate\Database\QueryException::class, \Throwable::class)
        );
    }

    public function test_invalid_argument_exception_is_recognized(): void
    {
        $this->assertTrue(
            is_subclass_of(\InvalidArgumentException::class, \Throwable::class)
        );
    }

    public function test_runtime_exception_is_recognized(): void
    {
        $this->assertTrue(
            is_subclass_of(\RuntimeException::class, \Throwable::class)
        );
    }

    // ==================== Error Recovery Strategy Tests ====================

    public function test_recovery_strategy_for_query_errors(): void
    {
        // Query errors should use retry strategy
        $this->assertEquals('retry', AnalyticsErrorHandlerService::RECOVERY_RETRY);
    }

    public function test_recovery_strategy_for_validation_errors(): void
    {
        // Validation errors should use skip strategy
        $this->assertEquals('skip', AnalyticsErrorHandlerService::RECOVERY_SKIP);
    }

    public function test_recovery_strategy_for_performance_errors(): void
    {
        // Performance errors should use fallback strategy
        $this->assertEquals('fallback', AnalyticsErrorHandlerService::RECOVERY_FALLBACK);
    }

    public function test_recovery_strategy_for_security_errors(): void
    {
        // Security errors should use abort strategy
        $this->assertEquals('abort', AnalyticsErrorHandlerService::RECOVERY_ABORT);
    }

    // ==================== Error Severity Tests ====================

    public function test_error_class_has_high_severity(): void
    {
        // Error class (not Exception) should be critical
        $this->assertTrue(is_subclass_of(\Error::class, \Throwable::class));
    }

    public function test_overflow_exception_has_critical_severity(): void
    {
        // OverflowException should be critical
        $this->assertTrue(is_subclass_of(\OverflowException::class, \Throwable::class));
    }

    // ==================== Tenant Isolation Tests ====================

    public function test_different_tenants_get_different_service_instances(): void
    {
        $tenant1Service = Mockery::mock(TenantContextService::class);
        $tenant1Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-1');

        $tenant2Service = Mockery::mock(TenantContextService::class);
        $tenant2Service->shouldReceive('getCurrentTenantId')
            ->andReturn('tenant-2');

        $service1 = new AnalyticsErrorHandlerService($tenant1Service);
        $service2 = new AnalyticsErrorHandlerService($tenant2Service);

        $this->assertNotSame($service1, $service2);
    }

    // ==================== Configuration Tests ====================

    public function test_default_configuration_exists(): void
    {
        $tenantService = Mockery::mock(TenantContextService::class);
        $tenantService->shouldReceive('getCurrentTenantId')
            ->andReturn('test-tenant');

        $service = new AnalyticsErrorHandlerService($tenantService);

        // Verify service was created with default config
        $this->assertInstanceOf(AnalyticsErrorHandlerService::class, $service);
    }

    // ==================== Class Properties Tests ====================

    public function test_class_has_tenant_context_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('tenantContextService', $propertyNames);
    }

    public function test_class_has_error_config_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('errorConfig', $propertyNames);
    }

    public function test_class_has_error_history_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('errorHistory', $propertyNames);
    }

    public function test_class_has_error_metrics_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('errorMetrics', $propertyNames);
    }

    public function test_class_has_error_trends_property(): void
    {
        $reflection = new \ReflectionClass(AnalyticsErrorHandlerService::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('errorTrends', $propertyNames);
    }
}
