<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Connection;
use App\Models\Circle;
use App\Services\JobMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobMatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private JobMatchingService $jobMatchingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jobMatchingService = new JobMatchingService();
    }

    public function test_calculate_match_score_returns_weighted_score()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Laravel', 'Vue.js']
        ]);
        
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'skills_required' => ['PHP', 'Laravel', 'JavaScript'],
            'title' => 'Senior PHP Developer',
            'description' => 'We are looking for a senior PHP developer...',
        ]);

        $score = $this->jobMatchingService->calculateMatchScore($job, $user);

        $this->assertIsFloat($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_connection_score_increases_with_mutual_connections()
    {
        $user = User::factory()->create();
        $contact1 = User::factory()->create();
        $contact2 = User::factory()->create();
        $company = Company::factory()->create();
        
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        // Create connections
        Connection::factory()->create([
            'user_id' => $user->id,
            'connected_user_id' => $contact1->id,
            'status' => 'accepted',
        ]);

        Connection::factory()->create([
            'user_id' => $user->id,
            'connected_user_id' => $contact2->id,
            'status' => 'accepted',
        ]);

        // Create career timelines for contacts at the company
        $contact1->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $contact2->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Senior Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $connectionScore = $this->jobMatchingService->getConnectionScore($user, $job);

        $this->assertGreaterThan(0, $connectionScore);
        $this->assertLessThanOrEqual(100, $connectionScore);
    }

    public function test_skills_score_calculates_percentage_match()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Laravel', 'Vue.js', 'MySQL']
        ]);
        
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'skills_required' => ['PHP', 'Laravel'], // 2 out of 2 match
        ]);

        $skillsScore = $this->jobMatchingService->getSkillsScore($user, $job);

        $this->assertEquals(100, $skillsScore); // Perfect match
    }

    public function test_skills_score_handles_partial_matches()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Vue.js']
        ]);
        
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'skills_required' => ['PHP', 'Laravel', 'JavaScript', 'MySQL'], // 1 out of 4 match
        ]);

        $skillsScore = $this->jobMatchingService->getSkillsScore($user, $job);

        $this->assertEquals(25, $skillsScore); // 25% match
    }

    public function test_education_score_considers_degree_relevance()
    {
        $user = User::factory()->create();
        
        // Create education with computer science degree
        $user->educations()->create([
            'degree' => 'Bachelor of Computer Science',
            'field_of_study' => 'Computer Science',
            'school_id' => 1,
            'graduation_year' => 2020,
        ]);

        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'title' => 'Software Engineer',
            'description' => 'We are looking for a software engineer with programming experience...',
        ]);

        $educationScore = $this->jobMatchingService->getEducationScore($user, $job);

        $this->assertGreaterThan(50, $educationScore); // Should be above neutral
    }

    public function test_circle_score_calculates_alumni_overlap()
    {
        $user = User::factory()->create();
        $employee = User::factory()->create();
        $company = Company::factory()->create();
        
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        // Create shared circle
        $circle = Circle::factory()->create([
            'name' => 'MIT Class of 2020',
            'type' => 'school_year',
        ]);

        $user->circles()->attach($circle->id);
        $employee->circles()->attach($circle->id);

        // Create career timeline for employee at the company
        $employee->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $circleScore = $this->jobMatchingService->getCircleScore($user, $job);

        $this->assertGreaterThan(0, $circleScore);
    }

    public function test_find_mutual_connections_returns_connected_employees()
    {
        $user = User::factory()->create();
        $contact = User::factory()->create();
        $company = Company::factory()->create();
        
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        // Create connection
        Connection::factory()->create([
            'user_id' => $user->id,
            'connected_user_id' => $contact->id,
            'status' => 'accepted',
        ]);

        // Create career timeline for contact at the company
        $contact->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Senior Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $mutualConnections = $this->jobMatchingService->findMutualConnections($user, $job);

        $this->assertCount(1, $mutualConnections);
        $this->assertEquals($contact->id, $mutualConnections->first()->id);
    }

    public function test_get_match_reasons_returns_detailed_explanations()
    {
        $user = User::factory()->create([
            'skills' => ['PHP', 'Laravel']
        ]);
        
        $contact = User::factory()->create();
        $company = Company::factory()->create();
        
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'skills_required' => ['PHP', 'Laravel'],
        ]);

        // Create connection
        Connection::factory()->create([
            'user_id' => $user->id,
            'connected_user_id' => $contact->id,
            'status' => 'accepted',
        ]);

        // Create career timeline for contact
        $contact->careerTimelines()->create([
            'company' => $company->name,
            'title' => 'Developer',
            'is_current' => true,
            'start_date' => now()->subYear(),
        ]);

        $reasons = $this->jobMatchingService->getMatchReasons($user, $job);

        $this->assertIsArray($reasons);
        $this->assertNotEmpty($reasons);
        
        // Check that reasons have required structure
        foreach ($reasons as $reason) {
            $this->assertArrayHasKey('type', $reason);
            $this->assertArrayHasKey('reason', $reason);
            $this->assertArrayHasKey('score', $reason);
            $this->assertArrayHasKey('details', $reason);
        }
    }

    public function test_store_match_score_creates_or_updates_record()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        $matchScore = $this->jobMatchingService->storeMatchScore($job, $user);

        $this->assertInstanceOf(\App\Models\JobMatchScore::class, $matchScore);
        $this->assertEquals($job->id, $matchScore->job_id);
        $this->assertEquals($user->id, $matchScore->user_id);
        $this->assertNotNull($matchScore->score);
        $this->assertNotNull($matchScore->calculated_at);

        // Test update
        $originalScore = $matchScore->score;
        $updatedMatchScore = $this->jobMatchingService->storeMatchScore($job, $user);
        
        $this->assertEquals($matchScore->id, $updatedMatchScore->id);
        // Score might be the same or different depending on data, but should be recalculated
        $this->assertNotNull($updatedMatchScore->calculated_at);
    }

    public function test_zero_connection_score_when_no_mutual_connections()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
        ]);

        $connectionScore = $this->jobMatchingService->getConnectionScore($user, $job);

        $this->assertEquals(0, $connectionScore);
    }

    public function test_neutral_skills_score_when_no_skills_data()
    {
        $user = User::factory()->create([
            'skills' => null
        ]);
        
        $company = Company::factory()->create();
        $job = JobPosting::factory()->create([
            'company_id' => $company->id,
            'skills_required' => null,
        ]);

        $skillsScore = $this->jobMatchingService->getSkillsScore($user, $job);

        $this->assertEquals(50, $skillsScore); // Neutral score
    }
}