<?php

use App\Models\Lead;
use App\Models\User;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    // In a real app, you'd assign admin role here
    $this->actingAs($this->admin);
});

test('can view lead management dashboard', function () {
    $response = $this->get('/admin/lead-management');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('Admin/LeadManagement/Index')
    );
});

test('can create new lead via api', function () {
    $leadData = [
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

    $response = $this->post('/admin/lead-management', $leadData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Lead created successfully',
    ]);

    $this->assertDatabaseHas('leads', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'company' => 'Test Company',
    ]);
});

test('validates required fields when creating lead', function () {
    $response = $this->post('/admin/lead-management', [
        'first_name' => '',
        'email' => 'invalid-email',
        'lead_type' => 'invalid',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'lead_type']);
});

test('can update existing lead', function () {
    $lead = Lead::factory()->create([
        'first_name' => 'Jane',
        'status' => 'new',
    ]);

    $response = $this->put("/admin/lead-management/{$lead->id}", [
        'first_name' => 'Jane Updated',
        'status' => 'contacted',
        'priority' => 'high',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Lead updated successfully',
    ]);

    $lead->refresh();
    expect($lead->first_name)->toBe('Jane Updated');
    expect($lead->status)->toBe('contacted');
    expect($lead->priority)->toBe('high');
});

test('can qualify a lead', function () {
    $lead = Lead::factory()->create(['status' => 'contacted']);

    $qualificationData = [
        'qualification_data' => [
            'budget' => '10k_50k',
            'timeline' => '3_months',
            'decision_maker' => 'yes',
        ],
        'notes' => 'Lead meets all qualification criteria',
    ];

    $response = $this->post("/admin/lead-management/{$lead->id}/qualify", $qualificationData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Lead qualified successfully',
    ]);

    $lead->refresh();
    expect($lead->status)->toBe('qualified');
    expect($lead->qualified_at)->not->toBeNull();
});

test('can create follow-up sequence', function () {
    $lead = Lead::factory()->create();

    $response = $this->post("/admin/lead-management/{$lead->id}/follow-up", [
        'sequence_type' => 'standard',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Follow-up sequence created successfully',
    ]);

    // Check that activities were created
    expect($lead->activities())->toHaveCount(6); // 5 sequence + 1 note about sequence creation
});

test('can add activity to lead', function () {
    $lead = Lead::factory()->create();

    $activityData = [
        'type' => 'call',
        'subject' => 'Discovery call',
        'description' => 'Initial discovery call with prospect',
        'outcome' => 'positive',
    ];

    $response = $this->post("/admin/lead-management/{$lead->id}/activity", $activityData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Activity added successfully',
    ]);

    $this->assertDatabaseHas('lead_activities', [
        'lead_id' => $lead->id,
        'type' => 'call',
        'subject' => 'Discovery call',
        'outcome' => 'positive',
    ]);
});

test('can get lead analytics', function () {
    // Create some test leads
    Lead::factory()->count(5)->create(['status' => 'new']);
    Lead::factory()->count(3)->create(['status' => 'qualified', 'qualified_at' => now()]);
    Lead::factory()->count(2)->create(['status' => 'closed_won']);

    $response = $this->get('/admin/lead-management/analytics/data');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $data = $response->json('data');
    expect($data)->toHaveKey('analytics');
    expect($data)->toHaveKey('pipeline');
    expect($data)->toHaveKey('performance_metrics');
    expect($data['analytics']['total_leads'])->toBe(10);
});

test('can bulk sync leads to CRM', function () {
    $leads = Lead::factory()->count(3)->create();
    $leadIds = $leads->pluck('id')->toArray();

    $response = $this->post('/admin/lead-management/bulk-sync', [
        'lead_ids' => $leadIds,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Bulk sync completed',
    ]);
});

test('can export leads', function () {
    Lead::factory()->count(5)->create(['status' => 'new']);
    Lead::factory()->count(3)->create(['status' => 'qualified']);

    $response = $this->get('/admin/lead-management/export?status=new');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);

    $leads = $response->json('leads');
    expect($leads)->toHaveCount(5);
    expect(collect($leads)->pluck('status')->unique()->toArray())->toBe(['new']);
});

test('can get scoring rules', function () {
    $response = $this->get('/admin/lead-management/scoring-rules');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('can create scoring rule', function () {
    $ruleData = [
        'name' => 'Enterprise Lead Bonus',
        'description' => 'Extra points for enterprise leads',
        'trigger_type' => 'form_submission',
        'conditions' => ['lead_type' => 'enterprise'],
        'points' => 25,
        'is_active' => true,
        'priority' => 5,
    ];

    $response = $this->post('/admin/lead-management/scoring-rules', $ruleData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Scoring rule created successfully',
    ]);

    $this->assertDatabaseHas('lead_scoring_rules', [
        'name' => 'Enterprise Lead Bonus',
        'trigger_type' => 'form_submission',
        'points' => 25,
    ]);
});

test('can get CRM integrations', function () {
    $response = $this->get('/admin/lead-management/crm-integrations');

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
});

test('can create CRM integration', function () {
    $integrationData = [
        'name' => 'Salesforce Integration',
        'provider' => 'salesforce',
        'config' => [
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'instance_url' => 'https://test.salesforce.com',
        ],
        'is_active' => true,
        'sync_direction' => 'push',
        'sync_interval' => 3600,
    ];

    $response = $this->post('/admin/lead-management/crm-integrations', $integrationData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'CRM integration created successfully',
    ]);

    $this->assertDatabaseHas('crm_integrations', [
        'name' => 'Salesforce Integration',
        'provider' => 'salesforce',
        'is_active' => true,
    ]);
});

test('validates CRM integration data', function () {
    $response = $this->post('/admin/lead-management/crm-integrations', [
        'name' => '',
        'provider' => 'invalid',
        'config' => 'not-an-array',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'provider', 'config']);
});

test('can update lead behavioral data', function () {
    $lead = Lead::factory()->create(['score' => 50]);

    $behaviorData = [
        'behavioral_data' => [
            'page_visits' => [
                ['page' => 'pricing', 'timestamp' => now()->toISOString()],
                ['page' => 'features', 'timestamp' => now()->toISOString()],
            ],
            'time_on_site' => 450,
            'downloads' => 2,
        ],
    ];

    $response = $this->post("/admin/lead-management/{$lead->id}/behavior", $behaviorData);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'message' => 'Behavioral data updated successfully',
    ]);

    $lead->refresh();
    expect($lead->behavioral_data)->toHaveKey('page_visits');
    expect($lead->behavioral_data)->toHaveKey('time_on_site');
    expect($lead->behavioral_data['time_on_site'])->toBe(450);
});
