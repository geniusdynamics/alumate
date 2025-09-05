<?php

namespace Tests\Feature\Api;

use App\Models\Template;
use App\Models\User;
use App\Models\Tenant;
use App\Models\BrandColor;
use App\Models\BrandFont;
use App\Models\BrandLogo;
use App\Services\TemplatePreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Template Preview Controller Test
 *
 * Comprehensive tests for template preview generation, rendering, and responsive behavior.
 */
class TemplatePreviewControllerTest extends TestCase
{

    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Tenant $tenant;
    protected Template $template;
    protected TemplatePreviewService $previewService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        // Create template with sample structure
        $this->template = Template::factory()->create([
            'tenant_id' => $this->tenant->id,
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'headline' => 'Welcome to Our Platform',
                            'subheading' => 'Build amazing experiences',
                            'cta_text' => 'Get Started',
                        ]
                    ],
                    [
                        'type' => 'form',
                        'config' => [
                            'fields' => [
                                [
                                    'type' => 'text',
                                    'label' => 'Full Name',
                                    'name' => 'full_name',
                                    'required' => true,
                                ],
                                [
                                    'type' => 'email',
                                    'label' => 'Email',
                                    'name' => 'email',
                                    'required' => true,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme_colors' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                ]
            ]
        ]);

        // Set up brand assets
        $this->setupBrandAssets();

        // Authenticate user
        Sanctum::actingAs($this->user);
    }

