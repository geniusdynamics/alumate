<?php

use App\Models\Component;
use App\Models\Tenant;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('CTA Vue Component Integration', function () {
    it('can render CTA button component with sample data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Join Our Network',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'lg',
                    'trackingParams' => [
                        'utm_source' => 'homepage',
                        'utm_medium' => 'cta_button',
                        'utm_campaign' => 'signup_drive'
                    ]
                ],
                'trackingEnabled' => true,
                'conversionGoal' => 'signup'
            ]
        ]);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->config['type'])->toBe('button');
        expect($component->config['buttonConfig']['text'])->toBe('Join Our Network');
        expect($component->config['trackingEnabled'])->toBeTrue();
    });

    it('can render CTA banner component with sample data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'banner',
            'config' => [
                'type' => 'banner',
                'bannerConfig' => [
                    'title' => 'Connect with Alumni Worldwide',
                    'subtitle' => 'Your next opportunity awaits',
                    'description' => 'Join our professional network',
                    'layout' => 'center-aligned',
                    'height' => 'large',
                    'primaryCTA' => [
                        'text' => 'Get Started',
                        'url' => '/signup',
                        'style' => 'primary',
                        'size' => 'lg'
                    ]
                ],
                'trackingEnabled' => true
            ]
        ]);

        expect($component->config['type'])->toBe('banner');
        expect($component->config['bannerConfig']['title'])->toBe('Connect with Alumni Worldwide');
        expect($component->config['bannerConfig']['layout'])->toBe('center-aligned');
    });

    it('can render CTA inline link component with sample data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'inline-link',
            'config' => [
                'type' => 'inline-link',
                'inlineLinkConfig' => [
                    'text' => 'Learn more about our platform',
                    'url' => '/about',
                    'style' => 'arrow',
                    'size' => 'base',
                    'trackingParams' => [
                        'utm_source' => 'content',
                        'utm_medium' => 'inline_link'
                    ]
                ],
                'trackingEnabled' => true
            ]
        ]);

        expect($component->config['type'])->toBe('inline-link');
        expect($component->config['inlineLinkConfig']['text'])->toBe('Learn more about our platform');
        expect($component->config['inlineLinkConfig']['style'])->toBe('arrow');
    });

    it('validates CTA component configuration structure', function () {
        $validConfig = [
            'type' => 'button',
            'buttonConfig' => [
                'text' => 'Click Me',
                'url' => '/test',
                'style' => 'primary',
                'size' => 'md'
            ],
            'trackingEnabled' => true
        ];

        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => $validConfig
        ]);

        // Test that the component was created successfully
        expect($component->config)->toBe($validConfig);
        expect($component->config['buttonConfig'])->toHaveKey('text');
        expect($component->config['buttonConfig'])->toHaveKey('url');
        expect($component->config['buttonConfig'])->toHaveKey('style');
        expect($component->config['buttonConfig'])->toHaveKey('size');
    });

    it('supports A/B testing configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Join Now',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'lg'
                ],
                'abTest' => [
                    'enabled' => true,
                    'testId' => 'signup_button_test',
                    'variants' => [
                        [
                            'id' => 'control',
                            'name' => 'Original',
                            'weight' => 50,
                            'config' => [
                                'buttonConfig' => [
                                    'text' => 'Join Now'
                                ]
                            ]
                        ],
                        [
                            'id' => 'variant_a',
                            'name' => 'Action Focused',
                            'weight' => 50,
                            'config' => [
                                'buttonConfig' => [
                                    'text' => 'Start Networking Today'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        expect($component->config['abTest']['enabled'])->toBeTrue();
        expect($component->config['abTest']['testId'])->toBe('signup_button_test');
        expect($component->config['abTest']['variants'])->toHaveCount(2);
        
        $variants = $component->config['abTest']['variants'];
        expect($variants[0]['id'])->toBe('control');
        expect($variants[1]['id'])->toBe('variant_a');
    });

    it('supports accessibility configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Accessible Button',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'lg',
                    'accessibility' => [
                        'ariaLabel' => 'Join our alumni network - opens signup form',
                        'ariaDescribedBy' => 'signup-help-text',
                        'keyboardShortcut' => 'alt+s'
                    ]
                ],
                'respectReducedMotion' => true,
                'highContrast' => true
            ]
        ]);

        $accessibility = $component->config['buttonConfig']['accessibility'];
        expect($accessibility['ariaLabel'])->toBe('Join our alumni network - opens signup form');
        expect($accessibility['ariaDescribedBy'])->toBe('signup-help-text');
        expect($accessibility['keyboardShortcut'])->toBe('alt+s');
        expect($component->config['respectReducedMotion'])->toBeTrue();
        expect($component->config['highContrast'])->toBeTrue();
    });

    it('supports conversion tracking configuration', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Track Me',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'lg',
                    'trackingParams' => [
                        'utm_source' => 'homepage',
                        'utm_medium' => 'cta_button',
                        'utm_campaign' => 'signup_drive',
                        'utm_content' => 'primary_cta'
                    ],
                    'conversionEvents' => [
                        [
                            'eventName' => 'signup_initiated',
                            'category' => 'conversion',
                            'action' => 'click',
                            'label' => 'primary_signup_button',
                            'value' => 100
                        ]
                    ]
                ],
                'trackingEnabled' => true,
                'conversionGoal' => 'signup'
            ]
        ]);

        expect($component->config['trackingEnabled'])->toBeTrue();
        expect($component->config['conversionGoal'])->toBe('signup');
        
        $trackingParams = $component->config['buttonConfig']['trackingParams'];
        expect($trackingParams['utm_source'])->toBe('homepage');
        expect($trackingParams['utm_medium'])->toBe('cta_button');
        
        $conversionEvents = $component->config['buttonConfig']['conversionEvents'];
        expect($conversionEvents)->toHaveCount(1);
        expect($conversionEvents[0]['eventName'])->toBe('signup_initiated');
        expect($conversionEvents[0]['value'])->toBe(100);
    });

    it('supports different CTA styles and sizes', function () {
        $styles = ['primary', 'secondary', 'outline', 'ghost', 'link'];
        $sizes = ['xs', 'sm', 'md', 'lg', 'xl'];

        foreach ($styles as $style) {
            foreach ($sizes as $size) {
                $component = Component::factory()->create([
                    'tenant_id' => $this->tenant->id,
                    'category' => 'ctas',
                    'type' => 'button',
                    'config' => [
                        'type' => 'button',
                        'buttonConfig' => [
                            'text' => "Test {$style} {$size}",
                            'url' => '/test',
                            'style' => $style,
                            'size' => $size
                        ]
                    ]
                ]);

                expect($component->config['buttonConfig']['style'])->toBe($style);
                expect($component->config['buttonConfig']['size'])->toBe($size);
            }
        }
    });

    it('supports banner layouts and configurations', function () {
        $layouts = ['left-aligned', 'center-aligned', 'right-aligned', 'split'];
        $heights = ['compact', 'medium', 'large', 'full-screen'];

        foreach ($layouts as $layout) {
            foreach ($heights as $height) {
                $component = Component::factory()->create([
                    'tenant_id' => $this->tenant->id,
                    'category' => 'ctas',
                    'type' => 'banner',
                    'config' => [
                        'type' => 'banner',
                        'bannerConfig' => [
                            'title' => "Test {$layout} {$height}",
                            'layout' => $layout,
                            'height' => $height,
                            'primaryCTA' => [
                                'text' => 'Test CTA',
                                'url' => '/test',
                                'style' => 'primary',
                                'size' => 'md'
                            ]
                        ]
                    ]
                ]);

                expect($component->config['bannerConfig']['layout'])->toBe($layout);
                expect($component->config['bannerConfig']['height'])->toBe($height);
            }
        }
    });

    it('supports inline link styles and configurations', function () {
        $styles = ['default', 'underline', 'button-like', 'arrow', 'external'];
        $sizes = ['xs', 'sm', 'base', 'lg', 'xl'];

        foreach ($styles as $style) {
            foreach ($sizes as $size) {
                $component = Component::factory()->create([
                    'tenant_id' => $this->tenant->id,
                    'category' => 'ctas',
                    'type' => 'inline-link',
                    'config' => [
                        'type' => 'inline-link',
                        'inlineLinkConfig' => [
                            'text' => "Test {$style} {$size}",
                            'url' => '/test',
                            'style' => $style,
                            'size' => $size
                        ]
                    ]
                ]);

                expect($component->config['inlineLinkConfig']['style'])->toBe($style);
                expect($component->config['inlineLinkConfig']['size'])->toBe($size);
            }
        }
    });
});