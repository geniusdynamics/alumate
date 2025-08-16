<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Connection;
use App\Models\JobApplication;
use App\Models\JobMatchScore;
use App\Models\JobPosting;
use App\Models\User;
use App\Models\WorkExperience;
use App\Services\JobMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobMatchingIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $connectionAtCompany;

    protected User $alumniAtCompany;

    protected Company $company;

    protected JobPosting $job;

    protected JobMatchingService $jobMatchingService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        $this->user = User::factory()->create([
            'profile_data' => [
                'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL'],
                'interests' => ['Web Development', 'API Design'],
            ],
        ]);

        $this->connectionAtCompany = User::factory()->create();
        $this->alumniAtCompany = User::factory()->create();

        $this->company = Company::factory()->create([
            'name' => 'TechCorp Inc',
            'industry' => 'Technology',
            'size' => 'large',
        ]);

        $this->job = JobPosting::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Senior Laravel Developer',
            'description' => 'We are looking for an experienced Laravel developer...',
            'requirements' => ['PHP', 'Laravel', 'Vue.js', '3+ years experience'],
            'location' => 'San Francisco, CA',
            'salary_min' => 120000,
            'salary_max' => 160000,
            'is_active' => true,
        ]);

        // Create work experiences for connections at the company
        WorkExperience::factory()->create([
            'user_id' => $this->connectionAtCompany->id,
            'company' => $this->company->name,
            'title' => 'Senior Software Engineer',
            'is_current' => true,
        ]);

        WorkExperience::factory()->create([
            'user_id' => $this->alumniAtCompany->id,
            'company' => $this->company->name,
            'title' => 'Engineering Manager',
            'is_current' => true,
        ]);

        // Create connections
        $this->user->sendConnectionRequest($this->connectionAtCompany);
        $this->connectionAtCompany->acceptConnectionRequest(
            $this->connectionAtCompany->receivedConnectionRequests()->first()->id
        );

        $this->jobMatchingService = app(JobMatchingService::class);
    }

    public function test_job_matching_algorithm_integration()
    {
        // Generate match scores
        $response = $this->actingAs($this->user)
            ->postJson('/api/jobs/generate-matches');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job matches generated successfully',
            ]);

        // Verify match score was created
        $this->assertDatabaseHas('job_match_scores', [
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
        ]);

        $matchScore = JobMatchScore::where('job_id', $this->job->id)
            ->where('user_id', $this->user->id)
            ->first();

        // Should have high match score due to skill alignment
        $this->assertGreaterThan(70, $matchScore->score);
        $this->assertArrayHasKey('skills_match', $matchScore->match_reasons);
        $this->assertArrayHasKey('connection_boost', $matchScore->match_reasons);
    }

    public function test_personalized_job_recommendations()
    {
        // Create match score
        JobMatchScore::factory()->create([
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
            'score' => 85.5,
            'match_reasons' => [
                'skills_match' => 90,
                'experience_match' => 75,
                'location_match' => 80,
                'connection_boost' => 10,
            ],
        ]);

        // Create additional jobs with lower scores
        $lowScoreJob = JobPosting::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'is_active' => true,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $lowScoreJob->id,
            'user_id' => $this->user->id,
            'score' => 45.0,
        ]);

        // Get recommendations
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'company',
                            'location',
                            'match_score',
                            'match_reasons',
                            'has_applied',
                            'mutual_connections',
                        ],
                    ],
                ],
            ]);

        $jobs = $response->json('data.data');

        // Should be sorted by match score (highest first)
        $this->assertEquals($this->job->id, $jobs[0]['id']);
        $this->assertEquals(85.5, $jobs[0]['match_score']);
        $this->assertArrayHasKey('skills_match', $jobs[0]['match_reasons']);
    }

    public function test_job_application_workflow_with_resume_upload()
    {
        $resume = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');
        $coverLetter = 'I am very excited about this opportunity...';

        // Apply for job
        $response = $this->actingAs($this->user)
            ->postJson("/api/jobs/{$this->job->id}/apply", [
                'cover_letter' => $coverLetter,
                'resume' => $resume,
                'additional_info' => 'I have 5 years of Laravel experience',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Application submitted successfully!',
            ]);

        // Verify application was created
        $this->assertDatabaseHas('job_applications', [
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $application = JobApplication::where('job_id', $this->job->id)
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertEquals($coverLetter, $application->cover_letter);
        $this->assertNotNull($application->resume_path);

        // Verify resume was stored
        Storage::disk('private')->assertExists($application->resume_path);

        // Test duplicate application prevention
        $response = $this->actingAs($this->user)
            ->postJson("/api/jobs/{$this->job->id}/apply", [
                'cover_letter' => 'Another application',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have already applied for this position.',
            ]);
    }

    public function test_mutual_connections_at_company()
    {
        // Get job details with mutual connections
        $response = $this->actingAs($this->user)
            ->getJson("/api/jobs/{$this->job->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'company',
                    'mutual_connections',
                    'alumni_at_company',
                    'match_analysis',
                ],
            ]);

        $jobData = $response->json('data');

        // Should show mutual connection
        $this->assertCount(1, $jobData['mutual_connections']);
        $this->assertEquals($this->connectionAtCompany->id, $jobData['mutual_connections'][0]['id']);
        $this->assertEquals('Senior Software Engineer', $jobData['mutual_connections'][0]['title']);

        // Should show alumni at company
        $this->assertGreaterThanOrEqual(1, count($jobData['alumni_at_company']));
    }

    public function test_introduction_request_workflow()
    {
        // Request introduction through mutual connection
        $response = $this->actingAs($this->user)
            ->postJson("/api/jobs/{$this->job->id}/request-introduction", [
                'contact_id' => $this->connectionAtCompany->id,
                'message' => 'Hi! Could you help me get an introduction for this Laravel position at TechCorp?',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Introduction request sent successfully!',
            ]);

        // Verify introduction request was created
        $this->assertDatabaseHas('introduction_requests', [
            'requester_id' => $this->user->id,
            'contact_id' => $this->connectionAtCompany->id,
            'job_id' => $this->job->id,
            'status' => 'pending',
        ]);

        // Test that contact receives the request
        $response = $this->actingAs($this->connectionAtCompany)
            ->getJson('/api/introduction-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'requester',
                        'job',
                        'message',
                        'status',
                        'created_at',
                    ],
                ],
            ]);

        $requests = $response->json('data');
        $this->assertCount(1, $requests);
        $this->assertEquals($this->user->id, $requests[0]['requester']['id']);
    }

    public function test_job_application_status_tracking()
    {
        // Create application
        $application = JobApplication::factory()->create([
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        // Get user's applications
        $response = $this->actingAs($this->user)
            ->getJson('/api/applications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'status_label',
                            'applied_at',
                            'job',
                            'timeline',
                        ],
                    ],
                ],
            ]);

        $applications = $response->json('data.data');
        $this->assertCount(1, $applications);
        $this->assertEquals('pending', $applications[0]['status']);

        // Update application status (simulate employer action)
        $application->update(['status' => 'reviewing']);

        // Check updated status
        $response = $this->actingAs($this->user)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $applications = $response->json('data.data');
        $this->assertEquals('reviewing', $applications[0]['status']);
    }

    public function test_job_search_and_filtering()
    {
        // Create additional jobs for filtering
        $remoteJob = JobPosting::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'title' => 'Remote PHP Developer',
            'location' => 'Remote',
            'job_type' => 'remote',
            'is_active' => true,
        ]);

        $frontendJob = JobPosting::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'title' => 'Frontend Developer',
            'requirements' => ['JavaScript', 'React', 'CSS'],
            'is_active' => true,
        ]);

        // Create match scores
        JobMatchScore::factory()->create([
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
            'score' => 85,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $remoteJob->id,
            'user_id' => $this->user->id,
            'score' => 75,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $frontendJob->id,
            'user_id' => $this->user->id,
            'score' => 60,
        ]);

        // Test search by title
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/search?query=Laravel');

        $response->assertStatus(200);
        $jobs = $response->json('data.data');
        $this->assertCount(1, $jobs);
        $this->assertEquals($this->job->id, $jobs[0]['id']);

        // Test filter by location
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations?location=Remote');

        $response->assertStatus(200);
        $jobs = $response->json('data.data');
        $remoteJobs = collect($jobs)->where('location', 'Remote');
        $this->assertGreaterThan(0, $remoteJobs->count());

        // Test filter by minimum match score
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations?min_score=80');

        $response->assertStatus(200);
        $jobs = $response->json('data.data');
        $this->assertCount(1, $jobs);
        $this->assertEquals($this->job->id, $jobs[0]['id']);
    }

    public function test_job_bookmarking_and_saved_jobs()
    {
        // Bookmark job
        $response = $this->actingAs($this->user)
            ->postJson("/api/jobs/{$this->job->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job bookmarked successfully',
            ]);

        // Verify bookmark was created
        $this->assertDatabaseHas('job_bookmarks', [
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
        ]);

        // Get saved jobs
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/saved');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'company',
                        'bookmarked_at',
                        'match_score',
                    ],
                ],
            ]);

        $savedJobs = $response->json('data');
        $this->assertCount(1, $savedJobs);
        $this->assertEquals($this->job->id, $savedJobs[0]['id']);

        // Remove bookmark
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/jobs/{$this->job->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Job bookmark removed',
            ]);

        // Verify bookmark was removed
        $this->assertDatabaseMissing('job_bookmarks', [
            'job_id' => $this->job->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_job_matching_performance_with_large_dataset()
    {
        // Create many jobs and users for performance testing
        $companies = Company::factory()->count(10)->create();
        $jobs = collect();

        foreach ($companies as $company) {
            $companyJobs = JobPosting::factory()->count(5)->create([
                'company_id' => $company->id,
                'is_active' => true,
            ]);
            $jobs = $jobs->merge($companyJobs);
        }

        $users = User::factory()->count(20)->create();

        // Measure time for batch match generation
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->postJson('/api/jobs/generate-matches', [
                'batch_size' => 100,
            ]);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(200);

        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(10, $executionTime, 'Job matching took too long');

        // Verify matches were generated
        $matchCount = JobMatchScore::where('user_id', $this->user->id)->count();
        $this->assertGreaterThan(0, $matchCount);
    }

    public function test_job_recommendation_personalization()
    {
        // Update user profile with specific preferences
        $this->user->update([
            'profile_data' => [
                'skills' => ['PHP', 'Laravel', 'Vue.js'],
                'interests' => ['Backend Development', 'API Design'],
                'job_preferences' => [
                    'preferred_locations' => ['San Francisco', 'Remote'],
                    'salary_expectation' => 140000,
                    'job_types' => ['full-time', 'remote'],
                ],
            ],
        ]);

        // Create jobs matching preferences
        $perfectMatch = JobPosting::factory()->create([
            'company_id' => $this->company->id,
            'title' => 'Senior Laravel API Developer',
            'location' => 'San Francisco, CA',
            'job_type' => 'full-time',
            'salary_min' => 130000,
            'salary_max' => 150000,
            'requirements' => ['PHP', 'Laravel', 'API Development'],
            'is_active' => true,
        ]);

        $partialMatch = JobPosting::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'title' => 'Frontend Developer',
            'location' => 'New York, NY',
            'requirements' => ['JavaScript', 'React'],
            'is_active' => true,
        ]);

        // Generate matches
        $this->jobMatchingService->generateMatchesForUser($this->user);

        // Get recommendations
        $response = $this->actingAs($this->user)
            ->getJson('/api/jobs/recommendations');

        $response->assertStatus(200);
        $jobs = $response->json('data.data');

        // Perfect match should have higher score
        $perfectMatchData = collect($jobs)->firstWhere('id', $perfectMatch->id);
        $partialMatchData = collect($jobs)->firstWhere('id', $partialMatch->id);

        if ($perfectMatchData && $partialMatchData) {
            $this->assertGreaterThan(
                $partialMatchData['match_score'],
                $perfectMatchData['match_score']
            );
        }
    }

    public function test_job_application_analytics_and_insights()
    {
        // Create multiple applications with different statuses
        $applications = [
            ['status' => 'pending', 'created_at' => now()->subDays(1)],
            ['status' => 'reviewing', 'created_at' => now()->subDays(3)],
            ['status' => 'interviewed', 'created_at' => now()->subDays(7)],
            ['status' => 'rejected', 'created_at' => now()->subDays(10)],
        ];

        foreach ($applications as $appData) {
            JobApplication::factory()->create([
                'user_id' => $this->user->id,
                'job_id' => JobPosting::factory()->create(['is_active' => true])->id,
                'status' => $appData['status'],
                'created_at' => $appData['created_at'],
            ]);
        }

        // Get application analytics
        $response = $this->actingAs($this->user)
            ->getJson('/api/applications/analytics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_applications',
                    'status_breakdown',
                    'application_rate',
                    'response_rate',
                    'recent_activity',
                    'recommendations',
                ],
            ]);

        $analytics = $response->json('data');

        $this->assertEquals(4, $analytics['total_applications']);
        $this->assertArrayHasKey('pending', $analytics['status_breakdown']);
        $this->assertArrayHasKey('reviewing', $analytics['status_breakdown']);
        $this->assertArrayHasKey('interviewed', $analytics['status_breakdown']);
        $this->assertArrayHasKey('rejected', $analytics['status_breakdown']);
    }
}
