<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('can fetch platform statistics via API', function () {
    // Create some test data that would be used for statistics
    // This would typically come from your actual platform data
    
    $response = $this->getJson('/api/analytics/metrics/total_alumni');
    
    $response->assertOk()
        ->assertJsonStructure([
            'value',
            'label',
            'last_updated'
        ]);
});

it('can fetch multiple statistics metrics', function () {
    $metrics = ['total_alumni', 'job_placements', 'success_rate', 'active_users'];
    
    foreach ($metrics as $metric) {
        $response = $this->getJson("/api/analytics/metrics/{$metric}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'value',
                'label',
                'last_updated'
            ]);
    }
});

it('can create a statistics component via API', function () {
    $statisticsData = [
        'name' => 'Platform Statistics',
        'category' => 'statistics',
        'type' => 'counters',
        'description' => 'Key platform metrics',
        'config' => [
            'title' => 'Platform Overview',
            'description' => 'Key metrics for our alumni platform',
            'displayType' => 'counters',
            'layout' => 'grid',
            'theme' => 'modern',
            'spacing' => 'default',
            'counterSize' => 'lg',
            'showLabels' => true,
            'showValues' => true,
            'gridColumns' => [
                'desktop' => 4,
                'tablet' => 2,
                'mobile' => 1
            ],
            'animation' => [
                'enabled' => true,
                'trigger' => 'scroll',
                'duration' => 2000,
                'delay' => 0,
                'stagger' => 100,
                'easing' => 'ease-out'
            ],
            'realTimeData' => [
                'enabled' => true,
                'sources' => ['total_alumni', 'job_placements'],
                'refreshInterval' => 30000
            ],
            'accessibility' => [
                'announceUpdates' => true,
                'respectReducedMotion' => true
            ],
            'statistics' => [
                [
                    'id' => 'total_alumni',
                    'value' => 15420,
                    'label' => 'Total Alumni',
                    'format' => 'number',
                    'type' => 'counter',
                    'source' => 'api',
                    'apiEndpoint' => '/api/analytics/metrics/total_alumni',
                    'icon' => 'users',
                    'trend' => [
                        'direction' => 'up',
                        'value' => 12,
                        'label' => '% this month'
                    ]
                ],
                [
                    'id' => 'job_placements',
                    'value' => 8934,
                    'label' => 'Job Placements',
                    'format' => 'number',
                    'type' => 'counter',
                    'source' => 'api',
                    'apiEndpoint' => '/api/analytics/metrics/job_placements',
                    'icon' => 'briefcase'
                ]
            ]
        ],
        'is_active' => true
    ];

    $response = $this->postJson('/api/components', $statisticsData);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'category',
                'type',
                'config',
                'is_active',
                'created_at',
                'updated_at'
            ]
        ]);

    expect($response->json('data.category'))->toBe('statistics');
    expect($response->json('data.config.displayType'))->toBe('counters');
    expect($response->json('data.config.statistics'))->toHaveCount(2);
});

it('can update a statistics component configuration', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => [
            'displayType' => 'counters',
            'statistics' => [
                [
                    'id' => 'test_stat',
                    'value' => 100,
                    'label' => 'Test Statistic',
                    'format' => 'number',
                    'type' => 'counter'
                ]
            ]
        ]
    ]);

    $updatedConfig = [
        'config' => [
            'displayType' => 'mixed',
            'title' => 'Updated Statistics Dashboard',
            'statistics' => [
                [
                    'id' => 'test_stat',
                    'value' => 150,
                    'label' => 'Updated Test Statistic',
                    'format' => 'number',
                    'type' => 'counter',
                    'trend' => [
                        'direction' => 'up',
                        'value' => 50,
                        'label' => '% increase'
                    ]
                ],
                [
                    'id' => 'progress_stat',
                    'value' => 75,
                    'target' => 100,
                    'label' => 'Progress Metric',
                    'format' => 'percentage',
                    'type' => 'progress',
                    'color' => 'green'
                ]
            ]
        ]
    ];

    $response = $this->putJson("/api/components/{$component->id}", $updatedConfig);

    $response->assertOk()
        ->assertJsonPath('data.config.displayType', 'mixed')
        ->assertJsonPath('data.config.title', 'Updated Statistics Dashboard');

    expect($response->json('data.config.statistics'))->toHaveCount(2);
    expect($response->json('data.config.statistics.0.trend.direction'))->toBe('up');
});

