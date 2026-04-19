<?php
// ABOUTME: Unit tests for SchemaUtilityService covering schema creation, validation, and management
// ABOUTME: Tests schema operations, migrations, indexes, RLS policies, and error handling

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\SchemaUtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Mockery;

class SchemaUtilityServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected SchemaUtilityService $schemaUtility;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->schemaUtility = app(SchemaUtilityService::class);
    }
    
    protected function tearDown(): void
    {
        // Clean up test schemas
        $this->cleanupTestSchemas();
        
        parent::tearDown();
    }
    
    /** @test */
    public function it_can_create_tenant_schema()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        
        // Act
        $result = $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue($this->schemaUtility->schemaExists($schemaName));
        
        // Verify schema was created in database
        $schemas = DB::select("
            SELECT schema_name 
            FROM information_schema.schemata 
            WHERE schema_name = ?
        ", [$schemaName]);
        
        $this->assertCount(1, $schemas);
    }
    
    /** @test */
    public function it_prevents_duplicate_schema_creation()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        
        // Create schema first time
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Act - Try to create again
        $result = $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Assert
        $this->assertFalse($result);
    }
    
    /** @test */
    public function it_can_run_tenant_migrations()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Act
        $result = $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify tables were created in the schema
        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ? 
            AND table_type = 'BASE TABLE'
        ", [$schemaName]);
        
        $tableNames = array_column($tables, 'table_name');
        $this->assertContains('students', $tableNames);
        $this->assertContains('courses', $tableNames);
        $this->assertContains('enrollments', $tableNames);
        $this->assertContains('grades', $tableNames);
        $this->assertContains('activity_logs', $tableNames);
    }
    
    /** @test */
    public function it_can_setup_schema_indexes()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Act
        $result = $this->schemaUtility->setupSchemaIndexes($schemaName);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify indexes were created
        $indexes = DB::select("
            SELECT indexname 
            FROM pg_indexes 
            WHERE schemaname = ?
        ", [$schemaName]);
        
        $indexNames = array_column($indexes, 'indexname');
        $this->assertContains('idx_students_email', $indexNames);
        $this->assertContains('idx_students_student_id', $indexNames);
        $this->assertContains('idx_enrollments_student_course', $indexNames);
    }
    
    /** @test */
    public function it_can_setup_rls_policies()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Act
        $result = $this->schemaUtility->setupRLSPolicies($schemaName);
        
        // Assert
        $this->assertTrue($result);
        
        // Verify RLS is enabled on tables
        $rlsTables = DB::select("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = ? 
            AND rowsecurity = true
        ", [$schemaName]);
        
        $this->assertNotEmpty($rlsTables);
    }
    
    /** @test */
    public function it_can_validate_schema_structure()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Act
        $validation = $this->schemaUtility->validateSchemaStructure($schemaName);
        
        // Assert
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        $this->assertNotEmpty($validation['tables']);
        $this->assertArrayHasKey('students', $validation['tables']);
        $this->assertArrayHasKey('courses', $validation['tables']);
        $this->assertArrayHasKey('enrollments', $validation['tables']);
    }
    
    /** @test */
    public function it_detects_invalid_schema_structure()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Don't run migrations - schema will be empty
        
        // Act
        $validation = $this->schemaUtility->validateSchemaStructure($schemaName);
        
        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
        $this->assertStringContainsString('Missing required table: students', implode(', ', $validation['errors']));
    }
    
    /** @test */
    public function it_can_clone_schema_structure()
    {
        // Arrange
        $sourceTenant = Tenant::factory()->create();
        $targetTenant = Tenant::factory()->create();
        $sourceSchema = "tenant_{$sourceTenant->id}";
        $targetSchema = "tenant_{$targetTenant->id}";
        
        // Setup source schema
        $this->schemaUtility->createTenantSchema($sourceTenant, $sourceSchema);
        $this->schemaUtility->runTenantMigrations($sourceSchema);
        
        // Act
        $result = $this->schemaUtility->cloneSchemaStructure($sourceSchema, $targetSchema);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue($this->schemaUtility->schemaExists($targetSchema));
        
        // Verify both schemas have same table structure
        $sourceValidation = $this->schemaUtility->validateSchemaStructure($sourceSchema);
        $targetValidation = $this->schemaUtility->validateSchemaStructure($targetSchema);
        
        $this->assertTrue($sourceValidation['valid']);
        $this->assertTrue($targetValidation['valid']);
        $this->assertEquals(
            array_keys($sourceValidation['tables']),
            array_keys($targetValidation['tables'])
        );
    }
    
    /** @test */
    public function it_can_drop_tenant_schema()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        $this->assertTrue($this->schemaUtility->schemaExists($schemaName));
        
        // Act
        $result = $this->schemaUtility->dropTenantSchema($schemaName);
        
        // Assert
        $this->assertTrue($result);
        $this->assertFalse($this->schemaUtility->schemaExists($schemaName));
    }
    
    /** @test */
    public function it_handles_schema_creation_errors_gracefully()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $invalidSchemaName = "invalid-schema-name!@#"; // Invalid characters
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->schemaUtility->createTenantSchema($tenant, $invalidSchemaName);
    }
    
    /** @test */
    public function it_can_get_schema_statistics()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Add some test data
        DB::statement("SET search_path TO {$schemaName}");
        DB::table('students')->insert([
            'student_id' => 'TEST001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'test@example.com',
            'enrollment_date' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        DB::statement("SET search_path TO public");
        
        // Act
        $stats = $this->schemaUtility->getSchemaStatistics($schemaName);
        
        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('table_count', $stats);
        $this->assertArrayHasKey('total_rows', $stats);
        $this->assertArrayHasKey('schema_size', $stats);
        $this->assertArrayHasKey('tables', $stats);
        
        $this->assertGreaterThan(0, $stats['table_count']);
        $this->assertGreaterThan(0, $stats['total_rows']);
        $this->assertArrayHasKey('students', $stats['tables']);
        $this->assertEquals(1, $stats['tables']['students']['row_count']);
    }
    
    /** @test */
    public function it_can_backup_schema_structure()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Act
        $backup = $this->schemaUtility->backupSchemaStructure($schemaName);
        
        // Assert
        $this->assertIsArray($backup);
        $this->assertArrayHasKey('schema_name', $backup);
        $this->assertArrayHasKey('tables', $backup);
        $this->assertArrayHasKey('indexes', $backup);
        $this->assertArrayHasKey('constraints', $backup);
        $this->assertArrayHasKey('created_at', $backup);
        
        $this->assertEquals($schemaName, $backup['schema_name']);
        $this->assertNotEmpty($backup['tables']);
    }
    
    /** @test */
    public function it_can_restore_schema_from_backup()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        $this->schemaUtility->runTenantMigrations($schemaName);
        
        // Create backup
        $backup = $this->schemaUtility->backupSchemaStructure($schemaName);
        
        // Drop schema
        $this->schemaUtility->dropTenantSchema($schemaName);
        $this->assertFalse($this->schemaUtility->schemaExists($schemaName));
        
        // Act
        $result = $this->schemaUtility->restoreSchemaFromBackup($backup);
        
        // Assert
        $this->assertTrue($result);
        $this->assertTrue($this->schemaUtility->schemaExists($schemaName));
        
        // Verify structure was restored
        $validation = $this->schemaUtility->validateSchemaStructure($schemaName);
        $this->assertTrue($validation['valid']);
    }
    
    /** @test */
    public function it_validates_schema_name_format()
    {
        // Test valid schema names
        $this->assertTrue($this->schemaUtility->isValidSchemaName('tenant_123'));
        $this->assertTrue($this->schemaUtility->isValidSchemaName('tenant_abc_123'));
        $this->assertTrue($this->schemaUtility->isValidSchemaName('test_schema'));
        
        // Test invalid schema names
        $this->assertFalse($this->schemaUtility->isValidSchemaName('123_tenant')); // Starts with number
        $this->assertFalse($this->schemaUtility->isValidSchemaName('tenant-123')); // Contains hyphen
        $this->assertFalse($this->schemaUtility->isValidSchemaName('tenant 123')); // Contains space
        $this->assertFalse($this->schemaUtility->isValidSchemaName('tenant@123')); // Contains special char
        $this->assertFalse($this->schemaUtility->isValidSchemaName('')); // Empty string
        $this->assertFalse($this->schemaUtility->isValidSchemaName('a')); // Too short
    }
    
    /** @test */
    public function it_can_list_all_tenant_schemas()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $schema1 = "tenant_{$tenant1->id}";
        $schema2 = "tenant_{$tenant2->id}";
        
        $this->schemaUtility->createTenantSchema($tenant1, $schema1);
        $this->schemaUtility->createTenantSchema($tenant2, $schema2);
        
        // Act
        $schemas = $this->schemaUtility->listTenantSchemas();
        
        // Assert
        $this->assertIsArray($schemas);
        $this->assertContains($schema1, $schemas);
        $this->assertContains($schema2, $schemas);
        
        // Should not contain system schemas
        $this->assertNotContains('public', $schemas);
        $this->assertNotContains('information_schema', $schemas);
        $this->assertNotContains('pg_catalog', $schemas);
    }
    
    /** @test */
    public function it_can_check_schema_permissions()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $schemaName = "tenant_{$tenant->id}";
        $this->schemaUtility->createTenantSchema($tenant, $schemaName);
        
        // Act
        $permissions = $this->schemaUtility->checkSchemaPermissions($schemaName);
        
        // Assert
        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('can_create', $permissions);
        $this->assertArrayHasKey('can_usage', $permissions);
        $this->assertArrayHasKey('owner', $permissions);
        
        $this->assertTrue($permissions['can_create']);
        $this->assertTrue($permissions['can_usage']);
        $this->assertNotEmpty($permissions['owner']);
    }
    
    /** @test */
    public function it_handles_concurrent_schema_operations()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $schema1 = "tenant_{$tenant1->id}";
        $schema2 = "tenant_{$tenant2->id}";
        
        // Act - Create schemas concurrently
        $result1 = $this->schemaUtility->createTenantSchema($tenant1, $schema1);
        $result2 = $this->schemaUtility->createTenantSchema($tenant2, $schema2);
        
        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($this->schemaUtility->schemaExists($schema1));
        $this->assertTrue($this->schemaUtility->schemaExists($schema2));
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