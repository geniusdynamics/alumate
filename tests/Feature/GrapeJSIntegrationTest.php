<?php

use App\Models\Component;
use App\Models\ComponentTheme;
use App\Models\ComponentVersion;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    $this->actingAs($this->user);
    
    Storage::fake('public');
    Cache::flush();
});

describe('GrapeJS Integration - Component Block Conversion', function () {
    it('converts hero component to GrapeJS block format correctly', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Alumni Success Hero',
            'description' => 'Hero section showcasing alumni achievements',
            'config' => [
                'headline' => 'Join Our Alumni Network',
                'subheading' => 'Connect with thousands of successful graduates',
                'audienceType' => 'individual',
                'backgroundType' => 'image',
                'ctaButtons' => [
                    ['text' => 'Get Started', 'url' => '/signup', 'style' => 'primary']
                ]
            ],
            'metadata' => [
                'tags' => ['networking', 'career', 'alumni'],
                'difficulty' => 'beginner'
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'block' => [
                        'id',
                        'label',
                        'category',
                        'content',
                        'attributes',
                        'media'
                    ],
                    'traits',
                    'styles'
                ]
            ]);

        $blockData = $response->json('data.block');
        
        expect($blockData['id'])->toBe("component-{$component->id}");
        expect($blockData['label'])->toBe($component->name);
        expect($blockData['category'])->toBe('hero-sections');
        expect($blockData['attributes'])->toHaveKey('data-component-id', $component->id);
        expect($blockData['attributes'])->toHaveKey('data-component-category', 'hero');
        expect($blockData['attributes'])->toHaveKey('data-audience-type', 'individual');
    });

    it('converts form component to GrapeJS block with proper traits', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Alumni Registration Form',
            'config' => [
                'title' => 'Join Our Network',
                'layout' => 'two-column',
                'fields' => [
                    ['type' => 'text', 'name' => 'name', 'label' => 'Full Name', 'required' => true],
                    ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                    ['type' => 'select', 'name' => 'graduation_year', 'label' => 'Graduation Year', 'required' => false]
                ],
                'submitText' => 'Register Now',
                'crmIntegration' => true
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk();
        
        $blockData = $response->json('data.block');
        $traits = $response->json('data.traits');

        expect($blockData['category'])->toBe('forms-lead-capture');
        expect($traits)->toContain([
            'name' => 'title',
            'type' => 'text',
            'label' => 'Form Title',
            'value' => 'Join Our Network'
        ]);
        expect($traits)->toContain([
            'name' => 'layout',
            'type' => 'select',
            'label' => 'Layout',
            'options' => [
                ['id' => 'single-column', 'name' => 'Single Column'],
                ['id' => 'two-column', 'name' => 'Two Column'],
                ['id' => 'grid', 'name' => 'Grid']
            ],
            'value' => 'two-column'
        ]);
    });

    it('converts testimonial component with video support', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'name' => 'Alumni Success Stories',
            'config' => [
                'layout' => 'carousel',
                'showAuthorPhoto' => true,
                'showRating' => true,
                'autoplay' => true,
                'videoSupport' => true,
                'filterOptions' => ['industry', 'graduation_year', 'location']
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk();
        
        $blockData = $response->json('data.block');
        expect($blockData['category'])->toBe('testimonials-reviews');
        expect($blockData['attributes'])->toHaveKey('data-video-support', true);
        expect($blockData['attributes'])->toHaveKey('data-layout', 'carousel');
    });

    it('converts statistics component with animation settings', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'statistics',
            'name' => 'Alumni Network Stats',
            'config' => [
                'displayType' => 'counters',
                'layout' => 'grid',
                'animationEnabled' => true,
                'animationDuration' => 2000,
                'triggerOnScroll' => true,
                'statistics' => [
                    ['label' => 'Alumni', 'value' => 50000, 'suffix' => '+'],
                    ['label' => 'Companies', 'value' => 5000, 'suffix' => '+'],
                    ['label' => 'Success Rate', 'value' => 95, 'suffix' => '%']
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk();
        
        $blockData = $response->json('data.block');
        expect($blockData['category'])->toBe('statistics-metrics');
        expect($blockData['attributes'])->toHaveKey('data-animation-enabled', true);
        expect($blockData['attributes'])->toHaveKey('data-trigger-on-scroll', true);
    });

    it('converts CTA component with tracking parameters', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'name' => 'Join Network CTA',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Join Now',
                    'style' => 'primary',
                    'size' => 'large',
                    'url' => '/signup'
                ],
                'tracking' => [
                    'enabled' => true,
                    'utmSource' => 'alumni_page',
                    'utmMedium' => 'cta_button',
                    'utmCampaign' => 'alumni_recruitment'
                ],
                'abTesting' => [
                    'enabled' => true,
                    'variants' => ['Join Now', 'Get Started', 'Sign Up Today']
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk();
        
        $blockData = $response->json('data.block');
        expect($blockData['category'])->toBe('call-to-actions');
        expect($blockData['attributes'])->toHaveKey('data-tracking-enabled', true);
        expect($blockData['attributes'])->toHaveKey('data-ab-testing', true);
    });

    it('converts media component with optimization settings', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'name' => 'Alumni Gallery',
            'config' => [
                'type' => 'image-gallery',
                'layout' => 'masonry',
                'optimization' => [
                    'lazyLoading' => true,
                    'webpSupport' => true,
                    'responsiveImages' => true
                ],
                'lightbox' => [
                    'enabled' => true,
                    'showCaptions' => true,
                    'showNavigation' => true
                ],
                'accessibility' => [
                    'altTextRequired' => true,
                    'keyboardNavigation' => true
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-block");

        $response->assertOk();
        
        $blockData = $response->json('data.block');
        expect($blockData['category'])->toBe('media-gallery');
        expect($blockData['attributes'])->toHaveKey('data-lazy-loading', true);
        expect($blockData['attributes'])->toHaveKey('data-webp-support', true);
        expect($blockData['attributes'])->toHaveKey('data-keyboard-navigation', true);
    });
});

