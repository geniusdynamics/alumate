<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Analytics\GoogleAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job for handling heavy Google Analytics sync operations
 */
class GoogleSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected string $operation;
    protected ?string $tenantId;

    /**
     * Create a new job instance.
     *
     * @param array $data Data to sync
     * @param string $operation Operation type ('goals' or 'segments')
     * @param string|null $tenantId Tenant identifier
     */
    public function __construct(array $data, string $operation, ?string $tenantId = null)
    {
        $this->data = $data;
        $this->operation = $operation;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleAnalyticsService $googleAnalyticsService): void
    {
        try {
            Log::info("Starting Google Analytics {$this->operation} sync job", [
                'operation' => $this->operation,
                'tenant_id' => $this->tenantId,
                'data_count' => count($this->data),
            ]);

            $result = match ($this->operation) {
                'goals' => $googleAnalyticsService->syncGoals($this->data),
                'segments' => $googleAnalyticsService->exportSegments($this->data),
                default => throw new \InvalidArgumentException("Unsupported operation: {$this->operation}"),
            };

            Log::info("Google Analytics {$this->operation} sync job completed", [
                'operation' => $this->operation,
                'tenant_id' => $this->tenantId,
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error("Google Analytics {$this->operation} sync job failed", [
                'operation' => $this->operation,
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Google Analytics sync job failed permanently', [
            'operation' => $this->operation,
            'tenant_id' => $this->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}