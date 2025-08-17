<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventRegistration;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventsSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $organizer;

    protected User $attendee1;

    protected User $attendee2;

    protected Institution $institution;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->institution = Institution::factory()->create();

        $this->organizer = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->organizer->assignRole('Institution Admin');

        $this->attendee1 = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->attendee1->assignRole('Graduate');

        $this->attendee2 = User::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->attendee2->assignRole('Graduate');

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'institution_id' => $this->institution->id,
            'title' => 'Annual Alumni Networking Event',
            'description' => 'Join us for an evening of networking and reconnection',
            'event_type' => 'networking',
            'start_date' => now()->addWeeks(2),
            'end_date' => now()->addWeeks(2)->addHours(3),
            'location' => 'University Campus, Main Hall',
            'max_attendees' => 100,
            'is_public' => true,
            'registration_required' => true,
        ]);
    }

    public function test_event_creation_workflow()
    {
        $eventData = [
            'title' => 'Tech Career Fair 2024',
            'description' => 'Connect with top tech companies and explore career opportunities',
            'event_type' => 'career_fair',
            'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
            'end_date' => now()->addMonth()->addHours(6)->format('Y-m-d H:i:s'),
            'location' => 'Convention Center',
            'max_attendees' => 500,
            'is_public' => true,
            'registration_required' => true,
            'registration_deadline' => now()->addWeeks(3)->format('Y-m-d'),
            'tags' => ['career', 'technology', 'networking'],
        ];

        $response = $this->actingAs($this->organizer)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
            ]);

        // Verify event was created
        $this->assertDatabaseHas('events', [
            'title' => 'Tech Career Fair 2024',
            'organizer_id' => $this->organizer->id,
            'event_type' => 'career_fair',
        ]);

        $event = Event::where('title', 'Tech Career Fair 2024')->first();
        $this->assertEquals(['career', 'technology', 'networking'], $event->tags);
    }

    public function test_event_registration_and_rsvp_workflow()
    {
        // Register for event
        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$this->event->id}/register", [
                'attendance_type' => 'in_person',
                'dietary_restrictions' => 'Vegetarian',
                'special_requests' => 'Wheelchair accessible seating',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully registered for event',
            ]);

        // Verify registration was created
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'status' => 'registered',
        ]);

        // Test duplicate registration prevention
        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$this->event->id}/register");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You are already registered for this event',
            ]);

        // Update RSVP status
        $response = $this->actingAs($this->attendee1)
            ->putJson("/api/events/{$this->event->id}/rsvp", [
                'status' => 'attending',
            ]);

        $response->assertStatus(200);

        // Verify RSVP was updated
        $registration = EventRegistration::where('event_id', $this->event->id)
            ->where('user_id', $this->attendee1->id)
            ->first();

        $this->assertEquals('attending', $registration->rsvp_status);
    }

    public function test_event_capacity_management()
    {
        // Set event capacity to 2
        $this->event->update(['max_attendees' => 2]);

        // Register two attendees
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'status' => 'registered',
        ]);

        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee2->id,
            'status' => 'registered',
        ]);

        // Try to register third attendee
        $thirdAttendee = User::factory()->create();

        $response = $this->actingAs($thirdAttendee)
            ->postJson("/api/events/{$this->event->id}/register");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Event is at full capacity',
            ]);

        // Test waitlist functionality
        $response = $this->actingAs($thirdAttendee)
            ->postJson("/api/events/{$this->event->id}/register", [
                'join_waitlist' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Added to event waitlist',
            ]);

        // Verify waitlist registration
        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $this->event->id,
            'user_id' => $thirdAttendee->id,
            'status' => 'waitlisted',
        ]);
    }

    public function test_virtual_event_creation_and_management()
    {
        $virtualEventData = [
            'title' => 'Virtual Alumni Webinar',
            'description' => 'Learn about industry trends from successful alumni',
            'event_type' => 'webinar',
            'start_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'end_date' => now()->addWeek()->addHours(2)->format('Y-m-d H:i:s'),
            'is_virtual' => true,
            'max_attendees' => 1000,
            'virtual_platform' => 'zoom',
            'virtual_settings' => [
                'meeting_id' => '123-456-789',
                'passcode' => 'alumni2024',
                'waiting_room' => true,
                'recording_enabled' => true,
            ],
        ];

        $response = $this->actingAs($this->organizer)
            ->postJson('/api/events', $virtualEventData);

        $response->assertStatus(201);

        $event = Event::where('title', 'Virtual Alumni Webinar')->first();
        $this->assertTrue($event->is_virtual);
        $this->assertEquals('zoom', $event->virtual_platform);
        $this->assertArrayHasKey('meeting_id', $event->virtual_settings);

        // Test virtual event registration
        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$event->id}/register", [
                'attendance_type' => 'virtual',
            ]);

        $response->assertStatus(200);

        // Get virtual event access details
        $response = $this->actingAs($this->attendee1)
            ->getJson("/api/events/{$event->id}/virtual-access");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'platform',
                    'meeting_url',
                    'meeting_id',
                    'passcode',
                    'instructions',
                ],
            ]);
    }

    public function test_event_check_in_and_attendance_tracking()
    {
        // Register attendee
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'status' => 'registered',
            'rsvp_status' => 'attending',
        ]);

        // Update event to current time for check-in
        $this->event->update([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        // Check in attendee
        $response = $this->actingAs($this->organizer)
            ->postJson("/api/events/{$this->event->id}/check-in", [
                'user_id' => $this->attendee1->id,
                'check_in_method' => 'manual',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Attendee checked in successfully',
            ]);

        // Verify attendance record
        $this->assertDatabaseHas('event_attendees', [
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'checked_in' => true,
        ]);

        // Test self check-in with QR code
        $response = $this->actingAs($this->attendee2)
            ->postJson("/api/events/{$this->event->id}/self-check-in", [
                'qr_code' => $this->event->check_in_code,
            ]);

        // Should fail if not registered
        $response->assertStatus(400);

        // Register attendee2 first
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee2->id,
            'status' => 'registered',
        ]);

        // Now self check-in should work
        $response = $this->actingAs($this->attendee2)
            ->postJson("/api/events/{$this->event->id}/self-check-in", [
                'qr_code' => $this->event->check_in_code,
            ]);

        $response->assertStatus(200);
    }

    public function test_event_networking_features()
    {
        // Register multiple attendees
        $attendees = User::factory()->count(5)->create();

        foreach ($attendees as $attendee) {
            EventRegistration::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $attendee->id,
                'status' => 'registered',
            ]);
        }

        // Get event attendees for networking
        $response = $this->actingAs($this->attendee1)
            ->getJson("/api/events/{$this->event->id}/attendees");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar_url',
                        'bio',
                        'current_position',
                        'interests',
                        'connection_status',
                    ],
                ],
            ]);

        // Send networking message
        $targetAttendee = $attendees->first();

        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$this->event->id}/network", [
                'recipient_id' => $targetAttendee->id,
                'message' => 'Hi! I saw we both attended this event. Would love to connect!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Networking message sent successfully',
            ]);

        // Verify networking message was created
        $this->assertDatabaseHas('event_networking_messages', [
            'event_id' => $this->event->id,
            'sender_id' => $this->attendee1->id,
            'recipient_id' => $targetAttendee->id,
        ]);
    }

    public function test_event_feedback_and_follow_up()
    {
        // Mark event as completed
        $this->event->update([
            'start_date' => now()->subHours(4),
            'end_date' => now()->subHour(),
            'status' => 'completed',
        ]);

        // Create attendance record
        EventAttendee::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'checked_in' => true,
        ]);

        // Submit event feedback
        $feedbackData = [
            'overall_rating' => 5,
            'content_rating' => 4,
            'organization_rating' => 5,
            'venue_rating' => 4,
            'comments' => 'Great event! Loved the networking opportunities.',
            'would_recommend' => true,
            'suggestions' => 'Maybe provide more time for Q&A sessions',
        ];

        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$this->event->id}/feedback", $feedbackData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Feedback submitted successfully',
            ]);

        // Verify feedback was stored
        $this->assertDatabaseHas('event_feedback', [
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'overall_rating' => 5,
        ]);

        // Get event analytics for organizer
        $response = $this->actingAs($this->organizer)
            ->getJson("/api/events/{$this->event->id}/analytics");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'registration_stats',
                    'attendance_stats',
                    'feedback_summary',
                    'engagement_metrics',
                ],
            ]);
    }

    public function test_recurring_event_management()
    {
        $recurringEventData = [
            'title' => 'Monthly Alumni Coffee Chat',
            'description' => 'Casual monthly meetup for local alumni',
            'event_type' => 'social',
            'start_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'end_date' => now()->addWeek()->addHours(2)->format('Y-m-d H:i:s'),
            'location' => 'Local Coffee Shop',
            'is_recurring' => true,
            'recurrence_pattern' => [
                'frequency' => 'monthly',
                'interval' => 1,
                'day_of_month' => 15,
                'end_date' => now()->addYear()->format('Y-m-d'),
            ],
        ];

        $response = $this->actingAs($this->organizer)
            ->postJson('/api/events', $recurringEventData);

        $response->assertStatus(201);

        // Verify recurring events were created
        $events = Event::where('title', 'Monthly Alumni Coffee Chat')->get();
        $this->assertGreaterThan(1, $events->count());

        // Test updating recurring series
        $parentEvent = $events->where('is_parent_event', true)->first();

        $response = $this->actingAs($this->organizer)
            ->putJson("/api/events/{$parentEvent->id}/series", [
                'update_type' => 'all_future',
                'location' => 'New Coffee Shop Location',
            ]);

        $response->assertStatus(200);

        // Verify future events were updated
        $futureEvents = Event::where('parent_event_id', $parentEvent->id)
            ->where('start_date', '>', now())
            ->get();

        foreach ($futureEvents as $event) {
            $this->assertEquals('New Coffee Shop Location', $event->location);
        }
    }

    public function test_event_media_and_content_sharing()
    {
        Storage::fake('public');

        // Upload event banner
        $banner = UploadedFile::fake()->image('event-banner.jpg', 1200, 600);

        $response = $this->actingAs($this->organizer)
            ->postJson("/api/events/{$this->event->id}/media", [
                'type' => 'banner',
                'file' => $banner,
            ]);

        $response->assertStatus(200);

        // Verify file was stored
        Storage::disk('public')->assertExists('events/'.$banner->hashName());

        // Update event with banner
        $this->event->refresh();
        $this->assertNotNull($this->event->banner_url);

        // Share event content during event
        $this->event->update([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        $photo = UploadedFile::fake()->image('event-photo.jpg');

        $response = $this->actingAs($this->attendee1)
            ->postJson("/api/events/{$this->event->id}/share", [
                'content' => 'Having a great time at the alumni event!',
                'media' => [$photo],
                'share_to_timeline' => true,
            ]);

        $response->assertStatus(200);

        // Verify event content was shared
        $this->assertDatabaseHas('event_content_shares', [
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
        ]);
    }

    public function test_event_notification_system()
    {
        // Register for event
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->attendee1->id,
            'status' => 'registered',
        ]);

        // Test event reminder notifications
        $response = $this->actingAs($this->organizer)
            ->postJson("/api/events/{$this->event->id}/send-reminder", [
                'reminder_type' => '24_hours',
                'custom_message' => 'Don\'t forget about tomorrow\'s networking event!',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reminder sent to all registered attendees',
            ]);

        // Verify notifications were created
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->attendee1->id,
            'type' => 'App\Notifications\EventReminder',
        ]);

        // Test event update notifications
        $response = $this->actingAs($this->organizer)
            ->putJson("/api/events/{$this->event->id}", [
                'location' => 'Updated Location - Building B',
                'notify_attendees' => true,
            ]);

        $response->assertStatus(200);

        // Verify update notification was sent
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->attendee1->id,
            'type' => 'App\Notifications\EventUpdated',
        ]);
    }

    public function test_event_search_and_discovery()
    {
        // Create various events for search testing
        Event::factory()->create([
            'title' => 'Tech Networking Mixer',
            'event_type' => 'networking',
            'tags' => ['technology', 'networking'],
            'is_public' => true,
        ]);

        Event::factory()->create([
            'title' => 'Career Development Workshop',
            'event_type' => 'workshop',
            'tags' => ['career', 'professional-development'],
            'is_public' => true,
        ]);

        Event::factory()->create([
            'title' => 'Alumni Reunion Gala',
            'event_type' => 'reunion',
            'tags' => ['reunion', 'social'],
            'is_public' => true,
        ]);

        // Search by title
        $response = $this->actingAs($this->attendee1)
            ->getJson('/api/events/search?query=Tech');

        $response->assertStatus(200);
        $events = $response->json('data.data');
        $this->assertCount(1, $events);
        $this->assertStringContainsString('Tech', $events[0]['title']);

        // Filter by event type
        $response = $this->actingAs($this->attendee1)
            ->getJson('/api/events?event_type=networking');

        $response->assertStatus(200);
        $events = $response->json('data.data');
        $networkingEvents = collect($events)->where('event_type', 'networking');
        $this->assertGreaterThan(0, $networkingEvents->count());

        // Filter by tags
        $response = $this->actingAs($this->attendee1)
            ->getJson('/api/events?tags[]=career&tags[]=technology');

        $response->assertStatus(200);
        $events = $response->json('data.data');

        foreach ($events as $event) {
            $eventTags = $event['tags'] ?? [];
            $hasMatchingTag = array_intersect(['career', 'technology'], $eventTags);
            $this->assertNotEmpty($hasMatchingTag);
        }

        // Filter by date range
        $response = $this->actingAs($this->attendee1)
            ->getJson('/api/events?start_date='.now()->format('Y-m-d').'&end_date='.now()->addMonth()->format('Y-m-d'));

        $response->assertStatus(200);
        $events = $response->json('data.data');

        foreach ($events as $event) {
            $eventDate = \Carbon\Carbon::parse($event['start_date']);
            $this->assertTrue($eventDate->between(now(), now()->addMonth()));
        }
    }
}
