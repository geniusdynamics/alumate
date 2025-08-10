# CRM Integrations

This document provides comprehensive information about the CRM integration system, including supported providers, configuration, and usage.

## Supported CRM Providers

### 1. Salesforce
- **Authentication**: OAuth 2.0 with client credentials
- **API Version**: v58.0
- **Configuration**:
  ```json
  {
    "client_id": "your_salesforce_client_id",
    "client_secret": "your_salesforce_client_secret",
    "instance_url": "https://your-instance.salesforce.com"
  }
  ```

### 2. HubSpot
- **Authentication**: Bearer token
- **API Version**: v3
- **Configuration**:
  ```json
  {
    "access_token": "your_hubspot_access_token"
  }
  ```

### 3. Zoho CRM
- **Authentication**: OAuth 2.0 with refresh token
- **API Version**: v2
- **Configuration**:
  ```json
  {
    "client_id": "your_zoho_client_id",
    "client_secret": "your_zoho_client_secret",
    "refresh_token": "your_zoho_refresh_token",
    "api_domain": "https://www.zohoapis.com"
  }
  ```

### 4. Frappe CRM
- **Authentication**: API Key + Secret
- **API Version**: Latest
- **Configuration**:
  ```json
  {
    "instance_url": "https://your-frappe-site.com",
    "api_key": "your_frappe_api_key",
    "api_secret": "your_frappe_api_secret"
  }
  ```

### 5. Twenty CRM
- **Authentication**: Bearer token (API Key)
- **API Type**: GraphQL
- **Configuration**:
  ```json
  {
    "instance_url": "https://your-twenty-instance.com",
    "api_key": "your_twenty_api_key"
  }
  ```

## Field Mappings

Each CRM provider has different field names. The system uses configurable field mappings to translate between our internal lead fields and CRM-specific fields.

### Standard Lead Fields
- `first_name`
- `last_name`
- `email`
- `phone`
- `company`
- `job_title`
- `lead_source`
- `status`
- `notes`

### Provider-Specific Mappings

#### Salesforce
```json
{
  "first_name": "FirstName",
  "last_name": "LastName",
  "email": "Email",
  "phone": "Phone",
  "company": "Company",
  "job_title": "Title",
  "lead_source": "LeadSource",
  "status": "Status"
}
```

#### HubSpot
```json
{
  "first_name": "firstname",
  "last_name": "lastname",
  "email": "email",
  "phone": "phone",
  "company": "company",
  "job_title": "jobtitle",
  "lead_source": "hs_lead_status"
}
```

#### Zoho CRM
```json
{
  "first_name": "First_Name",
  "last_name": "Last_Name",
  "email": "Email",
  "phone": "Phone",
  "company": "Company",
  "job_title": "Designation",
  "lead_source": "Lead_Source",
  "status": "Lead_Status"
}
```

#### Frappe CRM
```json
{
  "first_name": "first_name",
  "last_name": "last_name",
  "email": "email_id",
  "phone": "phone",
  "company": "company_name",
  "job_title": "designation",
  "lead_source": "source",
  "status": "status"
}
```

#### Twenty CRM
```json
{
  "first_name": "firstName",
  "last_name": "lastName",
  "email": "email",
  "phone": "phone",
  "company": "companyName",
  "job_title": "jobTitle"
}
```

## Configuration

### Creating a CRM Integration

```php
use App\Models\CrmIntegration;

$integration = CrmIntegration::create([
    'name' => 'My Salesforce Integration',
    'provider' => 'salesforce',
    'config' => [
        'client_id' => 'your_client_id',
        'client_secret' => 'your_client_secret',
        'instance_url' => 'https://your-instance.salesforce.com'
    ],
    'is_active' => true,
    'sync_direction' => 'push', // push, pull, or bidirectional
    'sync_interval' => 3600, // seconds
    'field_mappings' => [
        'first_name' => 'FirstName',
        'last_name' => 'LastName',
        'email' => 'Email',
        // ... other mappings
    ]
]);
```

### Testing Connection

```php
$result = $integration->testConnection();

if ($result['success']) {
    echo "Connection successful!";
} else {
    echo "Connection failed: " . $result['message'];
}
```

### Syncing Leads

```php
use App\Models\Lead;

$lead = Lead::find(1);
$result = $integration->syncLead($lead);

if ($result['success']) {
    echo "Lead synced successfully!";
    echo "CRM ID: " . $result['data']['id'];
} else {
    echo "Sync failed: " . $result['message'];
}
```

## API Usage

### Admin Controller Endpoints

#### Get CRM Integrations
```http
GET /admin/lead-management/crm-integrations
```

