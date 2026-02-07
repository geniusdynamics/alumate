<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\GamificationAnalyticsService;
use App\Services\HeatMapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job for pre-warming analytics caches to improve performance
 *
 * This job pre-loads frequently accessed analytics data into Redis cache
 * to reduce database load and improve response times.
 */
class OptimizeAnalyticsCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $tenantId;

    protected array $cacheOptions;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $tenantId = null, array $cacheOptions = [])
    {
        $this->tenantId = $tenantId;
        $this->cacheOptions = array_merge([
            'warm_leaderboard' => true,
            'warm_metrics' => true,
            'warm_heatmaps' => false, // Expensive, optional
            'leaderboard_limit' => 50,
        ], $cacheOptions);
    }

    /**
     * Execute the job.
     */
    public function handle(
        GamificationAnalyticsService $gamificationService,
        HeatMapService $heatMapService
    ): void {
        try {
            Log::info('Starting OptimizeAnalyticsCacheJob', [
                'tenant_id' => $this->tenantId,
                'cache_options' => $this->cacheOptions,
            ]);

            $startTime = microtime(true);

            // Set tenant context if provided
            if ($this->tenantId) {
                // Assuming tenant context is set via middleware/service
                $this->setTenantContext($this->tenantId);
            }

            $cacheOperations = 0;

            // Warm leaderboard cache
            if ($this->cacheOptions['warm_leaderboard']) {
                $this->warmLeaderboardCache($gamificationService);
                $cacheOperations++;
            }

            // Warm metrics cache
            if ($this->cacheOptions['warm_metrics']) {
                $this->warmMetricsCache($gamificationService);
                $cacheOperations++;
            }

            // Warm heatmap caches (optional, more expensive)
            if ($this->cacheOptions['warm_heatmaps']) {
                $this->warmHeatmapCache($heatMapService);
                $cacheOperations++;
            }

            $duration = microtime(true) - $startTime;

            Log::info('OptimizeAnalyticsCacheJob completed successfully', [
                'tenant_id' => $this->tenantId,
                'cache_operations' => $cacheOperations,
                'duration' => $duration,
            ]);

        } catch (\Exception $e) {
            Log::error('OptimizeAnalyticsCacheJob failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Warm leaderboard cache
     */
    protected function warmLeaderboardCache(GamificationAnalyticsService $service): void
    {
        $limit = $this->cacheOptions['leaderboard_limit'];

        // This will trigger cache population via the service method
        $leaderboard = $service->getLeaderboard($limit);

        Log::debug('Warmed leaderboard cache', [
            'tenant_id' => $this->tenantId,
            'limit' => $limit,
            'entries_cached' => $leaderboard->count(),
        ]);
    }

    /**
     * Warm metrics cache
     */
    protected function warmMetricsCache(GamificationAnalyticsService $service): void
    {
        // Warm metrics for different time ranges
        $timeRanges = [
            [], // All time
            [now()->subDays(7)->toDateString(), now()->toDateString()], // Last 7 days
            [now()->subDays(30)->toDateString(), now()->toDateString()], // Last 30 days
        ];

        foreach ($timeRanges as $range) {
            $metrics = $service->getGamificationMetrics($range);

            Log::debug('Warmed metrics cache', [
                'tenant_id' => $this->tenantId,
                'date_range' => $range,
                'total_events' => $metrics['total_events'],
            ]);
        }
    }

    /**
     * Warm heatmap cache (expensive operation)
     */
    protected function warmHeatmapCache(HeatMapService $service): void
    {
        // This would require getting popular pages and warming their heatmaps
        // Implementation depends on having access to page analytics
        Log::debug('Heatmap cache warming skipped (not implemented)', [
            'tenant_id' => $this->tenantId,
        ]);
    }

    /**
     * Set tenant context for the job
     */
    protected function setTenantContext(string $tenantId): void
    {
        // Implementation depends on tenancy system
        // This might involve setting a global tenant context
        // or passing tenant ID to service methods
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['analytics', 'cache', 'optimization', 'tenant:'.($this->tenantId ?? 'global')];
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Add any middleware needed for tenant context
        ];
    }
}
