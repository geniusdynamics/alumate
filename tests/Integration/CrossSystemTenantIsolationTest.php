<?php

namespace Tests\Integration;

use App\Models\Component;
use App\Models\ComponentInstance;
use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Models\BrandConfig;
use App\Models\Institution;
use App\Models\CareerPath;
use App\Models\EducationHistory;
use App\Models\Company;
use App\Models\CareerTimeline;
use App\Models\Discussion;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrossSystemTenantIsolationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;
    protected User $user1;
    protected User $user2;
    protected User $user3;
    protected Institution $institution1;
    protected Institution $institution2;
    protected Institution $institution3;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');

        // Create three tenants for comprehensive isolation testing
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Isolation University A',
            'domain' => 'isolation-a.edu',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Isolation University B',
            'domain' => 'isolation-b.edu',
        ]);

        $this->tenant3 = Tenant::factory()->create([
            'name' => 'Isolation University C',
            'domain' => 'isolation-c.edu',
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

        // Create users for each tenant
        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'institution_id' => $this->institution1->id,
            'email' => 'user1@isolation-a.edu',
            'graduation_year' => 2020,
            'is_alumni' => true,
        ]);

        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'institution_id' => $this->institution2->id,
            'email' => 'user2@isolation-b.edu',
            'graduation_year' => 2021,
            'is_alumni' => true,
        ]);

        $this->user3 = User::factory()->create([
            'tenant_id' => $this->tenant3->id,
            'institution_id' => $this->institution3->id,
            'email' => 'user3@isolation-c.edu',
            'graduation_year' => 2019,
            'is_alumni' => true,
        ]);

        // Create test data across all systems for each tenant
        $this->createTenantTestData();
    }

    protected function createTenantTestData()
    {
        // Create Component Library data for each tenant
        foreach ([$this->tenant1, $this->tenant2, $this->tenant3] as $index => $tenant) {
            $user = [$this->user1, $this->user2, $this->user3][$index];
            $institution = [$this->institution1, $this->institution2, $this->institution3][$index];

            // Create components
            Component::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => "Hero Component Tenant " . ($index + 1),
                'category' => 'hero',
                'is_active' => true,
            ]);

            Component::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => "Form Component Tenant " . ($index + 1),
                'category' => 'forms',
                'is_active' => true,
            ]);

            // Create brand config
            BrandConfig::factory()->create([
                'tenant_id' => $tenant->id,
                'institution_name' => $institution->name,
                'primary_color' => '#1a365d',
            ]);

            // Create templates
            $template = Template::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => "Template Tenant " . ($index + 1),
                'category' => 'landing',
            ]);

            // Create landing pages
            LandingPage::factory()->create([
                'tenant_id' => $tenant->id,
                'template_id' => $template->id,
                'name' => "Landing Page Tenant " . ($index + 1),
                'status' => 'published',
            ]);

            // Create company
            $company = Company::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => "Company Tenant " . ($index + 1),
                'industry' => 'Technology',
            ]);

            // Create education and career data
            EducationHistory::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'degree' => 'Bachelor of Science',
                'major' => 'Computer Science',
            ]);

            CareerPath::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'current_company' => $company->id,
                'job_title' => 'Software Engineer',
            ]);

            // Create career timeline
            CareerTimeline::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'title' => "Career Milestone Tenant " . ($index + 1),
                'event_type' => 'career_milestone',
                'is_public' => true,
            ]);

            // Create discussion
            $discussion = Discussion::factory()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'title' => "Discussion Tenant " . ($index + 1),
                'category' => 'career_advice',
                'is_active' => true,
            ]);

            // Create comments
            Comment::factory()->create([
                'tenant_id' => $tenant->id,
                'discussion_id' => $discussion->id,
                'user_id' => $user->id,
                'content' => "Comment from Tenant " . ($index + 1),
            ]);
        }
    }

    public function test_complete_cross_system_tenant_isolation()
    {
        // Test that each tenant can only access their own data across all systems
        foreach ([$this->user1, $this->user2, $this->user3] as $index => $user) {
            $tenant = [$this->tenant1, $this->tenant2, $this->tenant3][$index];
            $tenantNumber = $index + 1;

            // Test Component Library isolation
            $this->testComponentLibraryIsolation($user, $tenantNumber);

            // Test Page Builder isolation
            $this->testPageBuilderIsolation($user, $tenantNumber);

            // Test Graduate Tracking isolation
            $this->testGraduateTrackingIsolation($user, $tenantNumber);

            // Test Alumni Platform isolation
            $this->testAlumniPlatformIsolation($user, $tenantNumber);
        }
    }

    protected function testComponentLibraryIsolation($user, $expectedTenantNumber)
    {
        // Test components
        $response = $this->actingAs($user)
            ->getJson('/api/components');

        $response->assertStatus(200);
        $components = $response->json('data');

        $this->assertCount(2, $components); // 2 components per tenant
        foreach ($components as $component) {
            $this->assertEquals($user->tenant_id, $component['tenant_id']);
            $this->assertStringContains("Tenant {$expectedTenantNumber}", $component['name']);
        }

        // Test GrapeJS blocks
        $response = $this->actingAs($user)
            ->getJson('/api/components/grapejs-blocks');

        $response->assertStatus(200);
        $blocks = $response->json('data');

        $this->assertCount(2, $blocks);
        foreach ($blocks as $block) {
            $this->assertEquals($user->tenant_id, $block['tenant_id']);
        }
    }

    protected function testPageBuilderIsolation($user, $expectedTenantNumber)
    {
        // Test templates
        $response = $this->actingAs($user)
            ->getJson('/api/templates');

        $response->assertStatus(200);
        $templates = $response->json('data');

        $this->assertCount(1, $templates);
        $this->assertEquals($user->tenant_id, $templates[0]['tenant_id']);
        $this->assertStringContains("Tenant {$expectedTenantNumber}", $templates[0]['name']);

        // Test landing pages
        $response = $this->actingAs($user)
            ->getJson('/api/landing-pages');

        $response->assertStatus(200);
        $pages = $response->json('data');

        $this->assertCount(1, $pages);
        $this->assertEquals($user->tenant_id, $pages[0]['tenant_id']);
        $this->assertStringContains("Tenant {$expectedTenantNumber}", $pages[0]['name']);
    }

    protected function testGraduateTrackingIsolation($user, $expectedTenantNumber)
    {
        // Test career paths
        $response = $this->actingAs($user)
            ->getJson('/api/career-paths');

        $response->assertStatus(200);
        $careerPaths = $response->json('data');

        $this->assertCount(1, $careerPaths);
        $this->assertEquals($user->tenant_id, $careerPaths[0]['tenant_id']);
        $this->assertEquals('Software Engineer', $careerPaths[0]['job_title']);

        // Test education history
        $response = $this->actingAs($user)
            ->getJson('/api/education-history');

        $response->assertStatus(200);
        $education = $response->json('data');

        $this->assertCount(1, $education);
        $this->assertEquals($user->tenant_id, $education[0]['tenant_id']);
        $this->assertEquals('Computer Science', $education[0]['major']);
    }

    protected function testAlumniPlatformIsolation($user, $expectedTenantNumber)
    {
        // Test career timeline
        $response = $this->actingAs($user)
            ->getJson('/api/alumni/career-timeline');

        $response->assertStatus(200);
        $timeline = $response->json('data');

        $this->assertCount(1, $timeline);
        $this->assertEquals($user->tenant_id, $timeline[0]['tenant_id']);
        $this->assertStringContains("Tenant {$expectedTenantNumber}", $timeline[0]['title']);

        // Test discussions
        $response = $this->actingAs($user)
            ->getJson('/api/alumni/discussions');

        $response->assertStatus(200);
        $discussions = $response->json('data');

        $this->assertCount(1, $discussions);
        $this->assertEquals($user->tenant_id, $discussions[0]['tenant_id']);
        $this->assertStringContains("Tenant {$expectedTenantNumber}", $discussions[0]['title']);

        // Test social timeline
        $response = $this->actingAs($user)
            ->getJson('/api/alumni/social-timeline');

        $response->assertStatus(200);
        $socialData = $response->json('data');

        // Should contain timeline and discussion data
        $this->assertCount(2, $socialData);
        foreach ($socialData as $item) {
            $this->assertEquals($user->tenant_id, $item['tenant_id']);
        }
    }

    public function test_cross_tenant_api_endpoint_protection()
    {
        // Test that users cannot access other tenants' specific resources
        $otherTenantUser = Component::where('tenant_id', '!=', $this->user1->tenant_id)->first();

        if ($otherTenantUser) {
            // Try to access other tenant's component
            $response = $this->actingAs($this->user1)
                ->getJson("/api/components/{$otherTenantUser->id}");

            $response->assertStatus(403);
        }

        // Test cross-tenant page access
        $otherTenantPage = LandingPage::where('tenant_id', '!=', $this->user1->tenant_id)->first();

        if ($otherTenantPage) {
            $response = $this->actingAs($this->user1)
                ->getJson("/api/pages/{$otherTenantPage->slug}/render");

            $response->assertStatus(403);
        }

        // Test cross-tenant career data access
        $otherTenantCareer = CareerPath::where('tenant_id', '!=', $this->user1->tenant_id)->first();

        if ($otherTenantCareer) {
            $response = $this->actingAs($this->user1)
                ->getJson("/api/career-paths/{$otherTenantCareer->id}");

            $response->assertStatus(403);
        }
    }

    public function test_tenant_isolation_in_bulk_operations()
    {
        // Test bulk operations respect tenant boundaries
        $tenant1ComponentIds = Component::where('tenant_id', $this->tenant1->id)
            ->pluck('id')
            ->toArray();

        $tenant2ComponentIds = Component::where('tenant_id', $this->tenant2->id)
            ->pluck('id')
            ->toArray();

        // User 1 should only be able to bulk update their own components
        $response = $this->actingAs($this->user1)
            ->postJson('/api/components/bulk-update', [
                'component_ids' => $tenant1ComponentIds,
                'updates' => ['is_active' => false]
            ]);

        $response->assertStatus(200);

        // Verify tenant 1 components were updated
        foreach ($tenant1ComponentIds as $componentId) {
            $component = Component::find($componentId);
            $this->assertFalse($component->is_active);
        }

        // Verify tenant 2 components were not affected
        foreach ($tenant2ComponentIds as $componentId) {
            $component = Component::find($componentId);
            $this->assertTrue($component->is_active);
        }

        // User 1 should not be able to bulk update tenant 2 components
        $response = $this->actingAs($this->user1)
            ->postJson('/api/components/bulk-update', [
                'component_ids' => $tenant2ComponentIds,
                'updates' => ['is_active' => false]
            ]);

        $response->assertStatus(403);
    }

    public function test_tenant_isolation_in_search_and_filters()
    {
        // Test search operations are tenant-isolated
        $response = $this->actingAs($this->user1)
            ->getJson('/api/components/search?q=Hero');

        $response->assertStatus(200);
        $results = $response->json('data');

        // Should only return tenant 1's hero component
        $this->assertCount(1, $results);
        $this->assertEquals($this->tenant1->id, $results[0]['tenant_id']);
        $this->assertStringContains('Tenant 1', $results[0]['name']);

        // Test filtering by category
        $response = $this->actingAs($this->user1)
            ->getJson('/api/components?category=forms');

        $response->assertStatus(200);
        $filteredResults = $response->json('data');

        $this->assertCount(1, $filteredResults);
        $this->assertEquals('forms', $filteredResults[0]['category']);
        $this->assertEquals($this->tenant1->id, $filteredResults[0]['tenant_id']);
    }

    public function test_tenant_isolation_in_analytics_aggregation()
    {
        // Test analytics are tenant-specific
        $response = $this->actingAs($this->user1)
            ->getJson('/api/analytics/overview');

        $response->assertStatus(200);
        $analytics = $response->json('data');

        $this->assertArrayHasKey('total_components', $analytics);
        $this->assertArrayHasKey('total_pages', $analytics);
        $this->assertArrayHasKey('total_career_paths', $analytics);
        $this->assertArrayHasKey('tenant_id', $analytics);

        // Verify tenant-specific metrics
        $this->assertEquals($this->tenant1->id, $analytics['tenant_id']);
        $this->assertEquals(2, $analytics['total_components']); // 2 components per tenant
        $this->assertEquals(1, $analytics['total_pages']); // 1 page per tenant
        $this->assertEquals(1, $analytics['total_career_paths']); // 1 career path per tenant
    }

    public function test_database_level_tenant_isolation_verification()
    {
        // Verify database-level tenant isolation by checking direct database queries
        $tenant1Components = Component::where('tenant_id', $this->tenant1->id)->get();
        $tenant2Components = Component::where('tenant_id', $this->tenant2->id)->get();
        $tenant3Components = Component::where('tenant_id', $this->tenant3->id)->get();

        // Each tenant should have their own components
        $this->assertCount(2, $tenant1Components);
        $this->assertCount(2, $tenant2Components);
        $this->assertCount(2, $tenant3Components);

        // Verify no cross-tenant data exists
        $crossTenantCheck1 = Component::where('tenant_id', $this->tenant1->id)
            ->where('name', 'like', '%Tenant 2%')
            ->exists();

        $crossTenantCheck2 = Component::where('tenant_id', $this->tenant2->id)
            ->where('name', 'like', '%Tenant 1%')
            ->exists();

        $this->assertFalse($crossTenantCheck1);
        $this->assertFalse($crossTenantCheck2);

        // Verify all components belong to correct tenants
        foreach ($tenant1Components as $component) {
            $this->assertEquals($this->tenant1->id, $component->tenant_id);
        }

        foreach ($tenant2Components as $component) {
            $this->assertEquals($this->tenant2->id, $component->tenant_id);
        }

        foreach ($tenant3Components as $component) {
            $this->assertEquals($this->tenant3->id, $component->tenant_id);
        }
    }

    public function test_tenant_context_preservation_in_api_calls()
    {
        // Test that tenant context is preserved throughout API call chains
        $response = $this->actingAs($this->user1)
            ->getJson('/api/user/tenant-context');

        $response->assertStatus(200);
        $context = $response->json('data');

        $this->assertEquals($this->tenant1->id, $context['current_tenant_id']);
        $this->assertEquals($this->user1->tenant_id, $context['user_tenant_id']);
        $this->assertEquals($this->tenant1->domain, $context['tenant_domain']);

        // Test context preservation in nested calls
        $response = $this->actingAs($this->user1)
            ->getJson('/api/components/tenant-context-test');

        $response->assertStatus(200);
        $nestedContext = $response->json('data');

        $this->assertEquals($this->tenant1->id, $nestedContext['tenant_context']['tenant_id']);
        $this->assertEquals($this->tenant1->id, $nestedContext['component']['tenant_id']);
    }
}