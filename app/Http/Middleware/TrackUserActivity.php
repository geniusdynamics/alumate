<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track activity for authenticated users
        if (Auth::check()) {
            $user = Auth::user();

            // Update last activity timestamp
            $user->updateLastActivity();

            // Log specific activities
            $this->logActivity($request, $user);
        }

        return $response;
    }

    /**
     * Log specific user activities.
     */
    private function logActivity(Request $request, $user): void
    {
        $route = $request->route();
        if (! $route) {
            return;
        }

        $routeName = $route->getName();
        $method = $request->method();
        $path = $request->path();

        // Define activities to log
        $activitiesToLog = [
            'login' => 'user_login',
            'logout' => 'user_logout',
            'dashboard' => 'dashboard_view',
            'profile.edit' => 'profile_view',
            'profile.update' => 'profile_update',
            'graduates.index' => 'graduates_list_view',
            'graduates.show' => 'graduate_profile_view',
            'jobs.index' => 'jobs_list_view',
            'jobs.show' => 'job_view',
            'job-applications.store' => 'job_application_submit',
        ];

        // Log activity if it's in our list
        if (isset($activitiesToLog[$routeName])) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'route' => $routeName,
                    'method' => $method,
                    'path' => $path,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ])
                ->log($activitiesToLog[$routeName]);
        }

        // Log POST, PUT, DELETE requests for audit purposes
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            activity()
                ->causedBy($user)
                ->withProperties([
                    'route' => $routeName,
                    'method' => $method,
                    'path' => $path,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_data' => $this->sanitizeRequestData($request->all()),
                ])
                ->log('user_action');
        }
    }

    /**
     * Sanitize request data for logging (remove sensitive information).
     */
    private function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_token',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
