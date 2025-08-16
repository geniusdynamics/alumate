<?php

use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailMarketingService;

// Simple email marketing tests without full seeding

test('can create email campaign', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $campaignData = [
        'name' => 'Test Newsletter',
        'description' => 'A test newsletter campaign',
        'subject' => 'Welcome to our Alumni Network',
        'content' => 'Hello {{first_name}}, welcome to our community!',
        'type' => 'newsletter',
        'provider' => 'internal',
    ];

    $response = $this->postJson('/api/email-campaigns', $campaignData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'campaign' => [
                'id',
                'name',
                'subject',
                'type',
                'status',
                'provider',
            ],
        ]);

    expect($response->json('campaign.name'))->toBe('Test Newsletter');
    expect($response->json('campaign.status'))->toBe('draft');
});

test('can get campaign list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/email-campaigns');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'campaigns',
            'stats' => [
                'total',
                'sent',
                'scheduled',
                'draft',
            ],
        ]);
});

test('can preview campaign', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $campaign = EmailCampaign::create([
        'name' => 'Test Campaign',
        'subject' => 'Test Subject',
        'content' => 'Hello {{first_name}}, your current role is {{current_role}}.',
        'type' => 'newsletter',
        'status' => 'draft',
        'provider' => 'internal',
        'created_by' => $user->id,
        'tenant_id' => $user->tenant_id,
    ]);

    $response = $this->postJson("/api/email-campaigns/{$campaign->id}/preview");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'preview' => [
                'subject',
                'content',
                'recipient' => [
                    'name',
                    'email',
                ],
            ],
        ]);
});

test('email marketing service creates campaign', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $service = app(EmailMarketingService::class);

    $campaignData = [
        'name' => 'Service Test Campaign',
        'subject' => 'Test Subject',
        'content' => 'Test content with {{first_name}}',
        'type' => 'engagement',
        'provider' => 'internal',
    ];

    $campaign = $service->createCampaign($campaignData);

    expect($campaign)->toBeInstanceOf(EmailCampaign::class);
    expect($campaign->name)->toBe('Service Test Campaign');
    expect($campaign->status)->toBe('draft');
    expect($campaign->created_by)->toBe($user->id);
});

test('can get campaign recipients', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $campaign = EmailCampaign::create([
        'name' => 'Test Campaign',
        'subject' => 'Test Subject',
        'content' => 'Test content',
        'type' => 'newsletter',
        'status' => 'draft',
        'provider' => 'internal',
        'audience_criteria' => [
            'graduation_years' => [2020, 2021],
        ],
        'created_by' => $user->id,
        'tenant_id' => $user->tenant_id,
    ]);

    $response = $this->getJson("/api/email-campaigns/{$campaign->id}/recipients");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'recipients',
            'count',
        ]);
});

test('can create automation rule', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $template = EmailTemplate::create([
        'name' => 'Welcome Template',
        'category' => 'newsletter',
        'html_content' => '<h1>Welcome!</h1>',
        'is_active' => true,
        'created_by' => $user->id,
        'tenant_id' => $user->tenant_id,
    ]);

    $ruleData = [
        'name' => 'Welcome New Users',
        'description' => 'Send welcome email to new users',
        'trigger_event' => 'user_registered',
        'template_id' => $template->id,
        'delay_minutes' => 0,
        'is_active' => true,
    ];

    $response = $this->postJson('/api/email-automation-rules', $ruleData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'rule' => [
                'id',
                'name',
                'trigger_event',
                'is_active',
            ],
        ]);

    expect($response->json('rule.name'))->toBe('Welcome New Users');
    expect($response->json('rule.trigger_event'))->toBe('user_registered');
});

test('can get email analytics', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/email-campaigns/analytics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'analytics' => [
                'total_campaigns',
                'total_sent',
                'total_recipients',
                'total_delivered',
                'total_opened',
                'total_clicked',
                'average_open_rate',
                'average_click_rate',
                'campaigns_by_type',
                'campaigns_by_status',
            ],
        ]);
});

test('personalization works', function () {
    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);

    $service = app(EmailMarketingService::class);

    $content = 'Hello {{full_name}}, welcome to our platform!';
    $personalizedContent = $service->personalizeContent($content, $user);

    expect($personalizedContent)->toContain('Hello John Doe');
});
