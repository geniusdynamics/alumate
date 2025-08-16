<?php

use App\Models\FundraisingCampaign;
use App\Models\User;

test('can create fundraising campaign', function () {
    $user = User::factory()->create();

    $campaignData = [
        'title' => 'Test Campaign',
        'description' => 'This is a test campaign',
        'goal_amount' => 10000,
        'start_date' => now()->addDay()->format('Y-m-d'),
        'end_date' => now()->addMonth()->format('Y-m-d'),
        'type' => 'general',
        'allow_peer_fundraising' => true,
        'show_donor_names' => true,
        'allow_anonymous_donations' => true,
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/fundraising-campaigns', $campaignData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'title',
            'description',
            'goal_amount',
            'raised_amount',
            'status',
            'creator',
        ]);

    $this->assertDatabaseHas('fundraising_campaigns', [
        'title' => 'Test Campaign',
        'created_by' => $user->id,
    ]);
});

test('can view campaign analytics', function () {
    $user = User::factory()->create();
    $campaign = FundraisingCampaign::factory()
        ->for($user, 'creator')
        ->active()
        ->create();

    $response = $this->actingAs($user)
        ->getJson("/api/fundraising-campaigns/{$campaign->id}/analytics");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'analytics' => [
                'total_raised',
                'goal_amount',
                'progress_percentage',
                'donor_count',
                'average_donation',
            ],
            'top_donors',
            'recent_donations',
        ]);
});

test('can create peer fundraiser', function () {
    $user = User::factory()->create();
    $campaign = FundraisingCampaign::factory()
        ->active()
        ->create(['allow_peer_fundraising' => true]);

    $peerFundraiserData = [
        'campaign_id' => $campaign->id,
        'title' => 'My Fundraiser',
        'personal_message' => 'Please support this cause',
        'goal_amount' => 5000,
    ];

    $response = $this->actingAs($user)
        ->postJson('/api/peer-fundraisers', $peerFundraiserData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'id',
            'title',
            'personal_message',
            'goal_amount',
            'raised_amount',
            'campaign',
            'user',
        ]);

    expect($peerFundraiser = \App\Models\PeerFundraiser::where([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'title' => 'My Fundraiser',
    ])->first())->not->toBeNull();
});
