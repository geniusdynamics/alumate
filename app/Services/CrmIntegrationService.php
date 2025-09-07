<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Models\CrmSyncLog;
use App\Jobs\ProcessCrmWebhook;
use App\Jobs\SyncLeadToCrm;
use App\Jobs\RetryFailedCrmSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * CRM Integration Service for lead synchronization
 *
 * This service handles bidirectional synchronization between the application
 * and external CRM systems (Salesforce, HubSpot, Zoho, etc.)
 */
class CrmIntegrationService
{
    /**
     * Synchronize a lead with external CRM systems
     */
    public function syncLeadToCrm(Lead $lead, ?CrmIntegration $integration = null): array
    {
        try {
            if (!$integration) {
                $integration = $this->getActiveCrmIntegration();
            }

            if (!$integration || !$integration->is_active) {
                return [
                    'success' => false,
                    'message' => 'No active CRM integration found',
                ];
            }

            // Create sync log
            $syncLog = $this->createSyncLog($lead, $integration, 'create');

            // Map lead data according to field mappings
            $mappedData = $this->mapLeadData($lead, $integration);

            // Sync with CRM
            $result = $this->performCrmSync($integration, $mappedData, 'create');

            // Update sync log and lead
            if ($result['success']) {
                $syncLog->markSuccessful($result['response']);
                $lead->update([
                    'crm_id' => $result['crm_record_id'],
                    'synced_at' => now(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Lead synced successfully',
                    'crm_record_id' => $result['crm_record_id'],
                    'sync_log_id' => $syncLog->id,
                ];
            } else {
                $syncLog->markFailed($result['error']);
                return [
                    'success' => false,
                    'message' => $result['error'],
                    'sync_log_id' => $syncLog->id,
                ];
            }

        } catch (\Exception $e) {
            Log::error('CRM lead sync failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform bidirectional sync for lead updates
     */
    public function syncLeadUpdates(Lead $lead): array
    {
        $results = [];

        $integrations = CrmIntegration::active()->get();

        foreach ($integrations as $integration) {
            if ($lead->crm_id) {
                // Update existing CRM record
                $result = $this->updateCrmRecord($lead, $integration);
            } else {
                // Create new CRM record
                $result = $this->syncLeadToCrm($lead, $integration);
            }

            $results[] = [
                'provider' => $integration->provider,
                'result' => $result,
            ];
        }

        return [
            'success' => collect($results)->every(fn($r) => $r['result']['success']),
            'results' => $results,
        ];
    }

    /**
     * Update existing CRM record
     */
    public function updateCrmRecord(Lead $lead, CrmIntegration $integration): array
    {
        try {
            if (!$lead->crm_id) {
                return $this->syncLeadToCrm($lead, $integration);
            }

            $syncLog = $this->createSyncLog($lead, $integration, 'update');
            $mappedData = $this->mapLeadData($lead, $integration);

            $result = $this->performCrmSync($integration, $mappedData, 'update', $lead->crm_id);

            if ($result['success']) {
                $syncLog->markSuccessful($result['response']);
                $lead->update(['synced_at' => now()]);

                return [
                    'success' => true,
                    'message' => 'CRM record updated successfully',
                    'sync_log_id' => $syncLog->id,
                ];
            } else {
                $syncLog->markFailed($result['error']);
                return [
                    'success' => false,
                    'message' => $result['error'],
                    'sync_log_id' => $syncLog->id,
                ];
            }

        } catch (\Exception $e) {
            Log::error('CRM record update failed', [
                'lead_id' => $lead->id,
                'crm_id' => $lead->crm_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pull lead updates from CRM systems
     */
    public function pullCrmUpdates(CrmIntegration $integration): array
    {
        try {
            $client = $integration->getApiClient();
            $updates = $client->getRecentUpdates();

            $processed = 0;
            $errors = [];

            foreach ($updates as $update) {
                try {
                    $lead = $this->findOrCreateLeadFromCrmData($update, $integration);
                    $this->updateLeadFromCrmData($lead, $update, $integration);

                    $syncLog = $this->createSyncLog($lead, $integration, 'pull');
                    $syncLog->markSuccessful(['crm_data' => $update]);

                    $processed++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'crm_record_id' => $update['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'message' => "Processed {$processed} CRM updates",
            ];

        } catch (\Exception $e) {
            Log::error('CRM pull updates failed', [
                'integration_id' => $integration->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process webhook from CRM system
     */
    public function processWebhook(string $provider, array $payload): array
    {
        try {
            $integration = CrmIntegration::byProvider($provider)->active()->first();

            if (!$integration) {
                throw new \Exception("No active integration found for provider: {$provider}");
            }

            // Validate webhook signature
            if (!$this->validateWebhookSignature($integration, $payload)) {
                throw new \Exception('Invalid webhook signature');
            }

            // Queue webhook processing
            ProcessCrmWebhook::dispatch($integration, $payload)
                ->onQueue('crm-webhooks');

            return [
                'success' => true,
                'message' => 'Webhook queued for processing',
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle conflict resolution for sync operations
     */
    public function resolveSyncConflict(Lead $lead, array $crmData, CrmIntegration $integration): array
    {
        try {
            // Implement conflict resolution logic
            // For now, prefer local data over CRM data
            $conflictLog = [
                'lead_id' => $lead->id,
                'crm_data' => $crmData,
                'local_data' => $lead->toArray(),
                'resolution' => 'prefer_local',
                'resolved_at' => now(),
            ];

            Log::info('CRM sync conflict resolved', $conflictLog);

            return [
                'success' => true,
                'resolution' => 'prefer_local',
                'message' => 'Conflict resolved by preferring local data',
            ];

        } catch (\Exception $e) {
            Log::error('Conflict resolution failed', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get sync status and statistics
     */
    public function getSyncStatus(?CrmIntegration $integration = null): array
    {
        $query = CrmSyncLog::query();

        if ($integration) {
            $query->where('crm_integration_id', $integration->id);
        }

        $stats = [
            'total_syncs' => $query->count(),
            'successful_syncs' => (clone $query)->successful()->count(),
            'failed_syncs' => (clone $query)->failed()->count(),
            'pending_syncs' => (clone $query)->pending()->count(),
            'retryable_syncs' => (clone $query)->retryable()->count(),
            'last_sync_at' => $query->latest('created_at')->value('created_at'),
            'average_sync_duration' => $query->whereNotNull('sync_duration')->avg('sync_duration'),
        ];

        return $stats;
    }

    /**
     * Retry failed sync operations
     */
    public function retryFailedSyncs(?CrmIntegration $integration = null): array
    {
        $query = CrmSyncLog::retryable();

        if ($integration) {
            $query->where('crm_integration_id', $integration->id);
        }

        $failedSyncs = $query->get();
        $results = [];

        foreach ($failedSyncs as $syncLog) {
            try {
                $lead = $syncLog->lead;
                $integration = $syncLog->crmIntegration;

                if ($lead && $integration) {
                    $result = $this->syncLeadToCrm($lead, $integration);
                    $results[] = [
                        'sync_log_id' => $syncLog->id,
                        'result' => $result,
                    ];
                }
            } catch (\Exception $e) {
                $results[] = [
                    'sync_log_id' => $syncLog->id,
                    'result' => ['success' => false, 'message' => $e->getMessage()],
                ];
            }
        }

        return [
            'processed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Create sync log entry
     */
    private function createSyncLog(Lead $lead, CrmIntegration $integration, string $syncType): CrmSyncLog
    {
        return CrmSyncLog::create([
            'tenant_id' => $integration->tenant_id ?? 1,
            'crm_integration_id' => $integration->id,
            'lead_id' => $lead->id,
            'sync_type' => $syncType,
            'crm_provider' => $integration->provider,
            'status' => 'pending',
        ]);
    }

    /**
     * Map lead data according to CRM field mappings
     */
    private function mapLeadData(Lead $lead, CrmIntegration $integration): array
    {
        $mappedData = [];

        foreach ($integration->field_mappings as $localField => $crmField) {
            $value = $this->getLeadFieldValue($lead, $localField);
            if ($value !== null) {
                $mappedData[$crmField] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * Get field value from lead
     */
    private function getLeadFieldValue(Lead $lead, string $field)
    {
        switch ($field) {
            case 'full_name':
                return $lead->full_name;
            case 'first_name':
                return $lead->first_name;
            case 'last_name':
                return $lead->last_name;
            case 'email':
                return $lead->email;
            case 'phone':
                return $lead->phone;
            case 'company':
                return $lead->company;
            case 'job_title':
                return $lead->job_title;
            case 'lead_type':
                return $lead->lead_type;
            case 'source':
                return $lead->source;
            case 'status':
                return $lead->status;
            case 'score':
                return $lead->score;
            case 'priority':
                return $lead->priority;
            default:
                return $lead->$field ?? null;
        }
    }

    /**
     * Perform actual CRM sync operation
     */
    private function performCrmSync(CrmIntegration $integration, array $data, string $operation, ?string $crmRecordId = null): array
    {
        try {
            $client = $integration->getApiClient();

            switch ($operation) {
                case 'create':
                    $result = $client->createLead($data);
                    return [
                        'success' => true,
                        'crm_record_id' => $result['id'],
                        'response' => $result,
                    ];

                case 'update':
                    $result = $client->updateLead($crmRecordId, $data);
                    return [
                        'success' => true,
                        'crm_record_id' => $crmRecordId,
                        'response' => $result,
                    ];

                default:
                    throw new \Exception("Unsupported sync operation: {$operation}");
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find or create lead from CRM data
     */
    private function findOrCreateLeadFromCrmData(array $crmData, CrmIntegration $integration): Lead
    {
        // Try to find existing lead by CRM ID
        $lead = Lead::where('crm_id', $crmData['id'])->first();

        if (!$lead) {
            // Try to find by email
            $email = $this->extractEmailFromCrmData($crmData, $integration);
            if ($email) {
                $lead = Lead::where('email', $email)->first();
            }
        }

        if (!$lead) {
            // Create new lead
            $lead = $this->createLeadFromCrmData($crmData, $integration);
        }

        return $lead;
    }

    /**
     * Update lead from CRM data
     */
    private function updateLeadFromCrmData(Lead $lead, array $crmData, CrmIntegration $integration): void
    {
        $updateData = [];

        foreach ($integration->field_mappings as $localField => $crmField) {
            if (isset($crmData[$crmField])) {
                $updateData[$localField] = $crmData[$crmField];
            }
        }

        if (!empty($updateData)) {
            $lead->update($updateData);
        }
    }

    /**
     * Extract email from CRM data
     */
    private function extractEmailFromCrmData(array $crmData, CrmIntegration $integration): ?string
    {
        $emailField = array_search('email', $integration->field_mappings);
        return $crmData[$emailField] ?? null;
    }

    /**
     * Create lead from CRM data
     */
    private function createLeadFromCrmData(array $crmData, CrmIntegration $integration): Lead
    {
        $leadData = [
            'crm_id' => $crmData['id'],
            'crm_provider' => $integration->provider,
            'source' => 'crm_import',
        ];

        foreach ($integration->field_mappings as $localField => $crmField) {
            if (isset($crmData[$crmField])) {
                $leadData[$localField] = $crmData[$crmField];
            }
        }

        return Lead::create($leadData);
    }

    /**
     * Get active CRM integration
     */
    private function getActiveCrmIntegration(): ?CrmIntegration
    {
        return CrmIntegration::active()->first();
    }

    /**
     * Validate webhook signature
     */
    private function validateWebhookSignature(CrmIntegration $integration, array $payload): bool
    {
        // Implementation depends on CRM provider
        // For now, return true - implement proper validation based on provider
        return true;
    }
}