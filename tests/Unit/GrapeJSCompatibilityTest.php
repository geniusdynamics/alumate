<?php

use App\Models\Component;
use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

describe('GrapeJS Component Type Compatibility', function () {
    it('validates hero component compatibility with GrapeJS features', function () {
        $heroComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'headline' => 'Test Hero',
                'subheading' => 'Test Subheading',
                'audienceType' => 'individual',
                'backgroundType' => 'video',
                'ctaButtons' => [
                    ['text' => 'Get Started', 'url' => '/signup']
                ],
                'responsive' => [
                    'desktop' => ['padding' => '80px 20px'],
                    'tablet' => ['padding' => '60px 15px'],
                    'mobile' => ['padding' => '40px 10px']
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$heroComponent->id}/grapejs-compatibility");

        $response->assertOk()
            ->assertJsonStructure([
                'success' => true,
                'data' => [
                    'compatible',
                    'features_supported',
                    'limitations',
                    'grapejs_version_requirements',
                    'recommended_plugins'
                ]
            ]);

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('drag_drop');
        expect($compatibility['features_supported'])->toContain('responsive_design');
        expect($compatibility['features_supported'])->toContain('style_manager');
        expect($compatibility['features_supported'])->toContain('trait_manager');
        expect($compatibility['features_supported'])->toContain('block_manager');
    });

    it('validates form component compatibility with validation features', function () {
        $formComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'config' => [
                'title' => 'Contact Form',
                'fields' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'validation' => ['required' => true, 'min_length' => 2]
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'validation' => ['required' => true, 'email_format' => true]
                    ],
                    [
                        'type' => 'select',
                        'name' => 'category',
                        'options' => ['general', 'support', 'sales']
                    ]
                ],
                'validation' => [
                    'client_side' => true,
                    'server_side' => true,
                    'real_time' => true
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$formComponent->id}/grapejs-compatibility");

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('form_validation');
        expect($compatibility['features_supported'])->toContain('field_configuration');
        expect($compatibility['features_supported'])->toContain('dynamic_fields');
        expect($compatibility['limitations'])->toBeArray();
    });

    it('validates testimonial component compatibility with media features', function () {
        $testimonialComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'testimonials',
            'config' => [
                'layout' => 'carousel',
                'videoSupport' => true,
                'filterOptions' => ['industry', 'location'],
                'animations' => [
                    'enabled' => true,
                    'type' => 'slide',
                    'duration' => 500
                ],
                'accessibility' => [
                    'keyboardNavigation' => true,
                    'screenReaderSupport' => true
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$testimonialComponent->id}/grapejs-compatibility");

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('video_support');
        expect($compatibility['features_supported'])->toContain('carousel_navigation');
        expect($compatibility['features_supported'])->toContain('filtering');
        expect($compatibility['features_supported'])->toContain('accessibility');
    });

    it('validates statistics component compatibility with animation features', function () {
        $statisticsComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'statistics',
            'config' => [
                'displayType' => 'counters',
                'animationEnabled' => true,
                'triggerOnScroll' => true,
                'realTimeData' => true,
                'chartTypes' => ['bar', 'line', 'pie'],
                'interactivity' => [
                    'hover_effects' => true,
                    'click_actions' => true,
                    'tooltips' => true
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$statisticsComponent->id}/grapejs-compatibility");

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('counter_animations');
        expect($compatibility['features_supported'])->toContain('scroll_triggers');
        expect($compatibility['features_supported'])->toContain('real_time_data');
        expect($compatibility['features_supported'])->toContain('chart_rendering');
    });

    it('validates CTA component compatibility with tracking features', function () {
        $ctaComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'config' => [
                'type' => 'button',
                'tracking' => [
                    'enabled' => true,
                    'analytics_provider' => 'google_analytics',
                    'conversion_goals' => ['signup', 'purchase']
                ],
                'abTesting' => [
                    'enabled' => true,
                    'variants' => 3,
                    'traffic_split' => [40, 30, 30]
                ],
                'personalization' => [
                    'enabled' => true,
                    'rules' => ['location', 'device', 'referrer']
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$ctaComponent->id}/grapejs-compatibility");

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('conversion_tracking');
        expect($compatibility['features_supported'])->toContain('ab_testing');
        expect($compatibility['features_supported'])->toContain('personalization');
        expect($compatibility['features_supported'])->toContain('analytics_integration');
    });

    it('validates media component compatibility with optimization features', function () {
        $mediaComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'type' => 'image-gallery',
                'optimization' => [
                    'lazyLoading' => true,
                    'webpSupport' => true,
                    'responsiveImages' => true,
                    'compressionEnabled' => true
                ],
                'lightbox' => [
                    'enabled' => true,
                    'fullscreen' => true,
                    'zoom' => true
                ],
                'cdn' => [
                    'enabled' => true,
                    'provider' => 'cloudflare'
                ]
            ]
        ]);

        $response = $this->getJson("/api/components/{$mediaComponent->id}/grapejs-compatibility");

        $compatibility = $response->json('data');
        
        expect($compatibility['compatible'])->toBeTrue();
        expect($compatibility['features_supported'])->toContain('lazy_loading');
        expect($compatibility['features_supported'])->toContain('image_optimization');
        expect($compatibility['features_supported'])->toContain('lightbox');
        expect($compatibility['features_supported'])->toContain('cdn_integration');
    });
});

