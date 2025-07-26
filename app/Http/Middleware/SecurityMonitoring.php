<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityMonitoring
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Check for malicious requests
        if ($this->securityService->detectMaliciousRequest($request)) {
            return response()->json(['error' => 'Request blocked for security reasons'], 403);
        }

        // Check rate limiting for authenticated users
        if (Auth::check()) {
            $identifier = 'user:' . Auth::id() . ':' . $request->ip();
            if ($this->securityService->detectRateLimitViolation($identifier, 100, 1)) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
        } else {
            // Rate limit for unauthenticated requests
            $identifier = 'ip:' . $request->ip();
            if ($this->securityService->detectRateLimitViolation($identifier, 30, 1)) {
                return response()->json(['error' => 'Rate limit exceeded'], 429);
            }
        }

        $response = $next($request);

        // Log data access for sensitive operations
        if (Auth::check() && $this->isSensitiveRoute($request)) {
            $this->logDataAccess($request);
        }

        return $response;
    }

    private function isSensitiveRoute(Request $request)
    {
        $sensitiveRoutes = [
            'graduates.*',
            'jobs.*',
            'applications.*',
            'employers.*',
            'users.*',
            'institutions.*',
        ];

        $routeName = $request->route()->getName();
        
        foreach ($sensitiveRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private function logDataAccess(Request $request)
    {
        $route = $request->route();
        $routeName = $route->getName();
        $parameters = $route->parameters();

        // Determine resource type and ID from route
        $resourceType = $this->getResourceTypeFromRoute($routeName);
        $resourceId = $this->getResourceIdFromParameters($parameters);
        $accessType = $this->getAccessTypeFromMethod($request->method());

        if ($resourceType && $resourceId) {
            $this->securityService->logDataAccess(
                $resourceType,
                $resourceId,
                $accessType,
                true,
                'middleware_check'
            );
        }
    }

    private function getResourceTypeFromRoute($routeName)
    {
        if (strpos($routeName, 'graduates') !== false) return 'graduate';
        if (strpos($routeName, 'jobs') !== false) return 'job';
        if (strpos($routeName, 'applications') !== false) return 'application';
        if (strpos($routeName, 'employers') !== false) return 'employer';
        if (strpos($routeName, 'users') !== false) return 'user';
        if (strpos($routeName, 'institutions') !== false) return 'institution';
        
        return null;
    }

    private function getResourceIdFromParameters($parameters)
    {
        // Look for common parameter names that indicate resource ID
        $idParams = ['id', 'graduate', 'job', 'application', 'employer', 'user', 'institution'];
        
        foreach ($idParams as $param) {
            if (isset($parameters[$param])) {
                return is_object($parameters[$param]) ? $parameters[$param]->id : $parameters[$param];
            }
        }

        return null;
    }

    private function getAccessTypeFromMethod($method)
    {
        switch (strtoupper($method)) {
            case 'GET':
                return 'view';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'unknown';
        }
    }
}