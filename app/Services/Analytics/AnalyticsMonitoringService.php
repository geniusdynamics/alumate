<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Services\TenantContextService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Monitoring Service
 *
 * Provides comprehensive monitoring and alerting for the analytics system,
 * including health checks, metrics monitoring, anomaly detection, and
 * alert management with proper tenant isolation.
 */
class AnalyticsMonitoringService
{
    // Health status constants
    public const STATUS_HEALTHY = 'healthy';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    public const STATUS_UNKNOWN = 'unknown';

    // Alert severity constants
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    // Cache TTL constants
    private const CACHE_TTL_SHORT = 60;        // 1 minute

    private const CACHE_TTL_MEDIUM = 300;       // 5 minutes

    private const CACHE_TTL_LONG = 3600;        // 1 hour

    private const CACHE_TTL_VERY_LONG = 86400;  // 24 hours

    // Monitoring intervals
    private const METRICS_CHECK_INTERVAL = 60;      // 1 minute

    private const HEALTH_CHECK_INTERVAL = 300;       // 5 minutes

    private const ANOMALY_DETECTION_INTERVAL = 900;  // 15 minutes

    private TenantContextService $tenantContext;

    private array $alertConfig;

    private array $healthCheckServices = [];

    public function __construct(TenantContextService $tenantContext)
    {
        $this->tenantContext = $tenantContext;
        $this->alertConfig = config('analytics.alerting', [
            'enabled' => true,
            'thresholds' => [
                'error_rate' => 5.0,
                'response_time' => 5000, // milliseconds
                'data_latency' => 300,   // seconds
                'queue_depth' => 100,
            ],
            'channels' => ['database', 'log'],
        ]);

        // Register health check services
        $this->registerHealthCheckServices();
    }

    /**
     * Check overall analytics system health
     *
     * @return array Health status with details
     */
    public function checkHealth(): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $cacheKey = $this->buildCacheKey('health_check', 'overall');

