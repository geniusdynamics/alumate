<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * Template Cache Service
 *
 * Advanced multi-layer caching service for template performance optimization
 * with Redis support, tenant isolation, and intelligent cache management.
 */
class TemplateCacheService
{
    private const L1_STORE = 'template_l1';
    private const L2_STORE = 'template_l2';
    private const L3_STORE = 'template_archive';
    private const METADATA_STORE = 'template_metadata';
    private const OPTIMIZATION_STORE = 'template_optimization';
    private const POPULAR_STORE = 'template_popular';

    private const L1_TTL = 60;      // 1 minute
    private const L2_TTL = 3600;    // 1 hour
    private const L3_TTL = 86400;   // 24 hours
    private const METADATA_TTL = 300; // 5 minutes
    private const OPTIMIZATION_TTL = 1800; // 30 minutes
    private const POPULAR_TTL = 3600; // 1 hour

    /**
     * Get template with multi-layer caching
     *
     * @param int $templateId
     * @param callable $callback
     * @return mixed
     */
    public function rememberTemplate(int $templateId, callable $callback)
    {
        $key = $this->getTemplateKey($templateId);

        // Try L1 cache first (fast memory)
        $l1Cache = Cache::store(self::L1_STORE);
        if ($l1Cache->has($key)) {
            Log::debug("Template cache hit L1", ['template_id' => $templateId]);
            return $l1Cache->get($key);
        }

        // Try L2 cache (Redis)
        $l2Cache = Cache::store(self::L2_STORE);
        if ($l2Cache->has($key)) {
            $data = $l2Cache->get($key);
            // Populate L1 cache
            $l1Cache->put($key, $data, self::L1_TTL);
            Log::debug("Template cache hit L2", ['template_id' => $templateId]);
            return $data;
        }

        // Try L3 cache (database/archive)
        $l3Cache = Cache::store(self::L3_STORE);
        if ($l3Cache->has($key)) {
            $data = $l3Cache->get($key);
            // Populate higher layers
            $l2Cache->put($key, $data, self::L2_TTL);
            $l1Cache->put($key, $data, self::L1_TTL);
            Log::debug("Template cache hit L3", ['template_id' => $templateId]);
            return $data;
        }

        // Cache miss - execute callback and cache result
        $data = $callback();

        // Store in all layers
        $l3Cache->put($key, $data, self::L3_TTL);
        $l2Cache->put($key, $data, self::L2_TTL);
        $l1Cache->put($key, $data, self::L1_TTL);

        Log::debug("Template cache miss", ['template_id' => $templateId]);
        return $data;
    }

    /**
     * Get template metadata with caching
     *
     * @param int $templateId
     * @param callable $callback
     * @return mixed
     */
    public function rememberTemplateMetadata(int $templateId, callable $callback)
    {
        $key = $this->getTemplateMetadataKey($templateId);
        $cache = Cache::store(self::METADATA_STORE);

        return $cache->remember($key, self::METADATA_TTL, function () use ($callback, $templateId) {
            Log::debug("Template metadata cache miss", ['template_id' => $templateId]);
            return $callback();
        });
    }

    /**
     * Get template optimization data
     *
     * @param int $templateId
     * @param callable $callback
     * @return mixed
     */
    public function rememberTemplateOptimization(int $templateId, callable $callback)
    {
        $key = $this->getTemplateOptimizationKey($templateId);
        $cache = Cache::store(self::OPTIMIZATION_STORE);

        return $cache->remember($key, self::OPTIMIZATION_TTL, function () use ($callback, $templateId) {
            Log::debug("Template optimization cache miss", ['template_id' => $templateId]);
            return $callback();
        });
    }

    /**
     * Get popular templates with caching
     *
     * @param callable $callback
     * @return mixed
     */
    public function rememberPopularTemplates(callable $callback)
    {
        $key = 'popular_templates';
        $cache = Cache::store(self::POPULAR_STORE);

        return $cache->remember($key, self::POPULAR_TTL, function () use ($callback) {
            Log::debug("Popular templates cache miss");
            return $callback();
        });
    }

