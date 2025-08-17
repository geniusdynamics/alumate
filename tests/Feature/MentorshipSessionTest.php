<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipSessionTest extends TestCase
{
    use RefreshDatabase;

    private User $mentor;

    private User $mentee;

    private MentorshipRequest $mentorship;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->mentor = User::factory()->create();
        $this->mentee = User::factory()->create();

        // Create mentor profile
        MentorProfile::factory()->create([
            'user_id' => $this->mentor->id,
            'is_active' => true,
        ]);

        // Create accepted mentorship
        $this->mentorship = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'accepted',
        ]);
    }

    public function test_mentor_can_schedule_session()
    {
        $this->actingAs($this->mentor);

        $sessionData = [
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
            'notes' => 'Career discussion session',
        ];

        $response = $this->postJson('/api/mentorships/sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'session' => [
                    'id',
                    'mentorship_id',
                    'scheduled_at',
                    'duration',
                    'notes',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('mentorship_sessions', [
            'mentorship_id' => $this->mentorship->id,
            'duration' => 60,
            'notes' => 'Career discussion session',
            'status' => 'scheduled',
        ]);
    }

    public function test_mentee_can_schedule_session()
    {
        $this->actingAs($this->mentee);

        $sessionData = [
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 45,
        ];

        $response = $this->postJson('/api/mentorships/sessions', $sessionData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('mentorship_sessions', [
            'mentorship_id' => $this->mentorship->id,
            'duration' => 45,
            'status' => 'scheduled',
        ]);
    }

    public function test_unauthorized_user_cannot_schedule_session()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $sessionData = [
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
        ];

        $response = $this->postJson('/api/mentorships/sessions', $sessionData);

        $response->assertStatus(403);
    }

    public function test_cannot_schedule_session_for_non_accepted_mentorship()
    {
        $pendingMentorship = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->mentor);

        $sessionData = [
            'mentorship_id' => $pendingMentorship->id,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
        ];

        $response = $this->postJson('/api/mentorships/sessions', $sessionData);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Can only schedule sessions for accepted mentorships.',
            ]);
    }

    public function test_cannot_schedule_session_in_past()
    {
        $this->actingAs($this->mentor);

        $sessionData = [
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->subHours(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
        ];

        $response = $this->postJson('/api/mentorships/sessions', $sessionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_can_get_upcoming_sessions()
    {
        // Create some sessions
        MentorshipSession::factory()->create([
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(1),
            'status' => 'scheduled',
        ]);

        MentorshipSession::factory()->create([
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(3),
            'status' => 'scheduled',
        ]);

        // Past session (should not be included)
        MentorshipSession::factory()->create([
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->subDays(1),
            'status' => 'completed',
        ]);

        $this->actingAs($this->mentor);

        $response = $this->getJson('/api/mentorships/sessions/upcoming');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'sessions' => [
                    '*' => [
                        'id',
                        'scheduled_at',
                        'duration',
                        'status',
                        'mentorship' => [
                            'mentor',
                            'mentee',
                        ],
                    ],
                ],
            ]);

        $sessions = $response->json('sessions');
        $this->assertCount(2, $sessions);
    }

    public function test_can_complete_session()
    {
        $this->setUpTestData();
        $session = MentorshipSession::factory()->create([
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->subHours(1), // Past session
            'status' => 'scheduled',
        ]);

        $this->actingAs($this->mentor);

        $completionData = [
            'notes' => 'Great discussion about career goals',
            'rating' => 5,
            'feedback' => 'Very productive session',
        ];

        $response = $this->postJson("/api/mentorships/sessions/{$session->id}/complete", $completionData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Session completed successfully',
            ]);

        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertEquals('Great discussion about career goals', $session->notes);
        $this->assertArrayHasKey('mentor', $session->feedback);
        $this->assertEquals(5, $session->feedback['mentor']['rating']);
    }

    public function test_validation_rules_for_session_scheduling()
    {
        $this->setUpTestData();
        $this->actingAs($this->mentor);

        // Test missing required fields
        $response = $this->postJson('/api/mentorships/sessions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mentorship_id', 'scheduled_at']);

        // Test invalid duration
        $response = $this->postJson('/api/mentorships/sessions', [
            'mentorship_id' => $this->mentorship->id,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 200, // Too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['duration']);

        // Test invalid mentorship_id
        $response = $this->postJson('/api/mentorships/sessions', [
            'mentorship_id' => 99999,
            'scheduled_at' => now()->addDays(1)->format('Y-m-d H:i:s'),
            'duration' => 60,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['mentorship_id']);
    }
}