describe('GrapeJS Integration - Serialization and Deserialization', function () {
    it('serializes component library components to GrapeJS format', function () {
        $components = Component::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/components/serialize-to-grapejs', [
            'component_ids' => $components->pluck('id')->toArray(),
            'include_styles' => true,
            'include_assets' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'components',
                    'styles',
                    'assets',
                    'metadata' => [
                        'serialized_at',
                        'component_count',
                        'format_version'
                    ]
                ]
            ]);

        $serializedData = $response->json('data');
        expect($serializedData['components'])->toHaveCount(3);
        expect($serializedData['metadata']['component_count'])->toBe(3);
        expect($serializedData['metadata']['format_version'])->toBe('1.0.0');
    });

    it('deserializes GrapeJS data to component library format', function () {
        $grapeJSData = [
            'html' => '<section class="hero-component" data-component-id="123">
                        <h1>Welcome Alumni</h1>
                        <p>Join our network</p>
                      </section>',
            'css' => '.hero-component { padding: 60px 20px; background: #f8f9fa; }',
            'components' => [
                [
                    'type' => 'hero-component',
                    'attributes' => [
                        'data-component-id' => '123',
                        'data-component-category' => 'hero'
                    ],
                    'components' => [
                        ['type' => 'text', 'content' => 'Welcome Alumni'],
                        ['type' => 'text', 'content' => 'Join our network']
                    ]
                ]
            ],
            'styles' => [
                [
                    'selectors' => ['.hero-component'],
                    'style' => ['padding' => '60px 20px', 'background' => '#f8f9fa']
                ]
            ]
        ];

        $response = $this->postJson('/api/components/deserialize-from-grapejs', [
            'grapejs_data' => $grapeJSData,
            'create_components' => true,
            'tenant_id' => $this->tenant->id
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'components',
                    'created_count',
                    'warnings'
                ]
            ]);

        $result = $response->json('data');
        expect($result['created_count'])->toBeGreaterThan(0);
        expect($result['components'])->toBeArray();
    });

    it('handles serialization errors gracefully', function () {
        $invalidComponentId = 99999;

        $response = $this->postJson('/api/components/serialize-to-grapejs', [
            'component_ids' => [$invalidComponentId],
            'include_styles' => true
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Some components could not be found'
            ]);
    });

    it('validates GrapeJS data before deserialization', function () {
        $invalidGrapeJSData = [
            'html' => '<div>Invalid structure</div>',
            // Missing required components and styles arrays
        ];

        $response = $this->postJson('/api/components/deserialize-from-grapejs', [
            'grapejs_data' => $invalidGrapeJSData,
            'create_components' => true
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid GrapeJS data format'
            ]);
    });
});

