<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Services\TemplateCacheService;

/**
 * Template Performance Monitor
 *
 * Monitors and optimizes template performance with real-time metrics,
 * intelligent cache warming, and performance recommendations.
 */
class TemplatePerformanceMonitor
{
    private TemplateCacheService $cacheService;
    private const METRICS_KEY = 'template_performance_metrics';
    private const THRESHOLDS = [
        'slow_query' => 1000, // ms
        'cache_hit_ratio' => 0.8, // 80%
        'memory_usage' => 80, // percent
    ];

    public function __construct(TemplateCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Record template rendering performance
     *
     * @param int $templateId
     * @param float $renderTime
     * @param bool $cacheHit
     * @param int $memoryUsage
     */
    public function recordTemplateRender(int $templateId, float $renderTime, bool $cacheHit, int $memoryUsage): void
    {
        $metrics = [
            'template_id' => $templateId,
            'render_time' => $renderTime,
            'cache_hit' => $cacheHit,
            'memory_usage' => $memoryUsage,
            'timestamp' => now()->toISOString(),
        ];

        // Store in Redis for real-time analysis
        Redis::lpush("template_perf:{$templateId}", json_encode($metrics));
        Redis::expire("template_perf:{$templateId}", 86400); // 24 hours

        // Check for performance issues
        $this->checkPerformanceThresholds($templateId, $renderTime, $cacheHit, $memoryUsage);

        Log::debug('Template performance recorded', $metrics);
    }

    /**
     * Get template performance statistics
     *
     * @param int $templateId
     * @return array
     */
    public function getTemplatePerformanceStats(int $templateId): array
    {
        $key = "template_perf:{$templateId}";
        $entries = Redis::lrange($key, 0, 99); // Last 100 entries

        if (empty($entries)) {
            return [
                'template_id' => $templateId,
                'sample_size' => 0,
                'avg_render_time' => 0,
                'cache_hit_ratio' => 0,
                'memory_usage_avg' => 0,
                'performance_score' => 0,
            ];
        }

        $metrics = array_map('json_decode', $entries);
        $totalTime = array_sum(array_column($metrics, 'renderTime'));
        $cacheHits = array_sum(array_column($metrics, 'cacheHit'));
        $totalMemory = array_sum(array_column($metrics, 'memoryUsage'));

        $sampleSize = count($metrics);
        $avgRenderTime = $totalTime / $sampleSize;
        $cacheHitRatio = $cacheHits / $sampleSize;
        $avgMemoryUsage = $totalMemory / $sampleSize;

        // Calculate performance score (0-100)
        $performanceScore = $this->calculatePerformanceScore($avgRenderTime, $cacheHitRatio, $avgMemoryUsage);

        return [
            'template_id' => $templateId,
            'sample_size' => $sampleSize,
            'avg_render_time' => round($avgRenderTime, 2),
            'cache_hit_ratio' => round($cacheHitRatio, 2),
            'memory_usage_avg' => round($avgMemoryUsage, 2),
            'performance_score' => round($performanceScore, 2),
            'recommendations' => $this->getPerformanceRecommendations($avgRenderTime, $cacheHitRatio, $avgMemoryUsage),
        ];
    }

    /**
     * Get overall system performance metrics
     *
     * @return array
     */
    public function getSystemPerformanceMetrics(): array
    {
        $cacheStats = $this->cacheService->getCacheStats();

        return [
            'cache_stats' => $cacheStats,
            'active_templates_count' => $this->getActiveTemplatesCount(),
            'slow_templates' => $this->getSlowTemplates(),
            'cache_hit_ratio' => $this->calculateSystemCacheHitRatio(),
            'memory_usage_trend' => $this->getMemoryUsageTrend(),
            'recommendations' => $this->getSystemRecommendations(),
        ];
    }

    /**
     * Warm up cache for popular templates
     *
     * @param int $count Number of templates to warm up
     */
    public function warmUpPopularTemplates(int $count = 50): void
    {
        $popularTemplates = \App\Models\Template::query()
            ->active()
            ->orderBy('usage_count', 'desc')
            ->limit($count)
            ->get();

        $this->cacheService->warmUpTemplateCache($popularTemplates);

        Log::info("Cache warmed up for {$count} popular templates");
    }

    /**
     * Optimize cache based on usage patterns
     */
    public function optimizeCache(): void
    {
        // Identify and cache frequently accessed templates
        $frequentTemplates = $this->identifyFrequentTemplates();
        $this->cacheService->warmUpTemplateCache($frequentTemplates);

        // Clean up old cache entries
        $this->cleanupOldCacheEntries();

        // Adjust cache TTL based on usage patterns
        $this->adjustCacheTTL();

        Log::info('Cache optimization completed');
    }

    /**
     * Check performance thresholds and alert if needed
     *
     * @param int $templateId
     * @param float $renderTime
     * @param bool $cacheHit
     * @param int $memoryUsage
     */
    private function checkPerformanceThresholds(int $templateId, float $renderTime, bool $cacheHit, int $memoryUsage): void
    {
        $alerts = [];

        if ($renderTime > self::THRESHOLDS['slow_query']) {
            $alerts[] = "Slow render time: {$renderTime}ms for template {$templateId}";
        }

        if (!$cacheHit) {
            $alerts[] = "Cache miss for template {$templateId}";
        }

        if ($memoryUsage > self::THRESHOLDS['memory_usage']) {
            $alerts[] = "High memory usage: {$memoryUsage}% for template {$templateId}";
        }

        foreach ($alerts as $alert) {
            Log::warning('Template performance alert', ['alert' => $alert]);
            // Here you could send notifications, alerts, etc.
        }
    }

    /**
     * Calculate performance score
     *
     * @param float $avgRenderTime
     * @param float $cacheHitRatio
     * @param float $avgMemoryUsage
     * @return float
     */
    private function calculatePerformanceScore(float $avgRenderTime, float $cacheHitRatio, float $avgMemoryUsage): float
    {
        // Weighted scoring system
        $renderTimeScore = max(0, 100 - ($avgRenderTime / 10)); // Lower time = higher score
        $cacheScore = $cacheHitRatio * 100; // Higher cache hit = higher score
        $memoryScore = max(0, 100 - $avgMemoryUsage); // Lower memory = higher score

        return ($renderTimeScore * 0.4) + ($cacheScore * 0.4) + ($memoryScore * 0.2);
    }

    /**
     * Get performance recommendations
     *
     * @param float $avgRenderTime
     * @param float $cacheHitRatio
     * @param float $avgMemoryUsage
     * @return array
     */
    private function getPerformanceRecommendations(float $avgRenderTime, float $cacheHitRatio, float $avgMemoryUsage): array
    {
        $recommendations = [];

        if ($avgRenderTime > 500) {
            $recommendations[] = 'Consider optimizing template structure or adding database indexes';
        }

        if ($cacheHitRatio < 0.7) {
            $recommendations[] = 'Improve cache hit ratio by adjusting cache TTL or warming strategies';
        }

        if ($avgMemoryUsage > 70) {
            $recommendations[] = 'Monitor memory usage and consider cache size optimization';
        }

        return $recommendations;
    }

    /**
     * Get active templates count
     *
     * @return int
     */
    private function getActiveTemplatesCount(): int
    {
        return \App\Models\Template::active()->count();
    }

    /**
     * Get slow performing templates
     *
     * @return array
     */
    private function getSlowTemplates(): array
    {
        // This would require storing aggregate metrics in Redis or database
        // For now, return empty array as placeholder
        return [];
    }

    /**
     * Calculate system-wide cache hit ratio
     *
     * @return float
     */
    private function calculateSystemCacheHitRatio(): float
    {
        // This would require tracking cache hits/misses system-wide
        // For now, return placeholder
        return 0.85;
    }

    /**
     * Get memory usage trend
     *
     * @return array
     */
    private function getMemoryUsageTrend(): array
    {
        // This would track memory usage over time
        // For now, return placeholder
        return ['current' => 65, 'trend' => 'stable'];
    }

    /**
     * Get system-wide recommendations
     *
     * @return array
     */
    private function getSystemRecommendations(): array
    {
        $recommendations = [];

        $cacheStats = $this->cacheService->getCacheStats();
        if (!$cacheStats['redis_connected']) {
            $recommendations[] = 'Redis connection issues detected - check Redis service';
        }

        if ($this->getActiveTemplatesCount() > 1000) {
            $recommendations[] = 'High number of active templates - consider cache partitioning';
        }

        return $recommendations;
    }

    /**
     * Identify frequently accessed templates
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function identifyFrequentTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Template::query()
            ->active()
            ->where('usage_count', '>', 10)
            ->orderBy('last_used_at', 'desc')
            ->limit(100)
            ->get();
    }

    /**
     * Clean up old cache entries
     */
    private function cleanupOldCacheEntries(): void
    {
        // Redis automatically expires entries, but we can clean up specific patterns
        $this->cacheService->invalidateSearchCache('old_*');
    }

    /**
     * Adjust cache TTL based on usage patterns
     */
    private function adjustCacheTTL(): void
    {
        // This would analyze usage patterns and adjust TTL accordingly
        // For now, this is a placeholder for future implementation
    }
}