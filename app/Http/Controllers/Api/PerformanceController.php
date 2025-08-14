<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PerformanceController extends Controller
{
    /**
     * Store performance metrics from the frontend
     */
    public function storeMetrics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metrics' => 'required|array',
            'metrics.*.name' => 'required|string|max:100',
            'metrics.*.value' => 'required|numeric',
            'metrics.*.timestamp' => 'required|integer',
            'metrics.*.url' => 'required|string|max:500',
            'metrics.*.userAgent' => 'nullable|string|max:500',
            'metrics.*.connection' => 'nullable|array',
            'session' => 'required|array',
            'session.url' => 'required|string|max:500',
            'session.referrer' => 'nullable|string|max:500',
            'session.userAgent' => 'required|string|max:500',
            'session.viewport' => 'required|array',
            'session.screen' => 'required|array',
            'session.connection' => 'nullable|array',
            'timestamp' => 'required|integer'
        ]);

        try {
            // Store metrics in database for analysis
            $this->storeMetricsInDatabase($validated);
            
            // Cache recent metrics for real-time monitoring
            $this->cacheRecentMetrics($validated);
            
            // Check for performance issues and alert if necessary
            $this->checkPerformanceThresholds($validated['metrics']);
            
            return response()->json([
                'success' => true,
                'message' => 'Performance metrics stored successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to store performance metrics', [
                'error' => $e->getMessage(),
                'metrics_count' => count($validated['metrics'])
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to store performance metrics'
            ], 500);
        }
    }

    /**
     * Get performance analytics dashboard data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['1h', '24h', '7d', '30d'])],
            'metric' => 'nullable|string|max:100',
            'page' => 'nullable|string|max:500'
        ]);

        $period = $validated['period'] ?? '24h';
        $metric = $validated['metric'] ?? null;
        $page = $validated['page'] ?? null;

        try {
            $analytics = $this->getPerformanceAnalytics($period, $metric, $page);
            
            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get performance analytics', [
                'error' => $e->getMessage(),
                'period' => $period,
                'metric' => $metric
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance analytics'
            ], 500);
        }
    }

    /**
     * Get performance recommendations
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['1h', '24h', '7d', '30d'])],
        ]);

        $period = $validated['period'] ?? '24h';

        try {
            $recommendations = $this->generatePerformanceRecommendations($period);
            
            return response()->json([
                'success' => true,
                'data' => $recommendations
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get performance recommendations', [
                'error' => $e->getMessage(),
                'period' => $period
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance recommendations'
            ], 500);
        }
    }

    /**
     * Get page-specific performance data
     */
    public function getPagePerformance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|string|max:500',
            'period' => ['nullable', Rule::in(['1h', '24h', '7d', '30d'])],
        ]);

        $url = $validated['url'];
        $period = $validated['period'] ?? '24h';

        try {
            $performance = $this->getPagePerformanceData($url, $period);
            
            return response()->json([
                'success' => true,
                'data' => $performance
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get page performance', [
                'error' => $e->getMessage(),
                'url' => $url,
                'period' => $period
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get page performance'
            ], 500);
        }
    }

    /**
     * Get real-time performance metrics
     */
    public function getRealTimeMetrics(): JsonResponse
    {
        try {
            $metrics = Cache::get('performance_metrics_realtime', []);
            
            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get real-time metrics', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get real-time metrics'
            ], 500);
        }
    }

    /**
     * Store performance session data (enhanced monitoring)
     */
    public function storeSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sessionId' => 'required|string',
            'startTime' => 'required|integer',
            'metrics' => 'required|array',
            'metrics.*.loadTime' => 'required|numeric',
            'metrics.*.renderTime' => 'required|numeric',
            'metrics.*.memoryUsage' => 'required|numeric',
            'metrics.*.bundleSize' => 'required|numeric',
            'metrics.*.networkRequests' => 'required|integer',
            'metrics.*.firstContentfulPaint' => 'nullable|numeric',
            'metrics.*.largestContentfulPaint' => 'nullable|numeric',
            'metrics.*.cumulativeLayoutShift' => 'nullable|numeric',
            'metrics.*.firstInputDelay' => 'nullable|numeric',
            'userAgent' => 'required|string',
            'url' => 'required|string',
            'viewport' => 'nullable|array',
            'connection' => 'nullable|string'
        ]);

        try {
            // Store session data
            $sessionId = DB::table('performance_sessions')->insertGetId([
                'url' => $validated['url'],
                'user_agent' => $validated['userAgent'],
                'viewport_width' => $validated['viewport']['width'] ?? null,
                'viewport_height' => $validated['viewport']['height'] ?? null,
                'connection_type' => $validated['connection'] ?? null,
                'user_id' => auth()->id(),
                'tenant_id' => tenant('id'),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Store individual metrics
            $metricsData = [];
            foreach ($validated['metrics'] as $metricData) {
                foreach ($metricData as $name => $value) {
                    if (is_numeric($value)) {
                        $metricsData[] = [
                            'session_id' => $sessionId,
                            'name' => $name,
                            'value' => $value,
                            'url' => $validated['url'],
                            'metadata' => json_encode([
                                'session_id' => $validated['sessionId'],
                                'viewport' => $validated['viewport'] ?? null,
                                'connection' => $validated['connection'] ?? null
                            ]),
                            'timestamp' => $validated['startTime'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }

            if (!empty($metricsData)) {
                DB::table('performance_metrics')->insert($metricsData);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'metrics_count' => count($metricsData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to store performance session', [
                'error' => $e->getMessage(),
                'session_id' => $validated['sessionId']
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store performance session'
            ], 500);
        }
    }

    /**
     * Get Core Web Vitals summary
     */
    public function getCoreWebVitals(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['1h', '24h', '7d', '30d'])],
            'page' => 'nullable|string|max:500'
        ]);

        $period = $validated['period'] ?? '24h';
        $page = $validated['page'] ?? null;

        try {
            $vitals = $this->getCoreWebVitalsData($period, $page);
            
            return response()->json([
                'success' => true,
                'data' => $vitals
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get Core Web Vitals', [
                'error' => $e->getMessage(),
                'period' => $period
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get Core Web Vitals'
            ], 500);
        }
    }

    /**
     * Store metrics in database
     */
    private function storeMetricsInDatabase(array $data): void
    {
        $sessionData = $data['session'];
        $metrics = $data['metrics'];

        // Store session information
        $sessionId = DB::table('performance_sessions')->insertGetId([
            'url' => $sessionData['url'],
            'referrer' => $sessionData['referrer'] ?? null,
            'user_agent' => $sessionData['userAgent'],
            'viewport_width' => $sessionData['viewport']['width'],
            'viewport_height' => $sessionData['viewport']['height'],
            'screen_width' => $sessionData['screen']['width'],
            'screen_height' => $sessionData['screen']['height'],
            'screen_color_depth' => $sessionData['screen']['colorDepth'] ?? null,
            'connection_type' => $sessionData['connection']['effectiveType'] ?? null,
            'connection_downlink' => $sessionData['connection']['downlink'] ?? null,
            'connection_rtt' => $sessionData['connection']['rtt'] ?? null,
            'connection_save_data' => $sessionData['connection']['saveData'] ?? false,
            'memory_used' => $sessionData['memory']['usedJSHeapSize'] ?? null,
            'memory_total' => $sessionData['memory']['totalJSHeapSize'] ?? null,
            'memory_limit' => $sessionData['memory']['jsHeapSizeLimit'] ?? null,
            'user_id' => auth()->id(),
            'tenant_id' => tenant('id'),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Store individual metrics
        $metricsData = [];
        foreach ($metrics as $metric) {
            $metricsData[] = [
                'session_id' => $sessionId,
                'name' => $metric['name'],
                'value' => $metric['value'],
                'url' => $metric['url'],
                'metadata' => json_encode($metric),
                'timestamp' => $metric['timestamp'],
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Batch insert metrics
        if (!empty($metricsData)) {
            DB::table('performance_metrics')->insert($metricsData);
        }
    }

    /**
     * Cache recent metrics for real-time monitoring
     */
    private function cacheRecentMetrics(array $data): void
    {
        $cacheKey = 'performance_metrics_realtime';
        $cacheDuration = 300; // 5 minutes

        $recentMetrics = Cache::get($cacheKey, []);
        
        // Add new metrics
        foreach ($data['metrics'] as $metric) {
            $recentMetrics[] = [
                'name' => $metric['name'],
                'value' => $metric['value'],
                'url' => $metric['url'],
                'timestamp' => $metric['timestamp']
            ];
        }

        // Keep only last 100 metrics
        $recentMetrics = array_slice($recentMetrics, -100);

        Cache::put($cacheKey, $recentMetrics, $cacheDuration);
    }

    /**
     * Check performance thresholds and alert if necessary
     */
    private function checkPerformanceThresholds(array $metrics): void
    {
        $thresholds = [
            'LCP' => 2500, // Largest Contentful Paint (ms)
            'FID' => 100,  // First Input Delay (ms)
            'CLS' => 0.1,  // Cumulative Layout Shift
            'FCP' => 1800, // First Contentful Paint (ms)
            'TTFB' => 800, // Time to First Byte (ms)
        ];

        foreach ($metrics as $metric) {
            $name = $metric['name'];
            $value = $metric['value'];
            
            if (isset($thresholds[$name]) && $value > $thresholds[$name]) {
                Log::warning('Performance threshold exceeded', [
                    'metric' => $name,
                    'value' => $value,
                    'threshold' => $thresholds[$name],
                    'url' => $metric['url'],
                    'user_id' => auth()->id(),
                    'tenant_id' => tenant('id')
                ]);

                // You could also send alerts to monitoring services here
                // $this->sendPerformanceAlert($metric, $thresholds[$name]);
            }
        }
    }

    /**
     * Get performance analytics data
     */
    private function getPerformanceAnalytics(string $period, ?string $metric, ?string $page): array
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $query = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'));

        if ($metric) {
            $query->where('pm.name', $metric);
        }

        if ($page) {
            $query->where('pm.url', 'like', "%{$page}%");
        }

        // Get basic statistics
        $stats = $query->selectRaw('
            pm.name,
            COUNT(*) as count,
            AVG(pm.value) as avg_value,
            MIN(pm.value) as min_value,
            MAX(pm.value) as max_value,
            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY pm.value) as median_value,
            PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY pm.value) as p95_value
        ')
        ->groupBy('pm.name')
        ->get();

        // Get time series data
        $timeSeries = $query->selectRaw('
            pm.name,
            DATE_TRUNC(\'hour\', pm.created_at) as hour,
            AVG(pm.value) as avg_value,
            COUNT(*) as count
        ')
        ->groupBy('pm.name', 'hour')
        ->orderBy('hour')
        ->get()
        ->groupBy('name');

        // Get top slow pages
        $slowPages = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->whereIn('pm.name', ['LCP', 'FCP', 'TTFB'])
            ->selectRaw('
                pm.url,
                pm.name,
                AVG(pm.value) as avg_value,
                COUNT(*) as count
            ')
            ->groupBy('pm.url', 'pm.name')
            ->orderByDesc('avg_value')
            ->limit(10)
            ->get()
            ->groupBy('url');

        return [
            'period' => $period,
            'statistics' => $stats,
            'timeSeries' => $timeSeries,
            'slowPages' => $slowPages,
            'generatedAt' => now()->toISOString()
        ];
    }

    /**
     * Get Core Web Vitals data
     */
    private function getCoreWebVitalsData(string $period, ?string $page): array
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $query = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->whereIn('pm.name', ['LCP', 'FID', 'CLS', 'FCP', 'TTFB']);

        if ($page) {
            $query->where('pm.url', 'like', "%{$page}%");
        }

        $vitals = $query->selectRaw('
            pm.name,
            COUNT(*) as count,
            AVG(pm.value) as avg_value,
            PERCENTILE_CONT(0.75) WITHIN GROUP (ORDER BY pm.value) as p75_value,
            PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY pm.value) as p95_value
        ')
        ->groupBy('pm.name')
        ->get()
        ->keyBy('name');

        // Calculate scores based on Core Web Vitals thresholds
        $thresholds = [
            'LCP' => ['good' => 2500, 'poor' => 4000],
            'FID' => ['good' => 100, 'poor' => 300],
            'CLS' => ['good' => 0.1, 'poor' => 0.25],
            'FCP' => ['good' => 1800, 'poor' => 3000],
            'TTFB' => ['good' => 800, 'poor' => 1800]
        ];

        $scores = [];
        foreach ($vitals as $name => $vital) {
            if (isset($thresholds[$name])) {
                $p75 = $vital->p75_value;
                $threshold = $thresholds[$name];
                
                if ($p75 <= $threshold['good']) {
                    $score = 'good';
                } elseif ($p75 <= $threshold['poor']) {
                    $score = 'needs-improvement';
                } else {
                    $score = 'poor';
                }
                
                $scores[$name] = $score;
            }
        }

        return [
            'period' => $period,
            'vitals' => $vitals,
            'scores' => $scores,
            'thresholds' => $thresholds,
            'generatedAt' => now()->toISOString()
        ];
    }

    /**
     * Generate performance recommendations
     */
    private function generatePerformanceRecommendations(string $period): array
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        $recommendations = [];

        // Get Core Web Vitals data
        $vitals = $this->getCoreWebVitalsData($period, null);
        
        // Check each vital and generate recommendations
        foreach ($vitals['vitals'] as $name => $vital) {
            $score = $vitals['scores'][$name] ?? 'good';
            
            if ($score === 'poor' || $score === 'needs-improvement') {
                $recommendations[] = [
                    'id' => "cwv-{$name}",
                    'title' => "Improve {$name}",
                    'description' => $this->getVitalRecommendation($name),
                    'priority' => $score === 'poor' ? 'high' : 'medium',
                    'impact' => $score === 'poor' ? 'High user experience improvement' : 'Moderate user experience improvement',
                    'metric' => $name,
                    'currentValue' => $vital->p75_value,
                    'targetValue' => $vitals['thresholds'][$name]['good']
                ];
            }
        }

        // Check for slow API responses
        $slowApis = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->where('pm.name', 'ApiRequest')
            ->selectRaw('AVG(pm.value) as avg_value, COUNT(*) as count')
            ->first();

        if ($slowApis && $slowApis->avg_value > 2000) {
            $recommendations[] = [
                'id' => 'slow-api',
                'title' => 'Optimize API Response Times',
                'description' => 'API endpoints are responding slowly. Consider implementing caching, database query optimization, or using a CDN.',
                'priority' => 'high',
                'impact' => 'Faster page loads and better user experience',
                'metric' => 'ApiRequest',
                'currentValue' => $slowApis->avg_value,
                'targetValue' => 2000
            ];
        }

        // Check for large resource loads
        $slowResources = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->where('pm.name', 'ResourceLoad')
            ->selectRaw('AVG(pm.value) as avg_value, COUNT(*) as count')
            ->first();

        if ($slowResources && $slowResources->avg_value > 1000) {
            $recommendations[] = [
                'id' => 'slow-resources',
                'title' => 'Optimize Resource Loading',
                'description' => 'Large resources are slowing down page loads. Consider image compression, lazy loading, or using a CDN.',
                'priority' => 'medium',
                'impact' => 'Faster initial page loads',
                'metric' => 'ResourceLoad',
                'currentValue' => $slowResources->avg_value,
                'targetValue' => 1000
            ];
        }

        // Check for excessive DOM size
        $largeDom = DB::table('performance_sessions as ps')
            ->where('ps.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->whereNotNull('ps.memory_used')
            ->selectRaw('AVG(ps.memory_used) as avg_memory')
            ->first();

        if ($largeDom && $largeDom->avg_memory > 50000000) { // 50MB
            $recommendations[] = [
                'id' => 'large-dom',
                'title' => 'Optimize Memory Usage',
                'description' => 'High memory usage detected. Consider reducing DOM size, implementing virtualization, or optimizing JavaScript.',
                'priority' => 'medium',
                'impact' => 'Better performance on low-end devices',
                'metric' => 'MemoryUsage',
                'currentValue' => $largeDom->avg_memory,
                'targetValue' => 50000000
            ];
        }

        return [
            'recommendations' => $recommendations,
            'period' => $period,
            'generatedAt' => now()->toISOString()
        ];
    }

    /**
     * Get page-specific performance data
     */
    private function getPagePerformanceData(string $url, string $period): array
    {
        $hours = match($period) {
            '1h' => 1,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24
        };

        // Get metrics for specific page
        $metrics = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->where('pm.url', 'like', "%{$url}%")
            ->selectRaw('
                pm.name,
                COUNT(*) as count,
                AVG(pm.value) as avg_value,
                MIN(pm.value) as min_value,
                MAX(pm.value) as max_value,
                PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY pm.value) as median_value,
                PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY pm.value) as p95_value
            ')
            ->groupBy('pm.name')
            ->get();

        // Get time series data
        $timeSeries = DB::table('performance_metrics as pm')
            ->join('performance_sessions as ps', 'pm.session_id', '=', 'ps.id')
            ->where('pm.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->where('pm.url', 'like', "%{$url}%")
            ->selectRaw('
                pm.name,
                DATE_TRUNC(\'hour\', pm.created_at) as hour,
                AVG(pm.value) as avg_value,
                COUNT(*) as count
            ')
            ->groupBy('pm.name', 'hour')
            ->orderBy('hour')
            ->get()
            ->groupBy('name');

        // Get user sessions for this page
        $sessions = DB::table('performance_sessions as ps')
            ->where('ps.created_at', '>=', now()->subHours($hours))
            ->where('ps.tenant_id', tenant('id'))
            ->where('ps.url', 'like', "%{$url}%")
            ->selectRaw('
                COUNT(*) as total_sessions,
                AVG(ps.viewport_width) as avg_viewport_width,
                AVG(ps.viewport_height) as avg_viewport_height,
                COUNT(DISTINCT ps.user_agent) as unique_browsers
            ')
            ->first();

        return [
            'url' => $url,
            'period' => $period,
            'metrics' => $metrics,
            'timeSeries' => $timeSeries,
            'sessions' => $sessions,
            'generatedAt' => now()->toISOString()
        ];
    }

    /**
     * Get recommendation text for Core Web Vitals
     */
    private function getVitalRecommendation(string $vital): string
    {
        return match($vital) {
            'LCP' => 'Optimize images, remove unused CSS/JS, use CDN, and improve server response times.',
            'FID' => 'Reduce JavaScript execution time, remove non-critical third-party scripts, and use web workers.',
            'CLS' => 'Set size attributes on images and videos, avoid inserting content above existing content.',
            'FCP' => 'Eliminate render-blocking resources, minify CSS, and optimize web fonts.',
            'TTFB' => 'Optimize server configuration, use CDN, and implement caching strategies.',
            default => 'Optimize this metric for better performance.'
        };
    }
}