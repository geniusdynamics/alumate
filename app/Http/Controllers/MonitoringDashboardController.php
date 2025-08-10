<?php

namespace App\Http\Controllers;

use App\Services\Homepage\MonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringDashboardController extends Controller
{
    public function __construct(
        private MonitoringService $monitoring
    ) {}

    /**
     * Show the monitoring dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Monitoring/Dashboard', [
            'dashboardData' => $this->monitoring->getDashboardData(),
        ]);
    }

    /**
     * Get dashboard data as JSON.
     */
    public function data(): JsonResponse
    {
        return response()->json($this->monitoring->getDashboardData());
    }

    /**
     * Get uptime status.
     */
    public function uptime(): JsonResponse
    {
        $results = $this->monitoring->checkUptime();
        
        return response()->json([
            'status' => collect($results)->every(fn($result) => $result['status'] === 'up') ? 'up' : 'down',
            'endpoints' => $results,
            'checked_at' => now()->toISOString(),
        ]);
    }

    /**
     * Get conversion metrics.
     */
    public function conversionMetrics(Request $request): JsonResponse
    {
        $timeframe = $request->get('timeframe', '7d');
        
        return response()->json($this->monitoring->getConversionMetrics());
    }

    /**
     * Get performance metrics.
     */
    public function performanceMetrics(Request $request): JsonResponse
    {
        $metricType = $request->get('type', 'page_load');
        $timeframe = $request->get('timeframe', '24h');
        
        // Convert timeframe to hours
        $hours = match ($timeframe) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
        
        $metrics = \DB::table('homepage_performance_metrics')
            ->where('metric_type', $metricType)
            ->where('recorded_at', '>=', now()->subHours($hours))
            ->selectRaw('
                DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00:00") as hour,
                AVG(value) as avg_value,
                MIN(value) as min_value,
                MAX(value) as max_value,
                COUNT(*) as sample_count
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        return response()->json([
            'metric_type' => $metricType,
            'timeframe' => $timeframe,
            'data' => $metrics,
        ]);
    }

    /**
     * Get error logs.
     */
    public function errorLogs(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        
        try {
            $logPath = storage_path('logs/homepage-errors.log');
            
            if (!file_exists($logPath)) {
                return response()->json([
                    'errors' => [],
                    'message' => 'No error log file found',
                ]);
            }
            
            $lines = array_slice(file($logPath), -$limit);
            $errors = [];
            
            foreach ($lines as $line) {
                if (preg_match('/\[(.*?)\].*?(ERROR|CRITICAL|ALERT).*?:(.*?)(\{.*\})?$/', $line, $matches)) {
                    $contextData = null;
                    if (isset($matches[4])) {
                        $contextData = json_decode($matches[4], true);
                    }
                    
                    $errors[] = [
                        'timestamp' => $matches[1],
                        'level' => $matches[2],
                        'message' => trim($matches[3]),
                        'context' => $contextData,
                    ];
                }
            }
            
            return response()->json([
                'errors' => array_reverse($errors), // Most recent first
                'total' => count($errors),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [],
                'error' => 'Failed to read error logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get system health status.
     */
    public function systemHealth(): JsonResponse
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'queue' => $this->checkQueueHealth(),
            'memory' => $this->getMemoryUsage(),
            'disk' => $this->getDiskUsage(),
        ];
        
        $overallStatus = collect($health)
            ->filter(fn($check) => isset($check['status']))
            ->every(fn($check) => $check['status'] === 'healthy') ? 'healthy' : 'unhealthy';
        
        return response()->json([
            'overall_status' => $overallStatus,
            'checks' => $health,
            'checked_at' => now()->toISOString(),
        ]);
    }

    /**
     * Record custom metric.
     */
    public function recordMetric(Request $request): JsonResponse
    {
        $request->validate([
            'metric_type' => 'required|string|max:50',
            'metric_name' => 'required|string|max:100',
            'value' => 'required|numeric',
            'unit' => 'required|string|max:20',
            'additional_data' => 'sometimes|array',
        ]);
        
        $this->monitoring->recordPerformanceMetric(
            $request->metric_type,
            $request->metric_name,
            $request->value,
            $request->unit,
            $request->additional_data ?? []
        );
        
        return response()->json([
            'message' => 'Metric recorded successfully',
            'recorded_at' => now()->toISOString(),
        ]);
    }

    /**
     * Test alert system.
     */
    public function testAlert(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:uptime,performance,error,conversion,security,test',
            'severity' => 'required|in:info,warning,error,critical',
        ]);
        
        // Use the AlertingService directly for testing
        $alertingService = app(\App\Services\Homepage\AlertingService::class);
        $alertingService->testAlert($request->type, $request->severity);
        
        return response()->json([
            'message' => 'Test alert sent successfully',
            'type' => $request->type,
            'severity' => $request->severity,
            'channels' => $this->getConfiguredAlertChannels(),
        ]);
    }
    
    /**
     * Get configured alert channels.
     */
    private function getConfiguredAlertChannels(): array
    {
        $config = config('services.monitoring', []);
        $channels = [];
        
        if (!empty($config['alert_email'])) {
            $channels[] = 'email';
        }
        
        if (!empty($config['slack_webhook'])) {
            $channels[] = 'slack';
        }
        
        if (!empty($config['pagerduty_key'])) {
            $channels[] = 'pagerduty';
        }
        
        if (app()->bound('sentry')) {
            $channels[] = 'sentry';
        }
        
        if (!empty($config['datadog_api_key'])) {
            $channels[] = 'datadog';
        }
        
        return $channels;
    }

    /**
     * Check database health.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            \DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'healthy',
                'response_time' => $responseTime,
                'connection' => config('database.default'),
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
            \Cache::put($testKey, 'test', 60);
            $value = \Cache::get($testKey);
            \Cache::forget($testKey);
            
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
     * Check queue health.
     */
    private function checkQueueHealth(): array
    {
        try {
            // Check if queue workers are running
            $queueSize = \Queue::size();
            
            return [
                'status' => 'healthy',
                'queue_size' => $queueSize,
                'driver' => config('queue.default'),
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
        $current = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        return [
            'current' => $current,
            'current_mb' => round($current / 1024 / 1024, 2),
            'peak' => $peak,
            'peak_mb' => round($peak / 1024 / 1024, 2),
            'limit' => $limit,
            'limit_mb' => $limit ? round($limit / 1024 / 1024, 2) : null,
            'usage_percentage' => $limit ? round(($current / $limit) * 100, 2) : null,
        ];
    }

    /**
     * Get disk usage.
     */
    private function getDiskUsage(): array
    {
        $path = base_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        
        return [
            'free' => $free,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total' => $total,
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used' => $total - $free,
            'used_gb' => round(($total - $free) / 1024 / 1024 / 1024, 2),
            'used_percentage' => round((($total - $free) / $total) * 100, 2),
        ];
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $limit): ?int
    {
        if ($limit === '-1') {
            return null; // Unlimited
        }
        
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => (int) $limit,
        };
    }
}