describe('GrapeJS Drag and Drop Compatibility', function () {
    it('tests drag and drop functionality for all component types', function () {
        $componentTypes = ['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'];
        
        foreach ($componentTypes as $type) {
            $component = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => $type,
                'is_active' => true
            ]);

            $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/drag-drop", [
                'test_scenarios' => [
                    'drag_from_palette',
                    'drop_on_canvas',
                    'reorder_components',
                    'nested_dropping',
                    'drag_between_containers'
                ]
            ]);

            $response->assertOk();
            
            $dragDropResults = $response->json('data');
            expect($dragDropResults['drag_drop_compatible'])->toBeTrue("Component type {$type} should support drag and drop");
            
            foreach ($dragDropResults['test_results'] as $test) {
                expect($test['success'])->toBeTrue("Drag-drop scenario '{$test['scenario']}' should work for {$type} components");
            }
        }
    });

    it('validates component nesting capabilities', function () {
        $containerComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'layout' => 'container',
                'allowNesting' => true,
                'maxNestingDepth' => 3,
                'allowedChildTypes' => ['forms', 'ctas', 'media']
            ]
        ]);

        $childComponents = [
            Component::factory()->create(['tenant_id' => $this->tenant->id, 'category' => 'forms']),
            Component::factory()->create(['tenant_id' => $this->tenant->id, 'category' => 'ctas']),
            Component::factory()->create(['tenant_id' => $this->tenant->id, 'category' => 'media'])
        ];

        $response = $this->postJson("/api/components/{$containerComponent->id}/grapejs-compatibility/nesting", [
            'child_component_ids' => collect($childComponents)->pluck('id')->toArray(),
            'test_nesting_depth' => 2
        ]);

        $response->assertOk();
        
        $nestingResults = $response->json('data');
        expect($nestingResults['nesting_supported'])->toBeTrue();
        expect($nestingResults['max_depth_supported'])->toBeGreaterThanOrEqual(2);
        
        foreach ($nestingResults['child_compatibility'] as $childResult) {
            expect($childResult['can_nest'])->toBeTrue();
        }
    });

    it('tests component resizing and responsive behavior', function () {
        $responsiveComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'responsive' => [
                    'desktop' => ['width' => '100%', 'height' => 'auto'],
                    'tablet' => ['width' => '100%', 'height' => 'auto'],
                    'mobile' => ['width' => '100%', 'height' => 'auto']
                ],
                'resizable' => true,
                'maintainAspectRatio' => true
            ]
        ]);

        $response = $this->postJson("/api/components/{$responsiveComponent->id}/grapejs-compatibility/responsive", [
            'test_breakpoints' => ['desktop', 'tablet', 'mobile'],
            'test_resize_handles' => true,
            'test_aspect_ratio' => true
        ]);

        $response->assertOk();
        
        $responsiveResults = $response->json('data');
        expect($responsiveResults['responsive_compatible'])->toBeTrue();
        expect($responsiveResults['resize_handle_support'])->toBeTrue();
        expect($responsiveResults['aspect_ratio_support'])->toBeTrue();
        
        expect($responsiveResults['breakpoint_support'])->toHaveCount(3);
        foreach ($responsiveResults['breakpoint_support'] as $breakpoint => $supported) {
            expect($supported)->toBeTrue("Breakpoint {$breakpoint} should be supported");
        }
    });
});

