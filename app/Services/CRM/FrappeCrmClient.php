<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;

class FrappeCrmClient implements CrmClientInterface
{
    private array $config;
    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = rtrim($config['instance_url'] ?? 'https://your-frappe-site.com', '/');
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('GET', '/api/resource/Lead', [
                'limit_page_length' => 1
            ]);

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
        $frappeData = $this->mapToFrappeFields($data);

        $response = $this->makeRequest('POST', '/api/resource/Lead', $frappeData);

        if ($response['status'] === 200) {
            return [
                'id' => $response['data']['data']['name'],
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to create lead in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function updateLead(string $crmId, array $data): array
    {
        $frappeData = $this->mapToFrappeFields($data);

        $response = $this->makeRequest('PUT', "/api/resource/Lead/{$crmId}", $frappeData);

        if ($response['status'] === 200) {
            return [
                'id' => $crmId,
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to update lead in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function getLead(string $crmId): array
    {
        $response = $this->makeRequest('GET', "/api/resource/Lead/{$crmId}");

        if ($response['status'] === 200) {
            return $response['data']['data'];
        }

        throw new \Exception('Failed to get lead from Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    public function deleteLead(string $crmId): bool
    {
        $response = $this->makeRequest('DELETE', "/api/resource/Lead/{$crmId}");

        return $response['status'] === 202;
    }

    public function searchLeads(array $criteria): array
    {
        $filters = [];
        foreach ($criteria as $key => $value) {
            $frappeField = $this->mapToFrappeFields([$key => $value]);
            $frappeFieldName = array_keys($frappeField)[0];
            $filters[] = [$frappeFieldName, '=', $value];
        }

        $response = $this->makeRequest('GET', '/api/resource/Lead', [
            'filters' => json_encode($filters),
            'fields' => json_encode(['name', 'lead_name', 'email_id', 'company_name', 'status'])
        ]);

        if ($response['status'] === 200) {
            return $response['data']['data'] ?? [];
        }

        return [];
    }

    public function getAvailableFields(): array
    {
        $response = $this->makeRequest('GET', '/api/resource/DocType/Lead');

        if ($response['status'] === 200) {
            $doctype = $response['data']['data'];
            
            return array_map(function ($field) {
                return [
                    'name' => $field['fieldname'],
                    'label' => $field['label'],
                    'type' => $field['fieldtype'],
                    'required' => $field['reqd'] ?? false,
                ];
            }, $doctype['fields'] ?? []);
        }

        return [];
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Frappe uses API key and secret for authentication
            if (isset($this->config['api_key']) && isset($this->config['api_secret'])) {
                $headers['Authorization'] = 'token ' . $this->config['api_key'] . ':' . $this->config['api_secret'];
            }

            $request = Http::withHeaders($headers);

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

    private function mapToFrappeFields(array $data): array
    {
        $mapping = [
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'email' => 'email_id',
            'phone' => 'phone',
            'company' => 'company_name',
            'job_title' => 'designation',
            'lead_source' => 'source',
            'website' => 'website',
            'status' => 'status',
            'industry' => 'industry',
            'annual_revenue' => 'annual_revenue',
            'no_of_employees' => 'no_of_employees',
            'description' => 'notes',
            'city' => 'city',
            'state' => 'state',
            'country' => 'country',
            'territory' => 'territory',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $frappeField = $mapping[$key] ?? $key;
            $mapped[$frappeField] = $value;
        }

        // Set lead_name if not provided
        if (!isset($mapped['lead_name']) && (isset($mapped['first_name']) || isset($mapped['last_name']))) {
            $mapped['lead_name'] = trim(($mapped['first_name'] ?? '') . ' ' . ($mapped['last_name'] ?? ''));
        }

        return $mapped;
    }

    /**
     * Create a customer from lead
     */
    public function convertToCustomer(string $leadId, array $customerData = []): array
    {
        $lead = $this->getLead($leadId);
        
        $customerData = array_merge([
            'customer_name' => $lead['lead_name'],
            'customer_type' => 'Individual',
            'customer_group' => 'All Customer Groups',
            'territory' => $lead['territory'] ?? 'All Territories',
        ], $customerData);

        $response = $this->makeRequest('POST', '/api/resource/Customer', $customerData);

        if ($response['status'] === 200) {
            // Update lead status to converted
            $this->updateLead($leadId, ['status' => 'Converted']);
            
            return [
                'id' => $response['data']['data']['name'],
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to convert lead to customer in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    /**
     * Create an opportunity from lead
     */
    public function createOpportunity(string $leadId, array $opportunityData): array
    {
        $lead = $this->getLead($leadId);
        
        $opportunityData = array_merge([
            'opportunity_from' => 'Lead',
            'party_name' => $leadId,
            'customer_name' => $lead['lead_name'],
            'opportunity_type' => 'Sales',
            'source' => $lead['source'] ?? 'Existing Customer',
        ], $opportunityData);

        $response = $this->makeRequest('POST', '/api/resource/Opportunity', $opportunityData);

        if ($response['status'] === 200) {
            return [
                'id' => $response['data']['data']['name'],
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to create opportunity in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    /**
     * Add a note/comment to lead
     */
    public function addNote(string $leadId, string $content, string $title = 'Lead Note'): array
    {
        $noteData = [
            'note' => $content,
            'title' => $title,
            'reference_type' => 'Lead',
            'reference_name' => $leadId,
            'public' => 1,
        ];

        $response = $this->makeRequest('POST', '/api/resource/Note', $noteData);

        if ($response['status'] === 200) {
            return [
                'id' => $response['data']['data']['name'],
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to add note in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }

    /**
     * Get lead activities/communications
     */
    public function getLeadActivities(string $leadId): array
    {
        $response = $this->makeRequest('GET', '/api/resource/Communication', [
            'filters' => json_encode([
                ['reference_doctype', '=', 'Lead'],
                ['reference_name', '=', $leadId]
            ]),
            'fields' => json_encode(['name', 'subject', 'content', 'communication_type', 'creation'])
        ]);

        if ($response['status'] === 200) {
            return $response['data']['data'] ?? [];
        }

        return [];
    }

    /**
     * Create a quotation for the lead
     */
    public function createQuotation(string $leadId, array $quotationData): array
    {
        $lead = $this->getLead($leadId);
        
        $quotationData = array_merge([
            'quotation_to' => 'Lead',
            'party_name' => $leadId,
            'customer_name' => $lead['lead_name'],
        ], $quotationData);

        $response = $this->makeRequest('POST', '/api/resource/Quotation', $quotationData);

        if ($response['status'] === 200) {
            return [
                'id' => $response['data']['data']['name'],
                'success' => true,
                'data' => $response['data']['data'],
            ];
        }

        throw new \Exception('Failed to create quotation in Frappe CRM: ' . ($response['error'] ?? 'Unknown error'));
    }
}