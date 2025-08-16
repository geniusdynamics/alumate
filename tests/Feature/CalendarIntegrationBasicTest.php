<?php

use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'web');
});

it('can access calendar connections endpoint', function () {
    $response = $this->getJson('/api/calendar/connections');

    $response->assertOk()
        ->assertJsonStructure([
            'connections',
        ]);
});

it('can create calendar connection manually', function () {
    $connection = CalendarConnection::create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'credentials' => [
            'access_token' => 'test-token',
            'refresh_token' => 'test-refresh-token',
        ],
        'is_active' => true,
    ]);

    expect($connection)->toBeInstanceOf(CalendarConnection::class);
    expect($connection->user_id)->toBe($this->user->id);
    expect($connection->provider)->toBe('google');
    expect($connection->is_active)->toBeTrue();
});

it('calendar connection belongs to user', function () {
    $connection = CalendarConnection::create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'credentials' => ['access_token' => 'test'],
        'is_active' => true,
    ]);

    expect($connection->user)->toBeInstanceOf(User::class);
    expect($connection->user->id)->toBe($this->user->id);
});

it('user has calendar connections relationship', function () {
    CalendarConnection::create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'credentials' => ['access_token' => 'test'],
        'is_active' => true,
    ]);

    $connections = $this->user->calendarConnections;

    expect($connections)->toHaveCount(1);
    expect($connections->first())->toBeInstanceOf(CalendarConnection::class);
});

it('can validate calendar connection request', function () {
    $response = $this->postJson('/api/calendar/connect', [
        'provider' => 'invalid-provider',
        'credentials' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['provider', 'credentials.access_token']);
});

it('can get sync status', function () {
    CalendarConnection::create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'credentials' => ['access_token' => 'test'],
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/calendar/sync-status');

    $response->assertOk()
        ->assertJsonStructure([
            'summary' => [
                'total_connections',
                'active_connections',
                'failed_syncs',
                'last_sync',
            ],
            'connections',
        ]);
});