describe('GrapeJS Integration - Trait Configuration Validation', function () {
    it('validates hero component traits configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'audienceType' => 'individual'
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-traits/validate");

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'valid',
                    'traits',
                    'errors',
                    'warnings'
                ]
            ]);

        $validation = $response->json('data');
        expect($validation['valid'])->toBeTrue();
        expect($validation['traits'])->toBeArray();
        expect($validation['errors'])->toBeEmpty();
    });

    it('detects invalid trait configurations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                // Missing required headline
                'subheading' => 'Test Subheading',
                'audienceType' => 'invalid_audience' // Invalid value
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-traits/validate");

        $response->assertOk();
        
        $validation = $response->json('data');
        expect($validation['valid'])->toBeFalse();
        expect($validation['errors'])->not->toBeEmpty();
        expect($validation['errors'])->toContain('Missing required trait: headline');
        expect($validation['errors'])->toContain('Invalid value for audienceType trait');
    });

    it('validates form component field configurations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'config' => [
                'title' => 'Contact Form',
                'fields' => [
                    ['type' => 'text', 'name' => 'name', 'label' => 'Name', 'required' => true],
                    ['type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true],
                    ['type' => 'textarea', 'name' => 'message', 'label' => 'Message', 'required' => false]
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-traits/validate");

        $response->assertOk();
        
        $validation = $response->json('data');
        expect($validation['valid'])->toBeTrue();
        
        // Check that field traits are properly configured
        $fieldTraits = collect($validation['traits'])->where('name', 'fields')->first();
        expect($fieldTraits)->not->toBeNull();
        expect($fieldTraits['type'])->toBe('composite');
    });

    it('validates responsive configuration traits', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Responsive Hero',
                'responsive' => [
                    'desktop' => ['padding' => '60px 20px', 'fontSize' => '48px'],
                    'tablet' => ['padding' => '40px 15px', 'fontSize' => '36px'],
                    'mobile' => ['padding' => '20px 10px', 'fontSize' => '24px']
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$component->id}/grapejs-traits/validate");

        $response->assertOk();
        
        $validation = $response->json('data');
        expect($validation['valid'])->toBeTrue();
        
        // Check responsive traits
        $responsiveTraits = collect($validation['traits'])->where('name', 'responsive')->first();
        expect($responsiveTraits)->not->toBeNull();
        expect($responsiveTraits['type'])->toBe('responsive');
    });
});

