<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventCheckIn;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

describe('Events API', function () {
    it('can list events', function () {
        Event::factory()->count(5)->published()->create();
        
        $response = $this->getJson('/api/events');
        
        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'type',
                            'format',
                            'start_date',
                            'end_date',
                            'organizer',
                            'current_attendees'
                        ]
                    ],
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ]);
    });

    it('can filter events by type', function () {
        Event::factory()->published()->create(['type' => 'networking']);
        Event::factory()->published()->create(['type' => 'reunion']);
        
        $response = $this->getJson('/api/events?type=networking');
        
        $response->assertOk();
        $events = $response->json('data');
        
        expect($events)->toHaveCount(1);
        expect($events[0]['type'])->toBe('networking');
    });

    it('can filter events by format', function () {
        Event::factory()->published()->virtual()->create();
        Event::factory()->published()->inPerson()->create();
        
        $response = $this->getJson('/api/events?format=virtual');
        
        $response->assertOk();
        $events = $response->json('data');
        
        expect($events)->toHaveCount(1);
        expect($events[0]['format'])->toBe('virtual');
    });

    it('can search events by title', function () {
        Event::factory()->published()->create(['title' => 'Alumni Networking Event']);
        Event::factory()->published()->create(['title' => 'Class Reunion 2024']);
        
        $response = $this->getJson('/api/events?search=networking');
        
        $response->assertOk();
        $events = $response->json('data');
        
        expect($events)->toHaveCount(1);
        expect($events[0]['title'])->toContain('Networking');
    });

    it('can show a specific event', function () {
        $event = Event::factory()->published()->create();
        
        $response = $this->getJson("/api/events/{$event->id}");
        
        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'format',
                        'start_date',
                        'end_date',
                        'organizer',
                        'user_data' => [
                            'is_registered',
                            'is_checked_in',
                            'can_edit'
                        ]
                    ]
                ]);
    });

    it('can create an event', function () {
        $eventData = [
            'title' => 'Test Event',
            'description' => 'This is a test event',
            'type' => 'networking',
            'format' => 'in_person',
            'start_date' => now()->addDays(7)->toISOString(),
            'end_date' => now()->addDays(7)->addHours(3)->toISOString(),
            'timezone' => 'UTC',
            'venue_name' => 'Test Venue',
            'venue_address' => '123 Test St, Test City',
            'visibility' => 'public',
            'enable_networking' => true,
            'enable_checkin' => true,
        ];
        
        $response = $this->postJson('/api/events', $eventData);
        
        $response->assertCreated()
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'title',
                        'organizer'
                    ]
                ]);
        
        $this->assertDatabaseHas('events', [
            'title' => 'Test Event',
            'organizer_id' => $this->user->id,
        ]);
    });

    it('validates required fields when creating an event', function () {
        $response = $this->postJson('/api/events', []);
        
        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'title',
                    'description',
                    'type',
                    'format',
                    'start_date',
                    'end_date',
                    'timezone',
                    'visibility'
                ]);
    });

    it('can update an event', function () {
        $event = Event::factory()->create(['organizer_id' => $this->user->id]);
        
        $updateData = [
            'title' => 'Updated Event Title',
            'description' => 'Updated description',
        ];
        
        $response = $this->putJson("/api/events/{$event->id}", $updateData);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
        ]);
    });

    it('prevents unauthorized users from updating events', function () {
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $otherUser->id]);
        
        $response = $this->putJson("/api/events/{$event->id}", [
            'title' => 'Unauthorized Update',
        ]);
        
        $response->assertForbidden();
    });

    it('can delete an event', function () {
        $event = Event::factory()->create(['organizer_id' => $this->user->id]);
        
        $response = $this->deleteJson("/api/events/{$event->id}");
        
        $response->assertOk();
        
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    });
});

