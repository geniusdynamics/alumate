<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

/**
 * Performance Monitoring and Alerting Service
 *
 * Monitors performance metrics, enforces budgets, and sends alerts.
 */
class PerformanceMonitoringService
{
    const CACHE_KEY = 'performance_metrics';
    const ALERT_THRESHOLD_TTL = 3600; // 1 hour

    private array $performanceBudgets = [
        'response_time' => [
            'warning' => 500, // ms
            'critical' => 1000, // ms
        ],
        'memory_usage' => [
            'warning' => 128, // MB
            'critical' => 256, // MB
        ],
        'component_render_time' => [
            'warning' => 100, // ms
            'critical' => 300, // ms
        ],
        'database_query_time' => [
            'warning' => 50, // ms
            'critical' => 200, // ms
        ],
    ];

    private array $alertCooldowns = [];

    /**
     * Monitor component performance and send alerts
     */
    public function monitorComponent(Component $component, array $metrics): void
    {
        $violations = $this->checkPerformanceViolations($metrics, $component);

        if (!empty($violations)) {
            $this->handlePerformanceAlerts($component, $violations);
        }

        // Store metrics for trend analysis
        $this->storePerformanceMetrics($component, $metrics);
    }

    /**
     * Monitor system-wide performance
     */
    public function monitorSystemPerformance(): void
    {
        $systemMetrics = $this->collectSystemMetrics();

        // Check memory usage
        if ($systemMetrics['memory_usage'] >= $this->performanceBudgets['memory_usage']['critical']) {
            $this->sendSystemAlert('critical', 'High Memory Usage', [
                'current_usage' => $systemMetrics['memory_usage'],
                'threshold' => $this->performanceBudgets['memory_usage']['critical'],
                'percentage' => round(($systemMetrics['memory_usage'] / 256) * 100, 2),
            ]);
        } elseif ($systemMetrics['memory_usage'] >= $this->performanceBudgets['memory_usage']['warning']) {
            $this->sendSystemAlert('warning', 'Elevated Memory Usage', [
                'current_usage' => $systemMetrics['memory_usage'],
                'threshold' => $this->performanceBudgets['memory_usage']['warning'],
            ]);
        }

        // Store system metrics
        Cache::put('system_performance_metrics', $systemMetrics, 3600);
    }

    /**
     * Check performance violations against budgets
     */
    private function checkPerformanceViolations(array $metrics, Component $component): array
    {
        $violations = [];

        // Check component render time
        if (isset($metrics['render_time'])) {
            if ($metrics['render_time'] >= $this->performanceBudgets['component_render_time']['critical']) {
                $violations[] = [
                    'type' => 'critical',
                    'metric' => 'component_render_time',
                    'value' => $metrics['render_time'],
                    'threshold' => $this->performanceBudgets['component_render_time']['critical'],
                    'message' => "Component render time critically high: {$metrics['render_time']}ms",
                ];
            } elseif ($metrics['render_time'] >= $this->performanceBudgets['component_render_time']['warning']) {
                $violations[] = [
                    'type' => 'warning',
                    'metric' => 'component_render_time',
                    'value' => $metrics['render_time'],
                    'threshold' => $this->performanceBudgets['component_render_time']['warning'],
                    'message' => "Component render time elevated: {$metrics['render_time']}ms",
                ];
            }
        }

        return $violations;
    }

    /**
     * Handle performance alerts
     */
    private function handlePerformanceAlerts(Component $component, array $violations): void
    {
        foreach ($violations as $violation) {
            $alertKey = $this->generateAlertKey($component, $violation);

            // Prevent alert spam with cooldown
            if ($this->isAlertCooldownActive($alertKey)) {
                continue;
            }

            Log::warning('Performance violation detected', [
                'component_id' => $component->id,
                'violation' => $violation,
            ]);

            $this->sendAlert($component, $violation);

            // Set cooldown
            $this->setAlertCooldown($alertKey);
        }
    }

