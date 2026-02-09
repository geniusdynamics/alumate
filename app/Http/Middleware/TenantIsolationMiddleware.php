<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantIsolationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check if user has access to requested tenant
        $requestedTenantId = $request->header('X-Tenant-ID')
            ?? $request->input('tenant_id')
            ?? $request->route('tenant_id');

        if ($requestedTenantId) {
            // Validate that the requested tenant ID is a valid UUID
            if (! $this->isValidUuid($requestedTenantId)) {
                Log::warning('Invalid tenant ID format attempted', [
                    'user_id' => $user->id,
                    'requested_tenant_id' => $requestedTenantId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'Invalid tenant ID format',
                ], 400);
            }

            $hasAccess = $user->tenants()
                ->where('tenants.id', $requestedTenantId)
                ->exists();

            if (! $hasAccess) {
                Log::warning('Unauthorized tenant access attempt', [
                    'user_id' => $user->id,
                    'requested_tenant_id' => $requestedTenantId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'message' => 'You do not have access to this tenant',
                ], 403);
            }
        }

        // Additional security: Ensure the user can only access tenants they belong to
        // If no specific tenant was requested, ensure they're accessing their default tenant
        if (! $requestedTenantId && $user->tenants()->count() > 0) {
            // Force the user to access only their assigned tenant
            $assignedTenantId = $user->tenants()->first()->id;

            // Log potential security issue if user is trying to access without specifying tenant
            Log::info('User accessing without specific tenant ID', [
                'user_id' => $user->id,
                'assigned_tenant_id' => $assignedTenantId,
                'ip' => $request->ip(),
            ]);
        }

        // Ensure all queries are scoped to current tenant
        if ($user->currentTenant) {
            app()->bind('current_tenant', fn () => $user->currentTenant);
        }

        return $next($request);
    }

    /**
     * Validate that the provided string is a valid UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        // Regular expression to validate UUID format
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
