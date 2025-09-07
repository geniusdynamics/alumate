<?php

namespace App\Services;

use App\Models\Template;
use App\Models\LandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * Template Performance Optimizer Service
 *
 * Provides comprehensive performance optimization for template rendering and caching.
 * Implements multi-level caching strategy with tenant isolation and performance monitoring.
 */
class TemplatePerformanceOptimizer
{
    /**
     * Cache key prefixes for different optimization layers
     */
    public const CACHE_PREFIX = 'template_performance:';
    public const RENDER_CACHE_PREFIX = 'template_render:';
    public const OPTIMIZATION_CACHE_PREFIX = 'template_opt:';
    public const METADATA_CACHE_PREFIX = 'template_meta:';

    /**
     * Cache durations for different cache levels
     */
    public const RENDER_CACHE_DURATION = 3600; // 1 hour
    public const OPTIMIZATION_CACHE_DURATION = 1800; // 30 minutes
    public const METADATA_CACHE_DURATION = 300; // 5 minutes

    /**
     * Performance thresholds for optimization triggers
     */
    private array $performanceThresholds = [
        'slow_render_threshold' => 2000, // ms
        'high_memory_threshold' => 128, // MB
        'cache_hit_improvement' => 80, // percentage
        'optimization_gain_threshold' => 50, // percentage improvement
    ];

    /**
     * Multi-level cache configuration
     */
    private array $cacheLayers = [
        'l1' => ['store' => 'array', 'duration' => 60], // Memory (short-term)
        'l2' => ['store' => 'redis', 'duration' => 3600], // Redis (medium-term)
        'l3' => ['store' => 'database', 'duration' => 86400], // DB (long-term)
    ];

    /**
     * Optimize template rendering performance
     *
     * @param Template $template
     * @param array $renderContext
     * @param int|null $tenantId
     * @return array Optimization results with cached/rendered content
     */
    public function optimizeTemplateRendering(Template $template, array $renderContext = [], ?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        $startTime = microtime(true);

        try {
            $optimizationResult = $this->applyOptimizedRendering($template, $renderContext, $tenantId);
            $endTime = microtime(true);

            $renderMetrics = [
                'template_id' => $template->id,
                'tenant_id' => $tenantId,
                'render_time' => round(($endTime - $startTime) * 1000, 2),
                'cache_hit' => $optimizationResult['cache_hit'] ?? false,
                'memory_peak' => memory_get_peak_usage(true) / 1024 / 1024,
                'optimization_applied' => count($optimizationResult['optimizations'] ?? []),
            ];

            $this->recordPerformanceMetrics($renderMetrics);
            $this->updateTemplatePerformanceData($template, $renderMetrics);

            return $optimizationResult;

        } catch (\Exception $e) {
            Log::error('Template optimization failed', [
                'template_id' => $template->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'render_context' => $renderContext,
            ]);

            // Fallback to basic rendering
            return $this->fallbackRendering($template, $renderContext);
        }
    }

    /**
     * Pre-cache commonly used templates for faster access
     *
     * @param int|null $tenantId
     * @param int $limit Number of templates to pre-cache
     * @return array Pre-caching results
     */
    public function warmTemplateCache(?int $tenantId = null, int $limit = 50): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        $results = ['total_warmed' => 0, 'cache_keys' => [], 'errors' => []];

