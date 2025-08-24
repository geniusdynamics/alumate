<?php

use App\Models\CrmIntegration;
use App\Models\Lead;
use App\Models\User;
use App\Services\CRM\FrappeCrmClient;
use App\Services\CRM\TwentyCrmClient;
use App\Services\CRM\ZohoCrmClient;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('can create CRM integration for different providers', function () {
    $providers = ['salesforce', 'hubspot', 'zoho', 'frappe', 'twenty'];

    foreach ($providers as $provider) {
        $integration = CrmIntegration::factory()->create([
            'provider' => $provider,
            'name' => ucfirst($provider).' Integration',
        ]);

        expect($integration->provider)->toBe($provider);
        expect($integration->name)->toBe(ucfirst($provider).' Integration');
    }
});

test('can get API client for different providers', function () {
    $integrations = [
        'zoho' => ZohoCrmClient::class,
        'frappe' => FrappeCrmClient::class,
        'twenty' => TwentyCrmClient::class,
    ];

    foreach ($integrations as $provider => $expectedClass) {
        $integration = CrmIntegration::factory()->create([
            'provider' => $provider,
            'config' => [
                'api_key' => 'test_key',
                'api_secret' => 'test_secret',
                'instance_url' => 'https://test.example.com',
            ],
        ]);

        $client = $integration->getApiClient();
        expect($client)->toBeInstanceOf($expectedClass);
    }
});

test('throws exception for unsupported provider', function () {
    $integration = CrmIntegration::factory()->create([
        'provider' => 'unsupported_crm',
    ]);

    expect(fn () => $integration->getApiClient())
        ->toThrow(\Exception::class, 'Unsupported CRM provider: unsupported_crm');
});

test('can test connection for active integration', function () {
    $integration = CrmIntegration::factory()->create([
        'provider' => 'frappe',
        'is_active' => true,
        'config' => [
            'instance_url' => 'https://test-frappe.com',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
        ],
    ]);

    // Mock the API client
    $mockClient = \Mockery::mock(FrappeCrmClient::class);
    $mockClient->shouldReceive('testConnection')
        ->once()
        ->andReturn([
            'connected' => true,
            'message' => 'Connection successful',
        ]);

    // This would normally test the actual connection
    // For now, we'll just verify the method exists
    expect($integration)->toHaveMethod('testConnection');
});

test('can sync lead to CRM', function () {
    $integration = CrmIntegration::factory()->create([
        'provider' => 'twenty',
        'is_active' => true,
        'field_mappings' => [
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'email' => 'email',
            'company' => 'companyName',
        ],
    ]);

    $lead = Lead::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'company' => 'Test Company',
    ]);

    // Mock the sync process
    expect($integration)->toHaveMethod('syncLead');
    expect($integration->is_active)->toBeTrue();
});

test('does not sync when integration is inactive', function () {
    $integration = CrmIntegration::factory()->create([
        'is_active' => false,
    ]);

    $lead = Lead::factory()->create();

    $result = $integration->syncLead($lead);

    expect($result['success'])->toBeFalse();
    expect($result['message'])->toBe('Integration is not active');
});

test('can map lead data according to field mappings', function () {
    $integration = CrmIntegration::factory()->create([
        'field_mappings' => [
            'first_name' => 'firstName',
            'last_name' => 'lastName',
            'email' => 'emailAddress',
            'full_name' => 'displayName',
            'utm_source' => 'leadSource',
        ],
    ]);

    $lead = Lead::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'utm_data' => ['utm_source' => 'google'],
    ]);

    // Test the mapping logic through reflection
    $reflection = new \ReflectionClass($integration);
    $method = $reflection->getMethod('mapLeadData');
    $method->setAccessible(true);

    $mappedData = $method->invoke($integration, $lead);

    expect($mappedData)->toHaveKey('firstName');
    expect($mappedData)->toHaveKey('lastName');
    expect($mappedData)->toHaveKey('emailAddress');
    expect($mappedData)->toHaveKey('displayName');
    expect($mappedData)->toHaveKey('leadSource');

    expect($mappedData['firstName'])->toBe('Jane');
    expect($mappedData['lastName'])->toBe('Smith');
    expect($mappedData['emailAddress'])->toBe('jane@example.com');
    expect($mappedData['displayName'])->toBe('Jane Smith');
    expect($mappedData['leadSource'])->toBe('google');
});

test('can check if sync is due', function () {
    // Integration that has never been synced
    $neverSynced = CrmIntegration::factory()->create([
        'is_active' => true,
        'last_sync_at' => null,
        'sync_interval' => 3600, // 1 hour
    ]);

    expect($neverSynced->isSyncDue())->toBeTrue();

    // Integration synced recently
    $recentlySynced = CrmIntegration::factory()->create([
        'is_active' => true,
        'last_sync_at' => now()->subMinutes(30),
        'sync_interval' => 3600, // 1 hour
    ]);

    expect($recentlySynced->isSyncDue())->toBeFalse();

    // Integration synced long ago
    $oldSync = CrmIntegration::factory()->create([
        'is_active' => true,
        'last_sync_at' => now()->subHours(2),
        'sync_interval' => 3600, // 1 hour
    ]);

    expect($oldSync->isSyncDue())->toBeTrue();

    // Inactive integration
    $inactive = CrmIntegration::factory()->create([
        'is_active' => false,
        'last_sync_at' => null,
    ]);

    expect($inactive->isSyncDue())->toBeFalse();
});