it('can fetch statistics component with real-time data integration', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => [
            'displayType' => 'counters',
            'realTimeData' => [
                'enabled' => true,
                'sources' => ['total_alumni', 'active_users'],
                'refreshInterval' => 30000
            ],
            'statistics' => [
                [
                    'id' => 'total_alumni',
                    'value' => 15000,
                    'label' => 'Total Alumni',
                    'format' => 'number',
                    'type' => 'counter',
                    'source' => 'api',
                    'apiEndpoint' => '/api/analytics/metrics/total_alumni'
                ]
            ]
        ]
    ]);

    $response = $this->getJson("/api/components/{$component->id}");

    $response->assertOk()
        ->assertJsonPath('data.config.realTimeData.enabled', true)
        ->assertJsonPath('data.config.realTimeData.sources.0', 'total_alumni');
});

it('validates statistics component data on creation', function () {
    $invalidData = [
        'name' => 'Invalid Statistics',
        'category' => 'statistics',
        'type' => 'counters',
        'config' => [
            'displayType' => 'invalid_type', // Invalid display type
            'statistics' => [] // Empty statistics array
        ]
    ];

    $response = $this->postJson('/api/components', $invalidData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['config']);
});

it('can fetch comparison chart data for statistics', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'charts',
        'config' => [
            'displayType' => 'charts',
            'charts' => [
                [
                    'id' => 'performance_comparison',
                    'type' => 'before-after',
                    'title' => 'Platform Impact',
                    'format' => 'percentage',
                    'data' => [
                        [
                            'id' => 'job_placement_rate',
                            'label' => 'Job Placement Rate',
                            'beforeValue' => 67,
                            'afterValue' => 94,
                            'value' => 94
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $response = $this->getJson("/api/components/{$component->id}");

    $response->assertOk()
        ->assertJsonPath('data.config.charts.0.type', 'before-after')
        ->assertJsonPath('data.config.charts.0.data.0.beforeValue', 67)
        ->assertJsonPath('data.config.charts.0.data.0.afterValue', 94);
});

it('can handle error states in statistics API responses', function () {
    // Test with a non-existent metric endpoint
    $response = $this->getJson('/api/analytics/metrics/non_existent_metric');

    $response->assertNotFound()
        ->assertJson([
            'message' => 'Metric not found'
        ]);
});

it('respects tenant isolation for statistics components', function () {
    $otherTenant = Tenant::factory()->create();
    $otherComponent = Component::factory()->create([
        'tenant_id' => $otherTenant->id,
        'category' => 'statistics',
        'type' => 'counters'
    ]);

    // Try to access component from different tenant
    $response = $this->getJson("/api/components/{$otherComponent->id}");

    $response->assertNotFound();
});

it('can track analytics events for statistics components', function () {
    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => [
            'displayType' => 'counters',
            'statistics' => [
                [
                    'id' => 'tracked_stat',
                    'value' => 1000,
                    'label' => 'Tracked Statistic',
                    'format' => 'number',
                    'type' => 'counter'
                ]
            ]
        ]
    ]);

    // Simulate analytics event tracking
    $analyticsData = [
        'event' => 'statistics_view',
        'component_id' => $component->id,
        'properties' => [
            'display_type' => 'counters',
            'statistics_count' => 1,
            'theme' => 'default'
        ]
    ];

    $response = $this->postJson('/api/analytics/events', $analyticsData);

    $response->assertCreated();
});