    /**
     * Send performance alert
     */
    private function sendAlert(Component $component, array $violation): void
    {
        $alertData = [
            'component_id' => $component->id,
            'component_name' => $component->name,
            'tenant_id' => $component->tenant_id,
            'violation_type' => $violation['type'],
            'metric' => $violation['metric'],
            'value' => $violation['value'],
            'threshold' => $violation['threshold'],
            'message' => $violation['message'],
            'timestamp' => now()->toISOString(),
        ];

        // Log alert
        Log::channel('performance_alerts')->warning('Performance Alert', $alertData);

        // Send notification (would implement specific notification channels)
//        Notification::route('slack', env('SLACK_WEBHOOK_PERFORMANCE'))
//            ->notify(new PerformanceAlert($alertData));

        // Store alert for dashboard
        $this->storeAlert($alertData);
    }

    /**
     * Send system alert
     */
    private function sendSystemAlert(string $severity, string $title, array $data): void
    {
        $alertData = [
            'type' => 'system',
            'severity' => $severity,
            'title' => $title,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance_alerts')->$severity($title, $alertData);
        $this->storeAlert($alertData);
    }

    /**
     * Store performance metrics for analysis
     */
    private function storePerformanceMetrics(Component $component, array $metrics): void
    {
        $key = "component_performance:{$component->tenant_id}:{$component->id}";
        $metrics['timestamp'] = now()->toISOString();

        // Keep last 100 metrics
        $existing = Cache::get($key, []);
        array_unshift($existing, $metrics);

        if (count($existing) > 100) {
            array_pop($existing);
        }

        Cache::put($key, $existing, 86400); // Keep for 24 hours
    }

    /**
     * Store alert for dashboard retrieval
     */
    private function storeAlert(array $alert): void
    {
        $alerts = Cache::get('performance_alerts', []);
        array_unshift($alerts, $alert);

        // Keep last 1000 alerts
        if (count($alerts) > 1000) {
            array_pop($alerts);
        }

        Cache::put('performance_alerts', $alerts, 86400);
    }

    /**
     * Generate alert cooldown key
     */
    private function generateAlertKey(Component $component, array $violation): string
    {
        return "alert_cooldown:{$component->tenant_id}:{$component->id}:{$violation['metric']}:{$violation['type']}";
    }

    /**
     * Check if alert cooldown is active
     */
    private function isAlertCooldownActive(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Set alert cooldown
     */
    private function setAlertCooldown(string $key): void
    {
        $ttl = $this->getCooldownTtl();
        Cache::put($key, true, $ttl);
    }

    /**
     * Get cooldown TTL based on alert severity
     */
    private function getCooldownTtl(): int
    {
        // Different cooldowns for development vs production
        return env('APP_ENV') === 'production' ? 3600 : 300; // 1 hour in prod, 5 minutes in dev
    }

    /**
     * Collect system metrics
     */
    public function collectSystemMetrics(): array
    {
        // Use standard PHP functions to get memory usage
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB

        // Calculate response time if available
        $responseTime = defined('LARAVEL_START') ?
            microtime(true) - LARAVEL_START : 0;

        return [
            'memory_usage' => round($memoryUsage, 2),
            'peak_memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'response_time' => round($responseTime * 1000, 2),
            'cpu_usage' => sys_getloadavg()[0] ?? 0,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Update performance budgets
     */
    public function updatePerformanceBudgets(array $newBudgets): void
    {
        $this->performanceBudgets = array_merge($this->performanceBudgets, $newBudgets);

        Log::info('Performance budgets updated', [
            'new_budgets' => $newBudgets,
        ]);
    }

    /**
     * Get performance report
     */
    public function getPerformanceReport(int $tenantId = null, int $days = 7): array
    {
        $startDate = now()->subDays($days);

        // Get component performance trends
        $components = Component::query()
            ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
            ->where('created_at', '>=', $startDate)
            ->get();

        $componentReports = [];
        foreach ($components as $component) {
            $metrics = $this->getComponentPerformanceHistory($component);
            $componentReports[] = [
                'component_id' => $component->id,
                'component_name' => $component->name,
                'avg_render_time' => collect($metrics)->avg('render_time'),
                'max_render_time' => collect($metrics)->max('render_time'),
                'total_views' => collect($metrics)->sum('views'),
                'metrics' => $metrics,
            ];
        }

        // Get system performance
        $systemPerformance = Cache::get('system_performance_metrics', []);

        // Get alerts
        $alerts = Cache::get('performance_alerts', []);

        return [
            'period' => "{$days} days",
            'start_date' => $startDate->toDateString(),
            'end_date' => now()->toDateString(),
            'component_reports' => $componentReports,
            'system_performance' => $systemPerformance,
            'alerts' => $alerts,
            'budget_compliance' => $this->calculateBudgetCompliance($componentReports),
        ];
    }

    /**
     * Get component performance history
     */
    private function getComponentPerformanceHistory(Component $component): array
    {
        $key = "component_performance:{$component->tenant_id}:{$component->id}";
        return Cache::get($key, []);
    }

    /**
     * Calculate budget compliance
     */
    private function calculateBudgetCompliance(array $componentReports): array
    {
        $totalComponents = count($componentReports);
        $compliantComponents = 0;
        $warningComponents = 0;
        $criticalComponents = 0;

        foreach ($componentReports as $report) {
            $avgRenderTime = $report['avg_render_time'] ?? 0;

            if ($avgRenderTime >= $this->performanceBudgets['component_render_time']['critical']) {
                $criticalComponents++;
            } elseif ($avgRenderTime >= $this->performanceBudgets['component_render_time']['warning']) {
                $warningComponents++;
            } else {
                $compliantComponents++;
            }
        }

        return [
            'total_components' => $totalComponents,
            'compliant' => $compliantComponents,
            'warning' => $warningComponents,
            'critical' => $criticalComponents,
            'compliance_rate' => $totalComponents > 0 ? round(($compliantComponents / $totalComponents) * 100, 2) : 0,
        ];
    }

    /**
     * Generate performance recommendations
     */
    public function generatePerformanceRecommendations(int $tenantId = null): array
    {
        $report = $this->getPerformanceReport($tenantId, 30); // Last 30 days

        $recommendations = [];

        // Analyze component render times
        foreach ($report['component_reports'] as $componentReport) {
            if ($componentReport['avg_render_time'] > $this->performanceBudgets['component_render_time']['warning']) {
                $recommendations[] = [
                    'type' => 'component_optimization',
                    'component_id' => $componentReport['component_id'],
                    'component_name' => $componentReport['component_name'],
                    'priority' => 'high',
                    'recommendation' => 'Optimize component render time with lazy loading and caching',
                    'potential_gain' => round($componentReport['avg_render_time'] - 50, 2) . 'ms',
                ];
            }
        }

        // System-wide recommendations
        if ($report['budget_compliance']['critical'] > 0) {
            $recommendations[] = [
                'type' => 'system_optimization',
                'priority' => 'critical',
                'recommendation' => 'Critical performance issues detected. Consider upgrading infrastructure or optimizing queries.',
                'affected_components' => $report['budget_compliance']['critical'],
            ];
        }

        return $recommendations;
    }

    /**
     * Clean up old performance data
     */
    public function cleanupOldPerformanceData(): void
    {
        $oldDate = now()->subDays(90); // Keep 90 days of data

        // Clean up component performance data
        $components = Component::all();
        foreach ($components as $component) {
            $key = "component_performance:{$component->tenant_id}:{$component->id}";
            $existingMetrics = Cache::get($key, []);

            $filteredMetrics = collect($existingMetrics)
                ->filter(fn($metric) => isset($metric['timestamp']))
                ->filter(fn($metric) => Carbon::parse($metric['timestamp'])->gte($oldDate))
                ->values()
                ->toArray();

            Cache::put($key, $filteredMetrics, 86400);
        }

        Log::info('Old performance data cleaned up', [
            'cutoff_date' => $oldDate->toDateString(),
        ]);
    }
}
