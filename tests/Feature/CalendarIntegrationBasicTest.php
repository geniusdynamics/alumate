<?php

use App\Models\CalendarConnection;
use App\Models\Event;
use App\Models\User;
use App\Services\CalendarIntegrationService;
use Database\Factories\CalendarConnectionFactory;
use Database\Factories\EventFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'web');
    $this->calendarService = app(CalendarIntegrationService::class);
});

it('can access calendar connections endpoint', function () {
    $response = $this->getJson('/api/calendar/connections');

    $response->assertOk()
        ->assertJsonStructure([
            'connections',
        ]);
});

it('can create calendar connection using factory', function () {
    $connection = CalendarConnectionFactory::new()->create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'is_active' => true,
    ]);

    expect($connection)->toBeInstanceOf(CalendarConnection::class);
    expect($connection->user_id)->toBe($this->user->id);
    expect($connection->provider)->toBe('google');
    expect($connection->is_active)->toBeTrue();
});

it('can create active calendar connection', function () {
    $connection = CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    expect($connection->is_active)->toBeTrue();
    expect($connection->sync_status)->toBe('success');
    expect($connection->last_sync_at)->not->toBeNull();
});

it('can create failed calendar connection', function () {
    $connection = CalendarConnectionFactory::new()->failed()->create([
        'user_id' => $this->user->id,
    ]);

    expect($connection->is_active)->toBeTrue();
    expect($connection->sync_status)->toBe('failed');
    expect($connection->sync_error)->not->toBeNull();
});

it('calendar connection belongs to user', function () {
    $connection = CalendarConnectionFactory::new()->create([
        'user_id' => $this->user->id,
        'provider' => 'google',
        'is_active' => true,
    ]);

    expect($connection->user)->toBeInstanceOf(User::class);
    expect($connection->user->id)->toBe($this->user->id);
});

it('user has calendar connections relationship', function () {
    CalendarConnectionFactory::new()->create([
        'user_id' => $this->user->id,
        'provider' => 'google',
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
    CalendarConnectionFactory::new()->active()->create([
        'user_id' => $this->user->id,
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

it('can send calendar invites for an event', function () {
    Mail::fake();

    $event = EventFactory::new()->create([
        'user_id' => $this->user->id,
        'attendees' => ['test1@example.com', 'test2@example.com'],
    ]);

    $response = $this->postJson("/api/calendar/events/{$event->id}/invites");

    $response->assertOk()
        ->assertJson(['message' => 'Calendar invites sent successfully']);

    // Verify that email invites were attempted
    Mail::assertSent(\App\Mail\CalendarInviteMail::class, 2);
});

it('requires authorization to send invites', function () {
    $otherUser = User::factory()->create();
    $event = EventFactory::new()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->postJson("/api/calendar/events/{$event->id}/invites");

    $response->assertForbidden();
});

it('can get user availability', function () {
    CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    $startDate = now()->format('Y-m-d');
    $endDate = now()->addDays(7)->format('Y-m-d');

    $response = $this->getJson("/api/calendar/availability?start_date={$startDate}&end_date={$endDate}");

    $response->assertOk()
        ->assertJsonStructure([
            'availability',
            'user' => [
                'id',
                'name',
            ],
        ]);
});

it('can find available time slots for multiple users', function () {
    $otherUser = User::factory()->create();

    $startDate = now()->format('Y-m-d');
    $endDate = now()->addDays(7)->format('Y-m-d');

    $response = $this->postJson('/api/calendar/find-slots', [
        'user_ids' => [$this->user->id, $otherUser->id],
        'start_date' => $startDate,
        'end_date' => $endDate,
        'duration_minutes' => 60,
        'working_hours' => ['09:00', '17:00'],
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'available_slots',
            'parameters' => [
                'users',
                'duration_minutes',
                'working_hours',
            ],
        ]);
});

it('validates find slots request', function () {
    $response = $this->postJson('/api/calendar/find-slots', [
        'user_ids' => [],
        'start_date' => 'invalid-date',
        'end_date' => 'invalid-date',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['user_ids', 'start_date', 'end_date']);
});
