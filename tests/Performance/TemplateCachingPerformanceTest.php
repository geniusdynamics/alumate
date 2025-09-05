<?php

namespace Tests\Performance;

use App\Models\Institution;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TemplateCachingPerformanceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create([
            'name' => 'Performance University',
            'domain' => 'performance-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'Performance Admin',
            'email' => 'admin@performance-university.com',
            'tenant_id' => $this->institution->id,
        ]);

        // Clear all cache before each test
        Cache::flush();
    }

    public function test_template_list_caching_performance()
    {
        // Create multiple templates
        Template::factory()->count(50)->create([
            'tenant_id' => $this->testUser->tenant_id,
        ]);

        // First request - should cache
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=50');

        $firstRequestTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Second request - should use cache
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=50');

        $secondRequestTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Cached request should be at least 50% faster
        $this->assertLessThan($firstRequestTime * 0.6, $secondRequestTime);

        // Verify cache headers or meta information
        $responseData = $response->json();
        $this->assertArrayHasKey('cached_at', $responseData['meta'] ?? []);
    }

    public function test_template_preview_caching_optimization()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'structure' => [
                'sections' => array_fill(0, 20, [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Complex Template with Large Content',
                        'subtitle' => str_repeat('This is a very long subtitle for performance testing. ', 50),
                        'content' => str_repeat('Large content block for caching optimization. ', 100),
                    ]
                ])
            ],
        ]);

        // First preview request - complex rendering
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $firstRenderTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Second preview request - should use cache
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $secondRenderTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Cached preview should be significantly faster
        $this->assertLessThan($firstRenderTime * 0.3, $secondRenderTime);
        $this->assertGreaterThan(0.001, $secondRenderTime); // But not instant (cache validation)

        // Verify preview is identical
        $firstPreview = json_encode($response->json('data'));
        $secondPreview = json_encode($this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview")
            ->json('data'));

        $this->assertEquals($firstPreview, $secondPreview);
    }

    public function test_cache_invalidation_on_template_updates()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Cache Invalidation Test',
        ]);

        // Load template to cache it
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}");

        $response->assertStatus(200);
        $originalName = $response->json('data.name');

        // Update template
        $updateData = [
            'name' => 'Updated Cache Invalidation Test',
        ];

        $response = $this->actingAs($this->testUser)
            ->putJson("/api/templates/{$template->id}", $updateData);

        $response->assertStatus(200);

        // Fetch template again - cache should be invalidated
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}");

        $response->assertStatus(200);
        $updatedName = $response->json('data.name');

        // Should reflect the update (cache invalidated)
        $this->assertNotEquals($originalName, $updatedName);
        $this->assertEquals('Updated Cache Invalidation Test', $updatedName);
    }

    public function test_query_result_caching_with_complex_filters()
    {
        // Create templates with various properties for filtering
        $templates = [
            ['category' => 'landing', 'audience_type' => 'individual', 'campaign_type' => 'marketing', 'is_premium' => false],
            ['category' => 'landing', 'audience_type' => 'individual', 'campaign_type' => 'onboarding', 'is_premium' => true],
            ['category' => 'form', 'audience_type' => 'institution', 'campaign_type' => 'leadership', 'is_premium' => false],
            ['category' => 'email', 'audience_type' => 'institution', 'campaign_type' => 'marketing', 'is_premium' => true],
            ['category' => 'social', 'audience_type' => 'general', 'campaign_type' => 'marketing', 'is_premium' => false],
        ];

        foreach ($templates as $templateData) {
            Template::factory()->create(array_merge($templateData, [
                'tenant_id' => $this->testUser->tenant_id,
            ]));
        }

        // Complex filtered query
        $filters = [
            'category' => 'landing',
            'audience_type' => 'individual',
            'is_premium' => 'false',
        ];

        // First filtered request
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?' . http_build_query($filters));

        $firstQueryTime = microtime(true) - $startTime;
        $response->assertStatus(200);
        $firstResults = $response->json('data.data');

        // Second filtered request - should use cache
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?' . http_build_query($filters));

        $secondQueryTime = microtime(true) - $startTime;
        $response->assertStatus(200);
        $secondResults = $response->json('data.data');

        // Results should be identical
        $this->assertEquals($firstResults, $secondResults);

        // Performance improvement should be significant
        $this->assertLessThan($firstQueryTime * 0.5, $secondQueryTime);
    }

    public function test_redis_cache_performance_vs_database()
    {
        // Create test templates
        $templateIds = Template::factory()->count(20)->create([
            'tenant_id' => $this->testUser->tenant_id,
        ])->pluck('id');

        // Database fetch baseline (without cache)
        Cache::flush(); // Ensure cold cache
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=20&cache=false'); // Assuming cache can be disabled

        $dbFetchTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Redis cached fetch
        Cache::flush(); // Start fresh
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=20'); // Should use cache

        $firstCachedTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Second cached fetch (warm cache)
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson('/api/templates?per_page=20');

        $cachedFetchTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Cached fetches should be significantly faster
        $this->assertLessThan($dbFetchTime * 0.7, $firstCachedTime);
        $this->assertLessThan($firstCachedTime * 0.3, $cachedFetchTime);
    }

    public function test_cache_performance_under_load()
    {
        // Simulate high load by creating many templates
        Template::factory()->count(100)->create([
            'tenant_id' => $this->testUser->tenant_id,
        ]);

        $responseTimes = [];
        $cacheHitTimes = [];

        // Multiple requests to simulate load
        for ($i = 0; $i < 10; $i++) {
            $startTime = microtime(true);
            $response = $this->actingAs($this->testUser)
                ->getJson('/api/templates?page=' . ($i + 1));

            $responseTime = microtime(true) - $startTime;
            $responseTimes[] = $responseTime;

            $response->assertStatus(200);

            // Second request to same page (cache hit)
            $startTime = microtime(true);
            $response = $this->actingAs($this->testUser)
                ->getJson('/api/templates?page=' . ($i + 1));

            $cacheHitTime = microtime(true) - $startTime;
            $cacheHitTimes[] = $cacheHitTime;

            $response->assertStatus(200);
        }

        // Calculate performance metrics
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $avgCacheHitTime = array_sum($cacheHitTimes) / count($cacheHitTimes);

        // Performance assertions
        $this->assertLessThan(1.0, $avgResponseTime); // Average response under 1 second
        $this->assertLessThan(0.1, $avgCacheHitTime); // Cache hits under 100ms
        $this->assertLessThan($avgResponseTime * 0.5, $avgCacheHitTime); // Cache at least 50% faster

        // Verify consistent performance (no significant degradation)
        $maxTime = max($cacheHitTimes);
        $minTime = min($cacheHitTimes);
        $this->assertLessThan(2.0, $maxTime / $minTime); // Performance variation under 2x
    }

    public function test_cache_memory_efficiency()
    {
        // Test with large dataset to verify memory efficiency
        $largeTemplateData = [
            'name' => 'Large Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'sections' => array_fill(0, 50, [
                    'type' => 'content',
                    'config' => [
                        'title' => str_repeat('Large content title ', 100),
                        'body' => str_repeat('Large content body with lots of text for memory testing. ', 200),
                        'media' => [
                            'images' => array_fill(0, 20, [
                                'url' => 'https://example.com/large-image-' . rand() . '.jpg',
                                'alt' => 'Large image for memory testing',
                            ])
                        ]
                    ]
                ])
            ],
        ];

        $template = Template::factory()->create(array_merge($largeTemplateData, [
            'tenant_id' => $this->testUser->tenant_id,
        ]));

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Generate preview for large template
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $response->assertStatus(200);

        $renderTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Performance assertions for large templates
        $this->assertLessThan(3.0, $renderTime); // Large template render under 3 seconds
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed); // Under 50MB memory usage

        // Test cached performance
        $startTime = microtime(true);
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/preview");

        $cachedRenderTime = microtime(true) - $startTime;
        $response->assertStatus(200);

        // Cached render should be much faster and use less memory
        $this->assertLessThan($renderTime * 0.2, $cachedRenderTime);
    }
}