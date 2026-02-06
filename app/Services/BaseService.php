<?php

namespace App\Services;

use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ABOUTME: Base service class providing common tenant functionality for schema-based multi-tenancy
 * ABOUTME: Handles tenant context management, schema switching, and common service operations
 */
abstract class BaseService
{
    protected TenantContextService $tenantContext;
    protected ?string $currentTenantId = null;
    protected ?string $currentSchema = null;

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
        $this->initializeTenantContext();
    }

    /**
     * Initialize tenant context for the service
     */
    protected function initializeTenantContext(): void
    {
        try {
            $this->currentTenantId = $this->tenantContext->getCurrentTenantId();
            $this->currentSchema = $this->tenantContext->getCurrentSchema();
        } catch (Exception $e) {
            Log::warning('Failed to initialize tenant context in service', [
                'service' => static::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute a callback within a specific tenant context
     */
    protected function withTenantContext(string $tenantId, callable $callback)
    {
        return $this->tenantContext->executeInTenantContext($tenantId, $callback);
    }

    /**
     * Execute a callback within the current tenant context
     */
    protected function withCurrentTenantContext(callable $callback)
    {
        if (!$this->currentTenantId) {
            throw new Exception('No current tenant context available');
        }

        return $this->withTenantContext($this->currentTenantId, $callback);
    }

    /**
     * Get the current tenant ID
     */
    protected function getCurrentTenantId(): ?string
    {
        return $this->currentTenantId ?? $this->tenantContext->getCurrentTenantId();
    }

    /**
     * Get the current schema name
     */
    protected function getCurrentSchema(): ?string
    {
        return $this->currentSchema ?? $this->tenantContext->getCurrentSchema();
    }

    /**
     * Switch to a specific tenant schema
     */
    protected function switchToTenantSchema(string $tenantId): void
    {
        $this->tenantContext->setTenantContext($tenantId);
        $this->currentTenantId = $tenantId;
        $this->currentSchema = $this->tenantContext->getCurrentSchema();
    }

    /**
     * Ensure we're operating within a tenant context
     */
    protected function ensureTenantContext(): void
    {
        if (!$this->getCurrentTenantId()) {
            throw new Exception('Tenant context is required for this operation');
        }
    }

    /**
     * Log an activity with tenant context
     */
    protected function logActivity(string $action, string $description, array $context = [], string $level = 'info'): void
    {
        $logContext = array_merge($context, [
            'service' => static::class,
            'tenant_id' => $this->getCurrentTenantId(),
            'schema' => $this->getCurrentSchema(),
            'action' => $action,
        ]);

        Log::log($level, $description, $logContext);
    }

    /**
     * Handle cross-tenant operations safely
     */
    protected function executeCrossTenantOperation(array $tenantIds, callable $callback): array
    {
        $results = [];
        $originalTenantId = $this->getCurrentTenantId();

        try {
            foreach ($tenantIds as $tenantId) {
                $results[$tenantId] = $this->withTenantContext($tenantId, $callback);
            }
        } finally {
            // Restore original tenant context
            if ($originalTenantId) {
                $this->switchToTenantSchema($originalTenantId);
            }
        }

        return $results;
    }

    /**
     * Validate tenant access for the current user
     */
    protected function validateTenantAccess(string $tenantId): bool
    {
        // This would typically check if the current user has access to the specified tenant
        // Implementation depends on your authorization logic
        return $this->tenantContext->validateTenantAccess($tenantId);
    }

    /**
     * Get tenant-specific configuration
     */
    protected function getTenantConfig(string $key, $default = null)
    {
        $this->ensureTenantContext();
        
        // This would retrieve tenant-specific configuration
        // Implementation depends on how you store tenant configs
        return $this->tenantContext->getTenantConfig($key, $default);
    }

    /**
     * Handle service errors with tenant context
     */
    protected function handleServiceError(Exception $e, string $operation, array $context = []): void
    {
        $this->logActivity(
            'error',
            "Service error during {$operation}: {$e->getMessage()}",
            array_merge($context, [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]),
            'error'
        );

        throw $e;
    }

    /**
     * Execute a database transaction within tenant context
     */
    protected function executeInTransaction(callable $callback)
    {
        $this->ensureTenantContext();
        
        return \Illuminate\Support\Facades\DB::transaction($callback);
    }

    /**
     * Get paginated results with tenant context
     */
    protected function getPaginatedResults($query, int $perPage = 15, array $columns = ['*'])
    {
        $this->ensureTenantContext();
        
        return $query->paginate($perPage, $columns);
    }

    /**
     * Apply common filters to a query
     */
    protected function applyCommonFilters($query, array $filters = [])
    {
        // Apply tenant-specific filters if needed
        // Most filtering is now handled by schema isolation
        
        if (isset($filters['active']) && $filters['active'] !== null) {
            $query->where('is_active', $filters['active']);
        }

        if (isset($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (isset($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }

        return $query;
    }

    /**
     * Cache key with tenant context
     */
    protected function getTenantCacheKey(string $key): string
    {
        $tenantId = $this->getCurrentTenantId();
        return "tenant:{$tenantId}:{$key}";
    }

    /**
     * Remember data in cache with tenant context
     */
    protected function rememberInTenantCache(string $key, callable $callback, int $ttl = 3600)
    {
        $cacheKey = $this->getTenantCacheKey($key);
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Forget cached data with tenant context
     */
    protected function forgetTenantCache(string $key): void
    {
        $cacheKey = $this->getTenantCacheKey($key);
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
    }
}