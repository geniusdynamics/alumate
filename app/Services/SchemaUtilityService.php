<?php
// ABOUTME: Schema utility service for managing tenant schema creation, validation, and maintenance operations
// ABOUTME: Provides comprehensive tools for schema-based multi-tenancy infrastructure management

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;

class SchemaUtilityService
{
    protected TenantContextService $tenantService;
    
    public function __construct(TenantContextService $tenantService)
    {
        $this->tenantService = $tenantService;
    }
    
    /**
     * Create a new tenant schema with all required tables
     */
    public function createTenantSchema(string $schemaName, array $options = []): array
    {
        $result = [
            'schema_name' => $schemaName,
            'created' => false,
            'tables_created' => [],
            'errors' => []
        ];
        
        try {
            // Check if schema already exists
            if ($this->schemaExists($schemaName)) {
                if (!($options['force'] ?? false)) {
                    throw new Exception("Schema '{$schemaName}' already exists");
                }
                
                // Drop existing schema if force is true
                $this->dropSchema($schemaName);
            }
            
            // Create the schema
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            $result['created'] = true;
            
            // Set search path to new schema
            DB::statement("SET search_path TO {$schemaName}, public");
            
            // Run tenant migrations
            $this->runTenantMigrations($schemaName);
            
            // Verify tables were created
            $result['tables_created'] = $this->getSchemaTableList($schemaName);
            
            // Create initial indexes
            $this->createOptimizedIndexes($schemaName);
            
            // Set up RLS policies if enabled
            if ($options['enable_rls'] ?? false) {
                $this->setupRowLevelSecurity($schemaName);
            }
            
            Log::info("Tenant schema created successfully", [
                'schema_name' => $schemaName,
                'tables_count' => count($result['tables_created'])
            ]);
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            Log::error("Failed to create tenant schema", [
                'schema_name' => $schemaName,
                'error' => $e->getMessage()
            ]);
        } finally {
            // Reset search path
            DB::statement("SET search_path TO public");
        }
        
        return $result;
    }
    
    /**
     * Run tenant-specific migrations
     */
    public function runTenantMigrations(string $schemaName): void
    {
        $migrationPath = database_path('migrations/tenant');
        
        if (!is_dir($migrationPath)) {
            throw new Exception("Tenant migration directory not found: {$migrationPath}");
        }
        
        // Set schema in connection config temporarily
        $originalSearchPath = DB::select("SHOW search_path")[0]->search_path;
        
        try {
            DB::statement("SET search_path TO {$schemaName}, public");
            
            // Get migration files
            $migrationFiles = glob($migrationPath . '/*.php');
            sort($migrationFiles);
            
            foreach ($migrationFiles as $file) {
                $this->runSingleMigration($file, $schemaName);
            }
            
        } finally {
            DB::statement("SET search_path TO {$originalSearchPath}");
        }
    }
    
    /**
     * Run a single migration file
     */
    protected function runSingleMigration(string $filePath, string $schemaName): void
    {
        $fileName = basename($filePath, '.php');
        
        // Extract class name from file
        $content = file_get_contents($filePath);
        preg_match('/class\s+(\w+)/', $content, $matches);
        
        if (!isset($matches[1])) {
            throw new Exception("Could not extract class name from migration: {$fileName}");
        }
        
        $className = $matches[1];
        
        // Include the migration file
        require_once $filePath;
        
        // Instantiate and run the migration
        $migration = new $className();
        
        if (method_exists($migration, 'up')) {
            $migration->up();
            Log::info("Migration executed", [
                'schema' => $schemaName,
                'migration' => $className
            ]);
        }
    }
    
