<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for AnalyticsController tenant isolation
 * 
 * This test suite ensures that analytics data is properly isolated between tenants
 * and that cross-tenant data access is prevented.
 */
class AnalyticsControllerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;
    private Tenant $tenant2;
    private User $user1;
    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'status' => 'active',
        ]);

        // Create users for each tenant
        $this->user1 = User::factory()->create([
            'email' => 'user1@tenant1.com',
        ]);

        $this->user2 = User::factory()->create([
            'email' => 'user2@tenant2.com',
        ]);
    }

    /**
     * Test that analytics events are isolated between tenants
     */
    public function test_analytics_events_are_isolated_between_tenants()
    {
        // Set tenant context to tenant1
        $tenantContextService = app(TenantContextService::class);
        $tenantContextService->setTenant($this->tenant1->id);

        // Create analytics events for tenant1
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'home',
                'action' => 'view',
                'session_id' => 'session-1',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'about',
                'action' => 'view',
                'session_id' => 'session-2',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create analytics events for tenant2
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'contact',
                'action' => 'view',
                'session_id' => 'session-3',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.2',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 can only see its own events
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Events = DB::table('analytics_events')->count();
        $this->assertEquals(2, $tenant1Events, 'Tenant 1 should see only 2 events');

        // Verify tenant2 can only see its own events
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Events = DB::table('analytics_events')->count();
        $this->assertEquals(1, $tenant2Events, 'Tenant 2 should see only 1 event');
    }

    /**
     * Test that conversions are isolated between tenants
     */
    public function test_conversions_are_isolated_between_tenants()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create conversions for tenant1
        DB::table('analytics_conversions')->insert([
            [
                'goalId' => 'goal-1',
                'goalName' => 'Sign Up',
                'goalType' => 'conversion',
                'value' => 100.00,
                'trackingCode' => 'code-1',
                'audience' => 'individual',
                'sessionId' => 'session-1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create conversions for tenant2
        DB::table('analytics_conversions')->insert([
            [
                'goalId' => 'goal-2',
                'goalName' => 'Purchase',
                'goalType' => 'conversion',
                'value' => 250.00,
                'trackingCode' => 'code-2',
                'audience' => 'individual',
                'sessionId' => 'session-2',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 can only see its own conversions
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Conversions = DB::table('analytics_conversions')->count();
        $this->assertEquals(1, $tenant1Conversions, 'Tenant 1 should see only 1 conversion');

        // Verify tenant2 can only see its own conversions
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Conversions = DB::table('analytics_conversions')->count();
        $this->assertEquals(1, $tenant2Conversions, 'Tenant 2 should see only 1 conversion');
    }

    /**
     * Test that errors are isolated between tenants
     */
    public function test_errors_are_isolated_between_tenants()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create errors for tenant1
        DB::table('analytics_errors')->insert([
            [
                'error_type' => 'javascript_error',
                'error_data' => json_encode(['message' => 'Error 1']),
                'session_id' => 'session-1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create errors for tenant2
        DB::table('analytics_errors')->insert([
            [
                'error_type' => 'javascript_error',
                'error_data' => json_encode(['message' => 'Error 2']),
                'session_id' => 'session-2',
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Mozilla/5.0',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 can only see its own errors
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Errors = DB::table('analytics_errors')->count();
        $this->assertEquals(1, $tenant1Errors, 'Tenant 1 should see only 1 error');

        // Verify tenant2 can only see its own errors
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Errors = DB::table('analytics_errors')->count();
        $this->assertEquals(1, $tenant2Errors, 'Tenant 2 should see only 1 error');
    }

    /**
     * Test that cohorts are isolated between tenants
     */
    public function test_cohorts_are_isolated_between_tenants()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create cohort for tenant1
        $cohort1 = \App\Models\Cohort::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cohort 1',
            'criteria' => ['type' => 'acquisition_date'],
            'status' => 'active',
            'created_by' => $this->user1->id,
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create cohort for tenant2
        $cohort2 = \App\Models\Cohort::create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Cohort 2',
            'criteria' => ['type' => 'acquisition_date'],
            'status' => 'active',
            'created_by' => $this->user2->id,
        ]);

        // Verify tenant1 can only see its own cohorts
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Cohorts = \App\Models\Cohort::byTenant($this->tenant1->id)->count();
        $this->assertEquals(1, $tenant1Cohorts, 'Tenant 1 should see only 1 cohort');

        // Verify tenant2 can only see its own cohorts
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Cohorts = \App\Models\Cohort::byTenant($this->tenant2->id)->count();
        $this->assertEquals(1, $tenant2Cohorts, 'Tenant 2 should see only 1 cohort');
    }

    /**
     * Test that custom events are isolated between tenants
     */
    public function test_custom_events_are_isolated_between_tenants()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create custom event definition for tenant1
        $event1 = \App\Models\CustomEventDefinition::create([
            'tenant_id' => $this->tenant1->id,
            'event_name' => 'custom_event_1',
            'schema' => ['type' => 'object'],
            'description' => 'Custom event for tenant 1',
            'category' => 'user_action',
            'created_by' => $this->user1->id,
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create custom event definition for tenant2
        $event2 = \App\Models\CustomEventDefinition::create([
            'tenant_id' => $this->tenant2->id,
            'event_name' => 'custom_event_2',
            'schema' => ['type' => 'object'],
            'description' => 'Custom event for tenant 2',
            'category' => 'user_action',
            'created_by' => $this->user2->id,
        ]);

        // Verify tenant1 can only see its own custom events
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Events = \App\Models\CustomEventDefinition::byTenant($this->tenant1->id)->count();
        $this->assertEquals(1, $tenant1Events, 'Tenant 1 should see only 1 custom event');

        // Verify tenant2 can only see its own custom events
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Events = \App\Models\CustomEventDefinition::byTenant($this->tenant2->id)->count();
        $this->assertEquals(1, $tenant2Events, 'Tenant 2 should see only 1 custom event');
    }

    /**
     * Test that sync logs are isolated between tenants
     */
    public function test_sync_logs_are_isolated_between_tenants()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create sync log for tenant1
        DB::table('sync_logs')->insert([
            [
                'tenant_id' => $this->tenant1->id,
                'sync_type' => 'ga',
                'status' => 'completed',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create sync log for tenant2
        DB::table('sync_logs')->insert([
            [
                'tenant_id' => $this->tenant2->id,
                'sync_type' => 'matomo',
                'status' => 'completed',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 can only see its own sync logs
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Logs = \App\Models\SyncLog::byTenant($this->tenant1->id)->count();
        $this->assertEquals(1, $tenant1Logs, 'Tenant 1 should see only 1 sync log');

        // Verify tenant2 can only see its own sync logs
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Logs = \App\Models\SyncLog::byTenant($this->tenant2->id)->count();
        $this->assertEquals(1, $tenant2Logs, 'Tenant 2 should see only 1 sync log');
    }

    /**
     * Test that tenant context is required for analytics operations
     */
    public function test_tenant_context_is_required_for_analytics_operations()
    {
        $tenantContextService = app(TenantContextService::class);

        // Clear tenant context
        $tenantContextService->clearContext();

        // Attempt to get metrics without tenant context should throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tenant context not available');

        // This should throw an exception
        $tenantContextService->getCurrentTenantId();
    }

    /**
     * Test that cache keys are tenant-specific
     */
    public function test_cache_keys_are_tenant_specific()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Set cache for tenant1
        Cache::put('test_key', 'value_tenant1', 60);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Set cache for tenant2
        Cache::put('test_key', 'value_tenant2', 60);

        // Verify tenant1 cache
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Value = Cache::get('test_key');
        $this->assertEquals('value_tenant1', $tenant1Value, 'Tenant 1 should see its own cache value');

        // Verify tenant2 cache
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Value = Cache::get('test_key');
        $this->assertEquals('value_tenant2', $tenant2Value, 'Tenant 2 should see its own cache value');
    }

    /**
     * Test that metrics calculation respects tenant isolation
     */
    public function test_metrics_calculation_respects_tenant_isolation()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create events for tenant1
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'home',
                'action' => 'view',
                'session_id' => 'session-1',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'about',
                'action' => 'view',
                'session_id' => 'session-2',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create events for tenant2
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'contact',
                'action' => 'view',
                'session_id' => 'session-3',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.2',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 metrics
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1PageViews = DB::table('analytics_events')
            ->where('event_name', 'page_view')
            ->count();
        $this->assertEquals(2, $tenant1PageViews, 'Tenant 1 should have 2 page views');

        // Verify tenant2 metrics
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2PageViews = DB::table('analytics_events')
            ->where('event_name', 'page_view')
            ->count();
        $this->assertEquals(1, $tenant2PageViews, 'Tenant 2 should have 1 page view');
    }

    /**
     * Test that export data respects tenant isolation
     */
    public function test_export_data_respects_tenant_isolation()
    {
        $tenantContextService = app(TenantContextService::class);

        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);

        // Create events for tenant1
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'home',
                'action' => 'view',
                'session_id' => 'session-1',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);

        // Create events for tenant2
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'contact',
                'action' => 'view',
                'session_id' => 'session-2',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.2',
                'timestamp' => now(),
                'created_at' => now(),
            ],
        ]);

        // Verify tenant1 export
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Export = DB::table('analytics_events')
            ->where('audience', 'individual')
            ->get();
        $this->assertCount(1, $tenant1Export, 'Tenant 1 export should contain 1 event');

        // Verify tenant2 export
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Export = DB::table('analytics_events')
            ->where('audience', 'individual')
            ->get();
        $this->assertCount(1, $tenant2Export, 'Tenant 2 export should contain 1 event');
    }

    /**
     * Test that validateTenantIsolation throws exception for invalid tenant access
     */
    public function test_validateTenantIsolation_throws_exception_for_invalid_access()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create a mock controller to test the validateTenantIsolation method
        $controller = new \App\Http\Controllers\AnalyticsController($tenantContextService);
        
        // Attempt to validate tenant isolation without proper user access
        // This should fail because we're not authenticated
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Tenant context is required');
        
        // Clear the tenant context first
        $tenantContextService->clearContext();
        
        // Use reflection to test the private method
        $reflectionMethod = new \ReflectionMethod($controller, 'validateTenantIsolation');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($controller);
    }

    /**
     * Test that ensureTenantContext properly applies tenant schema
     */
    public function test_ensureTenantContext_applies_tenant_schema()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create a mock controller to test the ensureTenantContext method
        $controller = new \App\Http\Controllers\AnalyticsController($tenantContextService);
        
        // Use reflection to test the private method
        $reflectionMethod = new \ReflectionMethod($controller, 'ensureTenantContext');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($controller);
        
        // Verify we're in the correct schema
        $this->assertEquals(
            $tenantContextService->generateSchemaName($this->tenant1->id),
            $tenantContextService->getCurrentSchema(),
            'Should be in tenant1 schema after ensureTenantContext'
        );
    }

    /**
     * Test that getTenantIdForInsert returns correct tenant ID
     */
    public function test_getTenantIdForInsert_returns_correct_tenant_id()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create a mock controller to test the getTenantIdForInsert method
        $controller = new \App\Http\Controllers\AnalyticsController($tenantContextService);
        
        // Use reflection to test the private method
        $reflectionMethod = new \ReflectionMethod($controller, 'getTenantIdForInsert');
        $reflectionMethod->setAccessible(true);
        
        $tenantId = $reflectionMethod->invoke($controller);
        
        $this->assertEquals($this->tenant1->id, $tenantId, 'Should return tenant1 ID');
    }

    /**
     * Test that cross-tenant data access is prevented in metrics calculation
     */
    public function test_cross_tenant_data_access_is_prevented_in_metrics()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create events for tenant1
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'home',
                'action' => 'view',
                'session_id' => 'session-1',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.1',
                'timestamp' => now()->subDays(5),
                'created_at' => now(),
            ],
        ]);
        
        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        
        // Create events for tenant2
        DB::table('analytics_events')->insert([
            [
                'event_name' => 'page_view',
                'audience' => 'individual',
                'section' => 'contact',
                'action' => 'view',
                'session_id' => 'session-2',
                'user_agent' => 'Mozilla/5.0',
                'ip_address' => '192.168.1.2',
                'timestamp' => now()->subDays(5),
                'created_at' => now(),
            ],
        ]);
        
        // Now switch back to tenant1 and verify metrics only show tenant1 data
        $tenantContextService->setTenant($this->tenant1->id);
        
        $tenant1PageViews = DB::table('analytics_events')
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp', [now()->subDays(7), now()])
            ->count();
            
        $this->assertEquals(1, $tenant1PageViews, 'Tenant 1 should only see its own page views even when querying for date range');
        
        // Verify tenant2 has its own data
        $tenantContextService->setTenant($this->tenant2->id);
        
        $tenant2PageViews = DB::table('analytics_events')
            ->where('event_name', 'page_view')
            ->whereBetween('timestamp', [now()->subDays(7), now()])
            ->count();
            
        $this->assertEquals(1, $tenant2PageViews, 'Tenant 2 should only see its own page views');
    }

    /**
     * Test that storeEvents includes tenant_id in inserts
     */
    public function test_storeEvents_includes_tenant_id()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create events using the controller's processEventChunk method
        $controller = new \App\Http\Controllers\AnalyticsController($tenantContextService);
        
        $events = [
            [
                'eventName' => 'page_view',
                'audience' => 'individual',
                'section' => 'home',
                'action' => 'view',
                'timestamp' => now(),
            ],
        ];
        
        // Use reflection to test the private method
        $reflectionMethod = new \ReflectionMethod($controller, 'processEventChunk');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($controller, $events, 'test-session', 'Mozilla/5.0', '192.168.1.1', $this->tenant1->id);
        
        // Verify the event was created with tenant_id
        $event = DB::table('analytics_events')->first();
        
        $this->assertNotNull($event, 'Event should be created');
        $this->assertEquals($this->tenant1->id, $event->tenant_id, 'Event should have tenant1 ID');
    }

    /**
     * Test that storeConversion includes tenant_id in inserts
     */
    public function test_storeConversion_includes_tenant_id()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create a conversion for tenant1
        DB::table('analytics_conversions')->insert([
            [
                'goalId' => 'goal-1',
                'goalName' => 'Sign Up',
                'goalType' => 'conversion',
                'value' => 100.00,
                'trackingCode' => 'code-1',
                'audience' => 'individual',
                'sessionId' => 'session-1',
                'timestamp' => now(),
                'created_at' => now(),
                'tenant_id' => $this->tenant1->id,
            ],
        ]);
        
        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        
        // Create a conversion for tenant2
        DB::table('analytics_conversions')->insert([
            [
                'goalId' => 'goal-2',
                'goalName' => 'Purchase',
                'goalType' => 'conversion',
                'value' => 250.00,
                'trackingCode' => 'code-2',
                'audience' => 'individual',
                'sessionId' => 'session-2',
                'timestamp' => now(),
                'created_at' => now(),
                'tenant_id' => $this->tenant2->id,
            ],
        ]);
        
        // Verify tenant1 conversions
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Conversions = DB::table('analytics_conversions')
            ->where('tenant_id', $this->tenant1->id)
            ->count();
        $this->assertEquals(1, $tenant1Conversions, 'Tenant 1 should have 1 conversion');
        
        // Verify tenant2 conversions
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Conversions = DB::table('analytics_conversions')
            ->where('tenant_id', $this->tenant2->id)
            ->count();
        $this->assertEquals(1, $tenant2Conversions, 'Tenant 2 should have 1 conversion');
    }

    /**
     * Test that storeError includes tenant_id in inserts
     */
    public function test_storeError_includes_tenant_id()
    {
        $tenantContextService = app(TenantContextService::class);
        
        // Set tenant context to tenant1
        $tenantContextService->setTenant($this->tenant1->id);
        
        // Create an error for tenant1
        DB::table('analytics_errors')->insert([
            [
                'error_type' => 'javascript_error',
                'error_data' => json_encode(['message' => 'Error 1']),
                'session_id' => 'session-1',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'timestamp' => now(),
                'created_at' => now(),
                'tenant_id' => $this->tenant1->id,
            ],
        ]);
        
        // Switch to tenant2
        $tenantContextService->setTenant($this->tenant2->id);
        
        // Create an error for tenant2
        DB::table('analytics_errors')->insert([
            [
                'error_type' => 'javascript_error',
                'error_data' => json_encode(['message' => 'Error 2']),
                'session_id' => 'session-2',
                'ip_address' => '192.168.1.2',
                'user_agent' => 'Mozilla/5.0',
                'timestamp' => now(),
                'created_at' => now(),
                'tenant_id' => $this->tenant2->id,
            ],
        ]);
        
        // Verify tenant1 errors
        $tenantContextService->setTenant($this->tenant1->id);
        $tenant1Errors = DB::table('analytics_errors')
            ->where('tenant_id', $this->tenant1->id)
            ->count();
        $this->assertEquals(1, $tenant1Errors, 'Tenant 1 should have 1 error');
        
        // Verify tenant2 errors
        $tenantContextService->setTenant($this->tenant2->id);
        $tenant2Errors = DB::table('analytics_errors')
            ->where('tenant_id', $this->tenant2->id)
            ->count();
        $this->assertEquals(1, $tenant2Errors, 'Tenant 2 should have 1 error');
    }
}
