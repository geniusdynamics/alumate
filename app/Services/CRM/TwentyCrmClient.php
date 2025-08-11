<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Http;

class TwentyCrmClient implements CrmClientInterface
{
    private array $config;

    private string $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = rtrim($config['instance_url'] ?? 'https://your-twenty-instance.com', '/');
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('POST', '/graphql', [
                'query' => '
                    query GetPeople($first: Int) {
                        people(first: $first) {
                            edges {
                                node {
                                    id
                                    name {
                                        firstName
                                        lastName
                                    }
                                }
                            }
                        }
                    }
                ',
                'variables' => ['first' => 1],
            ]);

            return [
                'connected' => $response['status'] === 200 && ! isset($response['data']['errors']),
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
        $twentyData = $this->mapToTwentyFields($data);

        // Twenty CRM uses GraphQL mutations
        $mutation = '
            mutation CreatePerson($data: PersonCreateInput!) {
                createPerson(data: $data) {
                    id
                    name {
                        firstName
                        lastName
                    }
                    email
                    phone
                    jobTitle
                    companyId
                    createdAt
                    updatedAt
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => ['data' => $twentyData],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['createPerson'])) {
            $personData = $response['data']['data']['createPerson'];

            return [
                'id' => $personData['id'],
                'success' => true,
                'data' => $personData,
            ];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to create lead in Twenty CRM: '.$errorMessage);
    }

    public function updateLead(string $crmId, array $data): array
    {
        $twentyData = $this->mapToTwentyFields($data);

        $mutation = '
            mutation UpdatePerson($id: ID!, $data: PersonUpdateInput!) {
                updatePerson(id: $id, data: $data) {
                    id
                    name {
                        firstName
                        lastName
                    }
                    email
                    phone
                    jobTitle
                    companyId
                    updatedAt
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => [
                'id' => $crmId,
                'data' => $twentyData,
            ],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['updatePerson'])) {
            return [
                'id' => $crmId,
                'success' => true,
                'data' => $response['data']['data']['updatePerson'],
            ];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to update lead in Twenty CRM: '.$errorMessage);
    }

    public function getLead(string $crmId): array
    {
        $query = '
            query GetPerson($id: ID!) {
                person(id: $id) {
                    id
                    name {
                        firstName
                        lastName
                    }
                    email
                    phone
                    jobTitle
                    companyId
                    company {
                        id
                        name
                        domainName
                    }
                    createdAt
                    updatedAt
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $query,
            'variables' => ['id' => $crmId],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['person'])) {
            return $response['data']['data']['person'];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to get lead from Twenty CRM: '.$errorMessage);
    }

    public function deleteLead(string $crmId): bool
    {
        $mutation = '
            mutation DeletePerson($id: ID!) {
                deletePerson(id: $id) {
                    id
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => ['id' => $crmId],
        ]);

        return $response['status'] === 200 && isset($response['data']['data']['deletePerson']);
    }

    public function searchLeads(array $criteria): array
    {
        // Build filter conditions for GraphQL
        $filters = [];
        foreach ($criteria as $key => $value) {
            $twentyField = $this->mapFieldName($key);
            $filters[] = [
                'field' => $twentyField,
                'operator' => 'eq',
                'value' => $value,
            ];
        }

        $query = '
            query SearchPeople($filters: [PersonFilterInput!]) {
                people(filter: { and: $filters }) {
                    edges {
                        node {
                            id
                            name {
                                firstName
                                lastName
                            }
                            email
                            phone
                            jobTitle
                            companyId
                            company {
                                name
                            }
                        }
                    }
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $query,
            'variables' => ['filters' => $filters],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['people']['edges'])) {
            return array_map(function ($edge) {
                return $edge['node'];
            }, $response['data']['data']['people']['edges']);
        }

        return [];
    }

    public function getAvailableFields(): array
    {
        // Twenty CRM uses GraphQL introspection to get schema
        $query = '
            query IntrospectPersonType {
                __type(name: "Person") {
                    fields {
                        name
                        type {
                            name
                            kind
                        }
                        description
                    }
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $query,
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['__type']['fields'])) {
            return array_map(function ($field) {
                return [
                    'name' => $field['name'],
                    'label' => ucwords(str_replace('_', ' ', $field['name'])),
                    'type' => $field['type']['name'] ?? $field['type']['kind'],
                    'required' => false, // Twenty CRM doesn't expose required info via introspection
                    'description' => $field['description'],
                ];
            }, $response['data']['data']['__type']['fields']);
        }

        return [];
    }

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Twenty CRM uses API key authentication
            if (isset($this->config['api_key'])) {
                $headers['Authorization'] = 'Bearer '.$this->config['api_key'];
            }

            $request = Http::withHeaders($headers);

            $response = $request->$method($this->baseUrl.$endpoint, $data);

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

    private function mapToTwentyFields(array $data): array
    {
        $mapped = [];

        // Handle name fields
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $mapped['name'] = [
                'firstName' => $data['first_name'] ?? '',
                'lastName' => $data['last_name'] ?? '',
            ];
        }

        // Map other fields
        $fieldMapping = [
            'email' => 'email',
            'phone' => 'phone',
            'job_title' => 'jobTitle',
            'website' => 'linkedinLink', // Twenty doesn't have website field, using LinkedIn as alternative
        ];

        foreach ($fieldMapping as $sourceField => $targetField) {
            if (isset($data[$sourceField])) {
                $mapped[$targetField] = $data[$sourceField];
            }
        }

        // Handle company creation/linking
        if (isset($data['company']) && ! empty($data['company'])) {
            // In a real implementation, you'd first check if company exists or create it
            $mapped['companyName'] = $data['company'];
        }

        return $mapped;
    }

    private function mapFieldName(string $fieldName): string
    {
        $mapping = [
            'first_name' => 'name.firstName',
            'last_name' => 'name.lastName',
            'email' => 'email',
            'phone' => 'phone',
            'job_title' => 'jobTitle',
            'company' => 'company.name',
        ];

        return $mapping[$fieldName] ?? $fieldName;
    }

    /**
     * Create a company in Twenty CRM
     */
    public function createCompany(array $data): array
    {
        $mutation = '
            mutation CreateCompany($data: CompanyCreateInput!) {
                createCompany(data: $data) {
                    id
                    name
                    domainName
                    employees
                    linkedinLink
                    xLink
                    annualRecurringRevenue {
                        amountMicros
                        currencyCode
                    }
                    createdAt
                    updatedAt
                }
            }
        ';

        $companyData = [
            'name' => $data['name'],
            'domainName' => $data['domain'] ?? null,
            'employees' => isset($data['employees']) ? (int) $data['employees'] : null,
            'linkedinLink' => $data['linkedin_url'] ?? null,
            'xLink' => $data['twitter_url'] ?? null,
        ];

        if (isset($data['annual_revenue'])) {
            $companyData['annualRecurringRevenue'] = [
                'amountMicros' => (int) ($data['annual_revenue'] * 1000000), // Convert to micros
                'currencyCode' => $data['currency'] ?? 'USD',
            ];
        }

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => ['data' => $companyData],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['createCompany'])) {
            return [
                'id' => $response['data']['data']['createCompany']['id'],
                'success' => true,
                'data' => $response['data']['data']['createCompany'],
            ];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to create company in Twenty CRM: '.$errorMessage);
    }

    /**
     * Create an opportunity in Twenty CRM
     */
    public function createOpportunity(array $data): array
    {
        $mutation = '
            mutation CreateOpportunity($data: OpportunityCreateInput!) {
                createOpportunity(data: $data) {
                    id
                    name
                    amount {
                        amountMicros
                        currencyCode
                    }
                    stage
                    probability
                    closeDate
                    personId
                    companyId
                    createdAt
                    updatedAt
                }
            }
        ';

        $opportunityData = [
            'name' => $data['name'],
            'stage' => $data['stage'] ?? 'NEW',
            'probability' => isset($data['probability']) ? (int) $data['probability'] : 0,
            'closeDate' => $data['close_date'] ?? null,
            'personId' => $data['person_id'] ?? null,
            'companyId' => $data['company_id'] ?? null,
        ];

        if (isset($data['amount'])) {
            $opportunityData['amount'] = [
                'amountMicros' => (int) ($data['amount'] * 1000000), // Convert to micros
                'currencyCode' => $data['currency'] ?? 'USD',
            ];
        }

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => ['data' => $opportunityData],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['createOpportunity'])) {
            return [
                'id' => $response['data']['data']['createOpportunity']['id'],
                'success' => true,
                'data' => $response['data']['data']['createOpportunity'],
            ];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to create opportunity in Twenty CRM: '.$errorMessage);
    }