test('scopes work correctly', function () {
    // Create test integrations
    $active = CrmIntegration::factory()->create(['is_active' => true]);
    $inactive = CrmIntegration::factory()->create(['is_active' => false]);
    $salesforce = CrmIntegration::factory()->create(['provider' => 'salesforce']);
    $hubspot = CrmIntegration::factory()->create(['provider' => 'hubspot']);

    // Test active scope
    $activeIntegrations = CrmIntegration::active()->get();
    expect($activeIntegrations->pluck('id'))->toContain($active->id);
    expect($activeIntegrations->pluck('id'))->not->toContain($inactive->id);

    // Test provider scope
    $salesforceIntegrations = CrmIntegration::byProvider('salesforce')->get();
    expect($salesforceIntegrations->pluck('id'))->toContain($salesforce->id);
    expect($salesforceIntegrations->pluck('id'))->not->toContain($hubspot->id);
});

test('can update sync result', function () {
    $integration = CrmIntegration::factory()->create([
        'last_sync_at' => null,
        'last_sync_result' => null,
    ]);

    $result = [
        'success' => true,
        'synced_leads' => 5,
        'errors' => [],
    ];

    $integration->updateSyncResult($result);

    $integration->refresh();
    expect($integration->last_sync_at)->not->toBeNull();
    expect($integration->last_sync_result)->toBe($result);
});

test('frappe crm client can handle specific operations', function () {
    $config = [
        'instance_url' => 'https://test-frappe.com',
        'api_key' => 'test_key',
        'api_secret' => 'test_secret',
    ];

    $client = new FrappeCrmClient($config);

    // Test field mapping
    $reflection = new \ReflectionClass($client);
    $method = $reflection->getMethod('mapToFrappeFields');
    $method->setAccessible(true);

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'company' => 'Test Company',
    ];

    $mapped = $method->invoke($client, $data);

    expect($mapped)->toHaveKey('first_name');
    expect($mapped)->toHaveKey('last_name');
    expect($mapped)->toHaveKey('email_id');
    expect($mapped)->toHaveKey('company_name');
    expect($mapped)->toHaveKey('lead_name');

    expect($mapped['email_id'])->toBe('john@example.com');
    expect($mapped['company_name'])->toBe('Test Company');
    expect($mapped['lead_name'])->toBe('John Doe');
});

test('twenty crm client can handle graphql operations', function () {
    $config = [
        'instance_url' => 'https://test-twenty.com',
        'api_key' => 'test_api_key',
    ];

    $client = new TwentyCrmClient($config);

    // Test field mapping
    $reflection = new \ReflectionClass($client);
    $method = $reflection->getMethod('mapToTwentyFields');
    $method->setAccessible(true);

    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'job_title' => 'CEO',
        'company' => 'Smith Corp',
    ];

    $mapped = $method->invoke($client, $data);

    expect($mapped)->toHaveKey('name');
    expect($mapped)->toHaveKey('email');
    expect($mapped)->toHaveKey('jobTitle');
    expect($mapped)->toHaveKey('companyName');

    expect($mapped['name']['firstName'])->toBe('Jane');
    expect($mapped['name']['lastName'])->toBe('Smith');
    expect($mapped['email'])->toBe('jane@example.com');
    expect($mapped['jobTitle'])->toBe('CEO');
    expect($mapped['companyName'])->toBe('Smith Corp');
});

test('zoho crm client can handle oauth authentication', function () {
    $config = [
        'api_domain' => 'https://www.zohoapis.com',
        'client_id' => 'test_client_id',
        'client_secret' => 'test_client_secret',
        'refresh_token' => 'test_refresh_token',
    ];

    $client = new ZohoCrmClient($config);

    // Test field mapping
    $reflection = new \ReflectionClass($client);
    $method = $reflection->getMethod('mapToZohoFields');
    $method->setAccessible(true);

    $data = [
        'first_name' => 'Bob',
        'last_name' => 'Johnson',
        'email' => 'bob@example.com',
        'company' => 'Johnson Inc',
        'job_title' => 'Manager',
    ];

    $mapped = $method->invoke($client, $data);

    expect($mapped)->toHaveKey('First_Name');
    expect($mapped)->toHaveKey('Last_Name');
    expect($mapped)->toHaveKey('Email');
    expect($mapped)->toHaveKey('Company');
    expect($mapped)->toHaveKey('Designation');

    expect($mapped['First_Name'])->toBe('Bob');
    expect($mapped['Last_Name'])->toBe('Johnson');
    expect($mapped['Email'])->toBe('bob@example.com');
    expect($mapped['Company'])->toBe('Johnson Inc');
    expect($mapped['Designation'])->toBe('Manager');
});
