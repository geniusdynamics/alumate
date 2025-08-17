<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobMatchScore;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobMatchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    public function test_user_can_get_job_recommendations()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create a match score for the user
        JobMatchScore::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'score' => 75.5,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/jobs/recommendations');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'company',
                            'location',
                            'match_score',
                            'has_applied',
                        ],
                    ],
                ],
                'meta',
            ]);
    }

    public function test_user_can_get_job_details()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'score' => 80.0,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/jobs/{$job->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'company',
                    'match_analysis',
                    'mutual_connections',
                ],
            ]);
    }

    public function test_user_can_apply_for_job()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $resume = UploadedFile::fake()->create('resume.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson("/api/jobs/{$job->id}/apply", [
                'cover_letter' => 'I am very interested in this position...',
                'resume' => $resume,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Application submitted successfully!',
            ]);

        $this->assertDatabaseHas('job_applications', [
            'job_id' => $job->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        Storage::disk('private')->assertExists('resumes/'.$resume->hashName());
    }

    public function test_user_cannot_apply_twice_for_same_job()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create existing application
        JobApplication::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/jobs/{$job->id}/apply", [
                'cover_letter' => 'I am very interested in this position...',
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You have already applied for this position.',
            ]);
    }

    public function test_user_can_request_introduction()
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create connection between user and contact
        $user->connections()->attach($contact->id, [
            'status' => 'accepted',
            'connected_at' => now(),
        ]);

        // Create career timeline for contact at the company
        $contact->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Senior Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/jobs/{$job->id}/request-introduction", [
                'contact_id' => $contact->id,
                'message' => 'Could you help me with an introduction?',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Introduction request sent successfully!',
            ]);
    }

    public function test_user_can_get_mutual_connections_for_job()
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create connection
        $user->connections()->attach($contact->id, [
            'status' => 'accepted',
            'connected_at' => now(),
        ]);

        // Create career timeline for contact at the company
        $contact->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Senior Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/jobs/{$job->id}/connections");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'title',
                        'mutual_circles',
                        'can_request_introduction',
                    ],
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_user_can_get_their_applications()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        JobApplication::factory()->create([
            'job_id' => $job->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/applications');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'status',
                            'status_label',
                            'applied_at',
                            'job',
                            'is_active',
                        ],
                    ],
                ],
            ]);
    }

    public function test_job_recommendations_are_filtered_by_minimum_score()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $highScoreJob = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $lowScoreJob = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        // Create match scores
        JobMatchScore::factory()->create([
            'job_id' => $highScoreJob->id,
            'user_id' => $user->id,
            'score' => 80.0,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $lowScoreJob->id,
            'user_id' => $user->id,
            'score' => 40.0,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/jobs/recommendations?min_score=70');

        $response->assertOk();

        $jobs = $response->json('data.data');
        $this->assertCount(1, $jobs);
        $this->assertEquals($highScoreJob->id, $jobs[0]['id']);
    }

    public function test_inactive_jobs_are_not_included_in_recommendations()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $activeJob = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $inactiveJob = JobPosting::factory()->create([
            'company_id' => $company->id,
            'is_active' => false,
        ]);

        // Create match scores for both
        JobMatchScore::factory()->create([
            'job_id' => $activeJob->id,
            'user_id' => $user->id,
            'score' => 75.0,
        ]);

        JobMatchScore::factory()->create([
            'job_id' => $inactiveJob->id,
            'user_id' => $user->id,
            'score' => 85.0,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/jobs/recommendations');

        $response->assertOk();

        $jobs = $response->json('data.data');
        $this->assertCount(1, $jobs);
        $this->assertEquals($activeJob->id, $jobs[0]['id']);
    }
}
