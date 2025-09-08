<?php
// ABOUTME: Feature tests for schema-based tenancy migration commands and services
// ABOUTME: Comprehensive test suite covering migration, validation, and rollback functionality

namespace Tests\Feature;

use App\Console\Commands\MigrateToSchemaTenancy;
use App\Models\Tenant;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\TenantContextService;
use App\Services\SchemaUtilityService;
use App\Services\MigrationValidationService;
use App\Services\MigrationRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrationCommandTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected TenantContextService $tenantService;
    protected SchemaUtilityService $schemaUtility;
    protected MigrationValidationService $validationService;
    protected MigrationRollbackService $rollbackService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantService = app(TenantContextService::class);
        $this->schemaUtility = app(SchemaUtilityService::class);
        $this->validationService = app(MigrationValidationService::class);
        $this->rollbackService = app(MigrationRollbackService::class);
        
        // Set up test storage
        Storage::fake('local');
        
        // Create hybrid tables with tenant_id columns for testing
        $this->createHybridTables();
    }
    
    protected function tearDown(): void
    {
        // Clean up any test schemas
        $this->cleanupTestSchemas();
        
        parent::tearDown();
    }
    
    /** @test */
    public function it_can_migrate_single_tenant_to_schema_based_tenancy()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        
        // Act
        $exitCode = Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->assertEquals(0, $exitCode);
        
        $tenant->refresh();
        $this->assertNotNull($tenant->schema_name);
        $this->assertEquals('completed', $tenant->migration_status);
        $this->assertNotNull($tenant->migration_completed_at);
        
        // Verify schema exists
        $this->assertTrue($this->schemaUtility->schemaExists($tenant->schema_name));
        
        // Verify data was migrated
        $this->tenantService->setTenant($tenant);
        $this->assertGreaterThan(0, Student::count());
        $this->assertGreaterThan(0, Course::count());
        $this->assertGreaterThan(0, Enrollment::count());
        $this->tenantService->clearTenant();
    }
    
    /** @test */
    public function it_can_migrate_all_tenants_to_schema_based_tenancy()
    {
        // Arrange
        $tenant1 = $this->createTenantWithData();
        $tenant2 = $this->createTenantWithData();
        
        // Act
        $exitCode = Artisan::call('tenancy:migrate-to-schema', [
            '--all' => true,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->assertEquals(0, $exitCode);
        
        $tenant1->refresh();
        $tenant2->refresh();
        
        $this->assertNotNull($tenant1->schema_name);
        $this->assertNotNull($tenant2->schema_name);
        $this->assertEquals('completed', $tenant1->migration_status);
        $this->assertEquals('completed', $tenant2->migration_status);
    }
    
    /** @test */
    public function it_validates_prerequisites_before_migration()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        // Don't create any data - this should fail validation
        
        // Act
        $exitCode = Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->assertNotEquals(0, $exitCode);
        
        $tenant->refresh();
        $this->assertNull($tenant->schema_name);
        $this->assertNotEquals('completed', $tenant->migration_status);
    }
    
    /** @test */
    public function it_creates_backup_before_migration()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        
        // Act
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $backupPath = "backups/tenants/{$tenant->id}";
        $this->assertTrue(Storage::exists($backupPath));
        
        // Check that backup files exist
        $backupDirs = Storage::directories($backupPath);
        $this->assertNotEmpty($backupDirs);
        
        $latestBackup = end($backupDirs);
        $this->assertTrue(Storage::exists("{$latestBackup}/students.json"));
        $this->assertTrue(Storage::exists("{$latestBackup}/courses.json"));
        $this->assertTrue(Storage::exists("{$latestBackup}/enrollments.json"));
    }
    
    /** @test */
    public function it_validates_migration_success()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        
        // Migrate tenant
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant->fresh());
        
        // Assert
        $this->assertEquals('passed', $validation['overall_status']);
        $this->assertEmpty($validation['errors']);
        $this->assertTrue($validation['validations']['schema_structure']['valid']);
        $this->assertTrue($validation['validations']['data_migration']['valid']);
        $this->assertTrue($validation['data_integrity']['valid']);
    }
    
    /** @test */
    public function it_detects_data_integrity_issues()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        
        // Migrate tenant
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Introduce data integrity issue
        $this->tenantService->setTenant($tenant->fresh());
        DB::table('enrollments')->insert([
            'student_id' => 99999, // Non-existent student
            'course_id' => 1,
            'semester' => 'Fall',
            'academic_year' => '2024',
            'status' => 'active',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->tenantService->clearTenant();
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant->fresh());
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertFalse($validation['data_integrity']['valid']);
    }
    
    /** @test */
    public function it_can_rollback_migration()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        $originalStudentCount = Student::where('tenant_id', $tenant->id)->count();
        
        // Migrate tenant
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        $tenant->refresh();
        $this->assertNotNull($tenant->schema_name);
        
        // Act - Rollback
        $rollbackResult = $this->rollbackService->rollbackTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('completed', $rollbackResult['status']);
        $this->assertTrue($rollbackResult['backup_restored']);
        $this->assertTrue($rollbackResult['tenant_updated']);
        
        $tenant->refresh();
        $this->assertNull($tenant->schema_name);
        $this->assertEquals('rolled_back', $tenant->migration_status);
        
        // Verify data is back in hybrid tables
        $restoredStudentCount = Student::where('tenant_id', $tenant->id)->count();
        $this->assertEquals($originalStudentCount, $restoredStudentCount);
    }
    
    /** @test */
    public function it_handles_migration_failure_gracefully()
    {
        // Arrange
        $tenant = $this->createTenantWithData();
        
        // Mock a failure during schema creation
        $this->mock(SchemaUtilityService::class, function ($mock) {
            $mock->shouldReceive('createTenantSchema')
                 ->andThrow(new \Exception('Schema creation failed'));
            $mock->shouldReceive('schemaExists')->andReturn(false);
        });
        
        // Act
        $exitCode = Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->assertNotEquals(0, $exitCode);
        
        $tenant->refresh();
        $this->assertNull($tenant->schema_name);
        $this->assertEquals('failed', $tenant->migration_status);
    }
    
    /** @test */
    public function it_preserves_data_relationships_during_migration()
    {
        // Arrange
        $tenant = $this->createTenantWithComplexData();
        
        // Get original relationship counts
        $originalEnrollmentCount = Enrollment::where('tenant_id', $tenant->id)->count();
        $originalStudentCourseRelations = DB::table('enrollments')
            ->where('tenant_id', $tenant->id)
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->count();
        
        // Act
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->tenantService->setTenant($tenant->fresh());
        
        $newEnrollmentCount = Enrollment::count();
        $newStudentCourseRelations = DB::table('enrollments')
            ->join('students', 'enrollments.student_id', '=', 'students.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->count();
        
        $this->assertEquals($originalEnrollmentCount, $newEnrollmentCount);
        $this->assertEquals($originalStudentCourseRelations, $newStudentCourseRelations);
        
        $this->tenantService->clearTenant();
    }
    
    /** @test */
    public function it_generates_comprehensive_validation_report()
    {
        // Arrange
        $tenant1 = $this->createTenantWithData();
        $tenant2 = $this->createTenantWithData();
        
        // Migrate tenants
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant1->id,
            '--no-interaction' => true
        ]);
        Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant2->id,
            '--no-interaction' => true
        ]);
        
        // Act
        $validationResults = [
            $this->validationService->validateTenantMigration($tenant1->fresh()),
            $this->validationService->validateTenantMigration($tenant2->fresh())
        ];
        
        $report = $this->validationService->generateValidationReport($validationResults);
        
        // Assert
        $this->assertStringContainsString('# Tenant Migration Validation Report', $report);
        $this->assertStringContainsString('Total Tenants: 2', $report);
        $this->assertStringContainsString('Passed: 2', $report);
        $this->assertStringContainsString('Failed: 0', $report);
        $this->assertStringContainsString($tenant1->name, $report);
        $this->assertStringContainsString($tenant2->name, $report);
    }
    
    /** @test */
    public function it_handles_concurrent_migrations_safely()
    {
        // Arrange
        $tenant1 = $this->createTenantWithData();
        $tenant2 = $this->createTenantWithData();
        
        // Act - Simulate concurrent migrations
        $process1 = Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant1->id,
            '--no-interaction' => true
        ]);
        
        $process2 = Artisan::call('tenancy:migrate-to-schema', [
            '--tenant' => $tenant2->id,
            '--no-interaction' => true
        ]);
        
        // Assert
        $this->assertEquals(0, $process1);
        $this->assertEquals(0, $process2);
        
        $tenant1->refresh();
        $tenant2->refresh();
        
        $this->assertNotNull($tenant1->schema_name);
        $this->assertNotNull($tenant2->schema_name);
        $this->assertNotEquals($tenant1->schema_name, $tenant2->schema_name);
    }
    
    /**
     * Create hybrid tables for testing
     */
    protected function createHybridTables(): void
    {
        // Create students table with tenant_id
        DB::statement('CREATE TABLE IF NOT EXISTS students (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            student_id VARCHAR(50) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            date_of_birth DATE,
            enrollment_date DATE NOT NULL,
            status VARCHAR(20) DEFAULT \'active\',
            gpa DECIMAL(3,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Create courses table with tenant_id
        DB::statement('CREATE TABLE IF NOT EXISTS courses (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            course_code VARCHAR(20) NOT NULL,
            course_name VARCHAR(255) NOT NULL,
            description TEXT,
            credits INTEGER NOT NULL DEFAULT 3,
            department VARCHAR(100),
            instructor VARCHAR(255),
            max_enrollment INTEGER,
            status VARCHAR(20) DEFAULT \'active\',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Create enrollments table with tenant_id
        DB::statement('CREATE TABLE IF NOT EXISTS enrollments (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            course_id INTEGER NOT NULL,
            semester VARCHAR(20) NOT NULL,
            academic_year VARCHAR(10) NOT NULL,
            status VARCHAR(20) DEFAULT \'active\',
            grade VARCHAR(5),
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Create grades table with tenant_id
        DB::statement('CREATE TABLE IF NOT EXISTS grades (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            student_id INTEGER NOT NULL,
            course_id INTEGER NOT NULL,
            enrollment_id INTEGER NOT NULL,
            assessment_name VARCHAR(255) NOT NULL,
            assessment_type VARCHAR(50) NOT NULL,
            points_earned DECIMAL(8,2) NOT NULL,
            points_possible DECIMAL(8,2) NOT NULL,
            percentage DECIMAL(5,2) NOT NULL,
            letter_grade VARCHAR(5),
            graded_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
        
        // Create activity_logs table with tenant_id
        DB::statement('CREATE TABLE IF NOT EXISTS activity_logs (
            id SERIAL PRIMARY KEY,
            tenant_id INTEGER NOT NULL,
            log_name VARCHAR(255),
            description TEXT NOT NULL,
            subject_type VARCHAR(255),
            subject_id INTEGER,
            causer_type VARCHAR(255),
            causer_id INTEGER,
            properties JSON,
            old_values JSON,
            new_values JSON,
            event VARCHAR(255),
            ip_address INET,
            user_agent TEXT,
            session_id VARCHAR(255),
            request_id VARCHAR(255),
            severity VARCHAR(20) DEFAULT \'info\',
            is_system_generated BOOLEAN DEFAULT FALSE,
            occurred_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )');
    }
    
    /**
     * Create a tenant with sample data
     */
    protected function createTenantWithData(): Tenant
    {
        $tenant = Tenant::factory()->create();
        
        // Create students
        $students = [];
        for ($i = 1; $i <= 3; $i++) {
            $studentId = DB::table('students')->insertGetId([
                'tenant_id' => $tenant->id,
                'student_id' => "STU{$tenant->id}{$i:03d}",
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
                'email' => $this->faker->unique()->email,
                'enrollment_date' => $this->faker->date(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $students[] = $studentId;
        }
        
        // Create courses
        $courses = [];
        for ($i = 1; $i <= 2; $i++) {
            $courseId = DB::table('courses')->insertGetId([
                'tenant_id' => $tenant->id,
                'course_code' => "CS{$i:03d}",
                'course_name' => "Computer Science {$i}",
                'credits' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $courses[] = $courseId;
        }
        
        // Create enrollments
        foreach ($students as $studentId) {
            foreach ($courses as $courseId) {
                $enrollmentId = DB::table('enrollments')->insertGetId([
                    'tenant_id' => $tenant->id,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'semester' => 'Fall',
                    'academic_year' => '2024',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Create grades
                DB::table('grades')->insert([
                    'tenant_id' => $tenant->id,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'enrollment_id' => $enrollmentId,
                    'assessment_name' => 'Midterm Exam',
                    'assessment_type' => 'exam',
                    'points_earned' => 85,
                    'points_possible' => 100,
                    'percentage' => 85.0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        return $tenant;
    }
    
    /**
     * Create a tenant with complex relational data
     */
    protected function createTenantWithComplexData(): Tenant
    {
        $tenant = $this->createTenantWithData();
        
        // Add more complex relationships and data
        $students = DB::table('students')->where('tenant_id', $tenant->id)->pluck('id');
        $courses = DB::table('courses')->where('tenant_id', $tenant->id)->pluck('id');
        
        // Add activity logs
        foreach ($students as $studentId) {
            DB::table('activity_logs')->insert([
                'tenant_id' => $tenant->id,
                'log_name' => 'student_activity',
                'description' => 'Student enrolled in course',
                'subject_type' => 'App\\Models\\Student',
                'subject_id' => $studentId,
                'event' => 'enrolled',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        return $tenant;
    }
    
    /**
     * Clean up test schemas
     */
    protected function cleanupTestSchemas(): void
    {
        try {
            $schemas = DB::select("
                SELECT schema_name 
                FROM information_schema.schemata 
                WHERE schema_name LIKE 'tenant_test_%'
            ");
            
            foreach ($schemas as $schema) {
                DB::statement("DROP SCHEMA IF EXISTS {$schema->schema_name} CASCADE");
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
}