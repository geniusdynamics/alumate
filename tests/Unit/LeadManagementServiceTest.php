<?php

use App\Models\Lead;
use App\Models\LeadScoringRule;
use App\Models\User;
use App\Services\LeadManagementService;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->service = new LeadManagementService;
});

test('can create lead from form submission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'phone' => '+1234567890',
        'company' => 'Test Company',
        'job_title' => 'CEO',
        'lead_type' => 'enterprise',
        'source' => 'homepage',
        'utm_data' => ['utm_source' => 'google'],
        'form_data' => ['interest' => 'demo'],
    ];

    $lead = $this->service->createLead($data);

    expect($lead)->toBeInstanceOf(Lead::class);
    expect($lead->first_name)->toBe('John');
    expect($lead->last_name)->toBe('Doe');
    expect($lead->email)->toBe('john@example.com');
    expect($lead->company)->toBe('Test Company');
    expect($lead->lead_type)->toBe('enterprise');
    expect($lead->source)->toBe('homepage');
    expect($lead->activities)->toHaveCount(1);
    expect($lead->activities->first()->subject)->toBe('Lead created');
});

test('applies lead scoring rules on creation', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a scoring rule
    LeadScoringRule::factory()->create([
        'name' => 'Form Submission',
        'trigger_type' => 'form_submission',
        'conditions' => ['lead_type' => 'enterprise'],
        'points' => 20,
        'is_active' => true,
    ]);

    $data = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane@example.com',
        'lead_type' => 'enterprise',
        'source' => 'homepage',
    ];

    $lead = $this->service->createLead($data);

    expect($lead->score)->toBeGreaterThan(0);
    expect($lead->activities)->toHaveCount(2); // Lead created + score update
});

test('can qualify a lead', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create([
        'status' => 'contacted',
        'created_by' => $user->id,
    ]);

    $qualificationData = [
        'budget' => '10000',
        'timeline' => '3_months',
        'decision_maker' => true,
    ];

    $qualifiedLead = $this->service->qualifyLead($lead->id, $qualificationData);

    expect($qualifiedLead->status)->toBe('qualified');
    expect($qualifiedLead->qualified_at)->not->toBeNull();
    expect($qualifiedLead->form_data)->toHaveKey('budget');
});

test('can create follow-up sequence', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create([
        'assigned_to' => $user->id,
    ]);

    $activities = $this->service->createFollowUpSequence($lead, 'standard');

    expect($activities)->toHaveCount(5);
    expect($activities->first()->type)->toBe('email');
    expect($activities->first()->subject)->toBe('Welcome and next steps');
    expect($activities->last()->type)->toBe('email');
});

test('can create enterprise follow-up sequence', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create([
        'assigned_to' => $user->id,
        'lead_type' => 'enterprise',
    ]);

    $activities = $this->service->createFollowUpSequence($lead, 'enterprise');

    expect($activities)->toHaveCount(5);
    expect($activities->first()->subject)->toBe('Enterprise solution overview');
    expect($activities->where('type', 'proposal'))->toHaveCount(1);
});

test('can get lead analytics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create test leads
    Lead::factory()->count(5)->create(['status' => 'new']);
    Lead::factory()->count(3)->create(['status' => 'qualified', 'qualified_at' => now()]);
    Lead::factory()->count(2)->create(['status' => 'closed_won']);

    $analytics = $this->service->getLeadAnalytics();

    expect($analytics)->toHaveKey('total_leads');
    expect($analytics)->toHaveKey('by_status');
    expect($analytics)->toHaveKey('qualified_rate');
    expect($analytics)->toHaveKey('conversion_rate');
    expect($analytics['total_leads'])->toBe(10);
    expect($analytics['by_status']['new'])->toBe(5);
    expect($analytics['by_status']['qualified'])->toBe(3);
});

test('can get lead pipeline data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Lead::factory()->create(['status' => 'new', 'score' => 50]);
    Lead::factory()->create(['status' => 'qualified', 'score' => 70]);
    Lead::factory()->create(['status' => 'closed_won', 'score' => 90]);

    $pipeline = $this->service->getLeadPipeline();

    expect($pipeline)->toBeArray();
    expect($pipeline)->toHaveCount(3);

    $newStage = collect($pipeline)->firstWhere('status', 'new');
    expect($newStage['count'])->toBe(1);
    expect($newStage['avg_score'])->toBe(50.0);
});

