<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantIsolationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check if user has access to requested tenant
        $requestedTenantId = $request->header('X-Tenant-ID') 
            ?? $request->input('tenant_id')
            ?? $request->route('tenant_id');

        if ($requestedTenantId) {
            $hasAccess = $user->tenants()
                ->where('tenants.id', $requestedTenantId)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'message' => 'You do not have access to this tenant',
                ], 403);
            }
        }

        // Ensure all queries are scoped to current tenant
        if ($user->currentTenant) {
            app()->bind('current_tenant', fn () => $user->currentTenant);
        }

        return $next($request);
    }
}