    /**
     * Create optimized indexes for tenant schema
     */
    public function createOptimizedIndexes(string $schemaName): void
    {
        $indexes = [
            // Students table indexes
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_students_email ON {$schemaName}.students(email)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_students_student_id ON {$schemaName}.students(student_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_students_status ON {$schemaName}.students(status)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_students_created_at ON {$schemaName}.students(created_at)",
            
            // Courses table indexes
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_courses_code ON {$schemaName}.courses(course_code)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_courses_global_id ON {$schemaName}.courses(global_course_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_courses_status ON {$schemaName}.courses(status)",
            
            // Enrollments table indexes
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_enrollments_student ON {$schemaName}.enrollments(student_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_enrollments_course ON {$schemaName}.enrollments(course_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_enrollments_status ON {$schemaName}.enrollments(status)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_enrollments_semester ON {$schemaName}.enrollments(semester, academic_year)",
            
            // Grades table indexes
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_grades_enrollment ON {$schemaName}.grades(enrollment_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_grades_student_course ON {$schemaName}.grades(student_id, course_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_grades_assessment ON {$schemaName}.grades(assessment_type)",
            
            // Activity logs indexes
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_activity_logs_subject ON {$schemaName}.activity_logs(subject_type, subject_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_activity_logs_causer ON {$schemaName}.activity_logs(causer_type, causer_id)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_activity_logs_created_at ON {$schemaName}.activity_logs(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_{$schemaName}_activity_logs_event ON {$schemaName}.activity_logs(event)",
        ];
        
        foreach ($indexes as $indexSql) {
            try {
                DB::statement($indexSql);
            } catch (Exception $e) {
                Log::warning("Failed to create index", [
                    'schema' => $schemaName,
                    'sql' => $indexSql,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Set up Row Level Security policies
     */
    public function setupRowLevelSecurity(string $schemaName): void
    {
        $tables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
        
        foreach ($tables as $table) {
            try {
                // Enable RLS on table
                DB::statement("ALTER TABLE {$schemaName}.{$table} ENABLE ROW LEVEL SECURITY");
                
                // Create policy for tenant isolation (though not needed in schema-based approach)
                DB::statement("
                    CREATE POLICY {$table}_tenant_policy ON {$schemaName}.{$table}
                    FOR ALL
                    USING (true)
                ");
                
            } catch (Exception $e) {
                Log::warning("Failed to setup RLS for table", [
                    'schema' => $schemaName,
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Validate tenant schema structure
     */
    public function validateSchemaStructure(string $schemaName): array
    {
        $validation = [
            'schema_name' => $schemaName,
            'valid' => true,
            'checks' => [],
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            // Check if schema exists
            if (!$this->schemaExists($schemaName)) {
                $validation['valid'] = false;
                $validation['errors'][] = "Schema '{$schemaName}' does not exist";
                return $validation;
            }
            
            // Check required tables
            $requiredTables = ['students', 'courses', 'enrollments', 'grades', 'activity_logs'];
            $existingTables = $this->getSchemaTableList($schemaName);
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $existingTables)) {
                    $validation['checks'][$table] = 'exists';
                } else {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Required table '{$table}' is missing";
                }
            }
            
            // Validate table structures
            foreach ($requiredTables as $table) {
                if (in_array($table, $existingTables)) {
                    $tableValidation = $this->validateTableStructure($schemaName, $table);
                    $validation['checks'][$table . '_structure'] = $tableValidation;
                    
                    if (!$tableValidation['valid']) {
                        $validation['valid'] = false;
                        $validation['errors'] = array_merge($validation['errors'], $tableValidation['errors']);
                    }
                    
                    $validation['warnings'] = array_merge($validation['warnings'], $tableValidation['warnings']);
                }
            }
            
            // Check indexes
            $indexValidation = $this->validateSchemaIndexes($schemaName);
            $validation['checks']['indexes'] = $indexValidation;
            $validation['warnings'] = array_merge($validation['warnings'], $indexValidation['warnings']);
            
        } catch (Exception $e) {
            $validation['valid'] = false;
            $validation['errors'][] = "Validation failed: " . $e->getMessage();
        }
        
        return $validation;
    }
    
    /**
     * Validate individual table structure
     */
    protected function validateTableStructure(string $schemaName, string $tableName): array
    {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        $expectedColumns = $this->getExpectedTableColumns($tableName);
        $actualColumns = $this->getTableColumns($schemaName, $tableName);
        
        // Check required columns
        foreach ($expectedColumns as $column => $properties) {
            if (!isset($actualColumns[$column])) {
                $validation['valid'] = false;
                $validation['errors'][] = "Missing column '{$column}' in table '{$tableName}'";
            } else {
                // Check column type
                $actualType = $actualColumns[$column]['data_type'];
                $expectedType = $properties['type'];
                
                if (!$this->isCompatibleType($actualType, $expectedType)) {
                    $validation['warnings'][] = "Column '{$column}' type mismatch: expected {$expectedType}, got {$actualType}";
                }
            }
        }
        
        // Check for unexpected columns (might indicate migration issues)
        foreach ($actualColumns as $column => $properties) {
            if (!isset($expectedColumns[$column])) {
                $validation['warnings'][] = "Unexpected column '{$column}' in table '{$tableName}'";
            }
        }
        
        return $validation;
    }
    
    /**
     * Get expected columns for a table
     */
    protected function getExpectedTableColumns(string $tableName): array
    {
        $columns = [
            'students' => [
                'id' => ['type' => 'bigint', 'nullable' => false],
                'student_id' => ['type' => 'character varying', 'nullable' => false],
                'first_name' => ['type' => 'character varying', 'nullable' => false],
                'last_name' => ['type' => 'character varying', 'nullable' => false],
                'email' => ['type' => 'character varying', 'nullable' => false],
                'phone' => ['type' => 'character varying', 'nullable' => true],
                'date_of_birth' => ['type' => 'date', 'nullable' => true],
                'status' => ['type' => 'character varying', 'nullable' => false],
                'created_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp without time zone', 'nullable' => true]
            ],
            'courses' => [
                'id' => ['type' => 'bigint', 'nullable' => false],
                'course_code' => ['type' => 'character varying', 'nullable' => false],
                'title' => ['type' => 'character varying', 'nullable' => false],
                'description' => ['type' => 'text', 'nullable' => true],
                'credits' => ['type' => 'integer', 'nullable' => false],
                'global_course_id' => ['type' => 'bigint', 'nullable' => true],
                'status' => ['type' => 'character varying', 'nullable' => false],
                'created_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp without time zone', 'nullable' => true]
            ],
            'enrollments' => [
                'id' => ['type' => 'bigint', 'nullable' => false],
                'student_id' => ['type' => 'bigint', 'nullable' => false],
                'course_id' => ['type' => 'bigint', 'nullable' => false],
                'status' => ['type' => 'character varying', 'nullable' => false],
                'semester' => ['type' => 'character varying', 'nullable' => false],
                'academic_year' => ['type' => 'character varying', 'nullable' => false],
                'enrolled_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'created_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp without time zone', 'nullable' => true]
            ],
            'grades' => [
                'id' => ['type' => 'bigint', 'nullable' => false],
                'student_id' => ['type' => 'bigint', 'nullable' => false],
                'course_id' => ['type' => 'bigint', 'nullable' => false],
                'enrollment_id' => ['type' => 'bigint', 'nullable' => false],
                'assessment_type' => ['type' => 'character varying', 'nullable' => false],
                'assessment_name' => ['type' => 'character varying', 'nullable' => false],
                'points_earned' => ['type' => 'numeric', 'nullable' => true],
                'points_possible' => ['type' => 'numeric', 'nullable' => false],
                'percentage' => ['type' => 'numeric', 'nullable' => true],
                'letter_grade' => ['type' => 'character varying', 'nullable' => true],
                'created_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp without time zone', 'nullable' => true]
            ],
            'activity_logs' => [
                'id' => ['type' => 'bigint', 'nullable' => false],
                'log_name' => ['type' => 'character varying', 'nullable' => true],
                'description' => ['type' => 'text', 'nullable' => false],
                'subject_type' => ['type' => 'character varying', 'nullable' => true],
                'subject_id' => ['type' => 'bigint', 'nullable' => true],
                'causer_type' => ['type' => 'character varying', 'nullable' => true],
                'causer_id' => ['type' => 'bigint', 'nullable' => true],
                'properties' => ['type' => 'json', 'nullable' => true],
                'event' => ['type' => 'character varying', 'nullable' => true],
                'created_at' => ['type' => 'timestamp without time zone', 'nullable' => true],
                'updated_at' => ['type' => 'timestamp without time zone', 'nullable' => true]
            ]
        ];
        
        return $columns[$tableName] ?? [];
    }
    
    /**
     * Get actual table columns from database
     */
    protected function getTableColumns(string $schemaName, string $tableName): array
    {
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length
            FROM information_schema.columns 
            WHERE table_schema = ? AND table_name = ?
            ORDER BY ordinal_position
        ", [$schemaName, $tableName]);
        
        $result = [];
        foreach ($columns as $column) {
            $result[$column->column_name] = [
                'data_type' => $column->data_type,
                'nullable' => $column->is_nullable === 'YES',
                'default' => $column->column_default,
                'max_length' => $column->character_maximum_length
            ];
        }
        
        return $result;
    }
    
    /**
     * Check if two data types are compatible
     */
    protected function isCompatibleType(string $actualType, string $expectedType): bool
    {
        $typeMap = [
            'bigint' => ['bigint', 'integer'],
            'integer' => ['integer', 'bigint'],
            'character varying' => ['character varying', 'varchar', 'text'],
            'text' => ['text', 'character varying'],
            'timestamp without time zone' => ['timestamp without time zone', 'timestamp'],
            'numeric' => ['numeric', 'decimal', 'real', 'double precision'],
            'json' => ['json', 'jsonb']
        ];
        
        $compatibleTypes = $typeMap[$expectedType] ?? [$expectedType];
        return in_array($actualType, $compatibleTypes);
    }
    
    /**
     * Validate schema indexes
     */
    protected function validateSchemaIndexes(string $schemaName): array
    {
        $validation = [
            'warnings' => []
        ];
        
        $indexes = DB::select("
            SELECT 
                i.relname as index_name,
                t.relname as table_name,
                a.attname as column_name
            FROM pg_class i
            JOIN pg_index ix ON i.oid = ix.indexrelid
            JOIN pg_class t ON ix.indrelid = t.oid
            JOIN pg_namespace n ON t.relnamespace = n.oid
            JOIN pg_attribute a ON t.oid = a.attrelid AND a.attnum = ANY(ix.indkey)
            WHERE n.nspname = ?
            AND i.relkind = 'i'
            ORDER BY t.relname, i.relname
        ", [$schemaName]);
        
        $indexCount = count($indexes);
        if ($indexCount < 10) {
            $validation['warnings'][] = "Schema has only {$indexCount} indexes, consider adding more for performance";
        }
        
        return $validation;
    }
    
    /**
     * Check if schema exists
     */
    public function schemaExists(string $schemaName): bool
    {
        $result = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schemaName]
        );
        
        return !empty($result);
    }
    
    /**
     * Get list of tables in schema
     */
    public function getSchemaTableList(string $schemaName): array
    {
        $tables = DB::select(
            "SELECT table_name FROM information_schema.tables WHERE table_schema = ?",
            [$schemaName]
        );
        
        return array_map(fn($table) => $table->table_name, $tables);
    }
    
    /**
     * Drop schema and all its contents
     */
    public function dropSchema(string $schemaName, bool $cascade = true): bool
    {
        try {
            $cascadeClause = $cascade ? 'CASCADE' : 'RESTRICT';
            DB::statement("DROP SCHEMA IF EXISTS {$schemaName} {$cascadeClause}");
            
            Log::info("Schema dropped", ['schema_name' => $schemaName]);
            return true;
            
        } catch (Exception $e) {
            Log::error("Failed to drop schema", [
                'schema_name' => $schemaName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Get schema statistics
     */
    public function getSchemaStatistics(string $schemaName): array
    {
        if (!$this->schemaExists($schemaName)) {
            throw new Exception("Schema '{$schemaName}' does not exist");
        }
        
        $stats = [
            'schema_name' => $schemaName,
            'table_count' => 0,
            'total_size_bytes' => 0,
            'tables' => []
        ];
        
        $tableStats = DB::select("
            SELECT 
                t.table_name,
                COALESCE(pg_total_relation_size(n.nspname||'.'||t.table_name), 0) as size_bytes,
                COALESCE(s.n_tup_ins, 0) as inserts,
                COALESCE(s.n_tup_upd, 0) as updates,
                COALESCE(s.n_tup_del, 0) as deletes,
                COALESCE(s.n_live_tup, 0) as live_tuples
            FROM information_schema.tables t
            JOIN pg_namespace n ON n.nspname = t.table_schema
            LEFT JOIN pg_stat_user_tables s ON s.schemaname = t.table_schema AND s.relname = t.table_name
            WHERE t.table_schema = ?
            ORDER BY size_bytes DESC
        ", [$schemaName]);
        
        foreach ($tableStats as $table) {
            $stats['tables'][] = [
                'name' => $table->table_name,
                'size_bytes' => $table->size_bytes,
                'size_human' => $this->formatBytes($table->size_bytes),
                'operations' => [
                    'inserts' => $table->inserts,
                    'updates' => $table->updates,
                    'deletes' => $table->deletes
                ],
                'live_tuples' => $table->live_tuples
            ];
            
            $stats['total_size_bytes'] += $table->size_bytes;
        }
        
        $stats['table_count'] = count($stats['tables']);
        $stats['total_size_human'] = $this->formatBytes($stats['total_size_bytes']);
        
        return $stats;
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Clone schema structure (without data)
     */
    public function cloneSchemaStructure(string $sourceSchema, string $targetSchema): array
    {
        $result = [
            'source_schema' => $sourceSchema,
            'target_schema' => $targetSchema,
            'success' => false,
            'tables_cloned' => [],
            'errors' => []
        ];
        
        try {
            if (!$this->schemaExists($sourceSchema)) {
                throw new Exception("Source schema '{$sourceSchema}' does not exist");
            }
            
            if ($this->schemaExists($targetSchema)) {
                throw new Exception("Target schema '{$targetSchema}' already exists");
            }
            
            // Create target schema
            DB::statement("CREATE SCHEMA {$targetSchema}");
            
            // Get all tables from source schema
            $tables = $this->getSchemaTableList($sourceSchema);
            
            foreach ($tables as $table) {
                try {
                    // Clone table structure
                    DB::statement("
                        CREATE TABLE {$targetSchema}.{$table} 
                        (LIKE {$sourceSchema}.{$table} INCLUDING ALL)
                    ");
                    
                    $result['tables_cloned'][] = $table;
                    
                } catch (Exception $e) {
                    $result['errors'][] = "Failed to clone table '{$table}': " . $e->getMessage();
                }
            }
            
            $result['success'] = empty($result['errors']);
            
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
        }
        
        return $result;
    }
}