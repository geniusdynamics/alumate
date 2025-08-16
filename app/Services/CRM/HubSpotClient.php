<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;

class HubSpotClient implements CrmClientInterface
{
    private array $config;

    private string $baseUrl = 'https://api.hubapi.com';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function testConnection(): array
    {
        $response = $this->makeRequest('GET', '/crm/v3/objects/contacts', [
            'limit' => 1,
        ]);

        return [
            'connected' => $response['status'] === 200,
            'message' => $response['status'] === 200 ? 'Connection successful' : 'Connection failed',
            'data' => $response['data'] ?? null,
        ];
    }

    public function createLead(array $data): array
    {
        $hubspotData = [
            'properties' => $this->mapToHubSpotFields($data),
        ];

        $response = $this->makeRequest('POST', '/crm/v3/objects/contacts', $hubspotData);

        if ($response['status'] === 201) {
            return [
                'id' => $response['data']['id'],
                'success' => true,
                'data' => $response['data'],
            ];
        }

        throw new \Exception('Failed to create lead in HubSpot: '.($response['error'] ?? 'Unknown error'));
    }

    public function updateLead(string $crmId, array $data): array
    {
        $hubspotData = [
            'properties' => $this->mapToHubSpotFields($data),
        ];

        $response = $this->makeRequest('PATCH', "/crm/v3/objects/contacts/{$crmId}", $hubspotData);

        if ($response['status'] === 200) {
            return [
                'id' => $crmId,
                'success' => true,
                'data' => $response['data'],
            ];
        }

        throw new \Exception('Failed to update lead in HubSpot: '.($response['error'] ?? 'Unknown error'));
    }

    public function getLead(string $crmId): array
    {
        $response = $this->makeRequest('GET', "/crm/v3/objects/contacts/{$crmId}");

        if ($response['status'] === 200) {
            return $response['data'];
        }

        throw new \Exception('Failed to get lead from HubSpot: '.($response['error'] ?? 'Unknown error'));
    }

    public function deleteLead(string $crmId): bool
    {
        $response = $this->makeRequest('DELETE', "/crm/v3/objects/contacts/{$crmId}");

        return $response['status'] === 204;
    }

    public function searchLeads(array $criteria): array
    {
        $searchData = [
            'filterGroups' => [
                [
                    'filters' => array_map(function ($key, $value) {
                        return [
                            'propertyName' => $key,
                            'operator' => 'EQ',
                            'value' => $value,
                        ];
                    }, array_keys($criteria), $criteria),
                ],
            ],
        ];

        $response = $this->makeRequest('POST', '/crm/v3/objects/contacts/search', $searchData);

        if ($response['status'] === 200) {
            return $response['data']['results'] ?? [];
        }

        return [];
    }

    public function getAvailableFields(): array
    {
        $response = $this->makeRequest('GET', '/crm/v3/properties/contacts');

        if ($response['status'] === 200) {
            return array_map(function ($field) {
                return [
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'required' => $field['required'] ?? false,
                ];
            }, $response['data']['results'] ?? []);
        }

        return [];
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['access_token'],
                'Content-Type' => 'application/json',
            ])->$method($this->baseUrl.$endpoint, $data);

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

    private function mapToHubSpotFields(array $data): array
    {
        $mapping = [
            'first_name' => 'firstname',
            'last_name' => 'lastname',
            'email' => 'email',
            'phone' => 'phone',
            'company' => 'company',
            'job_title' => 'jobtitle',
            'lead_source' => 'hs_lead_status',
            'website' => 'website',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $hubspotField = $mapping[$key] ?? $key;
            $mapped[$hubspotField] = $value;
        }

        return $mapped;
    }
}
