<?php
// ABOUTME: Integration tests for schema-based tenancy services working together
// ABOUTME: Tests service interactions, data flow, and end-to-end functionality

namespace Tests\Integration;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\LandingPage;
use App\Models\Template;
use App\Models\Lead;
use App\Services\TenantContextService;
use App\Services\TenantSchemaService;
use App\Services\LeadScoringService;
use App\Services\LandingPageService;
use App\Services\AnalyticsService;
use App\Services\LeadManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaBasedServicesIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TenantContextService $tenantContext;
    protected TenantSchemaService $tenantSchema;
    protected LeadScoringService $leadScoring;
    protected LandingPageService $landingPage;
    protected AnalyticsService $analytics;
    protected LeadManagementService $leadManagement;
    
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantContext = app(TenantContextService::class);
        $this->tenantSchema = app(TenantSchemaService::class);
        $this->leadScoring = app(LeadScoringService::class);
        $this->landingPage = app(LandingPageService::class);
        $this->analytics = app(AnalyticsService::class);
        $this->leadManagement = app(LeadManagementService::class);
        
        $this->setupTestEnvironment();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestEnvironment();
        parent::tearDown();
    }

    protected function setupTestEnvironment(): void
    {
        // Create test tenants
        $this->tenant1 = Tenant::create([
            'name' => 'Integration Test University 1',
            'slug' => 'integration-test-1',
            'domain' => 'integration1.test.com',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Integration Test University 2',
            'slug' => 'integration-test-2',
            'domain' => 'integration2.test.com',
            'status' => 'active',
        ]);

        // Create schemas and migrate
        $this->tenantSchema->createSchema($this->tenant1->id);
        $this->tenantSchema->createSchema($this->tenant2->id);
        $this->tenantSchema->migrateSchema($this->tenant1->id);
        $this->tenantSchema->migrateSchema($this->tenant2->id);

        // Create test users
        $this->createTestUsers();
    }

    protected function createTestUsers(): void
    {
        // Create user in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $this->user1 = User::create([
            'name' => 'Integration Test User 1',
            'email' => 'user1@integration1.test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Create user in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $this->user2 = User::create([
            'name' => 'Integration Test User 2',
            'email' => 'user2@integration2.test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    protected function cleanupTestEnvironment(): void
    {
        try {
            $this->tenantSchema->dropSchema($this->tenant1->id);
            $this->tenantSchema->dropSchema($this->tenant2->id);
        } catch (\Exception $e) {
            // Schemas might not exist, ignore
        }
    }

    /** @test */
    public function it_creates_complete_lead_generation_workflow(): void
    {
        // Set up tenant 1 context
        $this->tenantContext->setCurrentTenant($this->tenant1);
        
        // Create course and graduate data
        $course = Course::create([
            'title' => 'Computer Science',
            'code' => 'CS-101',
            'description' => 'Introduction to Computer Science',
        ]);

        $graduate = Graduate::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'course_id' => $course->id,
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Create job posting
        $job = Job::create([
            'title' => 'Software Developer',
            'description' => 'Entry level software developer position',
            'company' => 'Tech Corp',
            'location' => 'San Francisco, CA',
            'salary_min' => 80000,
            'salary_max' => 120000,
            'requirements' => ['Bachelor\'s degree', 'Programming skills'],
        ]);

        // Create template for landing page
        $template = Template::create([
            'name' => 'Job Application Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'job_application',
            'content' => [
                'title' => 'Apply for {{job_title}}',
                'description' => 'Join {{company}} as a {{job_title}}',
                'form_fields' => ['name', 'email', 'resume'],
            ],
        ]);

        // Create landing page from template
        $landingPageData = $this->landingPage->createFromTemplate($template->id, [
            'title' => 'Apply for Software Developer Position',
            'job_id' => $job->id,
            'customizations' => [
                'job_title' => $job->title,
                'company' => $job->company,
            ],
        ]);

        $this->assertInstanceOf(LandingPage::class, $landingPageData);
        $this->assertEquals('Apply for Software Developer Position', $landingPageData->title);

        // Simulate form submission to create lead
        $leadData = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1234567890',
            'source' => 'landing_page',
            'landing_page_id' => $landingPageData->id,
            'job_id' => $job->id,
        ];

        $submissionResult = $this->landingPage->handleFormSubmission($landingPageData->id, $leadData);
        $this->assertTrue($submissionResult['success']);

        // Verify lead was created
        $lead = Lead::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($lead);
        $this->assertEquals('Jane Smith', $lead->name);

        // Score the lead
        $scoringResult = $this->leadScoring->scoreLeadForJob($lead->id, $job->id);
        $this->assertIsArray($scoringResult);
        $this->assertArrayHasKey('score', $scoringResult);
        $this->assertArrayHasKey('factors', $scoringResult);

        // Get analytics for the workflow
        $analytics = $this->analytics->getEngagementMetrics();
        $this->assertIsArray($analytics);
        
        $leadAnalytics = $this->leadScoring->getScoringAnalytics();
        $this->assertIsArray($leadAnalytics);
        $this->assertArrayHasKey('total_leads', $leadAnalytics);
    }

    /** @test */
    public function it_maintains_data_isolation_across_tenant_workflows(): void
    {
        // Create workflow in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        
        $course1 = Course::create([
            'title' => 'Tenant 1 Course',
            'code' => 'T1-101',
            'description' => 'Course for tenant 1',
        ]);

        $job1 = Job::create([
            'title' => 'Tenant 1 Job',
            'description' => 'Job for tenant 1',
            'company' => 'Tenant 1 Corp',
            'location' => 'Location 1',
        ]);

        $template1 = Template::create([
            'name' => 'Tenant 1 Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'content' => ['title' => 'Tenant 1 Landing'],
        ]);

        $landingPage1 = $this->landingPage->createFromTemplate($template1->id, [
            'title' => 'Tenant 1 Landing Page',
        ]);

        // Create workflow in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        
        $course2 = Course::create([
            'title' => 'Tenant 2 Course',
            'code' => 'T2-101',
            'description' => 'Course for tenant 2',
        ]);

        $job2 = Job::create([
            'title' => 'Tenant 2 Job',
            'description' => 'Job for tenant 2',
            'company' => 'Tenant 2 Corp',
            'location' => 'Location 2',
        ]);

        $template2 = Template::create([
            'name' => 'Tenant 2 Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'content' => ['title' => 'Tenant 2 Landing'],
        ]);

        $landingPage2 = $this->landingPage->createFromTemplate($template2->id, [
            'title' => 'Tenant 2 Landing Page',
        ]);

        // Verify tenant 1 isolation
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $this->assertCount(1, Course::all());
        $this->assertCount(1, Job::all());
        $this->assertCount(1, Template::all());
        $this->assertCount(1, LandingPage::all());
        $this->assertEquals('Tenant 1 Course', Course::first()->title);
        $this->assertEquals('Tenant 1 Job', Job::first()->title);

        // Verify tenant 2 isolation
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $this->assertCount(1, Course::all());
        $this->assertCount(1, Job::all());
        $this->assertCount(1, Template::all());
        $this->assertCount(1, LandingPage::all());
        $this->assertEquals('Tenant 2 Course', Course::first()->title);
        $this->assertEquals('Tenant 2 Job', Job::first()->title);
    }

    /** @test */
    public function it_handles_cross_service_analytics_correctly(): void
    {
        $this->tenantContext->setCurrentTenant($this->tenant1);
        
        // Create comprehensive test data
        $course = Course::create([
            'title' => 'Analytics Test Course',
            'code' => 'ATC-101',
            'description' => 'Course for analytics testing',
        ]);

        $graduates = [];
        for ($i = 1; $i <= 5; $i++) {
            $graduates[] = Graduate::create([
                'name' => "Graduate {$i}",
                'email' => "graduate{$i}@test.com",
                'course_id' => $course->id,
                'graduation_year' => 2023,
                'employment_status' => $i % 2 === 0 ? 'employed' : 'unemployed',
            ]);
        }

        $jobs = [];
        for ($i = 1; $i <= 3; $i++) {
            $jobs[] = Job::create([
                'title' => "Job {$i}",
                'description' => "Test job {$i}",
                'company' => "Company {$i}",
                'location' => "Location {$i}",
            ]);
        }

        $template = Template::create([
            'name' => 'Analytics Template',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'analytics',
            'content' => ['title' => 'Analytics Landing'],
        ]);

        $landingPage = $this->landingPage->createFromTemplate($template->id, [
            'title' => 'Analytics Landing Page',
        ]);

        // Create leads through form submissions
        for ($i = 1; $i <= 10; $i++) {
            $leadData = [
                'name' => "Lead {$i}",
                'email' => "lead{$i}@test.com",
                'phone' => "+123456789{$i}",
                'source' => 'landing_page',
                'landing_page_id' => $landingPage->id,
                'job_id' => $jobs[($i - 1) % 3]->id,
            ];

            $this->landingPage->handleFormSubmission($landingPage->id, $leadData);
        }

        // Get analytics from different services
        $engagementMetrics = $this->analytics->getEngagementMetrics();
        $scoringAnalytics = $this->leadScoring->getScoringAnalytics();
        $alumniActivity = $this->analytics->getAlumniActivity();

        // Verify analytics data
        $this->assertIsArray($engagementMetrics);
        $this->assertIsArray($scoringAnalytics);
        $this->assertIsArray($alumniActivity);
        
        $this->assertArrayHasKey('total_leads', $scoringAnalytics);
        $this->assertEquals(10, $scoringAnalytics['total_leads']);
        
        // Verify cross-service data consistency
        $leads = Lead::all();
        $this->assertCount(10, $leads);
        
        foreach ($leads as $lead) {
            $this->assertNotNull($lead->landing_page_id);
            $this->assertNotNull($lead->job_id);
        }
    }

    /** @test */
    public function it_handles_service_failures_gracefully(): void
    {
        $this->tenantContext->setCurrentTenant($this->tenant1);
        
        // Test with invalid template ID
        $result = $this->landingPage->createFromTemplate(999999, [
            'title' => 'Invalid Template Test',
        ]);
        
        $this->assertNull($result);
        
        // Test form submission with invalid landing page ID
        $submissionResult = $this->landingPage->handleFormSubmission(999999, [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->assertFalse($submissionResult['success']);
        $this->assertArrayHasKey('error', $submissionResult);
        
        // Test lead scoring with invalid IDs
        $scoringResult = $this->leadScoring->scoreLeadForJob(999999, 999999);
        $this->assertNull($scoringResult);
    }

    /** @test */
    public function it_maintains_performance_across_tenant_switches(): void
    {
        // Create data in both tenants
        $this->createTestDataInBothTenants();
        
        $startTime = microtime(true);
        
        // Perform multiple tenant switches and operations
        for ($i = 0; $i < 10; $i++) {
            // Switch to tenant 1
            $this->tenantContext->setCurrentTenant($this->tenant1);
            $courses1 = Course::all();
            $analytics1 = $this->analytics->getEngagementMetrics();
            
            // Switch to tenant 2
            $this->tenantContext->setCurrentTenant($this->tenant2);
            $courses2 = Course::all();
            $analytics2 = $this->analytics->getEngagementMetrics();
            
            // Verify data isolation maintained
            $this->assertNotEquals($courses1->count(), $courses2->count());
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Performance should be reasonable (less than 5 seconds for 20 operations)
        $this->assertLessThan(5.0, $executionTime);
    }

    protected function createTestDataInBothTenants(): void
    {
        // Create different amounts of data in each tenant
        $this->tenantContext->setCurrentTenant($this->tenant1);
        for ($i = 1; $i <= 3; $i++) {
            Course::create([
                'title' => "Tenant 1 Course {$i}",
                'code' => "T1C-{$i}",
                'description' => "Course {$i} for tenant 1",
            ]);
        }
        
        $this->tenantContext->setCurrentTenant($this->tenant2);
        for ($i = 1; $i <= 5; $i++) {
            Course::create([
                'title' => "Tenant 2 Course {$i}",
                'code' => "T2C-{$i}",
                'description' => "Course {$i} for tenant 2",
            ]);
        }
    }
}