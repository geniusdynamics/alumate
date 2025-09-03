<?php

namespace App\Services;

use App\Models\Component;
use App\Models\ComponentInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Component Caching Service for performance optimization
 *
 * This service provides multi-layer caching strategies specifically
 * for component library system components and their renderings.
 */
class ComponentCachingService extends CacheService
{
    const COMPONENT_CACHE_TTL = 1800; // 30 minutes
    const RENDERED_COMPONENT_CACHE_TTL = 3600; // 1 hour
    const COMPONENT_THEME_CACHE_TTL = 7200; // 2 hours

    /**
     * Cache component configuration with tenant awareness
     */
    public function cacheComponent(Component $component): bool
    {
        $cacheKey = $this->getComponentCacheKey($component, 'config');

        return $this->put($cacheKey, $component->config, self::COMPONENT_CACHE_TTL);
    }

    /**
     * Get cached component configuration
     */
    public function getCachedComponent(Component $component)
    {
        $cacheKey = $this->getComponentCacheKey($component, 'config');
        $cached = $this->get($cacheKey);

        if ($cached !== null) {
            Log::info('Component cache hit', [
                'component_id' => $component->id,
                'cache_key' => $cacheKey
            ]);
        }

        return $cached;
    }

    /**
     * Cache rendered component HTML with configuration hash
     */
    public function cacheRenderedComponent(
        Component $component,
        array $configuration,
        string $renderedHtml,
        ?string $themeSlug = null
    ): bool {
        $configHash = $this->getConfigurationHash($configuration);
        $cacheKey = $this->getRenderedComponentCacheKey($component, $configHash, $themeSlug);

        return $this->put($cacheKey, [
            'html' => $renderedHtml,
            'config_hash' => $configHash,
            'rendered_at' => now()->toISOString(),
        ], self::RENDERED_COMPONENT_CACHE_TTL);
    }

    /**
     * Get cached rendered component
     */
    public function getCachedRenderedComponent(
        Component $component,
        array $configuration,
        ?string $themeSlug = null
    ): ?string {
        $configHash = $this->getConfigurationHash($configuration);
        $cacheKey = $this->getRenderedComponentCacheKey($component, $configHash, $themeSlug);

        $cached = $this->get($cacheKey);

        if ($cached && $this->isValidCacheEntry($cached, $configHash)) {
            Log::info('Rendered component cache hit', [
                'component_id' => $component->id,
                'cache_key' => $cacheKey
            ]);
            return $cached['html'];
        }

        return null;
    }

    /**
     * Cache component instance with theme configuration
     */
    public function cacheComponentInstance(ComponentInstance $instance, array $mergedConfig): bool
    {
        $cacheKey = $this->getComponentInstanceCacheKey($instance);

        return $this->put($cacheKey, [
            'component_id' => $instance->component_id,
            'configuration' => $mergedConfig,
            'theme_slug' => $instance->component->theme->slug ?? null,
            'cached_at' => now()->toISOString(),
        ], self::COMPONENT_CACHE_TTL);
    }

    /**
     * Get cached component instance configuration
     */
    public function getCachedComponentInstance(ComponentInstance $instance)
    {
        $cacheKey = $this->getComponentInstanceCacheKey($instance);
        return $this->get($cacheKey);
    }

    /**
     * Cache component theme styles
     */
    public function cacheComponentTheme(string $themeSlug, array $styles, int $tenantId): bool
    {
        $cacheKey = $this->getComponentThemeCacheKey($themeSlug, $tenantId);

        return $this->put($cacheKey, [
            'styles' => $styles,
            'compiled_at' => now()->toISOString(),
        ], self::COMPONENT_THEME_CACHE_TTL);
    }

    /**
     * Get cached component theme styles
     */
    public function getCachedComponentTheme(string $themeSlug, int $tenantId)
    {
        $cacheKey = $this->getComponentThemeCacheKey($themeSlug, $tenantId);

        $cached = $this->get($cacheKey);

        if ($cached !== null) {
            Log::info('Component theme cache hit', [
                'theme_slug' => $themeSlug,
                'cache_key' => $cacheKey
            ]);
        }

        return $cached;
    }

