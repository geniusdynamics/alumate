<?php

namespace App\Http\Middleware;

use App\Models\TenantOnboarding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnsureTenantOnboarded
{
    /**
     * Routes that should be excluded from onboarding check.
     */
    protected array $excludedRoutes = [
        'tenant.onboarding.*',
        'login',
        'logout',
        'register',
        'password.*',
        'api.*',
        'health-check',
        'horizon.*',
        'telescope.*',
        '_debugbar.*',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Skip check for excluded routes
            if ($this->shouldSkipCheck($request)) {
                return $next($request);
            }

            $tenant = tenant();

            // If no tenant context, skip check
            if (! $tenant) {
                return $next($request);
            }

            // Check if tenant has completed onboarding
            if ($this->hasCompletedOnboarding($tenant->id)) {
                return $next($request);
            }

            // Check if there's an active onboarding in progress
            $activeOnboarding = TenantOnboarding::forTenant($tenant->id)
                ->inProgress()
                ->first();

            if ($activeOnboarding) {
                // Check if onboarding has expired
                if ($activeOnboarding->hasExpired()) {
                    $activeOnboarding->markAsAbandoned();

                    Log::warning('Onboarding expired for tenant', [
                        'tenant_id' => $tenant->id,
                        'onboarding_id' => $activeOnboarding->id,
                    ]);
                } else {
                    // Redirect to onboarding wizard
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'onboarding_required',
                            'message' => 'Please complete the onboarding process.',
                            'redirect' => route('tenant.onboarding.wizard'),
                        ], 403);
                    }

                    return redirect()->route('tenant.onboarding.wizard')
                        ->with('info', 'Please complete the onboarding process to continue.');
                }
            }

            // No active onboarding, check if setup is complete
            if ($this->isTenantSetupComplete($tenant)) {
                return $next($request);
            }

            // Redirect to start onboarding
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'onboarding_required',
                    'message' => 'Please complete the onboarding process.',
                    'redirect' => route('tenant.onboarding.start', ['tenant' => $tenant]),
                ], 403);
            }

            return redirect()->route('tenant.onboarding.start', ['tenant' => $tenant])
                ->with('info', 'Welcome! Please complete the onboarding process to set up your institution.');

        } catch (\Exception $e) {
            Log::error('Error in EnsureTenantOnboarded middleware', [
                'tenant_id' => tenant('id') ?? null,
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
            ]);

            // In case of error, allow request to proceed to avoid blocking users
            return $next($request);
        }
    }

    /**
     * Check if the request should skip the onboarding check.
     */
    protected function shouldSkipCheck(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return false;
        }

        foreach ($this->excludedRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Skip for certain URL patterns
        $excludedPaths = [
            'onboarding',
            'api/',
            'sanctum/',
            'livewire/',
        ];

        $path = $request->path();
        foreach ($excludedPaths as $excludedPath) {
            if (str_starts_with($path, $excludedPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if tenant has completed onboarding.
     */
    protected function hasCompletedOnboarding(string $tenantId): bool
    {
        return TenantOnboarding::forTenant($tenantId)
            ->completed()
            ->exists();
    }

    /**
     * Check if tenant has basic setup complete (fallback check).
     */
    protected function isTenantSetupComplete($tenant): bool
    {
        // Check if tenant has basic required configuration
        $hasName = ! empty($tenant->name);
        $hasSettings = ! empty($tenant->settings);
        $hasUsers = $tenant->users()->exists();

        return $hasName && $hasSettings && $hasUsers;
    }
}
