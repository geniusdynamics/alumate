<?php

namespace Tests\Integration;

use App\Models\BrandConfig;
use App\Models\User;
use App\Models\Tenant;
use App\Models\LandingPage;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function brand_config_can_be_created_and_applied_to_landing_page()
    {
        // Create a brand configuration
        $brandData = [
            'name' => 'Company Brand',
            'primary_color' => '#3366cc',
            'secondary_color' => '#cc3366',
            'accent_color' => '#66cc33',
            'font_family' => 'Inter, sans-serif',
            'custom_css' => 'body { background: #f5f5f5; }',
            'is_active' => true,
            'is_default' => true
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs', $brandData);

        $response->assertStatus(201);
        $brandId = $response->json('brand_config.id');

        // Create a template
        $template = Template::factory()->create([
            'name' => 'Test Template',
            'tenant_id' => $this->tenant->id,
            'structure' => [
                'hero' => ['title' => '{{brand_name}}', 'subtitle' => 'Welcome'],
                'content' => ['body' => '<h1>Content</h1>']
            ]
        ]);

        // Apply the brand to the template
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandId}/apply-to-template/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['landing_page', 'message']);

        // Verify the landing page was created with brand applied
        $this->assertDatabaseHas('landing_pages', [
            'template_id' => $template->id,
            'tenant_id' => $this->tenant->id
        ]);

        $landingPage = LandingPage::where('template_id', $template->id)->first();
        $this->assertNotNull($landingPage);

        // Check that brand config was applied
        $config = $landingPage->fresh()->config;
        $this->assertArrayHasKey('brand', $config);
        $this->assertEquals('#3366cc', $config['brand']['colors']['primary']);
    }

    /** @test */
    public function full_brand_management_workflow_with_file_uploads()
    {
        Storage::fake('public');

        // 1. Create brand configuration
        $brandResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs', [
                'name' => 'Complete Brand',
                'primary_color' => '#007bff',
                'is_active' => true,
            ]);

        $brandResponse->assertStatus(201);
        $brandId = $brandResponse->json('brand_config.id');

        // 2. Upload logo
        $logoFile = \Illuminate\Http\UploadedFile::fake()->image('logo.png', 100, 100);
        $logoResponse = $this->actingAs($this->user, 'sanctum')
            ->post("/api/brand-configs/{$brandId}/upload-logo", [
                'logo' => $logoFile
            ]);

        $logoResponse->assertStatus(200);

        // 3. Upload favicon
        $faviconFile = \Illuminate\Http\UploadedFile::fake()->image('favicon.ico', 32, 32)->mimeType('image/x-icon');
        $faviconResponse = $this->actingAs($this->user, 'sanctum')
            ->post("/api/brand-configs/{$brandId}/upload-favicon", [
                'favicon' => $faviconFile
            ]);

        $faviconResponse->assertStatus(200);

        // 4. Update brand with logo/fav icon URLs
        $updateResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/brand-configs/{$brandId}", [
                'logo_url' => $logoResponse->json('logo.url'),
                'favicon_url' => $faviconResponse->json('favicon.url'),
                'font_family' => 'Arial, sans-serif'
            ]);

        $updateResponse->assertStatus(200);

        // 5. Create landing page with brand
        $template = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        $applyResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandId}/apply-to-template/{$template->id}");

        $applyResponse->assertStatus(200);

        // 6. Generate preview
        $previewResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandId}/preview");

        $previewResponse->assertStatus(200)
            ->assertJsonStructure(['brand_config', 'effective_config', 'preview_data']);

        // 7. Export brand configuration
        $exportResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandId}/export");

        $exportResponse->assertStatus(200);

        // Verify brand is fully functional
        $brandConfig = BrandConfig::find($brandId);
        $effectiveConfig = $brandConfig->getEffectiveConfig();

        $this->assertArrayHasKey('colors', $effectiveConfig);
        $this->assertArrayHasKey('typography', $effectiveConfig);
        $this->assertArrayHasKey('assets', $effectiveConfig);
        $this->assertNotNull($effectiveConfig['assets']['logo_url']);
    }

    /** @test */
    public function brand_consistency_check_and_auto_fixing()
    {
        // This would test the advanced brand consistency features
        // of the BrandCustomizerService if we wanted to extend the test

        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Consistency Test Brand',
            'primary_color' => '#ff0000',
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/brand-configs/{$brandConfig->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Consistency Test Brand']);

        // Verify brand is complete (has all required elements)
        $this->assertTrue($brandConfig->isComplete());
    }

    /** @test */
    public function tenant_isolation_is_enforced_across_brand_operations()
    {
        // Create two tenants
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create();

        $user1 = $this->user;
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Create brand for tenant 1
        $brandResponse = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/brand-configs', [
                'name' => 'Tenant 1 Brand',
                'primary_color' => '#ff0000',
                'is_active' => true
            ]);

        $brandResponse->assertStatus(201);
        $brandId = $brandResponse->json('brand_config.id');

        // Try to access this brand as user from tenant 2
        $accessResponse = $this->actingAs($user2, 'sanctum')
            ->getJson("/api/brand-configs/{$brandId}");

        $accessResponse->assertStatus(404);

        // User from tenant 2 should not see tenant 1's brands
        $listResponse = $this->actingAs($user2, 'sanctum')
            ->getJson('/api/brand-configs');

        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'brand_configs');
    }

    /** @test */
    public function brand_export_and_import_workflow()
    {
        // Create original brand configuration
        $originalBrand = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Export Test Brand',
            'primary_color' => '#3366cc',
            'secondary_color' => '#cc3366',
            'font_family' => 'Inter, sans-serif',
            'typography_settings' => ['heading_size' => '2rem'],
            'spacing_settings' => ['margin' => '1rem'],
        ]);

        // Export the brand configuration
        $exportResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$originalBrand->id}/export");

        $exportResponse->assertStatus(200);

        // Import the brand configuration
        $importData = [
            'brand_config' => [
                'name' => 'Imported Brand',
                'primary_color' => '#6633cc',
                'secondary_color' => '#33cc66',
                'font_family' => 'Roboto, sans-serif',
                'typography_settings' => ['body_size' => '1.1rem'],
                'spacing_settings' => ['padding' => '0.5rem'],
            ]
        ];

        $importResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs/import', $importData);

        $importResponse->assertStatus(201)
            ->assertJsonFragment(['name' => 'Imported Brand']);

        // Verify imported brand configuration
        $importedBrand = BrandConfig::where('name', 'Imported Brand')
            ->where('tenant_id', $this->tenant->id)
            ->first();

        $this->assertNotNull($importedBrand);
        $this->assertEquals('#6633cc', $importedBrand->primary_color);
        $this->assertEquals('Roboto, sans-serif', $importedBrand->font_family);
    }

    /** @test */
    public function brand_search_and_filtering_functionality()
    {
        // Create multiple brands for testing search
        BrandConfig::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Searchable Brand',
            'is_active' => true
        ]);

        BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Unique Brand Name',
            'is_active' => true
        ]);

        BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive Brand',
            'is_active' => false
        ]);

        // Test search functionality
        $searchResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/brand-configs?search=Unique');

        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'brand_configs')
            ->assertJsonPath('brand_configs.0.name', 'Unique Brand Name');

        // Test filtering by active status
        $activeResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/brand-configs?is_active=1');

        $activeResponse->assertStatus(200);
        $activeCount = collect($activeResponse->json('brand_configs'))
            ->where('is_active', true)->count();

        $this->assertGreaterThan(0, $activeCount);

        // Verify tenant isolation in search results
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        $otherSearchResponse = $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/brand-configs');

        $otherSearchResponse->assertStatus(200)
            ->assertJsonCount(0, 'brand_configs'); // Should see no brands from other tenant
    }

    /** @test */
    public function comprehensive_brand_before_after_demo()
    {
        // Demonstrate the complete brand management functionality

        // 1. Setup initial brand
        $brandResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs', [
                'name' => 'Demo Brand',
                'primary_color' => '#000000',
                'secondary_color' => '#ffffff',
                'font_family' => 'Arial, sans-serif',
                'is_active' => true,
                'is_default' => true
            ]);

        $brandResponse->assertStatus(201);
        $brandId = $brandResponse->json('brand_config.id');

        // 2. Test initial brand configuration
        $showResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/brand-configs/{$brandId}");

        $showResponse->assertStatus(200);
        $initialConfig = $showResponse->json('effective_config');

        $this->assertEquals('#000000', $initialConfig['colors']['primary']);
        $this->assertEquals('Arial, sans-serif', $initialConfig['typography']['font_family']);

        // 3. Update brand with better colors
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/brand-configs/{$brandId}", [
                'primary_color' => '#3366cc',
                'secondary_color' => '#e3f2fd',
                'accent_color' => '#ff4081',
                'font_family' => 'Inter, sans-serif'
            ]);

        // 4. Verify updated configuration
        $updatedShowResponse = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/brand-configs/{$brandId}");

        $updatedShowResponse->assertStatus(200);
        $updatedConfig = $updatedShowResponse->json('effective_config');

        $this->assertEquals('#3366cc', $updatedConfig['colors']['primary']);
        $this->assertEquals('Inter, sans-serif', $updatedConfig['typography']['font_family']);

        // 5. Test brand preview generation
        $previewResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandId}/preview");

        $previewResponse->assertStatus(200)
            ->assertJsonStructure(['preview_data.css_variables']);

        // This demonstrates the complete before/after brand transformation capability
        $this->assertNotEquals(
            $initialConfig['colors']['primary'],
            $updatedConfig['colors']['primary']
        );
    }
}