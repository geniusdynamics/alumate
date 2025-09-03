<?php

namespace Tests\Unit\Models;

use App\Models\Component;
use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test component has correct fillable attributes
     */
    public function test_fillable_attributes()
    {
        $component = new Component();

        $fillable = $component->getFillable();

        // Test critical fillable attributes
        $this->assertContains('tenant_id', $fillable);
        $this->assertContains('theme_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('slug', $fillable);
        $this->assertContains('category', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('version', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('usage_count', $fillable);
    }

    /**
     * Test component has correct cast attributes
     */
    public function test_cast_attributes()
    {
        $component = new Component();

        $casts = $component->getCasts();

        // Test JSON casts
        $this->assertArrayHasKey('config', $casts);
        $this->assertArrayHasKey('metadata', $casts);

        // Test boolean casts
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('is_template', $casts);

        // Test datetime casts
        $this->assertArrayHasKey('last_used_at', $casts);
        $this->assertArrayHasKey('created_at', $casts);
        $this->assertArrayHasKey('updated_at', $casts);
    }

    /**
     * Test component has correct hidden attributes
     */
    public function test_hidden_attributes()
    {
        $component = new Component();

        $hidden = $component->getHidden();

        // Components shouldn't have sensitive hidden fields by default
        $this->assertIsArray($hidden);
    }

    /**
     * Test component belongs to tenant relationship
     */
    public function test_belongs_to_tenant_relationship()
    {
        $tenant = Tenant::factory()->create();
        $component = Component::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertInstanceOf(Tenant::class, $component->tenant);
        $this->assertEquals($tenant->id, $component->tenant->id);
    }

    /**
     * Test component belongs to theme relationship
     */
    public function test_belongs_to_theme_relationship()
    {
        $theme = ComponentTheme::factory()->create();
        $component = Component::factory()->create(['theme_id' => $theme->id]);

        $this->assertInstanceOf(ComponentTheme::class, $component->theme);
        $this->assertEquals($theme->id, $component->theme->id);
    }

    /**
     * Test component has many instances relationship
     */
    public function test_has_many_instances_relationship()
    {
        $component = Component::factory()->create();

        // Create component instance via relationship
        $instance = $component->instances()->create([
            'page_type' => 'landing_page',
            'page_id' => 'homepage',
            'position' => 1
        ]);

        $this->assertEquals(1, $component->instances()->count());
        $this->assertEquals($instance->id, $component->instances()->first()->id);
    }

    /**
     * Test component has many versions relationship
     */
    public function test_has_many_versions_relationship()
    {
        $component = Component::factory()->create();

        // Create component version via relationship
        $version = $component->versions()->create([
            'version' => '2.0.0',
            'changes' => ['Added new feature']
        ]);

        $this->assertEquals(1, $component->versions()->count());
        $this->assertEquals($version->id, $component->versions()->first()->id);
    }

    /**
     * Test for tenant scope filters correctly
     */
    public function test_for_tenant_scope()
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        Component::factory()->create(['tenant_id' => $tenant->id]);
        Component::factory()->create(['tenant_id' => $tenant->id]);
        Component::factory()->create(['tenant_id' => $otherTenant->id]);

        $tenantComponents = Component::forTenant($tenant->id)->get();

        $this->assertCount(2, $tenantComponents);
        foreach ($tenantComponents as $component) {
            $this->assertEquals($tenant->id, $component->tenant_id);
        }
    }

    /**
     * Test active scope
     */
    public function test_active_scope()
    {
        Component::factory()->create(['is_active' => true]);
        Component::factory()->create(['is_active' => false]);

        $activeComponents = Component::active()->get();

        $this->assertCount(1, $activeComponents);
        $this->assertTrue($activeComponents->first()->is_active);
    }

    /**
     * Test by category scope
     */
    public function test_by_category_scope()
    {
        Component::factory()->create(['category' => 'hero']);
        Component::factory()->create(['category' => 'forms']);

        $heroComponents = Component::byCategory('hero')->get();

        $this->assertCount(1, $heroComponents);
        $this->assertEquals('hero', $heroComponents->first()->category);
    }

    /**
     * Test by type scope
     */
    public function test_by_type_scope()
    {
        Component::factory()->create(['type' => 'lead_capture']);
        Component::factory()->create(['type' => 'contact_form']);

        $leadCaptureComponents = Component::byType('lead_capture')->get();

        $this->assertCount(1, $leadCaptureComponents);
        $this->assertEquals('lead_capture', $leadCaptureComponents->first()->type);
    }

    /**
     * Test display name accessor
     */
    public function test_display_name_accessor()
    {
        $component = Component::factory()->create(['name' => 'Hero Section']);

        $this->assertEquals('Hero Section', $component->display_name);
    }

    /**
     * Test formatted config accessor
     */
    public function test_formatted_config_accessor()
    {
        $config = ['headline' => 'Welcome', 'cta_text' => 'Get Started'];
        $component = Component::factory()->create(['config' => $config]);

        $this->assertEquals($config, $component->formatted_config);
    }

    /**
     * Test component can generate preview HTML
     */
    public function test_generate_preview_html_method()
    {
        $config = ['headline' => 'Test Headline'];
        $component = Component::factory()->create([
            'name' => 'Test Component',
            'category' => 'hero',
            'config' => $config
        ]);

        $previewHtml = $component->generatePreviewHtml();

        $this->assertStringContains('Test Component', $previewHtml);
        $this->assertStringContains('hero', $previewHtml);
    }

    /**
     * Test component can generate responsive variants
     */
    public function test_generate_responsive_variants_method()
    {
        $config = ['mobile' => ['hide_cta' => true], 'desktop' => ['show_full_version' => true]];
        $component = Component::factory()->create(['config' => $config]);

        $variants = $component->generateResponsiveVariants();

        $this->assertIsArray($variants);
        $this->assertArrayHasKey('mobile', $variants);
        $this->assertArrayHasKey('tablet', $variants);
        $this->assertArrayHasKey('desktop', $variants);
    }

    /**
     * Test component can check for responsive configuration
     */
    public function test_has_responsive_config_method()
    {
        $configWithResponsive = ['responsive' => ['mobile' => [], 'desktop' => []]];
        $componentWithResponsive = Component::factory()->create(['config' => $configWithResponsive]);

        $configWithoutResponsive = ['other' => 'value'];
        $componentWithoutResponsive = Component::factory()->create(['config' => $configWithoutResponsive]);

        $this->assertTrue($componentWithResponsive->hasResponsiveConfig());
        $this->assertFalse($componentWithoutResponsive->hasResponsiveConfig());
    }

    /**
     * Test component can check accessibility features
     */
    public function test_has_accessibility_features_method()
    {
        $configWithAccessibility = ['accessibility' => ['enable_screen_reader_support' => true]];
        $componentWithAccessibility = Component::factory()->create(['config' => $configWithAccessibility]);

        $configWithoutAccessibility = ['other' => 'value'];
        $componentWithoutAccessibility = Component::factory()->create(['config' => $configWithoutAccessibility]);

        $this->assertTrue($componentWithAccessibility->hasAccessibilityFeatures());
        $this->assertFalse($componentWithoutAccessibility->hasAccessibilityFeatures());
    }

    /**
     * Test component is mobile optimized
     */
    public function test_is_mobile_optimized_method()
    {
        $mobileOptimizedConfig = ['responsive' => ['mobile' => ['optimized_touch' => true]]];
        $mobileOptimizedComponent = Component::factory()->create(['config' => $mobileOptimizedConfig]);

        $nonOptimizedConfig = ['other' => 'value'];
        $nonOptimizedComponent = Component::factory()->create(['config' => $nonOptimizedConfig]);

        $this->assertTrue($mobileOptimizedComponent->isMobileOptimized());
        $this->assertFalse($nonOptimizedComponent->isMobileOptimized());
    }

    /**
     * Test component can get accessibility metadata
     */
    public function test_get_accessibility_metadata_method()
    {
        $config = ['accessibility' => ['enable_screen_reader_support' => true, 'high_contrast_support' => true]];
        $component = Component::factory()->create(['config' => $config]);

        $metadata = $component->getAccessibilityMetadata();

        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('wcag_compliant', $metadata);
        $this->assertArrayHasKey('features', $metadata);
    }

    /**
     * Test component can get usage statistics
     */
    public function test_get_usage_stats_method()
    {
        $component = Component::factory()->create(['usage_count' => 25]);

        $stats = $component->getUsageStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_usage', $stats);
        $this->assertArrayHasKey('last_used_at', $stats);
        $this->assertArrayHasKey('popularity_score', $stats);
        $this->assertEquals(25, $stats['total_usage']);
    }

    /**
     * Test component can validate its configuration
     */
    public function test_validate_config_method()
    {
        // Valid config for hero component
        $validConfig = [
            'headline' => 'Welcome Message',
            'cta_text' => 'Get Started',
            'cta_url' => 'https://example.com'
        ];
        $validComponent = Component::factory()->create([
            'category' => 'hero',
            'config' => $validConfig
        ]);

        // Invalid config for hero component (missing required fields)
        $invalidConfig = ['optional_field' => 'value'];
        $invalidComponent = Component::factory()->create([
            'category' => 'hero',
            'config' => $invalidConfig
        ]);

        $this->assertTrue($validComponent->validateConfig());
        $this->assertFalse($invalidComponent->validateConfig());
    }

    /**
     * Test component can get validation errors
     */
    public function test_get_validation_errors_method()
    {
        $invalidConfig = ['optional_field' => 'value'];
        $invalidComponent = Component::factory()->create([
            'category' => 'hero',
            'config' => $invalidConfig
        ]);

        $validationErrors = $invalidComponent->getValidationErrors();

        $this->assertIsArray($validationErrors);
        // Should contain errors for missing required fields
        $this->assertNotEmpty($validationErrors);
    }

    /**
     * Test component can get Tailwind mappings
     */
    public function test_get_tailwind_mappings_method()
    {
        $component = Component::factory()->create(['category' => 'hero']);

        $tailwindMappings = $component->getTailwindMappings();

        $this->assertIsArray($tailwindMappings);
        $this->assertArrayHasKey('classes', $tailwindMappings);
    }

    /**
     * Test component can get category display name
     */
    public function test_get_category_display_name_method()
    {
        $component = Component::factory()->create(['category' => 'hero']);

        $displayName = $component->getCategoryDisplayName();

        $this->assertEquals('Hero Sections', $displayName);
    }

    /**
     * Test component can get supported features
     */
    public function test_get_supported_features_method()
    {
        $component = Component::factory()->create(['category' => 'hero']);

        $features = $component->getSupportedFeatures();

        $this->assertIsArray($features);
        $this->assertContains('responsive_design', $features);
        $this->assertContains('accessibility', $features);
        $this->assertContains('background_media', $features);
    }

    /**
     * Test component can increment usage count
     */
    public function test_increment_usage_count_method()
    {
        $component = Component::factory()->create(['usage_count' => 10]);

        $component->incrementUsageCount();

        $this->assertEquals(11, $component->fresh()->usage_count);
    }

    /**
     * Test component can update last used time
     */
    public function test_update_last_used_at_method()
    {
        $component = Component::factory()->create(['last_used_at' => null]);

        $component->updateLastUsedAt();

        $this->assertNotNull($component->fresh()->last_used_at);
    }

    /**
     * Test component can duplicate itself
     */
    public function test_duplicate_method()
    {
        $originalComponent = Component::factory()->create([
            'name' => 'Original Hero',
            'category' => 'hero'
        ]);

        $duplicateComponent = $originalComponent->duplicate([
            'name' => 'Duplicated Hero',
            'is_active' => false
        ]);

        $this->assertNotEquals($originalComponent->id, $duplicateComponent->id);
        $this->assertEquals('Duplicated Hero', $duplicateComponent->name);
        $this->assertEquals('hero', $duplicateComponent->category);
        $this->assertFalse($duplicateComponent->is_active);
        $this->assertNotNull($duplicateComponent->slug);
    }
}