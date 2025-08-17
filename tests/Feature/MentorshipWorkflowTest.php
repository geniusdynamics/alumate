<?php

namespace Tests\Feature;

use App\Models\MentorProfile;
use App\Models\MentorshipRequest;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MentorshipWorkflowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $mentor;

    protected User $mentee;

    protected User $anotherMentee;

    protected MentorProfile $mentorProfile;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mentor with experience
        $this->mentor = User::factory()->create([
            'name' => 'Sarah Johnson',
            'bio' => 'Senior Software Engineer with 8 years of experience in tech',
        ]);

        // Create mentor's work experience
        WorkExperience::factory()->create([
            'user_id' => $this->mentor->id,
            'company' => 'Google',
            'title' => 'Senior Software Engineer',
            'industry' => 'Technology',
            'is_current' => true,
            'start_date' => now()->subYears(3),
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->mentor->id,
            'company' => 'Microsoft',
            'title' => 'Software Engineer',
            'industry' => 'Technology',
            'is_current' => false,
            'start_date' => now()->subYears(6),
            'end_date' => now()->subYears(3),
        ]);

        // Create mentor profile
        $this->mentorProfile = MentorProfile::factory()->create([
            'user_id' => $this->mentor->id,
            'expertise_areas' => ['Software Development', 'Career Growth', 'Technical Leadership'],
            'industries' => ['Technology', 'Startups'],
            'mentoring_capacity' => 5,
            'session_duration' => 60,
            'availability' => [
                'monday' => ['09:00-12:00', '14:00-17:00'],
                'wednesday' => ['10:00-16:00'],
                'friday' => ['09:00-11:00'],
            ],
            'is_active' => true,
        ]);

        // Create mentees
        $this->mentee = User::factory()->create([
            'name' => 'Alex Chen',
            'bio' => 'Recent graduate looking to break into tech',
        ]);

        $this->anotherMentee = User::factory()->create([
            'name' => 'Jordan Smith',
            'bio' => 'Mid-level developer seeking career advancement',
        ]);
    }

    public function test_mentor_profile_creation_and_setup()
    {
        $newMentor = User::factory()->create();

        // Create mentor profile
        $profileData = [
            'bio' => 'Experienced product manager with expertise in fintech',
            'expertise_areas' => ['Product Management', 'Fintech', 'Strategy'],
            'industries' => ['Financial Services', 'Technology'],
            'mentoring_capacity' => 3,
            'session_duration' => 45,
            'hourly_rate' => null, // Free mentoring
            'availability' => [
                'tuesday' => ['14:00-18:00'],
                'thursday' => ['10:00-14:00'],
                'saturday' => ['09:00-12:00'],
            ],
            'mentoring_preferences' => [
                'communication_style' => 'structured',
                'session_format' => 'video_call',
                'focus_areas' => ['career_planning', 'skill_development'],
            ],
        ];

        $response = $this->actingAs($newMentor)
            ->postJson('/api/mentorship/profile', $profileData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Mentor profile created successfully',
            ]);

        // Verify profile was created
        $this->assertDatabaseHas('mentor_profiles', [
            'user_id' => $newMentor->id,
            'mentoring_capacity' => 3,
            'session_duration' => 45,
            'is_active' => true,
        ]);

        $profile = MentorProfile::where('user_id', $newMentor->id)->first();
        $this->assertEquals(['Product Management', 'Fintech', 'Strategy'], $profile->expertise_areas);
        $this->assertEquals(['Financial Services', 'Technology'], $profile->industries);
    }

    public function test_mentor_discovery_and_search()
    {
        // Create additional mentors for search testing
        $mentor2 = User::factory()->create();
        MentorProfile::factory()->create([
            'user_id' => $mentor2->id,
            'expertise_areas' => ['Marketing', 'Brand Management'],
            'industries' => ['Consumer Goods', 'Retail'],
            'is_active' => true,
        ]);

        $mentor3 = User::factory()->create();
        MentorProfile::factory()->create([
            'user_id' => $mentor3->id,
            'expertise_areas' => ['Data Science', 'Machine Learning'],
            'industries' => ['Technology', 'Healthcare'],
            'is_active' => true,
        ]);

        // Search all mentors
        $response = $this->actingAs($this->mentee)
            ->getJson('/api/mentorship/mentors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'avatar_url',
                        'bio',
                        'expertise_areas',
                        'industries',
                        'rating',
                        'total_mentees',
                        'availability_status',
                    ],
                ],
            ]);

        $mentors = $response->json('data');
        $this->assertCount(3, $mentors);

        // Search by expertise area
        $response = $this->actingAs($this->mentee)
            ->getJson('/api/mentorship/mentors?expertise=Software Development');

        $response->assertStatus(200);
        $mentors = $response->json('data');
        $this->assertCount(1, $mentors);
        $this->assertEquals($this->mentor->id, $mentors[0]['id']);

        // Search by industry
        $response = $this->actingAs($this->mentee)
            ->getJson('/api/mentorship/mentors?industry=Technology');

        $response->assertStatus(200);
        $mentors = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($mentors));

        // Search by availability
        $response = $this->actingAs($this->mentee)
            ->getJson('/api/mentorship/mentors?available=true');

        $response->assertStatus(200);
        $mentors = $response->json('data');

        foreach ($mentors as $mentor) {
            $this->assertEquals('available', $mentor['availability_status']);
        }
    }

    public function test_mentorship_request_workflow()
    {
        // Send mentorship request
        $requestData = [
            'message' => 'Hi Sarah! I\'m a recent graduate interested in breaking into tech. I\'d love to learn from your experience at Google and Microsoft.',
            'goals' => 'I want to improve my technical skills and learn about career progression in tech',
            'preferred_frequency' => 'bi_weekly',
            'preferred_duration' => 60,
            'topics_of_interest' => ['Technical Skills', 'Career Planning', 'Interview Preparation'],
        ];

        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/mentors/{$this->mentor->id}/request", $requestData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Mentorship request sent successfully',
            ]);

        // Verify request was created
        $this->assertDatabaseHas('mentorship_requests', [
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'pending',
        ]);

        $request = MentorshipRequest::where('mentor_id', $this->mentor->id)
            ->where('mentee_id', $this->mentee->id)
            ->first();

        $this->assertEquals($requestData['goals'], $request->goals);
        $this->assertEquals($requestData['topics_of_interest'], $request->topics_of_interest);

        // Test duplicate request prevention
        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/mentors/{$this->mentor->id}/request", $requestData);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You already have a pending or active mentorship request with this mentor',
            ]);
    }

    public function test_mentorship_request_acceptance_and_rejection()
    {
        // Create mentorship request
        $request = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'pending',
        ]);

        // Mentor views pending requests
        $response = $this->actingAs($this->mentor)
            ->getJson('/api/mentorship/requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'mentee',
                        'message',
                        'goals',
                        'topics_of_interest',
                        'created_at',
                        'status',
                    ],
                ],
            ]);

        $requests = $response->json('data');
        $this->assertCount(1, $requests);

        // Accept mentorship request
        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/requests/{$request->id}/accept", [
                'welcome_message' => 'Welcome! I\'m excited to work with you. Let\'s schedule our first session.',
                'mentorship_agreement' => [
                    'session_frequency' => 'bi_weekly',
                    'session_duration' => 60,
                    'communication_channels' => ['video_call', 'email'],
                    'goals' => 'Focus on technical skills and career development',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Mentorship request accepted',
            ]);

        // Verify request status updated
        $request->refresh();
        $this->assertEquals('accepted', $request->status);
        $this->assertNotNull($request->accepted_at);

        // Test rejection workflow with another mentee
        $rejectionRequest = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->anotherMentee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/requests/{$rejectionRequest->id}/reject", [
                'reason' => 'Currently at capacity with mentees',
                'feedback' => 'Your background looks great! I recommend reaching out to mentors specializing in your specific area of interest.',
            ]);

        $response->assertStatus(200);

        $rejectionRequest->refresh();
        $this->assertEquals('rejected', $rejectionRequest->status);
    }

    public function test_mentorship_session_scheduling()
    {
        // Create accepted mentorship
        $mentorship = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'accepted',
        ]);

        // Schedule session
        $sessionData = [
            'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'duration' => 60,
            'session_type' => 'video_call',
            'agenda' => 'Introduction and goal setting',
            'notes' => 'First session to get to know each other and set expectations',
        ];

        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/{$mentorship->id}/sessions", $sessionData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Session scheduled successfully',
            ]);

        // Verify session was created
        $this->assertDatabaseHas('mentorship_sessions', [
            'mentorship_request_id' => $mentorship->id,
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'scheduled',
        ]);

        $session = MentorshipSession::where('mentorship_request_id', $mentorship->id)->first();
        $this->assertEquals($sessionData['agenda'], $session->agenda);

        // Test session confirmation by mentor
        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/sessions/{$session->id}/confirm");

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('confirmed', $session->status);
    }

    public function test_mentorship_session_management()
    {
        // Create confirmed session
        $session = MentorshipSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'scheduled_at' => now()->addHour(),
            'status' => 'confirmed',
        ]);

        // Start session
        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/sessions/{$session->id}/start");

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('in_progress', $session->status);
        $this->assertNotNull($session->started_at);

        // Complete session with notes
        $completionData = [
            'mentor_notes' => 'Great first session. Alex is motivated and has clear goals.',
            'mentee_notes' => 'Very helpful session. Got great advice on technical skills to focus on.',
            'action_items' => [
                'Complete online course on data structures',
                'Practice coding problems daily',
                'Update LinkedIn profile',
            ],
            'next_session_topics' => ['Resume review', 'Interview preparation'],
        ];

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/sessions/{$session->id}/complete", $completionData);

        $response->assertStatus(200);

        $session->refresh();
        $this->assertEquals('completed', $session->status);
        $this->assertNotNull($session->completed_at);
        $this->assertEquals($completionData['action_items'], $session->action_items);

        // Test session rescheduling
        $futureSession = MentorshipSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'scheduled_at' => now()->addWeek(),
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->mentee)
            ->putJson("/api/mentorship/sessions/{$futureSession->id}/reschedule", [
                'new_scheduled_at' => now()->addWeek()->addDays(2)->format('Y-m-d H:i:s'),
                'reason' => 'Schedule conflict came up',
            ]);

        $response->assertStatus(200);

        $futureSession->refresh();
        $this->assertEquals('rescheduled', $futureSession->status);
    }

    public function test_mentorship_progress_tracking()
    {
        // Create mentorship with multiple completed sessions
        $mentorship = MentorshipRequest::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'accepted',
        ]);

        // Create completed sessions
        $sessions = MentorshipSession::factory()->count(3)->create([
            'mentorship_request_id' => $mentorship->id,
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'completed',
            'completed_at' => now()->subWeeks(rand(1, 4)),
        ]);

        // Get mentorship progress
        $response = $this->actingAs($this->mentee)
            ->getJson("/api/mentorship/{$mentorship->id}/progress");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'mentorship_id',
                    'total_sessions',
                    'completed_sessions',
                    'upcoming_sessions',
                    'progress_percentage',
                    'goals_progress',
                    'recent_sessions',
                    'action_items',
                ],
            ]);

        $progress = $response->json('data');
        $this->assertEquals(3, $progress['completed_sessions']);
        $this->assertGreaterThan(0, $progress['progress_percentage']);

        // Update mentorship goals
        $response = $this->actingAs($this->mentee)
            ->putJson("/api/mentorship/{$mentorship->id}/goals", [
                'goals' => [
                    [
                        'title' => 'Learn React fundamentals',
                        'description' => 'Complete React course and build a project',
                        'target_date' => now()->addMonth()->format('Y-m-d'),
                        'status' => 'in_progress',
                    ],
                    [
                        'title' => 'Improve interview skills',
                        'description' => 'Practice technical interviews',
                        'target_date' => now()->addWeeks(6)->format('Y-m-d'),
                        'status' => 'not_started',
                    ],
                ],
            ]);

        $response->assertStatus(200);

        // Verify goals were updated
        $mentorship->refresh();
        $this->assertCount(2, $mentorship->goals);
    }

    public function test_mentorship_feedback_and_rating_system()
    {
        // Create completed session
        $session = MentorshipSession::factory()->create([
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'completed',
            'completed_at' => now()->subDays(1),
        ]);

        // Mentee provides feedback on session
        $feedbackData = [
            'rating' => 5,
            'feedback' => 'Excellent session! Sarah provided great insights and actionable advice.',
            'helpful_aspects' => ['Clear explanations', 'Practical examples', 'Encouraging attitude'],
            'improvement_suggestions' => 'Maybe provide more resources for follow-up learning',
        ];

        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/sessions/{$session->id}/feedback", $feedbackData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Feedback submitted successfully',
            ]);

        // Verify feedback was stored
        $this->assertDatabaseHas('session_feedback', [
            'session_id' => $session->id,
            'feedback_by' => $this->mentee->id,
            'rating' => 5,
        ]);

        // Mentor provides feedback on mentee
        $mentorFeedbackData = [
            'rating' => 4,
            'feedback' => 'Alex is engaged and asks great questions. Shows good progress.',
            'mentee_strengths' => ['Quick learner', 'Prepared for sessions', 'Takes initiative'],
            'areas_for_improvement' => 'Could benefit from more hands-on practice',
        ];

        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/sessions/{$session->id}/feedback", $mentorFeedbackData);

        $response->assertStatus(200);

        // Get mentor's overall rating
        $response = $this->actingAs($this->mentee)
            ->getJson("/api/mentorship/mentors/{$this->mentor->id}");

        $response->assertStatus(200);
        $mentorData = $response->json('data');
        $this->assertArrayHasKey('rating', $mentorData);
        $this->assertArrayHasKey('total_reviews', $mentorData);
    }

    public function test_mentorship_capacity_management()
    {
        // Set mentor capacity to 2
        $this->mentorProfile->update(['mentoring_capacity' => 2]);

        // Create 2 active mentorships
        MentorshipRequest::factory()->count(2)->create([
            'mentor_id' => $this->mentor->id,
            'status' => 'accepted',
        ]);

        // Try to send another request (should be waitlisted or rejected)
        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/mentors/{$this->mentor->id}/request", [
                'message' => 'Would love to work with you!',
                'goals' => 'Career development',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Mentor is currently at full capacity',
            ]);

        // Test waitlist functionality
        $response = $this->actingAs($this->mentee)
            ->postJson("/api/mentorship/mentors/{$this->mentor->id}/request", [
                'message' => 'Would love to work with you!',
                'goals' => 'Career development',
                'join_waitlist' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Added to mentor\'s waitlist',
            ]);

        // Verify waitlist entry
        $this->assertDatabaseHas('mentorship_requests', [
            'mentor_id' => $this->mentor->id,
            'mentee_id' => $this->mentee->id,
            'status' => 'waitlisted',
        ]);
    }

    public function test_mentorship_analytics_and_insights()
    {
        // Create multiple mentorships and sessions for analytics
        $mentorships = MentorshipRequest::factory()->count(3)->create([
            'mentor_id' => $this->mentor->id,
            'status' => 'accepted',
        ]);

        foreach ($mentorships as $mentorship) {
            MentorshipSession::factory()->count(rand(2, 5))->create([
                'mentorship_request_id' => $mentorship->id,
                'mentor_id' => $this->mentor->id,
                'mentee_id' => $mentorship->mentee_id,
                'status' => 'completed',
            ]);
        }

        // Get mentor analytics
        $response = $this->actingAs($this->mentor)
            ->getJson('/api/mentorship/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_mentees',
                    'active_mentorships',
                    'completed_sessions',
                    'average_rating',
                    'session_completion_rate',
                    'mentee_success_stories',
                    'monthly_stats',
                ],
            ]);

        $analytics = $response->json('data');
        $this->assertEquals(3, $analytics['active_mentorships']);
        $this->assertGreaterThan(0, $analytics['completed_sessions']);

        // Get mentee analytics
        $response = $this->actingAs($this->mentee)
            ->getJson('/api/mentorship/my-analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'current_mentors',
                    'total_sessions',
                    'goals_achieved',
                    'skills_developed',
                    'career_progress',
                ],
            ]);
    }
}
