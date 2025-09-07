<?php

namespace Tests\Unit\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * Unit tests for LeadScoringService
 */
class LeadScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeadScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeadScoringService();
    }

    /**
     * Test service instantiation
     */
    public function test_service_can_be_instantiated()
    {
        $this->assertInstanceOf(LeadScoringService::class, $this->service);
    }

    /**
     * Test calculating lead score with basic lead data
     */
    public function test_calculate_lead_score_basic()
    {
        $lead = Lead::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'source' => 'referral',
        ]);

        $score = $this->service->calculateLeadScore($lead);

        $this->assertIsInt($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /**
     * Test updating lead score with email open event
     */
    public function test_update_lead_score_email_open()
    {
        $lead = Lead::factory()->create(['score' => 10]);

        $result = $this->service->updateLeadScore($lead, 'email_opens');

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(10, $result['new_score']);
        $this->assertEquals(15, $result['change']); // 10 + 5 points for email open
        $this->assertEquals('email_opens', $result['reason']);

        // Check that lead score was updated
        $lead->refresh();
        $this->assertEquals($result['new_score'], $lead->score);

        // Check that activity was logged
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'score_change',
            'subject' => 'Lead score updated',
        ]);
    }

    /**
     * Test updating lead score with form submission
     */
    public function test_update_lead_score_form_submission()
    {
        $lead = Lead::factory()->create(['score' => 20]);

        $result = $this->service->updateLeadScore($lead, 'form_submissions');

        $this->assertTrue($result['success']);
        $this->assertEquals(45, $result['change']); // 20 + 25 points for form submission
        $this->assertEquals('form_submissions', $result['reason']);
    }

    /**
     * Test daily scoring limits
     */
    public function test_daily_scoring_limits()
    {
        $lead = Lead::factory()->create(['score' => 10]);

        // First email open should work
        $result1 = $this->service->updateLeadScore($lead, 'email_opens');
        $this->assertTrue($result1['success']);

        // Second email open should be limited (max 10 per day)
        $result2 = $this->service->updateLeadScore($lead, 'email_opens');
        $this->assertFalse($result2['success']);
        $this->assertStringContains('Daily scoring limit reached', $result2['message']);
    }

    /**
     * Test score decay for inactive leads
     */
    public function test_score_decay_inactive_leads()
    {
        $lead = Lead::factory()->create(['score' => 80]);

        // Create an old activity
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'email_opens',
            'created_at' => now()->subDays(100), // Very old activity
        ]);

        $decayAmount = $this->service->applyScoreDecay($lead);

        $this->assertGreaterThan(0, $decayAmount);
        $this->assertEquals(40, $decayAmount); // 50% decay for 90+ days
    }

    /**
     * Test score decay for recently active leads
     */
    public function test_score_decay_recent_activity()
    {
        $lead = Lead::factory()->create(['score' => 80]);

        // Create a recent activity
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'email_opens',
            'created_at' => now()->subDays(10), // Recent activity
        ]);

        $decayAmount = $this->service->applyScoreDecay($lead);

        $this->assertEquals(0, $decayAmount); // No decay for recent activity
    }

    /**
     * Test getting scoring analytics
     */
    public function test_get_scoring_analytics()
    {
        // Create test leads with different scores
        Lead::factory()->count(5)->create(['score' => 10]); // Cold
        Lead::factory()->count(3)->create(['score' => 30]); // Warm
        Lead::factory()->count(2)->create(['score' => 60]); // Hot
        Lead::factory()->count(1)->create(['score' => 80]); // Qualified

        $analytics = $this->service->getScoringAnalytics();

        $this->assertIsArray($analytics);
        $this->assertEquals(11, $analytics['total_leads']);
        $this->assertArrayHasKey('average_score', $analytics);
        $this->assertArrayHasKey('score_distribution', $analytics);
        $this->assertArrayHasKey('qualification_rates', $analytics);
        $this->assertArrayHasKey('top_scoring_leads', $analytics);
        $this->assertArrayHasKey('recent_score_changes', $analytics);

        // Check score distribution
        $this->assertEquals(5, $analytics['score_distribution']['cold']);
        $this->assertEquals(3, $analytics['score_distribution']['warm']);
        $this->assertEquals(2, $analytics['score_distribution']['hot']);
        $this->assertEquals(1, $analytics['score_distribution']['qualified']);
    }

    /**
     * Test batch updating scores
     */
    public function test_batch_update_scores()
    {
        $leads = Lead::factory()->count(3)->create(['score' => 10]);
        $leadIds = $leads->pluck('id')->toArray();

        $result = $this->service->batchUpdateScores($leadIds);

        $this->assertEquals(3, $result['total_processed']);
        $this->assertGreaterThanOrEqual(0, $result['updated']);
        $this->assertEquals(0, $result['errors']);
        $this->assertCount(3, $result['results']);
    }

    /**
     * Test getting scoring configuration
     */
    public function test_get_scoring_configuration()
    {
        $config = $this->service->getScoringConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('rules', $config);
        $this->assertArrayHasKey('thresholds', $config);
        $this->assertArrayHasKey('max_score', $config);
        $this->assertArrayHasKey('decay_schedule', $config);

        $this->assertEquals(100, $config['max_score']);
        $this->assertArrayHasKey('email_opens', $config['rules']);
        $this->assertArrayHasKey('qualified', $config['thresholds']);
    }

    /**
     * Test lead priority update based on score
     */
    public function test_lead_priority_update()
    {
        $lead = Lead::factory()->create(['score' => 10, 'priority' => 'low']);

        // Update score to hot level
        $this->service->updateLeadScore($lead, 'job_applications');

        $lead->refresh();
        $this->assertEquals('urgent', $lead->priority); // Job application gives 50 points, making it urgent
    }

    /**
     * Test calculating base score
     */
    public function test_calculate_base_score()
    {
        $lead = Lead::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'Acme Corp',
            'source' => 'referral',
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getBaseScore');
        $method->setAccessible(true);

        $baseScore = $method->invoke($this->service, $lead);

        $this->assertGreaterThan(0, $baseScore);
        // Should include: 20 (referral) + 5 (name) + 5 (email) + 5 (company) = 35
        $this->assertEquals(35, $baseScore);
    }

    /**
     * Test calculating engagement score
     */
    public function test_calculate_engagement_score()
    {
        $lead = Lead::factory()->create();

        // Create some engagement activities
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'email_opens',
            'created_at' => now()->subDays(5),
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'page_views',
            'created_at' => now()->subDays(3),
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateEngagementScore');
        $method->setAccessible(true);

        $engagementScore = $method->invoke($this->service, $lead);

        $this->assertEquals(7, $engagementScore); // 5 (email open) + 2 (page view)
    }

    /**
     * Test calculating event score
     */
    public function test_calculate_event_score()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateEventScore');
        $method->setAccessible(true);

        // Test email open
        $score1 = $method->invoke($this->service, 'email_opens', []);
        $this->assertEquals(5, $score1);

        // Test form submission
        $score2 = $method->invoke($this->service, 'form_submissions', []);
        $this->assertEquals(25, $score2);

        // Test unknown event
        $score3 = $method->invoke($this->service, 'unknown_event', []);
        $this->assertEquals(0, $score3);
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
     * Test score change logging
     */
    public function test_score_change_logging()
    {
        $lead = Lead::factory()->create(['score' => 10]);

        $this->service->updateLeadScore($lead, 'email_opens');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'score_change',
            'subject' => 'Lead score updated',
        ]);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('type', 'score_change')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('old_score', $activity->metadata);
        $this->assertArrayHasKey('new_score', $activity->metadata);
        $this->assertArrayHasKey('change', $activity->metadata);
        $this->assertArrayHasKey('event_type', $activity->metadata);
    }

    /**
     * Test qualification rate calculation
     */
    public function test_qualification_rate_calculation()
    {
        Lead::factory()->count(10)->create(['score' => 20]); // Below qualified threshold
        Lead::factory()->count(5)->create(['score' => 80]);  // Qualified

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getQualificationRates');
        $method->setAccessible(true);

        $leads = Lead::all();
        $rates = $method->invoke($this->service, $leads);

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('qualified', $rates);
        $this->assertArrayHasKey('hot', $rates);
        $this->assertArrayHasKey('conversion_rate', $rates);

        $this->assertEquals(33.33, round($rates['qualified'], 2)); // 5/15 = 33.33%
        $this->assertEquals(33.33, round($rates['hot'], 2)); // 5/15 = 33.33%
    }

    /**
     * Test score distribution calculation
     */
    public function test_score_distribution_calculation()
    {
        Lead::factory()->count(3)->create(['score' => 10]); // Cold
        Lead::factory()->count(2)->create(['score' => 30]); // Warm
        Lead::factory()->count(1)->create(['score' => 60]); // Hot
        Lead::factory()->count(1)->create(['score' => 80]); // Qualified

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getScoreDistribution');
        $method->setAccessible(true);

        $leads = Lead::all();
        $distribution = $method->invoke($this->service, $leads);

        $this->assertIsArray($distribution);
        $this->assertEquals(3, $distribution['cold']);
        $this->assertEquals(2, $distribution['warm']);
        $this->assertEquals(1, $distribution['hot']);
        $this->assertEquals(1, $distribution['qualified']);
    }

    /**
     * Test getting last activity date
     */
    public function test_get_last_activity_date()
    {
        $lead = Lead::factory()->create();

        // Create activities with different dates
        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'email_opens',
            'created_at' => now()->subDays(10),
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'page_views',
            'created_at' => now()->subDays(5), // More recent
        ]);

        LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'form_submissions',
            'created_at' => now()->subDays(15), // Older
        ]);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getLastActivityDate');
        $method->setAccessible(true);

        $lastActivityDate = $method->invoke($this->service, $lead);

        $this->assertInstanceOf(Carbon::class, $lastActivityDate);
        $this->assertEquals(now()->subDays(5)->toDateString(), $lastActivityDate->toDateString());
    }

    /**
     * Test getting last activity date with no activities
     */
    public function test_get_last_activity_date_no_activities()
    {
        $lead = Lead::factory()->create();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getLastActivityDate');
        $method->setAccessible(true);

        $lastActivityDate = $method->invoke($this->service, $lead);

        $this->assertNull($lastActivityDate);
    }
}