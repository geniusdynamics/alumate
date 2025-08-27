<?php

namespace Tests\EndToEnd;

use App\Models\Circle;
use App\Models\Company;
use App\Models\Connection;
use App\Models\Event;
use App\Models\Group;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $alumnus;

    protected User $mentor;

    protected User $employer;

    protected Circle $circle;

    protected Group $group;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different roles
        $this->alumnus = User::factory()->create([
            'name' => 'John Alumni',
            'email' => 'john@alumni.com',
        ]);

        $this->mentor = User::factory()->create([
            'name' => 'Jane Mentor',
            'email' => 'jane@mentor.com',
        ]);

        $this->employer = User::factory()->create([
            'name' => 'Bob Employer',
            'email' => 'bob@employer.com',
        ]);

        // Create social structures
        $this->circle = Circle::factory()->create([
            'name' => 'Computer Science 2020',
            'type' => 'school_year',
        ]);

        $this->group = Group::factory()->create([
            'name' => 'Tech Professionals',
            'type' => 'professional',
        ]);

        $this->company = Company::factory()->create([
            'name' => 'TechCorp Inc',
            'industry' => 'Technology',
        ]);

        // Add users to circles and groups
        $this->alumnus->circles()->attach($this->circle->id);
        $this->mentor->circles()->attach($this->circle->id);

        $this->alumnus->groups()->attach($this->group->id);
        $this->mentor->groups()->attach($this->group->id);
    }

    public function test_complete_alumni_platform_user_journey()
    {
        Storage::fake('public');

        // Step 1: New alumnus logs in and completes profile
        $this->actingAs($this->alumnus);

        $profileData = [
            'bio' => 'Software engineer passionate about web development',
            'location' => 'San Francisco, CA',
            'skills' => ['PHP', 'Laravel', 'Vue.js', 'JavaScript'],
            'website' => 'https://johnalumni.dev',
        ];

        $response = $this->putJson("/api/users/{$this->alumnus->id}/profile", $profileData);
        $response->assertStatus(200);

        // Step 2: Alumnus creates their first post
        $postData = [
            'content' => 'Hello everyone! Excited to be part of this alumni community. Looking forward to connecting with fellow graduates!',
            'post_type' => 'text',
            'visibility' => 'circles',
            'circle_ids' => [$this->circle->id],
        ];

        $response = $this->postJson('/api/posts', $postData);
        $response->assertStatus(201);

        $firstPost = Post::where('user_id', $this->alumnus->id)->first();
        $this->assertNotNull($firstPost);

        // Step 3: Mentor sees the post and engages with it
        $this->actingAs($this->mentor);

        $response = $this->postJson("/api/posts/{$firstPost->id}/engage", [
            'type' => 'like',
        ]);
        $response->assertStatus(200);

        $response = $this->postJson("/api/posts/{$firstPost->id}/engage", [
            'type' => 'comment',
            'metadata' => [
                'comment' => 'Welcome to the community! Great to have you here.',
            ],
        ]);
        $response->assertStatus(200);

        // Step 4: Alumnus discovers mentor through engagement and sends connection request
        $this->actingAs($this->alumnus);

        $response = $this->postJson("/api/users/{$this->mentor->id}/connect", [
            'message' => 'Hi Jane! Thanks for the welcome. I\'d love to connect and learn from your experience.',
        ]);
        $response->assertStatus(201);

        // Step 5: Mentor accepts connection request
        $this->actingAs($this->mentor);

        $connection = Connection::where('user_id', $this->alumnus->id)
            ->where('connected_user_id', $this->mentor->id)
            ->first();

        $response = $this->putJson("/api/connections/{$connection->id}", [
            'status' => 'accepted',
        ]);
        $response->assertStatus(200);

        // Step 6: Alumnus shares a career update
        $this->actingAs($this->alumnus);

        $careerUpdateData = [
            'content' => 'Excited to share that I just completed my Laravel certification! Thanks to everyone who supported me on this journey.',
            'post_type' => 'achievement',
            'visibility' => 'public',
            'metadata' => [
                'achievement' => [
                    'type' => 'certification',
                    'title' => 'Laravel Certified Developer',
                    'organization' => 'Laravel',
                ],
            ],
        ];

        $response = $this->postJson('/api/posts', $careerUpdateData);
        $response->assertStatus(201);

        // Step 7: Alumnus browses job opportunities
        $job = JobPosting::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Senior Laravel Developer',
            'description' => 'We are looking for an experienced Laravel developer...',
            'required_skills' => ['PHP', 'Laravel', 'Vue.js'],
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/jobs/recommendations');
        $response->assertStatus(200);

        // Step 8: Alumnus applies for a job
        $resume = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->postJson("/api/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am very interested in this position and believe my Laravel certification and experience make me a great fit.',
            'resume' => $resume,
        ]);
        $response->assertStatus(200);

        // Step 9: Alumnus joins a professional group discussion
        $response = $this->postJson('/api/posts', [
            'content' => 'What are the best practices for Laravel testing? I\'m working on improving my test coverage.',
            'post_type' => 'text',
            'visibility' => 'groups',
            'group_ids' => [$this->group->id],
        ]);
        $response->assertStatus(201);

        // Step 10: Mentor responds to the discussion
        $this->actingAs($this->mentor);

        $discussionPost = Post::where('user_id', $this->alumnus->id)
            ->where('visibility', 'groups')
            ->first();

        $response = $this->postJson("/api/posts/{$discussionPost->id}/engage", [
            'type' => 'comment',
            'metadata' => [
                'comment' => 'Great question! I recommend starting with Feature tests for your main user flows, then Unit tests for your services and models. Pest PHP is also worth checking out!',
            ],
        ]);
        $response->assertStatus(200);

        // Step 11: Alumnus schedules a mentorship session
        $response = $this->postJson('/api/mentorship/sessions', [
            'mentor_id' => $this->mentor->id,
            'topic' => 'Career Development and Laravel Best Practices',
            'preferred_date' => now()->addWeek()->format('Y-m-d'),
            'duration' => 60,
            'message' => 'I would love to discuss career growth opportunities and get advice on Laravel development.',
        ]);
        $response->assertStatus(201);

        // Step 12: Alumnus discovers and registers for an event
        $event = Event::factory()->create([
            'title' => 'Alumni Tech Meetup',
            'description' => 'Monthly meetup for tech alumni to network and share experiences',
            'event_date' => now()->addDays(14),
            'location' => 'San Francisco, CA',
            'is_virtual' => false,
        ]);

        $response = $this->postJson("/api/events/{$event->id}/register", [
            'dietary_requirements' => 'Vegetarian',
            'networking_interests' => ['Web Development', 'Career Growth'],
        ]);
        $response->assertStatus(201);

        // Step 13: Alumnus shares event experience
        $response = $this->postJson('/api/posts', [
            'content' => 'Had an amazing time at the Alumni Tech Meetup! Met so many inspiring people and learned about exciting opportunities in the industry.',
            'post_type' => 'text',
            'visibility' => 'public',
            'metadata' => [
                'event_id' => $event->id,
            ],
        ]);
        $response->assertStatus(201);

        // Step 14: Alumnus updates their career timeline
        $response = $this->postJson('/api/career', [
            'company' => 'TechCorp Inc',
            'title' => 'Senior Laravel Developer',
            'start_date' => now()->format('Y-m-d'),
            'is_current' => true,
            'description' => 'Leading development of web applications using Laravel and Vue.js',
            'achievements' => ['Implemented new feature that increased user engagement by 25%'],
            'location' => 'San Francisco, CA',
            'employment_type' => 'full-time',
        ]);
        $response->assertStatus(201);

        // Step 15: Alumnus creates a success story
        $response = $this->postJson('/api/success-stories', [
            'title' => 'From Bootcamp Graduate to Senior Developer',
            'content' => 'My journey from completing a coding bootcamp to landing my dream job as a Senior Laravel Developer...',
            'category' => 'career_growth',
            'is_featured' => false,
            'tags' => ['career', 'laravel', 'bootcamp', 'success'],
            'media_urls' => [],
        ]);
        $response->assertStatus(201);

        // Step 16: Alumnus searches for other alumni
        $response = $this->getJson('/api/alumni?skills[]=Laravel&location=San Francisco');
        $response->assertStatus(200);

        $alumni = $response->json('data');
        $this->assertNotEmpty($alumni);

        // Step 17: Alumnus saves a search for future use
        $response = $this->postJson('/api/search/save', [
            'name' => 'Laravel Developers in SF',
            'type' => 'alumni',
            'criteria' => [
                'skills' => ['Laravel'],
                'location' => 'San Francisco',
            ],
            'alert_frequency' => 'weekly',
        ]);
        $response->assertStatus(200);

        // Step 18: Alumnus views their dashboard with activity summary
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'recent_posts',
                    'connection_suggestions',
                    'upcoming_events',
                    'job_recommendations',
                    'activity_summary',
                ],
            ]);

        // Step 19: Alumnus checks their notifications
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(200);

        // Step 20: Verify the complete journey created expected data
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->alumnus->id,
            'post_type' => 'text',
        ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->alumnus->id,
            'post_type' => 'achievement',
        ]);

        $this->assertDatabaseHas('connections', [
            'user_id' => $this->alumnus->id,
            'connected_user_id' => $this->mentor->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('job_applications', [
            'user_id' => $this->alumnus->id,
            'job_id' => $job->id,
        ]);

        $this->assertDatabaseHas('event_registrations', [
            'user_id' => $this->alumnus->id,
            'event_id' => $event->id,
        ]);

        $this->assertDatabaseHas('career_timelines', [
            'user_id' => $this->alumnus->id,
            'company' => 'TechCorp Inc',
            'is_current' => true,
        ]);

        $this->assertDatabaseHas('success_stories', [
            'user_id' => $this->alumnus->id,
            'title' => 'From Bootcamp Graduate to Senior Developer',
        ]);

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->alumnus->id,
            'name' => 'Laravel Developers in SF',
        ]);
    }

    public function test_employer_journey_posting_job_and_finding_candidates()
    {
        $this->actingAs($this->employer);

        // Step 1: Employer creates company profile
        $response = $this->putJson("/api/companies/{$this->company->id}", [
            'description' => 'Leading technology company focused on web development solutions',
            'website' => 'https://techcorp.com',
            'size' => '50-200',
            'benefits' => ['Health Insurance', 'Remote Work', 'Professional Development'],
        ]);
        $response->assertStatus(200);

        // Step 2: Employer posts a job
        $jobData = [
            'title' => 'Full Stack Developer',
            'description' => 'We are looking for a talented full stack developer to join our growing team...',
            'required_skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL'],
            'preferred_skills' => ['Docker', 'AWS', 'Redis'],
            'experience_level' => 'mid',
            'employment_type' => 'full-time',
            'location' => 'San Francisco, CA',
            'remote_allowed' => true,
            'salary_min' => 80000,
            'salary_max' => 120000,
            'benefits' => ['Health Insurance', 'Dental', '401k', 'Remote Work'],
        ];

        $response = $this->postJson('/api/jobs', $jobData);
        $response->assertStatus(201);

        $job = JobPosting::where('company_id', $this->company->id)->first();

        // Step 3: Employer searches for potential candidates
        $response = $this->getJson('/api/alumni?skills[]=Laravel&skills[]=Vue.js&experience_level=mid');
        $response->assertStatus(200);

        // Step 4: Employer views candidate profiles
        $response = $this->getJson("/api/alumni/{$this->alumnus->id}");
        $response->assertStatus(200);

        // Step 5: Employer reviews job applications
        $response = $this->getJson("/api/jobs/{$job->id}/applications");
        $response->assertStatus(200);

        // Step 6: Employer participates in alumni community
        $response = $this->postJson('/api/posts', [
            'content' => 'TechCorp is always looking for talented developers! We believe in fostering growth and providing opportunities for alumni to advance their careers.',
            'post_type' => 'text',
            'visibility' => 'public',
        ]);
        $response->assertStatus(201);

        // Verify employer activities
        $this->assertDatabaseHas('job_postings', [
            'company_id' => $this->company->id,
            'title' => 'Full Stack Developer',
        ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $this->employer->id,
            'visibility' => 'public',
        ]);
    }

    public function test_mentor_journey_offering_guidance_and_building_relationships()
    {
        $this->actingAs($this->mentor);

        // Step 1: Mentor sets up mentorship profile
        $response = $this->putJson("/api/users/{$this->mentor->id}/mentorship-profile", [
            'expertise_areas' => ['Web Development', 'Career Growth', 'Leadership'],
            'availability' => 'weekends',
            'max_mentees' => 5,
            'session_duration' => 60,
            'bio' => 'Senior developer with 10+ years of experience helping others grow their careers',
            'is_accepting_mentees' => true,
        ]);
        $response->assertStatus(200);

        // Step 2: Mentor creates educational content
        $response = $this->postJson('/api/posts', [
            'content' => '5 Tips for Junior Developers:\n1. Write clean, readable code\n2. Test your code thoroughly\n3. Ask questions when stuck\n4. Contribute to open source\n5. Never stop learning',
            'post_type' => 'text',
            'visibility' => 'public',
        ]);
        $response->assertStatus(201);

        // Step 3: Mentor responds to mentorship requests
        $response = $this->getJson('/api/mentorship/requests');
        $response->assertStatus(200);

        // Step 4: Mentor schedules and conducts sessions
        $response = $this->postJson('/api/mentorship/sessions', [
            'mentee_id' => $this->alumnus->id,
            'topic' => 'Career Development Strategy',
            'scheduled_date' => now()->addDays(3)->format('Y-m-d H:i'),
            'duration' => 60,
            'notes' => 'Discuss career goals and create development plan',
        ]);
        $response->assertStatus(201);

        // Step 5: Mentor shares success stories
        $response = $this->postJson('/api/success-stories', [
            'title' => 'Helping 50+ Developers Advance Their Careers',
            'content' => 'Over the past 5 years, I\'ve had the privilege of mentoring over 50 developers...',
            'category' => 'mentorship',
            'is_featured' => true,
            'tags' => ['mentorship', 'career', 'leadership'],
        ]);
        $response->assertStatus(201);

        // Verify mentor activities
        $this->assertDatabaseHas('posts', [
            'user_id' => $this->mentor->id,
            'post_type' => 'text',
        ]);

        $this->assertDatabaseHas('success_stories', [
            'user_id' => $this->mentor->id,
            'category' => 'mentorship',
        ]);
    }

    public function test_cross_user_interactions_and_community_building()
    {
        // Step 1: Multiple users create posts
        $this->actingAs($this->alumnus);
        $alumnusPost = $this->postJson('/api/posts', [
            'content' => 'What are your favorite Laravel packages for 2024?',
            'post_type' => 'text',
            'visibility' => 'groups',
            'group_ids' => [$this->group->id],
        ])->json('data.post');

        $this->actingAs($this->mentor);
        $mentorPost = $this->postJson('/api/posts', [
            'content' => 'Just published a new article about clean code practices!',
            'post_type' => 'text',
            'visibility' => 'public',
        ])->json('data.post');

        // Step 2: Cross-engagement between users
        $this->actingAs($this->mentor);
        $this->postJson("/api/posts/{$alumnusPost['id']}/engage", [
            'type' => 'comment',
            'metadata' => [
                'comment' => 'Great question! I highly recommend Laravel Telescope for debugging and Spatie packages for various utilities.',
            ],
        ]);

        $this->actingAs($this->alumnus);
        $this->postJson("/api/posts/{$mentorPost['id']}/engage", [
            'type' => 'like',
        ]);

        $this->postJson("/api/posts/{$mentorPost['id']}/engage", [
            'type' => 'share',
            'metadata' => [
                'commentary' => 'Excellent insights on clean code! Highly recommend reading this.',
            ],
        ]);

        // Step 3: Group discussion thread
        $this->actingAs($this->employer);
        $this->postJson("/api/posts/{$alumnusPost['id']}/engage", [
            'type' => 'comment',
            'metadata' => [
                'comment' => 'From an employer perspective, we love seeing developers who use Laravel Horizon for queue management and Laravel Sanctum for API authentication.',
            ],
        ]);

        // Step 4: Event creation and participation
        $this->actingAs($this->mentor);
        $event = $this->postJson('/api/events', [
            'title' => 'Laravel Best Practices Workshop',
            'description' => 'Hands-on workshop covering Laravel best practices and modern development techniques',
            'event_date' => now()->addWeeks(2)->format('Y-m-d H:i'),
            'location' => 'Virtual',
            'is_virtual' => true,
            'max_attendees' => 50,
        ])->json('data.event');

        // Multiple users register for the event
        $this->actingAs($this->alumnus);
        $this->postJson("/api/events/{$event['id']}/register");

        $this->actingAs($this->employer);
        $this->postJson("/api/events/{$event['id']}/register");

        // Step 5: Verify community interactions
        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $alumnusPost['id'],
            'user_id' => $this->mentor->id,
            'type' => 'comment',
        ]);

        $this->assertDatabaseHas('post_engagements', [
            'post_id' => $mentorPost['id'],
            'user_id' => $this->alumnus->id,
            'type' => 'like',
        ]);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event['id'],
            'user_id' => $this->alumnus->id,
        ]);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event['id'],
            'user_id' => $this->employer->id,
        ]);
    }
}
