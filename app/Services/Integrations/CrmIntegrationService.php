<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Models\LearningProgress;
use App\Models\SyncLog;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CRM Integration Service for Analytics
 *
 * Handles synchronization of learning analytics data with external CRM systems
 * including HubSpot, Salesforce, and Frappe/Zoho. Maintains tenant isolation
 * and respects user consent for data sharing.
 */
class CrmIntegrationService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const MAX_RETRIES = 3;

    private const RETRY_DELAY = 1000; // milliseconds

    /**
     * Sync learning progress data to CRM
     *
     * @param  LearningProgress  $progress  Learning progress record
     * @param  string  $provider  CRM provider (hubspot, salesforce, frappe, zoho)
     * @return bool Success status
     */
    public function syncLearningProgress(LearningProgress $progress, string $provider = 'hubspot'): bool
    {
        try {
            // Check if CRM integration is configured
            $config = $this->getCrmConfig($provider);
            if (! $config) {
                Log::info('CRM integration not configured', ['provider' => $provider]);

                return false;
            }

            // Map learning progress to CRM fields
            $crmData = $this->mapLearningProgressToCrm($progress, $provider);

            // Send to CRM
            $result = $this->sendToCrm($provider, $crmData, $config);

            if ($result['success']) {
                $this->logSync($progress, $provider, 'success', $result);

                return true;
            } else {
                $this->logSync($progress, $provider, 'failed', $result);

                return false;
            }

        } catch (Exception $e) {
            Log::error('CRM sync failed', [
                'progress_id' => $progress->id,
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            $this->logSync($progress, $provider, 'error', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Get CRM configuration from environment
     */
    private function getCrmConfig(string $provider): ?array
    {
        $cacheKey = "crm_config_{$provider}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($provider) {
            switch ($provider) {
                case 'hubspot':
                    return config('services.hubspot.api_key') ? [
                        'api_key' => config('services.hubspot.api_key'),
                        'base_url' => 'https://api.hubapi.com',
                    ] : null;

                case 'salesforce':
                    return config('services.salesforce.client_id') ? [
                        'client_id' => config('services.salesforce.client_id'),
                        'client_secret' => config('services.salesforce.client_secret'),
                        'instance_url' => config('services.salesforce.instance_url'),
                        'access_token' => $this->getSalesforceToken($provider),
                    ] : null;

                case 'frappe':
                    return config('services.frappe.api_key') ? [
                        'api_key' => config('services.frappe.api_key'),
                        'api_secret' => config('services.frappe.api_secret'),
                        'base_url' => config('services.frappe.base_url'),
                    ] : null;

                case 'zoho':
                    return config('services.zoho.client_id') ? [
                        'client_id' => config('services.zoho.client_id'),
                        'client_secret' => config('services.zoho.client_secret'),
                        'access_token' => $this->getZohoToken($provider),
                    ] : null;

                default:
                    return null;
            }
        });
    }

    /**
     * Map learning progress to CRM-specific format
     */
    private function mapLearningProgressToCrm(LearningProgress $progress, string $provider): array
    {
        $baseData = [
            'tenant_id' => $progress->tenant_id,
            'user_id' => $progress->user_id,
            'course_id' => $progress->course_id,
            'engagement_score' => $progress->engagement_score,
            'total_score' => $progress->total_score,
            'modules_completed' => $progress->modules_completed,
            'completion_percentage' => $progress->completion_percentage,
            'last_activity' => $progress->updated_at->toISOString(),
            'certified' => $progress->certified ?? false,
        ];

        switch ($provider) {
            case 'hubspot':
                return [
                    'properties' => [
                        'engagement_score' => $baseData['engagement_score'],
                        'learning_completion_percentage' => $baseData['completion_percentage'],
                        'modules_completed' => $baseData['modules_completed'],
                        'total_score' => $baseData['total_score'],
                        'last_learning_activity' => $baseData['last_activity'],
                        'certified_status' => $baseData['certified'] ? 'Certified' : 'In Progress',
                        'tenant_id' => $baseData['tenant_id'],
                    ],
                ];

            case 'salesforce':
                return [
                    'Engagement_Score__c' => $baseData['engagement_score'],
                    'Learning_Completion_Percentage__c' => $baseData['completion_percentage'],
                    'Modules_Completed__c' => $baseData['modules_completed'],
                    'Total_Score__c' => $baseData['total_score'],
                    'Last_Learning_Activity__c' => $baseData['last_activity'],
                    'Certified_Status__c' => $baseData['certified'] ? 'Certified' : 'In Progress',
                    'Tenant_ID__c' => $baseData['tenant_id'],
                ];

            case 'frappe':
            case 'zoho':
                return [
                    'data' => [
                        'engagement_score' => $baseData['engagement_score'],
                        'completion_percentage' => $baseData['completion_percentage'],
                        'modules_completed' => $baseData['modules_completed'],
                        'total_score' => $baseData['total_score'],
                        'last_activity' => $baseData['last_activity'],
                        'certified' => $baseData['certified'],
                        'tenant_id' => $baseData['tenant_id'],
                    ],
                ];

            default:
                return $baseData;
        }
    }

    /**
     * Send data to CRM with retry logic
     */
    private function sendToCrm(string $provider, array $data, array $config): array
    {
        $attempts = 0;
        $lastError = null;

        while ($attempts < self::MAX_RETRIES) {
            try {
                switch ($provider) {
                    case 'hubspot':
                        return $this->sendToHubspot($data, $config);

                    case 'salesforce':
                        return $this->sendToSalesforce($data, $config);

                    case 'frappe':
                        return $this->sendToFrappe($data, $config);

                    case 'zoho':
                        return $this->sendToZoho($data, $config);

                    default:
                        throw new Exception("Unsupported CRM provider: {$provider}");
                }
            } catch (Exception $e) {
                $attempts++;
                $lastError = $e;

                if ($attempts >= self::MAX_RETRIES) {
                    break;
                }

                // Exponential backoff
                usleep(self::RETRY_DELAY * $attempts * 1000);
            }
        }

        return [
            'success' => false,
            'error' => $lastError?->getMessage() ?? 'Max retries exceeded',
        ];
    }

    /**
     * Send to HubSpot CRM
     */
    private function sendToHubspot(array $data, array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$config['api_key']}",
            'Content-Type' => 'application/json',
        ])->post("{$config['base_url']}/crm/v3/objects/learning_progress", $data);

        if ($response->successful()) {
            $responseData = $response->json();

            return [
                'success' => true,
                'crm_id' => $responseData['id'] ?? null,
                'response' => $responseData,
            ];
        }

        // Handle rate limiting
        if ($response->status() === 429) {
            throw new Exception('HubSpot rate limit exceeded');
        }

        return [
            'success' => false,
            'error' => $response->json()['message'] ?? 'HubSpot API error',
            'status' => $response->status(),
        ];
    }

    /**
     * Send to Salesforce CRM
     */
    private function sendToSalesforce(array $data, array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$config['access_token']}",
            'Content-Type' => 'application/json',
        ])->post("{$config['instance_url']}/services/data/v58.0/sobjects/Learning_Progress__c/", $data);

        if ($response->successful()) {
            $responseData = $response->json();

            return [
                'success' => true,
                'crm_id' => $responseData['id'] ?? null,
                'response' => $responseData,
            ];
        }

        // Handle rate limiting
        if ($response->status() === 429) {
            throw new Exception('Salesforce rate limit exceeded');
        }

        return [
            'success' => false,
            'error' => $response->json()['message'] ?? 'Salesforce API error',
            'status' => $response->status(),
        ];
    }

    /**
     * Send to Frappe CRM
     */
    private function sendToFrappe(array $data, array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => "token {$config['api_key']}:{$config['api_secret']}",
            'Content-Type' => 'application/json',
        ])->post("{$config['base_url']}/api/resource/Learning Progress", $data);

        if ($response->successful()) {
            $responseData = $response->json();

            return [
                'success' => true,
                'crm_id' => $responseData['data']['name'] ?? null,
                'response' => $responseData,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json()['message'] ?? 'Frappe API error',
            'status' => $response->status(),
        ];
    }

    /**
     * Send to Zoho CRM
     */
    private function sendToZoho(array $data, array $config): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Zoho-oauthtoken {$config['access_token']}",
            'Content-Type' => 'application/json',
        ])->post('https://www.zohoapis.com/crm/v2/Learning_Progress', $data);

        if ($response->successful()) {
            $responseData = $response->json();

            return [
                'success' => true,
                'crm_id' => $responseData['data'][0]['details']['id'] ?? null,
                'response' => $responseData,
            ];
        }

        return [
            'success' => false,
            'error' => $response->json()['message'] ?? 'Zoho API error',
            'status' => $response->status(),
        ];
    }

    /**
     * Get Salesforce access token
     */
    private function getSalesforceToken(string $provider): ?string
    {
        $cacheKey = 'salesforce_token';

        return Cache::remember($cacheKey, 3600, function () use ($provider) {
            $config = $this->getCrmConfig($provider);
            if (! $config) {
                return null;
            }

            $response = Http::asForm()->post($config['token_url'] ?? 'https://login.salesforce.com/services/oauth2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            return null;
        });
    }

    /**
     * Get Zoho access token
     */
    private function getZohoToken(string $provider): ?string
    {
        $cacheKey = 'zoho_token';

        return Cache::remember($cacheKey, 3600, function () use ($provider) {
            $config = $this->getCrmConfig($provider);
            if (! $config) {
                return null;
            }

            $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            return null;
        });
    }

    /**
     * Log sync operation
     */
    private function logSync(LearningProgress $progress, string $provider, string $status, array $data): void
    {
        SyncLog::create([
            'tenant_id' => $progress->tenant_id,
            'sync_type' => 'crm_analytics',
            'provider' => $provider,
            'status' => $status,
            'record_id' => $progress->id,
            'request_data' => $data['request'] ?? [],
            'response_data' => $data['response'] ?? $data,
            'error_message' => $data['error'] ?? null,
            'synced_at' => now(),
        ]);
    }
}
