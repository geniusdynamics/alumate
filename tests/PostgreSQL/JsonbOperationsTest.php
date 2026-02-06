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
 * PostgreSQL JSONB Operations Test
 *
 * Tests PostgreSQL-specific JSONB column operations that are not available in SQLite.
 * This ensures that JSONB features work correctly in the production environment.
 */
class JsonbOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected TenantSchemaService $tenantSchemaService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantSchemaService = app(TenantSchemaService::class);
    }

    /**
     * Test that JSONB columns are properly created in PostgreSQL.
     */
    public function test_jsonb_columns_are_created(): void
    {
        // Create a test table with JSONB column
        DB::statement('
            CREATE TABLE test_jsonb_table (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL,
                metadata JSONB
            )
        ');

        // Verify the column type is JSONB
        $columnType = DB::select("
            SELECT data_type
            FROM information_schema.columns
            WHERE table_name = 'test_jsonb_table'
            AND column_name = 'data'
        ");

        $this->assertNotEmpty($columnType);
        $this->assertEquals('jsonb', $columnType[0]->data_type);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_table');
    }

    /**
     * Test JSONB query operators work correctly.
     */
    public function test_jsonb_query_operators(): void
    {
        // Create a test table with JSONB column
        DB::statement('
            CREATE TABLE test_jsonb_query (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL
            )
        ');

        // Insert test data
        DB::table('test_jsonb_query')->insert([
            ['data' => json_encode(['name' => 'John', 'age' => 30])],
            ['data' => json_encode(['name' => 'Jane', 'age' => 25])],
            ['data' => json_encode(['name' => 'Bob', 'age' => 35])],
        ]);

        // Test JSONB contains operator (@>)
        $result = DB::select("
            SELECT * FROM test_jsonb_query
            WHERE data @> '{\"name\": \"John\"}'::jsonb
        ");

        $this->assertCount(1, $result);
        $this->assertEquals('John', json_decode($result[0]->data)->name);

        // Test JSONB key existence operator (?)
        $result = DB::select("
            SELECT * FROM test_jsonb_query
            WHERE data ? 'age'
        ");

        $this->assertCount(3, $result);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_query');
    }

    /**
     * Test JSONB path queries work correctly.
     */
    public function test_jsonb_path_queries(): void
    {
        // Create a test table with nested JSONB
        DB::statement('
            CREATE TABLE test_jsonb_nested (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL
            )
        ');

        // Insert nested test data
        DB::table('test_jsonb_nested')->insert([
            ['data' => json_encode(['user' => ['name' => 'John', 'profile' => ['age' => 30]]])],
            ['data' => json_encode(['user' => ['name' => 'Jane', 'profile' => ['age' => 25]]])],
        ]);

        // Test JSONB path query
        $result = DB::select("
            SELECT * FROM test_jsonb_nested
            WHERE data->'user'->'profile'->>'age' = '30'
        ");

        $this->assertCount(1, $result);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_nested');
    }

    /**
     * Test JSONB array operations work correctly.
     */
    public function test_jsonb_array_operations(): void
    {
        // Create a test table with JSONB arrays
        DB::statement('
            CREATE TABLE test_jsonb_arrays (
                id SERIAL PRIMARY KEY,
                tags JSONB NOT NULL
            )
        ');

        // Insert array data
        DB::table('test_jsonb_arrays')->insert([
            ['tags' => json_encode(['php', 'laravel', 'postgresql'])],
            ['tags' => json_encode(['javascript', 'vue', 'node'])],
            ['tags' => json_encode(['python', 'django', 'postgresql'])],
        ]);

        // Test JSONB contains array element
        $result = DB::select("
            SELECT * FROM test_jsonb_arrays
            WHERE tags @> '[\"postgresql\"]'::jsonb
        ");

        $this->assertCount(2, $result);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_arrays');
    }

    /**
     * Test JSONB update operations work correctly.
     */
    public function test_jsonb_update_operations(): void
    {
        // Create a test table
        DB::statement('
            CREATE TABLE test_jsonb_update (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL
            )
        ');

        // Insert test data
        DB::table('test_jsonb_update')->insert([
            ['data' => json_encode(['name' => 'John', 'age' => 30])],
        ]);

        // Update JSONB field using JSONB operators
        DB::statement("
            UPDATE test_jsonb_update
            SET data = jsonb_set(data, '{age}', '31'::jsonb)
            WHERE id = 1
        ");

        $result = DB::table('test_jsonb_update')->where('id', 1)->first();
        $data = json_decode($result->data, true);

        $this->assertEquals(31, $data['age']);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_update');
    }

    /**
     * Test JSONB indexing works correctly.
     */
    public function test_jsonb_indexing(): void
    {
        // Create a test table
        DB::statement('
            CREATE TABLE test_jsonb_index (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL
            )
        ');

        // Create GIN index on JSONB column
        DB::statement('
            CREATE INDEX idx_test_jsonb_data_gin
            ON test_jsonb_index USING GIN (data)
        ');

        // Verify index exists
        $indexExists = DB::select("
            SELECT 1
            FROM pg_indexes
            WHERE tablename = 'test_jsonb_index'
            AND indexname = 'idx_test_jsonb_data_gin'
        ");

        $this->assertNotEmpty($indexExists);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_index');
    }

    /**
     * Test JSONB aggregation functions work correctly.
     */
    public function test_jsonb_aggregation(): void
    {
        // Create a test table
        DB::statement('
            CREATE TABLE test_jsonb_agg (
                id SERIAL PRIMARY KEY,
                data JSONB NOT NULL
            )
        ');

        // Insert test data
        DB::table('test_jsonb_agg')->insert([
            ['data' => json_encode(['value' => 10])],
            ['data' => json_encode(['value' => 20])],
            ['data' => json_encode(['value' => 30])],
        ]);

        // Test JSONB path aggregation
        $result = DB::select("
            SELECT SUM((data->>'value')::int) as total
            FROM test_jsonb_agg
        ");

        $this->assertEquals(60, $result[0]->total);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_agg');
    }

    /**
     * Test that tenant metadata uses JSONB correctly.
     */
    public function test_tenant_metadata_jsonb(): void
    {
        // Create a tenant with JSONB metadata
        $tenant = Tenant::factory()->create([
            'metadata' => json_encode([
                'settings' => [
                    'theme' => 'dark',
                    'language' => 'en',
                ],
                'features' => ['analytics', 'reports'],
            ]),
        ]);

        // Refresh from database
        $tenant->refresh();

        // Verify JSONB data is preserved
        $metadata = json_decode($tenant->metadata, true);
        $this->assertIsArray($metadata);
        $this->assertEquals('dark', $metadata['settings']['theme']);
        $this->assertContains('analytics', $metadata['features']);

        // Test JSONB query on metadata
        $found = Tenant::whereRaw("metadata @> '{\"settings\": {\"theme\": \"dark\"}}'::jsonb")->first();
        $this->assertNotNull($found);
        $this->assertEquals($tenant->id, $found->id);
    }

    /**
     * Test JSONB null handling.
     */
    public function test_jsonb_null_handling(): void
    {
        // Create a test table
        DB::statement('
            CREATE TABLE test_jsonb_null (
                id SERIAL PRIMARY KEY,
                data JSONB
            )
        ');

        // Insert null and non-null values
        DB::table('test_jsonb_null')->insert([
            ['data' => null],
            ['data' => json_encode(['key' => 'value'])],
        ]);

        // Query for null values
        $nullResults = DB::table('test_jsonb_null')->whereNull('data')->get();
        $this->assertCount(1, $nullResults);

        // Query for non-null values
        $notNullResults = DB::table('test_jsonb_null')->whereNotNull('data')->get();
        $this->assertCount(1, $notNullResults);

        // Clean up
        DB::statement('DROP TABLE test_jsonb_null');
    }
}
