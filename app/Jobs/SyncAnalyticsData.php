<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\LearningUpdated;
use App\Services\Analytics\SyncService;
use App\Services\TenantContextService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sync Analytics Data Job
 *
 * Processes data synchronization between internal analytics and external services.
 * Handles batch processing of large datasets with proper error handling and tenant isolation.
 * Emits WebSocket events for real-time dashboard updates.
 */
class SyncAnalyticsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private string $tenantId,
        private array $sources = ['ga', 'matomo'],
        private array $timeRange = [],
        private bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        SyncService $syncService,
        TenantContextService $tenantContextService
    ): void {
        try {
            Log::info('Starting SyncAnalyticsData job', [
                'tenant_id' => $this->tenantId,
                'sources' => $this->sources,
                'time_range' => $this->timeRange,
                'force' => $this->force,
            ]);

            // Set tenant context for the entire job
            $tenantContextService->setTenant($this->tenantId);

            // Check if sync is needed (unless forced)
            if (! $this->force && ! $this->shouldRunSync()) {
                Log::info('Sync skipped - recent sync exists and not forced', [
                    'tenant_id' => $this->tenantId,
                ]);

                return;
            }

            // Run data synchronization
            $result = $syncService->syncData($this->tenantId, $this->sources, $this->timeRange);

            // Run discrepancy detection
            $discrepancies = $syncService->detectDiscrepancies($this->tenantId, $this->timeRange);

            // Emit WebSocket event for real-time updates
            broadcast(new LearningUpdated([
                'tenant_id' => $this->tenantId,
                'type' => 'analytics_synced',
                'sources' => $this->sources,
                'events_count' => $result['events_count'] ?? 0,
                'sessions' => $result['sessions'] ?? 0,
                'discrepancies_found' => count($discrepancies['discrepancies'] ?? []),
                'timestamp' => now()->toISOString(),
            ]))->toOthers();

            Log::info('SyncAnalyticsData job completed successfully', [
                'tenant_id' => $this->tenantId,
                'events_count' => $result['events_count'] ?? 0,
                'sessions' => $result['sessions'] ?? 0,
                'discrepancies_found' => count($discrepancies['discrepancies'] ?? []),
            ]);

        } catch (Exception $e) {
            Log::error('SyncAnalyticsData job failed', [
                'tenant_id' => $this->tenantId,
                'sources' => $this->sources,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Determine if sync should run based on recent activity
     */
    private function shouldRunSync(): bool
    {
        // Check if there's been a recent sync (within last hour)
        $syncService = app(SyncService::class);
        $status = $syncService->monitorSync($this->tenantId);

        if ($status['last_sync_at']) {
            $lastSync = strtotime($status['last_sync_at']);
            $oneHourAgo = strtotime('-1 hour');

            return $lastSync < $oneHourAgo;
        }

        // No previous sync, should run
        return true;
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'analytics',
            'sync',
            'tenant:'.$this->tenantId,
            'sources:'.implode(',', $this->sources),
        ];
    }

    /**
     * Get the retry delay for the job.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}
