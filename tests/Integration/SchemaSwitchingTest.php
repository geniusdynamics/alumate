<?php

// ABOUTME: Integration tests for schema switching functionality to verify database operations work correctly across tenant schemas
// ABOUTME: Tests cover schema creation, data isolation, query execution, and cross-tenant data protection

namespace Tests\Integration;

use Tests\TestCase;
use App\Services\TenantContextService;
use App\Models\User;
use App\Models\Lead;
use App\Models\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class SchemaSwitchingTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextService $tenantContextService;
    private string $tenant1 = 'test_tenant_1';
    private string $tenant2 = 'test_tenant_2';

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContextService = app(TenantContextService::class);
        
        // Create test tenant schemas
        $this->createTenantSchema($this->tenant1);
        $this->createTenantSchema($this->tenant2);
    }

    protected function tearDown(): void
    {
        // Clean up test schemas
        $this->dropTenantSchema($this->tenant1);
        $this->dropTenantSchema($this->tenant2);
        
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_and_switch_between_tenant_schemas()
    {
        // Switch to tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals($this->tenant1, $currentSchema);
        
        // Switch to tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals($this->tenant2, $currentSchema);
    }

    /** @test */
    public function it_maintains_data_isolation_between_tenant_schemas()
    {
        // Create user in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $user1 = User::create([
            'name' => 'Tenant 1 User',
            'email' => 'user1@tenant1.com',
            'password' => bcrypt('password')
        ]);
        
        // Create user in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $user2 = User::create([
            'name' => 'Tenant 2 User',
            'email' => 'user2@tenant2.com',
            'password' => bcrypt('password')
        ]);
        
        // Verify tenant 1 only sees its user
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $tenant1Users = User::all();
        $this->assertCount(1, $tenant1Users);
        $this->assertEquals('Tenant 1 User', $tenant1Users->first()->name);
        
        // Verify tenant 2 only sees its user
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $tenant2Users = User::all();
        $this->assertCount(1, $tenant2Users);
        $this->assertEquals('Tenant 2 User', $tenant2Users->first()->name);
    }

    /** @test */
    public function it_isolates_lead_data_between_tenants()
    {
        // Create leads in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        Lead::create([
            'name' => 'Lead 1 Tenant 1',
            'email' => 'lead1@tenant1.com',
            'phone' => '1234567890',
            'status' => 'new'
        ]);
        Lead::create([
            'name' => 'Lead 2 Tenant 1',
            'email' => 'lead2@tenant1.com',
            'phone' => '1234567891',
            'status' => 'qualified'
        ]);
        
        // Create leads in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        Lead::create([
            'name' => 'Lead 1 Tenant 2',
            'email' => 'lead1@tenant2.com',
            'phone' => '2234567890',
            'status' => 'new'
        ]);
        
        // Verify tenant 1 isolation
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $tenant1Leads = Lead::all();
        $this->assertCount(2, $tenant1Leads);
        $this->assertTrue($tenant1Leads->contains('email', 'lead1@tenant1.com'));
        $this->assertTrue($tenant1Leads->contains('email', 'lead2@tenant1.com'));
        $this->assertFalse($tenant1Leads->contains('email', 'lead1@tenant2.com'));
        
        // Verify tenant 2 isolation
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $tenant2Leads = Lead::all();
        $this->assertCount(1, $tenant2Leads);
        $this->assertTrue($tenant2Leads->contains('email', 'lead1@tenant2.com'));
        $this->assertFalse($tenant2Leads->contains('email', 'lead1@tenant1.com'));
    }

    /** @test */
    public function it_isolates_landing_page_data_between_tenants()
    {
        // Create landing pages in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        LandingPage::create([
            'title' => 'Tenant 1 Page',
            'slug' => 'tenant-1-page',
            'content' => 'Content for tenant 1',
            'status' => 'published'
        ]);
        
        // Create landing pages in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        LandingPage::create([
            'title' => 'Tenant 2 Page',
            'slug' => 'tenant-2-page',
            'content' => 'Content for tenant 2',
            'status' => 'published'
        ]);
        
        // Verify tenant 1 isolation
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $tenant1Pages = LandingPage::all();
        $this->assertCount(1, $tenant1Pages);
        $this->assertEquals('Tenant 1 Page', $tenant1Pages->first()->title);
        
        // Verify tenant 2 isolation
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $tenant2Pages = LandingPage::all();
        $this->assertCount(1, $tenant2Pages);
        $this->assertEquals('Tenant 2 Page', $tenant2Pages->first()->title);
    }

    /** @test */
    public function it_handles_complex_queries_within_tenant_context()
    {
        // Setup data in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@tenant1.com',
            'password' => bcrypt('password')
        ]);
        
        Lead::create([
            'name' => 'Lead 1',
            'email' => 'lead1@tenant1.com',
            'phone' => '1234567890',
            'status' => 'qualified',
            'user_id' => $user1->id
        ]);
        
        // Setup data in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@tenant2.com',
            'password' => bcrypt('password')
        ]);
        
        Lead::create([
            'name' => 'Lead 2',
            'email' => 'lead2@tenant2.com',
            'phone' => '2234567890',
            'status' => 'new',
            'user_id' => $user2->id
        ]);
        
        // Test complex query in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $qualifiedLeads = Lead::where('status', 'qualified')
            ->with('user')
            ->get();
        
        $this->assertCount(1, $qualifiedLeads);
        $this->assertEquals('Lead 1', $qualifiedLeads->first()->name);
        $this->assertEquals('User 1', $qualifiedLeads->first()->user->name);
        
        // Test complex query in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $newLeads = Lead::where('status', 'new')
            ->with('user')
            ->get();
        
        $this->assertCount(1, $newLeads);
        $this->assertEquals('Lead 2', $newLeads->first()->name);
        $this->assertEquals('User 2', $newLeads->first()->user->name);
    }

    /** @test */
    public function it_prevents_cross_tenant_data_access()
    {
        // Create data in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@tenant1.com',
            'password' => bcrypt('password')
        ]);
        $user1Id = $user1->id;
        
        // Switch to tenant 2 and try to access tenant 1 data
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $foundUser = User::find($user1Id);
        
        // Should not find the user from tenant 1
        $this->assertNull($foundUser);
    }

    private function createTenantSchema(string $tenantId): void
    {
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$tenantId}");
        
        // Run migrations for the tenant schema
        $this->tenantContextService->setCurrentTenant($tenantId);
        Artisan::call('migrate', ['--force' => true]);
    }

    private function dropTenantSchema(string $tenantId): void
    {
        DB::statement("DROP SCHEMA IF EXISTS {$tenantId} CASCADE");
    }
}