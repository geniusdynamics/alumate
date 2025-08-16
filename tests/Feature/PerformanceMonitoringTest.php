<?php

use App\Models\User;
use App\Services\PerformanceOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super-admin');

    $this->regularUser = User::factory()->create();

    $this->performanceService = app(PerformanceOptimizationService::class);
});

it('allows super admin to access performance monitoring page', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get('/admin/performance');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Admin/PerformanceMonitoring'));
});

it('denies regular users access to performance monitoring page', function () {
    $response = $this->actingAs($this->regularUser)
        ->get('/admin/performance');

    $response->assertForbidden();
});

it('returns performance metrics via API', function () {
    $response = $this->actingAs($this->superAdmin)
        ->getJson('/api/admin/performance/metrics');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'success',
        'metrics' => [
            'cache_hit_rate',
            'average_query_time',
            'active_connections',
            'memory_usage',
            'redis_memory_usage',
            'slow_queries_count',
            'timeline_generation_time',
        ],
        'budgets',
        'alerts',
    ]);
});

it('allows clearing performance caches', function () {
    // Set up some test cache data
    Cache::put('social_graph:connections:1', [2, 3, 4], now()->addHour());
    Cache::put('query_cache:timeline_segments:1', ['test' => 'data'], now()->addHour());

    $response = $this->actingAs($this->superAdmin)
        ->postJson('/api/admin/performance/clear-caches');

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Performance caches cleared successfully',
    ]);
});

it('allows optimizing social graph caching', function () {
    $response = $this->actingAs($this->superAdmin)
        ->postJson('/api/admin/performance/optimize-social-graph');

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Social graph caching optimized successfully',
    ]);
});

it('allows optimizing timeline queries', function () {
    $response = $this->actingAs($this->superAdmin)
        ->postJson('/api/admin/performance/optimize-timeline');

    $response->assertSuccessful();
    $response->assertJson([
        'success' => true,
        'message' => 'Timeline queries optimized successfully',
    ]);
});

it('denies regular users access to performance API endpoints', function () {
    $endpoints = [
        'GET' => '/api/admin/performance/metrics',
        'POST' => '/api/admin/performance/clear-caches',
        'POST' => '/api/admin/performance/optimize-social-graph',
        'POST' => '/api/admin/performance/optimize-timeline',
    ];

    foreach ($endpoints as $method => $endpoint) {
        $response = $this->actingAs($this->regularUser)
            ->json($method, $endpoint);

        // Should be forbidden due to role middleware
        $response->assertStatus(403);
    }
});

it('calculates performance budget status correctly', function () {
    $budgets = $this->performanceService->getPerformanceBudgetStatus();

    expect($budgets)->toBeArray();
    expect($budgets)->toHaveKeys([
        'timeline_generation',
        'cache_hit_rate',
        'memory_usage_mb',
        'active_connections',
    ]);

    foreach ($budgets as $metric => $budget) {
        expect($budget)->toHaveKeys(['budget', 'current', 'status', 'percentage']);
        expect($budget['status'])->toBeIn(['within_budget', 'approaching_limit', 'over_budget']);
        expect($budget['percentage'])->toBeNumeric();
    }
});

it('monitors performance metrics correctly', function () {
    $metrics = $this->performanceService->monitorPerformanceMetrics();

    expect($metrics)->toBeArray();
    expect($metrics)->toHaveKeys([
        'cache_hit_rate',
        'average_query_time',
        'active_connections',
        'memory_usage',
        'redis_memory_usage',
        'slow_queries_count',
        'timeline_generation_time',
    ]);

    expect($metrics['cache_hit_rate'])->toBeNumeric();
    expect($metrics['memory_usage'])->toBeNumeric();
    expect($metrics['redis_memory_usage'])->toBeArray();
});

it('handles performance optimization errors gracefully', function () {
    // Mock a service that throws an exception
    $this->mock(PerformanceOptimizationService::class, function ($mock) {
        $mock->shouldReceive('monitorPerformanceMetrics')
            ->andThrow(new \Exception('Test error'));
    });

    $response = $this->actingAs($this->superAdmin)
        ->getJson('/api/admin/performance/metrics');

    $response->assertStatus(500);
    $response->assertJson([
        'success' => false,
        'message' => 'Failed to retrieve performance metrics',
    ]);
});
