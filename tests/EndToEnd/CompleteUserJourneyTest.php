<?php

namespace Tests\EndToEnd;

use App\Models\Circle;
use App\Models\Company;
use App\Models\Event;
use App\Models\Group;
use App\Models\Institution;
use App\Models\JobPosting;
use App\Models\MentorProfile;
use App\Models\Post;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompleteUserJourneyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $newAlumni;

    protected User $experiencedAlumni;

    protected User $mentor;

    protected Institution $institution;

    protected Circle $circle;

    protected Group $group;

    protected Event $event;

    protected JobPosting $job;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(['public', 'private']);

        // Set up institution and community
        $this->institution = Institution::factory()->create([
            'name' => 'Tech University',
        ]);

        $this->circle = Circle::factory()->create([
            'name' => 'Tech University Class of 2020-2022',
            'type' => 'school_year',
        ]);

        $this->group = Group::factory()->create([
            'name' => 'Computer Science Alumni',
            'type' => 'academic',
            'institution_id' => $this->institution->id,
        ]);

        // Create experienced alumni who will be mentor
        $this->mentor = User::factory()->create([
            'name' => 'Sarah Johnson',
            'bio' => 'Senior Software Engineer with 8 years of experience',
            'institution_id' => $this->institution->id,
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->mentor->id,
            'company' => 'Google',
            'title' => 'Senior Software Engineer',
            'is_current' => true,
        ]);

        MentorProfile::factory()->create([
            'user_id' => $this->mentor->id,
            'expertise_areas' => ['Software Development', 'Career Growth'],
            'is_active' => true,
        ]);

        // Create experienced alumni for networking
        $this->experiencedAlumni = User::factory()->create([
            'name' => 'Mike Chen',
            'bio' => 'Product Manager at innovative startup',
            'institution_id' => $this->institution->id,
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->experiencedAlumni->id,
            'company' => 'TechCorp',
            'title' => 'Senior Product Manager',
            'is_current' => true,
        ]);

        // Add experienced users to circles and groups
        $this->mentor->circles()->attach($this->circle->id);
        $this->mentor->groups()->attach($this->group->id);
        $this->experiencedAlumni->circles()->attach($this->circle->id);
        $this->experiencedAlumni->groups()->attach($this->group->id);

        // Create event
        $this->event = Event::factory()->create([
            'title' => 'Tech Alumni Networking Night',
            'organizer_id' => $this->mentor->id,
            'institution_id' => $this->institution->id,
            'start_date' => now()->addWeeks(2),
            'is_public' => true,
        ]);

        // Create job posting
        $company = Company::factory()->create(['name' => 'TechCorp']);
        $this->job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'title' => 'Junior Software Developer',
            'requirements' => ['JavaScript', 'React', 'Node.js'],
            'is_active' => true,
        ]);
    }

    public function test_complete_new_alumni_onboarding_and_engagement_journey()
    {
        // Step 1: New alumni registers and completes profile
        $this->newAlumni = User::factory()->create([
            'name' => 'Alex Rodriguez',
            'email' => 'alex@example.com',
            'institution_id' => $this->institution->id,
        ]);

        // Complete profile setup
        $profileData = [
            'bio' => 'Recent Computer Science graduate passionate about web development',
            'location' => 'San Francisco, CA',
            'skills' => ['JavaScript', 'React', 'Python', 'SQL'],
            'interests' => ['Web Development', 'Machine Learning', 'Startups'],
            'graduation_year' => 2022,
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
        ];

        $response = $this->actingAs($this->newAlumni)
            ->putJson('/api/profile', $profileData);

        $response->assertStatus(200);

        // Step 2: Automatic circle and group assignment
        $response = $this->actingAs($this->newAlumni)
            ->postJson('/api/circles/auto-join');

        $response->assertStatus(200);

        // Verify circle membership
        $this->assertTrue($this->newAlumni->circles()->where('circles.id', $this->circle->id)->exists());

        // Join relevant groups
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/groups/{$this->group->id}/join");

        $response->assertStatus(200);

        // Step 3: Explore alumni directory and make connections
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/alumni?skills[]=Software Development');

        $response->assertStatus(200);
        $alumni = $response->json('data');
        $this->assertGreaterThan(0, count($alumni));

        // Send connection request to experienced alumni
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/alumni/{$this->experiencedAlumni->id}/connect", [
                'message' => 'Hi Mike! I\'m a recent CS grad from Tech University. Would love to connect and learn from your experience!',
            ]);

        $response->assertStatus(201);

        // Experienced alumni accepts connection
        $connection = $this->experiencedAlumni->receivedConnectionRequests()->first();
        $response = $this->actingAs($this->experiencedAlumni)
            ->postJson("/api/connections/{$connection->id}/accept");

        $response->assertStatus(200);

        // Step 4: Create first post to introduce themselves
        $introPost = [
            'content' => 'Hi everyone! I\'m Alex, a recent CS graduate from Tech University. Excited to be part of this amazing alumni community! Looking forward to connecting with fellow alumni and learning from your experiences. #NewAlumni #TechUniversity',
            'post_type' => 'text',
            'visibility' => 'public',
        ];

        $response = $this->actingAs($this->newAlumni)
            ->postJson('/api/posts', $introPost);

        $response->assertStatus(201);

        // Step 5: Engage with community content
        // View timeline and engage with posts
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $posts = $response->json('data.posts.data');

        if (count($posts) > 0) {
            $firstPost = $posts[0];

            // Like the post
            $response = $this->actingAs($this->newAlumni)
                ->postJson("/api/posts/{$firstPost['id']}/engage", [
                    'type' => 'like',
                ]);

            $response->assertStatus(200);

            // Comment on the post
            $response = $this->actingAs($this->newAlumni)
                ->postJson("/api/posts/{$firstPost['id']}/engage", [
                    'type' => 'comment',
                    'metadata' => [
                        'comment' => 'Great insights! Thanks for sharing.',
                    ],
                ]);

            $response->assertStatus(200);
        }

        // Step 6: Discover and register for events
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/events?event_type=networking');

        $response->assertStatus(200);
        $events = $response->json('data.data');
        $this->assertGreaterThan(0, count($events));

        // Register for networking event
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/events/{$this->event->id}/register", [
                'attendance_type' => 'in_person',
                'special_requests' => 'First time attending, looking forward to meeting everyone!',
            ]);

        $response->assertStatus(200);

        // Step 7: Explore job opportunities
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/jobs/recommendations');

        $response->assertStatus(200);
        $jobs = $response->json('data.data');

        if (count($jobs) > 0) {
            $targetJob = collect($jobs)->firstWhere('title', 'Junior Software Developer');

            if ($targetJob) {
                // View job details
                $response = $this->actingAs($this->newAlumni)
                    ->getJson("/api/jobs/{$targetJob['id']}");

                $response->assertStatus(200);

                // Bookmark the job
                $response = $this->actingAs($this->newAlumni)
                    ->postJson("/api/jobs/{$targetJob['id']}/bookmark");

                $response->assertStatus(200);

                // Apply for the job
                $resume = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

                $response = $this->actingAs($this->newAlumni)
                    ->postJson("/api/jobs/{$targetJob['id']}/apply", [
                        'cover_letter' => 'I am excited about this opportunity to start my career in software development...',
                        'resume' => $resume,
                    ]);

                $response->assertStatus(200);
            }
        }

        // Step 8: Seek mentorship
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/mentorship/mentors?expertise=Software Development');

        $response->assertStatus(200);
        $mentors = $response->json('data');
        $this->assertGreaterThan(0, count($mentors));

        // Send mentorship request
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/mentorship/mentors/{$this->mentor->id}/request", [
                'message' => 'Hi Sarah! I\'m a recent graduate looking to grow my career in software development. Your experience at Google would be invaluable to learn from.',
                'goals' => 'I want to improve my technical skills, learn about career progression, and get guidance on job searching.',
                'topics_of_interest' => ['Technical Skills', 'Career Planning', 'Interview Preparation'],
            ]);

        $response->assertStatus(201);

        // Mentor accepts the request
        $mentorshipRequest = $this->mentor->mentorRequests()->first();
        $response = $this->actingAs($this->mentor)
            ->postJson("/api/mentorship/requests/{$mentorshipRequest->id}/accept", [
                'welcome_message' => 'Welcome Alex! I\'m excited to help you grow in your career.',
            ]);

        $response->assertStatus(200);

        // Step 9: Share career update after some progress
        $careerUpdatePost = [
            'content' => 'Excited to share that I just completed my first technical interview! Thanks to all the amazing mentors and connections in this community for the support and guidance. The journey continues! 🚀 #CareerGrowth #TechUniversity',
            'post_type' => 'career_update',
            'visibility' => 'public',
            'metadata' => [
                'career_update' => [
                    'type' => 'milestone',
                    'milestone' => 'First Technical Interview',
                ],
            ],
        ];

        $response = $this->actingAs($this->newAlumni)
            ->postJson('/api/posts', $careerUpdatePost);

        $response->assertStatus(201);

        // Step 10: Engage in mentorship session
        // Schedule first mentorship session
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/mentorship/{$mentorshipRequest->id}/sessions", [
                'scheduled_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'duration' => 60,
                'agenda' => 'Introduction and goal setting',
            ]);

        $response->assertStatus(201);

        // Step 11: Participate in event networking
        // Simulate event happening and check-in
        $this->event->update([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour(),
        ]);

        // Self check-in to event
        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/events/{$this->event->id}/self-check-in", [
                'qr_code' => $this->event->check_in_code,
            ]);

        $response->assertStatus(200);

        // Network with other attendees
        $response = $this->actingAs($this->newAlumni)
            ->getJson("/api/events/{$this->event->id}/attendees");

        $response->assertStatus(200);
        $attendees = $response->json('data');

        if (count($attendees) > 0) {
            $targetAttendee = $attendees[0];

            $response = $this->actingAs($this->newAlumni)
                ->postJson("/api/events/{$this->event->id}/network", [
                    'recipient_id' => $targetAttendee['id'],
                    'message' => 'Great meeting you at the event! Would love to stay connected.',
                ]);

            $response->assertStatus(200);
        }

        // Step 12: Share event experience
        $eventPhoto = UploadedFile::fake()->image('event-photo.jpg');

        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/events/{$this->event->id}/share", [
                'content' => 'Amazing networking event tonight! Met so many inspiring alumni. Grateful to be part of this community! 🎉',
                'media' => [$eventPhoto],
                'share_to_timeline' => true,
            ]);

        $response->assertStatus(200);

        // Step 13: Provide event feedback
        $this->event->update(['status' => 'completed']);

        $response = $this->actingAs($this->newAlumni)
            ->postJson("/api/events/{$this->event->id}/feedback", [
                'overall_rating' => 5,
                'content_rating' => 5,
                'organization_rating' => 4,
                'venue_rating' => 4,
                'comments' => 'Excellent event! Great opportunity to meet fellow alumni and build connections.',
                'would_recommend' => true,
            ]);

        $response->assertStatus(200);

        // Step 14: Check progress and analytics
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/profile/analytics');

        $response->assertStatus(200);
        $analytics = $response->json('data');

        // Should show engagement metrics
        $this->assertArrayHasKey('total_connections', $analytics);
        $this->assertArrayHasKey('posts_created', $analytics);
        $this->assertArrayHasKey('events_attended', $analytics);

        // Step 15: Verify complete journey success
        // Check that user has:
        // - Completed profile
        $this->newAlumni->refresh();
        $this->assertNotNull($this->newAlumni->bio);
        $this->assertNotEmpty($this->newAlumni->profile_data['skills'] ?? []);

        // - Made connections
        $this->assertGreaterThan(0, $this->newAlumni->connections()->count());

        // - Joined circles and groups
        $this->assertGreaterThan(0, $this->newAlumni->circles()->count());
        $this->assertGreaterThan(0, $this->newAlumni->groups()->count());

        // - Created posts
        $this->assertGreaterThan(0, $this->newAlumni->posts()->count());

        // - Applied for jobs
        $this->assertGreaterThan(0, $this->newAlumni->jobApplications()->count());

        // - Has active mentorship
        $this->assertGreaterThan(0, $this->newAlumni->menteeRequests()->where('status', 'accepted')->count());

        // - Attended events
        $this->assertGreaterThan(0, $this->newAlumni->eventRegistrations()->count());

        // Final verification: User should now be an active, engaged member of the community
        $response = $this->actingAs($this->newAlumni)
            ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $dashboard = $response->json('data');

        // Dashboard should show rich, personalized content
        $this->assertArrayHasKey('timeline_posts', $dashboard);
        $this->assertArrayHasKey('upcoming_events', $dashboard);
        $this->assertArrayHasKey('job_recommendations', $dashboard);
        $this->assertArrayHasKey('mentorship_updates', $dashboard);
        $this->assertArrayHasKey('connection_suggestions', $dashboard);

        // User journey complete - from new alumni to engaged community member!
    }

    public function test_experienced_alumni_giving_back_journey()
    {
        // This test covers the journey of an experienced alumni who becomes active in giving back

        // Step 1: Experienced alumni updates profile with current role
        $response = $this->actingAs($this->experiencedAlumni)
            ->putJson('/api/profile', [
                'bio' => 'Senior Product Manager with 6 years of experience in tech startups',
                'current_position' => [
                    'company' => 'TechCorp',
                    'title' => 'Senior Product Manager',
                    'start_date' => now()->subYears(2)->format('Y-m-d'),
                ],
            ]);

        $response->assertStatus(200);

        // Step 2: Becomes a mentor
        $response = $this->actingAs($this->experiencedAlumni)
            ->postJson('/api/mentorship/profile', [
                'expertise_areas' => ['Product Management', 'Career Transition', 'Startup Experience'],
                'industries' => ['Technology', 'Startups'],
                'mentoring_capacity' => 3,
                'session_duration' => 45,
            ]);

        $response->assertStatus(201);

        // Step 3: Organizes an event
        $response = $this->actingAs($this->experiencedAlumni)
            ->postJson('/api/events', [
                'title' => 'Product Management Career Workshop',
                'description' => 'Learn about transitioning into product management roles',
                'event_type' => 'workshop',
                'start_date' => now()->addMonth()->format('Y-m-d H:i:s'),
                'end_date' => now()->addMonth()->addHours(3)->format('Y-m-d H:i:s'),
                'location' => 'Tech University Campus',
                'max_attendees' => 50,
            ]);

        $response->assertStatus(201);

        // Step 4: Shares valuable content
        $response = $this->actingAs($this->experiencedAlumni)
            ->postJson('/api/posts', [
                'content' => '5 key lessons I learned transitioning from engineering to product management. Thread 🧵 1/6',
                'post_type' => 'career_advice',
                'visibility' => 'public',
            ]);

        $response->assertStatus(201);

        // Step 5: Accepts mentorship requests and provides guidance
        // This would be tested through the mentorship workflow

        // Verify the experienced alumni is now actively giving back
        $this->assertTrue($this->experiencedAlumni->isMentor());
        $this->assertGreaterThan(0, $this->experiencedAlumni->hostedEvents()->count());
        $this->assertGreaterThan(0, $this->experiencedAlumni->posts()->count());
    }

    public function test_cross_platform_integration_journey()
    {
        // Test how the platform integrates various features in a realistic user flow

        $user = User::factory()->create(['institution_id' => $this->institution->id]);

        // User discovers job through network connection
        $user->sendConnectionRequest($this->experiencedAlumni);
        $this->experiencedAlumni->acceptConnectionRequest(
            $this->experiencedAlumni->receivedConnectionRequests()->first()->id
        );

        // Connection shares job opportunity
        $jobPost = Post::factory()->create([
            'user_id' => $this->experiencedAlumni->id,
            'content' => 'We\'re hiring! Great opportunity for recent grads at TechCorp.',
            'post_type' => 'job_share',
            'visibility' => 'public',
            'metadata' => [
                'job_id' => $this->job->id,
            ],
        ]);

        // User sees post in timeline and applies
        $response = $this->actingAs($user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);

        // User applies for job through the post
        $response = $this->actingAs($user)
            ->postJson("/api/jobs/{$this->job->id}/apply", [
                'cover_letter' => 'Saw this opportunity through Mike\'s post. Very interested!',
                'referral_source' => 'alumni_network',
                'referring_user_id' => $this->experiencedAlumni->id,
            ]);

        $response->assertStatus(200);

        // User requests introduction through mutual connection
        $response = $this->actingAs($user)
            ->postJson("/api/jobs/{$this->job->id}/request-introduction", [
                'contact_id' => $this->experiencedAlumni->id,
                'message' => 'Could you help me get an introduction for this role?',
            ]);

        $response->assertStatus(200);

        // User shares success story after getting the job
        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'content' => 'Thrilled to announce I got the job at TechCorp! Huge thanks to the alumni network for the support and connections. This community is amazing! 🎉',
                'post_type' => 'career_update',
                'visibility' => 'public',
                'metadata' => [
                    'career_update' => [
                        'type' => 'new_job',
                        'company' => 'TechCorp',
                        'title' => 'Junior Software Developer',
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // Verify the integrated journey worked
        $this->assertDatabaseHas('job_applications', [
            'user_id' => $user->id,
            'job_id' => $this->job->id,
        ]);

        $this->assertDatabaseHas('introduction_requests', [
            'requester_id' => $user->id,
            'contact_id' => $this->experiencedAlumni->id,
            'job_id' => $this->job->id,
        ]);

        $this->assertGreaterThan(0, $user->posts()->where('post_type', 'career_update')->count());
    }
}
