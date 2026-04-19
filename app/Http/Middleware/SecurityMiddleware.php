<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $securityService = app(\App\Services\SecurityService::class);

        // Check if IP is blocked
        if ($securityService->isIpBlocked($request->ip())) {
            Log::warning('Blocked request from IP: '.$request->ip(), [
                'path' => $request->path(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Access denied',
                'message' => 'Your IP address has been blocked due to security policies.',
            ], 403);
        }

        // Check for suspicious patterns
        if ($securityService->checkSuspiciousPatterns($request)) {
            Log::warning('Suspicious request blocked', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'error' => 'Suspicious activity detected',
                'message' => 'Your request contains suspicious patterns and has been blocked.',
            ], 403);
        }

        // Check rate limiting
        $identifier = $request->ip().':'.$request->path();
        if ($securityService->detectRateLimitViolation($identifier, 10, 1)) { // 10 requests per minute
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'identifier' => $identifier,
            ]);

            return response()->json([
                'error' => 'Too many requests',
                'message' => 'You have exceeded the rate limit. Please try again later.',
            ], 429);
        }

        return $next($request);
    }
}
