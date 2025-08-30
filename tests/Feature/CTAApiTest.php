<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

describe('CTA Component API', function () {
    it('can create a CTA button component via API', function () {
        $data = [
            'name' => 'Primary Signup CTA',
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
        ];

        $response = $this->postJson('/api/components', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'category',
                    'type',
                    'config',
                    'created_at',
                    'updated_at'
                ]
            ]);

        expect($response->json('data.category'))->toBe('ctas');
        expect($response->json('data.type'))->toBe('button');
        expect($response->json('data.config.type'))->toBe('button');
    });

    it('can create a CTA banner component via API', function () {
        $data = [
            'name' => 'Hero Banner CTA',
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
        ];

        $response = $this->postJson('/api/components', $data);

        $response->assertCreated();
        expect($response->json('data.config.bannerConfig.title'))->toBe('Connect with Alumni Worldwide');
    });

    it('can retrieve CTA components by category', function () {
        // Create multiple CTA components
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Button CTA',
                    'url' => '/test',
                    'style' => 'primary',
                    'size' => 'md'
                ]
            ]
        ]);

        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'banner',
            'config' => [
                'type' => 'banner',
                'bannerConfig' => [
                    'title' => 'Banner CTA',
                    'layout' => 'center-aligned'
                ]
            ]
        ]);

        // Create a non-CTA component to ensure filtering works
        Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'type' => 'hero-section'
        ]);

        $response = $this->getJson('/api/components?category=ctas');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'category',
                        'type',
                        'config'
                    ]
                ]
            ]);

        $components = $response->json('data');
        expect($components)->toHaveCount(2);
        
        foreach ($components as $component) {
            expect($component['category'])->toBe('ctas');
        }
    });

    it('can update a CTA component', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Original Text',
                    'url' => '/signup',
                    'style' => 'primary',
                    'size' => 'md'
                ]
            ]
        ]);

        $updateData = [
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    'text' => 'Updated Text',
                    'url' => '/signup',
                    'style' => 'secondary',
                    'size' => 'lg'
                ],
                'trackingEnabled' => true
            ]
        ];

        $response = $this->putJson("/api/components/{$component->id}", $updateData);

        $response->assertOk();
        expect($response->json('data.config.buttonConfig.text'))->toBe('Updated Text');
        expect($response->json('data.config.buttonConfig.style'))->toBe('secondary');
        expect($response->json('data.config.trackingEnabled'))->toBeTrue();
    });

    it('validates CTA component creation', function () {
        $data = [
            'name' => 'Invalid CTA',
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'buttonConfig' => [
                    // Missing required 'text' field
                    'url' => '/signup',
                    'style' => 'invalid-style', // Invalid style
                    'size' => 'lg'
                ]
            ]
        ];

        $response = $this->postJson('/api/components', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['config']);
    });

    it('enforces tenant isolation for CTA components', function () {
        $otherTenant = Tenant::factory()->create();
        $otherComponent = Component::factory()->create([
            'tenant_id' => $otherTenant->id,
            'category' => 'ctas',
            'type' => 'button'
        ]);

        // Try to access component from different tenant
        $response = $this->getJson("/api/components/{$otherComponent->id}");
        $response->assertNotFound();

        // Try to update component from different tenant
        $response = $this->putJson("/api/components/{$otherComponent->id}", [
            'name' => 'Hacked Component'
        ]);
        $response->assertNotFound();

        // Try to delete component from different tenant
        $response = $this->deleteJson("/api/components/{$otherComponent->id}");
        $response->assertNotFound();
    });
});

describe('CTA Component Analytics API', function () {
    it('can track CTA impressions', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button'
        ]);

        $data = [
            'event_type' => 'impression',
            'component_id' => $component->id,
            'data' => [
                'page_url' => '/homepage',
                'user_agent' => 'Test Browser',
                'timestamp' => now()->toISOString()
            ]
        ];

        $response = $this->postJson('/api/analytics/events', $data);

        $response->assertCreated();
        
        $this->assertDatabaseHas('component_analytics', [
            'component_id' => $component->id,
            'event_type' => 'impression'
        ]);
    });

    it('can track CTA clicks', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button'
        ]);

        $data = [
            'event_type' => 'click',
            'component_id' => $component->id,
            'data' => [
                'button_text' => 'Join Now',
                'destination_url' => '/signup',
                'page_url' => '/homepage',
                'timestamp' => now()->toISOString()
            ]
        ];

        $response = $this->postJson('/api/analytics/events', $data);

        $response->assertCreated();
        
        $this->assertDatabaseHas('component_analytics', [
            'component_id' => $component->id,
            'event_type' => 'click'
        ]);
    });

    it('can track CTA conversions', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button'
        ]);

        $data = [
            'event_type' => 'conversion',
            'component_id' => $component->id,
            'data' => [
                'conversion_type' => 'signup',
                'conversion_value' => 100,
                'user_id' => $this->user->id,
                'timestamp' => now()->toISOString()
            ]
        ];

        $response = $this->postJson('/api/analytics/events', $data);

        $response->assertCreated();
        
        $this->assertDatabaseHas('component_analytics', [
            'component_id' => $component->id,
            'event_type' => 'conversion',
            'user_id' => $this->user->id
        ]);
    });

    it('can retrieve CTA analytics data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button'
        ]);

        // Create some analytics data
        $component->analytics()->create([
            'event_type' => 'impression',
            'user_id' => $this->user->id,
            'data' => ['page_url' => '/homepage']
        ]);

        $component->analytics()->create([
            'event_type' => 'click',
            'user_id' => $this->user->id,
            'data' => ['button_text' => 'Join Now']
        ]);

        $response = $this->getJson("/api/components/{$component->id}/analytics");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'impressions',
                    'clicks',
                    'conversions',
                    'click_through_rate',
                    'conversion_rate'
                ]
            ]);

        expect($response->json('data.impressions'))->toBe(1);
        expect($response->json('data.clicks'))->toBe(1);
    });
});

describe('CTA Component A/B Testing API', function () {
    it('can assign A/B test variants', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'ctas',
            'type' => 'button',
            'config' => [
                'type' => 'button',
                'abTest' => [
                    'enabled' => true,
                    'testId' => 'signup_button_test',
                    'variants' => [
                        [
                            'id' => 'control',
                            'name' => 'Original',
                            'weight' => 50,
                            'config' => ['buttonConfig' => ['text' => 'Join Now']]
                        ],
                        [
                            'id' => 'variant_a',
                            'name' => 'Action Focused',
                            'weight' => 50,
                            'config' => ['buttonConfig' => ['text' => 'Start Today']]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->postJson("/api/components/{$component->id}/ab-test/assign", [
            'user_id' => $this->user->id
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'test_id',
                    'variant_id',
                    'variant_config'
                ]
            ]);

        $variant = $response->json('data');
        expect($variant['test_id'])->toBe('signup_button_test');
        expect(in_array($variant['variant_id'], ['control', 'variant_a']))->toBeTrue();
    });
});