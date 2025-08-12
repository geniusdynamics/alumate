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
}