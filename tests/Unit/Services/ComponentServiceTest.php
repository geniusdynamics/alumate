<?php

namespace Tests\Unit\Services;

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComponentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComponentService $componentService;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->componentService = app(ComponentService::class);
        $this->tenant = Tenant::factory()->create();
    }

    /**
     * Test component creation with valid data
     */
    public function test_create_component_with_valid_data()
    {
        $componentData = [
            'name' => 'Test Hero Component',
            'category' => 'hero',
            'type' => 'lead_capture',
            'config' => [
                'headline' => 'Welcome to Our Platform',
                'cta_text' => 'Get Started',
                'cta_url' => 'https://example.com'
            ],
        ];

        $component = $this->componentService->create($componentData, $this->tenant->id);

        $this->assertInstanceOf(Component::class, $component);
        $this->assertEquals($this->tenant->id, $component->tenant_id);
        $this->assertEquals('Test Hero Component', $component->name);
        $this->assertEquals('hero', $component->category);
        $this->assertEquals('lead_capture', $component->type);
        $this->assertArrayHasKey('headline', $component->config);
        $this->assertTrue($component->is_active);
        $this->assertEquals('1.0.0', $component->version);
    }

    /**
     * Test component creation with invalid data throws exception
     */
    public function test_create_component_with_invalid_data_throws_exception()
    {
        $this->expectException(\Exception::class);

        $invalidComponentData = [
            'name' => '', // Invalid: empty name
            'category' => 'invalid_category',
        ];

        $this->componentService->create($invalidComponentData, $this->tenant->id);
    }

    /**
     * Test updating an existing component
     */
    public function test_update_component()
    {
        $component = Component::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'name' => 'Updated Hero Component',
            'description' => 'Updated description',
            'is_active' => false
        ];

        $updatedComponent = $this->componentService->update($component, $updateData);

        $this->assertEquals('Updated Hero Component', $updatedComponent->name);
        $this->assertEquals('Updated description', $updatedComponent->description);
        $this->assertFalse($updatedComponent->is_active);
    }

    /**
     * Test component deletion
     */
    public function test_delete_component()
    {
        $component = Component::factory()->create(['tenant_id' => $this->tenant->id]);

        $result = $this->componentService->delete($component);

        $this->assertTrue($result);
        $this->assertSoftDeleted($component);
    }

    /**
     * Test component duplication
     */
    public function test_duplicate_component()
    {
        $originalComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Component',
            'category' => 'hero'
        ]);

        $modifications = [
            'name' => 'Duplicated Component',
            'is_active' => false
        ];

        $duplicatedComponent = $this->componentService->duplicate($originalComponent, $modifications);

        $this->assertNotEquals($originalComponent->id, $duplicatedComponent->id);
        $this->assertEquals('Duplicated Component', $duplicatedComponent->name);
        $this->assertEquals('hero', $duplicatedComponent->category);
        $this->assertFalse($duplicatedComponent->is_active);
        $this->assertNotNull($duplicatedComponent->slug);
    }

    /**
     * Test component search functionality
     */
    public function test_search_components()
    {
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Hero Component',
            'category' => 'hero',
            'is_active' => true
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Form Component',
            'category' => 'forms',
            'is_active' => true
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive Component',
            'is_active' => false
        ]);

        // Search by name
        $results = $this->componentService->search(['search' => 'Hero'], $this->tenant->id);
        $this->assertEquals(1, $results->total());

        // Search by category
        $results = $this->componentService->search(['category' => 'forms'], $this->tenant->id);
        $this->assertEquals(1, $results->total());

        // Search active only
        $results = $this->componentService->search(['is_active' => true], $this->tenant->id);
        $this->assertEquals(2, $results->total());
    }

    /**
     * Test getting components by category
     */
    public function test_get_by_category()
    {
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Hero 1'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Hero 2'
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Form 1'
        ]);

        $heroComponents = $this->componentService->getByCategory('hero', $this->tenant->id);

        $this->assertEquals(2, $heroComponents->count());
        foreach ($heroComponents as $component) {
            $this->assertEquals('hero', $component->category);
            $this->assertStringStartsWith('Hero', $component->name);
        }
    }

    /**
     * Test activating a component
     */
    public function test_activate_component()
    {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false
        ]);

        $activatedComponent = $this->componentService->activate($component);

        $this->assertTrue($activatedComponent->is_active);
    }

    /**
     * Test deactivating a component
     */
    public function test_deactivate_component()
    {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $deactivatedComponent = $this->componentService->deactivate($component);

        $this->assertFalse($deactivatedComponent->is_active);
    }

    /**
     * Test creating new component version
     */
    public function test_create_version()
    {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'version' => '1.0.0'
        ]);

        $newVersion = $this->componentService->createVersion(
            $component,
            '2.0.0',
            ['changed' => 'some_feature']
        );

        $this->assertNotEquals($component->id, $newVersion->id);
        $this->assertEquals('2.0.0', $newVersion->version);
        $this->assertEquals(['changed' => 'some_feature'], $newVersion->config['changes'] ?? []);
    }

    /**
     * Test component configuration validation
     */
    public function test_validate_component_config()
    {
        $validConfig = [
            'category' => 'hero',
            'type' => 'lead_capture',
            'config' => [
                'headline' => 'Valid Headline',
                'cta_text' => 'Valid CTA',
                'cta_url' => 'https://valid-url.com'
            ]
        ];

        $invalidConfig = [
            'category' => 'hero',
            'type' => 'lead_capture',
            'config' => [
                // Missing required headline, cta_text, cta_url
            ]
        ];

        $validResult = $this->componentService->validateConfig($validConfig);
        $invalidResult = $this->componentService->validateConfig($invalidConfig);

        $this->assertTrue($validResult['valid']);
        $this->assertArrayHasKey('errors', $validResult);

        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['errors']);
    }

    /**
     * Test component preview generation
     */
    public function test_generate_preview()
    {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Test Headline',
                'cta_text' => 'Click Me'
            ]
        ]);

        $preview = $this->componentService->generatePreview($component);

        $this->assertArrayHasKey('preview_html', $preview);
        $this->assertArrayHasKey('config_summary', $preview);
        $this->assertStringContains('Test Headline', $preview['preview_html']);
        $this->assertStringContains('Click Me', $preview['preview_html']);
    }

    /**
     * Test component statistics retrieval
     */
    public function test_get_component_stats()
    {
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'usage_count' => 50
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'usage_count' => 30
        ]);

        $stats = $this->componentService->getComponentStats($this->tenant->id);

        $this->assertArrayHasKey('total_components', $stats);
        $this->assertArrayHasKey('components_by_category', $stats);
        $this->assertArrayHasKey('total_usage', $stats);

        $this->assertEquals(2, $stats['total_components']);
        $this->assertEquals(80, $stats['total_usage']);
        $this->assertArrayHasKey('hero', $stats['components_by_category']);
        $this->assertArrayHasKey('forms', $stats['components_by_category']);
    }

    /**
     * Test component export functionality
     */
    public function test_export_components()
    {
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Exportable Hero',
            'category' => 'hero',
            'config' => ['test' => 'config']
        ]);

        $exportData = $this->componentService->exportComponents($this->tenant->id, [], 'json');

        $this->assertArrayHasKey('components', $exportData);
        $this->assertArrayHasKey('exported_at', $exportData);
        $this->assertCount(1, $exportData['components']);

        $component = $exportData['components'][0];
        $this->assertEquals('Exportable Hero', $component['name']);
        $this->assertEquals('hero', $component['category']);
    }

    /**
     * Test template component management
     */
    public function test_template_component_management()
    {
        $templateComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Template Hero',
            'is_template' => true
        ]);

        $regularComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Regular Hero',
            'is_template' => false
        ]);

        $templates = $this->componentService->getTemplates($this->tenant->id);

        $this->assertEquals(1, $templates->total());
        $this->assertEquals('Template Hero', $templates->first()->name);

        // Use template to create new component
        $newComponent = $this->componentService->useTemplate($templateComponent, [
            'name' => 'New From Template'
        ]);

        $this->assertEquals('New From Template', $newComponent->name);
        $this->assertNotEquals($templateComponent->id, $newComponent->id);
    }

    /**
     * Test bulk operations
     */
    public function test_bulk_operations()
    {
        $component1 = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $component2 = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $componentIds = [$component1->id, $component2->id];

        // Test bulk deactivation
        $result = $this->componentService->bulkDeactivate($componentIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated']);

        // Verify components are deactivated
        $this->assertFalse(Component::find($component1->id)->is_active);
        $this->assertFalse(Component::find($component2->id)->is_active);
    }

    /**
     * Test component caching functionality
     */
    public function test_component_caching()
    {
        $component = Component::factory()->create(['tenant_id' => $this->tenant->id]);

        // Cache component
        $cacheKey = "component_{$component->id}";
        $cachedData = [
            'id' => $component->id,
            'name' => $component->name,
            'config_summary' => 'cached'
        ];

        $this->componentService->cacheComponent($component, $cachedData);

        // Retrieve cached component
        $retrievedData = $this->componentService->getCachedComponent($component);

        $this->assertEquals($cachedData, $retrievedData);
    }
}