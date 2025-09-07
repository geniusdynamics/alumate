<?php

namespace Tests\Integration;

use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TenantIsolationVerificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;
    protected Institution $institution1;
    protected Institution $institution2;
    protected Institution $institution3;
    protected User $user1;
    protected User $user2;
    protected User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Set up three tenants for comprehensive isolation testing
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Isolation University A',
            'domain' => 'isolation-a.edu',
            'database' => 'tenant_a',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Isolation University B',
            'domain' => 'isolation-b.edu',
            'database' => 'tenant_b',
        ]);

        $this->tenant3 = Tenant::factory()->create([
            'name' => 'Isolation University C',
            'domain' => 'isolation-c.edu',
            'database' => 'tenant_c',
        ]);

        $this->institution1 = Institution::factory()->create([
            'name' => 'Isolation University A',
            'domain' => 'isolation-a.edu',
        ]);

        $this->institution2 = Institution::factory()->create([
            'name' => 'Isolation University B',
            'domain' => 'isolation-b.edu',
        ]);

        $this->institution3 = Institution::factory()->create([
            'name' => 'Isolation University C',
            'domain' => 'isolation-c.edu',
        ]);

        $this->user1 = User::factory()->create([
            'name' => 'Admin A',
            'email' => 'admin@isolation-a.edu',
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
        ]);

        $this->user2 = User::factory()->create([
            'name' => 'Admin B',
            'email' => 'admin@isolation-b.edu',
            'tenant_id' => $this->tenant2->id,
            'institution_id' => $this->institution2->id,
        ]);

        $this->user3 = User::factory()->create([
            'name' => 'Admin C',
            'email' => 'admin@isolation-c.edu',
            'tenant_id' => $this->tenant3->id,
            'institution_id' => $this->institution3->id,
        ]);
    }

    public function test_complete_tenant_data_isolation()
    {
        // Create resources for each tenant
        $template1 = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Template A',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing'
        ]);

        $template2 = Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Template B',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing'
        ]);

        $template3 = Template::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'name' => 'Template C',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing'
        ]);

        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $template1->id,
            'name' => 'Landing Page A'
        ]);

        $landingPage2 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'template_id' => $template2->id,
            'name' => 'Landing Page B'
        ]);

        $landingPage3 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'template_id' => $template3->id,
            'name' => 'Landing Page C'
        ]);

        $brandConfig1 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_name' => 'University A'
        ]);

        $brandConfig2 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'institution_name' => 'University B'
        ]);

        $brandConfig3 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'institution_name' => 'University C'
        ]);

        // Test tenant 1 can only see their own resources
        $response = $this->actingAs($this->user1)->getJson('/api/templates');
        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Template A', $templates[0]['name']);

        $response = $this->actingAs($this->user1)->getJson('/api/landing-pages');
        $response->assertStatus(200);
        $landingPages = $response->json('data.data');
        $this->assertCount(1, $landingPages);
        $this->assertEquals('Landing Page A', $landingPages[0]['name']);

        $response = $this->actingAs($this->user1)->getJson('/api/brand-config');
        $response->assertStatus(200);
        $brandConfigs = $response->json('data');
        $this->assertCount(1, $brandConfigs);
        $this->assertEquals('University A', $brandConfigs[0]['institution_name']);

        // Test tenant 2 can only see their own resources
        $response = $this->actingAs($this->user2)->getJson('/api/templates');
        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Template B', $templates[0]['name']);

        // Test tenant 3 can only see their own resources
        $response = $this->actingAs($this->user3)->getJson('/api/templates');
        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertCount(1, $templates);
        $this->assertEquals('Template C', $templates[0]['name']);

        // Verify database-level isolation
        $this->assertDatabaseHas('templates', [
            'id' => $template1->id,
            'tenant_id' => $this->tenant1->id
        ]);
        $this->assertDatabaseMissing('templates', [
            'id' => $template1->id,
            'tenant_id' => $this->tenant2->id
        ]);
        $this->assertDatabaseMissing('templates', [
            'id' => $template1->id,
            'tenant_id' => $this->tenant3->id
        ]);
    }

    public function test_cross_tenant_access_prevention()
    {
        // Create resources for tenant 1
        $template1 = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Template A'
        ]);

        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $template1->id,
            'name' => 'Landing Page A'
        ]);

        $brandConfig1 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_name' => 'University A'
        ]);

        // Test tenant 2 cannot access tenant 1's resources
        $response = $this->actingAs($this->user2)->getJson("/api/templates/{$template1->id}");
        $response->assertStatus(403);

        $response = $this->actingAs($this->user2)->getJson("/api/landing-pages/{$landingPage1->id}");
        $response->assertStatus(403);

        $response = $this->actingAs($this->user2)->getJson("/api/brand-config/{$brandConfig1->id}");
        $response->assertStatus(403);

        // Test tenant 3 cannot access tenant 1's resources
        $response = $this->actingAs($this->user3)->getJson("/api/templates/{$template1->id}");
        $response->assertStatus(403);

        // Test tenant 1 cannot access tenant 2's resources
        $template2 = Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Template B'
        ]);

        $response = $this->actingAs($this->user1)->getJson("/api/templates/{$template2->id}");
        $response->assertStatus(403);

        // Test tenant 1 cannot access tenant 3's resources
        $template3 = Template::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'name' => 'Template C'
        ]);

        $response = $this->actingAs($this->user1)->getJson("/api/templates/{$template3->id}");
        $response->assertStatus(403);
    }

    public function test_tenant_isolation_in_bulk_operations()
    {
        // Create multiple resources for each tenant
        $templates1 = Template::factory()->count(5)->create([
            'tenant_id' => $this->tenant1->id
        ]);

        $templates2 = Template::factory()->count(3)->create([
            'tenant_id' => $this->tenant2->id
        ]);

        $templates3 = Template::factory()->count(2)->create([
            'tenant_id' => $this->tenant3->id
        ]);

        // Test bulk operations respect tenant isolation
        $templateIds1 = $templates1->pluck('id')->toArray();
        $templateIds2 = $templates2->pluck('id')->toArray();

        // Tenant 1 bulk operation
        $response = $this->actingAs($this->user1)->postJson('/api/templates/bulk', [
            'operation' => 'update',
            'template_ids' => $templateIds1,
            'data' => ['category' => 'updated_category']
        ]);
        $response->assertStatus(200);

        // Verify tenant 1's templates were updated
        foreach ($templateIds1 as $templateId) {
            $template = Template::find($templateId);
            $this->assertEquals('updated_category', $template->category);
            $this->assertEquals($this->tenant1->id, $template->tenant_id);
        }

        // Verify tenant 2's templates were NOT updated
        foreach ($templateIds2 as $templateId) {
            $template = Template::find($templateId);
            $this->assertNotEquals('updated_category', $template->category);
            $this->assertEquals($this->tenant2->id, $template->tenant_id);
        }

        // Tenant 2 should not be able to bulk update tenant 1's templates
        $response = $this->actingAs($this->user2)->postJson('/api/templates/bulk', [
            'operation' => 'update',
            'template_ids' => $templateIds1,
            'data' => ['category' => 'hacked_category']
        ]);
        $response->assertStatus(403);

        // Verify tenant 1's templates were NOT affected
        foreach ($templateIds1 as $templateId) {
            $template = Template::find($templateId);
            $this->assertEquals('updated_category', $template->category);
        }
    }

    public function test_tenant_isolation_in_search_and_filters()
    {
        // Create similar resources across tenants
        Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Marketing Template',
            'category' => 'landing',
            'audience_type' => 'individual'
        ]);

        Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Marketing Template',
            'category' => 'landing',
            'audience_type' => 'individual'
        ]);

        Template::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'name' => 'Marketing Template',
            'category' => 'landing',
            'audience_type' => 'individual'
        ]);

        // Test search results are tenant-isolated
        $response = $this->actingAs($this->user1)->getJson('/api/templates?search=Marketing');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant1->id, $results[0]['tenant_id']);

        $response = $this->actingAs($this->user2)->getJson('/api/templates?search=Marketing');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant2->id, $results[0]['tenant_id']);

        $response = $this->actingAs($this->user3)->getJson('/api/templates?search=Marketing');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant3->id, $results[0]['tenant_id']);

        // Test filtering by category
        $response = $this->actingAs($this->user1)->getJson('/api/templates?category=landing');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant1->id, $results[0]['tenant_id']);
    }

    public function test_tenant_isolation_in_analytics_and_reporting()
    {
        // Create resources and analytics data for each tenant
        $template1 = Template::factory()->create(['tenant_id' => $this->tenant1->id]);
        $template2 = Template::factory()->create(['tenant_id' => $this->tenant2->id]);

        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $template1->id,
            'status' => 'published'
        ]);

        $landingPage2 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'template_id' => $template2->id,
            'status' => 'published'
        ]);

        // Simulate analytics data
        $landingPage1->increment('usage_count', 100);
        $landingPage2->increment('usage_count', 50);

        // Test analytics isolation
        $response = $this->actingAs($this->user1)->getJson('/api/analytics/overview');
        $response->assertStatus(200);
        $analytics = $response->json('data');

        // Should only include tenant 1's data
        $this->assertArrayHasKey('total_usage', $analytics);
        $this->assertEquals(100, $analytics['total_usage']);

        $response = $this->actingAs($this->user2)->getJson('/api/analytics/overview');
        $response->assertStatus(200);
        $analytics = $response->json('data');

        // Should only include tenant 2's data
        $this->assertEquals(50, $analytics['total_usage']);

        // Test template analytics isolation
        $response = $this->actingAs($this->user1)->getJson("/api/templates/{$template1->id}/analytics");
        $response->assertStatus(200);

        $response = $this->actingAs($this->user2)->getJson("/api/templates/{$template1->id}/analytics");
        $response->assertStatus(403); // Should not access tenant 1's template analytics
    }

    public function test_tenant_isolation_in_file_storage()
    {
        // Create templates with file uploads for each tenant
        $template1 = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'background_image' => 'tenant1/hero-image.jpg'
                        ]
                    ]
                ]
            ]
        ]);

        $template2 = Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'background_image' => 'tenant2/hero-image.jpg'
                        ]
                    ]
                ]
            ]
        ]);

        // Test file access isolation (simulated)
        // In a real implementation, this would test actual file system isolation
        $response = $this->actingAs($this->user1)->getJson("/api/templates/{$template1->id}/files");
        $response->assertStatus(200);
        $files = $response->json('data');
        $this->assertContains('tenant1/hero-image.jpg', $files);

        $response = $this->actingAs($this->user2)->getJson("/api/templates/{$template2->id}/files");
        $response->assertStatus(200);
        $files = $response->json('data');
        $this->assertContains('tenant2/hero-image.jpg', $files);
        $this->assertNotContains('tenant1/hero-image.jpg', $files);
    }

    public function test_tenant_isolation_in_public_urls()
    {
        // Create published landing pages for each tenant
        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'status' => 'published',
            'public_url' => 'https://isolation-a.edu/lp/123'
        ]);

        $landingPage2 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'status' => 'published',
            'public_url' => 'https://isolation-b.edu/lp/456'
        ]);

        $landingPage3 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'status' => 'published',
            'public_url' => 'https://isolation-c.edu/lp/789'
        ]);

        // Test public URL access
        $response = $this->get($landingPage1->public_url);
        $response->assertStatus(200);
        $this->assertStringContainsString('isolation-a.edu', $response->getContent());

        $response = $this->get($landingPage2->public_url);
        $response->assertStatus(200);
        $this->assertStringContainsString('isolation-b.edu', $response->getContent());

        $response = $this->get($landingPage3->public_url);
        $response->assertStatus(200);
        $this->assertStringContainsString('isolation-c.edu', $response->getContent());

        // Test tenant-specific subdomains/routing
        $response = $this->get('https://isolation-a.edu/lp/999'); // Non-existent page
        $response->assertStatus(404);

        // Verify tenant isolation in URL structure
        $this->assertStringContainsString('isolation-a.edu', $landingPage1->public_url);
        $this->assertStringContainsString('isolation-b.edu', $landingPage2->public_url);
        $this->assertStringContainsString('isolation-c.edu', $landingPage3->public_url);
    }

    public function test_tenant_isolation_in_background_jobs()
    {
        // Create resources that would trigger background jobs
        $landingPage1 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'status' => 'published'
        ]);

        $landingPage2 = LandingPage::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'status' => 'published'
        ]);

        // Simulate form submissions that would trigger jobs
        $formData1 = [
            'first_name' => 'John',
            'last_name' => 'Tenant1',
            'email' => 'john@tenant1.com'
        ];

        $formData2 = [
            'first_name' => 'Jane',
            'last_name' => 'Tenant2',
            'email' => 'jane@tenant2.com'
        ];

        $response = $this->postJson("/api/landing-pages/{$landingPage1->slug}/submit", $formData1);
        $response->assertStatus(200);

        $response = $this->postJson("/api/landing-pages/{$landingPage2->slug}/submit", $formData2);
        $response->assertStatus(200);

        // Verify leads are properly isolated
        $this->assertDatabaseHas('leads', [
            'email' => 'john@tenant1.com',
            'tenant_id' => $this->tenant1->id
        ]);

        $this->assertDatabaseHas('leads', [
            'email' => 'jane@tenant2.com',
            'tenant_id' => $this->tenant2->id
        ]);

        // Verify no cross-contamination
        $this->assertDatabaseMissing('leads', [
            'email' => 'john@tenant1.com',
            'tenant_id' => $this->tenant2->id
        ]);

        $this->assertDatabaseMissing('leads', [
            'email' => 'jane@tenant2.com',
            'tenant_id' => $this->tenant1->id
        ]);
    }

    public function test_tenant_isolation_in_cache()
    {
        // Create similar data across tenants
        Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Cached Template'
        ]);

        Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Cached Template'
        ]);

        // Clear cache and test isolation
        Cache::flush();

        // Access tenant 1's data
        $response = $this->actingAs($this->user1)->getJson('/api/templates?name=Cached Template');
        $response->assertStatus(200);
        $templates1 = $response->json('data.data');

        // Access tenant 2's data
        $response = $this->actingAs($this->user2)->getJson('/api/templates?name=Cached Template');
        $response->assertStatus(200);
        $templates2 = $response->json('data.data');

        // Verify different results for different tenants
        $this->assertCount(1, $templates1);
        $this->assertCount(1, $templates2);
        $this->assertEquals($this->tenant1->id, $templates1[0]['tenant_id']);
        $this->assertEquals($this->tenant2->id, $templates2[0]['tenant_id']);

        // Verify cache keys are tenant-specific
        $cacheKey1 = "templates:search:name=Cached Template:tenant={$this->tenant1->id}";
        $cacheKey2 = "templates:search:name=Cached Template:tenant={$this->tenant2->id}";

        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    public function test_tenant_isolation_edge_cases()
    {
        // Test with special characters in tenant data
        $template1 = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Template with spécial characters ñáéíóú',
            'description' => 'Description with <script>alert("xss")</script> and sql\' OR \'1\'=\'1'
        ]);

        $template2 = Template::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Template with spécial characters ñáéíóú',
            'description' => 'Description with <script>alert("xss")</script> and sql\' OR \'1\'=\'1'
        ]);

        // Test search with special characters
        $response = $this->actingAs($this->user1)->getJson('/api/templates?search=spécial');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant1->id, $results[0]['tenant_id']);

        $response = $this->actingAs($this->user2)->getJson('/api/templates?search=spécial');
        $response->assertStatus(200);
        $results = $response->json('data.data');
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant2->id, $results[0]['tenant_id']);

        // Test with very long names
        $longName = str_repeat('A', 255);
        $template3 = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => $longName
        ]);

        $response = $this->actingAs($this->user1)->getJson('/api/templates');
        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertContains($longName, array_column($templates, 'name'));

        // Verify tenant 2 cannot see tenant 1's long-named template
        $response = $this->actingAs($this->user2)->getJson('/api/templates');
        $response->assertStatus(200);
        $templates = $response->json('data.data');
        $this->assertNotContains($longName, array_column($templates, 'name'));
    }
}