            return Cache::remember($cacheKey, self::CACHE_TTL_MEDIUM, function () use ($tenantId) {
                $healthResults = [];
                $overallStatus = self::STATUS_HEALTHY;
                $issues = [];

                // Check all registered services
                foreach ($this->healthCheckServices as $serviceName => $service) {
                    $result = $this->checkServiceHealth($serviceName);
                    $healthResults[$serviceName] = $result;

                    if ($result['status'] === self::STATUS_CRITICAL) {
                        $overallStatus = self::STATUS_CRITICAL;
                        $issues[] = $result['message'];
                    } elseif ($result['status'] === self::STATUS_WARNING && $overallStatus !== self::STATUS_CRITICAL) {
                        $overallStatus = self::STATUS_WARNING;
                        $issues[] = $result['message'];
                    }
                }

                // Check database connectivity
                $dbHealth = $this->checkDatabaseHealth();
                $healthResults['database'] = $dbHealth;
                if ($dbHealth['status'] === self::STATUS_CRITICAL) {
                    $overallStatus = self::STATUS_CRITICAL;
                    $issues[] = $dbHealth['message'];
                } elseif ($dbHealth['status'] === self::STATUS_WARNING && $overallStatus !== self::STATUS_CRITICAL) {
                    $overallStatus = self::STATUS_WARNING;
                    $issues[] = $dbHealth['message'];
                }

                $result = [
                    'status' => $overallStatus,
                    'timestamp' => now()->toIso8601String(),
                    'tenant_id' => $tenantId,
                    'services' => $healthResults,
                    'issues' => $issues,
                    'checks_performed' => count($healthResults),
                ];

                Log::info('Analytics health check completed', [
                    'tenant_id' => $tenantId,
                    'status' => $overallStatus,
                    'services_checked' => count($healthResults),
                ]);

                return $result;
            });

        } catch (Exception $e) {
            Log::error('Analytics health check failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'status' => self::STATUS_UNKNOWN,
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Check health of a specific service
     *
     * @param  string  $serviceName  Service to check
     * @return array Health check result
     */
    public function checkServiceHealth(string $serviceName): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $cacheKey = $this->buildCacheKey('health_check', $serviceName);

            return Cache::remember($cacheKey, self::CACHE_TTL_SHORT, function () use ($serviceName) {
                if (! isset($this->healthCheckServices[$serviceName])) {
                    return [
                        'service' => $serviceName,
                        'status' => self::STATUS_UNKNOWN,
                        'message' => "Service '{$serviceName}' is not registered",
                        'timestamp' => now()->toIso8601String(),
                    ];
                }

                $checkFunction = $this->healthCheckServices[$serviceName];
                $result = $checkFunction();

                return array_merge([
                    'service' => $serviceName,
                    'timestamp' => now()->toIso8601String(),
                ], $result);

            });

        } catch (Exception $e) {
            Log::error("Health check failed for service: {$serviceName}", [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'service' => $serviceName,
                'status' => self::STATUS_UNKNOWN,
                'message' => $e->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ];
        }
    }

    /**
     * Monitor analytics metrics
     *
     * @param  array  $options  Monitoring options
     * @return array Current metrics
     */
    public function monitorMetrics(array $options = []): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $timeframe = $options['timeframe'] ?? '1h';
            $metrics = [];

            // Calculate time range
            $timeframeMinutes = $this->parseTimeframe($timeframe);
            $startTime = now()->subMinutes($timeframeMinutes);

            // Event processing metrics
            $metrics['events'] = $this->getEventMetrics($startTime);

            // Query performance metrics
            $metrics['queries'] = $this->getQueryMetrics($startTime);

            // Data processing metrics
            $metrics['processing'] = $this->getProcessingMetrics($startTime);

            // Cache performance metrics
            $metrics['cache'] = $this->getCacheMetrics($startTime);

            // API metrics
            $metrics['api'] = $this->getApiMetrics($startTime);

            // Overall system load
            $metrics['system_load'] = $this->calculateSystemLoad($metrics);

            $result = [
                'timestamp' => now()->toIso8601String(),
                'timeframe' => $timeframe,
                'timeframe_minutes' => $timeframeMinutes,
                'tenant_id' => $tenantId,
                'metrics' => $metrics,
            ];

            Log::debug('Analytics metrics collected', [
                'tenant_id' => $tenantId,
                'timeframe' => $timeframe,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to collect analytics metrics', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Detect anomalies in metrics
     *
     * @param  array  $metrics  Metrics to analyze
     * @return array Detected anomalies
     */
    public function detectAnomalies(array $metrics): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $anomalies = [];
            $baseline = $this->getBaselineMetrics();

            // Check event count anomalies
            $eventAnomaly = $this->detectEventAnomaly($metrics['events'] ?? [], $baseline['events'] ?? []);
            if ($eventAnomaly) {
                $anomalies[] = $eventAnomaly;
            }

            // Check query performance anomalies
            $queryAnomaly = $this->detectQueryAnomaly($metrics['queries'] ?? [], $baseline['queries'] ?? []);
            if ($queryAnomaly) {
                $anomalies[] = $queryAnomaly;
            }

            // Check cache hit ratio anomalies
            $cacheAnomaly = $this->detectCacheAnomaly($metrics['cache'] ?? [], $baseline['cache'] ?? []);
            if ($cacheAnomaly) {
                $anomalies[] = $cacheAnomaly;
            }

            // Check API latency anomalies
            $apiAnomaly = $this->detectApiAnomaly($metrics['api'] ?? [], $baseline['api'] ?? []);
            if ($apiAnomaly) {
                $anomalies[] = $apiAnomaly;
            }

            $result = [
                'timestamp' => now()->toIso8601String(),
                'anomalies_detected' => count($anomalies),
                'anomalies' => $anomalies,
                'tenant_id' => $tenantId,
                'severity_distribution' => $this->calculateSeverityDistribution($anomalies),
            ];

            // Log anomalies for monitoring
            if (! empty($anomalies)) {
                Log::warning('Analytics anomalies detected', [
                    'tenant_id' => $tenantId,
                    'anomaly_count' => count($anomalies),
                    'severities' => $result['severity_distribution'],
                ]);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('Anomaly detection failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'timestamp' => now()->toIso8601String(),
                'error' => $e->getMessage(),
                'anomalies_detected' => 0,
                'anomalies' => [],
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Send an alert notification
     *
     * @param  array  $alert  Alert data
     * @return bool Success status
     */
    public function sendAlert(array $alert): bool
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            // Validate alert structure
            $this->validateAlert($alert);

            // Enrich alert with metadata
            $alert['tenant_id'] = $tenantId;
            $alert['sent_at'] = now()->toIso8601String();
            $alert['alert_id'] = $this->generateAlertId();

            // Store alert in database
            $this->storeAlert($alert);

            // Send notifications via configured channels
            $channels = $this->alertConfig['channels'] ?? ['database', 'log'];

            foreach ($channels as $channel) {
                $this->sendToChannel($channel, $alert);
            }

            // Log the alert
            Log::channel('analytics_alerts')->warning('Analytics alert sent', [
                'alert_id' => $alert['alert_id'],
                'type' => $alert['type'] ?? 'unknown',
                'severity' => $alert['severity'] ?? self::SEVERITY_INFO,
                'message' => $alert['message'] ?? '',
                'tenant_id' => $tenantId,
            ]);

            // Trigger anomaly detection if critical
            if (($alert['severity'] ?? self::SEVERITY_INFO) === self::SEVERITY_CRITICAL) {
                $this->triggerAnomalyAnalysis($alert);
            }

            return true;

        } catch (Exception $e) {
            Log::error('Failed to send alert', [
                'error' => $e->getMessage(),
                'alert' => $alert ?? [],
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return false;
        }
    }

    /**
     * Configure alert rules
     *
     * @param  array  $config  Alert configuration
     * @return array Updated configuration
     */
    public function configureAlerts(array $config): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            // Merge new config with existing
            $this->alertConfig = array_merge($this->alertConfig, $config);

            // Store configuration
            $this->storeAlertConfig($this->alertConfig);

            Log::info('Alert configuration updated', [
                'tenant_id' => $tenantId,
                'config' => $this->alertConfig,
            ]);

            return [
                'success' => true,
                'configuration' => $this->alertConfig,
                'updated_at' => now()->toIso8601String(),
                'tenant_id' => $tenantId,
            ];

        } catch (Exception $e) {
            Log::error('Failed to configure alerts', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Get alert history
     *
     * @param  array  $options  Query options
     * @return array Alert history
     */
    public function getAlertHistory(array $options = []): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $limit = $options['limit'] ?? 100;
            $offset = $options['offset'] ?? 0;
            $severity = $options['severity'] ?? null;
            $type = $options['type'] ?? null;
            $fromDate = $options['from_date'] ?? null;
            $toDate = $options['to_date'] ?? null;

            // For now, return mock data structure (would query database in production)
            $alerts = $this->getStoredAlerts([
                'limit' => $limit,
                'offset' => $offset,
                'severity' => $severity,
                'type' => $type,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]);

            $totalCount = $this->countStoredAlerts([
                'severity' => $severity,
                'type' => $type,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ]);

            return [
                'alerts' => $alerts,
                'total_count' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'tenant_id' => $tenantId,
                'retrieved_at' => now()->toIso8601String(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to retrieve alert history', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'alerts' => [],
                'total_count' => 0,
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    /**
     * Get monitoring dashboard data
     *
     * @param  array  $options  Dashboard options
     * @return array Dashboard data
     */
    public function getMonitoringDashboard(array $options = []): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $timeframe = $options['timeframe'] ?? '24h';

            // Collect all dashboard data
            $healthCheck = $this->checkHealth();
            $metrics = $this->monitorMetrics(['timeframe' => $timeframe]);
            $anomalies = $this->detectAnomalies($metrics['metrics'] ?? []);
            $recentAlerts = $this->getAlertHistory(['limit' => 10]);

            // Calculate dashboard summary
            $summary = $this->calculateDashboardSummary($healthCheck, $anomalies, $recentAlerts);

            $dashboard = [
                'generated_at' => now()->toIso8601String(),
                'timeframe' => $timeframe,
                'tenant_id' => $tenantId,
                'summary' => $summary,
                'health' => $healthCheck,
                'metrics' => $metrics['metrics'] ?? [],
                'anomalies' => $anomalies,
                'recent_alerts' => $recentAlerts['alerts'] ?? [],
                'system_status' => $this->determineSystemStatus($healthCheck, $anomalies),
            ];

            Log::debug('Monitoring dashboard data generated', [
                'tenant_id' => $tenantId,
                'timeframe' => $timeframe,
            ]);

            return $dashboard;

        } catch (Exception $e) {
            Log::error('Failed to generate monitoring dashboard', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ]);

            return [
                'generated_at' => now()->toIso8601String(),
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantContext->getCurrentTenantId(),
            ];
        }
    }

    // Private helper methods

    /**
     * Register health check services
     */
    private function registerHealthCheckServices(): void
    {
        $this->healthCheckServices = [
            'event_processor' => function () {
                return $this->checkEventProcessorHealth();
            },
            'query_engine' => function () {
                return $this->checkQueryEngineHealth();
            },
            'cache_layer' => function () {
                return $this->checkCacheLayerHealth();
            },
            'data_pipeline' => function () {
                return $this->checkDataPipelineHealth();
            },
            'api_gateway' => function () {
                return $this->checkApiGatewayHealth();
            },
        ];
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::select('SELECT 1');
            $responseTime = (microtime(true) - $startTime) * 1000;

            // Check for recent errors
            $recentErrors = $this->getRecentDatabaseErrors();

            if ($responseTime > 5000) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Slow database response',
                    'response_time_ms' => round($responseTime, 2),
                    'recent_errors' => $recentErrors,
                ];
            }

            if (! empty($recentErrors)) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Recent database errors detected',
                    'response_time_ms' => round($responseTime, 2),
                    'recent_errors' => $recentErrors,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Database connection healthy',
                'response_time_ms' => round($responseTime, 2),
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check event processor health
     */
    private function checkEventProcessorHealth(): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();
            $queueDepth = $this->getEventQueueDepth();

            if ($queueDepth > ($this->alertConfig['thresholds']['queue_depth'] ?? 100)) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Event queue is backing up',
                    'queue_depth' => $queueDepth,
                ];
            }

            $processingRate = $this->getEventProcessingRate();
            $lagSeconds = $this->getEventProcessingLag();

            if ($lagSeconds > ($this->alertConfig['thresholds']['data_latency'] ?? 300)) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Event processing lag detected',
                    'queue_depth' => $queueDepth,
                    'processing_rate_per_min' => $processingRate,
                    'lag_seconds' => $lagSeconds,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Event processor healthy',
                'queue_depth' => $queueDepth,
                'processing_rate_per_min' => $processingRate,
                'lag_seconds' => $lagSeconds,
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Event processor check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check query engine health
     */
    private function checkQueryEngineHealth(): array
    {
        try {
            $avgResponseTime = $this->getAverageQueryResponseTime();
            $slowQueries = $this->getSlowQueryCount();

            if ($avgResponseTime > ($this->alertConfig['thresholds']['response_time'] ?? 5000)) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'High average query response time',
                    'avg_response_time_ms' => $avgResponseTime,
                    'slow_queries_last_hour' => $slowQueries,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Query engine healthy',
                'avg_response_time_ms' => $avgResponseTime,
                'slow_queries_last_hour' => $slowQueries,
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Query engine check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache layer health
     */
    private function checkCacheLayerHealth(): array
    {
        try {
            $hitRatio = $this->getCacheHitRatio();
            $memoryUsage = $this->getCacheMemoryUsage();

            if ($hitRatio < 70) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Low cache hit ratio',
                    'hit_ratio' => $hitRatio,
                    'memory_usage_mb' => $memoryUsage,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Cache layer healthy',
                'hit_ratio' => $hitRatio,
                'memory_usage_mb' => $memoryUsage,
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Cache layer check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check data pipeline health
     */
    private function checkDataPipelineHealth(): array
    {
        try {
            $pipelineStatus = $this->getPipelineStatus();

            if ($pipelineStatus['status'] === 'error') {
                return [
                    'status' => self::STATUS_CRITICAL,
                    'message' => 'Data pipeline has errors',
                    'details' => $pipelineStatus,
                ];
            }

            if ($pipelineStatus['status'] === 'degraded') {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'Data pipeline is degraded',
                    'details' => $pipelineStatus,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'Data pipeline healthy',
                'details' => $pipelineStatus,
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'Data pipeline check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check API gateway health
     */
    private function checkApiGatewayHealth(): array
    {
        try {
            $requestRate = $this->getApiRequestRate();
            $errorRate = $this->getApiErrorRate();
            $avgLatency = $this->getApiAvgLatency();

            if ($errorRate > ($this->alertConfig['thresholds']['error_rate'] ?? 5)) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'High API error rate',
                    'error_rate_percent' => $errorRate,
                    'requests_per_min' => $requestRate,
                    'avg_latency_ms' => $avgLatency,
                ];
            }

            return [
                'status' => self::STATUS_HEALTHY,
                'message' => 'API gateway healthy',
                'error_rate_percent' => $errorRate,
                'requests_per_min' => $requestRate,
                'avg_latency_ms' => $avgLatency,
            ];

        } catch (Exception $e) {
            return [
                'status' => self::STATUS_CRITICAL,
                'message' => 'API gateway check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get event metrics for monitoring
     */
    private function getEventMetrics(Carbon $startTime): array
    {
        try {
            $tenantId = $this->tenantContext->getCurrentTenantId();

            // Count events in timeframe
            $eventCount = AnalyticsEvent::byTenant($tenantId)
                ->where('created_at', '>=', $startTime)
                ->count();

            // Get event type breakdown
            $byType = AnalyticsEvent::byTenant($tenantId)
                ->where('created_at', '>=', $startTime)
                ->select('event_name', DB::raw('COUNT(*) as count'))
                ->groupBy('event_name')
                ->pluck('count', 'event_name')
                ->toArray();

            return [
                'total_events' => $eventCount,
                'by_type' => $byType,
                'events_per_minute' => $eventCount > 0 ? round($eventCount / max(1, now()->diffInMinutes($startTime)), 2) : 0,
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'total_events' => 0,
                'by_type' => [],
            ];
        }
    }

    /**
     * Get query performance metrics
     */
    private function getQueryMetrics(Carbon $startTime): array
    {
        try {
            // In production, this would query actual query log data
            return [
                'total_queries' => 0,
                'avg_response_time_ms' => 0,
                'p95_response_time_ms' => 0,
                'p99_response_time_ms' => 0,
                'slow_queries' => 0,
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get data processing metrics
     */
    private function getProcessingMetrics(Carbon $startTime): array
    {
        try {
            return [
                'records_processed' => 0,
                'processing_time_avg_ms' => 0,
                'failed_records' => 0,
                'throughput_per_sec' => 0,
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache performance metrics
     */
    private function getCacheMetrics(Carbon $startTime): array
    {
        try {
            return [
                'hits' => 0,
                'misses' => 0,
                'hit_ratio' => 0,
                'memory_usage_mb' => 0,
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get API metrics
     */
    private function getApiMetrics(Carbon $startTime): array
    {
        try {
            return [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'error_rate_percent' => 0,
                'avg_latency_ms' => 0,
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate overall system load
     */
    private function calculateSystemLoad(array $metrics): array
    {
        $load = 0;
        $factors = [];

        // Event processing load
        if (($metrics['events']['events_per_minute'] ?? 0) > 1000) {
            $load += 30;
            $factors['event_volume'] = 'high';
        } elseif (($metrics['events']['events_per_minute'] ?? 0) > 100) {
            $load += 15;
            $factors['event_volume'] = 'medium';
        }

        // Query load
        if (($metrics['queries']['avg_response_time_ms'] ?? 0) > 3000) {
            $load += 30;
            $factors['query_performance'] = 'slow';
        } elseif (($metrics['queries']['avg_response_time_ms'] ?? 0) > 1000) {
            $load += 15;
            $factors['query_performance'] = 'moderate';
        }

        // Cache efficiency
        if (($metrics['cache']['hit_ratio'] ?? 100) < 70) {
            $load += 20;
            $factors['cache_efficiency'] = 'low';
        }

        return [
            'load_percentage' => min(100, $load),
            'level' => match (true) {
                $load < 30 => 'low',
                $load < 60 => 'medium',
                $load < 80 => 'high',
                default => 'critical',
            },
            'factors' => $factors,
        ];
    }

    /**
     * Get baseline metrics for anomaly detection
     */
    private function getBaselineMetrics(): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = $this->buildCacheKey('baseline_metrics', now()->format('Y-m-d-H'));

        return Cache::remember($cacheKey, self::CACHE_TTL_LONG, function () {
            return [
                'events' => [
                    'events_per_minute' => 100,
                    'by_type' => [],
                ],
                'queries' => [
                    'avg_response_time_ms' => 500,
                    'slow_queries' => 5,
                ],
                'cache' => [
                    'hit_ratio' => 85,
                ],
                'api' => [
                    'error_rate_percent' => 1,
                    'avg_latency_ms' => 200,
                ],
            ];
        });
    }

    /**
     * Detect event anomalies
     */
    private function detectEventAnomaly(array $current, array $baseline): ?array
    {
        if (empty($current) || empty($baseline)) {
            return null;
        }

        $currentRate = $current['events_per_minute'] ?? 0;
        $baselineRate = $baseline['events_per_minute'] ?? 100;

        // Detect sudden drops
        if ($currentRate < $baselineRate * 0.5 && $currentRate < 10) {
            return [
                'type' => 'event_drop',
                'severity' => self::SEVERITY_CRITICAL,
                'message' => 'Significant drop in event processing rate',
                'details' => [
                    'current_rate' => $currentRate,
                    'baseline_rate' => $baselineRate,
                    'drop_percentage' => round((1 - $currentRate / $baselineRate) * 100, 2),
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        // Detect unusual spikes
        if ($currentRate > $baselineRate * 3) {
            return [
                'type' => 'event_spike',
                'severity' => self::SEVERITY_WARNING,
                'message' => 'Unusual spike in event processing rate',
                'details' => [
                    'current_rate' => $currentRate,
                    'baseline_rate' => $baselineRate,
                    'increase_factor' => round($currentRate / $baselineRate, 2),
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Detect query performance anomalies
     */
    private function detectQueryAnomaly(array $current, array $baseline): ?array
    {
        if (empty($current) || empty($baseline)) {
            return null;
        }

        $currentAvg = $current['avg_response_time_ms'] ?? 0;
        $baselineAvg = $baseline['avg_response_time_ms'] ?? 500;

        if ($currentAvg > $baselineAvg * 2) {
            return [
                'type' => 'query_slowdown',
                'severity' => self::SEVERITY_WARNING,
                'message' => 'Query response time has increased significantly',
                'details' => [
                    'current_avg_ms' => $currentAvg,
                    'baseline_avg_ms' => $baselineAvg,
                    'increase_factor' => round($currentAvg / $baselineAvg, 2),
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Detect cache anomalies
     */
    private function detectCacheAnomaly(array $current, array $baseline): ?array
    {
        if (empty($current) || empty($baseline)) {
            return null;
        }

        $currentRatio = $current['hit_ratio'] ?? 0;
        $baselineRatio = $baseline['hit_ratio'] ?? 85;

        if ($currentRatio < 50) {
            return [
                'type' => 'cache_degradation',
                'severity' => self::SEVERITY_CRITICAL,
                'message' => 'Cache hit ratio has dropped significantly',
                'details' => [
                    'current_hit_ratio' => $currentRatio,
                    'baseline_hit_ratio' => $baselineRatio,
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        if ($currentRatio < 70) {
            return [
                'type' => 'cache_degradation',
                'severity' => self::SEVERITY_WARNING,
                'message' => 'Cache hit ratio is below normal',
                'details' => [
                    'current_hit_ratio' => $currentRatio,
                    'baseline_hit_ratio' => $baselineRatio,
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Detect API anomalies
     */
    private function detectApiAnomaly(array $current, array $baseline): ?array
    {
        if (empty($current) || empty($baseline)) {
            return null;
        }

        $currentError = $current['error_rate_percent'] ?? 0;
        $baselineError = $baseline['error_rate_percent'] ?? 1;

        if ($currentError > 10) {
            return [
                'type' => 'high_error_rate',
                'severity' => self::SEVERITY_CRITICAL,
                'message' => 'API error rate is critically high',
                'details' => [
                    'current_error_rate' => $currentError,
                    'baseline_error_rate' => $baselineError,
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        if ($currentError > 5) {
            return [
                'type' => 'high_error_rate',
                'severity' => self::SEVERITY_WARNING,
                'message' => 'API error rate is elevated',
                'details' => [
                    'current_error_rate' => $currentError,
                    'baseline_error_rate' => $baselineError,
                ],
                'detected_at' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    /**
     * Calculate severity distribution
     */
    private function calculateSeverityDistribution(array $anomalies): array
    {
        $distribution = [
            self::SEVERITY_INFO => 0,
            self::SEVERITY_WARNING => 0,
            self::SEVERITY_CRITICAL => 0,
        ];

        foreach ($anomalies as $anomaly) {
            $severity = $anomaly['severity'] ?? self::SEVERITY_INFO;
            $distribution[$severity]++;
        }

        return $distribution;
    }

    /**
     * Calculate dashboard summary
     */
    private function calculateDashboardSummary(array $health, array $anomalies, array $recentAlerts): array
    {
        $status = $health['status'] ?? self::STATUS_UNKNOWN;
        $anomalyCount = $anomalies['anomalies_detected'] ?? 0;
        $criticalAlerts = count(array_filter($recentAlerts['alerts'] ?? [], fn ($a) => ($a['severity'] ?? '') === self::SEVERITY_CRITICAL));

        return [
            'overall_status' => $status,
            'services_healthy' => count($health['services'] ?? []),
            'total_services' => count($this->healthCheckServices),
            'anomalies_count' => $anomalyCount,
            'critical_alerts' => $criticalAlerts,
            'issues' => count($health['issues'] ?? []),
        ];
    }

    /**
     * Determine overall system status
     */
    private function determineSystemStatus(array $health, array $anomalies): string
    {
        if (($health['status'] ?? self::STATUS_UNKNOWN) === self::STATUS_CRITICAL) {
            return 'degraded';
        }

        if (($anomalies['anomalies_detected'] ?? 0) > 0) {
            return 'monitoring';
        }

        return 'healthy';
    }

    /**
     * Build cache key with tenant isolation
     */
    private function buildCacheKey(string $type, string $suffix = ''): string
    {
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? 'global';

        return "analytics:{$tenantId}:{$type}:{$suffix}";
    }

    /**
     * Parse timeframe to minutes
     */
    private function parseTimeframe(string $timeframe): int
    {
        return match ($timeframe) {
            '15m' => 15,
            '1h' => 60,
            '6h' => 360,
            '24h' => 1440,
            '7d' => 10080,
            default => 60,
        };
    }

    /**
     * Validate alert structure
     */
    private function validateAlert(array $alert): void
    {
        if (empty($alert['message'])) {
            throw new \InvalidArgumentException('Alert message is required');
        }
    }

    /**
     * Generate unique alert ID
     */
    private function generateAlertId(): string
    {
        return 'alert_'.now()->format('YmdHis').'_'.uniqid('', true);
    }

    /**
     * Store alert in database
     */
    private function storeAlert(array $alert): void
    {
        // In production, this would save to alerts table
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = $this->buildCacheKey('alerts', 'recent');

        $alerts = Cache::get($cacheKey, []);
        $alerts[] = $alert;

        // Keep last 100 alerts
        $alerts = array_slice($alerts, -100);

        Cache::put($cacheKey, $alerts, self::CACHE_TTL_VERY_LONG);
    }

    /**
     * Store alert configuration
     */
    private function storeAlertConfig(array $config): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = $this->buildCacheKey('alert_config');

        Cache::put($cacheKey, $config, self::CACHE_TTL_VERY_LONG);
    }

    /**
     * Get stored alerts
     */
    private function getStoredAlerts(array $options = []): array
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = $this->buildCacheKey('alerts', 'recent');

        $alerts = Cache::get($cacheKey, []);

        // Filter by options
        if (isset($options['severity'])) {
            $alerts = array_filter($alerts, fn ($a) => ($a['severity'] ?? '') === $options['severity']);
        }

        if (isset($options['type'])) {
            $alerts = array_filter($alerts, fn ($a) => ($a['type'] ?? '') === $options['type']);
        }

        // Apply pagination
        $offset = $options['offset'] ?? 0;
        $limit = $options['limit'] ?? 100;

        return array_slice(array_values($alerts), $offset, $limit);
    }

    /**
     * Count stored alerts
     */
    private function countStoredAlerts(array $options = []): int
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        $cacheKey = $this->buildCacheKey('alerts', 'recent');

        $alerts = Cache::get($cacheKey, []);

        if (isset($options['severity'])) {
            $alerts = array_filter($alerts, fn ($a) => ($a['severity'] ?? '') === $options['severity']);
        }

        if (isset($options['type'])) {
            $alerts = array_filter($alerts, fn ($a) => ($a['type'] ?? '') === $options['type']);
        }

        return count($alerts);
    }

    /**
     * Send alert to specific channel
     */
    private function sendToChannel(string $channel, array $alert): void
    {
        switch ($channel) {
            case 'log':
                Log::channel('analytics_alerts')->warning($alert['message'], [
                    'alert_id' => $alert['alert_id'] ?? null,
                    'type' => $alert['type'] ?? 'unknown',
                    'severity' => $alert['severity'] ?? self::SEVERITY_INFO,
                    'tenant_id' => $alert['tenant_id'] ?? null,
                ]);
                break;

            case 'database':
                // Would insert into alerts table
                break;

            case 'slack':
                // Would send to Slack webhook
                break;

            case 'email':
                // Would send email notification
                break;
        }
    }

    /**
     * Trigger anomaly analysis after critical alert
     */
    private function triggerAnomalyAnalysis(array $alert): void
    {
        $metrics = $this->monitorMetrics();
        $anomalies = $this->detectAnomalies($metrics['metrics'] ?? []);

        if ($anomalies['anomalies_detected'] > 0) {
            Log::info('Anomaly analysis triggered by critical alert', [
                'alert_id' => $alert['alert_id'] ?? null,
                'anomalies_found' => $anomalies['anomalies_detected'],
            ]);
        }
    }

    // Stub methods for health checks that require external systems

    private function getRecentDatabaseErrors(): array
    {
        return [];
    }

    private function getEventQueueDepth(): int
    {
        return 0;
    }

    private function getEventProcessingRate(): int
    {
        return 0;
    }

    private function getEventProcessingLag(): int
    {
        return 0;
    }

    private function getAverageQueryResponseTime(): float
    {
        return 0;
    }

    private function getSlowQueryCount(): int
    {
        return 0;
    }

    private function getCacheHitRatio(): float
    {
        return 0;
    }

    private function getCacheMemoryUsage(): float
    {
        return 0;
    }

    private function getPipelineStatus(): array
    {
        return ['status' => 'healthy'];
    }

    private function getApiRequestRate(): int
    {
        return 0;
    }

    private function getApiErrorRate(): float
    {
        return 0;
    }

    private function getApiAvgLatency(): float
    {
        return 0;
    }
}
