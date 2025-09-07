<?php

namespace Tests\Unit\Services;

use App\Models\BrandConfig;
use App\Models\User;
use App\Models\Tenant;
use App\Services\BrandConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrandConfigServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BrandConfigService $brandService;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brandService = app(BrandConfigService::class);
        $this->tenant = Tenant::factory()->create();
    }

    /** @test */
    public function it_can_create_brand_config()
    {
        $data = [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Brand',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'is_active' => true,
        ];

        $brandConfig = $this->brandService->create($data);

        $this->assertInstanceOf(BrandConfig::class, $brandConfig);
        $this->assertEquals('Test Brand', $brandConfig->name);
        $this->assertEquals($this->tenant->id, $brandConfig->tenant_id);
        $this->assertDatabaseHas('brand_configs', $data);
    }

    /** @test */
    public function it_throws_exception_for_duplicate_brand_name_in_same_tenant()
    {
        // Create first brand config
        $this->brandService->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Existing Brand',
            'primary_color' => '#ff0000',
        ]);

        // Try to create duplicate
        $this->expectException(\App\Exceptions\BrandConfigValidationException::class);

        $this->brandService->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Existing Brand', // Same name
            'primary_color' => '#00ff00',
        ]);
    }

    /** @test */
    public function it_validates_color_formats()
    {
        $this->expectException(\App\Exceptions\BrandConfigValidationException::class);

        $this->brandService->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Brand',
            'primary_color' => 'invalid-color', // Invalid hex
        ]);
    }

    /** @test */
    public function it_requires_tenant_id()
    {
        $this->expectException(\App\Exceptions\BrandConfigValidationException::class);

        $this->brandService->create([
            'name' => 'Test Brand',
            'primary_color' => '#ff0000',
        ]);
    }

    /** @test */
    public function it_can_get_brand_config_by_id()
    {
        $brandConfig = BrandConfig::factory()->create([
            'name' => 'Find Me',
            'tenant_id' => $this->tenant->id
        ]);

        $found = $this->brandService->getById($brandConfig->id);

        $this->assertEquals($brandConfig->id, $found->id);
        $this->assertEquals('Find Me', $found->name);
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_brand_config()
    {
        $this->expectException(\App\Exceptions\BrandConfigNotFoundException::class);

        $this->brandService->getById(999);
    }

    /** @test */
    public function it_can_list_brand_configs_with_filters()
    {
        // Create test data
        BrandConfig::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => true]);
        BrandConfig::factory()->create(['tenant_id' => $this->tenant->id, 'is_active' => false]);
        BrandConfig::factory()->create(['tenant_id' => Tenant::factory()->create()->id, 'is_active' => true]); // Different tenant

        $results = $this->brandService->getAll(['tenant_id' => $this->tenant->id]);

        $this->assertCount(2, $results);
        $this->assertEquals($this->tenant->id, $results[0]->tenant_id);
    }

    /** @test */
    public function it_supports_pagination()
    {
        BrandConfig::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $results = $this->brandService->getAll([
            'tenant_id' => $this->tenant->id,
            'per_page' => 3
        ]);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $results);
        $this->assertCount(3, $results->items());
        $this->assertEquals(5, $results->total());
    }

    /** @test */
    public function it_can_get_default_brand_config()
    {
        $default = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true,
            'is_active' => true
        ]);

        $nonDefault = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false,
            'is_active' => true
        ]);

        $result = $this->brandService->getDefault($this->tenant->id);

        $this->assertEquals($default->id, $result->id);
    }

    /** @test */
    public function it_returns_most_recent_active_if_no_default()
    {
        $newer = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false,
            'is_active' => true
        ]);

        sleep(1); // Make sure timestamps differ

        $older = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false,
            'is_active' => true
        ]);

        $result = $this->brandService->getDefault($this->tenant->id);

        $this->assertEquals($newer->id, $result->id);
    }

    /** @test */
    public function it_can_set_brand_config_as_default()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false
        ]);

        $result = $this->brandService->setAsDefault($brandConfig->id);

        $this->assertTrue($result);

        $brandConfig->fresh();
        $this->assertTrue($brandConfig->is_default);
    }

    /** @test */
    public function it_unsets_other_defaults_when_setting_new_default()
    {
        $existingDefault = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true
        ]);

        $newDefault = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false
        ]);

        $this->brandService->setAsDefault($newDefault->id);

        $existingDefault->fresh();
        $newDefault->fresh();

        $this->assertFalse($existingDefault->is_default);
        $this->assertTrue($newDefault->is_default);
    }

    /** @test */
    public function it_can_duplicate_brand_config()
    {
        $original = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Brand',
            'primary_color' => '#ff0000',
            'is_default' => true
        ]);

        $duplicate = $this->brandService->duplicate($original->id, [
            'name' => 'Duplicated Brand'
        ]);

        $this->assertEquals('Duplicated Brand', $duplicate->name);
        $this->assertEquals('#ff0000', $duplicate->primary_color);
        $this->assertFalse($duplicate->is_default); // Should reset default flag
        $this->assertEquals($this->tenant->id, $duplicate->tenant_id);
    }

    /** @test */
    public function it_clears_cache_when_creating_brand_config()
    {
        Cache::shouldReceive('tags->forget')
            ->once()
            ->with("tenant.{$this->tenant->id}.configs");

        $this->brandService->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Brand',
            'primary_color' => '#ff0000',
        ]);
    }

    /** @test */
    public function it_caches_brand_config_queries()
    {
        BrandConfig::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);

        // First call should cache
        $results1 = $this->brandService->getAll(['tenant_id' => $this->tenant->id]);

        // Second call should use cache
        $results2 = $this->brandService->getAll(['tenant_id' => $this->tenant->id]);

        $this->assertEquals($results1->pluck('id'), $results2->pluck('id'));
    }

    /** @test */
    public function it_can_delete_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $result = $this->brandService->delete($brandConfig->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted($brandConfig);
    }

    /** @test */
    public function it_prevents_deleting_default_brand_without_alternatives()
    {
        $defaultBrand = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true
        ]);

        $this->expectException(\App\Exceptions\BrandConfigDeletionException::class);

        $this->brandService->delete($defaultBrand->id);
    }

    /** @test */
    public function it_sets_alternative_as_default_when_deleting_current_default()
    {
        $defaultBrand = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true
        ]);

        $alternative = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false
        ]);

        $this->brandService->delete($defaultBrand->id);

        $alternative->fresh();
        $this->assertTrue($alternative->is_default);
        $this->assertSoftDeleted($defaultBrand);
    }

    /** @test */
    public function it_can_generate_preview_data()
    {
        $brandData = [
            'colors' => [
                'primary' => '#ff0000',
                'secondary' => '#00ff00'
            ],
            'typography' => [
                'font_family' => 'Arial, sans-serif',
                'font_weights' => [400, 600, 700]
            ],
            'logo' => [
                'url' => 'https://example.com/logo.png'
            ]
        ];

        $preview = $this->brandService->generatePreview($brandData);

        $this->assertEquals('#ff0000', $preview['colors']['primary']);
        $this->assertStringContains(':root {', $preview['css_variables']);
        $this->assertEquals('Arial, sans-serif', $preview['typography']['font_family']);
    }

    /** @test */
    public function it_can_export_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Export Test',
            'primary_color' => '#3366cc'
        ]);

        $export = $this->brandService->export($brandConfig->id);

        $this->assertArrayHasKey('brand_config', $export);
        $this->assertArrayHasKey('usage_stats', $export);
        $this->assertArrayHasKey('export timestamp', $export);
        $this->assertEquals('Export Test', $export['brand_config']['name']);
    }

    /** @test */
    public function it_can_import_brand_config()
    {
        $importData = [
            'brand_config' => [
                'name' => 'Imported Brand',
                'primary_color' => '#ff6600',
                'secondary_color' => '#0066ff',
                'is_active' => true
            ]
        ];

        $imported = $this->brandService->import($importData, [
            'tenant_id' => $this->tenant->id
        ]);

        $this->assertInstanceOf(BrandConfig::class, $imported);
        $this->assertEquals('Imported Brand', $imported->name);
        $this->assertEquals($this->tenant->id, $imported->tenant_id);
        $this->assertFalse((bool) $imported->is_default); // Should reset default flag
    }

    /** @test */
    public function it_validates_import_data_structure()
    {
        $this->expectException(\App\Exceptions\BrandConfigValidationException::class);

        $this->brandService->import(['invalid_structure' => []]);
    }

    /** @test */
    public function it_applies_brand_config_to_content()
    {
        $content = ['title' => 'Test Page', 'body' => 'Test content'];
        $brandConfig = [
            'colors' => ['primary' => '#ff0000'],
            'typography' => ['font_family' => 'Arial']
        ];

        $result = $this->brandService->applyBrandToContent($content, $brandConfig);

        $this->assertTrue($result['brand_applied']);
        $this->assertEquals($brandConfig, $result['brand_config']);
    }
}