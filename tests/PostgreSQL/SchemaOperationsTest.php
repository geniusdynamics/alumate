<?php

declare(strict_types=1);

namespace Tests\PostgreSQL;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantSchemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PostgreSQL Schema Operations Test
 *
 * Tests PostgreSQL-specific schema operations including tenant schema creation,
 * constraints, and advanced PostgreSQL features.
 */
class SchemaOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected TenantSchemaService $tenantSchemaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantSchemaService = app(TenantSchemaService::class);
    }

    /**
     * Test that tenant schemas can be created in PostgreSQL.
     */
    public function test_tenant_schema_creation(): void
    {
        $tenant = Tenant::factory()->create();

        // Create tenant schema
        $schemaName = "tenant_{$tenant->id}";
        $this->tenantSchemaService->createTenantSchema($tenant->id);

        // Verify schema exists
        $schemaExists = DB::select("
            SELECT schema_name
            FROM information_schema.schemata
            WHERE schema_name = ?
        ", [$schemaName]);

        $this->assertNotEmpty($schemaExists);
        $this->assertEquals($schemaName, $schemaExists[0]->schema_name);

        // Clean up
        $this->tenantSchemaService->dropTenantSchema($tenant->id);
    }

    /**
     * Test that tenant schemas can be dropped in PostgreSQL.
     */
    public function test_tenant_schema_deletion(): void
    {
        $tenant = Tenant::factory()->create();

        // Create tenant schema
        $this->tenantSchemaService->createTenantSchema($tenant->id);
        $schemaName = "tenant_{$tenant->id}";

        // Verify schema exists
        $schemaExists = DB::select("
            SELECT schema_name
            FROM information_schema.schemata
            WHERE schema_name = ?
        ", [$schemaName]);

        $this->assertNotEmpty($schemaExists);

        // Drop tenant schema
        $this->tenantSchemaService->dropTenantSchema($tenant->id);

        // Verify schema no longer exists
        $schemaExists = DB::select("
            SELECT schema_name
            FROM information_schema.schemata
            WHERE schema_name = ?
        ", [$schemaName]);

        $this->assertEmpty($schemaExists);
    }

    /**
     * Test PostgreSQL foreign key constraints work correctly.
     */
    public function test_foreign_key_constraints(): void
    {
        // Create test tables with foreign key
        DB::statement('
            CREATE TABLE test_parent (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )
        ');

        DB::statement('
            CREATE TABLE test_child (
                id SERIAL PRIMARY KEY,
                parent_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                CONSTRAINT fk_child_parent
                    FOREIGN KEY (parent_id)
                    REFERENCES test_parent(id)
                    ON DELETE CASCADE
            )
        ');

        // Insert parent record
        $parentId = DB::table('test_parent')->insertGetId(['name' => 'Parent']);

        // Insert valid child record
        DB::table('test_child')->insert([
            'parent_id' => $parentId,
            'name' => 'Child',
        ]);

        // Verify child exists
        $child = DB::table('test_child')->where('parent_id', $parentId)->first();
        $this->assertNotNull($child);

        // Test cascade delete
        DB::table('test_parent')->where('id', $parentId)->delete();

        // Verify child was deleted
        $child = DB::table('test_child')->where('parent_id', $parentId)->first();
        $this->assertNull($child);

        // Clean up
        DB::statement('DROP TABLE test_child');
        DB::statement('DROP TABLE test_parent');
    }

    /**
     * Test PostgreSQL unique constraints work correctly.
     */
    public function test_unique_constraints(): void
    {
        // Create table with unique constraint
        DB::statement('
            CREATE TABLE test_unique (
                id SERIAL PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                CONSTRAINT unique_email UNIQUE (email)
            )
        ');

        // Insert first record
        DB::table('test_unique')->insert(['email' => 'test@example.com']);

        // Attempt to insert duplicate
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('test_unique')->insert(['email' => 'test@example.com']);

        // Clean up
        DB::statement('DROP TABLE test_unique');
    }

    /**
     * Test PostgreSQL check constraints work correctly.
     */
    public function test_check_constraints(): void
    {
        // Create table with check constraint
        DB::statement('
            CREATE TABLE test_check (
                id SERIAL PRIMARY KEY,
                age INTEGER NOT NULL,
                CONSTRAINT check_age_positive CHECK (age >= 0)
            )
        ');

        // Insert valid record
        DB::table('test_check')->insert(['age' => 25]);

        // Attempt to insert invalid record
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('test_check')->insert(['age' => -5]);

        // Clean up
        DB::statement('DROP TABLE test_check');
    }

    /**
     * Test PostgreSQL indexes work correctly.
     */
    public function test_index_creation(): void
    {
        // Create table
        DB::statement('
            CREATE TABLE test_index (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL
            )
        ');

        // Create indexes
        DB::statement('CREATE INDEX idx_test_index_name ON test_index (name)');
        DB::statement('CREATE INDEX idx_test_index_email ON test_index (email)');

        // Verify indexes exist
        $indexes = DB::select("
            SELECT indexname
            FROM pg_indexes
            WHERE tablename = 'test_index'
            AND schemaname = 'public'
        ");

        $indexNames = array_column($indexes, 'indexname');
        $this->assertContains('idx_test_index_name', $indexNames);
        $this->assertContains('idx_test_index_email', $indexNames);

        // Clean up
        DB::statement('DROP TABLE test_index');
    }

    /**
     * Test PostgreSQL composite indexes work correctly.
     */
    public function test_composite_indexes(): void
    {
        // Create table
        DB::statement('
            CREATE TABLE test_composite (
                id SERIAL PRIMARY KEY,
                tenant_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL
            )
        ');

        // Create composite index
        DB::statement('
            CREATE INDEX idx_test_composite_tenant_user
            ON test_composite (tenant_id, user_id)
        ');

        // Verify composite index exists
        $indexExists = DB::select("
            SELECT 1
            FROM pg_indexes
            WHERE tablename = 'test_composite'
            AND indexname = 'idx_test_composite_tenant_user'
        ");

        $this->assertNotEmpty($indexExists);

        // Clean up
        DB::statement('DROP TABLE test_composite');
    }

    /**
     * Test PostgreSQL partial indexes work correctly.
     */
    public function test_partial_indexes(): void
    {
        // Create table
        DB::statement('
            CREATE TABLE test_partial (
                id SERIAL PRIMARY KEY,
                status VARCHAR(50) NOT NULL,
                created_at TIMESTAMP NOT NULL
            )
        ');

        // Create partial index
        DB::statement("
            CREATE INDEX idx_test_partial_active
            ON test_partial (created_at)
            WHERE status = 'active'
        ");

        // Verify partial index exists
        $indexExists = DB::select("
            SELECT 1
            FROM pg_indexes
            WHERE tablename = 'test_partial'
            AND indexname = 'idx_test_partial_active'
        ");

        $this->assertNotEmpty($indexExists);

        // Clean up
        DB::statement('DROP TABLE test_partial');
    }

    /**
     * Test PostgreSQL generated columns work correctly.
     */
    public function test_generated_columns(): void
    {
        // Create table with generated column
        DB::statement("
            CREATE TABLE test_generated (
                id SERIAL PRIMARY KEY,
                first_name VARCHAR(255) NOT NULL,
                last_name VARCHAR(255) NOT NULL,
                full_name VARCHAR(510) GENERATED ALWAYS AS (first_name || ' ' || last_name) STORED
            )
        ");

        // Insert record
        DB::table('test_generated')->insert([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Verify generated column
        $record = DB::table('test_generated')->first();
        $this->assertEquals('John Doe', $record->full_name);

        // Clean up
        DB::statement('DROP TABLE test_generated');
    }

    /**
     * Test PostgreSQL array columns work correctly.
     */
    public function test_array_columns(): void
    {
        // Create table with array column
        DB::statement('
            CREATE TABLE test_array (
                id SERIAL PRIMARY KEY,
                tags INTEGER[] NOT NULL
            )
        ');

        // Insert record with array
        DB::table('test_array')->insert([
            'tags' => [1, 2, 3],
        ]);

        // Verify array data
        $record = DB::table('test_array')->first();
        $this->assertEquals([1, 2, 3], $record->tags);

        // Test array contains operator
        $result = DB::select("
            SELECT * FROM test_array
            WHERE tags @> ARRAY[2]::INTEGER[]
        ");

        $this->assertCount(1, $result);

        // Clean up
        DB::statement('DROP TABLE test_array');
    }

    /**
     * Test PostgreSQL transaction isolation levels work correctly.
     */
    public function test_transaction_isolation(): void
    {
        // Create table
        DB::statement('
            CREATE TABLE test_transaction (
                id SERIAL PRIMARY KEY,
                value INTEGER NOT NULL
            )
        ');

        // Test serializable isolation
        DB::transaction(function () {
            DB::statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
            DB::table('test_transaction')->insert(['value' => 100]);
        });

        // Verify record was inserted
        $count = DB::table('test_transaction')->count();
        $this->assertEquals(1, $count);

        // Clean up
        DB::statement('DROP TABLE test_transaction');
    }

    /**
     * Test PostgreSQL full-text search works correctly.
     */
    public function test_full_text_search(): void
    {
        // Create table with text column
        DB::statement('
            CREATE TABLE test_fts (
                id SERIAL PRIMARY KEY,
                title TEXT NOT NULL,
                content TEXT NOT NULL
            )
        ');

        // Insert test data
        DB::table('test_fts')->insert([
            'title' => 'PostgreSQL Tutorial',
            'content' => 'Learn PostgreSQL database features',
        ]);

        DB::table('test_fts')->insert([
            'title' => 'Laravel Guide',
            'content' => 'Build applications with Laravel framework',
        ]);

        // Test full-text search
        $results = DB::select("
            SELECT * FROM test_fts
            WHERE to_tsvector('english', title || ' ' || content) @@ to_tsquery('english', 'PostgreSQL')
        ");

        $this->assertCount(1, $results);
        $this->assertStringContainsString('PostgreSQL', $results[0]->title);

        // Clean up
        DB::statement('DROP TABLE test_fts');
    }

    /**
     * Test PostgreSQL triggers work correctly.
     */
    public function test_triggers(): void
    {
        // Create tables
        DB::statement('
            CREATE TABLE test_audit_log (
                id SERIAL PRIMARY KEY,
                table_name VARCHAR(255) NOT NULL,
                action VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        DB::statement('
            CREATE TABLE test_trigger (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL
            )
        ');

        // Create trigger function
        DB::statement('
            CREATE OR REPLACE FUNCTION log_table_changes()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO test_audit_log (table_name, action)
                VALUES (TG_TABLE_NAME, TG_OP);
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql
        ');

        // Create trigger
        DB::statement('
            CREATE TRIGGER trigger_test_trigger_insert
            AFTER INSERT ON test_trigger
            FOR EACH ROW EXECUTE FUNCTION log_table_changes()
        ');

        // Insert record
        DB::table('test_trigger')->insert(['name' => 'Test']);

        // Verify trigger fired
        $log = DB::table('test_audit_log')->first();
        $this->assertNotNull($log);
        $this->assertEquals('test_trigger', $log->table_name);
        $this->assertEquals('INSERT', $log->action);

        // Clean up
        DB::statement('DROP TRIGGER trigger_test_trigger_insert ON test_trigger');
        DB::statement('DROP FUNCTION log_table_changes()');
        DB::statement('DROP TABLE test_trigger');
        DB::statement('DROP TABLE test_audit_log');
    }

    /**
     * Test PostgreSQL view creation works correctly.
     */
    public function test_views(): void
    {
        // Create table
        DB::statement('
            CREATE TABLE test_view_source (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL
            )
        ');

        // Insert data
        DB::table('test_view_source')->insert([
            ['name' => 'Item 1', 'status' => 'active'],
            ['name' => 'Item 2', 'status' => 'inactive'],
            ['name' => 'Item 3', 'status' => 'active'],
        ]);

        // Create view
        DB::statement("
            CREATE VIEW test_active_items AS
            SELECT * FROM test_view_source WHERE status = 'active'
        ");

        // Query view
        $results = DB::table('test_active_items')->get();
        $this->assertCount(2, $results);

        // Clean up
        DB::statement('DROP VIEW test_active_items');
        DB::statement('DROP TABLE test_view_source');
    }
}
