<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Services\CrmIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedCrmSubmission implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $backoff = [300, 900, 1800, 3600, 7200]; // 5min, 15min, 30min, 1hr, 2hr

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead,
        public array $crmConfig
    ) {
        $this->onQueue('crm-retry');
    }

    /**
     * Execute the job.
     */
    public function handle(CrmIntegrationService $crmService): void
    {
        try {
            Log::info('Retrying failed CRM submission', [
                'lead_id' => $this->lead->id,
                'provider' => $this->crmConfig['provider'] ?? 'unknown',
                'attempt' => $this->attempts()
            ]);

            // Get CRM integration
            $integration = CrmIntegration::where('provider', $this->crmConfig['provider'] ?? 'hubspot')
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                throw new \Exception('CRM integration not available');
            }

            // Check if lead was already synced by another process
            if ($this->lead->crm_id && $this->lead->synced_at) {
                Log::info('Lead already synced, skipping retry', [
                    'lead_id' => $this->lead->id,
                    'crm_id' => $this->lead->crm_id
                ]);
                return;
            }

            // Attempt to sync the lead
            $result = $integration->syncLead($this->lead);

            if ($result['success']) {
                $this->lead->addActivity('crm_retry_success', 'CRM sync retry successful', null, [
                    'provider' => $integration->provider,
                    'attempt' => $this->attempts(),
                    'result' => $result
                ]);

                Log::info('CRM retry successful', [
                    'lead_id' => $this->lead->id,
                    'provider' => $integration->provider,
                    'attempt' => $this->attempts()
                ]);
            } else {
                throw new \Exception($result['message'] ?? 'CRM sync failed');
            }

        } catch (\Exception $e) {
            Log::error('CRM retry failed', [
                'lead_id' => $this->lead->id,
                'provider' => $this->crmConfig['provider'] ?? 'unknown',
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            $this->lead->addActivity('crm_retry_failed', 'CRM sync retry failed', $e->getMessage(), [
                'provider' => $this->crmConfig['provider'] ?? 'unknown',
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger next retry
        }
    }

    /**
     * Handle job failure after all retries
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CRM retry job failed permanently', [
            'lead_id' => $this->lead->id,
            'provider' => $this->crmConfig['provider'] ?? 'unknown',
            'error' => $exception->getMessage(),
            'total_attempts' => $this->attempts()
        ]);

        $this->lead->addActivity('crm_retry_failed_permanent', 'CRM sync permanently failed', $exception->getMessage(), [
            'provider' => $this->crmConfig['provider'] ?? 'unknown',
            'total_attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'failed_permanently_at' => now()->toISOString()
        ]);

        // Update lead to indicate permanent CRM sync failure
        $this->lead->update([
            'notes' => ($this->lead->notes ?? '') . "\n\nCRM sync permanently failed after {$this->attempts()} attempts: {$exception->getMessage()}"
        ]);
    }
}