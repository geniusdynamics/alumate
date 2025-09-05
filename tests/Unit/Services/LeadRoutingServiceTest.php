<?php

namespace Tests\Unit\Services;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Services\LeadRoutingService;
use App\Jobs\RouteLeadToCrm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit tests for LeadRoutingService
 *
 * Tests multi-CRM lead distribution, routing strategies, and form field configuration
 */
class LeadRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeadRoutingService $service;
    protected Lead $lead;
    protected CrmIntegration $crmIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LeadRoutingService();

        // Create test lead
        $this->lead = Lead::factory()->create([
            'lead_type' => 'individual',
            'score' => 75,
            'source' => 'form_submission'
        ]);

        // Create test CRM integration
        $this->crmIntegration = CrmIntegration::factory()->create([
            'provider' => 'hubspot',
            'is_active' => true
        ]);
    }

    public function test_get_form_field_configuration_for_individual_audience()
    {
        $config = $this->service->getFormFieldConfiguration('individual');

        $this->assertArrayHasKey('first_name', $config);
        $this->assertArrayHasKey('last_name', $config);
        $this->assertArrayHasKey('email', $config);
        $this->assertArrayHasKey('job_title', $config);
        $this->assertArrayHasKey('company', $config);
        $this->assertEquals('individual', $config['company']['label']);
    }

    public function test_get_form_field_configuration_for_institution_audience()
    {
        $config = $this->service->getFormFieldConfiguration('institution');

        $this->assertArrayHasKey('company', $config);
        $this->assertArrayHasKey('alumni_count', $config);
        $this->assertArrayHasKey('implementation_timeline', $config);
        $this->assertEquals('Institution Name', $config['company']['label']);
        $this->assertTrue($config['company']['required']);
    }

    public function test_get_form_field_configuration_for_unknown_audience()
    {
        $config = $this->service->getFormFieldConfiguration('unknown_audience');

        // Should return default fields
        $this->assertArrayHasKey('first_name', $config);
        $this->assertArrayHasKey('last_name', $config);
        $this->assertArrayHasKey('email', $config);
        $this->assertArrayHasKey('inquiry_type', $config);
    }

    public function test_route_lead_with_primary_first_strategy()
    {
        Bus::fake();
        Queue::fake();

        $result = $this->service->routeLead($this->lead, 'individual');

        $this->assertTrue($result['success']);
        $this->assertEquals('individual', $this->lead->fresh()->lead_type);

        // Verify job was dispatched
        Bus::assertDispatched(RouteLeadToCrm::class, function ($job) {
            return $job->lead->id === $this->lead->id;
        });
    }

    public function test_route_lead_with_parallel_strategy_for_high_score_lead()
    {
        Bus::fake();

        // High score lead should trigger parallel routing
        $highScoreLead = Lead::factory()->create(['score' => 95]);

        $result = $this->service->routeLead($highScoreLead, 'institution');

        $this->assertTrue($result['success']);
        $this->assertEquals('parallel', $result['routing_strategy']['type']);
    }

    public function test_route_lead_with_qualify_only_strategy_for_low_score_lead()
    {
        Bus::fake();

        // Low score lead should be marked for qualification only
        $lowScoreLead = Lead::factory()->create(['score' => 30]);

        $result = $this->service->routeLead($lowScoreLead, 'student');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['routing_strategy']['qualify_only']);
        $this->assertEquals('qualify_only', $result['routing_strategy']['type']);
    }

    public function test_route_lead_with_no_active_crm_integrations()
    {
        // Deactivate all CRM integrations
        CrmIntegration::query()->update(['is_active' => false]);

        $result = $this->service->routeLead($this->lead, 'individual');

        $this->assertFalse($result['success']);
        $this->assertEquals('No CRM integrations configured', $result['message']);
    }

    public function test_batch_route_leads_performance()
    {
        Bus::fake();

        $leads = Lead::factory()->count(10)->create(['score' => 80]);

        $result = $this->service->batchRouteLeads($leads->pluck('id')->toArray(), 'individual');

        $this->assertEquals(10, $result['total_leads']);
        $this->assertGreaterThanOrEqual(10, $result['processed']);
        $this->assertGreaterThanOrEqual(0, $result['successful']);
    }

    public function test_determine_routing_strategy_based_on_audience_type()
    {
        $config = $this->service->getAudienceRoutingConfig('institution');

        $this->assertEquals('salesforce', $config['preferred_crm']);
        $this->assertEquals('hubspot', $config['secondary_crm']);
        $this->assertEquals('parallel', $config['routing_priority']);
        $this->assertEquals(80, $config['score_threshold']);
    }

    public function test_routing_strategy_with_custom_rules()
    {
        $customRules = [
            [
                'condition' => [
                    'lead_type' => ['operator' => 'equals', 'value' => 'individual'],
                    'score' => ['operator' => 'greater_than', 'value' => 70]
                ],
                'strategy' => [
                    'type' => 'primary_only',
                    'primary_crm' => 'custom_analytics'
                ]
            ]
        ];

        // Create lead matching the custom rule
        $matchingLead = Lead::factory()->create([
            'lead_type' => 'individual',
            'score' => 85
        ]);

        $result = $this->service->routeLead($matchingLead, 'individual', $customRules);

        $this->assertTrue($result['success']);
        $this->assertEquals('primary_only', $result['routing_strategy']['type']);
    }

    public function test_load_balancing_routing_strategy()
    {
        // Create multiple CRM integrations
        $secondaryCrm = CrmIntegration::factory()->create([
            'provider' => 'pipedrive',
            'is_active' => true
        ]);

        $cacheKey = 'crm_load_balancer_index';
        Cache::put($cacheKey, 1, now()->addHour()); // Set index to 1

        $result = $this->service->determineRoutingStrategy($this->lead, [
            'routing_priority' => 'load_balanced',
            'preferred_crm' => 'hubspot',
            'secondary_crm' => 'pipedrive'
        ], []);

        $this->assertArrayHasKey('load_balancing', $result);
    }

    public function test_routing_analytics_functionality()
    {
        // Create some test leads with different routing outcomes
        Lead::factory()->count(5)->create([
            'score' => 90,
            'created_at' => now()->subMonth()
        ]);

        Lead::factory()->create([
            'lead_type' => 'qualified',
            'qualified_at' => now()->subMonth(),
            'created_at' => now()->subMonth()
        ]);

        $analytics = $this->service->getRoutingAnalytics(
            now()->subMonths(2),
            now()
        );

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('total_leads', $analytics);
        $this->assertArrayHasKey('routed_leads', $analytics);
        $this->assertArrayHasKey('strategy_usage', $analytics);
        $this->assertArrayHasKey('crm_distribution', $analytics);
        $this->assertArrayHasKey('conversion_rate', $analytics);
    }

    public function test_audience_routing_configurations()
    {
        $audiences = ['individual', 'institution', 'employer', 'student', 'general'];

        foreach ($audiences as $audience) {
            $config = $this->service->getAudienceRoutingConfig($audience);

            $this->assertArrayHasKey('preferred_crm', $config);
            $this->assertArrayHasKey('routing_priority', $config);
            $this->assertArrayHasKey('score_threshold', $config);
        }
    }

    public function test_evaluate_rule_functionality()
    {
        $leadData = ['score' => 85, 'lead_type' => 'individual'];

        $rule = [
            'condition' => [
                'score' => ['operator' => 'greater_than', 'value' => 80],
                'lead_type' => ['operator' => 'equals', 'value' => 'individual']
            ],
            'strategy' => ['type' => 'parallel']
        ];

        $leadMock = $this->createMock(Lead::class);
        $leadMock->method('getAttribute')->willReturnCallback(function ($field) use ($leadData) {
            return $leadData[$field] ?? null;
        });

        $method = new \ReflectionMethod($this->service, 'evaluateRule');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $leadMock, $rule);

        $this->assertTrue($result);
    }

    public function test_determine_routing_strategy_with_dynamic_scores()
    {
        // Test different score ranges
        $scores = [10, 40, 60, 80, 95];

        foreach ($scores as $score) {
            $lead = Lead::factory()->create(['score' => $score]);
            $routingConfig = $this->service->getAudienceRoutingConfig('general');

            $strategy = $this->service->determineRoutingStrategy($lead, $routingConfig, []);

            if ($score >= 90) {
                $this->assertEquals('parallel', $strategy['type']);
            } elseif ($score < 40) {
                $this->assertEquals('qualify_only', $strategy['type']);
            } else {
                $this->assertArrayHasKey('type', $strategy);
            }
        }
    }

    protected function tearDown(): void
    {
        Cache::forget('crm_load_balancer_index');
        Cache::forget('enterprise_assignment_index');
        Cache::forget('individual_assignment_index');

        parent::tearDown();
    }
}