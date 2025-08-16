<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request, $limiter);

        if (RateLimiter::tooManyAttempts($key, $this->getMaxAttempts($limiter))) {
            return $this->buildResponse($key, $this->getMaxAttempts($limiter));
        }

        RateLimiter::hit($key, $this->getDecayMinutes($limiter) * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $this->getMaxAttempts($limiter),
            $this->calculateRemainingAttempts($key, $this->getMaxAttempts($limiter))
        );
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $limiter): string
    {
        $user = $request->user();

        if ($user) {
            return "api_rate_limit:{$limiter}:user:{$user->id}";
        }

        return "api_rate_limit:{$limiter}:ip:".$request->ip();
    }

    /**
     * Get the maximum number of attempts allowed.
     */
    protected function getMaxAttempts(string $limiter): int
    {
        return match ($limiter) {
            'api' => 60,
            'search' => 30,
            'upload' => 10,
            'webhook' => 100,
            default => 60,
        };
    }

    /**
     * Get the decay time in minutes.
     */
    protected function getDecayMinutes(string $limiter): int
    {
        return match ($limiter) {
            'api' => 1,
            'search' => 1,
            'upload' => 1,
            'webhook' => 1,
            default => 1,
        };
    }

    /**
     * Calculate the number of remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return RateLimiter::retriesLeft($key, $maxAttempts);
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
            'X-RateLimit-Reset' => now()->addMinute()->timestamp,
        ]);

        return $response;
    }

    /**
     * Build the rate limit exceeded response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.',
                'details' => [
                    'limit' => $maxAttempts,
                    'retry_after' => $retryAfter,
                ],
            ],
        ], 429, [
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }
}
