<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmIntegration extends Model
{
    protected $fillable = [
        'name',
        'provider',
        'config',
        'is_active',
        'sync_direction',
        'sync_interval',
        'last_sync_at',
        'last_sync_result',
        'field_mappings',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_sync_result' => 'array',
        'field_mappings' => 'array',
    ];

    /**
     * Get the API client for this CRM integration
     */
    public function getApiClient()
    {
        switch ($this->provider) {
            case 'salesforce':
                return new \App\Services\CRM\SalesforceClient($this->config);
            case 'hubspot':
                return new \App\Services\CRM\HubSpotClient($this->config);
            case 'pipedrive':
                return new \App\Services\CRM\PipedriveClient($this->config);
            case 'zoho':
                return new \App\Services\CRM\ZohoCrmClient($this->config);
            case 'twenty':
                return new \App\Services\CRM\TwentyCrmClient($this->config);
            case 'frappe':
                return new \App\Services\CRM\FrappeCrmClient($this->config);
            case 'custom':
                return new \App\Services\CRM\CustomCrmClient($this->config);
            default:
                throw new \Exception("Unsupported CRM provider: {$this->provider}");
        }
    }

    /**
     * Test the connection to the CRM
     */
    public function testConnection(): array
    {
        try {
            $client = $this->getApiClient();
            $result = $client->testConnection();
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Sync a lead to the CRM
     */
    public function syncLead(Lead $lead): array
    {
        if (!$this->is_active) {
            return [
                'success' => false,
                'message' => 'Integration is not active',
            ];
        }

        try {
            $client = $this->getApiClient();
            $mappedData = $this->mapLeadData($lead);
            
            if ($lead->crm_id) {
                // Update existing lead
                $result = $client->updateLead($lead->crm_id, $mappedData);
            } else {
                // Create new lead
                $result = $client->createLead($mappedData);
                $lead->update(['crm_id' => $result['id']]);
            }
            
            $lead->update(['synced_at' => now()]);
            
            return [
                'success' => true,
                'message' => 'Lead synced successfully',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Map lead data according to field mappings
     */
    private function mapLeadData(Lead $lead): array
    {
        $mappedData = [];
        
        foreach ($this->field_mappings as $localField => $crmField) {
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
            case 'utm_source':
                return $lead->utm_data['utm_source'] ?? null;
            case 'utm_medium':
                return $lead->utm_data['utm_medium'] ?? null;
            case 'utm_campaign':
                return $lead->utm_data['utm_campaign'] ?? null;
            default:
                return $lead->$field ?? null;
        }
    }

    /**
     * Update sync result
     */
    public function updateSyncResult(array $result): void
    {
        $this->update([
            'last_sync_at' => now(),
            'last_sync_result' => $result,
        ]);
    }

    /**
     * Check if sync is due
     */
    public function isSyncDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->last_sync_at) {
            return true;
        }

        return $this->last_sync_at->addSeconds($this->sync_interval)->isPast();
    }

    /**
     * Scope for active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for integrations by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for integrations due for sync
     */
    public function scopeDueForSync($query)
    {
        return $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('last_sync_at')
                          ->orWhereRaw('last_sync_at + INTERVAL sync_interval SECOND < NOW()');
                    });
    }
}
