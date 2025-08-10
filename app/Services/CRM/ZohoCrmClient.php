<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;

class ZohoCrmClient implements CrmClientInterface
{
    private array $config;
    private string $baseUrl;
    private ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['api_domain'] ?? 'https://www.zohoapis.com';
    }

    public function testConnection(): array
    {
        try {
            $this->authenticate();
            
            $response = $this->makeRequest('GET', '/crm/v2/settings/modules');

            return [
                'connected' => $response['status'] === 200,
                'message' => $response['status'] === 200 ? 'Connection successful' : 'Connection failed',
                'data' => $response['data'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    public function createLead(array $data): array
    {
        $this->authenticate();

        $zohoData = [
            'data' => [
                $this->mapToZohoFields($data)
            ]
        ];

        $response = $this->makeRequest('POST', '/crm/v2/Leads', $zohoData);

        if ($response['status'] === 201 && isset($response['data']['data'][0])) {
            $leadData = $response['data']['data'][0];
            
            return [
                'id' => $leadData['details']['id'],
                'success' => true,
                'data' => $leadData,
            ];
        }

        throw new \Exception('Failed to create lead in Zoho CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function updateLead(string $crmId, array $data): array
    {
        $this->authenticate();

        $zohoData = [
            'data' => [
                array_merge(['id' => $crmId], $this->mapToZohoFields($data))
            ]
        ];

        $response = $this->makeRequest('PUT', '/crm/v2/Leads', $zohoData);

        if ($response['status'] === 200) {
            return [
                'id' => $crmId,
                'success' => true,
                'data' => $response['data']['data'][0] ?? ['id' => $crmId],
            ];
        }

        throw new \Exception('Failed to update lead in Zoho CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function getLead(string $crmId): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', "/crm/v2/Leads/{$crmId}");

        if ($response['status'] === 200 && isset($response['data']['data'][0])) {
            return $response['data']['data'][0];
        }

        throw new \Exception('Failed to get lead from Zoho CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function deleteLead(string $crmId): bool
    {
        $this->authenticate();

        $response = $this->makeRequest('DELETE', "/crm/v2/Leads/{$crmId}");

        return $response['status'] === 200;
    }

    public function searchLeads(array $criteria): array
    {
        $this->authenticate();

        $searchCriteria = [];
        foreach ($criteria as $key => $value) {
            $zohoField = $this->mapToZohoFields([$key => $value]);
            $zohoFieldName = array_keys($zohoField)[0];
            $searchCriteria[] = "({$zohoFieldName}:equals:{$value})";
        }

        $criteriaString = implode(' and ', $searchCriteria);

        $response = $this->makeRequest('GET', '/crm/v2/Leads/search', [
            'criteria' => $criteriaString
        ]);

        if ($response['status'] === 200) {
            return $response['data']['data'] ?? [];
        }

        return [];
    }

    public function getAvailableFields(): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', '/crm/v2/settings/fields?module=Leads');

        if ($response['status'] === 200) {
            return array_map(function ($field) {
                return [
                    'name' => $field['api_name'],
                    'label' => $field['field_label'],
                    'type' => $field['data_type'],
                    'required' => $field['required'] ?? false,
                ];
            }, $response['data']['fields'] ?? []);
        }

        return [];
    }

    private function authenticate(): void
    {
        if ($this->accessToken) {
            return;
        }

        // Zoho uses OAuth 2.0 with refresh tokens
        if (isset($this->config['refresh_token'])) {
            $this->refreshAccessToken();
        } else {
            throw new \Exception('No refresh token available for Zoho CRM authentication');
        }
    }

    private function refreshAccessToken(): void
    {
        $response = Http::asForm()->post('https://accounts.zoho.com/oauth/v2/token', [
            'refresh_token' => $this->config['refresh_token'],
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->accessToken = $data['access_token'];
        } else {
            throw new \Exception('Failed to refresh Zoho CRM access token: ' . $response->body());
        }
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $request = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ]);

            if ($method === 'GET' && !empty($data)) {
                $response = $request->get($this->baseUrl . $endpoint, $data);
            } else {
                $response = $request->$method($this->baseUrl . $endpoint, $data);
            }

            return [
                'status' => $response->status(),
                'data' => $response->json(),
                'error' => $response->failed() ? $response->body() : null,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 500,
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function mapToZohoFields(array $data): array
    {
        $mapping = [
            'first_name' => 'First_Name',
            'last_name' => 'Last_Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'company' => 'Company',
            'job_title' => 'Designation',
            'lead_source' => 'Lead_Source',
            'website' => 'Website',
            'status' => 'Lead_Status',
            'industry' => 'Industry',
            'annual_revenue' => 'Annual_Revenue',
            'no_of_employees' => 'No_of_Employees',
            'description' => 'Description',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $zohoField = $mapping[$key] ?? $key;
            $mapped[$zohoField] = $value;
        }

        return $mapped;
    }
}

