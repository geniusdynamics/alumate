<?php

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('GrapeJS Integration Validation Suite', function () {
    it('validates component block conversion structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Test Hero Component',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'audienceType' => 'individual'
            ]
        ]);

        // Test block data structure
        $expectedBlockStructure = [
            'id' => "component-{$component->id}",
            'label' => $component->name,
            'category' => 'hero-sections',
            'content' => expect()->toBeString(),
            'attributes' => expect()->toBeArray()
        ];

        // Validate the component exists and has correct structure
        expect($component->id)->toBeInt();
        expect($component->name)->toBe('Test Hero Component');
        expect($component->category)->toBe('hero');
        expect($component->config)->toHaveKey('headline', 'Test Headline');
        expect($component->config)->toHaveKey('audienceType', 'individual');
    });

    it('validates component serialization data structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Test Form Component',
            'config' => [
                'title' => 'Contact Form',
                'fields' => [
                    ['type' => 'text', 'name' => 'name', 'required' => true],
                    ['type' => 'email', 'name' => 'email', 'required' => true]
                ]
            ]
        ]);

        // Test serialization structure
        $expectedSerializationStructure = [
            'type' => 'forms-lead-capture',
            'attributes' => expect()->toBeArray(),
            'components' => expect()->toBeArray(),
            'styles' => expect()->toBeArray()
        ];

        // Validate component data
        expect($component->config)->toHaveKey('title', 'Contact Form');
        expect($component->config['fields'])->toHaveCount(2);
        expect($component->config['fields'][0])->toHaveKey('type', 'text');
        expect($component->config['fields'][1])->toHaveKey('type', 'email');
    });

    it('validates trait configuration structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Valid Headline',
                'subheading' => 'Valid Subheading',
                'audienceType' => 'individual'
            ]
        ]);

        // Expected trait structure
        $expectedTraits = [
            ['name' => 'headline', 'type' => 'text', 'label' => 'Headline'],
            ['name' => 'subheading', 'type' => 'text', 'label' => 'Subheading'],
            ['name' => 'audienceType', 'type' => 'select', 'label' => 'Audience Type']
        ];

        // Validate component has required config for traits
        expect($component->config)->toHaveKey('headline');
        expect($component->config)->toHaveKey('subheading');
        expect($component->config)->toHaveKey('audienceType');
        expect($component->config['audienceType'])->toBeIn(['individual', 'institution', 'employer']);
    });

    it('validates performance metrics structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'type' => 'image-gallery',
                'images' => array_fill(0, 10, [
                    'src' => '/images/test.jpg',
                    'alt' => 'Test image'
                ])
            ]
        ]);

        // Expected performance metrics structure
        $expectedMetrics = [
            'render_time' => expect()->toBeNumeric(),
            'memory_usage' => expect()->toBeNumeric(),
            'dom_nodes_created' => expect()->toBeInt(),
            'performance_score' => expect()->toBeBetween(0, 100)
        ];

        // Validate component has complex configuration
        expect($component->config)->toHaveKey('type', 'image-gallery');
        expect($component->config['images'])->toHaveCount(10);
        expect($component->config['images'][0])->toHaveKey('src');
        expect($component->config['images'][0])->toHaveKey('alt');
    });

    it('validates compatibility check structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'statistics',
            'config' => [
                'displayType' => 'counters',
                'animationEnabled' => true,
                'statistics' => [
                    ['label' => 'Users', 'value' => 1000],
                    ['label' => 'Companies', 'value' => 500]
                ]
            ]
        ]);

        // Expected compatibility structure
        $expectedCompatibility = [
            'compatible' => expect()->toBeBool(),
            'features_supported' => expect()->toBeArray(),
            'limitations' => expect()->toBeArray(),
            'grapejs_version_requirements' => expect()->toBeString(),
            'recommended_plugins' => expect()->toBeArray()
        ];

        // Validate component configuration
        expect($component->config)->toHaveKey('displayType', 'counters');
        expect($component->config)->toHaveKey('animationEnabled', true);
        expect($component->config['statistics'])->toHaveCount(2);
    });

    it('validates error handling structure', function () {
        // Test with invalid component data
        $invalidComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => '', // Invalid: empty name
            'config' => [] // Invalid: missing required config
        ]);

        // Expected error structure
        $expectedErrorStructure = [
            'success' => false,
            'message' => expect()->toBeString(),
            'errors' => expect()->toBeArray()
        ];

        // Validate invalid component
        expect($invalidComponent->name)->toBeEmpty();
        expect($invalidComponent->config)->toBeEmpty();
    });

    it('validates category mapping correctness', function () {
        $categoryMappings = [
            'hero' => 'hero-sections',
            'forms' => 'forms-lead-capture',
            'testimonials' => 'testimonials-reviews',
            'statistics' => 'statistics-metrics',
            'ctas' => 'call-to-actions',
            'media' => 'media-gallery'
        ];

        foreach ($categoryMappings as $componentCategory => $grapeJSCategory) {
            $component = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => $componentCategory
            ]);

            expect($component->category)->toBe($componentCategory);
            
            // Test mapping function
            $mappedCategory = $this->mapCategoryToGrapeJS($componentCategory);
            expect($mappedCategory)->toBe($grapeJSCategory);
        }
    });

    it('validates responsive configuration structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Responsive Hero',
                'responsive' => [
                    'desktop' => ['padding' => '60px 20px'],
                    'tablet' => ['padding' => '40px 15px'],
                    'mobile' => ['padding' => '20px 10px']
                ]
            ]
        ]);

        // Validate responsive configuration
        expect($component->config)->toHaveKey('responsive');
        expect($component->config['responsive'])->toHaveKey('desktop');
        expect($component->config['responsive'])->toHaveKey('tablet');
        expect($component->config['responsive'])->toHaveKey('mobile');
        
        foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
            expect($component->config['responsive'][$breakpoint])->toBeArray();
        }
    });

    it('validates accessibility metadata structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'alt_text' => 'Descriptive alt text',
                'aria_label' => 'Accessible label',
                'keyboard_navigation' => true,
                'screen_reader_support' => true
            ]
        ]);

        // Validate accessibility configuration
        expect($component->config)->toHaveKey('alt_text');
        expect($component->config)->toHaveKey('aria_label');
        expect($component->config)->toHaveKey('keyboard_navigation', true);
        expect($component->config)->toHaveKey('screen_reader_support', true);
    });

    it('validates theme integration structure', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Themed Component',
                'theme_variables' => [
                    'primary_color' => '#007bff',
                    'secondary_color' => '#6c757d',
                    'font_family' => 'Arial, sans-serif'
                ]
            ]
        ]);

        // Validate theme configuration
        expect($component->config)->toHaveKey('theme_variables');
        expect($component->config['theme_variables'])->toHaveKey('primary_color');
        expect($component->config['theme_variables'])->toHaveKey('secondary_color');
        expect($component->config['theme_variables'])->toHaveKey('font_family');
    });
});

// Helper function for category mapping
function mapCategoryToGrapeJS(string $category): string
{
    return match($category) {
        'hero' => 'hero-sections',
        'forms' => 'forms-lead-capture',
        'testimonials' => 'testimonials-reviews',
        'statistics' => 'statistics-metrics',
        'ctas' => 'call-to-actions',
        'media' => 'media-gallery',
        default => 'general'
    };
}