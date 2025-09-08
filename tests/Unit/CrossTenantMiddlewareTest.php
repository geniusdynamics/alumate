<?php
// ABOUTME: Unit tests for CrossTenantMiddleware class functionality
// ABOUTME: Tests tenant context extraction, validation, schema switching, and audit logging

namespace Tests\Unit;

use App\Http\Middleware\CrossTenantMiddleware;
use App\Models\Tenant;
use App\Models\GlobalUser;
use App\Models\UserTenantMembership;
use App\Services\TenantContextService;
use App\Services\TenantSchemaService;
use App\Services\CrossTenantSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class CrossTenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected CrossTenantMiddleware $middleware;
    protected TenantContextService $contextService;
    protected TenantSchemaService $schemaService;
    protected CrossTenantSyncService $syncService;
    protected Tenant $tenant;
    protected GlobalUser $globalUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->contextService = Mockery::mock(TenantContextService::class);
        $this->schemaService = Mockery::mock(TenantSchemaService::class);
        $this->syncService = Mockery::mock(CrossTenantSyncService::class);
        
        $this->middleware = new CrossTenantMiddleware(
            $this->contextService,
            $this->schemaService,
            $this->syncService
        );
        
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        $this->tenant = Tenant::create([
            'name' => 'Test University',
            'slug' => 'test-uni',
            'domain' => 'test.example.com',
            'status' => 'active',
            'settings' => ['timezone' => 'UTC'],
        ]);

        $this->globalUser = GlobalUser::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => ['theme' => 'light'],
        ]);

        UserTenantMembership::create([
            'global_user_id' => $this->globalUser->id,
            'tenant_id' => $this->tenant->id,
            'role' => 'student',
            'status' => 'active',
            'permissions' => ['read', 'write'],
        ]);
    }

    /** @test */
    public function it_extracts_tenant_context_from_subdomain(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_extracts_tenant_context_from_header(): void
    {
        $request = Request::create('http://api.example.com/users');
        $request->headers->set('X-Tenant-ID', $this->tenant->id);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_extracts_tenant_context_from_parameter(): void
    {
        $request = Request::create('http://example.com/api/tenants/' . $this->tenant->id . '/users');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_404_when_tenant_not_found(): void
    {
        $request = Request::create('http://nonexistent.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn(null);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Tenant not found', $response->getContent());
    }

    /** @test */
    public function it_returns_403_when_tenant_is_inactive(): void
    {
        $inactiveTenant = Tenant::create([
            'name' => 'Inactive University',
            'slug' => 'inactive-uni',
            'domain' => 'inactive.example.com',
            'status' => 'inactive',
            'settings' => ['timezone' => 'UTC'],
        ]);
        
        $request = Request::create('http://inactive.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($inactiveTenant);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Tenant is not active', $response->getContent());
    }

    /** @test */
    public function it_validates_user_access_to_tenant(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        // Mock authenticated user
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($this->globalUser);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('validateUserAccess')
            ->with($this->globalUser, $this->tenant)
            ->once()
            ->andReturn(true);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_403_when_user_has_no_access_to_tenant(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        // Mock authenticated user
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($this->globalUser);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('validateUserAccess')
            ->with($this->globalUser, $this->tenant)
            ->once()
            ->andReturn(false);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Access denied', $response->getContent());
    }

    /** @test */
    public function it_allows_guest_access_for_public_routes(): void
    {
        $request = Request::create('http://test-uni.example.com/login');
        
        // Mock guest user
        Auth::shouldReceive('check')->andReturn(false);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        }, 'allow_guests');
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_switches_back_to_public_schema_after_request(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToPublicSchema')
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_tenant_access_for_audit(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        // Mock authenticated user
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($this->globalUser);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('validateUserAccess')
            ->with($this->globalUser, $this->tenant)
            ->once()
            ->andReturn(true);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify audit log was created
        $this->assertDatabaseHas('audit_trail', [
            'operation' => 'tenant_access',
            'table_name' => 'tenants',
            'record_id' => $this->tenant->id,
            'user_id' => $this->globalUser->id,
        ]);
    }

    /** @test */
    public function it_handles_schema_switching_errors(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once()
            ->andThrow(new \Exception('Schema not found'));
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Tenant schema error', $response->getContent());
    }

    /** @test */
    public function it_handles_sync_operation_failures(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => false, 'error' => 'Sync failed']);
        
        $this->schemaService
            ->shouldReceive('switchToPublicSchema')
            ->once();
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        // Should continue despite sync failure
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_caches_tenant_resolution_results(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify cache was set
        $cacheKey = 'tenant:resolution:' . md5($request->getHost());
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_handles_maintenance_mode(): void
    {
        $maintenanceTenant = Tenant::create([
            'name' => 'Maintenance University',
            'slug' => 'maintenance-uni',
            'domain' => 'maintenance.example.com',
            'status' => 'maintenance',
            'settings' => ['timezone' => 'UTC'],
        ]);
        
        $request = Request::create('http://maintenance.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($maintenanceTenant);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertStringContainsString('maintenance', $response->getContent());
    }

    /** @test */
    public function it_allows_super_admin_access_during_maintenance(): void
    {
        $superAdmin = GlobalUser::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'is_super_admin' => true,
        ]);
        
        $maintenanceTenant = Tenant::create([
            'name' => 'Maintenance University',
            'slug' => 'maintenance-uni',
            'domain' => 'maintenance.example.com',
            'status' => 'maintenance',
            'settings' => ['timezone' => 'UTC'],
        ]);
        
        $request = Request::create('http://maintenance.example.com/dashboard');
        
        // Mock authenticated super admin
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($superAdmin);
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($maintenanceTenant);
        
        $this->contextService
            ->shouldReceive('validateUserAccess')
            ->with($superAdmin, $maintenanceTenant)
            ->once()
            ->andReturn(true);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($maintenanceTenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($maintenanceTenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($maintenanceTenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_tracks_request_performance_metrics(): void
    {
        $request = Request::create('http://test-uni.example.com/dashboard');
        
        $this->contextService
            ->shouldReceive('resolveTenantFromRequest')
            ->with($request)
            ->once()
            ->andReturn($this->tenant);
        
        $this->contextService
            ->shouldReceive('setCurrentTenant')
            ->with($this->tenant)
            ->once();
        
        $this->schemaService
            ->shouldReceive('switchToSchema')
            ->with($this->tenant->id)
            ->once();
        
        $this->syncService
            ->shouldReceive('syncPendingOperations')
            ->with($this->tenant->id)
            ->once()
            ->andReturn(['success' => true]);
        
        $response = $this->middleware->handle($request, function ($req) {
            // Simulate some processing time
            usleep(10000); // 10ms
            return new Response('OK');
        });
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify performance metrics were logged
        $this->assertDatabaseHas('audit_trail', [
            'operation' => 'request_performance',
            'table_name' => 'requests',
            'category' => 'performance',
        ]);
    }
}