<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCheckIn;
use App\Models\EventConnectionRecommendation;
use App\Models\EventFeedback;
use App\Models\EventHighlight;
use App\Models\EventNetworkingConnection;
use App\Models\EventRegistration;
use App\Models\User;
use App\Services\EventFollowUpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFollowUpTest extends TestCase
{
    use RefreshDatabase;

    protected EventFollowUpService $followUpService;

    protected User $user;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->followUpService = app(EventFollowUpService::class);
        $this->user = User::factory()->create();
        $this->event = Event::factory()->create();

        // Register and check in the user
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'status' => 'registered',
        ]);

        EventCheckIn::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_submit_feedback()
    {
        $feedbackData = [
            'overall_rating' => 5,
            'content_rating' => 4,
            'organization_rating' => 5,
            'networking_rating' => 4,
            'venue_rating' => 5,
            'feedback_text' => 'Great event! Really enjoyed the networking opportunities.',
            'would_recommend' => true,
            'would_attend_again' => true,
            'improvement_suggestions' => ['More time for networking', 'Better audio system'],
            'is_anonymous' => false,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/events/{$this->event->id}/feedback", $feedbackData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_feedback', [
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'overall_rating' => 5,
            'would_recommend' => true,
        ]);
    }

    public function test_user_cannot_submit_feedback_twice()
    {
        // Submit feedback first time
        EventFeedback::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
        ]);

        $feedbackData = [
            'overall_rating' => 4,
            'feedback_text' => 'Second feedback attempt',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/events/{$this->event->id}/feedback", $feedbackData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Feedback has already been submitted for this event.']);
    }

    public function test_user_can_create_highlight()
    {
        $highlightData = [
            'type' => 'photo',
            'title' => 'Great networking session',
            'description' => 'Amazing conversations with fellow alumni',
            'media_urls' => ['https://example.com/photo1.jpg'],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/events/{$this->event->id}/highlights", $highlightData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_highlights', [
            'event_id' => $this->event->id,
            'created_by' => $this->user->id,
            'type' => 'photo',
            'title' => 'Great networking session',
        ]);
    }

    public function test_user_can_get_event_highlights()
    {
        EventHighlight::factory()->count(3)->create([
            'event_id' => $this->event->id,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/{$this->event->id}/highlights");

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function test_user_can_like_highlight()
    {
        $highlight = EventHighlight::factory()->create([
            'event_id' => $this->event->id,
            'is_approved' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/highlights/{$highlight->id}/interact", [
                'type' => 'like',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_highlight_interactions', [
            'highlight_id' => $highlight->id,
            'user_id' => $this->user->id,
            'type' => 'like',
        ]);
    }

    public function test_user_can_create_networking_connection()
    {
        $otherUser = User::factory()->create();

        // Register and check in the other user
        EventRegistration::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $otherUser->id,
            'status' => 'registered',
        ]);

        EventCheckIn::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $otherUser->id,
        ]);

        $connectionData = [
            'connected_user_id' => $otherUser->id,
            'connection_type' => 'met_at_event',
            'connection_note' => 'Great conversation about AI trends',
            'shared_interests' => ['Technology', 'Innovation'],
            'follow_up_requested' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/events/{$this->event->id}/connections", $connectionData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('event_networking_connections', [
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'connected_user_id' => $otherUser->id,
            'connection_type' => 'met_at_event',
        ]);
    }

    public function test_user_can_generate_connection_recommendations()
    {
        // Create other attendees
        $attendees = User::factory()->count(3)->create();

        foreach ($attendees as $attendee) {
            EventRegistration::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $attendee->id,
                'status' => 'registered',
            ]);

            EventCheckIn::factory()->create([
                'event_id' => $this->event->id,
                'user_id' => $attendee->id,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->postJson("/api/events/{$this->event->id}/generate-recommendations");

        $response->assertStatus(200);

        // Should have created some recommendations
        $this->assertTrue(
            EventConnectionRecommendation::where('event_id', $this->event->id)
                ->where('user_id', $this->user->id)
                ->exists()
        );
    }

    public function test_user_can_get_connection_recommendations()
    {
        $otherUser = User::factory()->create();

        EventConnectionRecommendation::factory()->create([
            'event_id' => $this->event->id,
            'user_id' => $this->user->id,
            'recommended_user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/{$this->event->id}/recommendations");

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_feedback_analytics_calculation()
    {
        // Create various feedback entries
        EventFeedback::factory()->count(3)->create([
            'event_id' => $this->event->id,
            'overall_rating' => 5,
            'would_recommend' => true,
        ]);

        EventFeedback::factory()->count(2)->create([
            'event_id' => $this->event->id,
            'overall_rating' => 3,
            'would_recommend' => false,
        ]);

        $analytics = $this->followUpService->getEventFeedbackAnalytics($this->event);

        $this->assertEquals(5, $analytics['total_responses']);
        $this->assertEquals(4.0, $analytics['average_rating']);
        $this->assertEquals(60.0, $analytics['recommendation_rate']);
    }

    public function test_follow_up_analytics_calculation()
    {
        // Create some follow-up activities
        EventFeedback::factory()->count(2)->create(['event_id' => $this->event->id]);
        EventHighlight::factory()->count(3)->create(['event_id' => $this->event->id]);
        EventNetworkingConnection::factory()->count(1)->create(['event_id' => $this->event->id]);

        $analytics = $this->followUpService->getFollowUpAnalytics($this->event);

        $this->assertArrayHasKey('total_attendees', $analytics);
        $this->assertArrayHasKey('engagement_score', $analytics);
        $this->assertArrayHasKey('total_connections', $analytics);
        $this->assertArrayHasKey('total_highlights', $analytics);
    }

    public function test_non_attendee_cannot_submit_feedback()
    {
        $nonAttendee = User::factory()->create();

        $feedbackData = [
            'overall_rating' => 5,
            'feedback_text' => 'Great event!',
        ];

        $response = $this->actingAs($nonAttendee)
            ->postJson("/api/events/{$this->event->id}/feedback", $feedbackData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Only attendees can provide feedback.']);
    }

    public function test_non_attendee_cannot_create_highlight()
    {
        $nonAttendee = User::factory()->create();

        $highlightData = [
            'type' => 'photo',
            'title' => 'Great event',
            'description' => 'Amazing experience',
        ];

        $response = $this->actingAs($nonAttendee)
            ->postJson("/api/events/{$this->event->id}/highlights", $highlightData);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Only attendees can create highlights.']);
    }
}