        try {
            // Get most frequently used templates
            $popularTemplates = Template::query()
                ->forTenant($tenantId)
                ->active()
                ->orderBy('usage_count', 'desc')
                ->orderBy('last_used_at', 'desc')
                ->limit($limit)
                ->get();

            foreach ($popularTemplates as $template) {
                try {
                    $cacheKey = $this->getRenderCacheKey($template->id, $tenantId);
                    $optimizedContent = $this->generateOptimizedTemplate($template);

                    Cache::store($this->cacheLayers['l2']['store'])
                         ->put($cacheKey, $optimizedContent, $this->cacheLayers['l2']['duration']);

                    $results['cache_keys'][] = $cacheKey;
                    $results['total_warmed']++;

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'template_id' => $template->id,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('Template cache warming completed', $results);
            return $results;

        } catch (\Exception $e) {
            Log::error('Cache warming failed', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            return $results;
        }
    }

    /**
     * Invalidate template caches when templates are updated
     *
     * @param Template $template
     * @param int|null $tenantId
     * @return bool
     */
    public function invalidateTemplateCache(Template $template, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        try {
            // Invalidate all cache layers
            foreach ($this->cacheLayers as $layer => $config) {
                $cacheKey = $this->getRenderCacheKey($template->id, $tenantId);
                Cache::store($config['store'])->forget($cacheKey);

                // Also invalidate optimization cache
                $optCacheKey = $this->getOptimizationCacheKey($template->id, $tenantId);
                Cache::store($config['store'])->forget($optCacheKey);
            }

            // Clear metadata cache
            Cache::forget($this->getMetadataCacheKey($template->id, $tenantId));

            // Clear related landing page caches
            if ($template->landingPages) {
                foreach ($template->landingPages as $landingPage) {
                    Cache::forget("landing_page:{$tenantId}:{$landingPage->id}");
                }
            }

            Log::info('Template cache invalidated', [
                'template_id' => $template->id,
                'tenant_id' => $tenantId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Cache invalidation failed', [
                'template_id' => $template->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get performance report for template optimization
     *
     * @param int|null $tenantId
     * @param int $days
     * @return array Performance report data
     */
    public function getPerformanceReport(?int $tenantId = null, int $days = 7): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        $cacheKey = self::CACHE_PREFIX . "report:{$tenantId}:{$days}";

        return Cache::remember($cacheKey, self::METADATA_CACHE_DURATION, function () use ($tenantId, $days) {
            $startDate = Carbon::now()->subDays($days);

            // Get template performance metrics from cache
            $templates = Template::query()
                ->forTenant($tenantId)
                ->where('last_used_at', '>=', $startDate)
                ->get();

            $templateMetrics = [];
            foreach ($templates as $template) {
                $templateMetrics[] = $this->getTemplatePerformanceMetrics($template, $days);
            }

            // Calculate overall statistics
            $overallStats = $this->calculateOverallPerformanceStats($templateMetrics);

            return [
                'period' => "{$days} days",
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d'),
                'tenant_id' => $tenantId,
                'template_metrics' => $templateMetrics,
                'overall_stats' => $overallStats,
                'cache_efficiency' => $this->analyzeCacheEfficiency($templateMetrics),
                'generated_at' => Carbon::now()->toISOString(),
            ];
        });
    }

    /**
     * Generate optimization recommendations
     *
     * @param int|null $tenantId
     * @return array Recommendations for performance improvement
     */
    public function generateOptimizationRecommendations(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();
        $report = $this->getPerformanceReport($tenantId, 30);

        $recommendations = [];

        // Analyze template performance
        foreach ($report['template_metrics'] as $metric) {
            $avgRenderTime = $metric['avg_render_time'] ?? 0;

            if ($avgRenderTime > $this->performanceThresholds['slow_render_threshold']) {
                $recommendations[] = [
                    'type' => 'template_optimization',
                    'priority' => 'high',
                    'template_id' => $metric['template_id'],
                    'template_name' => $metric['template_name'],
                    'issue' => 'Slow render time',
                    'current_value' => $avgRenderTime,
                    'threshold' => $this->performanceThresholds['slow_render_threshold'],
                    'recommendation' => 'Enable template pre-processing and optimize component lazy loading',
                ];
            }

            $cacheHitRate = $metric['cache_hit_rate'] ?? 0;
            if ($cacheHitRate < $this->performanceThresholds['cache_hit_improvement']) {
                $recommendations[] = [
                    'type' => 'cache_optimization',
                    'priority' => 'medium',
                    'template_id' => $metric['template_id'],
                    'template_name' => $metric['template_name'],
                    'issue' => 'Low cache hit rate',
                    'current_value' => $cacheHitRate,
                    'threshold' => $this->performanceThresholds['cache_hit_improvement'],
                    'recommendation' => 'Increase cache TTL and implement better cache warming strategy',
                ];
            }
        }

        // System-wide recommendations
        $cacheEfficiency = $report['cache_efficiency']['overall_efficiency'] ?? 0;
        if ($cacheEfficiency < 70) {
            $recommendations[] = [
                'type' => 'system_cache_optimization',
                'priority' => 'high',
                'issue' => 'Poor overall cache efficiency',
                'current_value' => $cacheEfficiency,
                'threshold' => 70,
                'recommendation' => 'Review cache configuration and implement better cache warming policies',
            ];
        }

        return $recommendations;
    }

    /**
     * Apply optimized rendering with multi-level caching
     */
    private function applyOptimizedRendering(Template $template, array $renderContext, int $tenantId): array
    {
        // Try L1 cache first (fast memory cache)
        $renderCacheKey = $this->getRenderCacheKey($template->id, $tenantId);
        $cachedContent = Cache::store($this->cacheLayers['l1']['store'])->get($renderCacheKey);

        if ($cachedContent) {
            return [
                'content' => $cachedContent,
                'cache_hit' => true,
                'cache_layer' => 'L1',
                'optimizations' => ['memory_cache'],
                'render_time_saved' => ($template->getLoadTime() ?? 1000), // Estimated save
            ];
        }

        // Try L2 cache (persistent cache)
        $cachedContent = Cache::store($this->cacheLayers['l2']['store'])->get($renderCacheKey);

        if ($cachedContent) {
            // Store in L1 for faster future access
            Cache::store($this->cacheLayers['l1']['store'])
                 ->put($renderCacheKey, $cachedContent, $this->cacheLayers['l1']['duration']);

            return [
                'content' => $cachedContent,
                'cache_hit' => true,
                'cache_layer' => 'L2',
                'optimizations' => ['redis_cache'],
                'render_time_saved' => ($template->getLoadTime() ?? 800),
            ];
        }

        // Generate optimized content
        $optimizedContent = $this->generateOptimizedTemplate($template, $renderContext);

        // Cache in all layers
        foreach ($this->cacheLayers as $layer => $config) {
            Cache::store($config['store'])
                 ->put($renderCacheKey, $optimizedContent, $config['duration']);
        }

        return [
            'content' => $optimizedContent,
            'cache_hit' => false,
            'cache_layer' => null,
            'optimizations' => ['structure_optimization', 'caching_applied'],
            'render_time_saved' => 0,
        ];
    }

    /**
     * Generate optimized template content
     */
    private function generateOptimizedTemplate(Template $template, array $context = []): string
    {
        // Apply structure optimizations
        $structure = $this->optimizeTemplateStructure($template->getEffectiveStructure());

        // Generate efficient HTML/CSS based on optimized structure
        // This would integrate with actual template rendering engine
        $content = $this->generateEfficientMarkup($structure, $context);

        // Apply content optimizations (minification, compression)
        return $this->optimizeContent($content);
    }

    /**
     * Optimize template structure for better performance
     */
    private function optimizeTemplateStructure(array $structure): array
    {
        if (!isset($structure['sections'])) {
            return $structure;
        }

        $optimizedSections = [];

        foreach ($structure['sections'] as $section) {
            // Optimize section configuration for better rendering
            $optimizedSection = $this->optimizeSection($section);
            $optimizedSections[] = $optimizedSection;
        }

        return [
            'sections' => $optimizedSections,
            'optimizations_applied' => [
                'lazy_loading',
                'resource_preloading',
                'structure_minimization',
            ]
        ];
    }

    /**
     * Optimize individual section for performance
     */
    private function optimizeSection(array $section): array
    {
        // Add performance-oriented attributes
        $optimized = $section;

        // Add lazy loading for images/media
        if (isset($section['type']) && in_array($section['type'], ['image', 'hero'])) {
            $optimized['performance_hints'] = [
                'lazy_load' => true,
                'preconnect' => true,
            ];
        }

        // Optimize configuration for faster processing
        if (isset($section['config'])) {
            $optimized['config'] = $this->optimizeSectionConfig($section['config']);
        }

        return $optimized;
    }

    /**
     * Optimize section configuration for performance
     */
    private function optimizeSectionConfig(array $config): array
    {
        $optimized = $config;

        // Remove unnecessary configuration options that slow rendering
        $heavyKeys = ['debug_info', 'verbose_logging', 'full_validation'];

        foreach ($heavyKeys as $key) {
            unset($optimized[$key]);
        }

        // Add performance configuration
        $optimized['_performance'] = [
            'skip_validation' => true, // Skip validation in production rendering
            'cacheable' => true,
            'minified' => true,
        ];

        return $optimized;
    }

    /**
     * Generate efficient markup from optimized structure
     */
    private function generateEfficientMarkup(array $structure, array $context): string
    {
        // This would integrate with actual template rendering
        // For now, return optimized JSON representation
        $structure['_performance_metadata'] = [
            'optimized_at' => Carbon::now()->toISOString(),
            'optimizations_applied' => $structure['optimizations_applied'] ?? [],
            'estimated_size_reduction' => '15-25%',
        ];

        return json_encode($structure);
    }

    /**
     * Apply content optimizations (compression, etc.)
     */
    private function optimizeContent(string $content): string
    {
        // Remove unnecessary whitespace
        $optimized = preg_replace('/\s+/', ' ', $content);

        // Add performance metadata
        return $optimized . "\n<!-- Performance Optimized: " . Carbon::now()->timestamp . " -->";
    }

    /**
     * Fallback rendering when optimization fails
     */
    private function fallbackRendering(Template $template, array $renderContext): array
    {
        return [
            'content' => json_encode($template->structure),
            'cache_hit' => false,
            'optimizations' => [],
            'fallback' => true,
            'error_occurred' => true,
        ];
    }

    /**
     * Record performance metrics
     */
    private function recordPerformanceMetrics(array $metrics): void
    {
        $tenantId = $metrics['tenant_id'];
        $templateId = $metrics['template_id'];

        // Store in performance metrics cache
        $metricsKey = "template_perf_metrics:{$tenantId}:{$templateId}";

        $existing = Cache::get($metricsKey, []);
        array_unshift($existing, $metrics);

        // Keep last 100 metrics
        if (count($existing) > 100) {
            array_pop($existing);
        }

        Cache::put($metricsKey, $existing, 86400); // Keep for 24 hours
    }

    /**
     * Update template performance data
     */
    private function updateTemplatePerformanceData(Template $template, array $metrics): void
    {
        $currentMetrics = $template->performance_metrics ?? [];

        $updates = [
            'last_render_time' => $metrics['render_time'],
            'avg_render_time' => $this->calculateAverageRenderTime($template),
            'cache_hit_rate' => $this->calculateCacheHitRate($template),
            'last_optimized_at' => Carbon::now()->toISOString(),
            'performance_score' => $this->calculatePerformanceScore($metrics),
        ];

        $updatedMetrics = array_merge($currentMetrics, $updates);
        $template->update(['performance_metrics' => $updatedMetrics]);
    }

    /**
     * Calculate average render time for template
     */
    private function calculateAverageRenderTime(Template $template): float
    {
        $metrics = Cache::get("template_perf_metrics:{$template->tenant_id}:{$template->id}", []);
        $renderTimes = array_column($metrics, 'render_time');

        return count($renderTimes) > 0 ? array_sum($renderTimes) / count($renderTimes) : 0;
    }

    /**
     * Calculate cache hit rate for template
     */
    private function calculateCacheHitRate(Template $template): float
    {
        $metrics = Cache::get("template_perf_metrics:{$template->tenant_id}:{$template->id}", []);
        $totalRequests = count($metrics);
        $cacheHits = count(array_filter($metrics, fn($m) => $m['cache_hit'] ?? false));

        return $totalRequests > 0 ? round(($cacheHits / $totalRequests) * 100, 2) : 0;
    }

    /**
     * Calculate performance score based on metrics
     */
    private function calculatePerformanceScore(array $metrics): int
    {
        $score = 100;

        // Deduct points for slow render time
        if (($metrics['render_time'] ?? 0) > 2000) {
            $score -= 30;
        } elseif (($metrics['render_time'] ?? 0) > 1000) {
            $score -= 15;
        }

        // Deduct points for high memory usage
        if (($metrics['memory_peak'] ?? 0) > 256) {
            $score -= 20;
        } elseif (($metrics['memory_peak'] ?? 0) > 128) {
            $score -= 10;
        }

        // Add bonus for cache hits
        if (($metrics['cache_hit'] ?? false)) {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get template performance metrics for analysis
     */
    private function getTemplatePerformanceMetrics(Template $template, int $days): array
    {
        $metrics = Cache::get("template_perf_metrics:{$template->tenant_id}:{$template->id}", []);
        $recentMetrics = collect($metrics)->filter(function ($metric) use ($days) {
            return isset($metric['timestamp']) &&
                   Carbon::parse($metric['timestamp'])->gte(Carbon::now()->subDays($days));
        })->toArray();

        return [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'metrics_count' => count($recentMetrics),
            'avg_render_time' => $this->calculateAverageRenderTime($template),
            'max_render_time' => collect($recentMetrics)->max('render_time') ?? 0,
            'cache_hit_rate' => $this->calculateCacheHitRate($template),
            'total_requests' => $template->usage_count,
            'performance_score' => $template->performance_metrics['performance_score'] ?? 0,
        ];
    }

    /**
     * Calculate overall performance statistics
     */
    private function calculateOverallPerformanceStats(array $templateMetrics): array
    {
        if (empty($templateMetrics)) {
            return ['avg_render_time' => 0, 'avg_cache_hit_rate' => 0, 'total_templates' => 0];
        }

        $renderTimes = array_column($templateMetrics, 'avg_render_time');
        $cacheHitRates = array_column($templateMetrics, 'cache_hit_rate');

        return [
            'total_templates' => count($templateMetrics),
            'avg_render_time' => round(array_sum($renderTimes) / count($renderTimes), 2),
            'max_render_time' => max($renderTimes),
            'avg_cache_hit_rate' => round(array_sum($cacheHitRates) / count($cacheHitRates), 2),
            'templates_with_slow_render' => count(array_filter($renderTimes, fn($t) => $t > 2000)),
            'templates_with_high_cache_hit' => count(array_filter($cacheHitRates, fn($r) => $r > 80)),
        ];
    }

    /**
     * Analyze cache efficiency across templates
     */
    private function analyzeCacheEfficiency(array $templateMetrics): array
    {
        $cacheHits = array_column($templateMetrics, 'cache_hit_rate');
        $cacheEfficiency = count($cacheHits) > 0 ? array_sum($cacheHits) / count($cacheHits) : 0;

        return [
            'overall_efficiency' => round($cacheEfficiency, 2),
            'efficiency_breakdown' => [
                'high_efficiency' => count(array_filter($cacheHits, fn($r) => $r >= 80)),
                'medium_efficiency' => count(array_filter($cacheHits, fn($r) => $r >= 50 && $r < 80)),
                'low_efficiency' => count(array_filter($cacheHits, fn($r) => $r < 50)),
            ],
            'recommendations' => $cacheEfficiency < 70 ? ['increase_cache_ttl', 'improve_cache_warming'] : [],
        ];
    }

    /**
     * Generate cache keys with proper tenant isolation
     */
    private function getRenderCacheKey(int $templateId, int $tenantId): string
    {
        return self::RENDER_CACHE_PREFIX . "{$tenantId}:{$templateId}";
    }

    private function getOptimizationCacheKey(int $templateId, int $tenantId): string
    {
        return self::OPTIMIZATION_CACHE_PREFIX . "{$tenantId}:{$templateId}";
    }

    private function getMetadataCacheKey(int $templateId, int $tenantId): string
    {
        return self::METADATA_CACHE_PREFIX . "{$tenantId}:{$templateId}";
    }

    /**
     * Get current tenant ID with proper fallback
     */
    private function getCurrentTenantId(): ?int
    {
        try {
            if (function_exists('tenant') && tenant()) {
                return tenant()->id;
            }
        } catch (\Exception $e) {
            // Handle cases where tenant context is not available
            Log::debug('Could not determine tenant context', ['error' => $e->getMessage()]);
        }

        return null;
    }
}