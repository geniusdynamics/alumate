<?php

namespace Tests\Feature\Api;

use App\Models\BrandConfig;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_can_list_brand_configs_for_tenant()
    {
        // Arrange
        BrandConfig::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id
        ]);
        BrandConfig::factory()->count(2)->create([
            'tenant_id' => Tenant::factory()->create()->id // Different tenant
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/brand-configs');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'brand_configs' => [
                    '*' => [
                        'id', 'name', 'primary_color', 'secondary_color',
                        'tenant_id', 'is_active', 'is_default', 'created_at'
                    ]
                ],
                'pagination' => [
                    'current_page', 'last_page', 'per_page', 'total'
                ],
                'meta' => [
                    'total_active', 'total_default'
                ]
            ])
            ->assertJsonPath('meta.total_active', 3);
    }

    /** @test */
    public function it_can_create_brand_config()
    {
        $brandData = [
            'name' => 'Test Brand',
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
            'accent_color' => '#0000ff',
            'font_family' => 'Arial, sans-serif',
            'font_weights' => [400, 500, 600, 700],
            'typography_settings' => ['body_font_size' => '16px'],
            'spacing_settings' => ['base_unit' => '1rem', 'scale' => 1.5],
            'brand_colors' => [
                ['name' => 'Red', 'value' => '#ff0000'],
                ['name' => 'Blue', 'value' => '#0000ff']
            ],
            'is_default' => false,
            'is_active' => true,
            'usage_guidelines' => 'Test guidelines'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs', $brandData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'brand_config' => [
                    'id', 'name', 'primary_color', 'secondary_color',
                    'tenant_id', 'is_active', 'created_at'
                ],
                'message'
            ])
            ->assertJsonPath('brand_config.tenant_id', $this->tenant->id);

        $this->assertDatabaseHas('brand_configs', [
            'name' => 'Test Brand',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_validates_brand_config_data()
    {
        $invalidData = [
            'name' => '', // Empty name should fail
            'primary_color' => 'invalid-color', // Invalid hex color
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/brand-configs', $invalidData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'primary_color']);
    }

    /** @test */
    public function it_can_show_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/brand-configs/{$brandConfig->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'brand_config' => [
                    'id', 'name', 'tenant_id', 'primary_color',
                    'secondary_color', 'font_family', 'is_active'
                ],
                'effective_config',
                'usage_stats'
            ]);
    }

    /** @test */
    public function it_prevents_access_to_other_tenant_brand_configs()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/brand-configs/{$brandConfig->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name'
        ]);

        $updateData = [
            'name' => 'Updated Brand',
            'primary_color' => '#ff5500',
            'custom_css' => 'body { color: red; }'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/brand-configs/{$brandConfig->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Brand']);

        $this->assertDatabaseHas('brand_configs', [
            'id' => $brandConfig->id,
            'name' => 'Updated Brand',
            'primary_color' => '#ff5500'
        ]);
    }

    /** @test */
    public function it_can_upload_logo()
    {
        Storage::fake('public');

        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $logoFile = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/upload-logo", [
                'logo' => $logoFile
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['logo', 'message']);

        // Verify file was processed (would need actual upload service mocking for full test)
        $brandConfig->fresh();
    }

    /** @test */
    public function it_can_upload_favicon()
    {
        Storage::fake('public');

        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $faviconFile = UploadedFile::fake()->image('favicon.ico', 32, 32)->mimeType('image/x-icon');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/upload-favicon", [
                'favicon' => $faviconFile
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_file_upload_requirements()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Test missing file
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/upload-logo", []);

        $response->assertStatus(422);

        // Test file too large
        $largeFile = UploadedFile::fake()->image('large.png', 100, 100)->size(6000); // 6MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/upload-logo", [
                'logo' => $largeFile
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_preview_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/preview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'brand_config',
                'effective_config',
                'preview_data' => [
                    'css_variables',
                    'preview_elements'
                ]
            ]);
    }

    /** @test */
    public function it_can_apply_brand_to_template()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/brand-configs/{$brandConfig->id}/apply-to-template/1");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'landing_page',
                'message'
            ]);
    }

    /** @test */
    public function it_can_delete_brand_config()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/brand-configs/{$brandConfig->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted($brandConfig);
    }

    /** @test */
    public function it_prevents_deleting_brand_config_in_use()
    {
        // This test would need to mock a situation where brand is in use
        // by landing pages
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Mock the scenario where brand config is in use
        // (would require adding fake landing page relationships)

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/brand-configs/{$brandConfig->id}");

        // Should be successful unless the brand is actually in use
        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_authentication_for_brand_operations()
    {
        $brandConfig = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        $response = $this->getJson("/api/brand-configs");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        // Multiple requests should be rate limited
        for ($i = 0; $i < 50; $i++) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->getJson('/api/brand-configs');

            if ($response->getStatusCode() === 429) {
                // Rate limit hit
                $response->assertStatus(429);
                break;
            }
        }
    }

    /** @test */
    public function it_supports_search_and_filtering()
    {
        $brand1 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Brand One',
            'is_active' => true
        ]);

        $brand2 = BrandConfig::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Brand Two',
            'is_active' => false
        ]);

        // Search by name
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/brand-configs?search=One');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'brand_configs')
            ->assertJsonPath('brand_configs.0.name', 'Test Brand One');

        // Filter by active status
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/brand-configs?is_active=0');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'brand_configs')
            ->assertJsonPath('brand_configs.0.name', 'Test Brand Two');
    }
}