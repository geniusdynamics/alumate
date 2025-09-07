<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateCrmIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'provider',
        'config',
        'is_active',
        'sync_direction',
        'sync_interval',
        'last_sync_at',
        'last_sync_result',
        'field_mappings',
        'sync_filters',
        'webhook_secret',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'last_sync_result' => 'array',
        'field_mappings' => 'array',
        'sync_filters' => 'array',
        'sync_interval' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'sync_direction' => 'one_way',
        'sync_interval' => 3600,
        'config' => '{}',
        'field_mappings' => '{}',
        'sync_filters' => '{}',
    ];

    /**
     * Supported CRM providers
     */
    public const PROVIDERS = [
        'salesforce',
        'hubspot',
        'pipedrive',
        'zoho',
        'twenty',
        'frappe',
        'custom',
    ];

    /**
     * Sync directions
     */
    public const SYNC_DIRECTIONS = [
        'one_way', // Only sync from template to CRM
        'two_way', // Sync both ways
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to active integrations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query by provider
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

    /**
     * Get the tenant that owns the integration
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get sync logs for this integration
     */
    public function syncLogs(): HasMany
    {
        return $this->hasMany(TemplateCrmSyncLog::class);
    }

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
     * Sync a template to the CRM
     */
    public function syncTemplate(Template $template): array
    {
        if (!$this->is_active) {
            return [
                'success' => false,
                'message' => 'Integration is not active',
            ];
        }

        try {
            $client = $this->getApiClient();
            $mappedData = $this->mapTemplateData($template);

            // Create sync log
            $syncLog = $this->createSyncLog($template, 'create', $mappedData);

            $result = $client->createLead($mappedData);

            // Update sync log with result
            $syncLog->update([
                'status' => 'success',
                'crm_record_id' => $result['id'] ?? null,
                'response_data' => $result,
                'synced_at' => now(),
            ]);

            $this->updateSyncResult([
                'success' => true,
                'message' => 'Template synced successfully',
                'crm_record_id' => $result['id'] ?? null,
            ]);

            return [
                'success' => true,
                'message' => 'Template synced successfully',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            // Update sync log with error
            $syncLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->updateSyncResult([
                'success' => false,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Map template data according to field mappings
     */
    private function mapTemplateData(Template $template): array
    {
        $mappedData = [];

        foreach ($this->field_mappings as $templateField => $crmField) {
            $value = $this->getTemplateFieldValue($template, $templateField);
            if ($value !== null) {
                $mappedData[$crmField] = $value;
            }
        }

        return $mappedData;
    }

    /**
     * Get field value from template
     */
    private function getTemplateFieldValue(Template $template, string $field)
    {
        switch ($field) {
            case 'name':
                return $template->name;
            case 'description':
                return $template->description;
            case 'category':
                return $template->category;
            case 'audience_type':
                return $template->audience_type;
            case 'campaign_type':
                return $template->campaign_type;
            case 'usage_count':
                return $template->usage_count;
            case 'conversion_rate':
                return $template->getConversionRate();
            case 'load_time':
                return $template->getLoadTime();
            case 'tags':
                return $template->tags;
            default:
                return $template->$field ?? null;
        }
    }

    /**
     * Create sync log entry
     */
    private function createSyncLog(Template $template, string $syncType, array $data): TemplateCrmSyncLog
    {
        return $this->syncLogs()->create([
            'tenant_id' => $this->tenant_id,
            'template_id' => $template->id,
            'sync_type' => $syncType,
            'crm_provider' => $this->provider,
            'sync_data' => $data,
            'status' => 'pending',
        ]);
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
     * Get available fields from CRM
     */
    public function getAvailableFields(): array
    {
        try {
            $client = $this->getApiClient();
            return $client->getAvailableFields();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate field mappings
     */
    public function validateFieldMappings(): array
    {
        $availableFields = $this->getAvailableFields();
        $availableFieldNames = array_column($availableFields, 'name');

        $errors = [];
        foreach ($this->field_mappings as $templateField => $crmField) {
            if (!in_array($crmField, $availableFieldNames)) {
                $errors[] = "CRM field '{$crmField}' is not available in {$this->provider}";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
