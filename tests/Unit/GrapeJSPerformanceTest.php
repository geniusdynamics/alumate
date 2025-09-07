<?php

use App\Models\Component;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    $this->actingAs($this->user);
    
    Cache::flush();
    DB::enableQueryLog();
});

describe('GrapeJS Performance Testing', function () {
    it('measures component block generation performance', function () {
        $components = Component::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        foreach ($components as $component) {
            $response = $this->getJson("/api/components/{$component->id}/grapejs-block");
            $response->assertOk();
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $averageTimePerComponent = $executionTime / 20;

        expect($averageTimePerComponent)->toBeLessThan(50); // Should be under 50ms per component
        expect($memoryUsage)->toBeLessThan(10 * 1024 * 1024); // Should use less than 10MB
        
        $queryCount = count(DB::getQueryLog());
        expect($queryCount)->toBeLessThan(40); // Should not exceed 2 queries per component on average
    });

    it('benchmarks serialization performance with large datasets', function () {
        // Create components with large configurations
        $largeComponents = [];
        for ($i = 0; $i < 10; $i++) {
            $largeComponents[] = Component::factory()->create([
                'tenant_id' => $this->tenant->id,
                'category' => 'forms',
                'config' => [
                    'title' => 'Large Form Component',
                    'fields' => array_fill(0, 50, [ // 50 fields
                        'type' => 'text',
                        'name' => 'field_' . rand(1, 1000),
                        'label' => 'Test Field ' . rand(1, 1000),
                        'validation' => [
                            'required' => rand(0, 1) === 1,
                            'min_length' => rand(1, 10),
                            'max_length' => rand(50, 200)
                        ]
                    ])
                ]
            ]);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $this->postJson('/api/components/serialize-to-grapejs', [
            'component_ids' => collect($largeComponents)->pluck('id')->toArray(),
            'include_styles' => true,
            'include_assets' => true
        ]);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $response->assertOk();

        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsage = $endMemory - $startMemory;

        expect($executionTime)->toBeLessThan(2000); // Should complete in under 2 seconds
        expect($memoryUsage)->toBeLessThan(50 * 1024 * 1024); // Should use less than 50MB

        $serializedData = $response->json('data');
        expect($serializedData['components'])->toHaveCount(10);
    });

    it('tests concurrent component loading performance', function () {
        $components = Component::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        $startTime = microtime(true);

        // Simulate concurrent requests by making multiple API calls
        $responses = [];
        foreach ($components as $component) {
            $responses[] = $this->getJson("/api/components/{$component->id}/grapejs-block");
        }

        $endTime = microtime(true);

        // Verify all requests succeeded
        foreach ($responses as $response) {
            $response->assertOk();
        }

        $totalTime = ($endTime - $startTime) * 1000;
        $averageTime = $totalTime / 5;

        expect($averageTime)->toBeLessThan(100); // Average should be under 100ms
        expect($totalTime)->toBeLessThan(300); // Total should be under 300ms
    });

    it('measures memory usage during component operations', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'type' => 'image-gallery',
                'images' => array_fill(0, 100, [ // 100 images
                    'src' => '/images/test-' . rand(1, 1000) . '.jpg',
                    'alt' => 'Test image ' . rand(1, 1000),
                    'caption' => 'Test caption ' . str_repeat('x', 100)
                ])
            ]
        ]);

        $initialMemory = memory_get_usage();

        // Perform various operations
        $operations = [
            'block_generation' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-block"),
            'serialization' => fn() => $this->postJson('/api/components/serialize-to-grapejs', [
                'component_ids' => [$component->id]
            ]),
            'trait_validation' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-traits/validate"),
            'compatibility_check' => fn() => $this->getJson("/api/components/{$component->id}/grapejs-compatibility")
        ];

        $memoryUsage = [];
        foreach ($operations as $name => $operation) {
            $beforeMemory = memory_get_usage();
            $response = $operation();
            $afterMemory = memory_get_usage();
            
            $response->assertOk();
            $memoryUsage[$name] = $afterMemory - $beforeMemory;
        }

        $totalMemoryIncrease = memory_get_usage() - $initialMemory;

        expect($totalMemoryIncrease)->toBeLessThan(20 * 1024 * 1024); // Should use less than 20MB total
        
        foreach ($memoryUsage as $operation => $usage) {
            expect($usage)->toBeLessThan(5 * 1024 * 1024); // Each operation should use less than 5MB
        }
    });

    it('benchmarks database query performance', function () {
        // Create components with relationships
        $components = Component::factory()->count(15)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        DB::flushQueryLog();

        $startTime = microtime(true);

        // Test bulk operations
        $response = $this->postJson('/api/components/grapejs-performance-test', [
            'component_ids' => $components->pluck('id')->toArray(),
            'test_type' => 'loading',
            'iterations' => 3
        ]);

        $endTime = microtime(true);

        $response->assertOk();

        $queries = DB::getQueryLog();
        $executionTime = ($endTime - $startTime) * 1000;

        expect(count($queries))->toBeLessThan(50); // Should not generate excessive queries
        expect($executionTime)->toBeLessThan(1000); // Should complete in under 1 second

        // Check for N+1 query problems
        $selectQueries = collect($queries)->filter(function ($query) {
            return str_starts_with(strtoupper(trim($query['query'])), 'SELECT');
        });

        expect($selectQueries->count())->toBeLessThan(20); // Should not have too many SELECT queries
    });

    it('tests caching performance improvements', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'is_active' => true
        ]);

        // First request (no cache)
        Cache::flush();
        $startTime = microtime(true);
        $response1 = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $firstRequestTime = (microtime(true) - $startTime) * 1000;

        $response1->assertOk();

        // Second request (with cache)
        $startTime = microtime(true);
        $response2 = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $secondRequestTime = (microtime(true) - $startTime) * 1000;

        $response2->assertOk();

        // Cache should improve performance
        expect($secondRequestTime)->toBeLessThan($firstRequestTime);
        expect($secondRequestTime)->toBeLessThan(20); // Cached request should be very fast

        // Verify responses are identical
        expect($response1->json())->toEqual($response2->json());
    });

    it('measures component rendering performance with complex configurations', function () {
        $complexComponent = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'hero',
            'config' => [
                'layout' => 'complex',
                'sections' => array_fill(0, 10, [
                    'type' => 'content_block',
                    'elements' => array_fill(0, 5, [
                        'type' => 'text',
                        'content' => str_repeat('Complex content ', 50),
                        'styling' => [
                            'font_size' => rand(12, 24),
                            'color' => '#' . dechex(rand(0, 16777215)),
                            'margin' => rand(5, 20) . 'px'
                        ]
                    ])
                ]),
                'animations' => array_fill(0, 20, [
                    'type' => 'fade_in',
                    'duration' => rand(500, 2000),
                    'delay' => rand(0, 1000)
                ]),
                'responsive' => [
                    'desktop' => ['columns' => 4, 'spacing' => '20px'],
                    'tablet' => ['columns' => 2, 'spacing' => '15px'],
                    'mobile' => ['columns' => 1, 'spacing' => '10px']
                ]
            ]
        ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $response = $this->getJson("/api/components/{$complexComponent->id}/grapejs-block");

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $response->assertOk();

        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsage = $endMemory - $startMemory;

        expect($executionTime)->toBeLessThan(200); // Should handle complexity in under 200ms
        expect($memoryUsage)->toBeLessThan(10 * 1024 * 1024); // Should use less than 10MB

        $blockData = $response->json('data.block');
        expect($blockData)->toHaveKey('components');
        expect($blockData['components'])->toBeArray();
    });

    it('tests performance degradation with increasing component count', function () {
        $componentCounts = [5, 10, 20, 50];
        $performanceResults = [];

        foreach ($componentCounts as $count) {
            $components = Component::factory()->count($count)->create([
                'tenant_id' => $this->tenant->id,
                'is_active' => true
            ]);

            $startTime = microtime(true);

            $response = $this->postJson('/api/components/serialize-to-grapejs', [
                'component_ids' => $components->pluck('id')->toArray()
            ]);

            $endTime = microtime(true);

            $response->assertOk();

            $executionTime = ($endTime - $startTime) * 1000;
            $performanceResults[$count] = [
                'time' => $executionTime,
                'time_per_component' => $executionTime / $count
            ];

            // Clean up for next iteration
            Component::whereIn('id', $components->pluck('id'))->delete();
        }

        // Performance should scale reasonably
        expect($performanceResults[5]['time_per_component'])->toBeLessThan(50);
        expect($performanceResults[10]['time_per_component'])->toBeLessThan(60);
        expect($performanceResults[20]['time_per_component'])->toBeLessThan(70);
        expect($performanceResults[50]['time_per_component'])->toBeLessThan(100);

        // Total time should not grow exponentially
        $timeRatio = $performanceResults[50]['time'] / $performanceResults[5]['time'];
        expect($timeRatio)->toBeLessThan(15); // Should not be more than 15x slower for 10x components
    });

    it('measures API response time consistency', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'forms',
            'is_active' => true
        ]);

        $responseTimes = [];
        $iterations = 10;

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $response = $this->getJson("/api/components/{$component->id}/grapejs-block");
            
            $endTime = microtime(true);
            
            $response->assertOk();
            $responseTimes[] = ($endTime - $startTime) * 1000;
        }

        $averageTime = array_sum($responseTimes) / count($responseTimes);
        $minTime = min($responseTimes);
        $maxTime = max($responseTimes);
        $variance = $maxTime - $minTime;

        expect($averageTime)->toBeLessThan(100); // Average should be under 100ms
        expect($variance)->toBeLessThan(50); // Variance should be under 50ms
        expect($maxTime)->toBeLessThan(150); // No single request should exceed 150ms

        // Calculate standard deviation
        $squaredDifferences = array_map(function ($time) use ($averageTime) {
            return pow($time - $averageTime, 2);
        }, $responseTimes);
        
        $standardDeviation = sqrt(array_sum($squaredDifferences) / count($squaredDifferences));
        expect($standardDeviation)->toBeLessThan(20); // Standard deviation should be low
    });
});

