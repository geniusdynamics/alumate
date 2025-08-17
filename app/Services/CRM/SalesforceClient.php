<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;

class SalesforceClient implements CrmClientInterface
{
    private array $config;

    private string $baseUrl;

    private ?string $accessToken = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = $config['instance_url'] ?? 'https://login.salesforce.com';
    }

    public function testConnection(): array
    {
        try {
            $this->authenticate();

            $response = $this->makeRequest('GET', '/services/data/v58.0/sobjects/Lead/describe');

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

        $salesforceData = $this->mapToSalesforceFields($data);

        $response = $this->makeRequest('POST', '/services/data/v58.0/sobjects/Lead', $salesforceData);

        if ($response['status'] === 201) {
            return [
                'id' => $response['data']['id'],
                'success' => true,
                'data' => $response['data'],
            ];
        }

        throw new \Exception('Failed to create lead in Salesforce: '.($response['error'] ?? 'Unknown error'));
    }

    public function updateLead(string $crmId, array $data): array
    {
        $this->authenticate();

        $salesforceData = $this->mapToSalesforceFields($data);

        $response = $this->makeRequest('PATCH', "/services/data/v58.0/sobjects/Lead/{$crmId}", $salesforceData);

        if ($response['status'] === 204) {
            return [
                'id' => $crmId,
                'success' => true,
                'data' => ['id' => $crmId],
            ];
        }

        throw new \Exception('Failed to update lead in Salesforce: '.($response['error'] ?? 'Unknown error'));
    }

    public function getLead(string $crmId): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', "/services/data/v58.0/sobjects/Lead/{$crmId}");

        if ($response['status'] === 200) {
            return $response['data'];
        }

        throw new \Exception('Failed to get lead from Salesforce: '.($response['error'] ?? 'Unknown error'));
    }

    public function deleteLead(string $crmId): bool
    {
        $this->authenticate();

        $response = $this->makeRequest('DELETE', "/services/data/v58.0/sobjects/Lead/{$crmId}");

        return $response['status'] === 204;
    }

    public function searchLeads(array $criteria): array
    {
        $this->authenticate();

        $whereClause = implode(' AND ', array_map(function ($key, $value) {
            return "{$key} = '{$value}'";
        }, array_keys($criteria), $criteria));

        $soql = "SELECT Id, FirstName, LastName, Email, Company FROM Lead WHERE {$whereClause}";

        $response = $this->makeRequest('GET', '/services/data/v58.0/query', [
            'q' => $soql,
        ]);

        if ($response['status'] === 200) {
            return $response['data']['records'] ?? [];
        }

        return [];
    }

    public function getAvailableFields(): array
    {
        $this->authenticate();

        $response = $this->makeRequest('GET', '/services/data/v58.0/sobjects/Lead/describe');

        if ($response['status'] === 200) {
            return array_map(function ($field) {
                return [
                    'name' => $field['name'],
                    'label' => $field['label'],
                    'type' => $field['type'],
                    'required' => ! $field['nillable'] && ! $field['defaultedOnCreate'],
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

        $response = Http::asForm()->post($this->baseUrl.'/services/oauth2/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $this->accessToken = $data['access_token'];
            $this->baseUrl = $data['instance_url'];
        } else {
            throw new \Exception('Failed to authenticate with Salesforce: '.$response->body());
        }
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $request = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->accessToken,
                'Content-Type' => 'application/json',
            ]);

            if ($method === 'GET' && ! empty($data)) {
                $response = $request->get($this->baseUrl.$endpoint, $data);
            } else {
                $response = $request->$method($this->baseUrl.$endpoint, $data);
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

    private function mapToSalesforceFields(array $data): array
    {
        $mapping = [
            'first_name' => 'FirstName',
            'last_name' => 'LastName',
            'email' => 'Email',
            'phone' => 'Phone',
            'company' => 'Company',
            'job_title' => 'Title',
            'lead_source' => 'LeadSource',
            'website' => 'Website',
            'status' => 'Status',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $salesforceField = $mapping[$key] ?? $key;
            $mapped[$salesforceField] = $value;
        }

        return $mapped;
    }
}
