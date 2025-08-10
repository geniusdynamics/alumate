<?php

namespace App\Services\Homepage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitoringService
{
    private array $config;
    private AlertingService $alerting;
    
    public function __construct(AlertingService $alerting)
    {
        $this->config = config('deployment.homepage.monitoring', []);
        $this->alerting = $alerting;
    }
    
    /**
     * Record performance metric.
     */
    public function recordPerformanceMetric(
        string $metricType,
        string $metricName,
        float $value,
        string $unit,
        array $additionalData = []
    ): void {
        if (!$this->config['performance_monitoring']) {
            return;
        }
        
        try {
            DB::table('homepage_performance_metrics')->insert([
                'metric_type' => $metricType,
                'metric_name' => $metricName,
                'value' => $value,
                'unit' => $unit,
                'environment' => app()->environment(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'additional_data' => json_encode($additionalData),
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Check for performance alerts
            $this->checkPerformanceAlerts($metricType, $metricName, $value);
            
        } catch (\Exception $e) {
            Log::error('Failed to record performance metric', [
                'metric_type' => $metricType,
                'metric_name' => $metricName,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Record error event.
     */
    public function recordError(
        string $errorType,
        string $message,
        array $context = [],
        string $severity = 'error'
    ): void {
        if (!$this->config['error_tracking']) {
            return;
        }
        
        $errorData = [
            'type' => $errorType,
            'message' => $message,
            'severity' => $severity,
            'context' => $context,
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ];
        
        // Log the error
        Log::channel('homepage-errors')->error($message, $errorData);
        
        // Send to external error tracking service
        $this->sendToErrorTrackingService($errorData);
        
        // Check if alert should be sent
        $this->checkErrorAlerts($errorType, $severity);
    }
    
    /**
     * Monitor homepage uptime.
     */
    public function checkUptime(): array
    {
        $results = [];
        $endpoints = [
            'homepage' => '/',
            'health_check' => '/health-check/homepage',
            'api_statistics' => '/api/homepage/statistics',
            'api_testimonials' => '/api/homepage/testimonials',
        ];
        
        foreach ($endpoints as $name => $endpoint) {
            $startTime = microtime(true);
            
            try {
                $response = Http::timeout(10)->get(config('app.url') . $endpoint);
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000, 2);
                
                $isUp = $response->successful();
                
                $results[$name] = [
                    'status' => $isUp ? 'up' : 'down',
                    'response_time' => $responseTime,
                    'status_code' => $response->status(),
                    'checked_at' => now()->toISOString(),
                ];
                
                // Record performance metric
                $this->recordPerformanceMetric(
                    'uptime_check',
                    $name . '_response_time',
                    $responseTime,
                    'ms',
                    ['status_code' => $response->status()]
                );
                
                // Check for uptime alerts
                if (!$isUp) {
                    $this->alerting->sendUptimeAlert($name, $endpoint, $response->status());
                }
                
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'down',
                    'response_time' => null,
                    'error' => $e->getMessage(),
                    'checked_at' => now()->toISOString(),
                ];
                
                $this->alerting->sendUptimeAlert($name, $endpoint, 0, $e->getMessage());
            }
        }
        
        // Store uptime results in cache
        Cache::put('homepage_uptime_results', $results, 300); // 5 minutes
        
        return $results;
    }
    
    /**
     * Get conversion metrics dashboard data.
     */
    public function getConversionMetrics(): array
    {
        try {
            $timeframe = now()->subDays(7);
            
            // Get CTA click metrics
            $ctaClicks = DB::table('homepage_analytics_events')
                ->where('event_type', 'cta_click')
                ->where('created_at', '>=', $timeframe)
                ->selectRaw('
                    event_data->>"$.cta" as cta_name,
                    event_data->>"$.section" as section,
                    COUNT(*) as clicks,
                    COUNT(DISTINCT session_id) as unique_sessions
                ')
                ->groupBy('cta_name', 'section')
                ->get();
            
            // Get conversion funnel data
            $funnelData = DB::table('homepage_analytics_events')
                ->where('created_at', '>=', $timeframe)
                ->selectRaw('
                    event_type,
                    COUNT(*) as events,
                    COUNT(DISTINCT session_id) as unique_sessions
                ')
                ->groupBy('event_type')
                ->get();
            
            // Get page performance metrics
            $performanceMetrics = DB::table('homepage_performance_metrics')
                ->where('recorded_at', '>=', $timeframe)
                ->where('metric_type', 'page_load')
                ->selectRaw('
                    metric_name,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as sample_count
                ')
                ->groupBy('metric_name')
                ->get();
            
            // Calculate conversion rates
            $totalPageViews = $funnelData->where('event_type', 'page_view')->first()->events ?? 0;
            $totalConversions = $funnelData->where('event_type', 'conversion')->first()->events ?? 0;
            $conversionRate = $totalPageViews > 0 ? ($totalConversions / $totalPageViews) * 100 : 0;
            
            return [
                'cta_performance' => $ctaClicks,
                'funnel_data' => $funnelData,
                'performance_metrics' => $performanceMetrics,
                'conversion_rate' => round($conversionRate, 2),
                'total_page_views' => $totalPageViews,
                'total_conversions' => $totalConversions,
                'timeframe' => $timeframe->toDateString() . ' to ' . now()->toDateString(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get conversion metrics', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'error' => 'Failed to retrieve conversion metrics',
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Generate monitoring dashboard data.
     */
    public function getDashboardData(): array
    {
        return [
            'uptime' => Cache::get('homepage_uptime_results', []),
            'conversion_metrics' => $this->getConversionMetrics(),
            'recent_errors' => $this->getRecentErrors(),
            'performance_summary' => $this->getPerformanceSummary(),
            'alerts' => $this->getActiveAlerts(),
            'system_health' => $this->getSystemHealth(),
        ];
    }
    
    /**
     * Check performance alerts.
     */
    private function checkPerformanceAlerts(string $metricType, string $metricName, float $value): void
    {
        $thresholds = [
            'page_load' => ['warning' => 2000, 'critical' => 5000], // milliseconds
            'api_response' => ['warning' => 1000, 'critical' => 3000],
            'database_query' => ['warning' => 500, 'critical' => 2000],
        ];
        
        if (!isset($thresholds[$metricType])) {
            return;
        }
        
        $threshold = $thresholds[$metricType];
        
        if ($value >= $threshold['critical']) {
            $this->alerting->sendPerformanceAlert($metricType, $metricName, $value, 'critical');
        } elseif ($value >= $threshold['warning']) {
            $this->alerting->sendPerformanceAlert($metricType, $metricName, $value, 'warning');
        }
    }
    
    /**
     * Check error alerts.
     */
    private function checkErrorAlerts(string $errorType, string $severity): void
    {
        $cacheKey = "error_alert_count_{$errorType}";
        $count = Cache::increment($cacheKey, 1);
        
        if ($count === 1) {
            Cache::put($cacheKey, 1, 300); // 5 minutes window
        }
        
        // Send alert if error count exceeds threshold
        $thresholds = [
            'critical' => 1,  // Immediate alert for critical errors
            'error' => 5,     // Alert after 5 errors in 5 minutes
            'warning' => 10,  // Alert after 10 warnings in 5 minutes
        ];
        
        if ($count >= ($thresholds[$severity] ?? 10)) {
            $this->alerting->sendErrorAlert($errorType, $severity, $count);
            Cache::forget($cacheKey); // Reset counter after alert
        }
    }
    

    
    /**
     * Send to external error tracking service.
     */
    private function sendToErrorTrackingService(array $errorData): void
    {
        // Send to Sentry if configured
        if (app()->bound('sentry')) {
            app('sentry')->captureException(new \Exception($errorData['message']), $errorData);
        }
        
        // Send to AlertingService for comprehensive alert handling
        $this->alerting->sendErrorAlert(
            $errorData['type'],
            $errorData['severity'],
            1,
            $errorData['message']
        );
    }
    
    /**
     * Monitor conversion metrics and send alerts if thresholds are breached.
     */
    public function monitorConversionMetrics(): void
    {
        try {
            $metrics = $this->getConversionMetrics();
            
            if (isset($metrics['error'])) {
                return; // Skip if there was an error getting metrics
            }
            
            // Define conversion rate thresholds
            $thresholds = [
                'conversion_rate' => ['min' => 2.0, 'max' => 15.0], // 2-15% expected range
                'cta_click_rate' => ['min' => 5.0, 'max' => 25.0], // 5-25% expected range
            ];
            
            // Check conversion rate
            $conversionRate = $metrics['conversion_rate'] ?? 0;
            if ($conversionRate < $thresholds['conversion_rate']['min']) {
                $this->alerting->sendConversionAlert(
                    'conversion_rate',
                    $conversionRate,
                    $thresholds['conversion_rate']['min'],
                    'below'
                );
            }
            
            // Check CTA performance
            foreach ($metrics['cta_performance'] as $cta) {
                $clickRate = $cta->unique_sessions > 0 
                    ? ($cta->clicks / $cta->unique_sessions) * 100 
                    : 0;
                    
                if ($clickRate < $thresholds['cta_click_rate']['min']) {
                    $this->alerting->sendConversionAlert(
                        "cta_click_rate_{$cta->cta_name}",
                        $clickRate,
                        $thresholds['cta_click_rate']['min'],
                        'below'
                    );
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to monitor conversion metrics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Monitor security threats and send alerts.
     */
    public function monitorSecurityThreats(): void
    {
        try {
            // Check for suspicious activity patterns
            $suspiciousIPs = $this->detectSuspiciousIPs();
            $rateLimitViolations = $this->detectRateLimitViolations();
            $maliciousRequests = $this->detectMaliciousRequests();
            
            foreach ($suspiciousIPs as $ip => $details) {
                $this->alerting->sendSecurityAlert('suspicious_ip', [
                    'ip_address' => $ip,
                    'request_count' => $details['count'],
                    'time_window' => $details['window'],
                    'user_agents' => $details['user_agents'],
                ]);
            }
            
            foreach ($rateLimitViolations as $violation) {
                $this->alerting->sendSecurityAlert('rate_limit_violation', $violation);
            }
            
            foreach ($maliciousRequests as $request) {
                $this->alerting->sendSecurityAlert('malicious_request', $request);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to monitor security threats', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Detect suspicious IP addresses.
     */
    private function detectSuspiciousIPs(): array
    {
        $timeframe = now()->subHours(1);
        $threshold = 100; // requests per hour
        
        $suspiciousIPs = DB::table('homepage_analytics_events')
            ->where('created_at', '>=', $timeframe)
            ->selectRaw('
                ip_address,
                COUNT(*) as request_count,
                COUNT(DISTINCT user_agent) as unique_user_agents,
                GROUP_CONCAT(DISTINCT user_agent) as user_agents
            ')
            ->groupBy('ip_address')
            ->having('request_count', '>', $threshold)
            ->get();
        
        $results = [];
        foreach ($suspiciousIPs as $ip) {
            $results[$ip->ip_address] = [
                'count' => $ip->request_count,
                'window' => '1 hour',
                'user_agents' => explode(',', $ip->user_agents),
            ];
        }
        
        return $results;
    }
    
    /**
     * Detect rate limit violations.
     */
    private function detectRateLimitViolations(): array
    {
        // This would integrate with your rate limiting system
        // For now, return empty array as placeholder
        return [];
    }
    
    /**
     * Detect malicious requests.
     */
    private function detectMaliciousRequests(): array
    {
        $timeframe = now()->subHours(1);
        
        // Look for common attack patterns in URLs and user agents
        $maliciousPatterns = [
            'sql_injection' => ['union select', 'drop table', '1=1', 'or 1=1'],
            'xss_attempt' => ['<script', 'javascript:', 'onerror=', 'onload='],
            'path_traversal' => ['../', '..\\', '/etc/passwd', '/windows/system32'],
            'bot_attack' => ['bot', 'crawler', 'spider', 'scraper'],
        ];
        
        $maliciousRequests = [];
        
        foreach ($maliciousPatterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                $requests = DB::table('homepage_analytics_events')
                    ->where('created_at', '>=', $timeframe)
                    ->where(function ($query) use ($pattern) {
                        $query->where('url', 'like', "%{$pattern}%")
                              ->orWhere('user_agent', 'like', "%{$pattern}%");
                    })
                    ->select('ip_address', 'url', 'user_agent', 'created_at')
                    ->get();
                
                foreach ($requests as $request) {
                    $maliciousRequests[] = [
                        'threat_type' => $type,
                        'pattern' => $pattern,
                        'ip_address' => $request->ip_address,
                        'url' => $request->url,
                        'user_agent' => $request->user_agent,
                        'timestamp' => $request->created_at,
                    ];
                }
            }
        }
        
        return $maliciousRequests;
    }
    

    
    /**
     * Get recent errors.
     */
    private function getRecentErrors(): array
    {
        try {
            $logPath = storage_path('logs/homepage-errors.log');
            
            if (!file_exists($logPath)) {
                return [];
            }
            
            $lines = array_slice(file($logPath), -50); // Last 50 lines
            $errors = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\].*?ERROR.*?:(.*?)$/', $line, $matches)) {
                    $errors[] = [
                        'timestamp' => $matches[1],
                        'message' => trim($matches[2]),
                    ];
                }
            }
            
            return array_reverse($errors); // Most recent first
            
        } catch (\Exception $e) {
            return [['error' => 'Failed to read error logs: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get performance summary.
     */
    private function getPerformanceSummary(): array
    {
        try {
            $timeframe = now()->subHours(24);
            
            return DB::table('homepage_performance_metrics')
                ->where('recorded_at', '>=', $timeframe)
                ->selectRaw('
                    metric_type,
                    AVG(value) as avg_value,
                    MIN(value) as min_value,
                    MAX(value) as max_value,
                    COUNT(*) as sample_count
                ')
                ->groupBy('metric_type')
                ->get()
                ->toArray();
                
        } catch (\Exception $e) {
            return [['error' => 'Failed to get performance summary: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Get active alerts.
     */
    private function getActiveAlerts(): array
    {
        // This would typically come from a dedicated alerts table
        // For now, return cached alert data
        return Cache::get('homepage_active_alerts', []);
    }
    
    /**
     * Get system health.
     */
    private function getSystemHealth(): array
    {
        try {
            return [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'memory' => $this->getMemoryUsage(),
                'disk' => $this->getDiskUsage(),
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to get system health: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check database health.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check cache health.
     */
    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            
            return [
                'status' => $value === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Check storage health.
     */
    private function checkStorageHealth(): array
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            \Storage::put($testFile, 'test');
            $content = \Storage::get($testFile);
            \Storage::delete($testFile);
            
            return [
                'status' => $content === 'test' ? 'healthy' : 'unhealthy',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get memory usage.
     */
    private function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }
    
    /**
     * Get disk usage.
     */
    private function getDiskUsage(): array
    {
        $path = base_path();
        
        return [
            'free' => disk_free_space($path),
            'total' => disk_total_space($path),
            'used_percentage' => round((1 - disk_free_space($path) / disk_total_space($path)) * 100, 2),
        ];
    }
}