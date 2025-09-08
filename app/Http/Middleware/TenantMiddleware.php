<?php
// ABOUTME: Middleware for automatic tenant resolution and context switching based on domain/subdomain
// ABOUTME: Handles tenant identification, schema switching, and request routing for multi-tenant applications

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContextService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        try {
            // Skip tenant resolution for certain routes
            if ($this->shouldSkipTenantResolution($request)) {
                return $next($request);
            }

            // Resolve tenant from request
            $tenant = $this->resolveTenant($request);

            if (!$tenant) {
                return $this->handleTenantNotFound($request);
            }

            // Validate tenant status
            if (!$this->validateTenantStatus($tenant)) {
                return $this->handleInactiveTenant($request, $tenant);
            }

            // Set tenant context
            TenantContextService::setTenant($tenant);

            // Add tenant information to request
            $request->merge(['tenant' => $tenant]);
            $request->attributes->set('tenant', $tenant);

            // Log tenant access
            $this->logTenantAccess($request, $tenant);

            $response = $next($request);

            // Add tenant headers to response
            $this->addTenantHeaders($response, $tenant);

            return $response;

        } catch (Exception $e) {
            Log::error('Tenant middleware error: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->handleTenantError($request, $e);
        }
    }

    /**
     * Resolve tenant from the request
     */
    private function resolveTenant(Request $request): ?Tenant
    {
        // Try multiple resolution strategies
        $strategies = [
            'resolveFromSubdomain',
            'resolveFromDomain',
            'resolveFromHeader',
            'resolveFromParameter',
            'resolveFromCache'
        ];

        foreach ($strategies as $strategy) {
            $tenant = $this->$strategy($request);
            if ($tenant) {
                return $tenant;
            }
        }

        return null;
    }

    /**
     * Resolve tenant from subdomain
     */
    private function resolveFromSubdomain(Request $request): ?Tenant
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
    private function resolveFromDomain(Request $request): ?Tenant
    {
        $domain = $request->getHost();
        return $this->findTenantByIdentifier($domain, 'domain');
    }

    /**
     * Resolve tenant from X-Tenant header
     */
    private function resolveFromHeader(Request $request): ?Tenant
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
    private function resolveFromParameter(Request $request): ?Tenant
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
    private function resolveFromCache(Request $request): ?Tenant
    {
        return TenantContextService::resolveTenantFromCache();
    }

    /**
     * Find tenant by identifier and type
     */
    private function findTenantByIdentifier(string $identifier, string $type): ?Tenant
    {
        $cacheKey = "tenant_lookup_{$type}_{$identifier}";
        
        return Cache::remember($cacheKey, 3600, function() use ($identifier, $type) {
            $query = Tenant::where('status', 'active');
            
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
     * Check if tenant resolution should be skipped
     */
    private function shouldSkipTenantResolution(Request $request): bool
    {
        $skipRoutes = [
            'admin/*',
            'api/system/*',
            'health-check',
            'telescope/*',
            'horizon/*',
            '_debugbar/*'
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Skip for certain domains
        $skipDomains = [
            'admin.' . config('app.domain'),
            'api.' . config('app.domain')
        ];

        if (in_array($request->getHost(), $skipDomains)) {
            return true;
        }

        return false;
    }

    /**
     * Validate tenant status and configuration
     */
    private function validateTenantStatus(Tenant $tenant): bool
    {
        // Check if tenant is active
        if ($tenant->status !== 'active') {
            return false;
        }

        // Check if tenant schema exists
        if (!TenantContextService::schemaExists($tenant->schema_name)) {
            Log::error("Tenant schema does not exist", [
                'tenant_id' => $tenant->id,
                'schema_name' => $tenant->schema_name
            ]);
            return false;
        }

        // Check subscription status if applicable
        if (method_exists($tenant, 'isSubscriptionActive') && !$tenant->isSubscriptionActive()) {
            return false;
        }

        return true;
    }

    /**
     * Handle tenant not found
     */
    private function handleTenantNotFound(Request $request)
    {
        Log::warning('Tenant not found', [
            'url' => $request->fullUrl(),
            'host' => $request->getHost(),
            'ip' => $request->ip()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'The requested tenant could not be found or is not accessible.'
            ], 404);
        }

        // Redirect to main application or show tenant selection
        return redirect()->to(config('app.url') . '/tenant-not-found')
            ->with('error', 'Tenant not found');
    }

    /**
     * Handle inactive tenant
     */
    private function handleInactiveTenant(Request $request, Tenant $tenant)
    {
        Log::warning('Inactive tenant access attempt', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'status' => $tenant->status,
            'url' => $request->fullUrl(),
            'ip' => $request->ip()
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant is currently inactive or suspended.'
            ], 403);
        }

        return redirect()->to(config('app.url') . '/tenant-inactive')
            ->with('error', 'Tenant is currently inactive');
    }

    /**
     * Handle tenant resolution errors
     */
    private function handleTenantError(Request $request, Exception $e)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant resolution failed',
                'message' => 'An error occurred while resolving the tenant context.'
            ], 500);
        }

        return redirect()->to(config('app.url') . '/error')
            ->with('error', 'An error occurred while accessing the application');
    }

    /**
     * Log tenant access for analytics
     */
    private function logTenantAccess(Request $request, Tenant $tenant): void
    {
        try {
            // Log to activity_logs table in tenant schema
            TenantContextService::withTenant($tenant, function() use ($request, $tenant) {
                \DB::table('activity_logs')->insert([
                    'tenant_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'action' => 'tenant_access',
                    'description' => 'Tenant accessed via ' . $request->getMethod() . ' ' . $request->path(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => json_encode([
                        'host' => $request->getHost(),
                        'referer' => $request->header('referer'),
                        'resolution_method' => $this->getResolutionMethod($request)
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            });
        } catch (Exception $e) {
            Log::error('Failed to log tenant access', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the method used to resolve the tenant
     */
    private function getResolutionMethod(Request $request): string
    {
        if ($request->header('X-Tenant')) {
            return 'header';
        }
        
        if ($request->query('tenant')) {
            return 'parameter';
        }
        
        $host = $request->getHost();
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            return 'subdomain';
        }
        
        return 'domain';
    }

    /**
     * Add tenant-specific headers to response
     */
    private function addTenantHeaders(Response $response, Tenant $tenant): void
    {
        $response->headers->set('X-Tenant-ID', $tenant->id);
        $response->headers->set('X-Tenant-Name', $tenant->name);
        $response->headers->set('X-Tenant-Schema', $tenant->schema_name);
        
        // Add cache control for tenant-specific content
        if (!$response->headers->has('Cache-Control')) {
            $response->headers->set('Cache-Control', 'private, max-age=300');
        }
    }

    /**
     * Clean up tenant context after request
     */
    public function terminate(Request $request, Response $response): void
    {
        try {
            // Clear tenant context to prevent memory leaks
            TenantContextService::clearTenant();
        } catch (Exception $e) {
            Log::error('Error during tenant middleware termination: ' . $e->getMessage());
        }
    }
}