describe('GrapeJS Performance Optimization Testing', function () {
    it('validates lazy loading performance benefits', function () {
        // Create components with large media configurations
        $mediaComponents = Component::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'media',
            'config' => [
                'type' => 'image-gallery',
                'optimization' => [
                    'lazyLoading' => true,
                    'webpSupport' => true
                ],
                'images' => array_fill(0, 20, [
                    'src' => '/images/large-image-' . rand(1, 1000) . '.jpg',
                    'alt' => 'Large test image'
                ])
            ]
        ]);

        $startTime = microtime(true);

        foreach ($mediaComponents as $component) {
            $response = $this->getJson("/api/components/{$component->id}/grapejs-block");
            $response->assertOk();
            
            $blockData = $response->json('data.block');
            expect($blockData['attributes'])->toHaveKey('data-lazy-loading', true);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        expect($executionTime)->toBeLessThan(500); // Should handle lazy loading efficiently
    });

    it('tests component bundling performance', function () {
        $components = Component::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true
        ]);

        // Test individual requests
        $startTime = microtime(true);
        foreach ($components as $component) {
            $this->getJson("/api/components/{$component->id}/grapejs-block");
        }
        $individualTime = (microtime(true) - $startTime) * 1000;

        // Test bundled request
        $startTime = microtime(true);
        $response = $this->postJson('/api/components/grapejs-blocks/batch', [
            'component_ids' => $components->pluck('id')->toArray()
        ]);
        $bundledTime = (microtime(true) - $startTime) * 1000;

        $response->assertOk();

        // Bundled request should be more efficient
        expect($bundledTime)->toBeLessThan($individualTime);
        expect($bundledTime)->toBeLessThan(200); // Should complete in under 200ms

        $batchData = $response->json('data');
        expect($batchData['blocks'])->toHaveCount(10);
    });

    it('validates caching strategy effectiveness', function () {
        $component = Component::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category' => 'statistics',
            'config' => [
                'statistics' => array_fill(0, 10, [
                    'label' => 'Test Stat',
                    'value' => rand(1000, 50000)
                ])
            ]
        ]);

        // Clear cache and measure first request
        Cache::flush();
        $startTime = microtime(true);
        $response1 = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $uncachedTime = (microtime(true) - $startTime) * 1000;

        $response1->assertOk();

        // Measure cached request
        $startTime = microtime(true);
        $response2 = $this->getJson("/api/components/{$component->id}/grapejs-block");
        $cachedTime = (microtime(true) - $startTime) * 1000;

        $response2->assertOk();

        // Cache should provide significant improvement
        $improvementRatio = $uncachedTime / $cachedTime;
        expect($improvementRatio)->toBeGreaterThan(2); // At least 2x improvement
        expect($cachedTime)->toBeLessThan(10); // Cached should be very fast
    });
});