describe('GrapeJS Style Manager Integration', function () {
    it('validates style manager compatibility with component themes', function () {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                    'success' => '#28a745',
                    'danger' => '#dc3545'
                ],
                'fonts' => [
                    'heading' => 'Arial, sans-serif',
                    'body' => 'Georgia, serif',
                    'monospace' => 'Courier New, monospace'
                ],
                'spacing' => [
                    'xs' => '4px',
                    'sm' => '8px',
                    'md' => '16px',
                    'lg' => '32px',
                    'xl' => '64px'
                ],
                'borders' => [
                    'radius' => ['sm' => '4px', 'md' => '8px', 'lg' => '16px'],
                    'width' => ['thin' => '1px', 'medium' => '2px', 'thick' => '4px']
                ]
            ]
        ]);

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero'
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/style-manager", [
            'theme_id' => $theme->id,
            'test_style_properties' => ['colors', 'typography', 'spacing', 'borders', 'shadows']
        ]);

        $response->assertOk();
        
        $styleResults = $response->json('data');
        expect($styleResults['style_manager_compatible'])->toBeTrue();
        expect($styleResults['theme_integration'])->toBeTrue();
        expect($styleResults['css_variable_support'])->toBeTrue();
        
        expect($styleResults['supported_properties'])->toContain('colors');
        expect($styleResults['supported_properties'])->toContain('typography');
        expect($styleResults['supported_properties'])->toContain('spacing');
        expect($styleResults['supported_properties'])->toContain('borders');
    });

    it('tests custom CSS property integration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'customStyles' => [
                    'background-gradient' => 'linear-gradient(45deg, #ff6b6b, #4ecdc4)',
                    'box-shadow' => '0 10px 30px rgba(0,0,0,0.1)',
                    'border-radius' => '12px',
                    'transform' => 'translateY(-5px)',
                    'transition' => 'all 0.3s ease'
                ]
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/custom-styles", [
            'validate_css_properties' => true,
            'check_browser_support' => true
        ]);

        $response->assertOk();
        
        $styleResults = $response->json('data');
        expect($styleResults['custom_styles_supported'])->toBeTrue();
        expect($styleResults['css_validation'])->toHaveKey('valid', true);
        expect($styleResults['browser_compatibility'])->toBeArray();
    });

    it('validates CSS Grid and Flexbox compatibility', function () {
        $layoutComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'layout' => [
                    'type' => 'grid',
                    'grid' => [
                        'columns' => 'repeat(auto-fit, minmax(300px, 1fr))',
                        'rows' => 'auto',
                        'gap' => '20px'
                    ],
                    'flexbox' => [
                        'direction' => 'row',
                        'justify' => 'space-between',
                        'align' => 'center',
                        'wrap' => 'wrap'
                    ]
                ]
            ]
        ]);

        $response = $this->postJson("/api/components/{$layoutComponent->id}/grapejs-compatibility/layout", [
            'test_css_grid' => true,
            'test_flexbox' => true,
            'test_responsive_layout' => true
        ]);

        $response->assertOk();
        
        $layoutResults = $response->json('data');
        expect($layoutResults['css_grid_support'])->toBeTrue();
        expect($layoutResults['flexbox_support'])->toBeTrue();
        expect($layoutResults['responsive_layout_support'])->toBeTrue();
    });
});

