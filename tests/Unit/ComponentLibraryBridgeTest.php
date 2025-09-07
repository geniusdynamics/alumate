<?php

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->actingAs($this->tenant->users()->first());
});

describe('ComponentLibraryBridge Service', function () {
    it('can initialize with all supporting services', function () {
        // This test would verify the JavaScript service initialization
        // Since we're testing PHP, we'll test the API endpoints that support the bridge
        
        $response = $this->getJson('/api/components/bridge/initialize');
        
        $response->assertOk()
            ->assertJsonStructure([
                'categories',
                'searchIndex',
                'analytics'
            ]);
    });

    it('can get organized categories for GrapeJS Block Manager', function () {
        // Create components in different categories
        $heroComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Hero Section',
            'is_active' => true
        ]);

        $formComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Contact Form',
            'is_active' => true
        ]);

        $response = $this->getJson('/api/components/bridge/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'icon',
                        'description',
                        'components',
                        'order',
                        'isCollapsed'
                    ]
                ]
            ]);

        $categories = $response->json('data');
        
        expect($categories)->toHaveCount(6); // All 6 default categories
        expect(collect($categories)->pluck('id')->toArray())
            ->toContain('hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media');
    });

    it('can search components with advanced filtering', function () {
        // Create test components
        $heroComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Alumni Success Hero',
            'description' => 'Hero section for alumni success stories',
            'type' => 'individual-hero',
            'is_active' => true
        ]);

        $formComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'name' => 'Alumni Registration Form',
            'description' => 'Form for alumni to register',
            'type' => 'registration-form',
            'is_active' => true
        ]);

        // Test basic search
        $response = $this->getJson('/api/components/bridge/search?q=alumni');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'component',
                        'relevanceScore',
                        'matchedFields',
                        'highlights'
                    ]
                ]
            ]);

        $results = $response->json('data');
        expect($results)->toHaveCount(2);

        // Test filtered search
        $response = $this->getJson('/api/components/bridge/search?q=alumni&category=hero');

        $results = $response->json('data');
        expect($results)->toHaveCount(1);
        expect($results[0]['component']['category'])->toBe('hero');
    });

    it('can track component usage for analytics', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'is_active' => true
        ]);

        // Track usage
        $response = $this->postJson('/api/components/bridge/track-usage', [
            'componentId' => $component->id,
            'context' => 'grapeJS'
        ]);

        $response->assertOk();

        // Get usage stats
        $response = $this->getJson("/api/components/bridge/usage-stats/{$component->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'componentId',
                    'totalUsage',
                    'recentUsage',
                    'averageRating',
                    'conversionRate',
                    'lastUsed',
                    'popularConfigurations'
                ]
            ]);

        $stats = $response->json('data');
        expect($stats['totalUsage'])->toBeGreaterThan(0);
    });

    it('can track component ratings', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $response = $this->postJson('/api/components/bridge/track-rating', [
            'componentId' => $component->id,
            'rating' => 4.5
        ]);

        $response->assertOk();

        $response = $this->getJson("/api/components/bridge/usage-stats/{$component->id}");
        $stats = $response->json('data');
        
        expect($stats['averageRating'])->toBeGreaterThan(0);
    });

    it('can get most used components', function () {
        // Create multiple components
        $components = Component::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        // Track usage for some components
        foreach ($components->take(3) as $component) {
            $this->postJson('/api/components/bridge/track-usage', [
                'componentId' => $component->id,
                'context' => 'grapeJS'
            ]);
        }

        $response = $this->getJson('/api/components/bridge/most-used?limit=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'componentId',
                        'totalUsage',
                        'recentUsage',
                        'averageRating',
                        'lastUsed'
                    ]
                ]
            ]);

        $mostUsed = $response->json('data');
        expect($mostUsed)->toHaveCount(3);
    });

    it('can get recently used components', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        // Track recent usage
        $this->postJson('/api/components/bridge/track-usage', [
            'componentId' => $component->id,
            'context' => 'grapeJS'
        ]);

        $response = $this->getJson('/api/components/bridge/recently-used?limit=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'componentId',
                        'totalUsage',
                        'lastUsed'
                    ]
                ]
            ]);

        $recentlyUsed = $response->json('data');
        expect($recentlyUsed)->toHaveCount(1);
    });

    it('can get trending components', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        // Track multiple recent usages to make it trending
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/components/bridge/track-usage', [
                'componentId' => $component->id,
                'context' => 'grapeJS'
            ]);
        }

        $response = $this->getJson('/api/components/bridge/trending?limit=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'componentId',
                        'recentUsage',
                        'totalUsage'
                    ]
                ]
            ]);

        $trending = $response->json('data');
        expect($trending)->toHaveCount(1);
        expect($trending[0]['recentUsage'])->toBeGreaterThan(0);
    });

    it('can get comprehensive analytics data', function () {
        // Create components and track usage
        $components = Component::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        foreach ($components as $component) {
            $this->postJson('/api/components/bridge/track-usage', [
                'componentId' => $component->id,
                'context' => 'grapeJS'
            ]);
        }

        $response = $this->getJson('/api/components/bridge/analytics');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totalComponents',
                    'totalUsage',
                    'averageRating',
                    'mostUsedCategory',
                    'usageTrend' => [
                        '*' => [
                            'date',
                            'count'
                        ]
                    ]
                ]
            ]);

        $analytics = $response->json('data');
        expect($analytics['totalComponents'])->toBeGreaterThan(0);
        expect($analytics['totalUsage'])->toBeGreaterThan(0);
        expect($analytics['usageTrend'])->toHaveCount(7); // 7 days of trend data
    });

    it('can generate component documentation', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Alumni Hero Section',
            'description' => 'Hero section for alumni engagement',
            'is_active' => true
        ]);

        $response = $this->getJson("/api/components/bridge/documentation/{$component->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'title',
                    'description',
                    'examples' => [
                        '*' => [
                            'title',
                            'description',
                            'config'
                        ]
                    ],
                    'properties' => [
                        '*' => [
                            'name',
                            'type',
                            'description',
                            'required'
                        ]
                    ],
                    'tips',
                    'troubleshooting' => [
                        '*' => [
                            'issue',
                            'solution',
                            'severity'
                        ]
                    ]
                ]
            ]);

        $documentation = $response->json('data');
        expect($documentation['title'])->toBe($component->name);
        expect($documentation['examples'])->toBeArray();
        expect($documentation['properties'])->toBeArray();
        expect($documentation['tips'])->toBeArray();
    });

    it('can generate component tooltips', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Component',
            'description' => 'A test component for tooltips',
            'is_active' => true
        ]);

        $response = $this->getJson("/api/components/bridge/tooltip/{$component->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'tooltip'
                ]
            ]);

        $tooltip = $response->json('data.tooltip');
        expect($tooltip)->toContain($component->name);
        expect($tooltip)->toContain('Click to add to your page');
    });

    it('can validate GrapeJS compatibility', function () {
        $validComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Valid Component',
            'category' => 'hero',
            'config' => [
                'headline' => 'Test Headline',
                'audienceType' => 'individual',
                'ctaButtons' => [
                    ['text' => 'Get Started', 'url' => '/signup']
                ]
            ],
            'is_active' => true
        ]);

        $response = $this->getJson("/api/components/bridge/validate/{$validComponent->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'valid',
                    'errors'
                ]
            ]);

        $validation = $response->json('data');
        expect($validation['valid'])->toBeTrue();
        expect($validation['errors'])->toBeEmpty();
    });

    it('can detect invalid components for GrapeJS', function () {
        $invalidComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => '', // Invalid: empty name
            'category' => 'hero',
            'config' => [], // Invalid: missing required config
            'is_active' => true
        ]);

        $response = $this->getJson("/api/components/bridge/validate/{$invalidComponent->id}");

        $response->assertOk();

        $validation = $response->json('data');
        expect($validation['valid'])->toBeFalse();
        expect($validation['errors'])->not->toBeEmpty();
    });

    it('can get GrapeJS-ready component data', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'name' => 'Test Hero',
            'is_active' => true
        ]);

        $response = $this->getJson("/api/components/bridge/grapeJS-data/{$component->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'block' => [
                        'id',
                        'label',
                        'category',
                        'media',
                        'content',
                        'attributes'
                    ],
                    'documentation',
                    'usage',
                    'tooltip'
                ]
            ]);

        $data = $response->json('data');
        expect($data['block']['label'])->toBe($component->name);
        expect($data['tooltip'])->toBeString();
    });
});