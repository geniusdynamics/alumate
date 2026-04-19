<?php

// ABOUTME: Feature tests for tenant isolation to verify complete application behavior with schema-based tenancy
// ABOUTME: Tests cover HTTP requests, middleware, authentication, and end-to-end tenant data isolation scenarios

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lead;
use App\Models\LandingPage;
use App\Services\TenantContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TenantContextService $tenantContextService;
    private string $tenant1 = 'feature_tenant_1';
    private string $tenant2 = 'feature_tenant_2';
    private User $tenant1User;
    private User $tenant2User;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContextService = app(TenantContextService::class);
        
        // Create test tenant schemas
        $this->createTenantSchema($this->tenant1);
        $this->createTenantSchema($this->tenant2);
        
        // Create test users for each tenant
        $this->createTenantUsers();
    }

    protected function tearDown(): void
    {
        // Clean up test schemas
        $this->dropTenantSchema($this->tenant1);
        $this->dropTenantSchema($this->tenant2);
        
        parent::tearDown();
    }

    /** @test */
    public function it_isolates_user_authentication_between_tenants()
    {
        // Test login for tenant 1 user
        $response = $this->withHeaders([
            'X-Tenant-ID' => $this->tenant1
        ])->post('/login', [
            'email' => 'admin@tenant1.com',
            'password' => 'password'
        ]);
        
        $response->assertStatus(302); // Redirect after successful login
        $this->assertAuthenticatedAs($this->tenant1User);
        
        // Logout
        $this->post('/logout');
        
        // Test that tenant 1 user cannot login to tenant 2
        $response = $this->withHeaders([
            'X-Tenant-ID' => $this->tenant2
        ])->post('/login', [
            'email' => 'admin@tenant1.com', // Tenant 1 email
            'password' => 'password'
        ]);
        
        $response->assertStatus(422); // Validation error
        $this->assertGuest();
    }

    /** @test */
    public function it_isolates_lead_management_between_tenants()
    {
        // Create leads in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        Lead::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'status' => 'new'
        ]);
        
        // Create leads in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        Lead::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'status' => 'qualified'
        ]);
        
        // Test tenant 1 can only see its leads
        $response = $this->actingAs($this->tenant1User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant1])
            ->get('/api/leads');
        
        $response->assertStatus(200);
        $leads = $response->json('data');
        $this->assertCount(1, $leads);
        $this->assertEquals('John Doe', $leads[0]['name']);
        
        // Test tenant 2 can only see its leads
        $response = $this->actingAs($this->tenant2User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant2])
            ->get('/api/leads');
        
        $response->assertStatus(200);
        $leads = $response->json('data');
        $this->assertCount(1, $leads);
        $this->assertEquals('Jane Smith', $leads[0]['name']);
    }

    /** @test */
    public function it_isolates_landing_page_management_between_tenants()
    {
        // Create landing pages in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $page1 = LandingPage::create([
            'title' => 'Tenant 1 Homepage',
            'slug' => 'homepage',
            'content' => 'Welcome to Tenant 1',
            'status' => 'published'
        ]);
        
        // Create landing pages in tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $page2 = LandingPage::create([
            'title' => 'Tenant 2 Homepage',
            'slug' => 'homepage',
            'content' => 'Welcome to Tenant 2',
            'status' => 'published'
        ]);
        
        // Test tenant 1 can only access its pages
        $response = $this->actingAs($this->tenant1User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant1])
            ->get('/api/landing-pages');
        
        $response->assertStatus(200);
        $pages = $response->json('data');
        $this->assertCount(1, $pages);
        $this->assertEquals('Tenant 1 Homepage', $pages[0]['title']);
        
        // Test tenant 2 can only access its pages
        $response = $this->actingAs($this->tenant2User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant2])
            ->get('/api/landing-pages');
        
        $response->assertStatus(200);
        $pages = $response->json('data');
        $this->assertCount(1, $pages);
        $this->assertEquals('Tenant 2 Homepage', $pages[0]['title']);
    }

    /** @test */
    public function it_prevents_cross_tenant_resource_access()
    {
        // Create a lead in tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $lead = Lead::create([
            'name' => 'Secret Lead',
            'email' => 'secret@tenant1.com',
            'phone' => '1111111111',
            'status' => 'new'
        ]);
        $leadId = $lead->id;
        
        // Try to access tenant 1's lead from tenant 2
        $response = $this->actingAs($this->tenant2User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant2])
            ->get("/api/leads/{$leadId}");
        
        $response->assertStatus(404); // Should not find the lead
    }

    /** @test */
    public function it_handles_tenant_switching_in_same_session()
    {
        // Create data in both tenants
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        Lead::create([
            'name' => 'Lead T1',
            'email' => 'lead@tenant1.com',
            'phone' => '1111111111',
            'status' => 'new'
        ]);
        
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        Lead::create([
            'name' => 'Lead T2',
            'email' => 'lead@tenant2.com',
            'phone' => '2222222222',
            'status' => 'qualified'
        ]);
        
        // Access tenant 1 data
        $response = $this->actingAs($this->tenant1User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant1])
            ->get('/api/leads');
        
        $response->assertStatus(200);
        $leads = $response->json('data');
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead T1', $leads[0]['name']);
        
        // Switch to tenant 2 in same session
        $response = $this->actingAs($this->tenant2User)
            ->withHeaders(['X-Tenant-ID' => $this->tenant2])
            ->get('/api/leads');
        
        $response->assertStatus(200);
        $leads = $response->json('data');
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead T2', $leads[0]['name']);
    }

    /** @test */
    public function it_validates_tenant_header_requirement()
    {
        // Request without tenant header should fail
        $response = $this->actingAs($this->tenant1User)
            ->get('/api/leads');
        
        $response->assertStatus(400); // Bad request due to missing tenant header
    }

    /** @test */
    public function it_validates_tenant_exists_before_processing_request()
    {
        // Request with non-existent tenant should fail
        $response = $this->actingAs($this->tenant1User)
            ->withHeaders(['X-Tenant-ID' => 'non_existent_tenant'])
            ->get('/api/leads');
        
        $response->assertStatus(404); // Tenant not found
    }

    /** @test */
    public function it_handles_concurrent_tenant_requests()
    {
        // Simulate concurrent requests to different tenants
        $responses = [];
        
        // Create test data
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        Lead::create(['name' => 'Concurrent Lead 1', 'email' => 'c1@t1.com', 'phone' => '1111', 'status' => 'new']);
        
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        Lead::create(['name' => 'Concurrent Lead 2', 'email' => 'c2@t2.com', 'phone' => '2222', 'status' => 'new']);
        
        // Simulate concurrent requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->actingAs($this->tenant1User)
                ->withHeaders(['X-Tenant-ID' => $this->tenant1])
                ->get('/api/leads');
                
            $responses[] = $this->actingAs($this->tenant2User)
                ->withHeaders(['X-Tenant-ID' => $this->tenant2])
                ->get('/api/leads');
        }
        
        // Verify all responses are correct
        foreach ($responses as $index => $response) {
            $response->assertStatus(200);
            $leads = $response->json('data');
            $this->assertCount(1, $leads);
            
            if ($index % 2 === 0) {
                // Tenant 1 requests
                $this->assertEquals('Concurrent Lead 1', $leads[0]['name']);
            } else {
                // Tenant 2 requests
                $this->assertEquals('Concurrent Lead 2', $leads[0]['name']);
            }
        }
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

    private function createTenantUsers(): void
    {
        // Create user for tenant 1
        $this->tenantContextService->setCurrentTenant($this->tenant1);
        $this->tenant1User = User::create([
            'name' => 'Tenant 1 Admin',
            'email' => 'admin@tenant1.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);
        
        // Create user for tenant 2
        $this->tenantContextService->setCurrentTenant($this->tenant2);
        $this->tenant2User = User::create([
            'name' => 'Tenant 2 Admin',
            'email' => 'admin@tenant2.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now()
        ]);
    }
}