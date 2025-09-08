<?php
// ABOUTME: Unit tests for MigrationRollbackService covering rollback logic and recovery procedures
// ABOUTME: Tests rollback validation, data restoration, schema cleanup, and error handling

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\MigrationRollbackService;
use App\Services\TenantContextService;
use App\Services\SchemaUtilityService;
use App\Services\MigrationValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrationRollbackServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected MigrationRollbackService $rollbackService;
    protected TenantContextService $tenantService;
    protected SchemaUtilityService $schemaUtility;
    protected MigrationValidationService $validationService;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->rollbackService = app(MigrationRollbackService::class);
        $this->tenantService = app(TenantContextService::class);
        $this->schemaUtility = app(SchemaUtilityService::class);
        $this->validationService = app(MigrationValidationService::class);
        
        // Create hybrid tables for testing
        $this->createHybridTables();
        
        // Setup storage for backups
        Storage::fake('backups');
    }
    
    protected function tearDown(): void
    {
        // Clean up test schemas
        $this->cleanupTestSchemas();
        
        parent::tearDown();
    }
    
    /** @test */
    public function it_validates_rollback_prerequisites_successfully()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        
        // Act
        $validation = $this->rollbackService->validateRollbackPrerequisites($tenant);
        
        // Assert
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertTrue($validation['checks']['tenant_exists']);
        $this->assertTrue($validation['checks']['schema_exists']);
        $this->assertTrue($validation['checks']['migration_completed']);
        $this->assertTrue($validation['checks']['backup_available']);
    }
    
    /** @test */
    public function it_fails_validation_for_non_migrated_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'migration_status' => 'pending'
        ]);
        
        // Act
        $validation = $this->rollbackService->validateRollbackPrerequisites($tenant);
        
        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertFalse($validation['checks']['migration_completed']);
        $this->assertStringContainsString('not completed', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_fails_validation_for_missing_schema()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'schema_name' => 'non_existent_schema',
            'migration_status' => 'completed'
        ]);
        
        // Act
        $validation = $this->rollbackService->validateRollbackPrerequisites($tenant);
        
        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertFalse($validation['checks']['schema_exists']);
        $this->assertStringContainsString('Schema does not exist', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_creates_emergency_backup_before_rollback()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        
        // Act
        $backupPath = $this->rollbackService->createEmergencyBackup($tenant);
        
        // Assert
        $this->assertNotNull($backupPath);
        $this->assertStringContainsString('emergency_backup', $backupPath);
        $this->assertStringContainsString($tenant->id, $backupPath);
        Storage::disk('backups')->assertExists($backupPath);
        
        // Verify backup content
        $backupContent = Storage::disk('backups')->get($backupPath);
        $this->assertStringContainsString('-- Emergency Backup', $backupContent);
        $this->assertStringContainsString('CREATE SCHEMA', $backupContent);
        $this->assertStringContainsString('INSERT INTO', $backupContent);
    }
    
    /** @test */
    public function it_restores_data_from_backup_successfully()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $backupPath = $this->createTestBackup($tenant);
        
        // Clear existing data
        $this->tenantService->setTenant($tenant);
        DB::table('students')->delete();
        DB::table('courses')->delete();
        DB::table('enrollments')->delete();
        $this->tenantService->clearTenant();
        
        // Act
        $result = $this->rollbackService->restoreFromBackup($tenant, $backupPath);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        
        // Verify data was restored
        $this->tenantService->setTenant($tenant);
        $this->assertGreaterThan(0, DB::table('students')->count());
        $this->assertGreaterThan(0, DB::table('courses')->count());
        $this->assertGreaterThan(0, DB::table('enrollments')->count());
        $this->tenantService->clearTenant();
    }
    
    /** @test */
    public function it_migrates_data_back_to_hybrid_tables()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        
        // Clear hybrid data to simulate clean state
        DB::table('students')->where('tenant_id', $tenant->id)->delete();
        DB::table('courses')->where('tenant_id', $tenant->id)->delete();
        DB::table('enrollments')->where('tenant_id', $tenant->id)->delete();
        
        // Act
        $result = $this->rollbackService->migrateDataBackToHybrid($tenant);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        
        // Verify data was migrated back
        $this->assertGreaterThan(0, DB::table('students')->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(0, DB::table('courses')->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(0, DB::table('enrollments')->where('tenant_id', $tenant->id)->count());
        
        // Verify data integrity
        $hybridStudents = DB::table('students')->where('tenant_id', $tenant->id)->count();
        $this->tenantService->setTenant($tenant);
        $schemaStudents = DB::table('students')->count();
        $this->tenantService->clearTenant();
        
        $this->assertEquals($schemaStudents, $hybridStudents);
    }
    
    /** @test */
    public function it_updates_tenant_configuration_after_rollback()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $originalSchemaName = $tenant->schema_name;
        
        // Act
        $result = $this->rollbackService->updateTenantConfiguration($tenant);
        
        // Assert
        $this->assertTrue($result['success']);
        
        // Refresh tenant from database
        $tenant->refresh();
        
        $this->assertNull($tenant->schema_name);
        $this->assertEquals('hybrid', $tenant->migration_status);
        $this->assertNotNull($tenant->rollback_completed_at);
        $this->assertNotNull($tenant->rollback_reason);
        
        // Verify configuration history
        $this->assertArrayHasKey('previous_schema_name', $tenant->rollback_metadata);
        $this->assertEquals($originalSchemaName, $tenant->rollback_metadata['previous_schema_name']);
    }
    
    /** @test */
    public function it_drops_tenant_schema_safely()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $schemaName = $tenant->schema_name;
        
        // Verify schema exists
        $schemaExists = DB::select("
            SELECT 1 FROM information_schema.schemata 
            WHERE schema_name = ?
        ", [$schemaName]);
        $this->assertNotEmpty($schemaExists);
        
        // Act
        $result = $this->rollbackService->dropTenantSchema($tenant);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        
        // Verify schema was dropped
        $schemaExists = DB::select("
            SELECT 1 FROM information_schema.schemata 
            WHERE schema_name = ?
        ", [$schemaName]);
        $this->assertEmpty($schemaExists);
    }
    
    /** @test */
    public function it_validates_rollback_success()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        
        // Perform rollback steps
        $this->rollbackService->migrateDataBackToHybrid($tenant);
        $this->rollbackService->updateTenantConfiguration($tenant);
        $this->rollbackService->dropTenantSchema($tenant);
        
        // Act
        $validation = $this->rollbackService->validateRollbackSuccess($tenant);
        
        // Assert
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertTrue($validation['checks']['schema_dropped']);
        $this->assertTrue($validation['checks']['data_restored']);
        $this->assertTrue($validation['checks']['configuration_updated']);
        $this->assertTrue($validation['checks']['data_integrity']);
    }
    
    /** @test */
    public function it_performs_complete_tenant_rollback()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $originalSchemaName = $tenant->schema_name;
        
        // Act
        $result = $this->rollbackService->rollbackTenantMigration($tenant, 'Test rollback');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        $this->assertArrayHasKey('backup_path', $result);
        $this->assertArrayHasKey('validation', $result);
        
        // Verify tenant state
        $tenant->refresh();
        $this->assertEquals('hybrid', $tenant->migration_status);
        $this->assertNull($tenant->schema_name);
        $this->assertNotNull($tenant->rollback_completed_at);
        $this->assertEquals('Test rollback', $tenant->rollback_reason);
        
        // Verify schema was dropped
        $schemaExists = DB::select("
            SELECT 1 FROM information_schema.schemata 
            WHERE schema_name = ?
        ", [$originalSchemaName]);
        $this->assertEmpty($schemaExists);
        
        // Verify data was restored to hybrid tables
        $this->assertGreaterThan(0, DB::table('students')->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(0, DB::table('courses')->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(0, DB::table('enrollments')->where('tenant_id', $tenant->id)->count());
    }
    
    /** @test */
    public function it_handles_rollback_errors_gracefully()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'schema_name' => 'non_existent_schema',
            'migration_status' => 'completed'
        ]);
        
        // Act
        $result = $this->rollbackService->rollbackTenantMigration($tenant, 'Test error handling');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Prerequisites validation failed', implode(', ', $result['errors']));
    }
    
    /** @test */
    public function it_rolls_back_all_tenants_in_batch()
    {
        // Arrange
        $tenants = [];
        for ($i = 0; $i < 3; $i++) {
            $tenants[] = $this->createMigratedTenant();
        }
        
        // Act
        $results = $this->rollbackService->rollbackAllTenants('Batch rollback test');
        
        // Assert
        $this->assertCount(3, $results);
        
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
            $this->assertEmpty($result['errors']);
        }
        
        // Verify all tenants were rolled back
        foreach ($tenants as $tenant) {
            $tenant->refresh();
            $this->assertEquals('hybrid', $tenant->migration_status);
            $this->assertNull($tenant->schema_name);
        }
    }
    
    /** @test */
    public function it_generates_rollback_report()
    {
        // Arrange
        $tenant1 = $this->createMigratedTenant();
        $tenant2 = $this->createMigratedTenant();
        
        $rollbackResults = [
            $this->rollbackService->rollbackTenantMigration($tenant1, 'Test rollback 1'),
            $this->rollbackService->rollbackTenantMigration($tenant2, 'Test rollback 2')
        ];
        
        // Act
        $report = $this->rollbackService->generateRollbackReport($rollbackResults);
        
        // Assert
        $this->assertStringContainsString('# Tenant Migration Rollback Report', $report);
        $this->assertStringContainsString('Total Tenants: 2', $report);
        $this->assertStringContainsString('Successful: 2', $report);
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
        $successTenant = $this->createMigratedTenant();
        $failTenant = Tenant::factory()->create([
            'schema_name' => 'non_existent_schema',
            'migration_status' => 'completed'
        ]);
        
        $rollbackResults = [
            $this->rollbackService->rollbackTenantMigration($successTenant, 'Success test'),
            $this->rollbackService->rollbackTenantMigration($failTenant, 'Failure test')
        ];
        
        // Act
        $report = $this->rollbackService->generateRollbackReport($rollbackResults);
        
        // Assert
        $this->assertStringContainsString('Total Tenants: 2', $report);
        $this->assertStringContainsString('Successful: 1', $report);
        $this->assertStringContainsString('Failed: 1', $report);
        $this->assertStringContainsString('## Failed Rollbacks', $report);
        $this->assertStringContainsString($failTenant->name, $report);
        $this->assertStringContainsString('Prerequisites validation failed', $report);
    }
    
    /** @test */
    public function it_handles_partial_rollback_recovery()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        
        // Simulate partial rollback state
        $tenant->update([
            'migration_status' => 'rolling_back',
            'rollback_started_at' => now()->subMinutes(30)
        ]);
        
        // Act
        $result = $this->rollbackService->recoverPartialRollback($tenant);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
        
        // Verify recovery completed the rollback
        $tenant->refresh();
        $this->assertEquals('hybrid', $tenant->migration_status);
        $this->assertNotNull($tenant->rollback_completed_at);
    }
    
    /** @test */
    public function it_exports_rollback_results_to_json()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $result = $this->rollbackService->rollbackTenantMigration($tenant, 'JSON export test');
        
        // Act
        $json = $this->rollbackService->exportRollbackToJson($result);
        
        // Assert
        $this->assertJson($json);
        
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('tenant_id', $decoded);
        $this->assertArrayHasKey('backup_path', $decoded);
        $this->assertArrayHasKey('validation', $decoded);
        $this->assertArrayHasKey('completed_at', $decoded);
    }
    
    /** @test */
    public function it_exports_rollback_results_to_csv()
    {
        // Arrange
        $tenant1 = $this->createMigratedTenant();
        $tenant2 = $this->createMigratedTenant();
        
        $rollbackResults = [
            $this->rollbackService->rollbackTenantMigration($tenant1, 'CSV test 1'),
            $this->rollbackService->rollbackTenantMigration($tenant2, 'CSV test 2')
        ];
        
        // Act
        $csv = $this->rollbackService->exportRollbackToCsv($rollbackResults);
        
        // Assert
        $this->assertStringContainsString('Tenant ID,Tenant Name,Success', $csv);
        $this->assertStringContainsString($tenant1->id, $csv);
        $this->assertStringContainsString($tenant2->id, $csv);
        $this->assertStringContainsString('true', $csv);
        
        // Verify CSV structure
        $lines = explode("\n", trim($csv));
        $this->assertCount(3, $lines); // Header + 2 data rows
    }
    
    /** @test */
    public function it_cleans_up_orphaned_schemas()
    {
        // Arrange
        $tenant = $this->createMigratedTenant();
        $schemaName = $tenant->schema_name;
        
        // Simulate orphaned schema (tenant deleted but schema remains)
        $tenant->delete();
        
        // Act
        $result = $this->rollbackService->cleanupOrphanedSchemas();
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['cleaned_count']);
        $this->assertContains($schemaName, $result['cleaned_schemas']);
        
        // Verify schema was dropped
        $schemaExists = DB::select("
            SELECT 1 FROM information_schema.schemata 
            WHERE schema_name = ?
        ", [$schemaName]);
        $this->assertEmpty($schemaExists);
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
     * Create a migrated tenant with schema and data
     */
    protected function createMigratedTenant(): Tenant
    {
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        
        // Create and populate hybrid data
        $this->createHybridDataForTenant($tenant);
        
        // Create schema and migrate
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Migrate data to schema
        $this->migrateDataToSchema($tenant, $schemaName);
        
        // Update tenant
        $tenant->update([
            'schema_name' => $schemaName,
            'migration_status' => 'completed',
            'migration_completed_at' => now()
        ]);
        
        // Create backup
        $this->createTestBackup($tenant);
        
        return $tenant;
    }
    
    /**
     * Create hybrid data for a tenant
     */
    protected function createHybridDataForTenant(Tenant $tenant): void
    {
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
    }
    
    /**
     * Migrate data to tenant schema
     */
    protected function migrateDataToSchema(Tenant $tenant, string $schemaName): void
    {
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
    }
    
    /**
     * Create a test backup for a tenant
     */
    protected function createTestBackup(Tenant $tenant): string
    {
        $backupPath = "tenant_{$tenant->id}/backup_" . now()->format('Y_m_d_H_i_s') . '.sql';
        
        $backupContent = "-- Test Backup for Tenant {$tenant->id}\n";
        $backupContent .= "-- Created at: " . now() . "\n\n";
        $backupContent .= "CREATE SCHEMA IF NOT EXISTS {$tenant->schema_name};\n";
        $backupContent .= "SET search_path TO {$tenant->schema_name};\n\n";
        
        // Add sample data
        $backupContent .= "INSERT INTO students (student_id, first_name, last_name, email, enrollment_date) VALUES\n";
        $backupContent .= "('STU001', 'John', 'Doe', 'john@test.com', '2024-01-01'),\n";
        $backupContent .= "('STU002', 'Jane', 'Smith', 'jane@test.com', '2024-01-01');\n\n";
        
        Storage::disk('backups')->put($backupPath, $backupContent);
        
        return $backupPath;
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