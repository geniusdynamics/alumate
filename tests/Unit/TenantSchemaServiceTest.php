<?php
// ABOUTME: Unit tests for TenantSchemaService to verify schema management operations
// ABOUTME: Tests schema creation, migration, validation, and cleanup functionality

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\TenantSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TenantSchemaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantSchemaService $tenantSchema;
    protected Tenant $testTenant;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantSchema = app(TenantSchemaService::class);
        $this->createTestTenant();
    }

    protected function tearDown(): void
    {
        // Clean up test schema
        if (isset($this->testTenant)) {
            try {
                $this->tenantSchema->dropSchema($this->testTenant->id);
            } catch (\Exception $e) {
                // Schema might not exist, ignore
            }
        }
        
        parent::tearDown();
    }

    protected function createTestTenant(): void
    {
        $this->testTenant = Tenant::create([
            'name' => 'Schema Test University',
            'slug' => 'schema-test',
            'domain' => 'schema.test.com',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_create_tenant_schema(): void
    {
        $result = $this->tenantSchema->createSchema($this->testTenant->id);
        
        $this->assertTrue($result);
        
        // Verify schema exists
        $schemaName = 'tenant_' . $this->testTenant->id;
        $schemas = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schemaName]
        );
        
        $this->assertCount(1, $schemas);
    }

    /** @test */
    public function it_prevents_duplicate_schema_creation(): void
    {
        // Create schema first time
        $result1 = $this->tenantSchema->createSchema($this->testTenant->id);
        $this->assertTrue($result1);
        
        // Attempt to create again
        $result2 = $this->tenantSchema->createSchema($this->testTenant->id);
        $this->assertFalse($result2);
    }

    /** @test */
    public function it_can_drop_tenant_schema(): void
    {
        // Create schema first
        $this->tenantSchema->createSchema($this->testTenant->id);
        
        // Drop schema
        $result = $this->tenantSchema->dropSchema($this->testTenant->id);
        $this->assertTrue($result);
        
        // Verify schema no longer exists
        $schemaName = 'tenant_' . $this->testTenant->id;
        $schemas = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schemaName]
        );
        
        $this->assertCount(0, $schemas);
    }

    /** @test */
    public function it_handles_dropping_non_existent_schema(): void
    {
        $result = $this->tenantSchema->dropSchema($this->testTenant->id);
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_check_if_schema_exists(): void
    {
        // Schema should not exist initially
        $exists = $this->tenantSchema->schemaExists($this->testTenant->id);
        $this->assertFalse($exists);
        
        // Create schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        
        // Schema should now exist
        $exists = $this->tenantSchema->schemaExists($this->testTenant->id);
        $this->assertTrue($exists);
    }

    /** @test */
    public function it_can_migrate_tenant_schema(): void
    {
        // Create schema first
        $this->tenantSchema->createSchema($this->testTenant->id);
        
        // Run migrations
        $result = $this->tenantSchema->migrateSchema($this->testTenant->id);
        $this->assertTrue($result);
        
        // Switch to tenant schema to verify tables exist
        $this->tenantSchema->switchToSchema($this->testTenant->id);
        
        // Check that core tables exist
        $requiredTables = ['users', 'courses', 'graduates', 'jobs', 'landing_pages'];
        
        foreach ($requiredTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist after migration"
            );
        }
    }

    /** @test */
    public function it_can_switch_to_tenant_schema(): void
    {
        // Create and migrate schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Switch to tenant schema
        $result = $this->tenantSchema->switchToSchema($this->testTenant->id);
        $this->assertTrue($result);
        
        // Verify current schema
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $expectedSchema = 'tenant_' . $this->testTenant->id;
        $this->assertEquals($expectedSchema, $currentSchema);
    }

    /** @test */
    public function it_can_switch_to_public_schema(): void
    {
        // Create tenant schema and switch to it
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->switchToSchema($this->testTenant->id);
        
        // Switch back to public schema
        $result = $this->tenantSchema->switchToPublicSchema();
        $this->assertTrue($result);
        
        // Verify current schema
        $currentSchema = DB::select('SELECT current_schema()')[0]->current_schema;
        $this->assertEquals('public', $currentSchema);
    }

    /** @test */
    public function it_validates_tenant_schema_integrity(): void
    {
        // Create and migrate schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Validate schema
        $isValid = $this->tenantSchema->validateSchema($this->testTenant->id);
        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_detects_invalid_schema(): void
    {
        // Create schema but don't migrate
        $this->tenantSchema->createSchema($this->testTenant->id);
        
        // Validation should fail because tables don't exist
        $isValid = $this->tenantSchema->validateSchema($this->testTenant->id);
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_can_rollback_schema_migrations(): void
    {
        // Create test migration file
        $migrationPath = database_path('migrations/tenant');
        if (!file_exists($migrationPath)) {
            mkdir($migrationPath, 0755, true);
        }

        $testMigration = $migrationPath . '/2024_01_01_000000_test_rollback.php';
        file_put_contents($testMigration, $this->getTestMigrationContent());

        try {
            // Create and migrate schema
            $this->tenantSchema->createSchema($this->testTenant->id);
            $this->tenantSchema->migrateSchema($this->testTenant->id, [$testMigration]);
            
            // Switch to tenant schema and verify table exists
            $this->tenantSchema->switchToSchema($this->testTenant->id);
            $this->assertTrue(Schema::hasTable('test_rollback_table'));

            // Rollback migration
            $result = $this->tenantSchema->rollbackSchema($this->testTenant->id, 1);
            $this->assertTrue($result);
            
            // Verify table no longer exists
            $this->assertFalse(Schema::hasTable('test_rollback_table'));
        } finally {
            // Clean up test migration file
            if (file_exists($testMigration)) {
                unlink($testMigration);
            }
        }
    }

    /** @test */
    public function it_can_get_schema_tables(): void
    {
        // Create and migrate schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Get schema tables
        $tables = $this->tenantSchema->getSchemaTables($this->testTenant->id);
        
        $this->assertIsArray($tables);
        $this->assertNotEmpty($tables);
        
        // Check for expected tables
        $expectedTables = ['users', 'courses', 'graduates', 'jobs'];
        foreach ($expectedTables as $expectedTable) {
            $this->assertContains($expectedTable, $tables);
        }
    }

    /** @test */
    public function it_can_get_schema_size(): void
    {
        // Create and migrate schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Get schema size
        $size = $this->tenantSchema->getSchemaSize($this->testTenant->id);
        
        $this->assertIsNumeric($size);
        $this->assertGreaterThan(0, $size);
    }

    /** @test */
    public function it_handles_schema_operations_with_invalid_tenant_id(): void
    {
        $invalidTenantId = 'invalid-tenant-id';
        
        $this->assertFalse($this->tenantSchema->createSchema($invalidTenantId));
        $this->assertFalse($this->tenantSchema->schemaExists($invalidTenantId));
        $this->assertFalse($this->tenantSchema->validateSchema($invalidTenantId));
    }

    /** @test */
    public function it_can_backup_tenant_schema(): void
    {
        // Create and migrate schema with some data
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Create backup
        $backupPath = $this->tenantSchema->backupSchema($this->testTenant->id);
        
        $this->assertNotNull($backupPath);
        $this->assertFileExists($backupPath);
        
        // Clean up backup file
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }

    /** @test */
    public function it_can_restore_tenant_schema_from_backup(): void
    {
        // Create and migrate schema
        $this->tenantSchema->createSchema($this->testTenant->id);
        $this->tenantSchema->migrateSchema($this->testTenant->id);
        
        // Create backup
        $backupPath = $this->tenantSchema->backupSchema($this->testTenant->id);
        
        // Drop schema
        $this->tenantSchema->dropSchema($this->testTenant->id);
        $this->assertFalse($this->tenantSchema->schemaExists($this->testTenant->id));
        
        // Restore from backup
        $result = $this->tenantSchema->restoreSchema($this->testTenant->id, $backupPath);
        $this->assertTrue($result);
        
        // Verify schema exists and is valid
        $this->assertTrue($this->tenantSchema->schemaExists($this->testTenant->id));
        $this->assertTrue($this->tenantSchema->validateSchema($this->testTenant->id));
        
        // Clean up backup file
        if (file_exists($backupPath)) {
            unlink($backupPath);
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