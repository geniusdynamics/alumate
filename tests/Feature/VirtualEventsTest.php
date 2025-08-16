<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\JitsiMeetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Jitsi configuration
        config([
            'services.jitsi.domain' => 'meet.jit.si',
            'services.jitsi.default_config' => [],
        ]);
    }

    public function test_can_create_virtual_event_with_jitsi_meeting()
    {
        $user = User::factory()->create();

        $eventData = [
            'title' => 'Virtual Alumni Meetup',
            'description' => 'A virtual meetup for alumni',
            'format' => 'virtual',
            'type' => 'networking',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(2),
            'timezone' => 'UTC',
            'meeting_config' => [
                'platform' => 'jitsi',
                'settings' => [
                    'waiting_room_enabled' => false,
                    'chat_enabled' => true,
                    'screen_sharing_enabled' => true,
                    'recording_enabled' => false,
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201);

        $event = Event::first();
        $this->assertEquals('jitsi', $event->meeting_platform);
        $this->assertNotNull($event->jitsi_room_id);
        $this->assertNotNull($event->meeting_url);
        $this->assertTrue($event->meeting_embed_allowed);
    }

    public function test_can_create_hybrid_event_with_jitsi_meeting()
    {
        $user = User::factory()->create();

        $eventData = [
            'title' => 'Hybrid Alumni Conference',
            'description' => 'A hybrid conference for alumni',
            'format' => 'hybrid',
            'type' => 'professional',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(4),
            'timezone' => 'UTC',
            'venue_name' => 'Alumni Center',
            'venue_address' => '123 University Ave',
            'meeting_config' => [
                'platform' => 'jitsi',
                'settings' => [
                    'waiting_room_enabled' => true,
                    'chat_enabled' => true,
                    'screen_sharing_enabled' => true,
                    'recording_enabled' => true,
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201);

        $event = Event::first();
        $this->assertEquals('hybrid', $event->format);
        $this->assertEquals('jitsi', $event->meeting_platform);
        $this->assertTrue($event->waiting_room_enabled);
        $this->assertTrue($event->recording_enabled);
    }

    public function test_can_create_event_with_manual_meeting_url()
    {
        $user = User::factory()->create();

        $eventData = [
            'title' => 'Zoom Alumni Meetup',
            'description' => 'A virtual meetup using Zoom',
            'format' => 'virtual',
            'type' => 'networking',
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(7)->addHours(2),
            'timezone' => 'UTC',
            'meeting_config' => [
                'platform' => 'zoom',
                'settings' => [
                    'meeting_url' => 'https://zoom.us/j/123456789',
                    'meeting_password' => 'password123',
                    'meeting_instructions' => 'Join via Zoom app or browser',
                ],
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201);

        $event = Event::first();
        $this->assertEquals('zoom', $event->meeting_platform);
        $this->assertEquals('https://zoom.us/j/123456789', $event->meeting_url);
        $this->assertFalse($event->meeting_embed_allowed);
    }

    public function test_jitsi_meet_service_creates_meeting()
    {
        $event = Event::factory()->create([
            'title' => 'Test Virtual Event',
            'format' => 'virtual',
        ]);

        $jitsiService = app(JitsiMeetService::class);
        $meetingData = $jitsiService->createMeeting($event->id, $event->title);

        $this->assertArrayHasKey('room_id', $meetingData);
        $this->assertArrayHasKey('meeting_url', $meetingData);
        $this->assertArrayHasKey('embed_url', $meetingData);
        $this->assertArrayHasKey('config', $meetingData);

        $this->assertStringContains('alumni-'.$event->id, $meetingData['room_id']);
        $this->assertStringContains('meet.jit.si', $meetingData['meeting_url']);
    }

    public function test_jitsi_meet_service_validates_meeting_urls()
    {
        $jitsiService = app(JitsiMeetService::class);

        // Test Zoom URL
        $zoomResult = $jitsiService->validateMeetingUrl('https://zoom.us/j/123456789');
        $this->assertTrue($zoomResult['valid']);
        $this->assertEquals('zoom', $zoomResult['platform']);

        // Test Google Meet URL
        $meetResult = $jitsiService->validateMeetingUrl('https://meet.google.com/abc-defg-hij');
        $this->assertTrue($meetResult['valid']);
        $this->assertEquals('google_meet', $meetResult['platform']);

        // Test invalid URL
        $invalidResult = $jitsiService->validateMeetingUrl('not-a-url');
        $this->assertFalse($invalidResult['valid']);
    }

    public function test_can_get_meeting_credentials_for_virtual_event()
    {
        $event = Event::factory()->create([
            'format' => 'virtual',
            'meeting_platform' => 'jitsi',
            'jitsi_room_id' => 'alumni-123-test-event',
            'meeting_url' => 'https://meet.jit.si/alumni-123-test-event',
            'meeting_embed_allowed' => true,
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'recording_enabled' => false,
            'waiting_room_enabled' => false,
        ]);

        $jitsiService = app(JitsiMeetService::class);
        $credentials = $jitsiService->generateMeetingCredentials($event);

        $this->assertEquals('jitsi', $credentials['platform']);
        $this->assertEquals('alumni-123-test-event', $credentials['room_id']);
        $this->assertStringContains('meet.jit.si', $credentials['meeting_url']);
        $this->assertNotNull($credentials['embed_url']);
        $this->assertArrayHasKey('features', $credentials);
        $this->assertTrue($credentials['features']['chat']);
        $this->assertFalse($credentials['features']['recording']);
    }

    public function test_can_update_virtual_meeting_settings()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'organizer_id' => $user->id,
            'format' => 'virtual',
            'meeting_platform' => 'jitsi',
            'jitsi_room_id' => 'alumni-123-test-event',
            'chat_enabled' => true,
            'recording_enabled' => false,
        ]);

        $newSettings = [
            'chat_enabled' => false,
            'recording_enabled' => true,
            'waiting_room_enabled' => true,
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/events/{$event->id}/virtual-settings", $newSettings);

        $response->assertStatus(200);

        $event->refresh();
        $this->assertFalse($event->chat_enabled);
        $this->assertTrue($event->recording_enabled);
        $this->assertTrue($event->waiting_room_enabled);
    }

    public function test_virtual_event_viewer_only_shown_to_registered_users()
    {
        $organizer = User::factory()->create();
        $registeredUser = User::factory()->create();
        $unregisteredUser = User::factory()->create();

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'format' => 'virtual',
            'meeting_platform' => 'jitsi',
        ]);

        // Register one user
        $event->registrations()->create([
            'user_id' => $registeredUser->id,
            'status' => 'registered',
        ]);

        // Test registered user can see virtual event details
        $response = $this->actingAs($registeredUser)
            ->getJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $eventData = $response->json();
        $this->assertTrue($eventData['user_data']['is_registered']);

        // Test unregistered user cannot see meeting details
        $response = $this->actingAs($unregisteredUser)
            ->getJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $eventData = $response->json();
        $this->assertFalse($eventData['user_data']['is_registered']);
    }
}
