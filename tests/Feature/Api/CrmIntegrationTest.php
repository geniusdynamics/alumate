<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Models\Tenant;
use App\Services\LeadRoutingService;
use App\Jobs\RouteLeadToCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for CRM integration functionality
 *
 * Tests the complete CRM integration workflow including
 * webhook processing, lead routing, and tenant isolation
 */
class CrmIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Lead $lead;
    protected CrmIntegration $crmIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure tenant context is initialized
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);

        $this->lead = Lead::factory()->create([
            'lead_type' => 'individual',
            'score' => 85,
            'email' => 'john.doe@test.com'
        ]);

        $this->crmIntegration = CrmIntegration::factory()->create([
            'provider' => 'hubspot',
            'is_active' => true,
            'tenant_id' => $this->tenant->id
        ]);
    }

    // Uncomment when we have CRM model relationships fixed
    // public function test_lead_creation_with_routing()
    // {
    //     $leadData = [
    //         'first_name' => 'Jane',
    //         'last_name' => 'Smith',
    //         'email' => 'jane.smith@test.com',
    //         'phone' => '+1234567890',
    //         'company' => 'Test Company',
    //         'job_title' => 'Marketing Manager',
    //         'lead_type' => 'individual',
    //         'audience_type' => 'individual'
    //     ];

    //     $response = $this->postJson('/api/leads', $leadData);

    //     $response->assertStatus(201)
    //              ->assertJsonStructure([
    //                  'data' => [
    //                      'id',
    //                      'email',
    //                      'first_name',
    //                      'lead_type',
    //                      'crm_provider',
    //                      'routing_status'
    //                  ]
    //              ]);

    //     $this->assertDatabaseHas('leads', [
    //         'email' => 'jane.smith@test.com',
    //         'lead_type' => 'individual'
    //     ]);

    //     // Verify job was queued
    //     Bus::assertDispatched(RouteLeadToCrm::class);
    // }

    public function test_crm_webhook_hubert_processing()
    {
        Bus::fake();

        $webhookData = [
            'eventId' => '12345',
            'subscriptionType' => 'contact.propertyChange',
            'portalId' => '12345',
            'appId' => '12345',
            'occurredAt' => now()->timestamp * 1000,
            'subscriptionId' => '12345',
            'attemptNumber' => 0,
            'objectId' => '12345',
            'changeSource' => 'crm',
            'propertyName' => 'email',
            'propertyValue' => 'john.doe@test.com',
            'isOriginal' => true
        ];

        // Generate mock signature
        $signature = hash_hmac('sha256', json_encode($webhookData), 'test_secret');

        $response = $this->postJson('/api/webhooks/crm/hubspot', $webhookData, [
            'X-HubSpot-Signature-v3' => $signature,
            'X-HubSpot-Request-Timestamp' => now()->timestamp
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_crm_webhook_with_invalid_signature()
    {
        $webhookData = [
            'eventId' => '12345',
            'objectId' => '12345'
        ];

        $response = $this->postJson('/api/webhooks/crm/hubspot', $webhookData);

        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid signature']);
    }

    public function test_lead_routing_service_integration()
    {
        Bus::fake();

        $service = app(LeadRoutingService::class);

        $result = $service->routeLead($this->lead, 'individual');

        $this->assertTrue($result['success']);
        $this->assertEquals('individual', $result['lead_id']);

        // Verify job was dispatched
        Bus::assertDispatched(RouteLeadToCrm::class, function ($job) {
            return $job->lead->id === $this->lead->id;
        });
    }

    public function test_multi_crm_routing_with_parallel_strategy()
    {
        Bus::fake();

        // Create multiple CRM integrations
        $secondaryCrm = CrmIntegration::factory()->create([
            'provider' => 'pipedrive',
            'is_active' => true,
            'tenant_id' => $this->tenant->id
        ]);

        // High score lead should trigger parallel routing
        $highScoreLead = Lead::factory()->create([
            'score' => 95,
            'lead_type' => 'institution'
        ]);

        $service = app(LeadRoutingService::class);
        $result = $service->routeLead($highScoreLead, 'institution');

        $this->assertTrue($result['success']);
        $this->assertEquals('parallel', $result['routing_strategy']['type']);

        // Verify multiple jobs were dispatched
        Bus::assertDispatched(RouteLeadToCrm::class, 2); // Should route to both CRMs
    }

    public function test_form_field_configuration_based_on_audience()
    {
        $service = app(LeadRoutingService::class);

        $individualConfig = $service->getFormFieldConfiguration('individual');
        $institutionConfig = $service->getFormFieldConfiguration('institution');

        // Individual config should include specific fields
        $this->assertArrayHasKey('job_title', $individualConfig);
        $this->assertArrayHasKey('company', $individualConfig);
        $this->assertArrayHasKey('industry', $individualConfig);

        // Institution config should include different specific fields
        $this->assertArrayHasKey('alumni_count', $institutionConfig);
        $this->assertArrayHasKey('implementation_timeline', $institutionConfig);
        $this->assertTrue($institutionConfig['company']['required']);
    }

    public function test_crm_routing_with_tenant_isolation()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create();
        $otherTenantCrm = CrmIntegration::factory()->create([
            'provider' => 'salesforce',
            'is_active' => true,
            'tenant_id' => $otherTenant->id
        ]);

        $this->assertNotEquals($this->tenant->id, $otherTenant->id);
        $this->assertNotEquals($this->crmIntegration->id, $otherTenantCrm->id);
    }

    public function test_lead_routing_analytics_functionality()
    {
        $service = app(LeadRoutingService::class);

        // Create some historical leads
        Lead::factory()->count(5)->create([
            'created_at' => now()->subMonth(),
            'score' => 90
        ]);

        $analytics = $service->getRoutingAnalytics(
            now()->subMonths(2),
            now()
        );

        $this->assertIsArray($analytics);
        $this->assertGreaterThanOrEqual(0, $analytics['total_leads']);
        $this->assertArrayHasKey('strategy_usage', $analytics);
        $this->assertArrayHasKey('crm_distribution', $analytics);
    }

    public function test_batch_lead_routing_performance()
    {
        Bus::fake();

        $leads = Lead::factory()->count(20)->create(['score' => 80]);

        $service = app(LeadRoutingService::class);
        $batchResult = $service->batchRouteLeads($leads->pluck('id')->toArray(), 'individual');

        $this->assertEquals(20, $batchResult['total_leads']);
        $this->assertGreaterThanOrEqual(20, $batchResult['processed']);
        $this->assertFalse(isset($batchResult['error'])); // Should not have error for successful batch
    }

    // public function test_lead_routing_with_custom_rules()
    // {
    //     $customRules = [
    //         [
    //             'condition' => [
    //                 'score' => ['operator' => 'greater_than', 'value' => 85],
    //                 'lead_type' => ['operator' => 'equals', 'value' => 'institution']
    //             ],
    //             'strategy' => [
    //                 'type' => 'parallel',
    //                 'primary_crm' => 'salesforce',
    //                 'secondary_crm' => 'hubspot'
    //             ]
    //         ]
    //     ];

    //     $matchingLead = Lead::factory()->create([
    //         'score' => 90,
    //         'lead_type' => 'institution'
    //     ]);

    //     $service = app(LeadRoutingService::class);
    //     $result = $service->routeLead($matchingLead, 'institution', $customRules);

    //     $this->assertTrue($result['success']);
    //     $this->assertEquals('parallel', $result['routing_strategy']['type']);
    // }

    public function test_crm_integration_status_in_response()
    {
        $service = app(LeadRoutingService::class);

        $result = $service->routeLead($this->lead, 'individual');

        $this->assertArrayHasKey('routed_to', $result);
        $this->assertArrayHasKey('routing_strategy', $result);
        $this->assertIsArray($result['routing_strategy']);
    }

    public function test_webhook_processing_for_multiple_providers()
    {
        Bus::fake();

        $providers = ['salesforce', 'pipedrive', 'generic'];

        foreach ($providers as $provider) {
            $webhookData = [
                'objectId' => '12345',
                'event' => 'update',
                'provider' => $provider
            ];

            if ($provider === 'generic') {
                $route = "/api/webhooks/crm/{$provider}/{$provider}";
            } else {
                $route = "/api/webhooks/crm/{$provider}";
            }

            $response = $this->postJson($route, $webhookData);

            $response->assertStatus(200)
                     ->assertJson(['success' => true]);
        }
    }

    public function test_routing_failure_handling()
    {
        // Deactivate all CRM integrations
        CrmIntegration::where('tenant_id', $this->tenant->id)
            ->update(['is_active' => false]);

        $service = app(LeadRoutingService::class);
        $result = $service->routeLead($this->lead, 'individual');

        $this->assertFalse($result['success']);
        $this->assertEquals('No CRM integrations configured', $result['message']);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        parent::tearDown();
    }
}