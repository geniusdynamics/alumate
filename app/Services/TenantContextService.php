<?php

// ABOUTME: Service for managing tenant context in schema-based multi-tenancy
// ABOUTME: Handles tenant resolution, schema switching, and query scoping for schema-based tenancy

namespace App\Services;

use App\Models\Tenant;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        $resolvedSchema = $this->resolveTenantSchemaName($tenantId);
        $this->currentTenantId = $tenantId;
        $this->currentTenant = null;
        $this->currentSchema = $resolvedSchema;
        $this->switchToTenantSchema($resolvedSchema);
    }

    public function setCurrentTenant(?string $tenantId): void
    {
        if ($tenantId === null || $tenantId === '') {
            throw new Exception('Invalid tenant ID');
        }

        $this->setTenant($tenantId);
    }

    /**
     * Get the current tenant ID
     */
    public function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId;
    }

    public function clearCurrentTenant(): void
    {
        $this->clearContext();
    }

    /**
     * Get the current tenant instance
     */
    public function getCurrentTenant(): ?Tenant
    {
        if (! $this->currentTenant && $this->currentTenantId) {
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
     * Handles UUID tenant IDs by converting them to a safe format
     */
    public function generateSchemaName(string $tenantId): string
    {
        // For UUIDs, replace hyphens with underscores to create valid schema names
        $safeTenantId = str_replace('-', '_', $tenantId);

        return 'tenant_'.$safeTenantId;
    }

    public function getTenantSchema(string $tenantId): string
    {
        return $this->resolveTenantSchemaName($tenantId);
    }

    /**
     * Switch to tenant schema
     */
    public function switchToTenantSchema(string $schemaName): void
    {
        $this->assertSafeSchemaName($schemaName);
        try {
            DB::statement("SET search_path TO {$schemaName}, public");
            $this->currentSchema = $schemaName;
        } catch (Exception $e) {
            $this->reportTenantContextFailure('switch_to_tenant_schema', [
                'schema' => $schemaName,
                'error' => $e->getMessage(),
            ]);
            throw new Exception("Failed to switch to tenant schema '{$schemaName}': ".$e->getMessage());
        }
    }

    /**
     * Switch to public schema
     */
    public function switchToPublicSchema(): void
    {
        try {
            DB::statement('SET search_path TO public');
        } catch (Exception $e) {
            $this->reportTenantContextFailure('switch_to_public_schema', [
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to switch to public schema: '.$e->getMessage());
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
            throw new Exception("Failed to create tenant schema '{$schemaName}': ".$e->getMessage());
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
            throw new Exception("Failed to drop tenant schema '{$schemaName}': ".$e->getMessage());
        }
    }

    /**
     * Check if tenant schema exists
     */
    public function tenantSchemaExists(string $tenantId): bool
    {
        $schemaName = $this->generateSchemaName($tenantId);

        $result = DB::select(
            'SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?',
            [$schemaName]
        );

        return ! empty($result);
    }

    public function schemaExists(string $schemaName): bool
    {
        $this->assertSafeSchemaName($schemaName);
        $result = DB::select(
            'SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?',
            [$schemaName]
        );

        return ! empty($result);
    }

    public function resolveTenantSchemaName(string $tenantId): string
    {
        $tenant = $this->getTenantByIdFromPublicSchema($tenantId);
        $resolvedSchema = $tenant?->schema_name ?: $this->generateSchemaName($tenantId);

        $this->assertSafeSchemaName($resolvedSchema);

        $expectedSchema = $this->generateSchemaName($tenantId);
        if ($tenant !== null && $tenant->schema_name !== null && $tenant->schema_name !== $expectedSchema) {
            $this->reportTenantContextFailure('tenant_schema_drift_detected', [
                'tenant_id' => $tenantId,
                'resolved_schema' => $resolvedSchema,
                'expected_schema' => $expectedSchema,
            ]);
        }

        if (config('tenancy.safeguards.require_existing_schema', false) && ! $this->schemaExists($resolvedSchema)) {
            $this->reportTenantContextFailure('tenant_schema_missing', [
                'tenant_id' => $tenantId,
                'resolved_schema' => $resolvedSchema,
            ]);

            throw new Exception("Tenant schema does not exist: {$resolvedSchema}");
        }

        return $resolvedSchema;
    }

    /**
     * Get list of all tenant schemas
     */
    public function getAllTenantSchemas(): array
    {
        $result = DB::select(
            "SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'tenant_%'"
        );

        return array_map(fn ($row) => $row->schema_name, $result);
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
        if (! Schema::hasTable('students')) {
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
        if (! Schema::hasTable('courses')) {
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
        if (! Schema::hasTable('enrollments')) {
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
        if (! Schema::hasTable('grades')) {
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

    public function withinTenantContext(string $tenantId, callable $callback)
    {
        return $this->runInTenantContext($tenantId, $callback);
    }

    public function withTenant(string $tenantId, callable $callback)
    {
        return $this->runInTenantContext($tenantId, $callback);
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
    public function validateTenantAccess(?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        if (! $tenantId) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Super admins can access any tenant
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Check if user belongs to this tenant
        return $user->tenants()->where('tenant_id', $tenantId)->exists();
    }

    /**
     * Resolve tenant from request (used by TenancyServiceProvider)
     */
    public function resolveFromRequest(\Illuminate\Http\Request $request): ?\App\Models\Tenant
    {
        // Try multiple resolution strategies
        $strategies = [
            'resolveFromSubdomain',
            'resolveFromDomain',
            'resolveFromHeader',
            'resolveFromParameter',
            'resolveFromCache',
        ];

        foreach ($strategies as $strategy) {
            $tenant = $this->$strategy($request);
            if ($tenant) {
                $this->setTenant($tenant->id);

                return $tenant;
            }
        }

        return null;
    }

    /**
     * Resolve tenant from subdomain
     */
    private function resolveFromSubdomain(\Illuminate\Http\Request $request): ?\App\Models\Tenant
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        // Check if we have a subdomain (more than 2 parts for .com domains)
        if (count($parts) >= 3) {
            $subdomain = $parts[0];

            // Skip common subdomains
            if (in_array($subdomain, ['www', 'api', 'admin', 'app'])) {
                return null;
            }

            return $this->findTenantByIdentifier($subdomain, 'subdomain');
        }

        return null;
    }

    /**
     * Resolve tenant from custom domain
     */
    private function resolveFromDomain(\Illuminate\Http\Request $request): ?\App\Models\Tenant
    {
        $domain = $request->getHost();

        return $this->findTenantByIdentifier($domain, 'domain');
    }

    /**
     * Resolve tenant from X-Tenant header
     */
    private function resolveFromHeader(\Illuminate\Http\Request $request): ?\App\Models\Tenant
    {
        $tenantIdentifier = $request->header('X-Tenant');

        if ($tenantIdentifier) {
            return $this->findTenantByIdentifier($tenantIdentifier, 'slug');
        }

        return null;
    }

    /**
     * Resolve tenant from query parameter
     */
    private function resolveFromParameter(\Illuminate\Http\Request $request): ?\App\Models\Tenant
    {
        $tenantIdentifier = $request->query('tenant');

        if ($tenantIdentifier) {
            return $this->findTenantByIdentifier($tenantIdentifier, 'slug');
        }

        return null;
    }

    /**
     * Resolve tenant from cache
     */
    public function resolveTenantFromCache(): ?\App\Models\Tenant
    {
        $tenantId = $this->getCurrentTenantId();

        if (! $tenantId) {
            return null;
        }

        $cacheKey = "tenant_model_{$tenantId}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            return \App\Models\Tenant::find($tenantId);
        });
    }

    /**
     * Find tenant by identifier and type
     */
    private function findTenantByIdentifier(string $identifier, string $type): ?\App\Models\Tenant
    {
        $cacheKey = "tenant_lookup_{$type}_{$identifier}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($identifier, $type) {
            $query = \App\Models\Tenant::where('status', 'active');

            switch ($type) {
                case 'subdomain':
                    return $query->where('subdomain', $identifier)->first();
                case 'domain':
                    return $query->where('custom_domain', $identifier)->first();
                case 'slug':
                    return $query->where('slug', $identifier)->first();
                default:
                    return null;
            }
        });
    }

    /**
     * Get tenant-specific configuration
     */
    public function getTenantConfig(string $key, $default = null)
    {
        return $default;
    }

    private function assertSafeSchemaName(string $schemaName): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $schemaName)) {
            throw new Exception("Invalid schema name: {$schemaName}");
        }
    }

    private function getTenantByIdFromPublicSchema(string $tenantId): ?Tenant
    {
        $originalSchema = $this->currentSchema;

        try {
            $this->switchToPublicSchema();

            return Tenant::find($tenantId);
        } finally {
            if ($originalSchema !== null) {
                $this->switchToTenantSchema($originalSchema);
            }
        }
    }

    private function reportTenantContextFailure(string $event, array $context): void
    {
        if (! config('tenancy.safeguards.log_context_failures', true)) {
            return;
        }

        Log::warning('tenant_context_event', array_merge([
            'event' => $event,
            'tenant_id' => $this->currentTenantId,
            'schema' => $this->currentSchema,
        ], $context));
    }
}
