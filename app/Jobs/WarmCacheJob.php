<?php

namespace App\Jobs;

use App\Services\CachingStrategyService;
use App\Services\ComponentCachingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WarmCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $tenantId;

    /**
     * Create a new job instance.
     */
    public function __construct(?int $tenantId = null)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        CachingStrategyService $cachingStrategyService,
        ComponentCachingService $componentCachingService
    ): void {
        try {
            Log::info('Starting WarmCacheJob', ['tenant_id' => $this->tenantId]);

            $startTime = microtime(true);

            // Warm general caches
            $cachingStrategyService->warmCache();

            // Warm component caches if tenant ID is provided
            if ($this->tenantId) {
                $componentCachingService->warmComponentCache($this->tenantId);
            }

            $duration = microtime(true) - $startTime;
            Log::info('WarmCacheJob completed successfully', [
                'tenant_id' => $this->tenantId,
                'duration' => $duration
            ]);

        } catch (\Exception $e) {
            Log::error('WarmCacheJob failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['cache', 'warming', 'tenant:' . ($this->tenantId ?? 'global')];
    }
}