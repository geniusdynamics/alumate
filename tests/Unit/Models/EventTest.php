<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\User;
use App\Models\Institution;
use App\Models\EventRegistration;
use App\Models\EventCheckIn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test event fillable attributes are correctly configured
     */
    public function test_fillable_attributes()
    {
        $event = new Event();

        $fillable = $event->getFillable();

        // Test critical fillable attributes
        $this->assertContains('title', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('start_date', $fillable);
        $this->assertContains('end_date', $fillable);
        $this->assertContains('type', $fillable);
        $this->assertContains('format', $fillable);
        $this->assertContains('organizer_id', $fillable);
        $this->assertContains('institution_id', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('max_capacity', $fillable);
    }

    /**
     * Test event cast attributes are correctly configured
     */
    public function test_cast_attributes()
    {
        $event = new Event();

        $casts = array_merge($event->casts(), $event->getCasts());

        // Test critical cast attributes
        $this->assertArrayHasKey('start_date', $casts);
        $this->assertArrayHasKey('end_date', $casts);
        $this->assertArrayHasKey('registration_deadline', $casts);
        $this->assertEquals('datetime', $casts['start_date']);
        $this->assertEquals('datetime', $casts['end_date']);

        // Test boolean casts
        $this->assertArrayHasKey('requires_approval', $casts);
        $this->assertArrayHasKey('allow_guests', $casts);
        $this->assertArrayHasKey('enable_networking', $casts);
        $this->assertEquals('boolean', $casts['requires_approval']);

        // Test array casts
        $this->assertArrayHasKey('target_circles', $casts);
        $this->assertArrayHasKey('tags', $casts);
        $this->assertArrayHasKey('settings', $casts);
        $this->assertEquals('array', $casts['target_circles']);
    }

    /**
     * Test event belongs to organizer relationship
     */
    public function test_belongs_to_organizer_relationship()
    {
        $organizer = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        $this->assertInstanceOf(User::class, $event->organizer);
        $this->assertEquals($organizer->id, $event->organizer->id);
    }

    /**
     * Test event belongs to institution relationship
     */
    public function test_belongs_to_institution_relationship()
    {
        $institution = Institution::factory()->create();
        $event = Event::factory()->create(['institution_id' => $institution->id]);

        $this->assertInstanceOf(Institution::class, $event->institution);
        $this->assertEquals($institution->id, $event->institution->id);
    }

    /**
     * Test event has many registrations relationship
     */
    public function test_has_many_registrations_relationship()
    {
        $event = Event::factory()->create();
        $registration = EventRegistration::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(EventRegistration::class, $event->registrations()->first());
        $this->assertEquals($registration->id, $event->registrations()->first()->id);
    }

    /**
     * Test event has many check-ins relationship
     */
    public function test_has_many_checkins_relationship()
    {
        $event = Event::factory()->create();
        $checkIn = EventCheckIn::factory()->create(['event_id' => $event->id]);

        $this->assertInstanceOf(EventCheckIn::class, $event->checkIns()->first());
        $this->assertEquals($checkIn->id, $event->checkIns()->first()->id);
    }

    /**
     * Test published scope filters events correctly
     */
    public function test_published_scope()
    {
        Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'draft']);
        Event::factory()->create(['status' => 'cancelled']);

        $publishedEvents = Event::published()->get();

        $this->assertCount(1, $publishedEvents);
        $this->assertEquals('published', $publishedEvents->first()->status);
    }

    /**
     * Test upcoming scope filters events correctly
     */
    public function test_upcoming_scope()
    {
        Event::factory()->create(['start_date' => now()->addDays(1)]);
        Event::factory()->create(['start_date' => now()->subDays(1)]);
        Event::factory()->create(['start_date' => now()->addWeeks(2)]);

        $upcomingEvents = Event::upcoming()->get();

        $this->assertCount(2, $upcomingEvents);
        foreach ($upcomingEvents as $event) {
            $this->assertTrue($event->start_date->isFuture());
        }
    }

    /**
     * Test past scope filters events correctly
     */
    public function test_past_scope()
    {
        Event::factory()->create(['end_date' => now()->subDays(1)]);
        Event::factory()->create(['end_date' => now()->addDays(1)]);
        Event::factory()->create(['end_date' => now()->subWeeks(2)]);

        $pastEvents = Event::past()->get();

        $this->assertCount(2, $pastEvents);
        foreach ($pastEvents as $event) {
            $this->assertTrue($event->end_date->isPast());
        }
    }

    /**
     * Test by type scope filters events correctly
     */
    public function test_by_type_scope()
    {
        Event::factory()->create(['type' => 'networking']);
        Event::factory()->create(['type' => 'career']);
        Event::factory()->create(['type' => 'social']);

        $networkingEvents = Event::byType('networking')->get();

        $this->assertCount(1, $networkingEvents);
        $this->assertEquals('networking', $networkingEvents->first()->type);
    }

    /**
     * Test by format scope filters events correctly
     */
    public function test_by_format_scope()
    {
        Event::factory()->create(['format' => 'virtual']);
        Event::factory()->create(['format' => 'in-person']);
        Event::factory()->create(['format' => 'hybrid']);

        $virtualEvents = Event::byFormat('virtual')->get();

        $this->assertCount(1, $virtualEvents);
        $this->assertEquals('virtual', $virtualEvents->first()->format);
    }

    /**
     * Test reunions scope filters events correctly
     */
    public function test_reunions_scope()
    {
        Event::factory()->create(['is_reunion' => true]);
        Event::factory()->create(['is_reunion' => false]);

        $reunionEvents = Event::reunions()->get();

        $this->assertCount(1, $reunionEvents);
        $this->assertTrue($reunionEvents->first()->is_reunion);
    }

    /**
     * Test is upcoming method
     */
    public function test_is_upcoming_method()
    {
        $upcomingEvent = Event::factory()->create(['start_date' => now()->addHours(2)]);
        $pastEvent = Event::factory()->create(['start_date' => now()->subHours(2)]);

        $this->assertTrue($upcomingEvent->isUpcoming());
        $this->assertFalse($pastEvent->isUpcoming());
    }

    /**
     * Test is past method
     */
    public function test_is_past_method()
    {
        $pastEvent = Event::factory()->create(['end_date' => now()->subHours(2)]);
        $ongoingEvent = Event::factory()->create(['end_date' => now()->addHours(2)]);

        $this->assertTrue($pastEvent->isPast());
        $this->assertFalse($ongoingEvent->isPast());
    }

    /**
     * Test is ongoing method
     */
    public function test_is_ongoing_method()
    {
        $ongoingEvent = Event::factory()->create([
            'start_date' => now()->subHours(1),
            'end_date' => now()->addHours(1)
        ]);
        $pastEvent = Event::factory()->create([
            'start_date' => now()->subHours(2),
            'end_date' => now()->subHours(1)
        ]);

        $this->assertTrue($ongoingEvent->isOngoing());
        $this->assertFalse($pastEvent->isOngoing());
    }

    /**
     * Test can register method with various conditions
     */
    public function test_can_register_method()
    {
        // Test open registration with capacity
        $openEvent = Event::factory()->create([
            'registration_status' => 'open',
            'max_capacity' => 100,
            'current_attendees' => 50,
            'registration_deadline' => now()->addDays(1)
        ]);

        $this->assertTrue($openEvent->canRegister());

        // Test closed registration
        $closedEvent = Event::factory()->create([
            'registration_status' => 'closed'
        ]);

        $this->assertFalse($closedEvent->canRegister());

        // Test full capacity
        $fullEvent = Event::factory()->create([
            'registration_status' => 'open',
            'max_capacity' => 10,
            'current_attendees' => 10
        ]);

        $this->assertFalse($fullEvent->canRegister());

        // Test past deadline
        $deadlineExpiredEvent = Event::factory()->create([
            'registration_status' => 'open',
            'registration_deadline' => now()->subHours(1)
        ]);

        $this->assertFalse($deadlineExpiredEvent->canRegister());
    }

    /**
     * Test has capacity method
     */
    public function test_has_capacity_method()
    {
        $eventWithCapacity = Event::factory()->create([
            'max_capacity' => 10,
            'current_attendees' => 5
        ]);

        $eventAtCapacity = Event::factory()->create([
            'max_capacity' => 10,
            'current_attendees' => 10
        ]);

        $eventUnlimitedCapacity = Event::factory()->create([
            'max_capacity' => null,
            'current_attendees' => 100
        ]);

        $this->assertTrue($eventWithCapacity->hasCapacity());
        $this->assertFalse($eventAtCapacity->hasCapacity());
        $this->assertTrue($eventUnlimitedCapacity->hasCapacity());
    }

    /**
     * Test get available spots method
     */
    public function test_get_available_spots_method()
    {
        $event = Event::factory()->create([
            'max_capacity' => 10,
            'current_attendees' => 7
        ]);

        $unlimitedEvent = Event::factory()->create(['max_capacity' => null]);

        $this->assertEquals(3, $event->getAvailableSpots());
        $this->assertEquals(PHP_INT_MAX, $unlimitedEvent->getAvailableSpots());
    }

    /**
     * Test is virtual method
     */
    public function test_is_virtual_method()
    {
        $virtualEvent = Event::factory()->create(['format' => 'virtual']);
        $inPersonEvent = Event::factory()->create(['format' => 'in-person']);
        $hybridEvent = Event::factory()->create(['format' => 'hybrid']);

        $this->assertTrue($virtualEvent->isVirtual());
        $this->assertFalse($inPersonEvent->isVirtual());
        $this->assertTrue($hybridEvent->isVirtual());
    }

    /**
     * Test has Jitsi meeting method
     */
    public function test_has_jitsi_meeting_method()
    {
        $jitsiEvent = Event::factory()->create([
            'meeting_platform' => 'jitsi',
            'jitsi_room_id' => 'test-room-123'
        ]);

        $nonJitsiEvent = Event::factory()->create([
            'meeting_platform' => 'zoom',
            'jitsi_room_id' => null
        ]);

        $this->assertTrue($jitsiEvent->hasJitsiMeeting());
        $this->assertFalse($nonJitsiEvent->hasJitsiMeeting());
    }

    /**
     * Test get Jitsi meeting URL method
     */
    public function test_get_jitsi_meeting_url_method()
    {
        $event = Event::factory()->create([
            'meeting_platform' => 'jitsi',
            'jitsi_room_id' => 'test-room-123'
        ]);

        config(['services.jitsi.domain' => 'meet.jit.si']);
        $url = $event->getJitsiMeetingUrl();

        $this->assertEquals('https://meet.jit.si/test-room-123', $url);
    }

    /**
     * Test get formatted duration method
     */
    public function test_get_formatted_duration_method()
    {
        $shortEvent = Event::factory()->create([
            'start_date' => now(),
            'end_date' => now()->addMinutes(45)
        ]);

        $longEvent = Event::factory()->create([
            'start_date' => now(),
            'end_date' => now()->addHours(2)->addMinutes(30)
        ]);

        $this->assertEquals('45 minutes', $shortEvent->getFormattedDuration());
        $this->assertEquals('2h 30m', $longEvent->getFormattedDuration());
    }

    /**
     * Test can embed meeting method
     */
    public function test_can_embed_meeting_method()
    {
        $embedAllowedEvent = Event::factory()->create([
            'meeting_platform' => 'jitsi',
            'meeting_embed_allowed' => true
        ]);

        $embedNotAllowedEvent = Event::factory()->create([
            'meeting_platform' => 'zoom',
            'meeting_embed_allowed' => false
        ]);

        $this->assertTrue($embedAllowedEvent->canEmbedMeeting());
        $this->assertFalse($embedNotAllowedEvent->canEmbedMeeting());
    }

    /**
     * Test generate Jitsi room ID method
     */
    public function test_generate_jitsi_room_id_method()
    {
        $event = Event::factory()->create([
            'title' => 'Test Event',
            'jitsi_room_id' => null
        ]);

        $roomId = $event->generateJitsiRoomId();

        $this->assertStringStartsWith('alumni-', $roomId);
        $this->assertStringContains('test-event', $roomId);

        // Test that room ID is saved to the event
        $this->assertEquals($roomId, $event->fresh()->jitsi_room_id);
    }

    /**
     * Test is reunion method
     */
    public function test_is_reunion_method()
    {
        $reunionEvent = Event::factory()->create(['is_reunion' => true]);
        $regularEvent = Event::factory()->create(['is_reunion' => false]);

        $this->assertTrue($reunionEvent->isReunion());
        $this->assertFalse($regularEvent->isReunion());
    }

    /**
     * Test get reunion years since graduation method
     */
    public function test_get_reunion_years_since_graduation_method()
    {
        $reunionEvent = Event::factory()->create([
            'graduation_year' => now()->year - 10
        ]);

        $this->assertEquals(10, $reunionEvent->getReunionYearsSinceGraduation());

        // Test null case
        $eventNoGraduationYear = Event::factory()->create([
            'graduation_year' => null
        ]);

        $this->assertNull($eventNoGraduationYear->getReunionYearsSinceGraduation());
    }

    /**
     * Test can user view method
     */
    public function test_can_user_view_method()
    {
        $publicEvent = Event::factory()->create(['visibility' => 'public']);
        $privateEvent = Event::factory()->create(['visibility' => 'private']);
        $organizer = User::factory()->create();
        $privateEvent->update(['organizer_id' => $organizer->id]);

        $user = User::factory()->create();

        // Public event is viewable by anyone
        $this->assertTrue($publicEvent->canUserView($user));

        // Private event is viewable only by organizer
        $this->assertFalse($privateEvent->canUserView($user));
        $this->assertTrue($privateEvent->refresh()->canUserView($organizer));
    }

    /**
     * Test can user edit method
     */
    public function test_can_user_edit_method()
    {
        $organizer = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);
        $otherUser = User::factory()->create();

        $this->assertTrue($event->canUserEdit($organizer));
        $this->assertFalse($event->canUserEdit($otherUser));
    }

    /**
     * Test update attendee count method
     */
    public function test_update_attendee_count_method()
    {
        $event = Event::factory()->create(['current_attendees' => 0]);

        // Create some registrations
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'registered',
            'guests_count' => 2
        ]);
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'registered',
            'guests_count' => 1
        ]);
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'cancelled',
            'guests_count' => 1
        ]);

        $event->updateAttendeeCount();

        // Should have 2 registered (each bringing 1 guest) + 2 guests = 4 attendees
        $this->assertEquals(4, $event->fresh()->current_attendees);
    }

    /**
     * Test get class display name method
     */
    public function test_get_class_display_name_method()
    {
        $eventWithClassIdentifier = Event::factory()->create([
            'class_identifier' => 'Class 2020',
            'graduation_year' => 2020
        ]);

        $eventWithGraduationYearOnly = Event::factory()->create([
            'class_identifier' => null,
            'graduation_year' => 2022
        ]);

        $genericEvent = Event::factory()->create([
            'class_identifier' => null,
            'graduation_year' => null
        ]);

        $this->assertEquals('Class 2020', $eventWithClassIdentifier->getClassDisplayName());
        $this->assertEquals('Class of 2022', $eventWithGraduationYearOnly->getClassDisplayName());
        $this->assertEquals('Alumni Class', $genericEvent->getClassDisplayName());
    }

    /**
     * Test committee member methods
     */
    public function test_committee_member_methods()
    {
        $organizer = User::factory()->create();
        $event = Event::factory()->create(['organizer_id' => $organizer->id]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Add committee members
        $event->addCommitteeMember($user1, 'Chair');
        $event->addCommitteeMember($user2, 'Secretary');

        $this->assertTrue($event->refresh()->isCommitteeMember($user1));
        $this->assertFalse($event->isCommitteeMember(User::factory()->create()));

        $this->assertEquals('Chair', $event->getCommitteeRole($user1));
        $this->assertEquals('Secretary', $event->getCommitteeRole($user2));

        // Remove member
        $event->removeCommitteeMember($user1);
        $this->assertFalse($event->fresh()->isCommitteeMember($user1));
    }

    /**
     * Test get reunion milestone display method
     */
    public function test_get_reunion_milestone_display_method()
    {
        $milestoneEvent = Event::factory()->create(['reunion_year_milestone' => 25]);
        $noMilestoneEvent = Event::factory()->create(['reunion_year_milestone' => null]);

        $this->assertEquals('25 Year Reunion', $milestoneEvent->getReunionMilestoneDisplay());
        $this->assertNull($noMilestoneEvent->getReunionMilestoneDisplay());
    }
}