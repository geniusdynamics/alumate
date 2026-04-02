<?php

namespace Tests\Integration;

use App\Models\Component;
use App\Models\ComponentCollection;
use App\Models\ComponentInstance;
use App\Models\LandingPage;
use App\Models\Tenant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComponentLibraryPageBuilderIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;
    protected Component $heroComponent;
    protected Component $formComponent;
    protected Component $testimonialComponent;
    protected LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('public');

        // Create two tenants for cross-tenant testing
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Component Test University A',
            'domain' => 'component-a.edu',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Component Test University B',
            'domain' => 'component-b.edu',
        ]);

        $this->user1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'admin@component-a.edu',
        ]);

        $this->user2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'admin@component-b.edu',
        ]);

        // Create test components for tenant 1
        $this->heroComponent = Component::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Hero Section',
            'category' => 'hero',
            'is_active' => true,
            'config' => [
                'title' => 'Welcome to {{institution_name}}',
                'subtitle' => 'Your journey starts here',
                'cta_text' => 'Get Started',
                'background_type' => 'gradient'
            ]
        ]);

        $this->formComponent = Component::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Contact Form',
            'category' => 'forms',
            'is_active' => true,
            'config' => [
                'fields' => [
                    ['name' => 'first_name', 'type' => 'text', 'required' => true],
                    ['name' => 'last_name', 'type' => 'text', 'required' => true],
                    ['name' => 'email', 'type' => 'email', 'required' => true],
                    ['name' => 'program_interest', 'type' => 'select', 'required' => false]
                ],
                'submit_button_text' => 'Submit Application'
            ]
        ]);

        $this->testimonialComponent = Component::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Student Testimonials',
            'category' => 'testimonials',
            'is_active' => true,
            'config' => [
                'testimonials' => [
                    [
                        'name' => 'John Doe',
                        'program' => 'Computer Science',
                        'quote' => 'Amazing experience at {{institution_name}}!'
                    ]
                ],
                'display_style' => 'carousel'
            ]
        ]);

        // Create landing page for integration testing
        $template = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Integration Test Template',
            'category' => 'landing',
        ]);

        $this->landingPage = LandingPage::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'template_id' => $template->id,
            'name' => 'Component Integration Test Page',
            'status' => 'draft',
        ]);
    }

    public function test_component_library_to_grapejs_blocks_conversion()
    {
        // Test individual component conversion to GrapeJS blocks
        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$this->heroComponent->id}/grapejs-block");

        $response->assertStatus(200);
        $blockData = $response->json('data');

        $this->assertEquals($this->heroComponent->name, $blockData['name']);
        $this->assertEquals($this->heroComponent->category, $blockData['category']);
        $this->assertArrayHasKey('html', $blockData);
        $this->assertArrayHasKey('traits', $blockData);
        $this->assertArrayHasKey('component', $blockData);

        // Test bulk component conversion
        $componentIds = [$this->heroComponent->id, $this->formComponent->id];
        $response = $this->actingAs($this->user1)
            ->postJson('/api/components/bulk-grapejs-blocks', [
                'component_ids' => $componentIds
            ]);

        $response->assertStatus(200);
        $bulkBlocks = $response->json('data');

        $this->assertCount(2, $bulkBlocks);
        $this->assertEquals($this->heroComponent->name, $bulkBlocks[0]['name']);
        $this->assertEquals($this->formComponent->name, $bulkBlocks[1]['name']);
    }

    public function test_component_collection_grapejs_integration()
    {
        // Create component collection
        $collection = ComponentCollection::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Marketing Components Collection',
            'description' => 'Collection of marketing-focused components',
        ]);

        // Add components to collection
        $collection->components()->attach([
            $this->heroComponent->id,
            $this->formComponent->id,
            $this->testimonialComponent->id
        ]);

        // Test collection to GrapeJS blocks conversion
        $response = $this->actingAs($this->user1)
            ->getJson("/api/component-collections/{$collection->id}/grapejs-blocks");

        $response->assertStatus(200);
        $collectionBlocks = $response->json('data');

        $this->assertCount(3, $collectionBlocks);
        $this->assertEquals('Marketing Components Collection', $collectionBlocks['collection_name']);

        // Verify each component block is included
        $blockNames = array_column($collectionBlocks['blocks'], 'name');
        $this->assertContains($this->heroComponent->name, $blockNames);
        $this->assertContains($this->formComponent->name, $blockNames);
        $this->assertContains($this->testimonialComponent->name, $blockNames);
    }

    public function test_component_instance_grapejs_rendering()
    {
        // Create component instance with custom configuration
        $instance = ComponentInstance::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'component_id' => $this->heroComponent->id,
            'name' => 'Custom Hero Instance',
            'custom_config' => [
                'title' => 'Custom Welcome Message',
                'background_type' => 'image',
                'image_url' => 'https://example.com/hero-bg.jpg'
            ]
        ]);

        // Test instance rendering in GrapeJS context
        $response = $this->actingAs($this->user1)
            ->getJson("/api/component-instances/{$instance->id}/grapejs-render");

        $response->assertStatus(200);
        $renderData = $response->json('data');

        $this->assertArrayHasKey('html', $renderData);
        $this->assertArrayHasKey('css', $renderData);
        $this->assertArrayHasKey('javascript', $renderData);
        $this->assertStringContainsString('Custom Welcome Message', $renderData['html']);
        $this->assertStringContainsString('hero-bg.jpg', $renderData['html']);
    }

    public function test_grapejs_template_component_integration()
    {
        // Test component integration within template structure
        $templateStructure = [
            'sections' => [
                [
                    'type' => 'component_block',
                    'component_id' => $this->heroComponent->id,
                    'position' => 1,
                    'config' => ['title' => 'Template Integrated Hero']
                ],
                [
                    'type' => 'component_block',
                    'component_id' => $this->formComponent->id,
                    'position' => 2,
                    'config' => ['submit_button_text' => 'Template Submit']
                ]
            ]
        ];

        $template = Template::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Component Integrated Template',
            'structure' => $templateStructure,
        ]);

        // Test template to GrapeJS conversion with components
        $response = $this->actingAs($this->user1)
            ->getJson("/api/templates/{$template->id}/grapejs-export");

        $response->assertStatus(200);
        $exportData = $response->json('data');

        $this->assertArrayHasKey('grapejs_data', $exportData);
        $this->assertArrayHasKey('components', $exportData);
        $this->assertCount(2, $exportData['components']);
        $this->assertStringContainsString('Template Integrated Hero', $exportData['grapejs_data']);
    }

    public function test_cross_tenant_component_isolation()
    {
        // Create component in tenant 2
        $tenant2Component = Component::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Tenant 2 Exclusive Component',
            'category' => 'hero',
        ]);

        // Test tenant 1 cannot access tenant 2's components
        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$tenant2Component->id}/grapejs-block");

        $response->assertStatus(403);

        // Test tenant 2 cannot access tenant 1's components
        $response = $this->actingAs($this->user2)
            ->getJson("/api/components/{$this->heroComponent->id}/grapejs-block");

        $response->assertStatus(403);

        // Test that each tenant only sees their own components in bulk operations
        $response = $this->actingAs($this->user1)
            ->getJson('/api/components/grapejs-blocks');

        $response->assertStatus(200);
        $tenant1Blocks = $response->json('data');

        $response = $this->actingAs($this->user2)
            ->getJson('/api/components/grapejs-blocks');

        $response->assertStatus(200);
        $tenant2Blocks = $response->json('data');

        // Each tenant should only see their own components
        $this->assertCount(3, $tenant1Blocks); // hero, form, testimonial
        $this->assertCount(1, $tenant2Blocks); // tenant2Component

        // Verify no cross-contamination
        $tenant1BlockNames = array_column($tenant1Blocks, 'name');
        $tenant2BlockNames = array_column($tenant2Blocks, 'name');

        $this->assertNotContains('Tenant 2 Exclusive Component', $tenant1BlockNames);
        $this->assertNotContains($this->heroComponent->name, $tenant2BlockNames);
    }

    public function test_component_grapejs_trait_configuration()
    {
        // Test component trait configuration for GrapeJS
        $componentWithTraits = Component::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Configurable Component',
            'category' => 'hero',
            'config' => [
                'title' => 'Configurable Title',
                'background_type' => 'color',
                'primary_color' => '#1a365d',
                'show_cta' => true,
                'animation_duration' => 1000
            ]
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$componentWithTraits->id}/grapejs-traits");

        $response->assertStatus(200);
        $traits = $response->json('data');

        $this->assertIsArray($traits);
        $this->assertGreaterThan(0, count($traits));

        // Verify trait types are correctly mapped
        $traitTypes = array_column($traits, 'type');
        $this->assertContains('text', $traitTypes);
        $this->assertContains('color', $traitTypes);
        $this->assertContains('checkbox', $traitTypes);
        $this->assertContains('number', $traitTypes);
    }

    public function test_component_grapejs_compatibility_matrix()
    {
        // Test component compatibility with different GrapeJS versions
        $response = $this->actingAs($this->user1)
            ->getJson('/api/components/grapejs-compatibility');

        $response->assertStatus(200);
        $compatibilityData = $response->json('data');

        $this->assertArrayHasKey('supported_versions', $compatibilityData);
        $this->assertArrayHasKey('compatibility_matrix', $compatibilityData);
        $this->assertArrayHasKey('recommended_version', $compatibilityData);

        // Verify compatibility matrix structure
        $this->assertIsArray($compatibilityData['compatibility_matrix']);
        $this->assertGreaterThan(0, count($compatibilityData['compatibility_matrix']));

        // Test individual component compatibility
        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$this->heroComponent->id}/grapejs-compatibility");

        $response->assertStatus(200);
        $componentCompatibility = $response->json('data');

        $this->assertArrayHasKey('component_id', $componentCompatibility);
        $this->assertArrayHasKey('compatible_versions', $componentCompatibility);
        $this->assertArrayHasKey('compatibility_score', $componentCompatibility);
        $this->assertEquals($this->heroComponent->id, $componentCompatibility['component_id']);
    }

    public function test_component_grapejs_performance_metrics()
    {
        // Test performance metrics for component to GrapeJS conversion
        $startTime = microtime(true);

        $response = $this->actingAs($this->user1)
            ->getJson('/api/components/bulk-grapejs-blocks', [
                'component_ids' => [$this->heroComponent->id, $this->formComponent->id, $this->testimonialComponent->id]
            ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Performance should be reasonable (< 2 seconds for 3 components)
        $this->assertLessThan(2000, $responseTime);

        // Test memory usage tracking (simulated)
        $memoryUsage = memory_get_usage(true);
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage); // Less than 50MB
    }

    public function test_component_grapejs_error_handling()
    {
        // Test error handling for invalid component IDs
        $invalidComponentId = 99999;
        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$invalidComponentId}/grapejs-block");

        $response->assertStatus(404);

        // Test error handling for corrupted component data
        $corruptedComponent = Component::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Corrupted Component',
            'config' => null // Invalid config
        ]);

        $response = $this->actingAs($this->user1)
            ->getJson("/api/components/{$corruptedComponent->id}/grapejs-block");

        // Should handle gracefully and return appropriate error
        $response->assertStatus(422);
        $this->assertArrayHasKey('error', $response->json());
    }
}