<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\CrmIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Job for routing a lead to a specific CRM system
 *
 * This job handles the actual routing logic including retry handling,
 * logging, and CRM integration for individual lead routing.
 */
class RouteLeadToCrm implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead,
        public CrmIntegration $crmIntegration,
        public array $routingMetadata = []
    ) {
        $this->onQueue('lead-routing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Ensure tenant context is maintained
        tenancy()->initialize($this->crmIntegration->tenant ?? tenant());

        try {
            Log::info('Starting lead routing to CRM', [
                'lead_id' => $this->lead->id,
                'crm_provider' => $this->crmIntegration->provider,
                'routing_metadata' => $this->routingMetadata
            ]);

            // Verify CRM integration is still active
            if (!$this->crmIntegration->is_active) {
                Log::warning('CRM integration no longer active, marking lead as unrouted', [
                    'lead_id' => $this->lead->id,
                    'crm_provider' => $this->crmIntegration->provider
                ]);

                $this->recordRoutingFailure('CRM integration not active');
                return;
            }

            // Check if lead is already synced to this CRM
            if ($this->isLeadAlreadyRouted()) {
                Log::info('Lead already routed to this CRM, skipping', [
                    'lead_id' => $this->lead->id,
                    'crm_provider' => $this->crmIntegration->provider,
                    'crm_id' => $this->lead->crm_id
                ]);

                $this->recordSuccessfulRouting();
                return;
            }

            // Sync lead to CRM
            $syncResult = $this->crmIntegration->syncLead($this->lead);

            if ($syncResult['success']) {
                $this->recordSuccessfulRouting($syncResult);
                $this->updateLeadWithRoutingInfo($syncResult);

                Log::info('Lead routing completed successfully', [
                    'lead_id' => $this->lead->id,
                    'crm_provider' => $this->crmIntegration->provider,
                    'crm_id' => $this->lead->crm_id,
                    'routing_metadata' => $this->routingMetadata
                ]);

            } else {
                $this->handleSyncFailure($syncResult);
            }

        } catch (\Exception $e) {
            Log::error('Lead routing failed with exception', [
                'lead_id' => $this->lead->id,
                'crm_provider' => $this->crmIntegration->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            $this->recordRoutingFailure($e->getMessage());
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Check if lead is already routed to this CRM
     */
    private function isLeadAlreadyRouted(): bool
    {
        return $this->lead->crm_id && $this->lead->synced_at;
    }

    /**
     * Record successful routing
     */
    private function recordSuccessfulRouting(array $syncResult = []): void
    {
        $activityData = [
            'provider' => $this->crmIntegration->provider,
            'routing_strategy' => $this->routingMetadata['strategy'] ?? 'primary',
            'crm_id' => $this->lead->crm_id,
            'sync_result' => $syncResult,
            'routed_at' => now()->toISOString()
        ];

        $this->lead->addActivity(
            'crm_routing_success',
            'Lead routed to ' . $this->crmIntegration->provider,
            null,
            $activityData
        );

        // Update CRM integration sync result
        $this->crmIntegration->updateSyncResult([
            'success' => true,
            'lead_id' => $this->lead->id,
            'routed_via' => $this->routingMetadata,
            'routed_at' => now()->toISOString()
        ]);
    }

    /**
     * Record routing failure
     */
    private function recordRoutingFailure(string $reason): void
    {
        $activityData = [
            'provider' => $this->crmIntegration->provider,
            'routing_strategy' => $this->routingMetadata['strategy'] ?? 'unknown',
            'failure_reason' => $reason,
            'attempt' => $this->attempts(),
            'failed_at' => now()->toISOString()
        ];

        $this->lead->addActivity(
            'crm_routing_failed',
            'Lead routing failed to ' . $this->crmIntegration->provider,
            $reason,
            $activityData
        );

        // Update CRM integration with routing failure
        $this->crmIntegration->updateSyncResult([
            'success' => false,
            'lead_id' => $this->lead->id,
            'routing_failed' => true,
            'failure_reason' => $reason,
            'attempts' => $this->attempts(),
            'failed_at' => now()->toISOString()
        ]);
    }

    /**
     * Update lead with routing information
     */
    private function updateLeadWithRoutingInfo(array $syncResult): void
    {
        $updateData = [
            'synced_at' => now(),
            'crm_provider' => $this->crmIntegration->provider
        ];

        // Add lead score if not already set
        if (!isset($this->lead->score) || $this->lead->score === null) {
            $updateData['score'] = 50; // Default score for routed leads
        }

        $this->lead->update($updateData);
    }

    /**
     * Handle sync failure from CRM
     */
    private function handleSyncFailure(array $syncResult): void
    {
        $errorMessage = $syncResult['error'] ?? 'Unknown CRM sync error';

        Log::warning('CRM sync failed during routing', [
            'lead_id' => $this->lead->id,
            'crm_provider' => $this->crmIntegration->provider,
            'error' => $errorMessage,
            'sync_result' => $syncResult
        ]);

        $this->recordRoutingFailure('CRM sync failed: ' . $errorMessage);

        throw new \Exception($errorMessage);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Lead routing job failed permanently', [
            'lead_id' => $this->lead->id,
            'crm_provider' => $this->crmIntegration->provider,
            'routing_metadata' => $this->routingMetadata,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Record permanent failure
        $this->recordRoutingFailure('Permanent routing failure: ' . $exception->getMessage());

        // Mark lead as routing failed
        $this->lead->update([
            'routing_status' => 'failed',
            'routing_failed_at' => now()
        ]);

        // Update CRM integration with permanent failure
        $this->crmIntegration->updateSyncResult([
            'success' => false,
            'lead_id' => $this->lead->id,
            'routing_permanently_failed' => true,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'failed_permanently_at' => now()->toISOString()
        ]);
    }
}