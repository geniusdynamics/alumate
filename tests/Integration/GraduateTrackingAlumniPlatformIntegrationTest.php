<?php

namespace Tests\Integration;

use App\Models\CareerOutcomeSnapshot;
use App\Models\CareerPath;
use App\Models\CareerTimeline;
use App\Models\Company;
use App\Models\EducationHistory;
use App\Models\Employer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\AchievementCelebration;
use App\Models\Discussion;
use App\Models\DiscussionLike;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GraduateTrackingAlumniPlatformIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $graduate1;
    protected User $graduate2;
    protected User $alumni1;
    protected User $alumni2;
    protected CareerPath $careerPath1;
    protected CareerPath $careerPath2;
    protected Company $company1;
    protected Company $company2;
    protected EducationHistory $education1;
    protected EducationHistory $education2;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();

        // Create two tenants for cross-tenant testing
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Graduate Tracking University A',
            'domain' => 'graduate-a.edu',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Graduate Tracking University B',
            'domain' => 'graduate-b.edu',
        ]);

        // Create users for tenant 1
        $this->graduate1 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'graduate1@graduate-a.edu',
            'graduation_year' => 2020,
            'is_alumni' => true,
        ]);

        $this->graduate2 = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'graduate2@graduate-a.edu',
            'graduation_year' => 2021,
            'is_alumni' => true,
        ]);

        // Create users for tenant 2
        $this->alumni1 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'alumni1@graduate-b.edu',
            'graduation_year' => 2019,
            'is_alumni' => true,
        ]);

        $this->alumni2 = User::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'email' => 'alumni2@graduate-b.edu',
            'graduation_year' => 2022,
            'is_alumni' => true,
        ]);

        // Create companies for career tracking
        $this->company1 = Company::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Tech Corp A',
            'industry' => 'Technology',
        ]);

        $this->company2 = Company::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Finance Corp B',
            'industry' => 'Finance',
        ]);

        // Create education histories
        $this->education1 = EducationHistory::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'degree' => 'Bachelor of Science',
            'major' => 'Computer Science',
            'graduation_year' => 2020,
        ]);

        $this->education2 = EducationHistory::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->alumni1->id,
            'degree' => 'Master of Business Administration',
            'major' => 'Finance',
            'graduation_year' => 2019,
        ]);

        // Create career paths
        $this->careerPath1 = CareerPath::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'current_company' => $this->company1->id,
            'job_title' => 'Software Engineer',
            'start_date' => '2020-06-01',
            'current_salary_range' => '75000-90000',
        ]);

        $this->careerPath2 = CareerPath::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->alumni1->id,
            'current_company' => $this->company2->id,
            'job_title' => 'Financial Analyst',
            'start_date' => '2019-08-01',
            'current_salary_range' => '80000-100000',
        ]);
    }

    public function test_graduate_profile_data_flow_to_alumni_platform()
    {
        // Test that graduate profile data flows correctly to alumni platform
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/profile');

        $response->assertStatus(200);
        $profileData = $response->json('data');

        $this->assertEquals($this->graduate1->id, $profileData['id']);
        $this->assertEquals($this->graduate1->graduation_year, $profileData['graduation_year']);
        $this->assertArrayHasKey('education_history', $profileData);
        $this->assertArrayHasKey('career_path', $profileData);
        $this->assertEquals('Computer Science', $profileData['education_history']['major']);
        $this->assertEquals('Software Engineer', $profileData['career_path']['job_title']);
    }

    public function test_career_timeline_integration_with_alumni_social_features()
    {
        // Create career timeline entries
        $timeline1 = CareerTimeline::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'title' => 'Started First Job',
            'description' => 'Joined Tech Corp A as Software Engineer',
            'event_type' => 'career_milestone',
            'event_date' => '2020-06-01',
            'is_public' => true,
        ]);

        $timeline2 = CareerTimeline::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'title' => 'Promotion',
            'description' => 'Promoted to Senior Software Engineer',
            'event_type' => 'promotion',
            'event_date' => '2022-01-15',
            'is_public' => true,
        ]);

        // Test timeline integration with social features
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/social-timeline');

        $response->assertStatus(200);
        $timelineData = $response->json('data');

        $this->assertCount(2, $timelineData);
        $this->assertEquals('Started First Job', $timelineData[0]['title']);
        $this->assertEquals('Promotion', $timelineData[1]['title']);
        $this->assertArrayHasKey('career_path', $timelineData[0]);
        $this->assertArrayHasKey('company', $timelineData[0]);
    }

    public function test_career_outcome_snapshots_and_analytics_integration()
    {
        // Create career outcome snapshots for analytics
        CareerOutcomeSnapshot::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'snapshot_date' => '2021-01-01',
            'employment_status' => 'employed',
            'job_satisfaction' => 8,
            'salary_range' => '60000-75000',
            'career_progression_score' => 7,
        ]);

        CareerOutcomeSnapshot::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'snapshot_date' => '2023-01-01',
            'employment_status' => 'employed',
            'job_satisfaction' => 9,
            'salary_range' => '90000-110000',
            'career_progression_score' => 9,
        ]);

        // Test analytics integration
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/career-analytics');

        $response->assertStatus(200);
        $analytics = $response->json('data');

        $this->assertArrayHasKey('career_progression', $analytics);
        $this->assertArrayHasKey('salary_trend', $analytics);
        $this->assertArrayHasKey('job_satisfaction_trend', $analytics);
        $this->assertGreaterThan(0, $analytics['career_progression']['improvement_percentage']);
    }

    public function test_achievement_celebration_system_integration()
    {
        // Create achievement celebration
        $achievement = AchievementCelebration::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'title' => 'Career Milestone Achievement',
            'description' => 'Congratulations on your promotion!',
            'achievement_type' => 'promotion',
            'celebration_date' => '2022-01-15',
            'is_featured' => true,
        ]);

        // Test integration with alumni social feed
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/achievements');

        $response->assertStatus(200);
        $achievements = $response->json('data');

        $this->assertCount(1, $achievements);
        $this->assertEquals('Career Milestone Achievement', $achievements[0]['title']);
        $this->assertEquals('promotion', $achievements[0]['achievement_type']);
        $this->assertArrayHasKey('user_profile', $achievements[0]);
        $this->assertArrayHasKey('career_data', $achievements[0]);
    }

    public function test_discussion_and_networking_integration()
    {
        // Create discussion thread
        $discussion = Discussion::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'title' => 'Career Advice Thread',
            'content' => 'Looking for advice on transitioning to management',
            'category' => 'career_advice',
            'is_active' => true,
        ]);

        // Create discussion likes and comments
        DiscussionLike::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'discussion_id' => $discussion->id,
            'user_id' => $this->graduate2->id,
        ]);

        Comment::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'discussion_id' => $discussion->id,
            'user_id' => $this->graduate2->id,
            'content' => 'Great question! I can share my experience...',
        ]);

        // Test discussion integration with user profiles
        $response = $this->actingAs($this->graduate1)
            ->getJson("/api/alumni/discussions/{$discussion->id}");

        $response->assertStatus(200);
        $discussionData = $response->json('data');

        $this->assertEquals('Career Advice Thread', $discussionData['title']);
        $this->assertArrayHasKey('user_profile', $discussionData);
        $this->assertArrayHasKey('comments', $discussionData);
        $this->assertArrayHasKey('likes', $discussionData);
        $this->assertCount(1, $discussionData['comments']);
        $this->assertGreaterThan(0, $discussionData['likes']);
    }

    public function test_employer_partnership_data_flow()
    {
        // Create employer partnership data
        $employer1 = Employer::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'company_id' => $this->company1->id,
            'partnership_level' => 'premium',
            'contact_person' => 'HR Manager',
            'partnership_start_date' => '2020-01-01',
            'job_posting_access' => true,
        ]);

        $employer2 = Employer::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'company_id' => $this->company2->id,
            'partnership_level' => 'basic',
            'contact_person' => 'Recruitment Coordinator',
            'partnership_start_date' => '2021-03-01',
            'job_posting_access' => true,
        ]);

        // Test employer data flow to alumni job portal
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/job-opportunities');

        $response->assertStatus(200);
        $jobData = $response->json('data');

        $this->assertArrayHasKey('premium_employers', $jobData);
        $this->assertArrayHasKey('partner_companies', $jobData);
        $this->assertArrayHasKey('available_positions', $jobData);
        $this->assertGreaterThan(0, count($jobData['premium_employers']));
    }

    public function test_cross_tenant_data_isolation_in_social_features()
    {
        // Create data in both tenants
        $timeline1 = CareerTimeline::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $this->graduate1->id,
            'title' => 'Tenant 1 Timeline',
            'is_public' => true,
        ]);

        $timeline2 = CareerTimeline::factory()->create([
            'tenant_id' => $this->tenant2->id,
            'user_id' => $this->alumni1->id,
            'title' => 'Tenant 2 Timeline',
            'is_public' => true,
        ]);

        // Test tenant 1 can only see tenant 1 data
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/social-timeline');

        $response->assertStatus(200);
        $tenant1Data = $response->json('data');

        $this->assertCount(1, $tenant1Data);
        $this->assertEquals('Tenant 1 Timeline', $tenant1Data[0]['title']);
        $this->assertEquals($this->tenant1->id, $tenant1Data[0]['tenant_id']);

        // Test tenant 2 can only see tenant 2 data
        $response = $this->actingAs($this->alumni1)
            ->getJson('/api/alumni/social-timeline');

        $response->assertStatus(200);
        $tenant2Data = $response->json('data');

        $this->assertCount(1, $tenant2Data);
        $this->assertEquals('Tenant 2 Timeline', $tenant2Data[0]['title']);
        $this->assertEquals($this->tenant2->id, $tenant2Data[0]['tenant_id']);

        // Verify no cross-contamination
        $tenant1Titles = array_column($tenant1Data, 'title');
        $tenant2Titles = array_column($tenant2Data, 'title');

        $this->assertNotContains('Tenant 2 Timeline', $tenant1Titles);
        $this->assertNotContains('Tenant 1 Timeline', $tenant2Titles);
    }

    public function test_graduate_to_alumni_status_progression_tracking()
    {
        // Test the progression from graduate to alumni status
        $recentGraduate = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'recent-grad@test.edu',
            'graduation_year' => 2023,
            'is_alumni' => false,
        ]);

        // Create initial career data for recent graduate
        $initialCareer = CareerPath::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $recentGraduate->id,
            'job_title' => 'Junior Developer',
            'start_date' => '2023-06-01',
        ]);

        // Test initial status
        $response = $this->actingAs($recentGraduate)
            ->getJson('/api/alumni/status');

        $response->assertStatus(200);
        $statusData = $response->json('data');

        $this->assertEquals('recent_graduate', $statusData['current_status']);
        $this->assertLessThan(12, $statusData['months_since_graduation']);

        // Simulate time progression and status change
        $recentGraduate->update(['is_alumni' => true]);

        $response = $this->actingAs($recentGraduate)
            ->getJson('/api/alumni/status');

        $response->assertStatus(200);
        $updatedStatus = $response->json('data');

        $this->assertEquals('alumni', $updatedStatus['current_status']);
        $this->assertTrue($updatedStatus['eligible_for_alumni_features']);
    }

    public function test_mentor_mentee_matching_integration()
    {
        // Create mentor-mentee relationships based on career data
        $mentor = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'mentor@test.edu',
            'graduation_year' => 2015,
            'is_alumni' => true,
        ]);

        $mentee = User::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'email' => 'mentee@test.edu',
            'graduation_year' => 2022,
            'is_alumni' => true,
        ]);

        // Create mentor's career data
        CareerPath::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'user_id' => $mentor->id,
            'job_title' => 'Senior Engineering Manager',
            'start_date' => '2015-06-01',
            'years_experience' => 8,
        ]);

        // Test mentor-mentee matching algorithm
        $response = $this->actingAs($mentee)
            ->getJson('/api/alumni/mentor-matching');

        $response->assertStatus(200);
        $matches = $response->json('data');

        $this->assertArrayHasKey('recommended_mentors', $matches);
        $this->assertArrayHasKey('match_score', $matches);
        $this->assertArrayHasKey('shared_interests', $matches);
        $this->assertGreaterThan(0, count($matches['recommended_mentors']));
    }

    public function test_alumni_event_integration_with_career_data()
    {
        // Create alumni events and test integration with career data
        $networkingEvent = \App\Models\Event::factory()->create([
            'tenant_id' => $this->tenant1->id,
            'title' => 'Tech Alumni Networking Mixer',
            'event_type' => 'networking',
            'target_audience' => 'tech_alumni',
            'event_date' => '2024-02-15',
            'max_attendees' => 50,
        ]);

        // Test event recommendations based on career data
        $response = $this->actingAs($this->graduate1)
            ->getJson('/api/alumni/event-recommendations');

        $response->assertStatus(200);
        $recommendations = $response->json('data');

        $this->assertArrayHasKey('recommended_events', $recommendations);
        $this->assertArrayHasKey('career_alignment_score', $recommendations);
        $this->assertArrayHasKey('networking_opportunities', $recommendations);
        $this->assertGreaterThan(0, $recommendations['career_alignment_score']);
    }
}