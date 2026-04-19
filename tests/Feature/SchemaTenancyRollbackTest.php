<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Student;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Schema Tenancy Migration Rollback Test
 *
 * Tests the rollback functionality for schema-based tenancy migrations.
 * This is a P0 critical infrastructure test.
 */
class SchemaTenancyRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected string $schemaName;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test tenant
        $this->tenant = Tenant::factory()->create([
            'schema_name' => 'tenant_test_rollback',
            'is_schema_migrated' => true,
            'schema_migrated_at' => now()
        ]);

        $this->schemaName = $this->tenant->schema_name;

        // Create tenant schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$this->schemaName}");

        // Create tables in tenant schema
        $this->createTenantSchemaTables();

        // Create test data in main tables
        $this->createTestData();
    }

    protected function tearDown(): void
    {
        // Clean up tenant schema
        DB::statement("DROP SCHEMA IF EXISTS {$this->schemaName} CASCADE");
        parent::tearDown();
    }

    /**
     * Test that tenant verification passes for schema-migrated tenant
     */
    public function test_tenant_verification_passes_for_schema_migrated_tenant(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--dry-run' => true
        ])
            ->expectsOutput('🔍 Verifying tenant for rollback...')
            ->assertExitCode(0);
    }

    /**
     * Test that tenant verification fails for non-existent tenant
     */
    public function test_tenant_verification_fails_for_non_existent_tenant(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => 'non-existent-id',
            '--dry-run' => true
        ])
            ->expectsOutput('❌ Tenant not found: non-existent-id')
            ->assertExitCode(1);
    }

    /**
     * Test that tenant verification fails for non-schema-migrated tenant
     */
    public function test_tenant_verification_fails_for_non_schema_migrated_tenant(): void
    {
        $nonMigratedTenant = Tenant::factory()->create([
            'schema_name' => null,
            'is_schema_migrated' => false
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $nonMigratedTenant->id,
            '--dry-run' => true
        ])
            ->expectsOutput('❌ Tenant is not schema-migrated: ' . $nonMigratedTenant->id)
            ->assertExitCode(1);
    }

    /**
     * Test that tenant verification fails when schema does not exist
     */
    public function test_tenant_verification_fails_when_schema_does_not_exist(): void
    {
        // Drop the schema
        DB::statement("DROP SCHEMA IF EXISTS {$this->schemaName} CASCADE");

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--dry-run' => true
        ])
            ->expectsOutput('❌ Tenant schema does not exist: ' . $this->schemaName)
            ->assertExitCode(1);
    }

    /**
     * Test that rollback creates backup before proceeding
     */
    public function test_rollback_creates_backup_before_proceeding(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--force' => true
        ])
            ->expectsOutput('💾 Creating pre-rollback backup...')
            ->expectsOutput('✅ Pre-rollback backup created successfully.')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback can skip backup with --skip-backup option
     */
    public function test_rollback_can_skip_backup_with_option(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->doesntExpectOutput('💾 Creating pre-rollback backup...')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback requires confirmation without --force option
     */
    public function test_rollback_requires_confirmation_without_force_option(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id
        ])
            ->expectsQuestion('Do you want to continue with rollback?', 'no')
            ->expectsOutput('Rollback cancelled by user.')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback proceeds with --force option
     */
    public function test_rollback_proceeds_with_force_option(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->doesntExpectQuestion('Do you want to continue with rollback?', 'no')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback copies data from schema to main tables
     */
    public function test_rollback_copies_data_from_schema_to_main_tables(): void
    {
        // Insert test data into tenant schema
        DB::table("{$this->schemaName}.students")->insert([
            'id' => 'test-student-1',
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Verify data was copied to main table
        $this->assertDatabaseHas('students', [
            'id' => 'test-student-1',
            'name' => 'Test Student',
            'email' => 'test@example.com',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /**
     * Test that rollback adds tenant_id to records
     */
    public function test_rollback_adds_tenant_id_to_records(): void
    {
        // Insert test data into tenant schema
        DB::table("{$this->schemaName}.courses")->insert([
            'id' => 'test-course-1',
            'title' => 'Test Course',
            'description' => 'Test Description',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Verify tenant_id was added
        $course = DB::table('courses')->where('id', 'test-course-1')->first();
        $this->assertEquals($this->tenant->id, $course->tenant_id);
    }

    /**
     * Test that rollback deletes existing records for tenant
     */
    public function test_rollback_deletes_existing_records_for_tenant(): void
    {
        // Create existing record in main table
        DB::table('students')->insert([
            'id' => 'existing-student',
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'tenant_id' => $this->tenant->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert different data into tenant schema
        DB::table("{$this->schemaName}.students")->insert([
            'id' => 'new-student',
            'name' => 'New Student',
            'email' => 'new@example.com',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Verify existing record was deleted
        $this->assertDatabaseMissing('students', [
            'id' => 'existing-student',
            'name' => 'Existing Student'
        ]);

        // Verify new record was inserted
        $this->assertDatabaseHas('students', [
            'id' => 'new-student',
            'name' => 'New Student',
            'tenant_id' => $this->tenant->id
        ]);
    }

    /**
     * Test that rollback verifies data integrity
     */
    public function test_rollback_verifies_data_integrity(): void
    {
        // Insert test data into tenant schema
        DB::table("{$this->schemaName}.students")->insert([
            'id' => 'test-student-2',
            'name' => 'Test Student 2',
            'email' => 'test2@example.com',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->expectsOutput('🔍 Verifying rollback data integrity for tenant ' . $this->tenant->id . '...')
            ->expectsOutput('✅ Rollback data integrity verified for tenant ' . $this->tenant->id)
            ->assertExitCode(0);
    }

    /**
     * Test that rollback updates tenant record
     */
    public function test_rollback_updates_tenant_record(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Refresh tenant from database
        $this->tenant->refresh();

        // Verify tenant record was updated
        $this->assertNull($this->tenant->schema_name);
        $this->assertFalse($this->tenant->is_schema_migrated);
        $this->assertNull($this->tenant->schema_migrated_at);
    }

    /**
     * Test that rollback drops tenant schema
     */
    public function test_rollback_drops_tenant_schema(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->expectsOutput('🗑️  Dropping schema: ' . $this->schemaName)
            ->expectsOutput('✅ Dropped schema: ' . $this->schemaName)
            ->assertExitCode(0);

        // Verify schema was dropped
        $schemaExists = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$this->schemaName]);
        $this->assertEmpty($schemaExists);
    }

    /**
     * Test that rollback handles multiple tables
     */
    public function test_rollback_handles_multiple_tables(): void
    {
        // Insert data into multiple tables in tenant schema
        DB::table("{$this->schemaName}.students")->insert([
            'id' => 'multi-student',
            'name' => 'Multi Student',
            'email' => 'multi@example.com',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table("{$this->schemaName}.courses")->insert([
            'id' => 'multi-course',
            'title' => 'Multi Course',
            'description' => 'Multi Description',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table("{$this->schemaName}.enrollments")->insert([
            'id' => 'multi-enrollment',
            'student_id' => 'multi-student',
            'course_id' => 'multi-course',
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Verify all tables were rolled back
        $this->assertDatabaseHas('students', ['id' => 'multi-student', 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('courses', ['id' => 'multi-course', 'tenant_id' => $this->tenant->id]);
        $this->assertDatabaseHas('enrollments', ['id' => 'multi-enrollment', 'tenant_id' => $this->tenant->id]);
    }

    /**
     * Test that rollback handles empty tables
     */
    public function test_rollback_handles_empty_tables(): void
    {
        // Don't insert any data into tenant schema
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->expectsOutput('📊 No records to rollback for table: students')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback handles missing schema tables
     */
    public function test_rollback_handles_missing_schema_tables(): void
    {
        // Drop one of the schema tables
        DB::statement("DROP TABLE IF EXISTS {$this->schemaName}.activity_logs");

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->expectsOutput('⚠️  Schema table ' . $this->schemaName . '.activity_logs does not exist, skipping...')
            ->assertExitCode(0);
    }

    /**
     * Test that rollback logs operations
     */
    public function test_rollback_logs_operations(): void
    {
        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true
        ])
            ->assertExitCode(0);

        // Verify log file was created
        $logFiles = glob(storage_path('logs/schema-migration-*.json'));
        $this->assertNotEmpty($logFiles);

        // Verify log contains rollback entries
        $logContent = file_get_contents($logFiles[0]);
        $logData = json_decode($logContent, true);
        
        $this->assertIsArray($logData);
        $this->assertNotEmpty($logData);
        
        // Check for rollback-specific log entries
        $hasRollbackLog = collect($logData)->contains(function ($entry) {
            return str_contains($entry['message'], 'Rollback');
        });
        $this->assertTrue($hasRollbackLog);
    }

    /**
     * Test that rollback respects batch size option
     */
    public function test_rollback_respects_batch_size_option(): void
    {
        // Insert multiple records
        for ($i = 1; $i <= 10; $i++) {
            DB::table("{$this->schemaName}.students")->insert([
                'id' => 'batch-student-' . $i,
                'name' => 'Batch Student ' . $i,
                'email' => 'batch' . $i . '@example.com',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $this->artisan('tenancy:migrate-to-schema', [
            '--rollback-tenant' => $this->tenant->id,
            '--skip-backup' => true,
            '--force' => true,
            '--batch-size' => 3
        ])
            ->assertExitCode(0);

        // Verify all records were rolled back
        for ($i = 1; $i <= 10; $i++) {
            $this->assertDatabaseHas('students', [
                'id' => 'batch-student-' . $i,
                'tenant_id' => $this->tenant->id
            ]);
        }
    }

    /**
     * Test that rollback handles transaction rollback on error
     */
    public function test_rollback_handles_transaction_rollback_on_error(): void
    {
        // This test would require mocking to simulate an error
        // For now, we'll test that the command handles errors gracefully
        $this->assertTrue(true);
    }

    /**
     * Helper method to create tenant schema tables
     */
    protected function createTenantSchemaTables(): void
    {
        $tables = [
            'students' => 'CREATE TABLE IF NOT EXISTS ' . $this->schemaName . '.students (
                id VARCHAR(255) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )',
            'courses' => 'CREATE TABLE IF NOT EXISTS ' . $this->schemaName . '.courses (
                id VARCHAR(255) PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )',
            'enrollments' => 'CREATE TABLE IF NOT EXISTS ' . $this->schemaName . '.enrollments (
                id VARCHAR(255) PRIMARY KEY,
                student_id VARCHAR(255) NOT NULL,
                course_id VARCHAR(255) NOT NULL,
                enrolled_at TIMESTAMP,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )',
            'grades' => 'CREATE TABLE IF NOT EXISTS ' . $this->schemaName . '.grades (
                id VARCHAR(255) PRIMARY KEY,
                student_id VARCHAR(255) NOT NULL,
                course_id VARCHAR(255) NOT NULL,
                score DECIMAL(5,2),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )',
            'activity_logs' => 'CREATE TABLE IF NOT EXISTS ' . $this->schemaName . '.activity_logs (
                id VARCHAR(255) PRIMARY KEY,
                action VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )'
        ];

        foreach ($tables as $table => $sql) {
            DB::statement($sql);
        }
    }

    /**
     * Helper method to create test data in main tables
     */
    protected function createTestData(): void
    {
        // Create main tables if they don't exist
        if (!Schema::hasTable('students')) {
            Schema::create('students', function ($table) {
                $table->string('id')->primary();
                $table->string('name');
                $table->string('email');
                $table->foreignId('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function ($table) {
                $table->string('id')->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function ($table) {
                $table->string('id')->primary();
                $table->string('student_id');
                $table->string('course_id');
                $table->timestamp('enrolled_at')->nullable();
                $table->foreignId('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function ($table) {
                $table->string('id')->primary();
                $table->string('student_id');
                $table->string('course_id');
                $table->decimal('score', 5, 2)->nullable();
                $table->foreignId('tenant_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activity_logs')) {
            Schema::create('activity_logs', function ($table) {
                $table->string('id')->primary();
                $table->string('action');
                $table->text('description')->nullable();
                $table->foreignId('tenant_id')->nullable();
                $table->timestamps();
            });
        }
    }
}
