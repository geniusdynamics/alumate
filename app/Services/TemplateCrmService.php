<?php

namespace App\Services;

use App\Models\Template;
use App\Models\TemplateCrmIntegration;
use App\Models\TemplateCrmSyncLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Template CRM Service
 *
 * Core business logic for template CRM integration, sync management,
 * and data mapping with tenant isolation.
 */
class TemplateCrmService
{
    /**
     * Cache keys and durations
     */
    private const CACHE_PREFIX = 'template_crm_';
    private const CACHE_DURATION = 300; // 5 minutes

    /**
     * Sync templates to all active CRM integrations
     *
     * @param array $templateIds Specific template IDs to sync (optional)
     * @return array
     */
    public function syncTemplatesToCrm(array $templateIds = []): array
    {
        $results = [
            'total_processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        try {
            // Get active CRM integrations
            $integrations = TemplateCrmIntegration::active()->get();

            if ($integrations->isEmpty()) {
                return array_merge($results, [
                    'message' => 'No active CRM integrations found'
                ]);
            }

            // Get templates to sync
            $templates = $this->getTemplatesToSync($templateIds);

            foreach ($integrations as $integration) {
                foreach ($templates as $template) {
                    $results['total_processed']++;

                    try {
                        $syncResult = $integration->syncTemplate($template);

                        if ($syncResult['success']) {
                            $results['successful']++;
                        } else {
                            $results['failed']++;
                        }

                        $results['details'][] = [
                            'integration_id' => $integration->id,
                            'template_id' => $template->id,
                            'provider' => $integration->provider,
                            'success' => $syncResult['success'],
                            'message' => $syncResult['message']
                        ];

                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['details'][] = [
                            'integration_id' => $integration->id,
                            'template_id' => $template->id,
                            'provider' => $integration->provider,
                            'success' => false,
                            'message' => $e->getMessage()
                        ];

                        Log::error('Template CRM sync failed', [
                            'integration_id' => $integration->id,
                            'template_id' => $template->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Template CRM sync process failed', [
                'error' => $e->getMessage(),
                'template_ids' => $templateIds
            ]);

            return array_merge($results, [
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * Sync templates based on filters
     *
     * @param array $filters Template filters (category, audience_type, etc.)
     * @return array
     */
    public function syncTemplatesByFilters(array $filters): array
    {
        $query = Template::query();

        // Apply filters
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['audience_type'])) {
            $query->where('audience_type', $filters['audience_type']);
        }

        if (isset($filters['campaign_type'])) {
            $query->where('campaign_type', $filters['campaign_type']);
        }

        if (isset($filters['is_premium'])) {
            $query->where('is_premium', $filters['is_premium']);
        }

        if (isset($filters['usage_threshold'])) {
            $query->where('usage_count', '>=', $filters['usage_threshold']);
        }

        $templateIds = $query->pluck('id')->toArray();

        return $this->syncTemplatesToCrm($templateIds);
    }

    /**
     * Get CRM integrations for a tenant
     *
     * @param int $tenantId
     * @return Collection
     */
    public function getTenantCrmIntegrations(int $tenantId): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "tenant_{$tenantId}_integrations";

        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($tenantId) {
            return TemplateCrmIntegration::forTenant($tenantId)->get();
        });
    }

    /**
     * Create a new CRM integration
     *
     * @param array $data
     * @return TemplateCrmIntegration
     */
    public function createCrmIntegration(array $data): TemplateCrmIntegration
    {
        // Validate provider
        if (!in_array($data['provider'], TemplateCrmIntegration::PROVIDERS)) {
            throw new \InvalidArgumentException("Unsupported CRM provider: {$data['provider']}");
        }

        $integration = TemplateCrmIntegration::create($data);

        // Clear cache
        $this->clearTenantCache($data['tenant_id']);

        return $integration;
    }

    /**
     * Update CRM integration
     *
     * @param int $integrationId
     * @param array $data
     * @return TemplateCrmIntegration
     */
    public function updateCrmIntegration(int $integrationId, array $data): TemplateCrmIntegration
    {
        $integration = TemplateCrmIntegration::findOrFail($integrationId);

        $integration->update($data);

        // Clear cache
        $this->clearTenantCache($integration->tenant_id);

        return $integration;
    }

    /**
     * Delete CRM integration
     *
     * @param int $integrationId
     * @return bool
     */
    public function deleteCrmIntegration(int $integrationId): bool
    {
        $integration = TemplateCrmIntegration::findOrFail($integrationId);

        $tenantId = $integration->tenant_id;

        $integration->delete();

        // Clear cache
        $this->clearTenantCache($tenantId);

        return true;
    }

    /**
     * Test CRM integration connection
     *
     * @param int $integrationId
     * @return array
     */
    public function testCrmConnection(int $integrationId): array
    {
        $integration = TemplateCrmIntegration::findOrFail($integrationId);

        return $integration->testConnection();
    }

    /**
     * Get sync logs for a tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @return Collection
     */
    public function getSyncLogs(int $tenantId, array $filters = []): Collection
    {
        $query = TemplateCrmSyncLog::forTenant($tenantId)->with(['crmIntegration', 'template']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['provider'])) {
            $query->where('crm_provider', $filters['provider']);
        }

        if (isset($filters['sync_type'])) {
            $query->where('sync_type', $filters['sync_type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get sync statistics for a tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getSyncStatistics(int $tenantId): array
    {
        $logs = TemplateCrmSyncLog::forTenant($tenantId)->get();

        $stats = [
            'total_syncs' => $logs->count(),
            'successful_syncs' => $logs->where('status', 'success')->count(),
            'failed_syncs' => $logs->where('status', 'failed')->count(),
            'pending_syncs' => $logs->where('status', 'pending')->count(),
            'success_rate' => 0,
            'by_provider' => [],
            'by_sync_type' => [],
            'recent_activity' => []
        ];

        if ($stats['total_syncs'] > 0) {
            $stats['success_rate'] = round(($stats['successful_syncs'] / $stats['total_syncs']) * 100, 2);
        }

        // Group by provider
        $stats['by_provider'] = $logs->groupBy('crm_provider')->map(function ($group) {
            return [
                'total' => $group->count(),
                'successful' => $group->where('status', 'success')->count(),
                'failed' => $group->where('status', 'failed')->count(),
            ];
        });

        // Group by sync type
        $stats['by_sync_type'] = $logs->groupBy('sync_type')->map(function ($group) {
            return [
                'total' => $group->count(),
                'successful' => $group->where('status', 'success')->count(),
                'failed' => $group->where('status', 'failed')->count(),
            ];
        });

        // Recent activity (last 10)
        $stats['recent_activity'] = $logs->sortByDesc('created_at')->take(10)->map(function ($log) {
            return $log->getFormattedSyncData();
        });

        return $stats;
    }

    /**
     * Process webhook from CRM provider
     *
     * @param string $provider
     * @param array $payload
     * @return array
     */
    public function processCrmWebhook(string $provider, array $payload): array
    {
        try {
            // Find active integrations for this provider
            $integrations = TemplateCrmIntegration::active()
                ->where('provider', $provider)
                ->get();

            if ($integrations->isEmpty()) {
                return [
                    'success' => false,
                    'message' => "No active integrations found for provider: {$provider}"
                ];
            }

            $results = [];
            foreach ($integrations as $integration) {
                // Process webhook based on provider and payload
                $result = $this->processWebhookForIntegration($integration, $payload);
                $results[] = $result;
            }

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('CRM webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate and apply field mappings
     *
     * @param int $integrationId
     * @param array $fieldMappings
     * @return array
     */
    public function validateFieldMappings(int $integrationId, array $fieldMappings): array
    {
        $integration = TemplateCrmIntegration::findOrFail($integrationId);

        $integration->field_mappings = $fieldMappings;
        $validation = $integration->validateFieldMappings();

        return $validation;
    }

    /**
     * Get available CRM fields for a provider
     *
     * @param string $provider
     * @param array $config
     * @return array
     */
    public function getAvailableCrmFields(string $provider, array $config): array
    {
        try {
            // Create a temporary client to get available fields
            $client = $this->createCrmClient($provider, $config);
            return $client->getAvailableFields();
        } catch (\Exception $e) {
            Log::error('Failed to get CRM fields', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get templates that match sync filters
     *
     * @param array $templateIds
     * @return Collection
     */
    private function getTemplatesToSync(array $templateIds = []): Collection
    {
        $query = Template::query();

        if (!empty($templateIds)) {
            $query->whereIn('id', $templateIds);
        }

        return $query->get();
    }

    /**
     * Process webhook for a specific integration
     *
     * @param TemplateCrmIntegration $integration
     * @param array $payload
     * @return array
     */
    private function processWebhookForIntegration(TemplateCrmIntegration $integration, array $payload): array
    {
        // Implementation depends on the CRM provider's webhook format
        // This is a basic implementation that can be extended

        Log::info('Processing CRM webhook', [
            'integration_id' => $integration->id,
            'provider' => $integration->provider,
            'payload_keys' => array_keys($payload)
        ]);

        // For now, just log the webhook
        // In production, this would update templates based on CRM changes

        return [
            'integration_id' => $integration->id,
            'processed' => true,
            'message' => 'Webhook logged successfully'
        ];
    }

    /**
     * Create CRM client instance
     *
     * @param string $provider
     * @param array $config
     * @return mixed
     */
    private function createCrmClient(string $provider, array $config)
    {
        switch ($provider) {
            case 'salesforce':
                return new \App\Services\CRM\SalesforceClient($config);
            case 'hubspot':
                return new \App\Services\CRM\HubSpotClient($config);
            case 'pipedrive':
                return new \App\Services\CRM\PipedriveClient($config);
            case 'zoho':
                return new \App\Services\CRM\ZohoCrmClient($config);
            case 'twenty':
                return new \App\Services\CRM\TwentyCrmClient($config);
            case 'frappe':
                return new \App\Services\CRM\FrappeCrmClient($config);
            default:
                throw new \InvalidArgumentException("Unsupported CRM provider: {$provider}");
        }
    }

    /**
     * Clear tenant-specific cache
     *
     * @param int $tenantId
     */
    private function clearTenantCache(int $tenantId): void
    {
        Cache::forget(self::CACHE_PREFIX . "tenant_{$tenantId}_integrations");
    }
}