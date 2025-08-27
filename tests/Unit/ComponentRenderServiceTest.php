<?php

use App\Models\Component;
use App\Models\ComponentInstance;
use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Services\ComponentRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ComponentRenderService;
    $this->tenant = Tenant::factory()->create();
    $this->theme = ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('ComponentRenderService', function () {
    it('can render a hero component with default configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'type' => 'standard',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
            ],
        ]);

        $result = $this->service->render($component);

        expect($result)->toHaveKeys([
            'id', 'name', 'category', 'type', 'version',
            'config', 'sample_data', 'template', 'responsive_config',
            'accessibility', 'css_variables', 'performance_hints',
        ]);

        expect($result['category'])->toBe('hero');
        expect($result['config']['headline'])->toBe('Test Headline');
        expect($result['sample_data'])->toHaveKey('headline');
        expect($result['template'])->toHaveKey('vue_template');
    });

    it('can render a form component with field configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'type' => 'contact',
            'config' => [
                'fields' => [
                    ['type' => 'text', 'label' => 'Name', 'required' => true],
                    ['type' => 'email', 'label' => 'Email', 'required' => true],
                ],
            ],
        ]);

        $result = $this->service->render($component);

        expect($result['category'])->toBe('forms');
        expect($result['config']['fields'])->toHaveCount(2);
        expect($result['sample_data'])->toHaveKey('fields');
        expect($result['template']['vue_template'])->toContain('form');
    });

    it('can render a testimonial component with layout options', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'type' => 'carousel',
            'config' => [
                'layout' => 'carousel',
                'show_author_photo' => true,
            ],
        ]);

        $result = $this->service->render($component);

        expect($result['category'])->toBe('testimonials');
        expect($result['config']['layout'])->toBe('carousel');
        expect($result['sample_data']['testimonials'])->toBeArray();
        expect($result['template']['vue_template'])->toContain('carousel');
    });

    it('merges configurations in correct priority order', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'theme_id' => $this->theme->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Component Headline',
                'background_type' => 'image',
            ],
        ]);

        $instanceConfig = [
            'headline' => 'Instance Headline', // Should override component config
            'subheading' => 'Instance Subheading', // Should be added
        ];

        $result = $this->service->render($component, $instanceConfig);

        expect($result['config']['headline'])->toBe('Instance Headline');
        expect($result['config']['subheading'])->toBe('Instance Subheading');
        expect($result['config']['background_type'])->toBe('image');
    });

    it('applies theme configuration correctly', function () {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                ],
                'typography' => [
                    'font_family' => 'Arial, sans-serif',
                ],
            ],
        ]);

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'theme_id' => $theme->id,
            'category' => 'hero',
        ]);

        $result = $this->service->render($component);

        expect($result['config'])->toHaveKey('theme_colors');
        expect($result['config']['theme_colors']['primary'])->toBe('#007bff');
        expect($result['css_variables'])->toHaveKey('--component-color-primary');
    });

    it('generates responsive configuration for different breakpoints', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'font_size' => '24px',
                'padding' => '40px',
            ],
        ]);

        $result = $this->service->render($component);

        expect($result['responsive_config'])->toHaveKeys(['mobile', 'tablet', 'desktop']);

        // Mobile should have scaled down values
        $mobileConfig = $result['responsive_config']['mobile'];
        expect($mobileConfig['font_size'])->not->toBe('24px'); // Should be scaled
        expect($mobileConfig['padding'])->not->toBe('40px'); // Should be scaled
    });

    it('generates accessibility attributes correctly', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
        ]);

        $result = $this->service->render($component);

        expect($result['accessibility'])->toHaveKeys([
            'role', 'aria_label', 'semantic_html',
            'keyboard_navigation', 'screen_reader',
        ]);

        expect($result['accessibility']['role'])->toBe('form');
        expect($result['accessibility']['semantic_html'])->toHaveKey('container');
    });

    it('generates TypeScript props interface', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Test',
                'show_statistics' => true,
                'cta_count' => 2,
            ],
        ]);

        $result = $this->service->render($component);

        $tsProps = $result['template']['typescript_props'];

        expect($tsProps)->toContain('interface');
        expect($tsProps)->toContain('headline');
        expect($tsProps)->toContain('string');
        expect($tsProps)->toContain('boolean');
        expect($tsProps)->toContain('number');
    });

    it('caches rendered components for performance', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
        ]);

        // Clear any existing cache
        Cache::flush();

        // First render should not be cached
        $result1 = $this->service->render($component);
        expect($result1)->toHaveKey('cache_key');

        // Second render should use cache
        $result2 = $this->service->render($component);
        expect($result2['cache_key'])->toBe($result1['cache_key']);
    });

    it('can render component instance with custom configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
        ]);

        $instance = ComponentInstance::factory()->create([
            'component_id' => $component->id,
            'custom_config' => [
                'headline' => 'Instance Specific Headline',
            ],
        ]);

        $result = $this->service->renderInstance($instance);

        expect($result['config']['headline'])->toBe('Instance Specific Headline');
    });

    it('handles rendering errors gracefully', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'invalid_category', // This should cause an error
        ]);

        $result = $this->service->render($component);

        expect($result)->toHaveKey('error');
        expect($result['error'])->toBeTrue();
        expect($result)->toHaveKey('error_message');
        expect($result)->toHaveKey('fallback_template');
    });

    it('generates performance hints correctly', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'type' => 'gallery',
                'lazy_load' => true,
            ],
        ]);

        $result = $this->service->render($component);

        $hints = $result['performance_hints'];

        expect($hints)->toHaveKeys([
            'lazy_load', 'preload_resources', 'critical_css',
            'image_optimization', 'caching_strategy',
        ]);

        expect($hints['lazy_load'])->toBeTrue();
        expect($hints['image_optimization'])->toHaveKey('formats');
    });

    it('can clear component cache', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
        ]);

        // Render to create cache
        $this->service->render($component);

        // Clear cache
        $result = $this->service->clearCache($component);
        expect($result)->toBeTrue();
    });

    it('generates sample data appropriate for each category', function () {
        $categories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];

        foreach ($categories as $category) {
            $component = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => $category,
            ]);

            $result = $this->service->render($component);

            expect($result['sample_data'])->not->toBeEmpty();

            // Each category should have appropriate sample data structure
            switch ($category) {
                case 'hero':
                    expect($result['sample_data'])->toHaveKeys(['headline', 'subheading']);
                    break;
                case 'forms':
                    expect($result['sample_data'])->toHaveKey('fields');
                    break;
                case 'testimonials':
                    expect($result['sample_data'])->toHaveKey('testimonials');
                    break;
                case 'statistics':
                    expect($result['sample_data'])->toHaveKey('metrics');
                    break;
            }
        }
    });

    it('generates CSS variables for theme integration', function () {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#ff0000'],
                'typography' => ['font_size' => '16px'],
                'spacing' => ['margin' => '20px'],
            ],
        ]);

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'theme_id' => $theme->id,
            'category' => 'hero',
        ]);

        $result = $this->service->render($component);

        $cssVars = $result['css_variables'];

        expect($cssVars)->toHaveKey('--component-color-primary');
        expect($cssVars['--component-color-primary'])->toBe('#ff0000');
        expect($cssVars)->toHaveKey('--component-font-font_size');
        expect($cssVars)->toHaveKey('--component-spacing-margin');
    });

    it('scales fonts and spacing appropriately for mobile', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'font_size' => '32px',
                'padding' => '60px',
            ],
        ]);

        $result = $this->service->render($component);
        $mobileConfig = $result['responsive_config']['mobile'];

        // Font size should be scaled down but not below minimum
        expect($mobileConfig['font_size'])->toMatch('/\d+px/');

        // Padding should be scaled down but not below minimum
        expect($mobileConfig['padding'])->toMatch('/\d+px/');
    });
});
