<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('can create a statistics component with counter configuration', function () {
    $config = [
        'title' => 'Platform Statistics',
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
            'sources' => ['total_alumni', 'job_placements', 'success_rate'],
            'refreshInterval' => 30000
        ],
        'accessibility' => [
            'announceUpdates' => true,
            'respectReducedMotion' => true
        ]
    ];

    $statistics = [
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
            'icon' => 'briefcase',
            'trend' => [
                'direction' => 'up',
                'value' => 8,
                'label' => '% this quarter'
            ]
        ],
        [
            'id' => 'success_rate',
            'value' => 94,
            'label' => 'Success Rate',
            'format' => 'percentage',
            'type' => 'counter',
            'source' => 'api',
            'apiEndpoint' => '/api/analytics/metrics/success_rate',
            'icon' => 'chart-bar',
            'trend' => [
                'direction' => 'up',
                'value' => 3,
                'label' => '% improvement'
            ]
        ]
    ];

    $config = array_merge($config, ['statistics' => $statistics]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => $config,
        'is_active' => true
    ]);

    expect($component)->toBeInstanceOf(Component::class);
    expect($component->category)->toBe('statistics');
    expect($component->type)->toBe('counters');
    expect($component->config['displayType'])->toBe('counters');
    expect($component->config['statistics'])->toHaveCount(3);
    expect($component->config['animation']['enabled'])->toBeTrue();
    expect($component->config['realTimeData']['enabled'])->toBeTrue();
});

it('can create a statistics component with progress bars', function () {
    $config = [
        'title' => 'Goal Progress',
        'displayType' => 'progress',
        'layout' => 'column',
        'theme' => 'default',
        'progressSize' => 'md',
        'showLabels' => true,
        'showValues' => true,
        'showTargets' => true,
        'animation' => [
            'enabled' => true,
            'trigger' => 'scroll',
            'duration' => 1500
        ]
    ];

    $statistics = [
        [
            'id' => 'annual_placements',
            'value' => 75,
            'target' => 100,
            'label' => 'Annual Job Placements',
            'format' => 'percentage',
            'type' => 'progress',
            'color' => 'green',
            'milestones' => [
                ['value' => 25, 'label' => 'Q1 Target', 'showLabel' => true],
                ['value' => 50, 'label' => 'Q2 Target', 'showLabel' => true],
                ['value' => 75, 'label' => 'Q3 Target', 'showLabel' => true],
                ['value' => 100, 'label' => 'Annual Target', 'showLabel' => true]
            ]
        ],
        [
            'id' => 'satisfaction_score',
            'value' => 88,
            'target' => 95,
            'label' => 'User Satisfaction Score',
            'format' => 'percentage',
            'type' => 'progress',
            'color' => 'blue',
            'segments' => [
                ['threshold' => 60, 'width' => 60, 'color' => '#ef4444'],
                ['threshold' => 80, 'width' => 20, 'color' => '#f97316'],
                ['threshold' => 100, 'width' => 20, 'color' => '#22c55e']
            ]
        ]
    ];

    $config = array_merge($config, ['statistics' => $statistics]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'progress',
        'config' => $config,
        'is_active' => true
    ]);

    expect($component->config['displayType'])->toBe('progress');
    expect($component->config['statistics'])->toHaveCount(2);
    expect($component->config['statistics'][0]['milestones'])->toHaveCount(4);
    expect($component->config['statistics'][1]['segments'])->toHaveCount(3);
});

it('can create a statistics component with comparison charts', function () {
    $config = [
        'title' => 'Performance Comparison',
        'displayType' => 'charts',
        'layout' => 'grid',
        'theme' => 'modern',
        'chartSize' => 'lg',
        'animation' => [
            'enabled' => true,
            'trigger' => 'scroll',
            'duration' => 800,
            'stagger' => 100
        ]
    ];

    $charts = [
        [
            'id' => 'before_after_comparison',
            'type' => 'before-after',
            'title' => 'Platform Impact',
            'description' => 'Before and after implementing our platform',
            'format' => 'percentage',
            'showLegend' => true,
            'legend' => [
                ['label' => 'Before', 'color' => '#6b7280'],
                ['label' => 'After', 'color' => '#22c55e']
            ],
            'data' => [
                [
                    'id' => 'job_placement_rate',
                    'label' => 'Job Placement Rate',
                    'beforeValue' => 67,
                    'afterValue' => 94,
                    'value' => 94
                ],
                [
                    'id' => 'avg_time_to_hire',
                    'label' => 'Avg. Time to Hire (days)',
                    'beforeValue' => 180,
                    'afterValue' => 45,
                    'value' => 45
                ]
            ]
        ],
        [
            'id' => 'competitive_comparison',
            'type' => 'competitive',
            'title' => 'Market Position',
            'description' => 'How we compare to competitors',
            'format' => 'percentage',
            'showLegend' => false,
            'data' => [
                [
                    'id' => 'our_platform',
                    'label' => 'Our Platform',
                    'value' => 94,
                    'highlighted' => true,
                    'color' => '#3b82f6'
                ],
                [
                    'id' => 'competitor_a',
                    'label' => 'Competitor A',
                    'value' => 78,
                    'color' => '#6b7280'
                ],
                [
                    'id' => 'competitor_b',
                    'label' => 'Competitor B',
                    'value' => 82,
                    'color' => '#6b7280'
                ]
            ]
        ]
    ];

    $config = array_merge($config, ['charts' => $charts]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'charts',
        'config' => $config,
        'is_active' => true
    ]);

    expect($component->config['displayType'])->toBe('charts');
    expect($component->config['charts'])->toHaveCount(2);
    expect($component->config['charts'][0]['type'])->toBe('before-after');
    expect($component->config['charts'][1]['type'])->toBe('competitive');
    expect($component->config['charts'][0]['data'])->toHaveCount(2);
    expect($component->config['charts'][1]['data'])->toHaveCount(3);
});