describe('GrapeJS Plugin Compatibility', function () {
    it('tests compatibility with common GrapeJS plugins', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'is_active' => true
        ]);

        $commonPlugins = [
            'grapejs-blocks-basic',
            'grapejs-plugin-forms',
            'grapejs-component-countdown',
            'grapejs-plugin-export',
            'grapejs-preset-webpage',
            'grapejs-plugin-ckeditor'
        ];

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/plugins", [
            'plugins' => $commonPlugins,
            'test_integration' => true
        ]);

        $response->assertOk();
        
        $pluginResults = $response->json('data');
        expect($pluginResults['overall_compatibility'])->toBeTrue();
        
        foreach ($pluginResults['plugin_compatibility'] as $plugin => $result) {
            expect($result['compatible'])->toBeTrue("Plugin {$plugin} should be compatible");
            if (!$result['compatible']) {
                expect($result['issues'])->toBeArray();
                expect($result['workarounds'])->toBeArray();
            }
        }
    });

    it('validates custom plugin integration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'customPlugins' => [
                    'alumni-gallery-plugin' => [
                        'version' => '1.0.0',
                        'features' => ['filtering', 'lightbox', 'lazy-loading']
                    ],
                    'analytics-tracking-plugin' => [
                        'version' => '2.1.0',
                        'features' => ['event-tracking', 'conversion-goals']
                    ]
                ]
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/custom-plugins", [
            'validate_plugin_apis' => true,
            'check_version_compatibility' => true
        ]);

        $response->assertOk();
        
        $customPluginResults = $response->json('data');
        expect($customPluginResults['custom_plugins_supported'])->toBeTrue();
        expect($customPluginResults['api_compatibility'])->toBeTrue();
        expect($customPluginResults['version_conflicts'])->toBeEmpty();
    });
});

describe('GrapeJS Browser Compatibility', function () {
    it('validates cross-browser compatibility', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'modernFeatures' => [
                    'css_grid' => true,
                    'css_variables' => true,
                    'intersection_observer' => true,
                    'web_animations_api' => true
                ]
            ]
        ]);

        $browsers = [
            'chrome' => ['version' => '90+', 'engine' => 'blink'],
            'firefox' => ['version' => '88+', 'engine' => 'gecko'],
            'safari' => ['version' => '14+', 'engine' => 'webkit'],
            'edge' => ['version' => '90+', 'engine' => 'blink']
        ];

        $response = $this->postJson("/api/components/{$component->id}/grapejs-compatibility/browsers", [
            'target_browsers' => $browsers,
            'test_modern_features' => true,
            'include_polyfills' => true
        ]);

        $response->assertOk();
        
        $browserResults = $response->json('data');
        expect($browserResults['overall_browser_support'])->toBeTrue();
        
        foreach ($browserResults['browser_compatibility'] as $browser => $result) {
            expect($result['supported'])->toBeTrue("Browser {$browser} should be supported");
            if (isset($result['required_polyfills'])) {
                expect($result['required_polyfills'])->toBeArray();
            }
        }
    });

    it('tests mobile browser compatibility', function () {
        $mobileComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'config' => [
                'mobileOptimizations' => [
                    'touch_friendly' => true,
                    'viewport_meta' => true,
                    'mobile_keyboard' => true,
                    'gesture_support' => true
                ]
            ]
        ]);

        $mobileBrowsers = [
            'chrome_mobile' => ['version' => '90+', 'platform' => 'android'],
            'safari_mobile' => ['version' => '14+', 'platform' => 'ios'],
            'firefox_mobile' => ['version' => '88+', 'platform' => 'android'],
            'samsung_internet' => ['version' => '14+', 'platform' => 'android']
        ];

        $response = $this->postJson("/api/components/{$mobileComponent->id}/grapejs-compatibility/mobile-browsers", [
            'target_browsers' => $mobileBrowsers,
            'test_touch_interactions' => true,
            'test_viewport_handling' => true
        ]);

        $response->assertOk();
        
        $mobileResults = $response->json('data');
        expect($mobileResults['mobile_browser_support'])->toBeTrue();
        expect($mobileResults['touch_compatibility'])->toBeTrue();
        expect($mobileResults['viewport_compatibility'])->toBeTrue();
    });
});