    /**
     * Add a note/activity to a person
     */
    public function addNote(string $personId, string $content, string $title = 'Lead Note'): array
    {
        $mutation = '
            mutation CreateActivity($data: ActivityCreateInput!) {
                createActivity(data: $data) {
                    id
                    title
                    body
                    type
                    authorId
                    assigneeId
                    createdAt
                    updatedAt
                }
            }
        ';

        $activityData = [
            'title' => $title,
            'body' => $content,
            'type' => 'NOTE',
            'assigneeId' => $personId,
        ];

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $mutation,
            'variables' => ['data' => $activityData],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['createActivity'])) {
            return [
                'id' => $response['data']['data']['createActivity']['id'],
                'success' => true,
                'data' => $response['data']['data']['createActivity'],
            ];
        }

        $errorMessage = $response['data']['errors'][0]['message'] ?? 'Unknown error';
        throw new \Exception('Failed to add note in Twenty CRM: '.$errorMessage);
    }

    /**
     * Get activities for a person
     */
    public function getPersonActivities(string $personId): array
    {
        $query = '
            query GetActivities($assigneeId: ID!) {
                activities(filter: { assigneeId: { eq: $assigneeId } }) {
                    edges {
                        node {
                            id
                            title
                            body
                            type
                            dueAt
                            completedAt
                            createdAt
                            updatedAt
                            author {
                                id
                                name {
                                    firstName
                                    lastName
                                }
                            }
                        }
                    }
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $query,
            'variables' => ['assigneeId' => $personId],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['activities']['edges'])) {
            return array_map(function ($edge) {
                return $edge['node'];
            }, $response['data']['data']['activities']['edges']);
        }

        return [];
    }

    /**
     * Get opportunities for a person
     */
    public function getPersonOpportunities(string $personId): array
    {
        $query = '
            query GetOpportunities($personId: ID!) {
                opportunities(filter: { personId: { eq: $personId } }) {
                    edges {
                        node {
                            id
                            name
                            amount {
                                amountMicros
                                currencyCode
                            }
                            stage
                            probability
                            closeDate
                            createdAt
                            updatedAt
                        }
                    }
                }
            }
        ';

        $response = $this->makeRequest('POST', '/graphql', [
            'query' => $query,
            'variables' => ['personId' => $personId],
        ]);

        if ($response['status'] === 200 && isset($response['data']['data']['opportunities']['edges'])) {
            return array_map(function ($edge) {
                return $edge['node'];
            }, $response['data']['data']['opportunities']['edges']);
        }

        return [];
    }
}
