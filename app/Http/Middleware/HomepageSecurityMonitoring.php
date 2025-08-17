<?php

namespace App\Http\Middleware;

use App\Services\Homepage\MonitoringService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HomepageSecurityMonitoring
{
    public function __construct(
        private MonitoringService $monitoring
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only monitor homepage-related routes
        if (! $this->isHomepageRoute($request)) {
            return $next($request);
        }

        // Check for suspicious activity
        $this->checkSuspiciousActivity($request);

        // Check rate limiting
        $this->checkRateLimit($request);

        // Check for malicious patterns
        $this->checkMaliciousPatterns($request);

        $response = $next($request);

        // Log the request for analysis
        $this->logRequest($request, $response);

        return $response;
    }

    /**
     * Check if this is a homepage-related route.
     */
    private function isHomepageRoute(Request $request): bool
    {
        $homepageRoutes = [
            '/',
            '/homepage',
            '/api/homepage',
            '/health-check/homepage',
        ];

        $path = $request->path();

        foreach ($homepageRoutes as $route) {
            if ($path === trim($route, '/') || str_starts_with($path, trim($route, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for suspicious activity patterns.
     */
    private function checkSuspiciousActivity(Request $request): void
    {
        $ip = $request->ip();
        $cacheKey = "suspicious_activity_{$ip}";

        // Track request count per IP
        $requestCount = Cache::increment($cacheKey, 1);

        if ($requestCount === 1) {
            Cache::put($cacheKey, 1, 300); // 5 minutes window
        }

        // Alert if too many requests from single IP
        if ($requestCount > 50) { // 50 requests in 5 minutes
            $this->monitoring->recordError(
                'suspicious_activity',
                "High request volume from IP: {$ip}",
                [
                    'ip_address' => $ip,
                    'request_count' => $requestCount,
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'time_window' => '5 minutes',
                ],
                'warning'
            );
        }

        // Critical alert for extremely high volume
        if ($requestCount > 100) {
            $this->monitoring->recordError(
                'potential_ddos',
                "Potential DDoS attack from IP: {$ip}",
                [
                    'ip_address' => $ip,
                    'request_count' => $requestCount,
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                ],
                'critical'
            );
        }
    }

    /**
     * Check rate limiting violations.
     */
    private function checkRateLimit(Request $request): void
    {
        $ip = $request->ip();
        $endpoint = $request->path();
        $cacheKey = "rate_limit_{$ip}_{$endpoint}";

        $requestCount = Cache::increment($cacheKey, 1);

        if ($requestCount === 1) {
            Cache::put($cacheKey, 1, 60); // 1 minute window
        }

        // Different limits for different endpoints
        $limits = [
            'api' => 30,     // 30 requests per minute for API endpoints
            'page' => 60,    // 60 requests per minute for page loads
            'default' => 45, // 45 requests per minute default
        ];

        $limit = $limits['default'];
        if (str_contains($endpoint, 'api/')) {
            $limit = $limits['api'];
        } elseif (! str_contains($endpoint, 'api/')) {
            $limit = $limits['page'];
        }

        if ($requestCount > $limit) {
            $this->monitoring->recordError(
                'rate_limit_violation',
                "Rate limit exceeded for IP: {$ip}",
                [
                    'ip_address' => $ip,
                    'endpoint' => $endpoint,
                    'request_count' => $requestCount,
                    'limit' => $limit,
                    'user_agent' => $request->userAgent(),
                ],
                'warning'
            );
        }
    }

    /**
     * Check for malicious patterns in request.
     */
    private function checkMaliciousPatterns(Request $request): void
    {
        $url = $request->fullUrl();
        $userAgent = $request->userAgent() ?? '';
        $input = json_encode($request->all());

        $patterns = [
            'sql_injection' => [
                'patterns' => ['union select', 'drop table', '1=1', 'or 1=1', 'select * from', 'insert into'],
                'severity' => 'critical',
            ],
            'xss_attempt' => [
                'patterns' => ['<script', 'javascript:', 'onerror=', 'onload=', 'alert(', 'document.cookie'],
                'severity' => 'critical',
            ],
            'path_traversal' => [
                'patterns' => ['../', '..\\', '/etc/passwd', '/windows/system32', '../../'],
                'severity' => 'critical',
            ],
            'command_injection' => [
                'patterns' => ['|', '&&', ';', '`', '$(', '${'],
                'severity' => 'critical',
            ],
            'suspicious_bot' => [
                'patterns' => ['sqlmap', 'nikto', 'nmap', 'masscan', 'zap'],
                'severity' => 'warning',
            ],
        ];

        foreach ($patterns as $type => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (
                    str_contains(strtolower($url), strtolower($pattern)) ||
                    str_contains(strtolower($userAgent), strtolower($pattern)) ||
                    str_contains(strtolower($input), strtolower($pattern))
                ) {
                    $this->monitoring->recordError(
                        $type,
                        "Malicious pattern detected: {$pattern}",
                        [
                            'pattern' => $pattern,
                            'type' => $type,
                            'ip_address' => $request->ip(),
                            'url' => $url,
                            'user_agent' => $userAgent,
                            'input_data' => $request->all(),
                        ],
                        $config['severity']
                    );

                    // Log to security log
                    Log::channel('homepage-alerts')->critical("Security threat detected: {$type}", [
                        'pattern' => $pattern,
                        'ip' => $request->ip(),
                        'url' => $url,
                        'user_agent' => $userAgent,
                    ]);

                    break; // Only alert once per request per type
                }
            }
        }
    }

    /**
     * Log request for analysis.
     */
    private function logRequest(Request $request, Response $response): void
    {
        // Only log if analytics table exists
        if (! \Schema::hasTable('homepage_analytics_events')) {
            return;
        }

        try {
            \DB::table('homepage_analytics_events')->insert([
                'event_type' => 'page_view',
                'session_id' => session()->getId(),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'referer' => $request->header('referer'),
                'event_data' => json_encode([
                    'method' => $request->method(),
                    'status_code' => $response->getStatusCode(),
                    'response_time' => microtime(true) - LARAVEL_START,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the request
            Log::error('Failed to log homepage request', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
