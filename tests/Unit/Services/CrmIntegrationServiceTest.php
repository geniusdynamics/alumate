<?php

namespace Tests\Unit\Services;

use App\Models\CrmIntegration;
use App\Models\CrmSyncLog;
use App\Models\Lead;
use App\Services\CrmIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

/**
 * Unit tests for CrmIntegrationService
 */
class CrmIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CrmIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrmIntegrationService();
    }

    /**
     * Test service instantiation
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(CrmIntegrationService::class, $this->service);
    }

    /**
     * Test syncing lead to CRM when no active integration exists
     */
    public function test_sync_lead_to_crm_no_active_integration()
    {
        $lead = Lead::factory()->create();

        $result = $this->service->syncLeadToCrm($lead);

        $this->assertFalse($result['success']);
        $this->assertStringContains('No active CRM integration found', $result['message']);
    }

    /**
     * Test syncing lead to CRM with active integration
     */
    public function test_sync_lead_to_crm_with_active_integration()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);
        $lead = Lead::factory()->create();

        // Mock the CRM client
        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('createLead')
            ->once()
            ->andReturn(['id' => 'crm_123']);

        $integrationMock = Mockery::mock($integration)->makePartial();
        $integrationMock->shouldReceive('getApiClient')
            ->once()
            ->andReturn($mockClient);

        $result = $this->service->syncLeadToCrm($lead, $integrationMock);

        $this->assertTrue($result['success']);
        $this->assertEquals('crm_123', $result['crm_record_id']);
        $this->assertEquals('Lead synced successfully', $result['message']);

        // Check that lead was updated
        $lead->refresh();
        $this->assertEquals('crm_123', $lead->crm_id);
        $this->assertNotNull($lead->synced_at);

        // Check that sync log was created
        $this->assertDatabaseHas('crm_sync_logs', [
            'lead_id' => $lead->id,
            'crm_integration_id' => $integration->id,
            'sync_type' => 'create',
            'status' => 'success',
        ]);
    }

    /**
     * Test syncing lead updates
     */
    public function test_sync_lead_updates()
    {
        $integration1 = CrmIntegration::factory()->create(['is_active' => true, 'provider' => 'salesforce']);
        $integration2 = CrmIntegration::factory()->create(['is_active' => true, 'provider' => 'hubspot']);
        $lead = Lead::factory()->create(['crm_id' => 'crm_123']);

        // Mock CRM clients
        $mockClient1 = Mockery::mock();
        $mockClient1->shouldReceive('updateLead')
            ->once()
            ->andReturn(['id' => 'crm_123']);

        $mockClient2 = Mockery::mock();
        $mockClient2->shouldReceive('updateLead')
            ->once()
            ->andReturn(['id' => 'crm_123']);

        $integrationMock1 = Mockery::mock($integration1)->makePartial();
        $integrationMock1->shouldReceive('getApiClient')->andReturn($mockClient1);

        $integrationMock2 = Mockery::mock($integration2)->makePartial();
        $integrationMock2->shouldReceive('getApiClient')->andReturn($mockClient2);

        // We need to mock the query builder to return our mocked integrations
        $this->mock(CrmIntegration::class, function ($mock) use ($integrationMock1, $integrationMock2) {
            $mock->shouldReceive('active')->andReturnSelf();
            $mock->shouldReceive('get')->andReturn(collect([$integrationMock1, $integrationMock2]));
        });

        $result = $this->service->syncLeadUpdates($lead);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['results']);
    }

    /**
     * Test updating CRM record
     */
    public function test_update_crm_record()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);
        $lead = Lead::factory()->create(['crm_id' => 'crm_123']);

        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('updateLead')
            ->once()
            ->andReturn(['id' => 'crm_123']);

        $integrationMock = Mockery::mock($integration)->makePartial();
        $integrationMock->shouldReceive('getApiClient')
            ->once()
            ->andReturn($mockClient);

        $result = $this->service->updateCrmRecord($lead, $integrationMock);

        $this->assertTrue($result['success']);
        $this->assertEquals('CRM record updated successfully', $result['message']);
    }

    /**
     * Test updating CRM record when lead has no CRM ID
     */
    public function test_update_crm_record_without_crm_id()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);
        $lead = Lead::factory()->create(['crm_id' => null]);

        $result = $this->service->updateCrmRecord($lead, $integration);

        $this->assertTrue($result['success']);
        $this->assertStringContains('Lead synced successfully', $result['message']);
    }

    /**
     * Test pulling CRM updates
     */
    public function test_pull_crm_updates()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);

        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('getRecentUpdates')
            ->once()
            ->andReturn([
                [
                    'id' => 'crm_123',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'email' => 'john@example.com',
                ]
            ]);

        $integrationMock = Mockery::mock($integration)->makePartial();
        $integrationMock->shouldReceive('getApiClient')
            ->once()
            ->andReturn($mockClient);

        $result = $this->service->pullCrmUpdates($integrationMock);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['errors']);
    }

    /**
     * Test processing webhook
     */
    public function test_process_webhook()
    {
        $integration = CrmIntegration::factory()->create([
            'is_active' => true,
            'provider' => 'salesforce'
        ]);

        $payload = ['type' => 'lead_updated', 'data' => ['id' => 'crm_123']];

        $result = $this->service->processWebhook('salesforce', $payload);

        $this->assertTrue($result['success']);
        $this->assertEquals('Webhook queued for processing', $result['message']);
    }

    /**
     * Test processing webhook with inactive integration
     */
    public function test_process_webhook_no_active_integration()
    {
        $payload = ['type' => 'lead_updated', 'data' => ['id' => 'crm_123']];

        $result = $this->service->processWebhook('nonexistent', $payload);

        $this->assertFalse($result['success']);
        $this->assertStringContains('No active integration found', $result['message']);
    }

    /**
     * Test resolving sync conflict
     */
    public function test_resolve_sync_conflict()
    {
        $integration = CrmIntegration::factory()->create();
        $lead = Lead::factory()->create();
        $crmData = ['first_name' => 'Jane', 'email' => 'jane@example.com'];

        $result = $this->service->resolveSyncConflict($lead, $crmData, $integration);

        $this->assertTrue($result['success']);
        $this->assertEquals('prefer_local', $result['resolution']);
    }

    /**
     * Test getting sync status
     */
    public function test_get_sync_status()
    {
        CrmSyncLog::factory()->count(5)->create(['status' => 'success']);
        CrmSyncLog::factory()->count(2)->create(['status' => 'failed']);
        CrmSyncLog::factory()->count(1)->create(['status' => 'pending']);

        $result = $this->service->getSyncStatus();

        $this->assertIsArray($result);
        $this->assertEquals(8, $result['total_syncs']);
        $this->assertEquals(5, $result['successful_syncs']);
        $this->assertEquals(2, $result['failed_syncs']);
        $this->assertEquals(1, $result['pending_syncs']);
    }

    /**
     * Test retrying failed syncs
     */
    public function test_retry_failed_syncs()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);
        $lead = Lead::factory()->create();

        CrmSyncLog::factory()->create([
            'lead_id' => $lead->id,
            'crm_integration_id' => $integration->id,
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $result = $this->service->retryFailedSyncs($integration);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['processed']);
    }

    /**
     * Test creating sync log
     */
    public function test_create_sync_log()
    {
        $integration = CrmIntegration::factory()->create();
        $lead = Lead::factory()->create();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('createSyncLog');
        $method->setAccessible(true);

        $syncLog = $method->invoke($this->service, $lead, $integration, 'create');

        $this->assertInstanceOf(CrmSyncLog::class, $syncLog);
        $this->assertEquals($lead->id, $syncLog->lead_id);
        $this->assertEquals($integration->id, $syncLog->crm_integration_id);
        $this->assertEquals('create', $syncLog->sync_type);
        $this->assertEquals('pending', $syncLog->status);
    }

    /**
     * Test mapping lead data
     */
    public function test_map_lead_data()
    {
        $integration = CrmIntegration::factory()->create([
            'field_mappings' => [
                'first_name' => 'firstname',
                'last_name' => 'lastname',
                'email' => 'email',
                'company' => 'company',
            ]
        ]);

        $lead = Lead::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('mapLeadData');
        $method->setAccessible(true);

        $mappedData = $method->invoke($this->service, $lead, $integration);

        $this->assertEquals([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
        ], $mappedData);
    }

    /**
     * Test getting lead field value
     */
    public function test_get_lead_field_value()
    {
        $lead = Lead::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'job_title' => 'Developer',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getLeadFieldValue');
        $method->setAccessible(true);

        $this->assertEquals('John', $method->invoke($this->service, $lead, 'first_name'));
        $this->assertEquals('Doe', $method->invoke($this->service, $lead, 'last_name'));
        $this->assertEquals('john@example.com', $method->invoke($this->service, $lead, 'email'));
        $this->assertEquals('John Doe', $method->invoke($this->service, $lead, 'full_name'));
        $this->assertEquals('Acme Corp', $method->invoke($this->service, $lead, 'company'));
        $this->assertEquals('Developer', $method->invoke($this->service, $lead, 'job_title'));
    }

    /**
     * Test getting active CRM integration
     */
    public function test_get_active_crm_integration()
    {
        $activeIntegration = CrmIntegration::factory()->create(['is_active' => true]);
        CrmIntegration::factory()->create(['is_active' => false]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getActiveCrmIntegration');
        $method->setAccessible(true);

        $result = $method->invoke($this->service);

        $this->assertInstanceOf(CrmIntegration::class, $result);
        $this->assertEquals($activeIntegration->id, $result->id);
    }

    /**
     * Test CRM sync failure handling
     */
    public function test_crm_sync_failure_handling()
    {
        $integration = CrmIntegration::factory()->create(['is_active' => true]);
        $lead = Lead::factory()->create();

        $mockClient = Mockery::mock();
        $mockClient->shouldReceive('createLead')
            ->once()
            ->andThrow(new \Exception('CRM API Error'));

        $integrationMock = Mockery::mock($integration)->makePartial();
        $integrationMock->shouldReceive('getApiClient')
            ->once()
            ->andReturn($mockClient);

        $result = $this->service->syncLeadToCrm($lead, $integrationMock);

        $this->assertFalse($result['success']);
        $this->assertEquals('CRM API Error', $result['message']);

        // Check that sync log was created with failed status
        $this->assertDatabaseHas('crm_sync_logs', [
            'lead_id' => $lead->id,
            'status' => 'failed',
            'error_message' => 'CRM API Error',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}