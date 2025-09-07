<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TemplateCacheService;
use App\Models\Template;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new TemplateCacheService();
    }

    public function test_template_caching_layers()
    {
        $template = Template::factory()->create();

        // First call should cache the template
        $result1 = $this->cacheService->rememberTemplate($template->id, function () use ($template) {
            return $template;
        });

        $this->assertEquals($template->id, $result1->id);

        // Second call should return from cache
        $result2 = $this->cacheService->rememberTemplate($template->id, function () {
            // This shouldn't be called if cache is working
            $this->fail('Callback should not be called when cache hit');
        });

        $this->assertEquals($template->id, $result2->id);
    }

    public function test_popular_templates_caching()
    {
        $templates = Template::factory()->count(5)->create();

        $result1 = $this->cacheService->rememberPopularTemplates(function () use ($templates) {
            return $templates;
        });

        $this->assertCount(5, $result1);

        // Second call should return from cache
        $result2 = $this->cacheService->rememberPopularTemplates(function () {
            $this->fail('Callback should not be called when cache hit');
        });

        $this->assertCount(5, $result2);
    }

    public function test_search_results_caching()
    {
        $query = 'test query';
        $filters = ['category' => 'landing'];
        $results = collect(['result1', 'result2']);

        $result1 = $this->cacheService->rememberSearchResults($query, $filters, function () use ($results) {
            return $results;
        });

        $this->assertEquals($results, $result1);

        // Second call should return from cache
        $result2 = $this->cacheService->rememberSearchResults($query, $filters, function () {
            $this->fail('Callback should not be called when cache hit');
        });

        $this->assertEquals($results, $result2);
    }

    public function test_template_cache_invalidation()
    {
        $template = Template::factory()->create();

        // Cache the template
        $this->cacheService->rememberTemplate($template->id, function () use ($template) {
            return $template;
        });

        // Invalidate cache
        $this->cacheService->invalidateTemplate($template->id);

        // Next call should execute callback again
        $called = false;
        $result = $this->cacheService->rememberTemplate($template->id, function () use ($template, &$called) {
            $called = true;
            return $template;
        });

        $this->assertTrue($called);
        $this->assertEquals($template->id, $result->id);
    }

    public function test_popular_templates_invalidation()
    {
        $templates = Template::factory()->count(3)->create();

        // Cache popular templates
        $this->cacheService->rememberPopularTemplates(function () use ($templates) {
            return $templates;
        });

        // Invalidate popular templates cache
        $this->cacheService->invalidatePopularTemplates();

        // Next call should execute callback again
        $called = false;
        $result = $this->cacheService->rememberPopularTemplates(function () use ($templates, &$called) {
            $called = true;
            return $templates;
        });

        $this->assertTrue($called);
        $this->assertCount(3, $result);
    }

    public function test_cache_stats()
    {
        $stats = $this->cacheService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('redis_connected', $stats);
        $this->assertArrayHasKey('l1_cache_size', $stats);
    }

    public function test_template_metadata_caching()
    {
        $templateId = 123;
        $metadata = ['key' => 'value', 'performance' => ['score' => 95]];

        $result1 = $this->cacheService->rememberTemplateMetadata($templateId, function () use ($metadata) {
            return $metadata;
        });

        $this->assertEquals($metadata, $result1);

        // Second call should return from cache
        $result2 = $this->cacheService->rememberTemplateMetadata($templateId, function () {
            $this->fail('Callback should not be called when cache hit');
        });

        $this->assertEquals($metadata, $result2);
    }

    public function test_template_optimization_caching()
    {
        $templateId = 456;
        $optimization = ['minified' => true, 'compressed' => true];

        $result1 = $this->cacheService->rememberTemplateOptimization($templateId, function () use ($optimization) {
            return $optimization;
        });

        $this->assertEquals($optimization, $result1);

        // Second call should return from cache
        $result2 = $this->cacheService->rememberTemplateOptimization($templateId, function () {
            $this->fail('Callback should not be called when cache hit');
        });

        $this->assertEquals($optimization, $result2);
    }

    public function test_search_cache_invalidation()
    {
        $query = 'test search';
        $filters = ['category' => 'email'];

        // Cache search results
        $this->cacheService->rememberSearchResults($query, $filters, function () {
            return collect(['result']);
        });

        // Invalidate search cache
        $this->cacheService->invalidateSearchCache();

        // This test verifies the method exists and doesn't throw errors
        $this->assertTrue(true);
    }
}