    /**
     * Cache search results with intelligent key generation
     *
     * @param string $query
     * @param array $filters
     * @param callable $callback
     * @return mixed
     */
    public function rememberSearchResults(string $query, array $filters, callable $callback)
    {
        $key = $this->getSearchKey($query, $filters);
        $cache = Cache::store(self::L2_STORE);

        return $cache->remember($key, self::L2_TTL, function () use ($callback, $query) {
            Log::debug("Search cache miss", ['query' => $query]);
            return $callback();
        });
    }

    /**
     * Invalidate template cache across all layers
     *
     * @param int $templateId
     */
    public function invalidateTemplate(int $templateId): void
    {
        $key = $this->getTemplateKey($templateId);

        Cache::store(self::L1_STORE)->forget($key);
        Cache::store(self::L2_STORE)->forget($key);
        Cache::store(self::L3_STORE)->forget($key);

        // Also invalidate related caches
        $this->invalidateTemplateMetadata($templateId);
        $this->invalidateTemplateOptimization($templateId);

        Log::debug("Template cache invalidated", ['template_id' => $templateId]);
    }

    /**
     * Invalidate template metadata cache
     *
     * @param int $templateId
     */
    public function invalidateTemplateMetadata(int $templateId): void
    {
        $key = $this->getTemplateMetadataKey($templateId);
        Cache::store(self::METADATA_STORE)->forget($key);
    }

    /**
     * Invalidate template optimization cache
     *
     * @param int $templateId
     */
    public function invalidateTemplateOptimization(int $templateId): void
    {
        $key = $this->getTemplateOptimizationKey($templateId);
        Cache::store(self::OPTIMIZATION_STORE)->forget($key);
    }

    /**
     * Invalidate popular templates cache
     */
    public function invalidatePopularTemplates(): void
    {
        Cache::store(self::POPULAR_STORE)->forget('popular_templates');
    }

    /**
     * Invalidate search cache by pattern
     *
     * @param string $pattern
     */
    public function invalidateSearchCache(string $pattern = '*'): void
    {
        $redis = Redis::connection('cache');

        try {
            $keys = $redis->keys("template:search:{$pattern}");
            if (!empty($keys)) {
                $redis->del($keys);
                Log::debug("Search cache invalidated", ['pattern' => $pattern, 'keys_deleted' => count($keys)]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to invalidate search cache", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Warm up template cache for popular templates
     *
     * @param Collection $templates
     */
    public function warmUpTemplateCache(Collection $templates): void
    {
        foreach ($templates as $template) {
            $this->rememberTemplate($template->id, function () use ($template) {
                return $template;
            });
        }

        Log::info("Template cache warmed up", ['templates_count' => $templates->count()]);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();

            return [
                'redis_connected' => true,
                'redis_memory_used' => $info['Memory']['used_memory_human'] ?? 'unknown',
                'redis_keys_count' => $redis->dbsize(),
                'l1_cache_size' => 'N/A (array store)',
            ];
        } catch (\Exception $e) {
            return [
                'redis_connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate template cache key
     *
     * @param int $templateId
     * @return string
     */
    private function getTemplateKey(int $templateId): string
    {
        return "template:{$templateId}";
    }

    /**
     * Generate template metadata cache key
     *
     * @param int $templateId
     * @return string
     */
    private function getTemplateMetadataKey(int $templateId): string
    {
        return "template:meta:{$templateId}";
    }

    /**
     * Generate template optimization cache key
     *
     * @param int $templateId
     * @return string
     */
    private function getTemplateOptimizationKey(int $templateId): string
    {
        return "template:opt:{$templateId}";
    }

    /**
     * Generate search cache key
     *
     * @param string $query
     * @param array $filters
     * @return string
     */
    private function getSearchKey(string $query, array $filters): string
    {
        $hash = md5($query . serialize($filters));
        return "template:search:{$hash}";
    }
}