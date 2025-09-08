<?php
// ABOUTME: Comprehensive test suite for schema-based tenancy architecture
// ABOUTME: Tests schema isolation, tenant context switching, data separation, and service functionality

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Graduate;
use App\Models\Course;
use App\Models\Job;
use App\Models\LandingPage;
use App\Models\Template;
use App\Services\TenantContextService;
use App\Services\TenantSchemaService;
use App\Services\LeadScoringService;
use App\Services\LandingPageService;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SchemaBasedTenancyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected TenantContextService $tenantContext;
    protected TenantSchemaService $tenantSchema;
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantContext = app(TenantContextService::class);
        $this->tenantSchema = app(TenantSchemaService::class);
        
        $this->createTestTenants();
        $this->createTestUsers();
    }

    protected function tearDown(): void
    {
        // Clean up test schemas
        if (isset($this->tenant1)) {
            $this->cleanupTenantSchema($this->tenant1->id);
        }
        if (isset($this->tenant2)) {
            $this->cleanupTenantSchema($this->tenant2->id);
        }
        
        parent::tearDown();
    }

    protected function createTestTenants(): void
    {
        $this->tenant1 = Tenant::create([
            'name' => 'Schema Test University 1',
            'slug' => 'schema-test-1',
            'domain' => 'schema1.test.com',
            'status' => 'active',
        ]);

        $this->tenant2 = Tenant::create([
            'name' => 'Schema Test University 2',
            'slug' => 'schema-test-2',
            'domain' => 'schema2.test.com',
            'status' => 'active',
        ]);
    }

    protected function createTestUsers(): void
    {
        // Create schemas first
        $this->tenantSchema->createSchema($this->tenant1->id);
        $this->tenantSchema->createSchema($this->tenant2->id);
        $this->tenantSchema->migrateSchema($this->tenant1->id);
        $this->tenantSchema->migrateSchema($this->tenant2->id);

        // Create users in tenant 1 schema
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $this->user1 = User::create([
            'name' => 'Test User 1',
            'email' => 'user1@schema1.test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Create users in tenant 2 schema
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $this->user2 = User::create([
            'name' => 'Test User 2',
            'email' => 'user2@schema2.test.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    protected function cleanupTenantSchema(string $tenantId): void
    {
        try {
            $this->tenantSchema->dropSchema($tenantId);
        } catch (\Exception $e) {
            // Schema might not exist, ignore
        }
    }

    /** @test */
    public function it_creates_separate_schemas_for_each_tenant(): void
    {
        $schema1 = 'tenant_' . $this->tenant1->id;
        $schema2 = 'tenant_' . $this->tenant2->id;

        // Verify schemas exist
        $schemas = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name IN (?, ?)",
            [$schema1, $schema2]
        );
        
        $this->assertCount(2, $schemas);
    }

    /** @test */
    public function it_isolates_data_between_tenant_schemas(): void
    {
        // Create data in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $course1 = Course::create([
            'title' => 'Course in Tenant 1',
            'code' => 'T1-101',
            'description' => 'Test course for tenant 1',
        ]);

        $graduate1 = Graduate::create([
            'name' => 'Graduate in Tenant 1',
            'email' => 'grad1@tenant1.com',
            'course_id' => $course1->id,
            'graduation_year' => 2023,
        ]);

        // Create data in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $course2 = Course::create([
            'title' => 'Course in Tenant 2',
            'code' => 'T2-101',
            'description' => 'Test course for tenant 2',
        ]);

        $graduate2 = Graduate::create([
            'name' => 'Graduate in Tenant 2',
            'email' => 'grad2@tenant2.com',
            'course_id' => $course2->id,
            'graduation_year' => 2023,
        ]);

        // Verify tenant 1 can only see its data
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $this->assertCount(1, Course::all());
        $this->assertCount(1, Graduate::all());
        $this->assertEquals('Course in Tenant 1', Course::first()->title);
        $this->assertEquals('Graduate in Tenant 1', Graduate::first()->name);

        // Verify tenant 2 can only see its data
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $this->assertCount(1, Course::all());
        $this->assertCount(1, Graduate::all());
        $this->assertEquals('Course in Tenant 2', Course::first()->title);
        $this->assertEquals('Graduate in Tenant 2', Graduate::first()->name);
    }

    /** @test */
    public function it_switches_database_context_correctly(): void
    {
        // Start in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals('tenant_' . $this->tenant1->id, $currentSchema);

        // Switch to tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals('tenant_' . $this->tenant2->id, $currentSchema);
    }

    /** @test */
    public function lead_scoring_service_works_with_schema_based_tenancy(): void
    {
        $leadScoringService = app(LeadScoringService::class);

        // Create test data in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $graduate1 = Graduate::create([
            'name' => 'Test Graduate 1',
            'email' => 'grad1@test.com',
            'graduation_year' => 2023,
            'employment_status' => 'employed',
        ]);

        // Create test data in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $graduate2 = Graduate::create([
            'name' => 'Test Graduate 2',
            'email' => 'grad2@test.com',
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Test analytics in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $analytics1 = $leadScoringService->getScoringAnalytics();
        $this->assertIsArray($analytics1);
        $this->assertArrayHasKey('total_leads', $analytics1);

        // Test analytics in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $analytics2 = $leadScoringService->getScoringAnalytics();
        $this->assertIsArray($analytics2);
        $this->assertArrayHasKey('total_leads', $analytics2);

        // Analytics should be different for each tenant
        $this->assertNotEquals($analytics1, $analytics2);
    }

    /** @test */
    public function landing_page_service_works_with_schema_based_tenancy(): void
    {
        $landingPageService = app(LandingPageService::class);

        // Create templates in each tenant
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $template1 = Template::create([
            'name' => 'Template 1',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'content' => ['title' => 'Welcome to Tenant 1'],
        ]);

        $this->tenantContext->setCurrentTenant($this->tenant2);
        $template2 = Template::create([
            'name' => 'Template 2',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'content' => ['title' => 'Welcome to Tenant 2'],
        ]);

        // Create landing pages from templates
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $landingPage1 = $landingPageService->createFromTemplate($template1->id, [
            'title' => 'Custom Landing Page 1',
        ]);

        $this->tenantContext->setCurrentTenant($this->tenant2);
        $landingPage2 = $landingPageService->createFromTemplate($template2->id, [
            'title' => 'Custom Landing Page 2',
        ]);

        // Verify isolation
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $this->assertCount(1, LandingPage::all());
        $this->assertEquals('Custom Landing Page 1', LandingPage::first()->title);

        $this->tenantContext->setCurrentTenant($this->tenant2);
        $this->assertCount(1, LandingPage::all());
        $this->assertEquals('Custom Landing Page 2', LandingPage::first()->title);
    }

    /** @test */
    public function analytics_service_works_with_schema_based_tenancy(): void
    {
        $analyticsService = app(AnalyticsService::class);

        // Create test data in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $course1 = Course::create([
            'title' => 'Analytics Course 1',
            'code' => 'AC-101',
            'description' => 'Test course for analytics',
        ]);
        
        Graduate::create([
            'name' => 'Analytics Graduate 1',
            'email' => 'analytics1@test.com',
            'course_id' => $course1->id,
            'graduation_year' => 2023,
            'employment_status' => 'employed',
        ]);

        // Create test data in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $course2 = Course::create([
            'title' => 'Analytics Course 2',
            'code' => 'AC-102',
            'description' => 'Test course for analytics',
        ]);
        
        Graduate::create([
            'name' => 'Analytics Graduate 2',
            'email' => 'analytics2@test.com',
            'course_id' => $course2->id,
            'graduation_year' => 2023,
            'employment_status' => 'unemployed',
        ]);

        // Test engagement metrics in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $metrics1 = $analyticsService->getEngagementMetrics();
        $this->assertIsArray($metrics1);

        // Test engagement metrics in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $metrics2 = $analyticsService->getEngagementMetrics();
        $this->assertIsArray($metrics2);

        // Metrics should be isolated per tenant
        $this->assertNotEquals($metrics1, $metrics2);
    }

    /** @test */
    public function it_handles_concurrent_tenant_operations(): void
    {
        // Simulate concurrent operations on different tenants
        $results = [];

        // Operation 1: Create data in tenant 1
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $course1 = Course::create([
            'title' => 'Concurrent Course 1',
            'code' => 'CC-101',
            'description' => 'Test concurrent operations',
        ]);
        $results['tenant1_course'] = $course1->id;

        // Operation 2: Create data in tenant 2
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $course2 = Course::create([
            'title' => 'Concurrent Course 2',
            'code' => 'CC-102',
            'description' => 'Test concurrent operations',
        ]);
        $results['tenant2_course'] = $course2->id;

        // Operation 3: Query tenant 1 data
        $this->tenantContext->setCurrentTenant($this->tenant1);
        $tenant1Courses = Course::all();
        $results['tenant1_count'] = $tenant1Courses->count();

        // Operation 4: Query tenant 2 data
        $this->tenantContext->setCurrentTenant($this->tenant2);
        $tenant2Courses = Course::all();
        $results['tenant2_count'] = $tenant2Courses->count();

        // Verify isolation maintained during concurrent operations
        $this->assertEquals(1, $results['tenant1_count']);
        $this->assertEquals(1, $results['tenant2_count']);
        $this->assertNotEquals($results['tenant1_course'], $results['tenant2_course']);
    }

    /** @test */
    public function it_validates_schema_integrity_after_operations(): void
    {
        // Perform various operations
        $this->tenantContext->setCurrentTenant($this->tenant1);
        
        Course::create([
            'title' => 'Integrity Test Course',
            'code' => 'ITC-101',
            'description' => 'Test schema integrity',
        ]);

        Graduate::create([
            'name' => 'Integrity Test Graduate',
            'email' => 'integrity@test.com',
            'graduation_year' => 2023,
        ]);

        Job::create([
            'title' => 'Integrity Test Job',
            'description' => 'Test job for schema integrity',
            'company' => 'Test Company',
            'location' => 'Test Location',
        ]);

        // Validate schema integrity
        $isValid = $this->tenantSchema->validateSchema($this->tenant1->id);
        $this->assertTrue($isValid);

        // Verify all required tables exist and have data
        $requiredTables = ['users', 'courses', 'graduates', 'jobs'];
        
        foreach ($requiredTables as $table) {
            $exists = Schema::hasTable($table);
            $this->assertTrue($exists, "Table {$table} should exist in tenant schema");
        }
    }

    /** @test */
    public function it_handles_schema_migration_rollback(): void
    {
        // Create a test migration
        $migrationPath = database_path('migrations/tenant');
        if (!file_exists($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $testMigration = $migrationPath . '/2024_01_01_000000_test_schema_rollback.php';
        file_put_contents($testMigration, $this->getTestMigrationContent());

        try {
            // Run migration
            $this->tenantSchema->migrateSchema($this->tenant1->id, [$testMigration]);
            
            // Verify table exists
            $this->tenantContext->setCurrentTenant($this->tenant1);
            $this->assertTrue(Schema::hasTable('test_rollback_table'));

            // Rollback migration
            $this->tenantSchema->rollbackSchema($this->tenant1->id, 1);
            
            // Verify table no longer exists
            $this->assertFalse(Schema::hasTable('test_rollback_table'));
        } finally {
            // Clean up test migration file
            if (file_exists($testMigration)) {
                unlink($testMigration);
            }
        }
    }

    protected function getTestMigrationContent(): string
    {
        return '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create("test_rollback_table", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists("test_rollback_table");
    }
};';
    }
}