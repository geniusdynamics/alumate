<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductionMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API Controller for production monitoring and analytics
 */
class MonitoringController extends Controller
{
    protected ProductionMonitoringService $monitoringService;

    public function __construct(ProductionMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Get production dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        $timeframe = $request->query('timeframe', 'realtime');

        try {
            $data = $this->monitoringService->getDashboardData($timeframe);

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard data retrieval failed', [
                'error' => $e->getMessage(),
                'timeframe' => $timeframe
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute monitoring cycle manually
     */
    public function executeCycle(Request $request): JsonResponse
    {
        try {
            $results = $this->monitoringService->executeMonitoringCycle();

            return response()->json([
                'status' => 'success',
                'message' => 'Monitoring cycle executed successfully',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            \Log::error('Manual monitoring cycle failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Monitoring cycle execution failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific monitoring metrics
     */
    public function metrics(Request $request, string $type): JsonResponse
    {
        $metric = $request->query('metric');
        $filters = $request->except(['metric']);

        try {
            switch ($type) {
                case 'performance':
                    $data = $this->monitoringService->monitorPerformance();
                    break;
                case 'security':
                    $data = $this->monitoringService->monitorSecurity();
                    break;
                case 'analytics':
                    $data = $this->monitoringService->monitorAnalytics();
                    break;
                case 'health':
                    $data = $this->monitoringService->checkSystemHealth();
                    break;
                default:
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid metric type requested'
                    ], 400);
            }

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error("Metrics retrieval failed for type: {$type}", [
                'error' => $e->getMessage(),
                'metric' => $metric
            ]);

            return response()->json([
                'status' => 'error',
                'message' => "Failed to retrieve {$type} metrics",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get alerts and notifications
     */
    public function alerts(Request $request): JsonResponse
    {
        $severity = $request->query('severity');
        $resolved = $request->query('resolved', false);

        try {
            $alerts = $this->monitoringService->processAlerts()['details'] ?? [];

            // Filter by severity if specified
            if ($severity) {
                $alerts = array_filter($alerts, fn($alert) => $alert['priority'] === $severity);
            }

            // Filter by resolved status
            if ($resolved !== null) {
                $isResolved = filter_var($resolved, FILTER_VALIDATE_BOOLEAN);
                $alerts = array_filter($alerts, fn($alert) => isset($alert['resolved']) && $alert['resolved'] === $isResolved);
            }

            return response()->json([
                'status' => 'success',
                'total' => count($alerts),
                'alerts' => array_values($alerts),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Alerts retrieval failed', [
                'error' => $e->getMessage(),
                'severity' => $severity,
                'resolved' => $resolved
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve alerts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get automated reports
     */
    public function reports(Request $request): JsonResponse
    {
        $type = $request->query('type', 'daily');

        try {
            $reports = $this->monitoringService->generateAutomatedReports();

            if (isset($reports[$type])) {
                return response()->json([
                    'status' => 'success',
                    'report_type' => $type,
                    'data' => $reports[$type],
                    'timestamp' => now()->toISOString(),
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Report type not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error("Report generation failed for type: {$type}", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get real-time monitoring data
     */
    public function realtime(Request $request): JsonResponse
    {
        try {
            $data = \Cache::get('realtime_monitoring', []);

            if (empty($data)) {
                // Execute a quick cycle if no cached data
                $data = $this->monitoringService->executeMonitoringCycle();
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Real-time data retrieval failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve real-time data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monitoring configuration
     */
    public function settings(Request $request): JsonResponse
    {
        $config = [
            'alert_channels' => \App\Services\ProductionMonitoringService::ALERT_PRIORITIES,
            'monitoring_frequencies' => [
                'realtime' => 'Real-time (every 5 minutes)',
                'hourly' => 'Hourly',
                'daily' => 'Daily',
                'weekly' => 'Weekly',
            ],
            'performance_budgets' => [
                'response_time' => [
                    'warning' => 500,
                    'critical' => 1000,
                ],
                'memory_usage' => [
                    'warning' => 128,
                    'critical' => 256,
                ],
                'component_render_time' => [
                    'warning' => 100,
                    'critical' => 300,
                ],
            ],
            'cache_duration' => \App\Services\ProductionMonitoringService::CACHE_DURATION,
        ];

        return response()->json([
            'status' => 'success',
            'config' => $config,
        ]);
    }
}