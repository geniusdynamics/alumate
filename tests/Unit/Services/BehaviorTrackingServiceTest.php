<?php

namespace Tests\Unit\Services;

use App\Models\Lead;
use App\Models\EmailSequence;
use App\Models\SequenceEnrollment;
use App\Models\BehaviorEvent;
use App\Services\BehaviorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

/**
 * Unit tests for BehaviorTrackingService
 */
class BehaviorTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BehaviorTrackingService $service;
    private Lead $lead;
    private EmailSequence $sequence;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock tenant
        $tenant = Mockery::mock('alias:App\Models\Tenant');
        $tenant->id = 1;
        $this->app->instance('tenant', $tenant);

        // Create service instance
        $this->service = new BehaviorTrackingService();

        // Create test data
        $this->lead = Lead::factory()->create(['tenant_id' => 1]);
        $this->sequence = EmailSequence::factory()->create([
            'tenant_id' => 1,
            'trigger_type' => 'behavior',
            'is_active' => true,
        ]);

        // Mock Cache facade
        Cache::shouldReceive('remember')->andReturnUsing(function ($key, $ttl, $callback) {
            return $callback();
        });
        Cache::shouldReceive('forget')->andReturn(true);
    }

    /**
     * Test service instantiation
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(BehaviorTrackingService::class, $this->service);
    }

    /**
     * Test tracking behavior event
     */
    public function test_track_behavior()
    {
        $eventData = [
            'lead_id' => $this->lead->id,
            'event_type' => 'page_visit',
            'event_data' => [
                'page_url' => '/test-page',
                'visit_duration' => 120,
            ],
            'metadata' => [
                'page_title' => 'Test Page',
                'referrer' => 'https://example.com',
            ],
        ];

        $event = $this->service->trackBehavior($eventData);

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals($this->lead->id, $event->lead_id);
        $this->assertEquals('page_visit', $event->event_type);
        $this->assertEquals('/test-page', $event->event_data['page_url']);
        $this->assertEquals(1, $event->tenant_id);
    }

    /**
     * Test tracking page visit
     */
    public function test_track_page_visit()
    {
        $event = $this->service->trackPageVisit(
            $this->lead->id,
            '/about-us',
            ['visit_duration' => 300, 'page_title' => 'About Us']
        );

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals('page_visit', $event->event_type);
        $this->assertEquals('/about-us', $event->event_data['page_url']);
        $this->assertEquals(300, $event->event_data['visit_duration']);
        $this->assertEquals('About Us', $event->metadata['page_title']);
    }

    /**
     * Test tracking form interaction
     */
    public function test_track_form_interaction()
    {
        $event = $this->service->trackFormInteraction(
            $this->lead->id,
            'contact_form',
            'field_focus',
            ['field_name' => 'email', 'field_value' => 'test@example.com']
        );

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals('form_interaction', $event->event_type);
        $this->assertEquals('contact_form', $event->event_data['form_type']);
        $this->assertEquals('field_focus', $event->event_data['action']);
        $this->assertEquals('email', $event->event_data['field_name']);
    }

    /**
     * Test tracking content engagement
     */
    public function test_track_content_engagement()
    {
        $event = $this->service->trackContentEngagement(
            $this->lead->id,
            'blog_post',
            123,
            'read',
            ['engagement_duration' => 180, 'scroll_depth' => 75]
        );

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals('content_engagement', $event->event_type);
        $this->assertEquals('blog_post', $event->event_data['content_type']);
        $this->assertEquals(123, $event->event_data['content_id']);
        $this->assertEquals('read', $event->event_data['action']);
        $this->assertEquals(180, $event->event_data['engagement_duration']);
    }

    /**
     * Test tracking email engagement
     */
    public function test_track_email_engagement()
    {
        $event = $this->service->trackEmailEngagement(
            $this->lead->id,
            456,
            'clicked',
            ['link_url' => 'https://example.com', 'link_text' => 'Learn More']
        );

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals('email_engagement', $event->event_type);
        $this->assertEquals(456, $event->event_data['email_send_id']);
        $this->assertEquals('clicked', $event->event_data['action']);
        $this->assertEquals('https://example.com', $event->event_data['link_url']);
    }

    /**
     * Test tracking custom event
     */
    public function test_track_custom_event()
    {
        $event = $this->service->trackCustomEvent(
            $this->lead->id,
            'custom_interaction',
            ['custom_data' => 'value'],
            ['source' => 'mobile_app']
        );

        $this->assertInstanceOf(BehaviorEvent::class, $event);
        $this->assertEquals('custom_interaction', $event->event_type);
        $this->assertEquals(['custom_data' => 'value'], $event->event_data);
        $this->assertEquals(['source' => 'mobile_app'], $event->metadata);
    }

    /**
     * Test lead score update
     */
    public function test_update_lead_score()
    {
        $initialScore = $this->lead->score ?? 0;

        $result = $this->service->updateLeadScore($this->lead->id, 'page_visit', ['visit_duration' => 300]);

        $this->assertTrue($result);
        $this->lead->refresh();

        // Page visit should increase score
        $this->assertGreaterThan($initialScore, $this->lead->score);
    }

    /**
     * Test lead score update with long visit duration bonus
     */
    public function test_update_lead_score_with_duration_bonus()
    {
        $initialScore = $this->lead->score ?? 0;

        $result = $this->service->updateLeadScore($this->lead->id, 'page_visit', ['visit_duration' => 400]);

        $this->assertTrue($result);
        $this->lead->refresh();

        // Long visit should get bonus (score should be doubled)
        $expectedScore = min(100, ($initialScore + 5) * 2); // 5 is base page_visit score
        $this->assertEquals($expectedScore, $this->lead->score);
    }

    /**
     * Test getting lead behavior history
     */
    public function test_get_lead_behavior_history()
    {
        BehaviorEvent::factory()->count(3)->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
        ]);

        $history = $this->service->getLeadBehaviorHistory($this->lead->id);

        $this->assertCount(3, $history);
        $this->assertEquals($this->lead->id, $history->first()->lead_id);
    }

    /**
     * Test getting lead engagement metrics
     */
    public function test_get_lead_engagement_metrics()
    {
        // Create various behavior events
        BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
            'event_type' => 'page_visit',
            'occurred_at' => now(),
        ]);

        BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
            'event_type' => 'form_interaction',
            'occurred_at' => now(),
        ]);

        BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
            'event_type' => 'email_engagement',
            'occurred_at' => now(),
        ]);

        $metrics = $this->service->getLeadEngagementMetrics($this->lead->id);

        $this->assertIsArray($metrics);
        $this->assertEquals(3, $metrics['total_events']);
        $this->assertEquals(1, $metrics['page_visits']);
        $this->assertEquals(1, $metrics['form_interactions']);
        $this->assertEquals(1, $metrics['email_engagements']);
        $this->assertArrayHasKey('engagement_score', $metrics);
    }

    /**
     * Test evaluating sequence triggers
     */
    public function test_evaluate_sequence_triggers()
    {
        $event = BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
            'event_type' => 'page_visit',
            'event_data' => ['page_url' => '/welcome'],
        ]);

        $triggeredSequences = $this->service->evaluateSequenceTriggers($event);

        // Should return empty array since no sequences match the trigger
        $this->assertIsArray($triggeredSequences);
    }

    /**
     * Test evaluating sequence triggers with matching conditions
     */
    public function test_evaluate_sequence_triggers_with_matching_conditions()
    {
        // Update sequence with matching trigger conditions
        $this->sequence->update([
            'trigger_conditions' => [
                'event_type' => 'page_visit',
                'event_data' => [
                    'page_url' => '/welcome'
                ]
            ]
        ]);

        $event = BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 1,
            'event_type' => 'page_visit',
            'event_data' => ['page_url' => '/welcome'],
        ]);

        $triggeredSequences = $this->service->evaluateSequenceTriggers($event);

        $this->assertCount(1, $triggeredSequences);
        $this->assertEquals($this->sequence->id, $triggeredSequences[0]->id);
    }

    /**
     * Test processing bulk events
     */
    public function test_process_bulk_events()
    {
        $events = [
            [
                'lead_id' => $this->lead->id,
                'event_type' => 'page_visit',
                'event_data' => ['page_url' => '/page1'],
            ],
            [
                'lead_id' => $this->lead->id,
                'event_type' => 'form_interaction',
                'event_data' => ['form_type' => 'contact'],
            ],
        ];

        $results = $this->service->processBulkEvents($events);

        $this->assertEquals(2, $results['processed']);
        $this->assertEquals(0, $results['failed']);
        $this->assertIsArray($results['errors']);
    }

    /**
     * Test processing bulk events with failures
     */
    public function test_process_bulk_events_with_failures()
    {
        $events = [
            [
                'lead_id' => $this->lead->id,
                'event_type' => 'page_visit',
                'event_data' => ['page_url' => '/page1'],
            ],
            [
                'lead_id' => 999999, // Invalid lead ID
                'event_type' => 'form_interaction',
                'event_data' => ['form_type' => 'contact'],
            ],
        ];

        $results = $this->service->processBulkEvents($events);

        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(1, $results['failed']);
        $this->assertCount(1, $results['errors']);
    }

    /**
     * Test getting behavior analytics
     */
    public function test_get_behavior_analytics()
    {
        BehaviorEvent::factory()->count(5)->create(['tenant_id' => 1]);

        $analytics = $this->service->getBehaviorAnalytics();

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('total_events', $analytics);
        $this->assertArrayHasKey('unique_leads', $analytics);
        $this->assertArrayHasKey('events_by_type', $analytics);
        $this->assertArrayHasKey('conversion_funnel', $analytics);
    }

    /**
     * Test cleanup old events
     */
    public function test_cleanup_old_events()
    {
        // Create old events
        BehaviorEvent::factory()->count(3)->create([
            'tenant_id' => 1,
            'occurred_at' => Carbon::now()->subDays(100),
        ]);

        // Create recent events
        BehaviorEvent::factory()->count(2)->create([
            'tenant_id' => 1,
            'occurred_at' => Carbon::now()->subDays(10),
        ]);

        $deletedCount = $this->service->cleanupOldEvents(90);

        $this->assertEquals(3, $deletedCount);
        $this->assertEquals(2, BehaviorEvent::where('tenant_id', 1)->count());
    }

    /**
     * Test event data validation
     */
    public function test_validate_event_data()
    {
        $validData = [
            'lead_id' => $this->lead->id,
            'event_type' => 'page_visit',
            'event_data' => ['page_url' => '/test'],
            'metadata' => ['source' => 'website'],
        ];

        // This should not throw an exception
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateEventData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $validData);
        $this->assertNull($result);
    }

    /**
     * Test event data validation with invalid data
     */
    public function test_validate_event_data_with_invalid_data()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateEventData');
        $method->setAccessible(true);

        $invalidData = [
            'lead_id' => 'invalid', // Should be integer
            'event_type' => '', // Cannot be empty
        ];

        $method->invoke($this->service, $invalidData);
    }

    /**
     * Test database transaction rollback on error
     */
    public function test_database_transaction_rollback_on_error()
    {
        // Mock DB to throw exception
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        Log::shouldReceive('error')->once();

        $this->expectException(\Exception::class);

        // This should trigger a rollback
        $this->service->trackBehavior([
            'lead_id' => $this->lead->id,
            'event_type' => 'page_visit',
            'event_data' => ['page_url' => '/test'],
        ]);
    }

    /**
     * Test tenant isolation
     */
    public function test_tenant_isolation()
    {
        // Create event for different tenant
        $otherTenantEvent = BehaviorEvent::factory()->create([
            'lead_id' => $this->lead->id,
            'tenant_id' => 2,
        ]);

        // Should not be able to access events from different tenant
        $history = $this->service->getLeadBehaviorHistory($this->lead->id);
        $this->assertCount(0, $history); // Should be empty due to tenant isolation
    }

    /**
     * Test score calculation for different event types
     */
    public function test_score_calculation_for_different_event_types()
    {
        $testCases = [
            ['event_type' => 'page_visit', 'expected_base' => 5],
            ['event_type' => 'form_interaction', 'expected_base' => 10],
            ['event_type' => 'content_engagement', 'expected_base' => 15],
            ['event_type' => 'email_engagement', 'expected_base' => 20],
            ['event_type' => 'unknown_event', 'expected_base' => 0],
        ];

        foreach ($testCases as $testCase) {
            $initialScore = $this->lead->score ?? 0;

            $this->service->updateLeadScore($this->lead->id, $testCase['event_type']);
            $this->lead->refresh();

            $expectedScore = min(100, $initialScore + $testCase['expected_base']);
            $this->assertEquals($expectedScore, $this->lead->score);

            // Reset lead score for next test
            $this->lead->update(['score' => $initialScore]);
        }
    }

    /**
     * Test engagement score calculation
     */
    public function test_engagement_score_calculation()
    {
        $events = collect([
            (object)['event_type' => 'page_visit'],
            (object)['event_type' => 'form_interaction'],
            (object)['event_type' => 'email_engagement'],
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateEngagementScore');
        $method->setAccessible(true);

        $score = $method->invoke($this->service, $events);

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}