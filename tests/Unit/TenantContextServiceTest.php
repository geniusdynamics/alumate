<?php

// ABOUTME: Unit tests for TenantContextService to verify tenant context management and schema switching functionality
// ABOUTME: Tests cover tenant resolution, schema switching, context isolation, and error handling scenarios

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Exception;

class TenantContextServiceTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextService $tenantContextService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContextService = app(TenantContextService::class);
    }

    /** @test */
    public function it_can_set_and_get_current_tenant_id()
    {
        $tenantId = 'tenant_123';
        
        $this->tenantContextService->setCurrentTenant($tenantId);
        
        $this->assertEquals($tenantId, $this->tenantContextService->getCurrentTenantId());
    }

    /** @test */
    public function it_can_switch_database_schema()
    {
        $tenantId = 'tenant_456';
        
        $this->tenantContextService->setCurrentTenant($tenantId);
        
        // Verify the database connection is using the correct schema
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals($tenantId, $currentSchema);
    }

    /** @test */
    public function it_throws_exception_for_invalid_tenant_id()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid tenant ID');
        
        $this->tenantContextService->setCurrentTenant('');
    }

    /** @test */
    public function it_throws_exception_for_null_tenant_id()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid tenant ID');
        
        $this->tenantContextService->setCurrentTenant(null);
    }

    /** @test */
    public function it_can_clear_current_tenant()
    {
        $tenantId = 'tenant_789';
        
        $this->tenantContextService->setCurrentTenant($tenantId);
        $this->assertEquals($tenantId, $this->tenantContextService->getCurrentTenantId());
        
        $this->tenantContextService->clearCurrentTenant();
        $this->assertNull($this->tenantContextService->getCurrentTenantId());
    }

    /** @test */
    public function it_maintains_tenant_isolation_between_requests()
    {
        $tenant1 = 'tenant_001';
        $tenant2 = 'tenant_002';
        
        // Set first tenant
        $this->tenantContextService->setCurrentTenant($tenant1);
        $this->assertEquals($tenant1, $this->tenantContextService->getCurrentTenantId());
        
        // Switch to second tenant
        $this->tenantContextService->setCurrentTenant($tenant2);
        $this->assertEquals($tenant2, $this->tenantContextService->getCurrentTenantId());
        
        // Verify isolation - should not have access to first tenant
        $this->assertNotEquals($tenant1, $this->tenantContextService->getCurrentTenantId());
    }

    /** @test */
    public function it_can_execute_callback_within_tenant_context()
    {
        $tenantId = 'tenant_callback';
        $result = null;
        
        $this->tenantContextService->withinTenantContext($tenantId, function() use (&$result) {
            $result = $this->tenantContextService->getCurrentTenantId();
        });
        
        $this->assertEquals($tenantId, $result);
        // Context should be cleared after callback
        $this->assertNull($this->tenantContextService->getCurrentTenantId());
    }

    /** @test */
    public function it_restores_previous_context_after_callback_exception()
    {
        $originalTenant = 'original_tenant';
        $tempTenant = 'temp_tenant';
        
        $this->tenantContextService->setCurrentTenant($originalTenant);
        
        try {
            $this->tenantContextService->withinTenantContext($tempTenant, function() {
                throw new Exception('Test exception');
            });
        } catch (Exception $e) {
            // Exception expected
        }
        
        // Should restore original context
        $this->assertEquals($originalTenant, $this->tenantContextService->getCurrentTenantId());
    }

    /** @test */
    public function it_validates_tenant_schema_exists_before_switching()
    {
        $nonExistentTenant = 'non_existent_tenant_999';
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tenant schema does not exist');
        
        $this->tenantContextService->setCurrentTenant($nonExistentTenant);
    }

    /** @test */
    public function it_can_get_tenant_database_connection()
    {
        $tenantId = 'tenant_connection';
        
        $this->tenantContextService->setCurrentTenant($tenantId);
        $connection = $this->tenantContextService->getTenantConnection();
        
        $this->assertNotNull($connection);
        $this->assertInstanceOf(\Illuminate\Database\Connection::class, $connection);
    }
}