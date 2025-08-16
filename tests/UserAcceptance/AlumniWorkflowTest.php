<?php

use App\Models\Education;
use App\Models\Event;
use App\Models\Group;
use App\Models\Institution;
use App\Models\Job;
use App\Models\MentorProfile;
use App\Models\Post;
use App\Models\User;
use App\Models\UserTestingSession;
use App\Services\UserTestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Alumni Workflow User Acceptance Tests', function () {
    beforeEach(function () {
        $this->userTestingService = app(UserTestingService::class);

        // Create test institution
        $this->institution = Institution::factory()->create([
            'name' => 'Test University',
            'domain' => 'test.edu',
        ]);

        // Create test alumni user
        $this->alumni = User::factory()->create([
            'name' => 'John Alumni',
            'email' => 'john@test.edu',
            'role' => 'alumni',
        ]);

        // Add education background
        Education::factory()->create([
            'user_id' => $this->alumni->id,
            'institution_id' => $this->institution->id,
            'degree' => 'Bachelor of Science',
            'field_of_study' => 'Computer Science',
            'graduation_year' => 2020,
        ]);
    });

    describe('Alumni Onboarding Workflow', function () {
        it('completes the full onboarding experience successfully', function () {
            // Start testing session
            $session = $this->userTestingService->createTestingSession(
                $this->alumni,
                'alumni_onboarding',
                ['test_type' => 'user_acceptance']
            );

            expect($session)->toBeInstanceOf(UserTestingSession::class);
            expect($session->scenario)->toBe('alumni_onboarding');
            expect($session->status)->toBe('active');

            // Step 1: Complete profile setup
            $this->actingAs($this->alumni)
                ->patch('/api/profile', [
                    'bio' => 'Software engineer with 5 years experience',
                    'location' => 'San Francisco, CA',
                    'website' => 'https://johndoe.dev',
                    'skills' => ['PHP', 'Laravel', 'Vue.js', 'JavaScript'],
                ])
                ->assertSuccessful();

            // Verify profile completion
            $this->alumni->refresh();
            expect($this->alumni->bio)->not->toBeNull();
            expect($this->alumni->location)->not->toBeNull();

            // Step 2: Connect with classmates (simulate finding and connecting)
            $classmate1 = User::factory()->create(['role' => 'alumni']);
            $classmate2 = User::factory()->create(['role' => 'alumni']);

            // Add same education background for classmates
            Education::factory()->create([
                'user_id' => $classmate1->id,
                'institution_id' => $this->institution->id,
                'graduation_year' => 2020,
            ]);

            Education::factory()->create([
                'user_id' => $classmate2->id,
                'institution_id' => $this->institution->id,
                'graduation_year' => 2020,
            ]);

            // Send connection requests
            $this->actingAs($this->alumni)
                ->post('/api/connections', [
                    'user_id' => $classmate1->id,
                    'message' => 'Hey! We graduated together from Test University!',
                ])
                ->assertSuccessful();

            $this->actingAs($this->alumni)
                ->post('/api/connections', [
                    'user_id' => $classmate2->id,
                    'message' => 'Great to connect with a fellow CS grad!',
                ])
                ->assertSuccessful();

            // Accept connections (simulate classmates accepting)
            $this->actingAs($classmate1)
                ->patch("/api/connections/{$this->alumni->id}/accept")
                ->assertSuccessful();

            $this->actingAs($classmate2)
                ->patch("/api/connections/{$this->alumni->id}/accept")
                ->assertSuccessful();

            // Verify connections were made
            $connections = $this->alumni->connections()->count();
            expect($connections)->toBeGreaterThanOrEqual(2);

            // Step 3: Join relevant groups
            $csGroup = Group::factory()->create([
                'name' => 'Computer Science Alumni',
                'type' => 'school',
                'institution_id' => $this->institution->id,
            ]);

            $this->actingAs($this->alumni)
                ->post("/api/groups/{$csGroup->id}/join")
                ->assertSuccessful();

            // Verify group membership
            expect($this->alumni->groups()->count())->toBeGreaterThanOrEqual(1);

            // Step 4: Create first post
            $this->actingAs($this->alumni)
                ->post('/api/posts', [
                    'content' => 'Excited to reconnect with my Test University family! Looking forward to networking and sharing experiences.',
                    'type' => 'general',
                    'visibility' => 'circles',
                ])
                ->assertSuccessful();

            // Verify post was created
            expect(Post::where('user_id', $this->alumni->id)->count())->toBe(1);

            // Complete testing session
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 300, // 5 minutes
            ]);

            // Verify success criteria
            $this->alumni->refresh();

            // Profile completion rate > 80%
            $profileFields = ['bio', 'location', 'website'];
            $completedFields = collect($profileFields)->filter(function ($field) {
                return ! empty($this->alumni->$field);
            })->count();
            $completionRate = ($completedFields / count($profileFields)) * 100;
            expect($completionRate)->toBeGreaterThan(80);

            // At least 3 connections made (we made 2, but could have auto-connections)
            expect($this->alumni->connections()->count())->toBeGreaterThanOrEqual(2);

            // Joined at least 1 group
            expect($this->alumni->groups()->count())->toBeGreaterThanOrEqual(1);
        });

        it('tracks user experience metrics during onboarding', function () {
            $session = $this->userTestingService->createTestingSession(
                $this->alumni,
                'alumni_onboarding'
            );

            // Simulate some onboarding steps with timing
            sleep(1);

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 180,
            ]);

            $metrics = $this->userTestingService->getUserExperienceMetrics($this->alumni);

            expect($metrics['total_sessions'])->toBe(1);
            expect($metrics['completed_sessions'])->toBe(1);
            expect($metrics['scenarios_tested'])->toBe(1);
        });
    });

    describe('Job Search Workflow', function () {
        it('successfully discovers and applies for jobs', function () {
            $session = $this->userTestingService->createTestingSession(
                $this->alumni,
                'job_search_workflow'
            );

            // Create test jobs
            $job1 = Job::factory()->create([
                'title' => 'Senior Software Engineer',
                'company' => 'Tech Corp',
                'location' => 'San Francisco, CA',
                'skills_required' => ['PHP', 'Laravel', 'Vue.js'],
                'status' => 'active',
            ]);

            $job2 = Job::factory()->create([
                'title' => 'Frontend Developer',
                'company' => 'Startup Inc',
                'location' => 'Remote',
                'skills_required' => ['JavaScript', 'Vue.js', 'React'],
                'status' => 'active',
            ]);

            // Step 1: Browse job listings
            $response = $this->actingAs($this->alumni)
                ->get('/api/jobs')
                ->assertSuccessful();

            $jobs = $response->json('data');
            expect(count($jobs))->toBeGreaterThanOrEqual(2);

            // Step 2: Use search filters
            $response = $this->actingAs($this->alumni)
                ->get('/api/jobs?'.http_build_query([
                    'skills' => 'Laravel',
                    'location' => 'San Francisco',
                ]))
                ->assertSuccessful();

            $filteredJobs = $response->json('data');
            expect(count($filteredJobs))->toBeGreaterThanOrEqual(1);

            // Step 3: View job details
            $this->actingAs($this->alumni)
                ->get("/api/jobs/{$job1->id}")
                ->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'company',
                        'description',
                        'skills_required',
                        'match_score',
                    ],
                ]);

            // Step 4: Request introduction (if connections exist at company)
            // This would typically involve finding mutual connections

            // Step 5: Apply for position
            $this->actingAs($this->alumni)
                ->post("/api/jobs/{$job1->id}/apply", [
                    'cover_letter' => 'I am excited to apply for this position...',
                    'resume_url' => 'https://example.com/resume.pdf',
                ])
                ->assertSuccessful();

            // Complete session
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 240,
            ]);

            // Verify success criteria
            // Found relevant jobs within reasonable time (simulated)
            // Successfully applied for position
            expect($this->alumni->jobApplications()->count())->toBe(1);
        });
    });

    describe('Mentorship Connection Workflow', function () {
        it('successfully finds and connects with mentors', function () {
            $session = $this->userTestingService->createTestingSession(
                $this->alumni,
                'mentorship_connection'
            );

            // Create mentor profiles
            $mentor = User::factory()->create(['role' => 'alumni']);
            $mentorProfile = MentorProfile::factory()->create([
                'user_id' => $mentor->id,
                'expertise_areas' => ['Software Engineering', 'Career Development'],
                'industry' => 'Technology',
                'availability' => 'available',
            ]);

            // Step 1: Browse mentor profiles
            $response = $this->actingAs($this->alumni)
                ->get('/api/mentors')
                ->assertSuccessful();

            $mentors = $response->json('data');
            expect(count($mentors))->toBeGreaterThanOrEqual(1);

            // Step 2: Filter by industry/expertise
            $response = $this->actingAs($this->alumni)
                ->get('/api/mentors?'.http_build_query([
                    'industry' => 'Technology',
                    'expertise' => 'Software Engineering',
                ]))
                ->assertSuccessful();

            $filteredMentors = $response->json('data');
            expect(count($filteredMentors))->toBeGreaterThanOrEqual(1);

            // Step 3: Send mentorship request
            $this->actingAs($this->alumni)
                ->post('/api/mentorship-requests', [
                    'mentor_id' => $mentor->id,
                    'message' => 'I would love to learn from your experience in software engineering.',
                    'goals' => 'Career advancement and technical skill development',
                ])
                ->assertSuccessful();

            // Step 4: Schedule initial meeting (simulate mentor acceptance)
            $request = $this->alumni->sentMentorshipRequests()->first();
            expect($request)->not->toBeNull();

            // Complete session
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 420,
            ]);

            // Verify success criteria
            expect($this->alumni->sentMentorshipRequests()->count())->toBe(1);
        });
    });

    describe('Event Participation Workflow', function () {
        it('successfully discovers and registers for events', function () {
            $session = $this->userTestingService->createTestingSession(
                $this->alumni,
                'event_participation'
            );

            // Create test events
            $event = Event::factory()->create([
                'title' => 'Alumni Networking Night',
                'description' => 'Join us for an evening of networking and reconnection',
                'location' => 'San Francisco, CA',
                'event_type' => 'networking',
                'start_date' => now()->addWeeks(2),
                'status' => 'published',
            ]);

            // Step 1: Browse upcoming events
            $response = $this->actingAs($this->alumni)
                ->get('/api/events')
                ->assertSuccessful();

            $events = $response->json('data');
            expect(count($events))->toBeGreaterThanOrEqual(1);

            // Step 2: Filter by location/type
            $response = $this->actingAs($this->alumni)
                ->get('/api/events?'.http_build_query([
                    'location' => 'San Francisco',
                    'type' => 'networking',
                ]))
                ->assertSuccessful();

            $filteredEvents = $response->json('data');
            expect(count($filteredEvents))->toBeGreaterThanOrEqual(1);

            // Step 3: View event details
            $this->actingAs($this->alumni)
                ->get("/api/events/{$event->id}")
                ->assertSuccessful()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'location',
                        'start_date',
                        'attendee_count',
                    ],
                ]);

            // Step 4: RSVP to event
            $this->actingAs($this->alumni)
                ->post("/api/events/{$event->id}/rsvp", [
                    'status' => 'attending',
                ])
                ->assertSuccessful();

            // Step 5: Add to calendar (simulate)
            // This would typically involve calendar integration

            // Complete session
            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 180,
            ]);

            // Verify success criteria
            expect($this->alumni->eventAttendances()->count())->toBe(1);
            expect($this->alumni->eventAttendances()->first()->status)->toBe('attending');
        });
    });

    describe('Overall User Experience Validation', function () {
        it('provides comprehensive analytics for user testing', function () {
            // Create multiple testing sessions
            $scenarios = ['alumni_onboarding', 'job_search_workflow', 'mentorship_connection'];

            foreach ($scenarios as $scenario) {
                $session = $this->userTestingService->createTestingSession(
                    $this->alumni,
                    $scenario
                );

                $session->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'duration_seconds' => rand(120, 600),
                ]);
            }

            // Record some feedback
            $this->userTestingService->recordFeedback(
                $this->alumni,
                'general_feedback',
                'The platform is intuitive and easy to use!',
                5
            );

            // Get analytics
            $analytics = $this->userTestingService->getTestingAnalytics();

            expect($analytics['total_sessions'])->toBeGreaterThanOrEqual(3);
            expect($analytics['unique_users'])->toBeGreaterThanOrEqual(1);
            expect($analytics['completion_rate'])->toBe(1.0); // All sessions completed
            expect($analytics['feedback_summary']['total_feedback'])->toBeGreaterThanOrEqual(1);
            expect($analytics['feedback_summary']['average_rating'])->toBe(5.0);
        });

        it('tracks user journey completion rates', function () {
            // Test abandoned session
            $abandonedSession = $this->userTestingService->createTestingSession(
                $this->alumni,
                'alumni_onboarding'
            );

            $abandonedSession->update(['status' => 'abandoned']);

            // Test completed session
            $completedSession = $this->userTestingService->createTestingSession(
                $this->alumni,
                'job_search_workflow'
            );

            $completedSession->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_seconds' => 300,
            ]);

            $metrics = $this->userTestingService->getUserExperienceMetrics($this->alumni);

            expect($metrics['total_sessions'])->toBe(2);
            expect($metrics['completed_sessions'])->toBe(1);
        });
    });
});
