<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\CrmIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncLeadToCrm implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Lead $lead,
        public CrmIntegration $integration,
        public array $crmConfig = []
    ) {
        $this->onQueue('crm-integration');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting CRM sync for lead', [
                'lead_id' => $this->lead->id,
                'provider' => $this->integration->provider
            ]);

            // Get CRM client
            $client = $this->integration->getApiClient();
            
            // Map lead data according to integration field mappings
            $mappedData = $this->mapLeadData();
            
            // Add CRM-specific data
            $crmData = array_merge($mappedData, [
                'lead_score' => $this->lead->score,
                'source' => 'form_submission',
                'tags' => $this->crmConfig['tags'] ?? [],
                'submitted_at' => $this->lead->created_at->toISOString()
            ]);

            // Sync to CRM
            if ($this->lead->crm_id) {
                // Update existing lead
                $result = $client->updateLead($this->lead->crm_id, $crmData);
                $this->lead->addActivity('crm_update', 'Lead updated in CRM', null, [
                    'provider' => $this->integration->provider,
                    'crm_id' => $this->lead->crm_id,
                    'result' => $result
                ]);
            } else {
                // Create new lead
                $result = $client->createLead($crmData);
                $this->lead->update([
                    'crm_id' => $result['id'] ?? null,
                    'synced_at' => now()
                ]);
                
                $this->lead->addActivity('crm_create', 'Lead created in CRM', null, [
                    'provider' => $this->integration->provider,
                    'crm_id' => $result['id'] ?? null,
                    'result' => $result
                ]);
            }

            // Update integration sync result
            $this->integration->updateSyncResult([
                'success' => true,
                'lead_id' => $this->lead->id,
                'result' => $result,
                'synced_at' => now()->toISOString()
            ]);

            Log::info('CRM sync completed successfully', [
                'lead_id' => $this->lead->id,
                'provider' => $this->integration->provider,
                'crm_id' => $result['id'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('CRM sync failed', [
                'lead_id' => $this->lead->id,
                'provider' => $this->integration->provider,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Update integration sync result with error
            $this->integration->updateSyncResult([
                'success' => false,
                'lead_id' => $this->lead->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'failed_at' => now()->toISOString()
            ]);

            // Add activity for failed sync
            $this->lead->addActivity('crm_sync_failed', 'CRM sync failed', $e->getMessage(), [
                'provider' => $this->integration->provider,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Map lead data according to CRM integration field mappings
     */
    private function mapLeadData(): array
    {
        $mappedData = [];
        $fieldMappings = $this->integration->field_mappings ?? [];

        foreach ($fieldMappings as $localField => $crmField) {
            $value = $this->getLeadFieldValue($localField);
            if ($value !== null) {
                $mappedData[$crmField] = $value;
            }
        }

        // Add default mappings if not specified
        $defaultMappings = [
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'job_title' => 'jobtitle'
        ];

        foreach ($defaultMappings as $localField => $crmField) {
            if (!isset($mappedData[$crmField])) {
                $value = $this->getLeadFieldValue($localField);
                if ($value !== null) {
                    $mappedData[$crmField] = $value;
                }
            }
        }

        return $mappedData;
    }

    /**
     * Get field value from lead
     */
    private function getLeadFieldValue(string $field)
    {
        switch ($field) {
            case 'full_name':
                return $this->lead->full_name;
            case 'utm_source':
                return $this->lead->utm_data['utm_source'] ?? null;
            case 'utm_medium':
                return $this->lead->utm_data['utm_medium'] ?? null;
            case 'utm_campaign':
                return $this->lead->utm_data['utm_campaign'] ?? null;
            case 'utm_term':
                return $this->lead->utm_data['utm_term'] ?? null;
            case 'utm_content':
                return $this->lead->utm_data['utm_content'] ?? null;
            default:
                return $this->lead->getAttribute($field);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('CRM sync job failed permanently', [
            'lead_id' => $this->lead->id,
            'provider' => $this->integration->provider,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Mark lead as sync failed
        $this->lead->addActivity('crm_sync_failed_permanent', 'CRM sync failed permanently', $exception->getMessage(), [
            'provider' => $this->integration->provider,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage()
        ]);

        // Update integration with permanent failure
        $this->integration->updateSyncResult([
            'success' => false,
            'lead_id' => $this->lead->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'permanently_failed_at' => now()->toISOString()
        ]);
    }
}