describe('GrapeJS Integration - Performance Testing', function () {
    it('measures component loading performance in GrapeJS environment', function () {
        $components = Component::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/components/grapejs-performance-test', [
            'component_ids' => $components->pluck('id')->toArray(),
            'test_type' => 'loading',
            'iterations' => 5
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'test_results' => [
                        'average_load_time',
                        'max_load_time',
                        'min_load_time',
                        'total_components',
                        'failed_loads'
                    ],
                    'component_performance' => [
                        '*' => [
                            'component_id',
                            'load_time',
                            'memory_usage',
                            'render_time'
                        ]
                    ],
                    'recommendations'
                ]
            ]);

        $results = $response->json('data.test_results');
        expect($results['total_components'])->toBe(10);
        expect($results['average_load_time'])->toBeNumeric();
        expect($results['failed_loads'])->toBe(0);
    });

    it('tests component rendering performance with large datasets', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'config' => [
                'layout' => 'grid',
                'itemsPerPage' => 50 // Large dataset
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-performance-test", [
            'test_type' => 'rendering',
            'dataset_size' => 'large',
            'measure_memory' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'render_time',
                    'memory_usage',
                    'dom_nodes_created',
                    'performance_score',
                    'bottlenecks',
                    'optimization_suggestions'
                ]
            ]);

        $performance = $response->json('data');
        expect($performance['render_time'])->toBeNumeric();
        expect($performance['memory_usage'])->toBeNumeric();
        expect($performance['performance_score'])->toBeBetween(0, 100);
    });

    it('benchmarks component interaction performance', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'config' => [
                'fields' => array_fill(0, 20, [ // Many fields for interaction testing
                    'type' => 'text',
                    'name' => 'field_' . rand(1, 1000),
                    'label' => 'Test Field',
                    'required' => false
                ])
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-performance-test", [
            'test_type' => 'interaction',
            'interactions' => ['click', 'focus', 'input', 'validation'],
            'iterations' => 10
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'interaction_times' => [
                        'click',
                        'focus', 
                        'input',
                        'validation'
                    ],
                    'average_response_time',
                    'ui_responsiveness_score'
                ]
            ]);

        $performance = $response->json('data');
        expect($performance['average_response_time'])->toBeLessThan(100); // Should be under 100ms
        expect($performance['ui_responsiveness_score'])->toBeGreaterThan(70); // Good responsiveness
    });
});

describe('GrapeJS Integration - Compatibility Testing', function () {
    it('tests all component types compatibility with GrapeJS features', function () {
        $categories = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];
        $compatibilityResults = [];

        foreach ($categories as $category) {
            $component = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => $category,
                'is_active' => true
            ]);

            $response = $this->getJson("/api/components/{$component->id}/grapejs-compatibility");
            
            $response->assertOk();
            $compatibilityResults[$category] = $response->json('data');
        }

        foreach ($compatibilityResults as $category => $result) {
            expect($result['compatible'])->toBeTrue("Category {$category} should be compatible");
            expect($result['features_supported'])->toBeArray();
            expect($result['limitations'])->toBeArray();
        }
    });

    it('validates drag and drop functionality', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'is_active' => true
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/drag-drop", [
            'test_scenarios' => [
                'drag_from_palette',
                'drop_on_canvas',
                'reorder_components',
                'nested_dropping'
            ]
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'drag_drop_compatible',
                    'supported_scenarios',
                    'test_results' => [
                        '*' => [
                            'scenario',
                            'success',
                            'error_message'
                        ]
                    ]
                ]
            ]);

        $results = $response->json('data');
        expect($results['drag_drop_compatible'])->toBeTrue();
        
        foreach ($results['test_results'] as $test) {
            expect($test['success'])->toBeTrue("Drag-drop scenario '{$test['scenario']}' should succeed");
        }
    });

    it('tests component resizing and responsive behavior', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'responsive' => [
                    'desktop' => ['width' => '100%', 'height' => 'auto'],
                    'tablet' => ['width' => '100%', 'height' => 'auto'],
                    'mobile' => ['width' => '100%', 'height' => 'auto']
                ]
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/responsive", [
            'test_breakpoints' => ['desktop', 'tablet', 'mobile'],
            'test_resize_handles' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'responsive_compatible',
                    'breakpoint_support',
                    'resize_handle_support',
                    'test_results'
                ]
            ]);

        $results = $response->json('data');
        expect($results['responsive_compatible'])->toBeTrue();
        expect($results['breakpoint_support'])->toHaveCount(3);
        expect($results['resize_handle_support'])->toBeTrue();
    });

    it('validates style manager integration', function () {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#007bff', 'secondary' => '#6c757d'],
                'fonts' => ['heading' => 'Arial', 'body' => 'Georgia'],
                'spacing' => ['small' => '8px', 'medium' => '16px', 'large' => '32px']
            ]
        ]);

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero'
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/style-manager", [
            'theme_id' => $theme->id,
            'test_style_properties' => ['colors', 'typography', 'spacing', 'borders']
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'style_manager_compatible',
                    'supported_properties',
                    'theme_integration',
                    'css_variable_support'
                ]
            ]);

        $results = $response->json('data');
        expect($results['style_manager_compatible'])->toBeTrue();
        expect($results['theme_integration'])->toBeTrue();
        expect($results['css_variable_support'])->toBeTrue();
    });
});

