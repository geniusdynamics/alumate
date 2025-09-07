<?php

use App\Models\CalendarConnection;
use App\Models\Event;
use App\Models\MentorshipSession;
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

it('can list calendar connections', function () {
    CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/calendar/connections');

    $response->assertOk()
        ->assertJsonStructure([
            'connections' => [
                '*' => [
                    'id',
                    'provider',
                    'is_active',
                    'last_sync_at',
                    'sync_status',
                ],
            ],
        ]);
});

it('can connect a calendar provider', function () {
    $credentials = [
        'access_token' => 'test-token',
        'refresh_token' => 'test-refresh-token',
        'expires_in' => 3600,
    ];

    $response = $this->postJson('/api/calendar/connect', [
        'provider' => 'google',
        'credentials' => $credentials,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'connection' => [
                'id',
                'provider',
                'is_active',
                'last_sync_at',
            ],
        ]);

    $this->assertDatabaseHas('calendar_connections', [
        'user_id' => $this->user->id,
        'provider' => 'google',
        'is_active' => true,
    ]);
});

it('validates calendar connection request', function () {
    $response = $this->postJson('/api/calendar/connect', [
        'provider' => 'invalid-provider',
        'credentials' => [],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['provider', 'credentials.access_token']);
});

it('can disconnect a calendar provider', function () {
    $connection = CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/calendar/connections/{$connection->id}/disconnect");

    $response->assertOk()
        ->assertJson(['message' => 'Calendar disconnected successfully']);

    $this->assertDatabaseHas('calendar_connections', [
        'id' => $connection->id,
        'is_active' => false,
    ]);
});

it('can get sync status', function () {
    CalendarConnectionFactory::new()->count(2)->active()->create([
        'user_id' => $this->user->id,
    ]);

    CalendarConnectionFactory::new()->failed()->create([
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

it('can get user availability', function () {
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

it('can create a calendar event', function () {
    $eventData = [
        'title' => 'Test Meeting',
        'description' => 'A test meeting',
        'start_time' => now()->addHour()->toISOString(),
        'end_time' => now()->addHours(2)->toISOString(),
        'location' => 'Conference Room A',
        'attendees' => ['test@example.com'],
        'event_type' => 'general',
    ];

    $response = $this->postJson('/api/calendar/events', $eventData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'event' => [
                'id',
                'title',
                'start_time',
                'end_time',
                'location',
            ],
        ]);

    $this->assertDatabaseHas('events', [
        'user_id' => $this->user->id,
        'title' => 'Test Meeting',
    ]);
});

it('validates event creation request', function () {
    $response = $this->postJson('/api/calendar/events', [
        'title' => '',
        'start_time' => 'invalid-date',
        'end_time' => 'invalid-date',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'start_time', 'end_time']);
});

it('can schedule a mentorship session via service', function () {
    $mentor = User::factory()->create();
    $mentee = User::factory()->create();

    $sessionData = [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'start_time' => now()->addDay()->toISOString(),
        'duration_minutes' => 60,
        'topic' => 'Career Development',
        'notes' => 'Discussing career goals',
    ];

    $response = $this->postJson('/api/calendar/schedule-mentorship', $sessionData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'session' => [
                'id',
                'mentor',
                'mentee',
                'scheduled_at',
                'duration_minutes',
                'topic',
            ],
        ]);

    $this->assertDatabaseHas('mentorship_sessions', [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'topic' => 'Career Development',
    ]);
});

it('validates mentorship session scheduling via service', function () {
    $response = $this->postJson('/api/calendar/schedule-mentorship', [
        'mentor_id' => 999,
        'mentee_id' => 999,
        'start_time' => 'invalid-date',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['mentor_id', 'mentee_id', 'start_time']);
});

it('prevents unauthorized mentorship session scheduling via service', function () {
    $mentor = User::factory()->create();
    $mentee = User::factory()->create();
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser);

    $response = $this->postJson('/api/calendar/schedule-mentorship', [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'start_time' => now()->addDay()->toISOString(),
        'duration_minutes' => 60,
    ]);

    $response->assertForbidden();
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

it('can schedule a mentorship session', function () {
    $mentor = User::factory()->create();
    $mentee = User::factory()->create();

    $sessionData = [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'start_time' => now()->addDay()->toISOString(),
        'duration_minutes' => 60,
        'topic' => 'Career Development',
        'notes' => 'Discussing career goals',
    ];

    $response = $this->postJson('/api/calendar/schedule-mentorship', $sessionData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'session' => [
                'id',
                'mentor',
                'mentee',
                'scheduled_at',
                'duration_minutes',
                'topic',
            ],
        ]);

    $this->assertDatabaseHas('mentorship_sessions', [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'topic' => 'Career Development',
    ]);
});

it('validates mentorship session scheduling', function () {
    $response = $this->postJson('/api/calendar/schedule-mentorship', [
        'mentor_id' => 999,
        'mentee_id' => 999,
        'start_time' => 'invalid-date',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['mentor_id', 'mentee_id', 'start_time']);
});

it('prevents unauthorized mentorship session scheduling', function () {
    $mentor = User::factory()->create();
    $mentee = User::factory()->create();
    $unauthorizedUser = User::factory()->create();

    $this->actingAs($unauthorizedUser);

    $response = $this->postJson('/api/calendar/schedule-mentorship', [
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'start_time' => now()->addDay()->toISOString(),
        'duration_minutes' => 60,
    ]);

    $response->assertForbidden();
});

it('can sync calendar events', function () {
    $connection = CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/calendar/connections/{$connection->id}/sync");

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'sync_result' => [
                'success',
                'events_synced',
                'last_sync_at',
            ],
        ]);
});

it('can handle calendar sync failures gracefully', function () {
    $connection = CalendarConnectionFactory::new()->failed()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/calendar/connections/{$connection->id}/sync");

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'sync_result',
        ]);
});

it('can create events with virtual meetings', function () {
    $eventData = [
        'title' => 'Virtual Team Meeting',
        'description' => 'Weekly team sync',
        'start_time' => now()->addHour()->toISOString(),
        'end_time' => now()->addHours(2)->toISOString(),
        'location' => null,
        'is_virtual' => true,
        'meeting_url' => 'https://zoom.us/j/123456789',
        'attendees' => ['team@example.com'],
        'event_type' => 'meeting',
    ];

    $response = $this->postJson('/api/calendar/events', $eventData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'event' => [
                'id',
                'title',
                'start_time',
                'end_time',
                'is_virtual',
                'meeting_url',
            ],
        ]);

    $this->assertDatabaseHas('events', [
        'user_id' => $this->user->id,
        'title' => 'Virtual Team Meeting',
        'is_virtual' => true,
    ]);
});

it('can handle multiple calendar providers', function () {
    CalendarConnectionFactory::new()->active()->google()->create([
        'user_id' => $this->user->id,
    ]);

    CalendarConnectionFactory::new()->active()->outlook()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson('/api/calendar/connections');

    $response->assertOk();

    $connections = $response->json('connections');
    expect($connections)->toHaveCount(2);

    $providers = collect($connections)->pluck('provider')->sort()->values();
    expect($providers)->toEqual(['google', 'outlook']);
});
