<?php
// ABOUTME: Service for managing tenant context in schema-based multi-tenancy
// ABOUTME: Handles tenant resolution, schema switching, and query scoping for schema-based tenancy

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant;
use Exception;

class TenantContextService
{
    protected ?string $currentTenantId = null;
    protected ?string $currentSchema = null;
    protected ?Tenant $currentTenant = null;

    /**
     * Set the current tenant context
     */
    public function setTenant(string $tenantId): void
    {
        $this->currentTenantId = $tenantId;
        $this->currentTenant = null; // Reset cached tenant
        
        // Generate schema name for tenant
        $this->currentSchema = $this->generateSchemaName($tenantId);
        
        // Switch to tenant schema
        $this->switchToTenantSchema($this->currentSchema);
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    /**
     * Get the current tenant instance
     */
    public function getCurrentTenant(): ?Tenant
    {
        if (!$this->currentTenant && $this->currentTenantId) {
            // Switch to public schema to fetch tenant
            $this->switchToPublicSchema();
            $this->currentTenant = Tenant::find($this->currentTenantId);
            // Switch back to tenant schema
            if ($this->currentSchema) {
                $this->switchToTenantSchema($this->currentSchema);
            }
        }
        
        return $this->currentTenant;
    }

    /**
     * Get the current schema name
     */
    public function getCurrentSchema(): ?string
    {
        return $this->currentSchema;
    }

    /**
     * Apply tenant context to a query builder
     */
    public function applyTenantContext(Builder $builder): void
    {
        // In schema-based tenancy, the context is applied by being in the correct schema
        // This method exists for compatibility but doesn't need to filter by tenant_id
        
        // Ensure we're in the correct tenant schema
        if ($this->currentSchema) {
            $this->switchToTenantSchema($this->currentSchema);
        }
    }

    /**
     * Scope query to specific tenant (for administrative purposes)
     */
    public function scopeToTenant(Builder $query, string $tenantId): Builder
    {
        // For schema-based tenancy, this would temporarily switch schema context
        $originalSchema = $this->currentSchema;
        $targetSchema = $this->generateSchemaName($tenantId);
        
        // Switch to target tenant schema
        $this->switchToTenantSchema($targetSchema);
        
        // Note: In a real implementation, you might want to restore the original schema
        // after the query is executed. This is a simplified version.
        
        return $query;
    }

    /**
     * Generate schema name for tenant
     */
    public function generateSchemaName(string $tenantId): string
    {
        return 'tenant_' . $tenantId;
    }

    /**
     * Switch to tenant schema
     */
    public function switchToTenantSchema(string $schemaName): void
    {
        try {
            DB::statement("SET search_path TO {$schemaName}, public");
            $this->currentSchema = $schemaName;
        } catch (Exception $e) {
            throw new Exception("Failed to switch to tenant schema '{$schemaName}': " . $e->getMessage());
        }
    }

    /**
     * Switch to public schema
     */
    public function switchToPublicSchema(): void
    {
        try {
            DB::statement("SET search_path TO public");
        } catch (Exception $e) {
            throw new Exception("Failed to switch to public schema: " . $e->getMessage());
        }
    }

    /**
     * Create tenant schema
     */
    public function createTenantSchema(string $tenantId): string
    {
        $schemaName = $this->generateSchemaName($tenantId);
        
        try {
            // Create schema
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            
            // Switch to new schema
            $this->switchToTenantSchema($schemaName);
            
            // Run migrations for tenant schema
            $this->runTenantMigrations($schemaName);
            
            return $schemaName;
        } catch (Exception $e) {
            throw new Exception("Failed to create tenant schema '{$schemaName}': " . $e->getMessage());
        }
    }

    /**
     * Drop tenant schema
     */
    public function dropTenantSchema(string $tenantId): void
    {
        $schemaName = $this->generateSchemaName($tenantId);
        
        try {
            // Switch to public schema first
            $this->switchToPublicSchema();
            
            // Drop schema
            DB::statement("DROP SCHEMA IF EXISTS {$schemaName} CASCADE");
        } catch (Exception $e) {
            throw new Exception("Failed to drop tenant schema '{$schemaName}': " . $e->getMessage());
        }
    }

    /**
     * Check if tenant schema exists
     */
    public function tenantSchemaExists(string $tenantId): bool
    {
        $schemaName = $this->generateSchemaName($tenantId);
        
        $result = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?",
            [$schemaName]
        );
        
        return !empty($result);
    }

    /**
     * Get list of all tenant schemas
     */
    public function getAllTenantSchemas(): array
    {
        $result = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'tenant_%'"
        );
        
        return array_map(fn($row) => $row->schema_name, $result);
    }

    /**
     * Run migrations for tenant schema
     */
    protected function runTenantMigrations(string $schemaName): void
    {
        // This would run tenant-specific migrations
        // For now, we'll create basic tables that every tenant needs
        
        $this->createBasicTenantTables();
    }

    /**
     * Create basic tables for tenant schema
     */
    protected function createBasicTenantTables(): void
    {
        // Create students table
        if (!Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('student_id')->unique();
                $table->string('first_name');
                $table->string('last_name');
                $table->string('email')->unique();
                $table->date('date_of_birth')->nullable();
                $table->string('phone')->nullable();
                $table->text('address')->nullable();
                $table->date('enrollment_date');
                $table->enum('status', ['active', 'inactive', 'graduated', 'suspended'])->default('active');
                $table->timestamps();
            });
        }

        // Create courses table
        if (!Schema::hasTable('courses')) {
            Schema::create('courses', function (Blueprint $table) {
                $table->id();
                $table->string('course_code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('credits');
                $table->string('department');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        // Create enrollments table
        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->date('enrollment_date');
                $table->enum('status', ['enrolled', 'completed', 'dropped', 'failed'])->default('enrolled');
                $table->decimal('grade', 5, 2)->nullable();
                $table->timestamps();
                
                $table->unique(['student_id', 'course_id']);
            });
        }

        // Create grades table
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('course_id')->constrained()->onDelete('cascade');
                $table->string('assignment_name');
                $table->decimal('score', 5, 2);
                $table->decimal('max_score', 5, 2);
                $table->date('graded_date');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Clear tenant context
     */
    public function clearContext(): void
    {
        $this->currentTenantId = null;
        $this->currentSchema = null;
        $this->currentTenant = null;
        
        // Switch back to public schema
        $this->switchToPublicSchema();
    }

    /**
     * Execute callback in tenant context
     */
    public function runInTenantContext(string $tenantId, callable $callback)
    {
        $originalTenantId = $this->currentTenantId;
        $originalSchema = $this->currentSchema;
        
        try {
            $this->setTenant($tenantId);
            return $callback();
        } finally {
            // Restore original context
            if ($originalTenantId) {
                $this->setTenant($originalTenantId);
            } else {
                $this->clearContext();
            }
        }
    }

    /**
     * Execute callback in tenant context (alias for BaseService compatibility)
     */
    public function executeInTenantContext(string $tenantId, callable $callback)
    {
        return $this->runInTenantContext($tenantId, $callback);
    }

    /**
     * Validate tenant access for the current user
     */
    public function validateTenantAccess(string $tenantId): bool
    {
        // Basic validation - check if tenant exists and user has access
        if (!$this->tenantSchemaExists($tenantId)) {
            return false;
        }

        // Additional access validation logic would go here
        // For now, we'll assume access is granted if tenant exists
        return true;
    }

    /**
     * Get tenant-specific configuration
     */
    public function getTenantConfig(string $key, $default = null)
    {
        // This would retrieve tenant-specific configuration from database or cache
        // For now, return default value
        return $default;
    }
}