<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\CrmIntegration;
use App\Models\Lead;
use App\Models\CrmSyncLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CrmIntegrationService
{
    public function syncFormSubmissionToCrm(FormSubmission $submission): bool
    {
        try {
            $form = $submission->form;
            $crmConfig = $form->crm_integration_config;
            
            if (empty($crmConfig) || !$crmConfig['enabled']) {
                return false;
            }
            
            $provider = $crmConfig['provider'] ?? 'salesforce';
            $integration = $this->getCrmIntegration($provider);
            
            if (!$integration) {
                throw new Exception("CRM integration not found for provider: {$provider}");
            }
            
            $leadData = $this->mapSubmissionToLeadData($submission, $crmConfig);
            $crmResponse = $this->sendToCrm($integration, $leadData);
            
            if ($crmResponse['success']) {
                $submission->update([
                    'crm_sync_status' => 'synced',
                    'crm_lead_id' => $crmResponse['lead_id'] ?? null,
                    'status' => 'synced'
                ]);
                
                // Create lead record if configured
                if ($crmConfig['create_lead_record'] ?? false) {
                    $this->createLeadRecord($submission, $crmResponse);
                }
                
                $this->logCrmSync($submission, 'success', $crmResponse);
                return true;
            } else {
                throw new Exception($crmResponse['error'] ?? 'Unknown CRM sync error');
            }
            
        } catch (Exception $e) {
            $submission->update([
                'crm_sync_status' => 'failed',
                'crm_sync_error' => [
                    'message' => $e->getMessage(),
                    'timestamp' => now()->toISOString()
                ]
            ]);
            
            $this->logCrmSync($submission, 'failed', ['error' => $e->getMessage()]);
            
            Log::error('CRM sync failed for form submission', [
                'submission_id' => $submission->id,
                'form_id' => $submission->form_id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    private function getCrmIntegration(string $provider): ?CrmIntegration
    {
        return CrmIntegration::where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }

 private function mapSubmissionToLeadData(FormSubmission $submission, array $crmConfig): array
    {
        $submissionData = $submission->submission_data;
        $fieldMappings = $crmConfig['field_mappings'] ?? [];
        $leadData = [];
        
        // Map form fields to CRM fields
        foreach ($fieldMappings as $formField => $crmField) {
            if (isset($submissionData[$formField])) {
                $leadData[$crmField] = $submissionData[$formField];
            }
        }
        
        // Add default fields
        $leadData['source'] = 'form_submission';
        $leadData['form_name'] = $submission->form->name;
        $leadData['submission_date'] = $submission->created_at->toISOString();
        
        // Add UTM parameters if available
        if ($submission->utm_source) {
            $leadData['utm_source'] = $submission->utm_source;
        }
        if ($submission->utm_medium) {
            $leadData['utm_medium'] = $submission->utm_medium;
        }
        if ($submission->utm_campaign) {
            $leadData['utm_campaign'] = $submission->utm_campaign;
        }
        
        return $leadData;
    }

    private function sendToCrm(CrmIntegration $integration, array $leadData): array
    {
        $provider = $integration->provider;
        
        switch ($provider) {
            case 'salesforce':
                return $this->sendToSalesforce($integration, $leadData);
            case 'hubspot':
                return $this->sendToHubspot($integration, $leadData);
            case 'pipedrive':
                return $this->sendToPipedrive($integration, $leadData);
            default:
                throw new Exception("Unsupported CRM provider: {$provider}");
        }
    }

    private function sendToSalesforce(CrmIntegration $integration, array $leadData): array
    {
        $config = $integration->configuration;
        $accessToken = $this->getSalesforceAccessToken($integration);
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
            'Content-Type' => 'application/json'
        ])->post($config['instance_url'] . '/services/data/v58.0/sobjects/Lead/', $leadData);
        
        if ($response->successful()) {
            $responseData = $response->json();
            return [
                'success' => true,
                'lead_id' => $responseData['id'],
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'Salesforce API error',
                'response' => $response->json()
            ];
        }
    }

    private function sendToHubspot(CrmIntegration $integration, array $leadData): array
    {
        $config = $integration->configuration;
        $apiKey = $config['api_key'];
        
        // Transform data for HubSpot format
        $hubspotData = [
            'properties' => []
        ];
        
        foreach ($leadData as $key => $value) {
            $hubspotData['properties'][$key] = $value;
        }
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->post('https://api.hubapi.com/crm/v3/objects/contacts', $hubspotData);
        
        if ($response->successful()) {
            $responseData = $response->json();
            return [
                'success' => true,
                'lead_id' => $responseData['id'],
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => $response->json()['message'] ?? 'HubSpot API error',
                'response' => $response->json()
            ];
        }
    }
   private function sendToPipedrive(CrmIntegration $integration, array $leadData): array
    {
        $config = $integration->configuration;
        $apiToken = $config['api_token'];
        $companyDomain = $config['company_domain'];
        
        $response = Http::post("https://{$companyDomain}.pipedrive.com/api/v1/persons", array_merge($leadData, [
            'api_token' => $apiToken
        ]));
        
        if ($response->successful()) {
            $responseData = $response->json();
            return [
                'success' => true,
                'lead_id' => $responseData['data']['id'],
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => $response->json()['error'] ?? 'Pipedrive API error',
                'response' => $response->json()
            ];
        }
    }

    private function getSalesforceAccessToken(CrmIntegration $integration): string
    {
        $config = $integration->configuration;
        
        // Check if we have a valid access token
        if (isset($config['access_token']) && isset($config['token_expires_at'])) {
            if (now()->lt($config['token_expires_at'])) {
                return $config['access_token'];
            }
        }
        
        // Refresh the token
        $response = Http::asForm()->post($config['token_url'], [
            'grant_type' => 'client_credentials',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret']
        ]);
        
        if ($response->successful()) {
            $tokenData = $response->json();
            
            // Update the integration with new token
            $integration->update([
                'configuration' => array_merge($config, [
                    'access_token' => $tokenData['access_token'],
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] - 300) // 5 min buffer
                ])
            ]);
            
            return $tokenData['access_token'];
        } else {
            throw new Exception('Failed to refresh Salesforce access token');
        }
    }

    private function createLeadRecord(FormSubmission $submission, array $crmResponse): Lead
    {
        return Lead::create([
            'first_name' => $submission->submission_data['first_name'] ?? null,
            'last_name' => $submission->submission_data['last_name'] ?? null,
            'email' => $submission->submission_data['email'] ?? null,
            'phone' => $submission->submission_data['phone'] ?? null,
            'company' => $submission->submission_data['company'] ?? null,
            'job_title' => $submission->submission_data['job_title'] ?? null,
            'source' => 'form_submission',
            'crm_id' => $crmResponse['lead_id'] ?? null,
            'form_submission_id' => $submission->id,
            'tenant_id' => $submission->tenant_id
        ]);
    }

    private function logCrmSync(FormSubmission $submission, string $status, array $data): void
    {
        CrmSyncLog::create([
            'submission_id' => $submission->id,
            'provider' => $submission->form->crm_integration_config['provider'] ?? 'unknown',
            'status' => $status,
            'request_data' => $data['request'] ?? [],
            'response_data' => $data['response'] ?? $data,
            'error_message' => $data['error'] ?? null,
            'synced_at' => now()
        ]);
    }

    public function retrySyncFailedSubmissions(): int
    {
        $failedSubmissions = FormSubmission::failedCrmSync()
            ->with('form')
            ->limit(50)
            ->get();
        
        $retryCount = 0;
        
        foreach ($failedSubmissions as $submission) {
            if ($this->syncFormSubmissionToCrm($submission)) {
                $retryCount++;
            }
        }
        
        return $retryCount;
    }
}