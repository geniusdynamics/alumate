<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class SocialRateLimiting
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $action = 'default'): ResponseAlias
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $user, $action);
        $maxAttempts = $this->getMaxAttempts($action);
        $decayMinutes = $this->getDecayMinutes($action);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            RateLimiter::retriesLeft($key, $maxAttempts)
        );
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, $user, string $action): string
    {
        return match ($action) {
            'post_creation' => "social:post_creation:{$user->id}",
            'post_interaction' => "social:post_interaction:{$user->id}",
            'connection_request' => "social:connection_request:{$user->id}",
            'message_sending' => "social:message_sending:{$user->id}",
            'profile_view' => "social:profile_view:{$user->id}",
            'search_query' => "social:search_query:{$user->id}",
            'group_join' => "social:group_join:{$user->id}",
            'comment_creation' => "social:comment_creation:{$user->id}",
            'file_upload' => "social:file_upload:{$user->id}",
            'api_access' => "social:api_access:{$user->id}",
            default => "social:general:{$user->id}",
        };
    }

    /**
     * Get the maximum number of attempts for the given action.
     */
    protected function getMaxAttempts(string $action): int
    {
        return match ($action) {
            'post_creation' => 10, // 10 posts per window
            'post_interaction' => 100, // 100 likes/reactions per window
            'connection_request' => 20, // 20 connection requests per window
            'message_sending' => 50, // 50 messages per window
            'profile_view' => 200, // 200 profile views per window
            'search_query' => 100, // 100 searches per window
            'group_join' => 5, // 5 group joins per window
            'comment_creation' => 30, // 30 comments per window
            'file_upload' => 20, // 20 file uploads per window
            'api_access' => 1000, // 1000 API calls per window
            default => 60, // Default rate limit
        };
    }

    /**
     * Get the decay time in minutes for the given action.
     */
    protected function getDecayMinutes(string $action): int
    {
        return match ($action) {
            'post_creation' => 60, // 1 hour window
            'post_interaction' => 15, // 15 minute window
            'connection_request' => 1440, // 24 hour window
            'message_sending' => 60, // 1 hour window
            'profile_view' => 60, // 1 hour window
            'search_query' => 60, // 1 hour window
            'group_join' => 1440, // 24 hour window
            'comment_creation' => 60, // 1 hour window
            'file_upload' => 60, // 1 hour window
            'api_access' => 60, // 1 hour window
            default => 60, // Default 1 hour window
        };
    }

    /**
     * Create a 'too many attempts' response.
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        $response = response()->json([
            'message' => 'Too many requests. Please slow down.',
            'retry_after' => $retryAfter,
            'max_attempts' => $maxAttempts,
        ], 429);

        return $this->addHeaders($response, $maxAttempts, 0, $retryAfter);
    }

    /**
     * Add rate limiting headers to the response.
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts, ?int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        if ($retryAfter !== null) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }

        return $response;
    }

    /**
     * Get adaptive rate limits based on user behavior.
     */
    protected function getAdaptiveRateLimit(Request $request, $user, string $action): array
    {
        $userTrustScore = $this->calculateUserTrustScore($user);
        $baseLimit = $this->getMaxAttempts($action);

        // Adjust limits based on trust score
        $adjustedLimit = match (true) {
            $userTrustScore >= 0.8 => (int) ($baseLimit * 1.5), // Trusted users get 50% more
            $userTrustScore >= 0.6 => $baseLimit, // Normal users get base limit
            $userTrustScore >= 0.4 => (int) ($baseLimit * 0.7), // Suspicious users get 30% less
            default => (int) ($baseLimit * 0.5), // Untrusted users get 50% less
        };

        return [
            'max_attempts' => $adjustedLimit,
            'decay_minutes' => $this->getDecayMinutes($action),
            'trust_score' => $userTrustScore,
        ];
    }

    /**
     * Calculate user trust score based on behavior patterns.
     */
    protected function calculateUserTrustScore($user): float
    {
        $score = 0.5; // Base score

        // Account age factor
        $accountAgeMonths = $user->created_at->diffInMonths(now());
        $score += min(0.2, $accountAgeMonths * 0.02);

        // Email verification
        if ($user->email_verified_at) {
            $score += 0.1;
        }

        // Profile completeness
        $profileFields = ['bio', 'location', 'website', 'avatar_url'];
        $completedFields = 0;
        foreach ($profileFields as $field) {
            if (! empty($user->$field)) {
                $completedFields++;
            }
        }
        $score += ($completedFields / count($profileFields)) * 0.1;

        // Connection count (more connections = more trusted)
        $connectionCount = method_exists($user, 'connections') ? $user->connections()->count() : 0;
        $score += min(0.1, $connectionCount * 0.005);

        // Recent violations (reduce score)
        $recentViolations = $this->getRecentSecurityViolations($user);
        $score -= $recentViolations * 0.1;

        return max(0.0, min(1.0, $score));
    }

    /**
     * Get recent security violations for the user.
     */
    protected function getRecentSecurityViolations($user): int
    {
        // This would check audit logs for recent violations
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Check if request should be blocked based on suspicious patterns.
     */
    protected function detectSuspiciousActivity(Request $request, $user): bool
    {
        $suspiciousPatterns = [
            $this->detectRapidFireRequests($user),
            $this->detectUnusualAccessPatterns($request, $user),
            $this->detectAutomatedBehavior($request, $user),
        ];

        return in_array(true, $suspiciousPatterns, true);
    }

    /**
     * Detect rapid-fire requests that might indicate automation.
     */
    protected function detectRapidFireRequests($user): bool
    {
        $key = "rapid_fire_detection:{$user->id}";
        $requestCount = RateLimiter::attempts($key);

        // If more than 30 requests in the last minute, flag as suspicious
        return $requestCount > 30;
    }

    /**
     * Detect unusual access patterns.
     */
    protected function detectUnusualAccessPatterns(Request $request, $user): bool
    {
        // Check for unusual IP addresses, user agents, etc.
        $currentIp = $request->ip();
        $userAgent = $request->userAgent();

        // This would implement more sophisticated pattern detection
        // For now, return false as placeholder
        return false;
    }

    /**
     * Detect automated behavior patterns.
     */
    protected function detectAutomatedBehavior(Request $request, $user): bool
    {
        // Check for bot-like behavior patterns
        $userAgent = $request->userAgent();

        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget', 'python-requests',
        ];

        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
