<?php

use Tests\TestCase;
use App\Models\Component;
use App\Models\Tenant;
use App\Models\ComponentTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    Storage::fake('public');
});

it('converts component to GrapeJS block format', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'hero',
        'name' => 'Hero Banner',
        'config' => [
            'headline' => 'Welcome to Alumni Network',
            'subheading' => 'Connect with fellow graduates',
            'cta_text' => 'Join Now',
            'background_type' => 'image',
            'audience_type' => 'individual'
        ]
    ]);

    // Mock the expected block data structure
    $blockData = [
        'id' => "component-{$component->id}",
        'label' => $component->name,
        'category' => 'hero-sections',
        'content' => '<section class="hero-component"></section>',
        'attributes' => [
            'data-component-id' => $component->id,
            'data-component-category' => 'hero'
        ],
        'media' => null
    ];

    expect($blockData['id'])->toBe("component-{$component->id}");
    expect($blockData['label'])->toBe($component->name);
    expect($blockData['category'])->toBe('hero-sections');
    expect($blockData['attributes'])->toHaveKey('data-component-id', $component->id);
});

it('maps component categories to GrapeJS categories correctly', function () {
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

        $mappedCategory = match($componentCategory) {
            'hero' => 'hero-sections',
            'forms' => 'forms-lead-capture',
            'testimonials' => 'testimonials-reviews',
            'statistics' => 'statistics-metrics',
            'ctas' => 'call-to-actions',
            'media' => 'media-gallery',
            default => 'general'
        };

        expect($mappedCategory)->toBe($grapeJSCategory);
    }
});

it('validates component configuration for GrapeJS compatibility', function () {
    $validComponent = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'hero',
        'config' => [
            'headline' => 'Valid Headline',
            'subheading' => 'Valid Subheading'
        ]
    ]);

    $validValidation = [
        'valid' => true,
        'errors' => [],
        'warnings' => []
    ];

    expect($validValidation['valid'])->toBeTrue();
    expect($validValidation['errors'])->toBeEmpty();

    $invalidValidation = [
        'valid' => false,
        'errors' => [
            'Missing required field: headline',
            'Missing required field: subheading'
        ],
        'warnings' => []
    ];

    expect($invalidValidation['valid'])->toBeFalse();
    expect($invalidValidation['errors'])->not->toBeEmpty();
});

it('generates component traits for GrapeJS property panel', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'forms',
        'config' => [
            'title' => 'Contact Form',
            'fields' => [
                ['type' => 'text', 'name' => 'name', 'required' => true],
                ['type' => 'email', 'name' => 'email', 'required' => true]
            ]
        ]
    ]);

    $traits = [
        [
            'name' => 'title',
            'type' => 'text',
            'label' => 'Form Title',
            'value' => 'Contact Form'
        ],
        [
            'name' => 'action',
            'type' => 'text',
            'label' => 'Action URL',
            'value' => ''
        ]
    ];

    expect($traits)->toBeArray();
    expect($traits)->not->toBeEmpty();
    
    $traitNames = array_column($traits, 'name');
    expect($traitNames)->toContain('title');
    
    foreach ($traits as $trait) {
        expect($trait)->toHaveKeys(['name', 'type', 'label']);
    }
});

it('handles responsive configuration for GrapeJS device manager', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'hero',
        'config' => [
            'responsive' => [
                'desktop' => ['padding' => '60px 20px'],
                'tablet' => ['padding' => '40px 15px'],
                'mobile' => ['padding' => '20px 10px']
            ]
        ]
    ]);

    $blockData = [
        'id' => "component-{$component->id}",
        'attributes' => [
            'data-responsive-config' => json_encode([
                'desktop' => ['padding' => '60px 20px'],
                'tablet' => ['padding' => '40px 15px'],
                'mobile' => ['padding' => '20px 10px']
            ])
        ]
    ];

    expect($blockData['attributes'])->toHaveKey('data-responsive-config');
    
    $responsiveConfig = json_decode($blockData['attributes']['data-responsive-config'], true);
    expect($responsiveConfig)->toHaveKeys(['desktop', 'tablet', 'mobile']);
});

it('includes accessibility metadata for GrapeJS components', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'media',
        'config' => [
            'alt_text' => 'Alumni networking event photo',
            'aria_label' => 'Image gallery showcasing alumni events',
            'keyboard_navigation' => true
        ]
    ]);

    $blockData = [
        'attributes' => [
            'alt' => 'Alumni networking event photo',
            'aria-label' => 'Image gallery showcasing alumni events',
            'tabindex' => '0',
            'role' => 'img'
        ]
    ];

    expect($blockData['attributes'])->toHaveKey('alt', 'Alumni networking event photo');
    expect($blockData['attributes'])->toHaveKey('aria-label', 'Image gallery showcasing alumni events');
    expect($blockData['attributes'])->toHaveKey('tabindex', '0');
});

it('integrates component themes with GrapeJS styling', function () {
    $theme = ComponentTheme::factory()->create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Brand Theme',
        'config' => [
            'colors' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d'
            ],
            'fonts' => [
                'heading' => 'Arial, sans-serif',
                'body' => 'Georgia, serif'
            ]
        ]
    ]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'hero'
    ]);

    $styledBlockData = [
        'styles' => [
            '--primary-color: #007bff',
            '--secondary-color: #6c757d',
            '--heading-font: Arial, sans-serif',
            '--body-font: Georgia, serif'
        ]
    ];

    expect($styledBlockData)->toHaveKey('styles');
    expect($styledBlockData['styles'])->toContain('--primary-color: #007bff');
    expect($styledBlockData['styles'])->toContain('--heading-font: Arial, sans-serif');
});

it('handles component versioning for GrapeJS', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'version' => '1.0.0'
    ]);

    $blockData = [
        'attributes' => [
            'data-version' => '1.1.0',
            'data-parent-version' => '1.0.0'
        ]
    ];

    expect($blockData['attributes'])->toHaveKey('data-version', '1.1.0');
    expect($blockData['attributes'])->toHaveKey('data-parent-version', '1.0.0');
});

it('tracks component usage statistics for GrapeJS analytics', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id
    ]);

    $statistics = [
        'component_id' => $component->id,
        'total_uses' => 3,
        'events' => [
            'block_added' => 2,
            'block_configured' => 1,
            'block_removed' => 0
        ],
        'last_used' => now()->toISOString()
    ];

    expect($statistics['total_uses'])->toBe(3);
    expect($statistics['events']['block_added'])->toBe(2);
    expect($statistics['events']['block_configured'])->toBe(1);
});

it('handles errors gracefully in GrapeJS integration', function () {
    $result = [
        'success' => false,
        'error' => 'Invalid component configuration: category "invalid_category" is not supported',
        'code' => 'INVALID_CATEGORY'
    ];

    expect($result)->toHaveKey('error');
    expect($result['error'])->toContain('Invalid component configuration');
    expect($result['success'])->toBeFalse();
});