test('can update behavioral data and apply scoring', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create scoring rule for page visits
    LeadScoringRule::factory()->create([
        'trigger_type' => 'page_visit',
        'conditions' => ['page' => 'pricing'],
        'points' => 10,
        'is_active' => true,
    ]);

    $lead = Lead::factory()->create(['score' => 20]);
    $initialScore = $lead->score;

    $behaviorData = [
        'page_visits' => [
            ['page' => 'pricing', 'timestamp' => now()->toISOString()],
        ],
        'time_on_site' => 300,
    ];

    $this->service->updateBehavioralData($lead, $behaviorData);

    $lead->refresh();
    expect($lead->behavioral_data)->toHaveKey('page_visits');
    expect($lead->score)->toBeGreaterThan($initialScore);
});

test('can get leads needing attention', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create leads that need attention
    $unassigned = Lead::factory()->create(['assigned_to' => null]);
    $urgentNotContacted = Lead::factory()->create([
        'priority' => 'urgent',
        'last_contacted_at' => null,
    ]);
    $oldContact = Lead::factory()->create([
        'last_contacted_at' => now()->subDays(10),
        'status' => 'contacted',
    ]);

    $needsAttention = $this->service->getLeadsNeedingAttention();

    expect($needsAttention)->toHaveCount(3);
    expect($needsAttention->pluck('id'))->toContain($unassigned->id);
    expect($needsAttention->pluck('id'))->toContain($urgentNotContacted->id);
    expect($needsAttention->pluck('id'))->toContain($oldContact->id);
});

test('can generate comprehensive lead report', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Lead::factory()->count(5)->create(['source' => 'homepage']);
    Lead::factory()->count(3)->create(['source' => 'demo_request']);

    $report = $this->service->generateLeadReport();

    expect($report)->toHaveKey('analytics');
    expect($report)->toHaveKey('pipeline');
    expect($report)->toHaveKey('needs_attention');
    expect($report)->toHaveKey('top_sources');
    expect($report)->toHaveKey('performance_metrics');

    expect($report['top_sources'])->toHaveCount(2);
    expect($report['performance_metrics'])->toHaveKey('total_leads');
    expect($report['performance_metrics'])->toHaveKey('qualification_rate');
});

test('can sync lead to CRM systems', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create();

    // Mock CRM integration
    $integration = \Mockery::mock(\App\Models\CrmIntegration::class);
    $integration->shouldReceive('syncLead')
        ->with($lead)
        ->andReturn(['success' => true, 'crm_id' => 'test123']);

    // This would normally test actual CRM sync
    // For now, we'll just verify the method exists and can be called
    $results = $this->service->syncLeadToCRM($lead);

    expect($results)->toBeArray();
});

test('lead model can update score with activity logging', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create(['score' => 50]);
    $initialActivityCount = $lead->activities()->count();

    $lead->updateScore(20, 'Demo request');

    expect($lead->score)->toBe(70);
    expect($lead->activities())->toHaveCount($initialActivityCount + 1);

    $scoreActivity = $lead->activities()->latest()->first();
    expect($scoreActivity->type)->toBe('score_change');
    expect($scoreActivity->description)->toContain('50 to 70');
});

test('lead model can update status with activity logging', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $lead = Lead::factory()->create(['status' => 'new']);
    $initialActivityCount = $lead->activities()->count();

    $lead->updateStatus('qualified', 'Met qualification criteria');

    expect($lead->status)->toBe('qualified');
    expect($lead->qualified_at)->not->toBeNull();
    expect($lead->activities())->toHaveCount($initialActivityCount + 1);

    $statusActivity = $lead->activities()->latest()->first();
    expect($statusActivity->type)->toBe('status_change');
    expect($statusActivity->description)->toContain('new to qualified');
});

test('lead model scopes work correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Lead::factory()->create(['status' => 'new', 'priority' => 'high', 'score' => 85]);
    Lead::factory()->create(['status' => 'qualified', 'priority' => 'low', 'score' => 30]);
    Lead::factory()->create(['priority' => 'high', 'score' => 90, 'assigned_to' => null]);

    expect(Lead::byStatus('new'))->toHaveCount(1);
    expect(Lead::byPriority('high'))->toHaveCount(2);
    expect(Lead::hot())->toHaveCount(2);
    expect(Lead::unassigned())->toHaveCount(1);
});