#### Create CRM Integration
```http
POST /admin/lead-management/crm-integrations
Content-Type: application/json

{
  "name": "Salesforce Integration",
  "provider": "salesforce",
  "config": {
    "client_id": "your_client_id",
    "client_secret": "your_client_secret",
    "instance_url": "https://your-instance.salesforce.com"
  },
  "is_active": true,
  "sync_direction": "push",
  "sync_interval": 3600,
  "field_mappings": {
    "first_name": "FirstName",
    "last_name": "LastName",
    "email": "Email"
  }
}
```

#### Test CRM Connection
```http
POST /admin/lead-management/crm-integrations/{integration}/test
```

#### Bulk Sync Leads
```http
POST /admin/lead-management/bulk-sync
Content-Type: application/json

{
  "lead_ids": [1, 2, 3, 4, 5]
}
```

## Advanced Features

### Frappe CRM Specific Features

#### Convert Lead to Customer
```php
$client = new \App\Services\CRM\FrappeCrmClient($config);
$result = $client->convertToCustomer($leadId, [
    'customer_type' => 'Company',
    'customer_group' => 'Commercial'
]);
```

#### Create Opportunity
```php
$result = $client->createOpportunity($leadId, [
    'opportunity_amount' => 50000,
    'probability' => 75,
    'expected_closing' => '2024-12-31'
]);
```

#### Add Notes
```php
$result = $client->addNote($leadId, 'Follow-up call scheduled', 'Call Scheduled');
```

### Twenty CRM Specific Features

#### Create Company
```php
$client = new \App\Services\CRM\TwentyCrmClient($config);
$result = $client->createCompany([
    'name' => 'Acme Corp',
    'domain' => 'acme.com',
    'employees' => 100,
    'annual_revenue' => 1000000
]);
```

#### Create Opportunity
```php
$result = $client->createOpportunity([
    'name' => 'Q4 Deal',
    'amount' => 75000,
    'stage' => 'PROPOSAL',
    'probability' => 80,
    'person_id' => $personId
]);
```

## Error Handling

All CRM clients implement consistent error handling:

```php
try {
    $result = $client->createLead($data);
    // Handle success
} catch (\Exception $e) {
    // Handle error
    $errorMessage = $e->getMessage();
    Log::error("CRM sync failed: {$errorMessage}");
}
```

## Sync Scheduling

The system supports automatic synchronization based on configurable intervals:

```php
// Get integrations due for sync
$dueIntegrations = CrmIntegration::dueForSync()->get();

foreach ($dueIntegrations as $integration) {
    if ($integration->isSyncDue()) {
        // Perform sync
        $leads = Lead::where('updated_at', '>', $integration->last_sync_at)->get();
        
        foreach ($leads as $lead) {
            $integration->syncLead($lead);
        }
        
        $integration->updateSyncResult([
            'success' => true,
            'synced_count' => $leads->count(),
            'timestamp' => now()
        ]);
    }
}
```

## Best Practices

1. **Rate Limiting**: Respect API rate limits for each provider
2. **Error Handling**: Implement proper error handling and logging
3. **Field Validation**: Validate data before sending to CRM
4. **Batch Operations**: Use batch operations when available
5. **Monitoring**: Monitor sync success rates and errors
6. **Security**: Store API credentials securely
7. **Testing**: Test integrations thoroughly before production use

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Check API credentials
   - Verify token expiration
   - Ensure proper permissions

2. **Field Mapping Errors**
   - Verify field names match CRM schema
   - Check required fields
   - Validate data types

3. **Rate Limiting**
   - Implement exponential backoff
   - Reduce sync frequency
   - Use batch operations

4. **Network Issues**
   - Check connectivity
   - Verify firewall settings
   - Monitor API status

### Debugging

Enable debug logging:

```php
// In config/logging.php
'channels' => [
    'crm' => [
        'driver' => 'single',
        'path' => storage_path('logs/crm.log'),
        'level' => 'debug',
    ],
],
```

Use in CRM clients:
```php
Log::channel('crm')->debug('CRM API Request', [
    'provider' => $this->provider,
    'endpoint' => $endpoint,
    'data' => $data
]);
```

## Contributing

When adding new CRM providers:

1. Implement the `CrmClientInterface`
2. Add provider to validation rules
3. Update factory and tests
4. Add documentation
5. Test thoroughly

Example implementation structure:
```php
<?php

namespace App\Services\CRM;

class NewCrmClient implements CrmClientInterface
{
    public function testConnection(): array { /* ... */ }
    public function createLead(array $data): array { /* ... */ }
    public function updateLead(string $crmId, array $data): array { /* ... */ }
    public function getLead(string $crmId): array { /* ... */ }
    public function deleteLead(string $crmId): bool { /* ... */ }
    public function searchLeads(array $criteria): array { /* ... */ }
    public function getAvailableFields(): array { /* ... */ }
}
```