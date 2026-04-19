<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $type = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $type);
        $limits = $this->getRateLimits($type);

        $maxAttempts = $limits['attempts'] ?? 60;
        $decayMinutes = $limits['decay'] ?? 1;

        if (RateLimiterFacade::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiterFacade::availableIn($key);

            return response()->json([
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Retry-After' => $retryAfter,
            ]);
        }

        RateLimiterFacade::hit($key, $decayMinutes * 60);

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set(
            'X-RateLimit-Remaining',
            max(0, $maxAttempts - RateLimiterFacade::attempts($key))
        );

        return $response;
    }

    /**
     * Resolve request signature for rate limiting.
     */
    private function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();
        $userId = $user ? $user->id : $request->ip();
        $tenantId = $user?->currentTenant?->id ?? 'global';

        return sprintf('rate_limit:%s:%s:%s:%s', $type, $tenantId, $userId, $request->path());
    }

    /**
     * Get rate limits by type.
     */
    private function getRateLimits(string $type): array
    {
        return match ($type) {
            'api' => ['attempts' => 100, 'decay' => 1], // 100 requests per minute
            'auth' => ['attempts' => 5, 'decay' => 1], // 5 login attempts per minute
            'upload' => ['attempts' => 10, 'decay' => 1], // 10 uploads per minute
            'search' => ['attempts' => 30, 'decay' => 1], // 30 searches per minute
            'webhook' => ['attempts' => 100, 'decay' => 1], // 100 webhooks per minute
            'social' => ['attempts' => 50, 'decay' => 1], // 50 social actions per minute
            'messaging' => ['attempts' => 60, 'decay' => 1], // 60 messages per minute
            'payment' => ['attempts' => 20, 'decay' => 1], // 20 payment actions per minute
            default => ['attempts' => 60, 'decay' => 1], // Default 60 per minute
        };
    }
}