it('can create a mixed statistics component with all display types', function () {
    $config = [
        'title' => 'Complete Dashboard',
        'description' => 'Comprehensive view of all platform metrics',
        'displayType' => 'mixed',
        'layout' => 'grid',
        'theme' => 'modern',
        'spacing' => 'spacious',
        'counterSize' => 'md',
        'progressSize' => 'md',
        'chartSize' => 'md',
        'showLabels' => true,
        'showValues' => true,
        'showTargets' => true,
        'gridColumns' => [
            'desktop' => 3,
            'tablet' => 2,
            'mobile' => 1
        ],
        'animation' => [
            'enabled' => true,
            'trigger' => 'scroll',
            'duration' => 1500,
            'delay' => 0,
            'stagger' => 150,
            'easing' => 'ease-out'
        ],
        'realTimeData' => [
            'enabled' => true,
            'sources' => ['total_alumni', 'active_users'],
            'refreshInterval' => 60000
        ],
        'accessibility' => [
            'ariaLabel' => 'Platform statistics dashboard',
            'announceUpdates' => true,
            'respectReducedMotion' => true
        ]
    ];

    $statistics = [
        // Counters
        [
            'id' => 'total_alumni',
            'value' => 15420,
            'label' => 'Total Alumni',
            'format' => 'number',
            'type' => 'counter',
            'source' => 'api',
            'icon' => 'users'
        ],
        [
            'id' => 'active_users',
            'value' => 3247,
            'label' => 'Active Users',
            'format' => 'number',
            'type' => 'counter',
            'source' => 'api',
            'icon' => 'user-group'
        ],
        // Progress bars
        [
            'id' => 'goal_progress',
            'value' => 78,
            'target' => 100,
            'label' => 'Annual Goal Progress',
            'format' => 'percentage',
            'type' => 'progress',
            'color' => 'green'
        ],
        // Chart data
        [
            'id' => 'satisfaction_chart',
            'label' => 'Satisfaction Metrics',
            'type' => 'chart',
            'chartType' => 'bar',
            'format' => 'percentage',
            'chartData' => [
                ['id' => 'overall', 'label' => 'Overall', 'value' => 94],
                ['id' => 'platform', 'label' => 'Platform', 'value' => 89],
                ['id' => 'support', 'label' => 'Support', 'value' => 96]
            ]
        ]
    ];

    $config = array_merge($config, ['statistics' => $statistics]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'mixed',
        'config' => $config,
        'is_active' => true
    ]);

    expect($component->config['displayType'])->toBe('mixed');
    expect($component->config['statistics'])->toHaveCount(4);
    
    $counterStats = collect($component->config['statistics'])->where('type', 'counter');
    $progressStats = collect($component->config['statistics'])->where('type', 'progress');
    $chartStats = collect($component->config['statistics'])->where('type', 'chart');
    
    expect($counterStats)->toHaveCount(2);
    expect($progressStats)->toHaveCount(1);
    expect($chartStats)->toHaveCount(1);
});

it('validates statistics component configuration', function () {
    $invalidConfig = [
        'displayType' => 'invalid_type',
        'statistics' => []
    ];

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => $invalidConfig,
        'is_active' => true
    ]);

    // Test that the component was created but with invalid config
    expect($component->config['displayType'])->toBe('invalid_type');
    expect($component->config['statistics'])->toBeEmpty();
});

it('handles error states in statistics configuration', function () {
    $config = [
        'title' => 'Statistics with Error Handling',
        'displayType' => 'counters',
        'errorHandling' => [
            'showErrors' => true,
            'errorMessage' => 'Failed to load statistics data',
            'allowRetry' => true
        ],
        'dataRefresh' => [
            'enabled' => true,
            'interval' => 30000,
            'retryAttempts' => 3
        ]
    ];

    $statistics = [
        [
            'id' => 'api_stat',
            'value' => 0,
            'label' => 'API Statistic',
            'format' => 'number',
            'type' => 'counter',
            'source' => 'api',
            'apiEndpoint' => '/api/analytics/metrics/test'
        ]
    ];

    $config = array_merge($config, ['statistics' => $statistics]);

    $component = Component::factory()->create([
        'tenant_id' => $this->tenant->id,
        'category' => 'statistics',
        'type' => 'counters',
        'config' => $config,
        'is_active' => true
    ]);

    expect($component->config['errorHandling']['showErrors'])->toBeTrue();
    expect($component->config['errorHandling']['allowRetry'])->toBeTrue();
    expect($component->config['dataRefresh']['retryAttempts'])->toBe(3);
});