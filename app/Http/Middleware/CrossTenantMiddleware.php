<?php
// ABOUTME: Middleware for handling cross-tenant operations and schema switching in hybrid tenancy architecture
// ABOUTME: Manages tenant context, schema switching, and cross-tenant access permissions with audit logging

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\GlobalUser;
use App\Models\UserTenantMembership;
use App\Models\AuditTrail;
use App\Services\CrossTenantSyncService;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class CrossTenantMiddleware
{
    /**
     * Cross-tenant sync service instance.
     */
    private CrossTenantSyncService $syncService;

    /**
     * Cache TTL for tenant context (in seconds).
     */
    private const TENANT_CACHE_TTL = 3600;

    /**
     * Maximum allowed cross-tenant operations per request.
     */
    private const MAX_CROSS_TENANT_OPS = 10;

    /**
     * Create a new middleware instance.
     */
    public function __construct(CrossTenantSyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Extract tenant context from request
        $tenantContext = $this->extractTenantContext($request);
        
        // Validate cross-tenant access permissions
        $this->validateCrossTenantAccess($request, $tenantContext);
        
        // Set up tenant schema context
        $this->setupTenantContext($tenantContext);
        
        // Log cross-tenant operation
        $this->logCrossTenantOperation($request, $tenantContext);
        
        try {
            // Process the request
            $response = $next($request);
            
            // Handle post-request synchronization if needed
            $this->handlePostRequestSync($request, $tenantContext);
            
            return $response;
            
        } catch (Exception $e) {
            // Log error and reset schema context
            $this->handleCrossTenantError($e, $request, $tenantContext);
            throw $e;
            
        } finally {
            // Always reset to default schema
            $this->resetSchemaContext();
        }
    }

    /**
     * Extract tenant context from the request.
     */
    private function extractTenantContext(Request $request): array
    {
        $context = [
            'primary_tenant_id' => null,
            'target_tenant_ids' => [],
            'operation_type' => 'single_tenant',
            'requires_global_access' => false,
            'cross_tenant_operation' => false,
        ];

        // Check for tenant ID in various request sources
        $primaryTenantId = $this->getPrimaryTenantId($request);
        if ($primaryTenantId) {
            $context['primary_tenant_id'] = $primaryTenantId;
        }

        // Check for cross-tenant operation indicators
        $targetTenantIds = $this->getTargetTenantIds($request);
        if (!empty($targetTenantIds)) {
            $context['target_tenant_ids'] = $targetTenantIds;
            $context['cross_tenant_operation'] = true;
            $context['operation_type'] = 'cross_tenant';
        }

        // Check for global operations (super admin analytics, etc.)
        if ($this->isGlobalOperation($request)) {
            $context['requires_global_access'] = true;
            $context['operation_type'] = 'global';
        }

        // Determine if this is a multi-tenant user operation
        if ($this->isMultiTenantUserOperation($request)) {
            $context['operation_type'] = 'multi_tenant_user';
            $context['cross_tenant_operation'] = true;
        }

        return $context;
    }

    /**
     * Get primary tenant ID from request.
     */
    private function getPrimaryTenantId(Request $request): ?string
    {
        // Check header
        if ($request->hasHeader('X-Tenant-ID')) {
            return $request->header('X-Tenant-ID');
        }

        // Check route parameter
        if ($request->route('tenant_id')) {
            return $request->route('tenant_id');
        }

        // Check query parameter
        if ($request->query('tenant_id')) {
            return $request->query('tenant_id');
        }

        // Check subdomain
        $subdomain = $this->extractSubdomain($request);
        if ($subdomain) {
            $tenant = Cache::remember(
                "tenant_by_subdomain:{$subdomain}",
                self::TENANT_CACHE_TTL,
                fn() => Tenant::where('subdomain', $subdomain)->first()
            );
            return $tenant?->id;
        }

        // Check user's default tenant
        if (Auth::check()) {
            $user = Auth::user();
            if ($user instanceof GlobalUser) {
                return $user->getDefaultTenantId();
            }
        }

        return null;
    }

    /**
     * Get target tenant IDs for cross-tenant operations.
     */
    private function getTargetTenantIds(Request $request): array
    {
        $tenantIds = [];

        // Check for multiple tenant IDs in request body
        if ($request->has('tenant_ids') && is_array($request->input('tenant_ids'))) {
            $tenantIds = array_merge($tenantIds, $request->input('tenant_ids'));
        }

        // Check for cross-tenant sync operations
        if ($request->has('sync_tenant_ids') && is_array($request->input('sync_tenant_ids'))) {
            $tenantIds = array_merge($tenantIds, $request->input('sync_tenant_ids'));
        }

        // Check for multi-institutional user operations
        if ($request->has('user_id') && $this->isMultiTenantUserOperation($request)) {
            $userId = $request->input('user_id');
            $userTenants = $this->getUserTenantIds($userId);
            $tenantIds = array_merge($tenantIds, $userTenants);
        }

        // Remove duplicates and validate
        $tenantIds = array_unique($tenantIds);
        
        // Limit the number of cross-tenant operations
        if (count($tenantIds) > self::MAX_CROSS_TENANT_OPS) {
            throw new Exception(
                "Too many cross-tenant operations requested. Maximum allowed: " . self::MAX_CROSS_TENANT_OPS
            );
        }

        return $tenantIds;
    }

    /**
     * Check if this is a global operation.
     */
    private function isGlobalOperation(Request $request): bool
    {
        $globalRoutes = [
            'admin.analytics.*',
            'admin.super.*',
            'api.admin.analytics.*',
            'api.admin.global.*',
            'sync.global.*',
        ];

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return false;
        }

        foreach ($globalRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Check for global operation indicators in request
        return $request->has('global_operation') || 
               $request->has('super_admin_analytics') ||
               str_contains($request->path(), '/admin/global/');
    }

    /**
     * Check if this is a multi-tenant user operation.
     */
    private function isMultiTenantUserOperation(Request $request): bool
    {
        $multiTenantRoutes = [
            'users.cross-tenant.*',
            'api.users.memberships.*',
            'api.users.institutions.*',
            'sync.users.*',
        ];

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return false;
        }

        foreach ($multiTenantRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Check for multi-tenant user indicators
        return $request->has('multi_tenant_user') ||
               $request->has('cross_institutional') ||
               str_contains($request->path(), '/users/cross-tenant/');
    }

    /**
     * Validate cross-tenant access permissions.
     */
    private function validateCrossTenantAccess(Request $request, array $context): void
    {
        if (!Auth::check()) {
            throw new Exception('Authentication required for cross-tenant operations');
        }

        $user = Auth::user();
        
        // For global operations, check super admin permissions
        if ($context['requires_global_access']) {
            $this->validateGlobalAccess($user, $request);
            return;
        }

        // For cross-tenant operations, validate access to all target tenants
        if ($context['cross_tenant_operation']) {
            $this->validateCrossTenantPermissions($user, $context, $request);
            return;
        }

        // For single tenant operations, validate tenant access
        if ($context['primary_tenant_id']) {
            $this->validateTenantAccess($user, $context['primary_tenant_id'], $request);
        }
    }

    /**
     * Validate global access permissions.
     */
    private function validateGlobalAccess($user, Request $request): void
    {
        if (!($user instanceof GlobalUser)) {
            throw new Exception('Global operations require global user account');
        }

        if (!$user->isSuperAdmin()) {
            throw new Exception('Super admin privileges required for global operations');
        }

        // Log super admin access
        AuditTrail::logActivity(
            'super_admin_access',
            'global_operation',
            null,
            $user->id,
            [
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'high'
        );
    }

    /**
     * Validate cross-tenant permissions.
     */
    private function validateCrossTenantPermissions($user, array $context, Request $request): void
    {
        if (!($user instanceof GlobalUser)) {
            throw new Exception('Cross-tenant operations require global user account');
        }

        $allTenantIds = array_merge(
            [$context['primary_tenant_id']],
            $context['target_tenant_ids']
        );
        $allTenantIds = array_filter(array_unique($allTenantIds));

        foreach ($allTenantIds as $tenantId) {
            $membership = UserTenantMembership::where('global_user_id', $user->id)
                                            ->where('tenant_id', $tenantId)
                                            ->where('status', 'active')
                                            ->first();

            if (!$membership) {
                throw new Exception("Access denied to tenant: {$tenantId}");
            }

            // Check if user has sufficient permissions for the operation
            $requiredPermission = $this->getRequiredPermission($request);
            if ($requiredPermission && !$membership->hasPermission($requiredPermission)) {
                throw new Exception(
                    "Insufficient permissions for tenant {$tenantId}. Required: {$requiredPermission}"
                );
            }
        }

        // Log cross-tenant access
        AuditTrail::logActivity(
            'cross_tenant_access',
            'multi_tenant_operation',
            null,
            $user->id,
            [
                'tenant_ids' => $allTenantIds,
                'operation_type' => $context['operation_type'],
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
            ],
            'medium'
        );
    }

    /**
     * Validate single tenant access.
     */
    private function validateTenantAccess($user, string $tenantId, Request $request): void
    {
        if ($user instanceof GlobalUser) {
            $membership = UserTenantMembership::where('global_user_id', $user->id)
                                            ->where('tenant_id', $tenantId)
                                            ->where('status', 'active')
                                            ->first();

            if (!$membership) {
                throw new Exception("Access denied to tenant: {$tenantId}");
            }

            $requiredPermission = $this->getRequiredPermission($request);
            if ($requiredPermission && !$membership->hasPermission($requiredPermission)) {
                throw new Exception(
                    "Insufficient permissions for tenant {$tenantId}. Required: {$requiredPermission}"
                );
            }
        }
    }

    /**
     * Set up tenant context for the request.
     */
    private function setupTenantContext(array $context): void
    {
        // Store context in request for later use
        request()->merge(['_tenant_context' => $context]);

        // Set primary tenant schema if specified
        if ($context['primary_tenant_id'] && !$context['requires_global_access']) {
            $this->switchToTenantSchema($context['primary_tenant_id']);
        }

        // For global operations, ensure we're using the default schema
        if ($context['requires_global_access']) {
            $this->resetSchemaContext();
        }
    }

    /**
     * Log cross-tenant operation.
     */
    private function logCrossTenantOperation(Request $request, array $context): void
    {
        if (!$context['cross_tenant_operation'] && !$context['requires_global_access']) {
            return;
        }

        $user = Auth::user();
        $operationType = $context['requires_global_access'] ? 'global_operation' : 'cross_tenant_operation';

        AuditTrail::logActivity(
            $operationType,
            'middleware_access',
            null,
            $user?->id,
            [
                'tenant_context' => $context,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $request->header('X-Request-ID') ?? uniqid(),
            ],
            $context['requires_global_access'] ? 'high' : 'medium'
        );
    }

    /**
     * Handle post-request synchronization.
     */
    private function handlePostRequestSync(Request $request, array $context): void
    {
        // Check if synchronization is needed based on the operation
        if (!$this->requiresPostRequestSync($request, $context)) {
            return;
        }

        try {
            // Determine sync type based on the request
            $syncType = $this->determineSyncType($request);
            
            if ($syncType && $context['cross_tenant_operation']) {
                // Perform cross-tenant synchronization
                $this->performPostRequestSync($context, $syncType, $request);
            }
            
        } catch (Exception $e) {
            // Log sync error but don't fail the request
            Log::error('Post-request sync failed', [
                'error' => $e->getMessage(),
                'context' => $context,
                'request_path' => $request->path(),
            ]);
        }
    }

    /**
     * Handle cross-tenant operation errors.
     */
    private function handleCrossTenantError(Exception $e, Request $request, array $context): void
    {
        Log::error('Cross-tenant operation failed', [
            'error' => $e->getMessage(),
            'context' => $context,
            'request_path' => $request->path(),
            'user_id' => Auth::id(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Log error in audit trail
        AuditTrail::logActivity(
            'cross_tenant_error',
            'operation_failed',
            null,
            Auth::id(),
            [
                'error_message' => $e->getMessage(),
                'tenant_context' => $context,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'method' => $request->method(),
            ],
            'high'
        );
    }

    /**
     * Switch to tenant schema.
     */
    private function switchToTenantSchema(string $tenantId): void
    {
        $tenant = Cache::remember(
            "tenant:{$tenantId}",
            self::TENANT_CACHE_TTL,
            fn() => Tenant::find($tenantId)
        );

        if (!$tenant) {
            throw new Exception("Tenant not found: {$tenantId}");
        }

        DB::statement("SET search_path TO {$tenant->schema_name}, public");
    }

    /**
     * Reset to default schema.
     */
    private function resetSchemaContext(): void
    {
        DB::statement('SET search_path TO public');
    }

    /**
     * Extract subdomain from request.
     */
    private function extractSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        // Return subdomain if it exists and is not 'www'
        if (count($parts) > 2 && $parts[0] !== 'www') {
            return $parts[0];
        }
        
        return null;
    }

    /**
     * Get user's tenant IDs.
     */
    private function getUserTenantIds(string $userId): array
    {
        return Cache::remember(
            "user_tenants:{$userId}",
            self::TENANT_CACHE_TTL,
            function () use ($userId) {
                return UserTenantMembership::where('global_user_id', $userId)
                                         ->where('status', 'active')
                                         ->pluck('tenant_id')
                                         ->toArray();
            }
        );
    }

    /**
     * Get required permission for the request.
     */
    private function getRequiredPermission(Request $request): ?string
    {
        $method = $request->method();
        $path = $request->path();
        
        // Define permission mapping based on routes and methods
        $permissionMap = [
            'GET' => 'read',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];
        
        $basePermission = $permissionMap[$method] ?? 'read';
        
        // Check for admin routes
        if (str_contains($path, '/admin/')) {
            return 'admin';
        }
        
        // Check for specific resource permissions
        if (str_contains($path, '/users/')) {
            return "users.{$basePermission}";
        }
        
        if (str_contains($path, '/courses/')) {
            return "courses.{$basePermission}";
        }
        
        if (str_contains($path, '/enrollments/')) {
            return "enrollments.{$basePermission}";
        }
        
        return $basePermission;
    }

    /**
     * Check if post-request sync is required.
     */
    private function requiresPostRequestSync(Request $request, array $context): bool
    {
        // Only sync for write operations
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return false;
        }

        // Check if this is a sync-triggering operation
        $syncTriggeringRoutes = [
            'users.*',
            'courses.*',
            'enrollments.*',
            'api.users.*',
            'api.courses.*',
            'api.enrollments.*',
        ];

        $routeName = $request->route()?->getName();
        if (!$routeName) {
            return false;
        }

        foreach ($syncTriggeringRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine sync type based on request.
     */
    private function determineSyncType(Request $request): ?string
    {
        $path = $request->path();
        
        if (str_contains($path, '/users/')) {
            return 'user_sync';
        }
        
        if (str_contains($path, '/courses/')) {
            return 'course_sync';
        }
        
        if (str_contains($path, '/enrollments/')) {
            return 'enrollment_sync';
        }
        
        return null;
    }

    /**
     * Perform post-request synchronization.
     */
    private function performPostRequestSync(array $context, string $syncType, Request $request): void
    {
        $tenantIds = array_merge(
            [$context['primary_tenant_id']],
            $context['target_tenant_ids']
        );
        $tenantIds = array_filter(array_unique($tenantIds));

        foreach ($tenantIds as $tenantId) {
            try {
                $this->syncService->syncTenantDataToGlobal(
                    $tenantId,
                    $syncType,
                    null,
                    [
                        'triggered_by' => 'middleware',
                        'request_id' => $request->header('X-Request-ID') ?? uniqid(),
                        'user_id' => Auth::id(),
                    ]
                );
            } catch (Exception $e) {
                Log::warning('Post-request sync failed for tenant', [
                    'tenant_id' => $tenantId,
                    'sync_type' => $syncType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}