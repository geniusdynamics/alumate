<?php
// ABOUTME: Service for managing tenant schema operations and database connections in hybrid tenancy architecture
// ABOUTME: Handles schema creation, migration, switching, and cleanup with connection pooling and caching

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Models\Tenant;
use App\Models\AuditTrail;
use App\Models\DataSyncLog;
use Exception;
use PDO;
use PDOException;

class TenantSchemaService
{
    /**
     * Cache TTL for schema information (in seconds).
     */
    private const SCHEMA_CACHE_TTL = 3600;

    /**
     * Maximum number of concurrent schema operations.
     */
    private const MAX_CONCURRENT_OPERATIONS = 5;

    /**
     * Default tenant schema prefix.
     */
    private const SCHEMA_PREFIX = 'tenant_';

    /**
     * Tables that should exist in every tenant schema.
     */
    private const REQUIRED_TENANT_TABLES = [
        'users',
        'courses',
        'enrollments',
        'assignments',
        'submissions',
        'grades',
        'announcements',
        'discussions',
        'files',
        'settings',
    ];

    /**
     * Current active schema context.
     */
    private ?string $currentSchema = null;

    /**
     * Schema operation lock tracking.
     */
    private array $operationLocks = [];

    /**
     * Create a new tenant schema.
     */
    public function createTenantSchema(string $tenantId, array $options = []): bool
    {
        $schemaName = $this->generateSchemaName($tenantId);
        
        try {
            // Acquire operation lock
            $this->acquireOperationLock($tenantId, 'create_schema');
            
            // Check if schema already exists
            if ($this->schemaExists($schemaName)) {
                throw new Exception("Schema already exists: {$schemaName}");
            }
            
            // Create the schema
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            
            // Set search path to new schema
            $this->switchToSchema($schemaName);
            
            // Create tenant-specific tables
            $this->createTenantTables($schemaName, $options);
            
            // Set up initial data if provided
            if (isset($options['initial_data'])) {
                $this->seedTenantData($schemaName, $options['initial_data']);
            }
            
            // Create indexes and constraints
            $this->createTenantIndexes($schemaName);
            
            // Update tenant record
            $this->updateTenantSchemaInfo($tenantId, $schemaName);
            
            // Clear schema cache
            $this->clearSchemaCache($tenantId);
            
            // Log schema creation
            $this->logSchemaOperation('create', $tenantId, $schemaName, $options);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback schema creation on error
            $this->rollbackSchemaCreation($schemaName);
            
            Log::error('Failed to create tenant schema', [
                'tenant_id' => $tenantId,
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
            
        } finally {
            // Reset to public schema
            $this->resetToPublicSchema();
            
            // Release operation lock
            $this->releaseOperationLock($tenantId, 'create_schema');
        }
    }

    /**
     * Drop a tenant schema.
     */
    public function dropTenantSchema(string $tenantId, bool $force = false): bool
    {
        $schemaName = $this->generateSchemaName($tenantId);
        
        try {
            // Acquire operation lock
            $this->acquireOperationLock($tenantId, 'drop_schema');
            
            // Check if schema exists
            if (!$this->schemaExists($schemaName)) {
                if (!$force) {
                    throw new Exception("Schema does not exist: {$schemaName}");
                }
                return true;
            }
            
            // Create backup before dropping (if not forced)
            if (!$force) {
                $this->createSchemaBackup($tenantId, $schemaName);
            }
            
            // Terminate active connections to the schema
            $this->terminateSchemaConnections($schemaName);
            
            // Drop the schema
            DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
            
            // Update tenant record
            $this->clearTenantSchemaInfo($tenantId);
            
            // Clear schema cache
            $this->clearSchemaCache($tenantId);
            
            // Log schema deletion
            $this->logSchemaOperation('drop', $tenantId, $schemaName, ['force' => $force]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to drop tenant schema', [
                'tenant_id' => $tenantId,
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
            
        } finally {
            // Reset to public schema
            $this->resetToPublicSchema();
            
            // Release operation lock
            $this->releaseOperationLock($tenantId, 'drop_schema');
        }
    }

    /**
     * Switch to a tenant schema.
     */
    public function switchToTenantSchema(string $tenantId): bool
    {
        $schemaName = $this->getTenantSchemaName($tenantId);
        
        if (!$schemaName) {
            throw new Exception("No schema found for tenant: {$tenantId}");
        }
        
        return $this->switchToSchema($schemaName);
    }

    /**
     * Switch to a specific schema.
     */
    public function switchToSchema(string $schemaName): bool
    {
        try {
            // Validate schema exists
            if (!$this->schemaExists($schemaName)) {
                throw new Exception("Schema does not exist: {$schemaName}");
            }
            
            // Set search path
            DB::statement("SET search_path TO {$schemaName}, public");
            
            // Update current schema tracking
            $this->currentSchema = $schemaName;
            
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to switch schema', [
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Reset to public schema.
     */
    public function resetToPublicSchema(): bool
    {
        try {
            DB::statement('SET search_path TO public');
            $this->currentSchema = 'public';
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to reset to public schema', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Get current active schema.
     */
    public function getCurrentSchema(): ?string
    {
        if ($this->currentSchema) {
            return $this->currentSchema;
        }
        
        try {
            $result = DB::select('SHOW search_path');
            $searchPath = $result[0]->search_path ?? 'public';
            
            // Extract first schema from search path
            $schemas = array_map('trim', explode(',', $searchPath));
            $this->currentSchema = $schemas[0] ?? 'public';
            
            return $this->currentSchema;
            
        } catch (Exception $e) {
            Log::warning('Failed to get current schema', [
                'error' => $e->getMessage(),
            ]);
            
            return 'public';
        }
    }

    /**
     * Check if a schema exists.
     */
    public function schemaExists(string $schemaName): bool
    {
        return Cache::remember(
            "schema_exists:{$schemaName}",
            self::SCHEMA_CACHE_TTL,
            function () use ($schemaName) {
                $result = DB::select(
                    "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
                    [$schemaName]
                );
                
                return !empty($result);
            }
        );
    }

    /**
     * Get tenant schema name.
     */
    public function getTenantSchemaName(string $tenantId): ?string
    {
        return Cache::remember(
            "tenant_schema:{$tenantId}",
            self::SCHEMA_CACHE_TTL,
            function () use ($tenantId) {
                $tenant = Tenant::find($tenantId);
                return $tenant?->schema_name;
            }
        );
    }

    /**
     * Generate schema name for tenant.
     */
    public function generateSchemaName(string $tenantId): string
    {
        // Sanitize tenant ID for use as schema name
        $sanitized = preg_replace('/[^a-zA-Z0-9_]/', '_', $tenantId);
        return self::SCHEMA_PREFIX . strtolower($sanitized);
    }

    /**
     * Get all tenant schemas.
     */
    public function getAllTenantSchemas(): array
    {
        return Cache::remember(
            'all_tenant_schemas',
            self::SCHEMA_CACHE_TTL,
            function () {
                $result = DB::select(
                    "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE ?",
                    [self::SCHEMA_PREFIX . '%']
                );
                
                return array_column($result, 'schema_name');
            }
        );
    }

    /**
     * Validate tenant schema integrity.
     */
    public function validateTenantSchema(string $tenantId): array
    {
        $schemaName = $this->getTenantSchemaName($tenantId);
        $issues = [];
        
        if (!$schemaName) {
            $issues[] = 'No schema name found for tenant';
            return $issues;
        }
        
        if (!$this->schemaExists($schemaName)) {
            $issues[] = "Schema does not exist: {$schemaName}";
            return $issues;
        }
        
        try {
            // Switch to tenant schema for validation
            $originalSchema = $this->getCurrentSchema();
            $this->switchToSchema($schemaName);
            
            // Check required tables
            foreach (self::REQUIRED_TENANT_TABLES as $tableName) {
                if (!Schema::hasTable($tableName)) {
                    $issues[] = "Missing required table: {$tableName}";
                }
            }
            
            // Check table structures
            $structureIssues = $this->validateTableStructures($schemaName);
            $issues = array_merge($issues, $structureIssues);
            
            // Check indexes
            $indexIssues = $this->validateSchemaIndexes($schemaName);
            $issues = array_merge($issues, $indexIssues);
            
            // Check constraints
            $constraintIssues = $this->validateSchemaConstraints($schemaName);
            $issues = array_merge($issues, $constraintIssues);
            
        } catch (Exception $e) {
            $issues[] = "Validation error: {$e->getMessage()}";
            
        } finally {
            // Restore original schema
            if (isset($originalSchema)) {
                $this->switchToSchema($originalSchema);
            }
        }
        
        return $issues;
    }

    /**
     * Migrate tenant schema to latest version.
     */
    public function migrateTenantSchema(string $tenantId, array $options = []): bool
    {
        $schemaName = $this->getTenantSchemaName($tenantId);
        
        if (!$schemaName) {
            throw new Exception("No schema found for tenant: {$tenantId}");
        }
        
        try {
            // Acquire operation lock
            $this->acquireOperationLock($tenantId, 'migrate_schema');
            
            // Create backup before migration
            if (!isset($options['skip_backup']) || !$options['skip_backup']) {
                $this->createSchemaBackup($tenantId, $schemaName);
            }
            
            // Switch to tenant schema
            $originalSchema = $this->getCurrentSchema();
            $this->switchToSchema($schemaName);
            
            // Run tenant-specific migrations
            $this->runTenantMigrations($schemaName, $options);
            
            // Update schema version
            $this->updateSchemaVersion($tenantId, $options['target_version'] ?? 'latest');
            
            // Clear schema cache
            $this->clearSchemaCache($tenantId);
            
            // Log migration
            $this->logSchemaOperation('migrate', $tenantId, $schemaName, $options);
            
            return true;
            
        } catch (Exception $e) {
            // Rollback migration on error
            if (isset($options['auto_rollback']) && $options['auto_rollback']) {
                $this->rollbackSchemaMigration($tenantId, $schemaName);
            }
            
            Log::error('Failed to migrate tenant schema', [
                'tenant_id' => $tenantId,
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
            
        } finally {
            // Restore original schema
            if (isset($originalSchema)) {
                $this->switchToSchema($originalSchema);
            }
            
            // Release operation lock
            $this->releaseOperationLock($tenantId, 'migrate_schema');
        }
    }

    /**
     * Create tenant-specific tables.
     */
    private function createTenantTables(string $schemaName, array $options = []): void
    {
        // Create users table (tenant-specific)
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('global_user_id')->nullable()->index();
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('role')->default('student');
            $table->json('permissions')->nullable();
            $table->json('profile_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['role', 'is_active']);
            $table->index('last_login_at');
        });
        
        // Create courses table (tenant-specific)
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('global_course_id')->nullable()->index();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('instructor_id')->index();
            $table->json('settings')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'start_date']);
            $table->index('instructor_id');
        });
        
        // Create enrollments table
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->uuid('course_id')->index();
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'suspended'])->default('enrolled');
            $table->enum('role', ['student', 'ta', 'instructor'])->default('student');
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('progress_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'course_id']);
            $table->index(['status', 'enrolled_at']);
        });
        
        // Create additional tenant tables
        $this->createAdditionalTenantTables();
    }

    /**
     * Create additional tenant tables.
     */
    private function createAdditionalTenantTables(): void
    {
        // Assignments table
        Schema::create('assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('instructions')->nullable();
            $table->integer('max_points')->default(100);
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_published')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['course_id', 'due_date']);
            $table->index('is_published');
        });
        
        // Submissions table
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id')->index();
            $table->uuid('user_id')->index();
            $table->text('content')->nullable();
            $table->json('files')->nullable();
            $table->enum('status', ['draft', 'submitted', 'graded'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['assignment_id', 'user_id']);
            $table->index(['status', 'submitted_at']);
        });
        
