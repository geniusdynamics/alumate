<?php

use App\Models\User;
use App\Models\Webhook;
use App\Services\WebhookService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

test('user can create a webhook', function () {
    $webhookData = [
        'url' => 'https://example.com/webhook',
        'events' => ['post.created', 'user.updated'],
        'name' => 'Test Webhook',
        'description' => 'A test webhook for development',
    ];

    $response = $this->postJson('/api/webhooks', $webhookData);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'url',
                'events',
                'status',
                'name',
                'description',
                'created_at',
            ],
        ]);

    $this->assertDatabaseHas('webhooks', [
        'user_id' => $this->user->id,
        'url' => 'https://example.com/webhook',
        'status' => 'active',
    ]);
});

test('user can list their webhooks', function () {
    Webhook::factory()->count(3)->create(['user_id' => $this->user->id]);
    Webhook::factory()->count(2)->create(); // Other user's webhooks

    $response = $this->getJson('/api/webhooks');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

test('user can view webhook details', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = $this->getJson("/api/webhooks/{$webhook->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'url',
                'events',
                'status',
            ],
        ]);
});

test('user cannot view other users webhooks', function () {
    $otherUser = User::factory()->create();
    $webhook = Webhook::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->getJson("/api/webhooks/{$webhook->id}");

    $response->assertForbidden();
});

test('user can update their webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $updateData = [
        'url' => 'https://updated-example.com/webhook',
        'events' => ['post.created', 'post.updated', 'user.created'],
    ];

    $response = $this->putJson("/api/webhooks/{$webhook->id}", $updateData);

    $response->assertSuccessful();

    $this->assertDatabaseHas('webhooks', [
        'id' => $webhook->id,
        'url' => 'https://updated-example.com/webhook',
    ]);
});

test('user can delete their webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = $this->deleteJson("/api/webhooks/{$webhook->id}");

    $response->assertSuccessful();

    $this->assertDatabaseMissing('webhooks', [
        'id' => $webhook->id,
    ]);
});

test('webhook validation works correctly', function () {
    $invalidData = [
        'url' => 'not-a-valid-url',
        'events' => [],
    ];

    $response = $this->postJson('/api/webhooks', $invalidData);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['url', 'events']);
});

test('webhook service can get available events', function () {
    $webhookService = app(WebhookService::class);
    $events = $webhookService->getAvailableEvents();

    expect($events)->toBeArray()
        ->and(count($events))->toBeGreaterThan(0)
        ->and($events[0])->toHaveKeys(['event', 'name', 'description']);
});

test('user can test a webhook', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    $response = $this->postJson("/api/webhooks/{$webhook->id}/test");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'delivery_id',
                'status',
            ],
        ]);

    $this->assertDatabaseHas('webhook_deliveries', [
        'webhook_id' => $webhook->id,
        'event_type' => 'webhook.test',
    ]);
});

test('rate limiting works for webhook endpoints', function () {
    $webhook = Webhook::factory()->create(['user_id' => $this->user->id]);

    // Make requests up to the limit (100 for webhook endpoints)
    for ($i = 0; $i < 101; $i++) {
        $response = $this->getJson('/api/webhooks');

        if ($i < 100) {
            $response->assertSuccessful();
        } else {
            $response->assertStatus(429); // Too Many Requests
            break;
        }
    }
});
