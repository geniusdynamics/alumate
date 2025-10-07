<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Analytics\ConsentService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Middleware for enforcing consent requirements on analytics endpoints
 *
 * Validates user consent for analytics data collection and blocks requests
 * from users who have not given consent.
 */
class ConsentMiddleware
{
    private ConsentService $consentService;

    public function __construct(ConsentService $consentService)
    {
        $this->consentService = $consentService;
    }

    /**
     * Handle an incoming request and enforce consent requirements
     *
     * @param Request $request
     * @param Closure $next
     * @param string $type Consent type to check (default: 'analytics')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $type = 'analytics')
    {
        try {
            // Skip consent check for anonymous aggregate queries
            if ($this->isAnonymousAggregateQuery($request)) {
                return $next($request);
            }

            // Throttle data export requests
            if ($this->isDataExportRequest($request)) {
                $userId = Auth::id() ?? 'guest';
                $key = "export:{$userId}";

                if (RateLimiter::tooManyAttempts($key, 1)) { // 1 request per day
                    Log::warning('Data export rate limit exceeded', [
                        'user_id' => $userId,
                        'ip' => $request->ip(),
                    ]);
                    return $this->denyAccess('Data export requests are limited to once per day');
                }

                RateLimiter::hit($key, 86400); // 24 hours
            }

            // Check if user has given consent for analytics tracking
            if (!$this->consentService->hasConsent(type: $type)) {
                Log::info('Analytics access denied - no consent', [
                    'user_id' => Auth::id(),
                    'type' => $type,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                ]);

                return $this->denyAccess("Consent required for {$type} tracking");
            }

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Consent middleware error', [
                'error' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_id' => Auth::id(),
            ]);

            return $this->denyAccess('Consent validation failed');
        }
    }

    /**
     * Check if the request is for anonymous aggregate data
     */
    private function isAnonymousAggregateQuery(Request $request): bool
    {
        $path = $request->path();

        // Allow anonymous aggregate queries like dashboard summaries, public stats
        return str_contains($path, 'analytics/aggregate') ||
               str_contains($path, 'analytics/summary') ||
               str_contains($path, 'analytics/public');
    }

    /**
     * Check if the request is for data export
     */
    private function isDataExportRequest(Request $request): bool
    {
        $path = $request->path();

        return str_contains($path, 'privacy/export') ||
               str_contains($path, 'analytics/export');
    }

    /**
     * Return access denied response
     */
    private function denyAccess(string $reason): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'Access denied',
            'message' => $reason,
            'code' => 'CONSENT_REQUIRED',
        ], 403);
    }
}