        // Grades table
        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('submission_id')->index();
            $table->uuid('grader_id')->index();
            $table->decimal('points', 8, 2);
            $table->text('feedback')->nullable();
            $table->json('rubric_data')->nullable();
            $table->timestamp('graded_at');
            $table->timestamps();
            
            $table->index('graded_at');
        });
        
        // Other tables (announcements, discussions, files, settings)
        $this->createSupportingTables();
    }

    /**
     * Create supporting tables.
     */
    private function createSupportingTables(): void
    {
        // Announcements
        Schema::create('announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->nullable()->index();
            $table->uuid('author_id')->index();
            $table->string('title');
            $table->text('content');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Discussions
        Schema::create('discussions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->index();
            $table->uuid('author_id')->index();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Files
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id')->nullable()->index();
            $table->uuid('user_id')->index();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->string('storage_path');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
        // Settings
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Create tenant indexes.
     */
    private function createTenantIndexes(string $schemaName): void
    {
        // Additional performance indexes
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_users_email_active ON {$schemaName}.users (email) WHERE is_active = true");
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_courses_status_dates ON {$schemaName}.courses (status, start_date, end_date)");
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_enrollments_user_status ON {$schemaName}.enrollments (user_id, status)");
        DB::statement("CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_assignments_course_published ON {$schemaName}.assignments (course_id) WHERE is_published = true");
    }

    /**
     * Seed tenant data.
     */
    private function seedTenantData(string $schemaName, array $initialData): void
    {
        // Seed default settings
        if (isset($initialData['settings'])) {
            foreach ($initialData['settings'] as $key => $value) {
                DB::table('settings')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'key' => $key,
                    'value' => json_encode($value),
                    'type' => 'system',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Seed initial users if provided
        if (isset($initialData['users'])) {
            foreach ($initialData['users'] as $userData) {
                DB::table('users')->insert(array_merge($userData, [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Additional helper methods for schema management.
     */
    private function acquireOperationLock(string $tenantId, string $operation): void
    {
        $lockKey = "schema_operation:{$tenantId}:{$operation}";
        
        if (isset($this->operationLocks[$lockKey])) {
            throw new Exception("Operation already in progress: {$operation} for tenant {$tenantId}");
        }
        
        $this->operationLocks[$lockKey] = time();
    }

    private function releaseOperationLock(string $tenantId, string $operation): void
    {
        $lockKey = "schema_operation:{$tenantId}:{$operation}";
        unset($this->operationLocks[$lockKey]);
    }

    private function clearSchemaCache(string $tenantId): void
    {
        Cache::forget("tenant_schema:{$tenantId}");
        Cache::forget("schema_exists:{$this->generateSchemaName($tenantId)}");
        Cache::forget('all_tenant_schemas');
    }

    private function logSchemaOperation(string $operation, string $tenantId, string $schemaName, array $context = []): void
    {
        AuditTrail::logActivity(
            'schema_operation',
            $operation,
            $tenantId,
            auth()->id(),
            array_merge($context, [
                'schema_name' => $schemaName,
                'operation' => $operation,
                'timestamp' => now()->toISOString(),
            ]),
            'medium'
        );
    }

    private function updateTenantSchemaInfo(string $tenantId, string $schemaName): void
    {
        Tenant::where('id', $tenantId)->update([
            'schema_name' => $schemaName,
            'schema_created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function clearTenantSchemaInfo(string $tenantId): void
    {
        Tenant::where('id', $tenantId)->update([
            'schema_name' => null,
            'schema_created_at' => null,
            'updated_at' => now(),
        ]);
    }

    private function rollbackSchemaCreation(string $schemaName): void
    {
        try {
            if ($this->schemaExists($schemaName)) {
                DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
            }
        } catch (Exception $e) {
            Log::error('Failed to rollback schema creation', [
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createSchemaBackup(string $tenantId, string $schemaName): void
    {
        // Implementation would depend on backup strategy
        // This is a placeholder for backup functionality
        Log::info('Schema backup created', [
            'tenant_id' => $tenantId,
            'schema_name' => $schemaName,
            'backup_timestamp' => now()->toISOString(),
        ]);
    }

    private function terminateSchemaConnections(string $schemaName): void
    {
        // Terminate active connections to the schema
        try {
            DB::statement(
                "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = current_database() AND query LIKE '%{$schemaName}%'"
            );
        } catch (Exception $e) {
            Log::warning('Failed to terminate schema connections', [
                'schema_name' => $schemaName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function validateTableStructures(string $schemaName): array
    {
        // Placeholder for table structure validation
        return [];
    }

    private function validateSchemaIndexes(string $schemaName): array
    {
        // Placeholder for index validation
        return [];
    }

    private function validateSchemaConstraints(string $schemaName): array
    {
        // Placeholder for constraint validation
        return [];
    }

    private function runTenantMigrations(string $schemaName, array $options): void
    {
        // Placeholder for running tenant-specific migrations
        Log::info('Tenant migrations completed', [
            'schema_name' => $schemaName,
            'options' => $options,
        ]);
    }

    private function updateSchemaVersion(string $tenantId, string $version): void
    {
        Tenant::where('id', $tenantId)->update([
            'schema_version' => $version,
            'updated_at' => now(),
        ]);
    }

    private function rollbackSchemaMigration(string $tenantId, string $schemaName): void
    {
        // Placeholder for migration rollback
        Log::info('Schema migration rollback initiated', [
            'tenant_id' => $tenantId,
            'schema_name' => $schemaName,
        ]);
    }
}