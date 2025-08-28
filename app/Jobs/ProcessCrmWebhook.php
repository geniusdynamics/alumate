<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Models\CrmIntegration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCrmWebhook implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300]; // 30 sec, 2 min, 5 min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $provider,
        public array $payload
    ) {
        $this->onQueue('webhook-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing CRM webhook', [
                'provider' => $this->provider,
                'event_type' => $this->payload['event_type'] ?? 'unknown'
            ]);

            // Get CRM integration
            $integration = CrmIntegration::where('provider', $this->provider)
                ->where('is_active', true)
                ->first();

            if (!$integration) {
                Log::warning('No active CRM integration found for webhook', [
                    'provider' => $this->provider
                ]);
                return;
            }

            // Process webhook based on event type
            $eventType = $this->payload['event_type'] ?? '';
            
            switch ($eventType) {
                case 'lead.created':
                case 'contact.created':
                    $this->handleLeadCreated($integration);
                    break;
                    
                case 'lead.updated':
                case 'contact.updated':
                    $this->handleLeadUpdated($integration);
                    break;
                    
                case 'lead.deleted':
                case 'contact.deleted':
                    $this->handleLeadDeleted($integration);
                    break;
                    
                case 'deal.won':
                case 'opportunity.closed_won':
                    $this->handleDealWon($integration);
                    break;
                    
                case 'deal.lost':
                case 'opportunity.closed_lost':
                    $this->handleDealLost($integration);
                    break;
                    
                default:
                    Log::info('Unhandled webhook event type', [
                        'provider' => $this->provider,
                        'event_type' => $eventType
                    ]);
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'payload' => $this->payload
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle lead created webhook
     */
    private function handleLeadCreated(CrmIntegration $integration): void
    {
        $leadData = $this->payload['data'] ?? [];
        $crmId = $leadData['id'] ?? null;

        if (!$crmId) {
            Log::warning('Lead created webhook missing ID', [
                'provider' => $this->provider,
                'payload' => $this->payload
            ]);
            return;
        }

        // Check if lead already exists
        $existingLead = Lead::where('crm_id', $crmId)->first();
        if ($existingLead) {
            Log::info('Lead already exists, skipping creation', [
                'crm_id' => $crmId,
                'lead_id' => $existingLead->id
            ]);
            return;
        }

        // Create lead from CRM data
        $mappedData = $this->mapCrmDataToLead($leadData, $integration);
        $lead = Lead::create(array_merge($mappedData, [
            'crm_id' => $crmId,
            'source' => 'crm_webhook',
            'synced_at' => now()
        ]));

        $lead->addActivity('crm_webhook_created', 'Lead created via CRM webhook', null, [
            'provider' => $this->provider,
            'crm_id' => $crmId,
            'webhook_data' => $leadData
        ]);

        Log::info('Lead created from CRM webhook', [
            'lead_id' => $lead->id,
            'crm_id' => $crmId,
            'provider' => $this->provider
        ]);
    }

    /**
     * Handle lead updated webhook
     */
    private function handleLeadUpdated(CrmIntegration $integration): void
    {
        $leadData = $this->payload['data'] ?? [];
        $crmId = $leadData['id'] ?? null;

        if (!$crmId) {
            return;
        }

        $lead = Lead::where('crm_id', $crmId)->first();
        if (!$lead) {
            Log::warning('Lead not found for update webhook', [
                'crm_id' => $crmId,
                'provider' => $this->provider
            ]);
            return;
        }

        // Update lead with CRM data
        $mappedData = $this->mapCrmDataToLead($leadData, $integration);
        $lead->update(array_merge($mappedData, [
            'synced_at' => now()
        ]));

        $lead->addActivity('crm_webhook_updated', 'Lead updated via CRM webhook', null, [
            'provider' => $this->provider,
            'crm_id' => $crmId,
            'webhook_data' => $leadData
        ]);

        Log::info('Lead updated from CRM webhook', [
            'lead_id' => $lead->id,
            'crm_id' => $crmId,
            'provider' => $this->provider
        ]);
    }

    /**
     * Handle lead deleted webhook
     */
    private function handleLeadDeleted(CrmIntegration $integration): void
    {
        $leadData = $this->payload['data'] ?? [];
        $crmId = $leadData['id'] ?? null;

        if (!$crmId) {
            return;
        }

        $lead = Lead::where('crm_id', $crmId)->first();
        if (!$lead) {
            return;
        }

        $lead->addActivity('crm_webhook_deleted', 'Lead deleted in CRM', null, [
            'provider' => $this->provider,
            'crm_id' => $crmId
        ]);

        // Soft delete the lead
        $lead->delete();

        Log::info('Lead deleted from CRM webhook', [
            'lead_id' => $lead->id,
            'crm_id' => $crmId,
            'provider' => $this->provider
        ]);
    }

    /**
     * Handle deal won webhook
     */
    private function handleDealWon(CrmIntegration $integration): void
    {
        $dealData = $this->payload['data'] ?? [];
        $leadId = $dealData['contact_id'] ?? $dealData['lead_id'] ?? null;

        if (!$leadId) {
            return;
        }

        $lead = Lead::where('crm_id', $leadId)->first();
        if (!$lead) {
            return;
        }

        $lead->updateStatus('closed_won', 'Deal won in CRM');
        $lead->addActivity('deal_won', 'Deal won in CRM', null, [
            'provider' => $this->provider,
            'deal_data' => $dealData,
            'deal_value' => $dealData['amount'] ?? null
        ]);

        Log::info('Deal won processed from CRM webhook', [
            'lead_id' => $lead->id,
            'crm_id' => $leadId,
            'provider' => $this->provider,
            'deal_value' => $dealData['amount'] ?? null
        ]);
    }

    /**
     * Handle deal lost webhook
     */
    private function handleDealLost(CrmIntegration $integration): void
    {
        $dealData = $this->payload['data'] ?? [];
        $leadId = $dealData['contact_id'] ?? $dealData['lead_id'] ?? null;

        if (!$leadId) {
            return;
        }

        $lead = Lead::where('crm_id', $leadId)->first();
        if (!$lead) {
            return;
        }

        $lead->updateStatus('closed_lost', 'Deal lost in CRM');
        $lead->addActivity('deal_lost', 'Deal lost in CRM', null, [
            'provider' => $this->provider,
            'deal_data' => $dealData,
            'lost_reason' => $dealData['lost_reason'] ?? null
        ]);

        Log::info('Deal lost processed from CRM webhook', [
            'lead_id' => $lead->id,
            'crm_id' => $leadId,
            'provider' => $this->provider,
            'lost_reason' => $dealData['lost_reason'] ?? null
        ]);
    }

    /**
     * Map CRM data to lead fields
     */
    private function mapCrmDataToLead(array $crmData, CrmIntegration $integration): array
    {
        $mappedData = [];
        $fieldMappings = array_flip($integration->field_mappings ?? []);

        foreach ($fieldMappings as $crmField => $localField) {
            if (isset($crmData[$crmField])) {
                $mappedData[$localField] = $crmData[$crmField];
            }
        }

        // Handle common CRM field variations
        $commonMappings = [
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'jobtitle' => 'job_title'
        ];

        foreach ($commonMappings as $crmField => $localField) {
            if (isset($crmData[$crmField]) && !isset($mappedData[$localField])) {
                $mappedData[$localField] = $crmData[$crmField];
            }
        }

        return $mappedData;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook processing job failed permanently', [
            'provider' => $this->provider,
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
            'attempts' => $this->attempts()
        ]);
    }
}