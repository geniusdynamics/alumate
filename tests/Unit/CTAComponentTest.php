<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Services\ComponentService;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->componentService = app(ComponentService::class);
});

describe('CTA Component Creation', function () {
    it('can create a CTA button component', function () {
        $config = [
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
        ];

        $component = $this->componentService->create([
            'name' => 'Primary Signup CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id);

        expect($component)->toBeInstanceOf(Component::class);
        expect($component->category)->toBe('ctas');
        expect($component->type)->toBe('button');
        expect($component->config['type'])->toBe('button');
        expect($component->config['buttonConfig']['text'])->toBe('Join Our Network');
        expect($component->config['trackingEnabled'])->toBeTrue();
    });

    it('can create a CTA banner component', function () {
        $config = [
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
        ];

        $component = $this->componentService->create([
            'name' => 'Hero Banner CTA',
            'category' => 'ctas',
            'type' => 'banner',
            'config' => $config
        ], $this->tenant->id);

        expect($component->config['type'])->toBe('banner');
        expect($component->config['bannerConfig']['title'])->toBe('Connect with Alumni Worldwide');
        expect($component->config['bannerConfig']['layout'])->toBe('center-aligned');
        expect($component->config['bannerConfig']['primaryCTA']['text'])->toBe('Get Started');
    });

    it('can create a CTA inline link component', function () {
        $config = [
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
        ];

        $component = $this->componentService->create([
            'name' => 'Learn More Link',
            'category' => 'ctas',
            'type' => 'inline-link',
            'config' => $config
        ], $this->tenant->id);

        expect($component->config['type'])->toBe('inline-link');
        expect($component->config['inlineLinkConfig']['text'])->toBe('Learn more about our platform');
        expect($component->config['inlineLinkConfig']['style'])->toBe('arrow');
    });
});

describe('CTA Component Validation', function () {
    it('validates required fields for button CTA', function () {
        $config = [
            'type' => 'button',
            'buttonConfig' => [
                // Missing required 'text' field
                'url' => '/signup',
                'style' => 'primary',
                'size' => 'lg'
            ]
        ];

        expect(fn() => $this->componentService->create([
            'name' => 'Invalid Button CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id))->toThrow(InvalidArgumentException::class);
    });

    it('validates CTA button styles', function () {
        $config = [
            'type' => 'button',
            'buttonConfig' => [
                'text' => 'Click Me',
                'url' => '/test',
                'style' => 'invalid-style', // Invalid style
                'size' => 'lg'
            ]
        ];

        expect(fn() => $this->componentService->create([
            'name' => 'Invalid Style CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id))->toThrow(InvalidArgumentException::class);
    });

    it('validates CTA button sizes', function () {
        $config = [
            'type' => 'button',
            'buttonConfig' => [
                'text' => 'Click Me',
                'url' => '/test',
                'style' => 'primary',
                'size' => 'invalid-size' // Invalid size
            ]
        ];

        expect(fn() => $this->componentService->create([
            'name' => 'Invalid Size CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id))->toThrow(InvalidArgumentException::class);
    });
});

describe('CTA Component A/B Testing', function () {
    it('can create CTA with A/B test configuration', function () {
        $config = [
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
        ];

        $component = $this->componentService->create([
            'name' => 'A/B Test CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id);

        expect($component->config['abTest']['enabled'])->toBeTrue();
        expect($component->config['abTest']['testId'])->toBe('signup_button_test');
        expect($component->config['abTest']['variants'])->toHaveCount(2);
    });
});

describe('CTA Component Tracking', function () {
    it('includes tracking parameters in configuration', function () {
        $config = [
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
                        'label' => 'primary_signup_button'
                    ]
                ]
            ],
            'trackingEnabled' => true,
            'conversionGoal' => 'signup'
        ];

        $component = $this->componentService->create([
            'name' => 'Tracked CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id);

        expect($component->config['trackingEnabled'])->toBeTrue();
        expect($component->config['conversionGoal'])->toBe('signup');
        expect($component->config['buttonConfig']['trackingParams'])->toHaveKey('utm_source');
        expect($component->config['buttonConfig']['conversionEvents'])->toHaveCount(1);
    });
});

describe('CTA Component Accessibility', function () {
    it('includes accessibility configuration', function () {
        $config = [
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
        ];

        $component = $this->componentService->create([
            'name' => 'Accessible CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $this->tenant->id);

        expect($component->config['respectReducedMotion'])->toBeTrue();
        expect($component->config['highContrast'])->toBeTrue();
        expect($component->config['buttonConfig']['accessibility']['ariaLabel'])
            ->toBe('Join our alumni network - opens signup form');
    });
});

describe('CTA Component Tenant Isolation', function () {
    it('scopes CTA components to tenant', function () {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $config = [
            'type' => 'button',
            'buttonConfig' => [
                'text' => 'Tenant 1 CTA',
                'url' => '/signup',
                'style' => 'primary',
                'size' => 'lg'
            ]
        ];

        $component1 = $this->componentService->create([
            'name' => 'Tenant 1 CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $tenant1->id);

        $component2 = $this->componentService->create([
            'name' => 'Tenant 2 CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => $config
        ], $tenant2->id);

        // Components should be isolated by tenant
        $tenant1Components = Component::where('tenant_id', $tenant1->id)->get();
        $tenant2Components = Component::where('tenant_id', $tenant2->id)->get();

        expect($tenant1Components)->toHaveCount(1);
        expect($tenant2Components)->toHaveCount(1);
        expect($tenant1Components->first()->id)->not->toBe($tenant2Components->first()->id);
    });
});