    /**
     * Preload frequently used components into cache
     */
    public function preloadFrequentlyUsedComponents(int $tenantId, int $limit = 20): void
    {
        $frequentComponents = ComponentInstance::whereHas('component', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->selectRaw('component_id, COUNT(*) as usage_count')
        ->groupBy('component_id')
        ->orderByDesc('usage_count')
        ->limit($limit)
        ->get();

        foreach ($frequentComponents as $usage) {
            $component = Component::where('tenant_id', $tenantId)
                ->where('id', $usage->component_id)
                ->first();

            if ($component && $component->is_active) {
                $this->cacheComponent($component);
                Log::info('Preloaded component to cache', [
                    'component_id' => $component->id,
                    'usage_count' => $usage->usage_count
                ]);
            }
        }
    }

    /**
     * Invalidate component cache
     */
    public function invalidateComponentCache(Component $component): void
    {
        $pattern = "component:{$component->tenant_id}:{$component->id}:*";

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }

        Log::info('Invalidated component cache', [
            'component_id' => $component->id,
            'keys_deleted' => count($keys ?? [])
        ]);
    }

    /**
     * Invalidate tenant component cache
     */
    public function invalidateTenantComponentCache(int $tenantId): void
    {
        $pattern = "component:{$tenantId}:*";

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }

        Log::info('Invalidated tenant component cache', [
            'tenant_id' => $tenantId,
            'keys_deleted' => count($keys ?? [])
        ]);
    }

    /**
     * Get component caching metrics
     */
    public function getComponentCachingMetrics(int $tenantId): array
    {
        $metrics = [
            'total_components' => 0,
            'cached_components' => 0,
            'cache_hit_rate' => 0,
            'average_load_time' => 0,
            'cache_size' => 0,
        ];

        $componentPattern = "component:{$tenantId}:*";
        $renderedPattern = "rendered_component:{$tenantId}:*";

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $componentKeys = Redis::keys($componentPattern);
            $renderedKeys = Redis::keys($renderedPattern);

            $metrics['total_components'] = count($componentKeys);
            $metrics['cached_components'] = count($renderedKeys);

            // Get cache info for size estimation
            $cacheInfo = Redis::info('memory');
            $metrics['cache_size'] = $cacheInfo['used_memory'] ?? 0;
        }

        return $metrics;
    }

    /**
     * Generate cache key for component configuration
     */
    private function getComponentCacheKey(Component $component, string $type = 'config'): string
    {
        return "component:{$component->tenant_id}:{$component->id}:{$type}";
    }

    /**
     * Generate cache key for rendered component
     */
    private function getRenderedComponentCacheKey(Component $component, string $configHash, ?string $themeSlug = null): string
    {
        $themePart = $themeSlug ? ":{$themeSlug}" : '';
        return "rendered_component:{$component->tenant_id}:{$component->id}:{$configHash}{$themePart}";
    }

    /**
     * Generate cache key for component instance
     */
    private function getComponentInstanceCacheKey(ComponentInstance $instance): string
    {
        return "component_instance:{$instance->tenant_id}:{$instance->id}";
    }

    /**
     * Generate cache key for component theme
     */
    private function getComponentThemeCacheKey(string $themeSlug, int $tenantId): string
    {
        return "component_theme:{$tenantId}:{$themeSlug}";
    }

    /**
     * Generate hash for component configuration
     */
    private function getConfigurationHash(array $configuration): string
    {
        return md5(json_encode($configuration, JSON_THROW_ON_ERROR));
    }

    /**
     * Validate cache entry
     */
    private function isValidCacheEntry(array $cachedEntry, string $expectedHash): bool
    {
        return ($cachedEntry['config_hash'] ?? '') === $expectedHash;
    }

    /**
     * Warm component cache on startup
     */
    public function warmComponentCache(int $tenantId): void
    {
        Log::info('Starting component cache warming', ['tenant_id' => $tenantId]);

        $this->preloadFrequentlyUsedComponents($tenantId);

        // Cache active component themes
        $activeThemes = Component::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with('theme')
            ->get()
            ->pluck('theme')
            ->unique('id')
            ->filter();

        foreach ($activeThemes as $theme) {
            if ($theme->config) {
                $this->cacheComponentTheme($theme->slug, $theme->config, $tenantId);
            }
        }

        Log::info('Component cache warming completed', ['tenant_id' => $tenantId]);
    }
}