    /**
     * Set up brand assets for testing
     */
    private function setupBrandAssets(): void
    {
        BrandColor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Primary',
            'value' => '#007bff',
            'type' => 'primary',
        ]);

        BrandColor::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Secondary',
            'value' => '#6c757d',
            'type' => 'secondary',
        ]);

        BrandFont::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Primary Font',
            'family' => 'Inter',
            'type' => 'web',
            'is_primary' => true,
        ]);

        BrandLogo::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Primary Logo',
            'url' => '/storage/brand-assets/' . $this->tenant->id . '/logos/logo.png',
            'is_primary' => true,
        ]);
    }

    /** @test */
    public function it_can_generate_template_preview()
    {
        $response = $this->postJson("/api/templates/{$this->template->id}/preview");

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'template',
                    'preview' => [
                        'template_id',
                        'template_name',
                        'template_category',
                        'responsive_previews',
                        'assets',
                        'structure',
                        'brand_info',
                        'generated_at',
                        'cache_duration',
                        'viewport_options',
                    ],
                ]);

        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['preview']['template_id']);
        $this->assertEquals($this->template->name, $responseData['preview']['template_name']);
        $this->assertArrayHasKey('desktop', $responseData['preview']['responsive_previews']);
        $this->assertArrayHasKey('tablet', $responseData['preview']['responsive_previews']);
        $this->assertArrayHasKey('mobile', $responseData['preview']['responsive_previews']);
    }

    /** @test */
    public function it_can_generate_preview_with_custom_config()
    {
        $customConfig = [
            'hero_title' => 'Custom Title',
            'hero_subtitle' => 'Custom Subtitle',
            'brand_colors' => [
                'primary' => '#ff0000',
            ]
        ];

        $response = $this->postJson("/api/templates/{$this->template->id}/preview", [
            'custom_config' => $customConfig,
            'cache_enabled' => false,
        ]);

        $response->assertSuccessful();
        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['preview']['template_id']);
    }

    /** @test */
    public function it_can_render_template_for_desktop_viewport()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/desktop");

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'template_id',
                    'viewport',
                    'html',
                    'rendered_at',
                ]);

        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['template_id']);
        $this->assertEquals('desktop', $responseData['viewport']);
        $this->assertStringContainsString($this->template->name, $responseData['html']);
        $this->assertStringContainsString('hero-wrapper', $responseData['html']);
    }

    /** @test */
    public function it_can_render_template_for_mobile_viewport()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/mobile");

        $response->assertSuccessful();

        $responseData = $response->json();

        $this->assertEquals('mobile', $responseData['viewport']);
        $this->assertStringContainsString('hero-title', $responseData['html']);
    }

    /** @test */
    public function it_can_render_template_for_tablet_viewport()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/tablet");

        $response->assertSuccessful();

        $responseData = $response->json();

        $this->assertEquals('tablet', $responseData['viewport']);
    }

    /** @test */
    public function it_returns_error_for_invalid_viewport()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/invalid");

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Invalid viewport. Must be one of: desktop, tablet, mobile',
                ]);
    }

    /** @test */
    public function it_can_generate_responsive_preview()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/responsive-preview");

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'template_id',
                    'template_name',
                    'responsive_preview' => [
                        'desktop',
                        'tablet',
                        'mobile',
                    ],
                    'viewports',
                    'generated_at',
                ]);

        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['template_id']);
        $this->assertCount(3, $responseData['responsive_preview']);
        $this->assertEquals(['desktop', 'tablet', 'mobile'], $responseData['viewports']);

        // Check each viewport has required structure
        foreach ($responseData['responsive_preview'] as $viewport => $preview) {
            $this->assertArrayHasKey('viewport', $preview);
            $this->assertArrayHasKey('width', $preview);
            $this->assertArrayHasKey('html', $preview);
            $this->assertArrayHasKey('css', $preview);
            $this->assertArrayHasKey('config', $preview);
        }
    }

    /** @test */
    public function it_can_get_preview_assets()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/preview/assets");

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'template_id',
                    'assets' => [
                        'styles' => [
                            'main_css',
                            'responsive_css',
                            'brand_css',
                        ],
                        'scripts' => [
                            'preview_js',
                            'responsive_js',
                        ],
                        'fonts',
                        'images',
                        'metadata',
                    ],
                    'cache_duration',
                    'expires_at',
                ]);

        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['template_id']);
        $this->assertArrayHasKey('template_images', $responseData['assets']['images']);
        $this->assertArrayHasKey('brand_images', $responseData['assets']['images']);
        $this->assertArrayHasKey('has_brand', $responseData['assets']['metadata']);
        $this->assertArrayHasKey('viewport_options', $responseData['assets']['metadata']);
    }

    /** @test */
    public function it_can_apply_brand_configuration()
    {
        $brandOverrides = [
            'primary_color' => '#ff0000',
            'secondary_color' => '#00ff00',
        ];

        $response = $this->postJson("/api/templates/{$this->template->id}/preview/apply-brand", [
            'custom_config' => [],
            'brand_overrides' => $brandOverrides,
        ]);

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'template_id',
                    'branded_structure',
                    'overwrites_applied',
                    'applied_at',
                ]);

        $responseData = $response->json();

        $this->assertEquals($this->template->id, $responseData['template_id']);
        $this->assertTrue($responseData['overwrites_applied']);
        $this->assertArrayHasKey('sections', $responseData['branded_structure']);
    }

    /** @test */
    public function it_can_get_preview_options()
    {
        $response = $this->getJson('/api/templates/preview-options');

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'viewports',
                    'cache_settings',
                    'supported_features',
                    'asset_types',
                ]);

        $responseData = $response->json();

        $this->assertCount(3, $responseData['viewports']);
        $this->assertArrayHasKey('desktop', $responseData['viewports'][0]);
        $this->assertArrayHasKey('tablet', $responseData['viewports'][1]);
        $this->assertArrayHasKey('mobile', $responseData['viewports'][2]);

        $this->assertArrayHasKey('preview_duration', $responseData['cache_settings']);
        $this->assertArrayHasKey('real_time_previews', $responseData['supported_features']);
        $this->assertArrayHasKey('responsive_design', $responseData['supported_features']);
        $this->assertArrayHasKey('brand_application', $responseData['supported_features']);
        $this->assertArrayHasKey('viewport_switching', $responseData['supported_features']);
    }

    /** @test */
    public function it_returns_error_for_nonexistent_template()
    {
        $response = $this->postJson('/api/templates/99999/preview');

        $response->assertNotFound();
    }

    /** @test */
    public function it_handles_invalid_custom_config()
    {
        $response = $this->postJson("/api/templates/{$this->template->id}/preview", [
            'custom_config' => 'invalid json',
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors',
                ]);
    }

    /** @test */
    public function it_can_clear_preview_cache()
    {
        $response = $this->postJson("/api/templates/{$this->template->id}/preview/clear-cache");

        $response->assertSuccessful()
                ->assertJsonStructure([
                    'message',
                    'template_id',
                    'cleared_at',
                ]);

        $responseData = $response->json();

        $this->assertEquals('Preview cache cleared successfully', $responseData['message']);
        $this->assertEquals($this->template->id, $responseData['template_id']);
    }

    /** @test */
    public function it_includes_brand_information_in_preview()
    {
        $response = $this->postJson("/api/templates/{$this->template->id}/preview");

        $response->assertSuccessful();

        $responseData = $response->json();
        $brandInfo = $responseData['preview']['brand_info'];

        $this->assertArrayHasKey('has_colors', $brandInfo);
        $this->assertArrayHasKey('colors_count', $brandInfo);
        $this->assertArrayHasKey('has_fonts', $brandInfo);
        $this->assertArrayHasKey('fonts_count', $brandInfo);
        $this->assertArrayHasKey('has_logos', $brandInfo);
        $this->assertArrayHasKey('logos_count', $brandInfo);
        $this->assertArrayHasKey('guidelines_applied', $brandInfo);
    }

    /** @test */
    public function it_generates_correct_html_structure_for_hero_section()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/desktop");

        $response->assertSuccessful();

        $html = $response->json()['html'];

        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<html', $html);
        $this->assertStringContainsString('<head>', $html);
        $this->assertStringContainsString('<body>', $html);
        $this->assertStringContainsString('hero-wrapper', $html);
        $this->assertStringContainsString('hero-title', $html);
    }

    /** @test */
    public function it_generates_correct_html_structure_for_form_section()
    {
        $response = $this->getJson("/api/templates/{$this->template->id}/render/desktop");

        $response->assertSuccessful();

        $html = $response->json()['html'];

        $this->assertStringContainsString('form-wrapper', $html);
        $this->assertStringContainsString('form-title', $html);
        $this->assertStringContainsString('form-control', $html);
        $this->assertStringContainsString('btn btn-primary', $html);
    }

    /** @test */
    public function it_handles_mixed_viewport_requests()
    {
        // Cache desktop version
        $this->getJson("/api/templates/{$this->template->id}/render/desktop");

        // Then request responsive preview
        $response = $this->getJson("/api/templates/{$this->template->id}/responsive-preview");

        $response->assertSuccessful();

        $responsiveData = $response->json()['responsive_preview'];

        // Verify different viewports have different configurations
        $this->assertNotEquals(
            $responsiveData['desktop']['config'],
            $responsiveData['mobile']['config']
        );
    }
}