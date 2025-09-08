<?php
// ABOUTME: Unit tests for MigrationValidationService covering validation logic and reporting
// ABOUTME: Tests schema validation, data integrity checks, relationship validation, and report generation

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\MigrationValidationService;
use App\Services\TenantContextService;
use App\Services\SchemaUtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationValidationServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected MigrationValidationService $validationService;
    protected TenantContextService $tenantService;
    protected SchemaUtilityService $schemaUtility;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationService = app(MigrationValidationService::class);
        $this->tenantService = app(TenantContextService::class);
        $this->schemaUtility = app(SchemaUtilityService::class);
        
        // Create hybrid tables for testing
        $this->createHybridTables();
    }
    
    protected function tearDown(): void
    {
        // Clean up test schemas
        $this->cleanupTestSchemas();
        
        parent::tearDown();
    }
    
    /** @test */
    public function it_validates_successful_tenant_migration()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('passed', $validation['overall_status']);
        $this->assertEmpty($validation['errors']);
        $this->assertTrue($validation['validations']['schema_structure']['valid']);
        $this->assertTrue($validation['validations']['data_migration']['valid']);
        $this->assertTrue($validation['data_integrity']['valid']);
        $this->assertTrue($validation['relationships']['valid']);
        $this->assertTrue($validation['performance']['valid']);
        $this->assertTrue($validation['tenant_isolation']['valid']);
    }
    
    /** @test */
    public function it_detects_missing_schema()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'schema_name' => 'non_existent_schema',
            'migration_status' => 'completed'
        ]);
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertFalse($validation['validations']['schema_structure']['valid']);
        $this->assertStringContainsString('Schema does not exist', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_detects_incomplete_schema_structure()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        
        // Create schema but don't run migrations
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        $tenant->update([
            'schema_name' => $schemaName,
            'migration_status' => 'completed'
        ]);
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertFalse($validation['validations']['schema_structure']['valid']);
        $this->assertStringContainsString('Missing required table', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_detects_data_migration_issues()
    {
        // Arrange
        $tenant = $this->createTenantWithHybridData();
        $schemaName = "tenant_{$tenant->id}";
        
        // Create schema and run migrations but don't migrate data
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        $tenant->update([
            'schema_name' => $schemaName,
            'migration_status' => 'completed'
        ]);
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertFalse($validation['validations']['data_migration']['valid']);
        $this->assertStringContainsString('Data count mismatch', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_detects_data_integrity_violations()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        
        // Introduce data integrity issue
        $this->tenantService->setTenant($tenant);
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
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertFalse($validation['data_integrity']['valid']);
        $this->assertStringContainsString('orphaned records', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_detects_relationship_violations()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        
        // Introduce relationship violation
        $this->tenantService->setTenant($tenant);
        
        // Create enrollment without corresponding student
        DB::statement('SET CONSTRAINTS ALL DEFERRED');
        DB::table('enrollments')->insert([
            'student_id' => 88888,
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
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertFalse($validation['relationships']['valid']);
    }
    
    /** @test */
    public function it_validates_tenant_isolation()
    {
        // Arrange
        $tenant1 = $this->createTenantWithMigratedSchema();
        $tenant2 = $this->createTenantWithMigratedSchema();
        
        // Act
        $validation1 = $this->validationService->validateTenantMigration($tenant1);
        $validation2 = $this->validationService->validateTenantMigration($tenant2);
        
        // Assert
        $this->assertTrue($validation1['tenant_isolation']['valid']);
        $this->assertTrue($validation2['tenant_isolation']['valid']);
        
        // Verify tenants can't see each other's data
        $this->tenantService->setTenant($tenant1);
        $tenant1StudentCount = Student::count();
        $this->tenantService->clearTenant();
        
        $this->tenantService->setTenant($tenant2);
        $tenant2StudentCount = Student::count();
        $this->tenantService->clearTenant();
        
        $this->assertGreaterThan(0, $tenant1StudentCount);
        $this->assertGreaterThan(0, $tenant2StudentCount);
        
        // Each tenant should only see their own data
        $this->assertEquals(3, $tenant1StudentCount); // Based on createTenantWithMigratedSchema
        $this->assertEquals(3, $tenant2StudentCount);
    }
    
    /** @test */
    public function it_validates_performance_metrics()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertTrue($validation['performance']['valid']);
        $this->assertArrayHasKey('query_times', $validation['performance']);
        $this->assertArrayHasKey('index_usage', $validation['performance']);
        
        // All query times should be reasonable (< 1000ms for test data)
        foreach ($validation['performance']['query_times'] as $queryTime) {
            $this->assertLessThan(1000, $queryTime);
        }
    }
    
    /** @test */
    public function it_generates_comprehensive_validation_report()
    {
        // Arrange
        $tenant1 = $this->createTenantWithMigratedSchema();
        $tenant2 = $this->createTenantWithMigratedSchema();
        
        $validationResults = [
            $this->validationService->validateTenantMigration($tenant1),
            $this->validationService->validateTenantMigration($tenant2)
        ];
        
        // Act
        $report = $this->validationService->generateValidationReport($validationResults);
        
        // Assert
        $this->assertStringContainsString('# Tenant Migration Validation Report', $report);
        $this->assertStringContainsString('Total Tenants: 2', $report);
        $this->assertStringContainsString('Passed: 2', $report);
        $this->assertStringContainsString('Failed: 0', $report);
        $this->assertStringContainsString('## Summary Statistics', $report);
        $this->assertStringContainsString('## Tenant Details', $report);
        $this->assertStringContainsString($tenant1->name, $report);
        $this->assertStringContainsString($tenant2->name, $report);
    }
    
    /** @test */
    public function it_generates_report_with_failures()
    {
        // Arrange
        $successTenant = $this->createTenantWithMigratedSchema();
        $failTenant = Tenant::factory()->create([
            'schema_name' => 'non_existent_schema',
            'migration_status' => 'completed'
        ]);
        
        $validationResults = [
            $this->validationService->validateTenantMigration($successTenant),
            $this->validationService->validateTenantMigration($failTenant)
        ];
        
        // Act
        $report = $this->validationService->generateValidationReport($validationResults);
        
        // Assert
        $this->assertStringContainsString('Total Tenants: 2', $report);
        $this->assertStringContainsString('Passed: 1', $report);
        $this->assertStringContainsString('Failed: 1', $report);
        $this->assertStringContainsString('## Failed Validations', $report);
        $this->assertStringContainsString($failTenant->name, $report);
        $this->assertStringContainsString('Schema does not exist', $report);
    }
    
    /** @test */
    public function it_validates_specific_validation_types()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        
        // Act & Assert - Schema Structure
        $schemaValidation = $this->validationService->validateSchemaStructure($tenant);
        $this->assertTrue($schemaValidation['valid']);
        $this->assertEmpty($schemaValidation['errors']);
        
        // Act & Assert - Data Migration
        $dataValidation = $this->validationService->validateDataMigration($tenant);
        $this->assertTrue($dataValidation['valid']);
        $this->assertEmpty($dataValidation['errors']);
        
        // Act & Assert - Data Integrity
        $integrityValidation = $this->validationService->validateDataIntegrity($tenant);
        $this->assertTrue($integrityValidation['valid']);
        $this->assertEmpty($integrityValidation['errors']);
        
        // Act & Assert - Relationships
        $relationshipValidation = $this->validationService->validateRelationships($tenant);
        $this->assertTrue($relationshipValidation['valid']);
        $this->assertEmpty($relationshipValidation['errors']);
        
        // Act & Assert - Performance
        $performanceValidation = $this->validationService->validatePerformance($tenant);
        $this->assertTrue($performanceValidation['valid']);
        
        // Act & Assert - Tenant Isolation
        $isolationValidation = $this->validationService->validateTenantIsolation($tenant);
        $this->assertTrue($isolationValidation['valid']);
    }
    
    /** @test */
    public function it_handles_validation_errors_gracefully()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'schema_name' => null,
            'migration_status' => 'failed'
        ]);
        
        // Act
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Assert
        $this->assertEquals('failed', $validation['overall_status']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertStringContainsString('No schema name configured', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_validates_batch_migrations()
    {
        // Arrange
        $tenants = [];
        for ($i = 0; $i < 3; $i++) {
            $tenants[] = $this->createTenantWithMigratedSchema();
        }
        
        // Act
        $validationResults = $this->validationService->validateBatchMigration($tenants);
        
        // Assert
        $this->assertCount(3, $validationResults);
        
        foreach ($validationResults as $result) {
            $this->assertEquals('passed', $result['overall_status']);
            $this->assertEmpty($result['errors']);
        }
        
        // Verify summary
        $summary = $this->validationService->getBatchValidationSummary($validationResults);
        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(3, $summary['passed']);
        $this->assertEquals(0, $summary['failed']);
        $this->assertEquals(100, $summary['success_rate']);
    }
    
    /** @test */
    public function it_exports_validation_results_to_json()
    {
        // Arrange
        $tenant = $this->createTenantWithMigratedSchema();
        $validation = $this->validationService->validateTenantMigration($tenant);
        
        // Act
        $json = $this->validationService->exportValidationToJson($validation);
        
        // Assert
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertEquals('passed', $decoded['overall_status']);
        $this->assertArrayHasKey('validations', $decoded);
        $this->assertArrayHasKey('data_integrity', $decoded);
        $this->assertArrayHasKey('relationships', $decoded);
        $this->assertArrayHasKey('performance', $decoded);
        $this->assertArrayHasKey('tenant_isolation', $decoded);
    }
    
    /** @test */
    public function it_exports_validation_results_to_csv()
    {
        // Arrange
        $tenant1 = $this->createTenantWithMigratedSchema();
        $tenant2 = $this->createTenantWithMigratedSchema();
        
        $validationResults = [
            $this->validationService->validateTenantMigration($tenant1),
            $this->validationService->validateTenantMigration($tenant2)
        ];
        
        // Act
        $csv = $this->validationService->exportValidationToCsv($validationResults);
        
        // Assert
        $this->assertStringContainsString('Tenant ID,Tenant Name,Overall Status', $csv);
        $this->assertStringContainsString($tenant1->id, $csv);
        $this->assertStringContainsString($tenant2->id, $csv);
        $this->assertStringContainsString('passed', $csv);
        
        // Verify CSV structure
        $lines = explode("\n", trim($csv));
        $this->assertCount(3, $lines); // Header + 2 data rows
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
    }
    
    /**
     * Create a tenant with hybrid data
     */
    protected function createTenantWithHybridData(): Tenant
    {
        $tenant = Tenant::factory()->create();
        
        // Create students
        for ($i = 1; $i <= 3; $i++) {
            DB::table('students')->insert([
                'tenant_id' => $tenant->id,
                'student_id' => "STU{$tenant->id}{$i:03d}",
                'first_name' => "Student{$i}",
                'last_name' => "Test",
                'email' => "student{$i}@tenant{$tenant->id}.com",
                'enrollment_date' => now()->subDays(30),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        // Create courses
        for ($i = 1; $i <= 2; $i++) {
            DB::table('courses')->insert([
                'tenant_id' => $tenant->id,
                'course_code' => "CS{$i:03d}",
                'course_name' => "Computer Science {$i}",
                'credits' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        // Create enrollments
        $students = DB::table('students')->where('tenant_id', $tenant->id)->pluck('id');
        $courses = DB::table('courses')->where('tenant_id', $tenant->id)->pluck('id');
        
        foreach ($students as $studentId) {
            foreach ($courses as $courseId) {
                DB::table('enrollments')->insert([
                    'tenant_id' => $tenant->id,
                    'student_id' => $studentId,
                    'course_id' => $courseId,
                    'semester' => 'Fall',
                    'academic_year' => '2024',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
        
        return $tenant;
    }
    
    /**
     * Create a tenant with migrated schema and data
     */
    protected function createTenantWithMigratedSchema(): Tenant
    {
        $tenant = $this->createTenantWithHybridData();
        $schemaName = "tenant_{$tenant->id}";
        
        // Create schema and run migrations
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Migrate data
        $this->tenantService->setTenant($tenant);
        
        // Copy students
        $hybridStudents = DB::table('public.students')->where('tenant_id', $tenant->id)->get();
        foreach ($hybridStudents as $student) {
            DB::table('students')->insert([
                'student_id' => $student->student_id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'enrollment_date' => $student->enrollment_date,
                'status' => $student->status,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ]);
        }
        
        // Copy courses
        $hybridCourses = DB::table('public.courses')->where('tenant_id', $tenant->id)->get();
        foreach ($hybridCourses as $course) {
            DB::table('courses')->insert([
                'course_code' => $course->course_code,
                'course_name' => $course->course_name,
                'credits' => $course->credits,
                'status' => $course->status,
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at
            ]);
        }
        
        // Copy enrollments with proper ID mapping
        $hybridEnrollments = DB::table('public.enrollments')->where('tenant_id', $tenant->id)->get();
        foreach ($hybridEnrollments as $enrollment) {
            // Map old IDs to new IDs
            $newStudentId = DB::table('students')
                ->join('public.students as ps', 'students.student_id', '=', 'ps.student_id')
                ->where('ps.id', $enrollment->student_id)
                ->value('students.id');
                
            $newCourseId = DB::table('courses')
                ->join('public.courses as pc', 'courses.course_code', '=', 'pc.course_code')
                ->where('pc.id', $enrollment->course_id)
                ->value('courses.id');
            
            if ($newStudentId && $newCourseId) {
                DB::table('enrollments')->insert([
                    'student_id' => $newStudentId,
                    'course_id' => $newCourseId,
                    'semester' => $enrollment->semester,
                    'academic_year' => $enrollment->academic_year,
                    'status' => $enrollment->status,
                    'enrolled_at' => $enrollment->enrolled_at,
                    'created_at' => $enrollment->created_at,
                    'updated_at' => $enrollment->updated_at
                ]);
            }
        }
        
        $this->tenantService->clearTenant();
        
        // Update tenant
        $tenant->update([
            'schema_name' => $schemaName,
            'migration_status' => 'completed',
            'migration_completed_at' => now()
        ]);
        
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
                WHERE schema_name LIKE 'tenant_%'
            ");
            
            foreach ($schemas as $schema) {
                DB::statement("DROP SCHEMA IF EXISTS {$schema->schema_name} CASCADE");
            }
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
    }
}