describe('GrapeJS Integration - Regression Testing', function () {
    it('maintains component functionality after GrapeJS updates', function () {
        // Create baseline component
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'audienceType' => 'individual'
            ]
        ]);

        // Test baseline functionality
        $baselineResponse = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $baselineResponse->assertOk();
        $baselineData = $baselineResponse->json('data');

        // Simulate GrapeJS version update (this would be done in a separate test environment)
        $response = $this->postJson('/api/components/grapejs-regression-test', [
            'component_id' => $component->id,
            'baseline_data' => $baselineData,
            'test_scenarios' => [
                'block_generation',
                'trait_configuration',
                'serialization',
                'deserialization',
                'style_application'
            ]
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'regression_detected',
                    'test_results' => [
                        '*' => [
                            'scenario',
                            'passed',
                            'differences',
                            'severity'
                        ]
                    ],
                    'summary' => [
                        'total_tests',
                        'passed_tests',
                        'failed_tests',
                        'critical_failures'
                    ]
                ]
            ]);

        $results = $response->json('data');
        expect($results['regression_detected'])->toBeFalse();
        expect($results['summary']['critical_failures'])->toBe(0);
    });

    it('validates backward compatibility with older component versions', function () {
        // Create component with older version format
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'version' => '1.0.0',
            'config' => [
                // Old format configuration
                'title' => 'Legacy Component',
                'settings' => ['old_property' => 'value']
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/backward", [
            'target_versions' => ['1.0.0', '1.1.0', '2.0.0'],
            'migration_test' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'backward_compatible',
                    'version_compatibility' => [
                        '*' => [
                            'version',
                            'compatible',
                            'migration_required',
                            'migration_path'
                        ]
                    ],
                    'breaking_changes'
                ]
            ]);

        $results = $response->json('data');
        expect($results['backward_compatible'])->toBeTrue();
        expect($results['breaking_changes'])->toBeEmpty();
    });

    it('tests integration stability under load', function () {
        // Create multiple components for load testing
        $components = Component::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/components/grapejs-stability-test', [
            'component_ids' => $components->pluck('id')->toArray(),
            'concurrent_operations' => 10,
            'test_duration' => 30, // seconds
            'operations' => [
                'block_generation',
                'serialization',
                'trait_validation',
                'performance_measurement'
            ]
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'stability_score',
                    'error_rate',
                    'average_response_time',
                    'memory_usage',
                    'failed_operations',
                    'performance_degradation'
                ]
            ]);

        $results = $response->json('data');
        expect($results['stability_score'])->toBeGreaterThan(95); // 95% stability
        expect($results['error_rate'])->toBeLessThan(0.05); // Less than 5% error rate
        expect($results['performance_degradation'])->toBeLessThan(0.1); // Less than 10% degradation
    });

    it('validates data integrity during GrapeJS operations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'config' => [
                'title' => 'Data Integrity Test Form',
                'fields' => [
                    ['type' => 'text', 'name' => 'name', 'required' => true],
                    ['type' => 'email', 'name' => 'email', 'required' => true]
                ]
            ]
        ]);

        // Perform multiple operations and check data integrity
        $operations = [
            'serialize_to_grapejs',
            'deserialize_from_grapejs',
            'update_configuration',
            'apply_theme',
            'generate_block'
        ];

        $response = $this->postJson("/api/components/{$component->id}/grapejs-integrity-test", [
            'operations' => $operations,
            'iterations' => 5,
            'validate_checksums' => true
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'integrity_maintained',
                    'checksum_validation',
                    'data_corruption_detected',
                    'operation_results' => [
                        '*' => [
                            'operation',
                            'success',
                            'data_integrity_score'
                        ]
                    ]
                ]
            ]);

        $results = $response->json('data');
        expect($results['integrity_maintained'])->toBeTrue();
        expect($results['data_corruption_detected'])->toBeFalse();
        expect($results['checksum_validation'])->toBeTrue();
    });
});