describe('Event Registration', function () {
    it('can register for an event', function () {
        $event = Event::factory()->published()->upcoming()->create();
        
        $registrationData = [
            'guests_count' => 1,
            'guest_details' => [
                ['name' => 'John Doe', 'email' => 'john@example.com']
            ],
            'special_requirements' => 'Vegetarian meal',
        ];
        
        $response = $this->postJson("/api/events/{$event->id}/register", $registrationData);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->user->id,
            'status' => 'registered',
            'guests_count' => 1,
        ]);
    });

    it('prevents duplicate registrations', function () {
        $event = Event::factory()->published()->upcoming()->create();
        
        // First registration
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id,
            'status' => 'registered',
        ]);
        
        // Attempt duplicate registration
        $response = $this->postJson("/api/events/{$event->id}/register");
        
        $response->assertBadRequest();
    });

    it('can cancel registration', function () {
        $event = Event::factory()->published()->upcoming()->create();
        $registration = EventRegistration::factory()->registered()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->deleteJson("/api/events/{$event->id}/register", [
            'reason' => 'Schedule conflict',
        ]);
        
        $response->assertOk();
        
        $registration->refresh();
        expect($registration->status)->toBe('cancelled');
        expect($registration->cancellation_reason)->toBe('Schedule conflict');
    });

    it('handles waitlist when event is full', function () {
        $event = Event::factory()->published()->upcoming()->withCapacity(1)->create();
        
        // Fill the event
        EventRegistration::factory()->registered()->create([
            'event_id' => $event->id,
        ]);
        $event->updateAttendeeCount();
        
        // Try to register when full
        $response = $this->postJson("/api/events/{$event->id}/register");
        
        $response->assertOk();
        
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $this->user->id,
            'status' => 'waitlisted',
        ]);
    });
});

describe('Event Check-in', function () {
    it('can check in to an event', function () {
        $event = Event::factory()->published()->create([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);
        
        EventRegistration::factory()->registered()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id,
        ]);
        
        $checkInData = [
            'method' => 'manual',
            'notes' => 'Checked in at registration desk',
        ];
        
        $response = $this->postJson("/api/events/{$event->id}/checkin", $checkInData);
        
        $response->assertOk();
        
        $this->assertDatabaseHas('event_check_ins', [
            'event_id' => $event->id,
            'user_id' => $this->user->id,
            'check_in_method' => 'manual',
        ]);
    });

    it('prevents check-in for unregistered users', function () {
        $event = Event::factory()->published()->create([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);
        
        $response = $this->postJson("/api/events/{$event->id}/checkin");
        
        $response->assertBadRequest();
    });

    it('prevents duplicate check-ins', function () {
        $event = Event::factory()->published()->create([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);
        
        $registration = EventRegistration::factory()->registered()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id,
        ]);
        
        EventCheckIn::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id,
        ]);
        
        $response = $this->postJson("/api/events/{$event->id}/checkin");
        
        $response->assertBadRequest();
    });
});

describe('Event Analytics', function () {
    it('can get event analytics for organizer', function () {
        $event = Event::factory()->create(['organizer_id' => $this->user->id]);
        
        // Create some registrations
        EventRegistration::factory()->registered()->count(5)->create(['event_id' => $event->id]);
        EventRegistration::factory()->attended()->count(3)->create(['event_id' => $event->id]);
        EventRegistration::factory()->cancelled()->count(2)->create(['event_id' => $event->id]);
        
        $response = $this->getJson("/api/events/{$event->id}/analytics");
        
        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'total_registered',
                        'total_waitlisted',
                        'total_cancelled',
                        'total_attended',
                        'total_no_show',
                        'check_in_rate',
                        'capacity_utilization',
                        'registration_timeline'
                    ]
                ]);
    });

    it('prevents non-organizers from viewing analytics', function () {
        $otherUser = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $otherUser->id]);
        
        $response = $this->getJson("/api/events/{$event->id}/analytics");
        
        $response->assertForbidden();
    });
});

describe('Event Recommendations', function () {
    it('can get upcoming events for user', function () {
        // Create events user is registered for
        $events = Event::factory()->published()->upcoming()->count(3)->create();
        
        foreach ($events as $event) {
            EventRegistration::factory()->registered()->create([
                'event_id' => $event->id,
                'user_id' => $this->user->id,
            ]);
        }
        
        $response = $this->getJson('/api/events-upcoming');
        
        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'start_date',
                            'organizer'
                        ]
                    ]
                ]);
        
        expect($response->json('data'))->toHaveCount(3);
    });

    it('can get recommended events', function () {
        Event::factory()->published()->upcoming()->count(5)->create();
        
        $response = $this->getJson('/api/events-recommended');
        
        $response->assertOk()
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'type',
                            'format',
                            'start_date'
                        ]
                    ]
                ]);
    });
});