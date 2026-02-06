<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Throwable;
use Exception;

/**
 * Analytics Logging Service
 *
 * Provides comprehensive analytics logging capabilities including event logging,
 * query logging, metric tracking, performance monitoring, and error logging.
 * Implements structured logging with proper tenant isolation for secure multi-tenant
 * operations.
 */
class AnalyticsLoggingService
{
    private const LOG_CACHE_KEY = 'analytics_logs';
    private const LOG_CACHE_TTL = 3600; // 1 hour
    private const MAX_LOG_HISTORY = 10000;
    private const LOG_RETENTION_DAYS = 90;

    private TenantContextService $tenantContextService;
    private array $logConfig;
    private array $logStorage = [];

    /**
     * Log types
     */
    public const TYPE_EVENT = 'event';
    public const TYPE_QUERY = 'query';
    public const TYPE_METRIC = 'metric';
    public const TYPE_PERFORMANCE = 'performance';
    public const TYPE_ERROR = 'error';

    /**
     * Log severity levels
     */
    public const SEVERITY_DEBUG = 'debug';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Export formats
     */
    public const FORMAT_JSON = 'json';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_ARRAY = 'array';

    /**
     * @param TenantContextService $tenantContextService
     * @param array $logConfig Logging configuration
     */
    public function __construct(
        TenantContextService $tenantContextService,
        array $logConfig = []
    ) {
        $this->tenantContextService = $tenantContextService;
        $this->logConfig = array_merge($this->getDefaultConfig(), $logConfig);
    }

    /**
     * Log an analytics event
     *
     * @param array $event Event data containing type, name, metadata, etc.
     * @return array Logged event record
     */
    public function logEvent(array $event): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $eventRecord = [
            'id' => uniqid('analytics_event_', true),
            'tenant_id' => $tenantId,
            'type' => self::TYPE_EVENT,
            'name' => $event['name'] ?? 'unknown',
            'category' => $event['category'] ?? 'general',
            'data' => $event['data'] ?? [],
            'user_id' => $event['user_id'] ?? null,
            'session_id' => $event['session_id'] ?? null,
            'metadata' => $event['metadata'] ?? [],
            'timestamp' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storeLog($eventRecord);

        Log::info("Analytics Event: {$eventRecord['name']}", [
            'event_id' => $eventRecord['id'],
            'tenant_id' => $tenantId,
            'category' => $eventRecord['category'],
            'user_id' => $eventRecord['user_id'],
        ]);

        return $eventRecord;
    }

    /**
     * Log an analytics query
     *
     * @param array $query Query data containing query string, parameters, execution time, etc.
     * @return array Logged query record
     */
    public function logQuery(array $query): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $queryRecord = [
            'id' => uniqid('analytics_query_', true),
            'tenant_id' => $tenantId,
            'type' => self::TYPE_QUERY,
            'query' => $query['query'] ?? '',
            'parameters' => $query['parameters'] ?? [],
            'execution_time_ms' => $query['execution_time_ms'] ?? 0,
            'result_count' => $query['result_count'] ?? 0,
            'source' => $query['source'] ?? 'unknown',
            'user_id' => $query['user_id'] ?? null,
            'metadata' => $query['metadata'] ?? [],
            'success' => $query['success'] ?? true,
            'timestamp' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storeLog($queryRecord);

        // Log slow queries as warnings
        $executionTime = $queryRecord['execution_time_ms'];
        $logLevel = match (true) {
            $executionTime > 1000 => 'warning',
            $executionTime > 500 => 'info',
            default => 'debug',
        };

        Log::log($logLevel, "Analytics Query: {$queryRecord['source']}", [
            'query_id' => $queryRecord['id'],
            'tenant_id' => $tenantId,
            'execution_time_ms' => $executionTime,
            'result_count' => $queryRecord['result_count'],
        ]);

        return $queryRecord;
    }

    /**
     * Log an analytics metric
     *
     * @param array $metric Metric data containing name, value, dimensions, etc.
     * @return array Logged metric record
     */
    public function logMetric(array $metric): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $metricRecord = [
            'id' => uniqid('analytics_metric_', true),
            'tenant_id' => $tenantId,
            'type' => self::TYPE_METRIC,
            'name' => $metric['name'] ?? 'unknown',
            'value' => $metric['value'] ?? 0,
            'previous_value' => $metric['previous_value'] ?? null,
            'change_percentage' => $metric['change_percentage'] ?? null,
            'dimensions' => $metric['dimensions'] ?? [],
            'unit' => $metric['unit'] ?? 'count',
            'source' => $metric['source'] ?? 'unknown',
            'timestamp' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storeLog($metricRecord);

        Log::info("Analytics Metric: {$metricRecord['name']}", [
            'metric_id' => $metricRecord['id'],
            'tenant_id' => $tenantId,
            'name' => $metricRecord['name'],
            'value' => $metricRecord['value'],
            'unit' => $metricRecord['unit'],
        ]);

        return $metricRecord;
    }

    /**
     * Log performance data
     *
     * @param array $performance Performance data containing operation, duration, memory usage, etc.
     * @return array Logged performance record
     */
    public function logPerformance(array $performance): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $performanceRecord = [
            'id' => uniqid('analytics_perf_', true),
            'tenant_id' => $tenantId,
            'type' => self::TYPE_PERFORMANCE,
            'operation' => $performance['operation'] ?? 'unknown',
            'duration_ms' => $performance['duration_ms'] ?? 0,
            'memory_usage_mb' => $performance['memory_usage_mb'] ?? 0,
            'peak_memory_mb' => $performance['peak_memory_mb'] ?? 0,
            'cpu_usage' => $performance['cpu_usage'] ?? 0,
            'query_count' => $performance['query_count'] ?? 0,
            'cache_hits' => $performance['cache_hits'] ?? 0,
            'cache_misses' => $performance['cache_misses'] ?? 0,
            'metadata' => $performance['metadata'] ?? [],
            'timestamp' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storeLog($performanceRecord);

        // Log performance issues as warnings
        $duration = $performanceRecord['duration_ms'];
        $severity = match (true) {
            $duration > 5000 => self::SEVERITY_CRITICAL,
            $duration > 2000 => self::SEVERITY_ERROR,
            $duration > 1000 => self::SEVERITY_WARNING,
            default => self::SEVERITY_DEBUG,
        };

        Log::log($severity, "Analytics Performance: {$performanceRecord['operation']}", [
            'performance_id' => $performanceRecord['id'],
            'tenant_id' => $tenantId,
            'duration_ms' => $duration,
            'memory_usage_mb' => $performanceRecord['memory_usage_mb'],
        ]);

        return $performanceRecord;
    }

    /**
     * Log an analytics error
     *
     * @param array $error Error data containing message, code, stack trace, etc.
     * @return array Logged error record
     */
    public function logError(array $error): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $errorRecord = [
            'id' => uniqid('analytics_log_error_', true),
            'tenant_id' => $tenantId,
            'type' => self::TYPE_ERROR,
            'message' => $error['message'] ?? 'Unknown error',
            'code' => $error['code'] ?? 0,
            'file' => $error['file'] ?? null,
            'line' => $error['line'] ?? null,
            'severity' => $error['severity'] ?? self::SEVERITY_ERROR,
            'category' => $error['category'] ?? 'unknown',
            'context' => $error['context'] ?? [],
            'trace' => $this->sanitizeTrace($error['trace'] ?? ''),
            'timestamp' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storeLog($errorRecord);

        // Log based on severity
        match ($errorRecord['severity']) {
            self::SEVERITY_CRITICAL => Log::critical("Analytics Error: {$errorRecord['message']}", $errorRecord),
            self::SEVERITY_ERROR => Log::error("Analytics Error: {$errorRecord['message']}", $errorRecord),
            self::SEVERITY_WARNING => Log::warning("Analytics Error: {$errorRecord['message']}", $errorRecord),
            default => Log::info("Analytics Error: {$errorRecord['message']}", $errorRecord),
        };

        return $errorRecord;
    }

    /**
     * Get logs with filters
     *
     * @param array $filters Filters to apply (type, severity, date range, etc.)
     * @param int $limit Maximum number of logs to return
     * @param int $offset Offset for pagination
     * @return array Filtered logs
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $logs = $this->getLogsFromStorage();

        // Filter by tenant
        $logs = array_filter($logs, function ($log) use ($tenantId) {
            return ($log['tenant_id'] ?? null) === $tenantId;
        });

        // Apply filters
        if (isset($filters['type'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['type'] ?? null) === $filters['type'];
            });
        }

        if (isset($filters['severity'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['severity'] ?? self::SEVERITY_INFO) === $filters['severity'];
            });
        }

        if (isset($filters['category'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['category'] ?? null) === $filters['category'];
            });
        }

        if (isset($filters['name'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return stripos($log['name'] ?? '', $filters['name']) !== false;
            });
        }

        if (isset($filters['since'])) {
            $since = strtotime($filters['since']);
            $logs = array_filter($logs, function ($log) use ($since) {
                $timestamp = strtotime($log['timestamp'] ?? 0);
                return $timestamp >= $since;
            });
        }

        if (isset($filters['until'])) {
            $until = strtotime($filters['until']);
            $logs = array_filter($logs, function ($log) use ($until) {
                $timestamp = strtotime($log['timestamp'] ?? 0);
                return $timestamp <= $until;
            });
        }

        if (isset($filters['user_id'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['user_id'] ?? null) === $filters['user_id'];
            });
        }

        // Sort by timestamp descending
        usort($logs, function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? 0);
            $timeB = strtotime($b['timestamp'] ?? 0);
            return $timeB <=> $timeA;
        });

        // Paginate
        $total = count($logs);
        $logs = array_slice($logs, $offset, $limit);

        return [
            'logs' => array_values($logs),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total,
        ];
    }

    /**
     * Get a log by ID
     *
     * @param string $logId Log ID to retrieve
     * @return array|null Log record or null if not found
     */
    public function getLogById(string $logId): ?array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $logs = $this->getLogsFromStorage();

        foreach ($logs as $log) {
            if (($log['id'] ?? null) === $logId && ($log['tenant_id'] ?? null) === $tenantId) {
                return $log;
            }
        }

        return null;
    }

    /**
     * Export logs
     *
     * @param array $filters Filters to apply
     * @param string $format Export format (json, csv, array)
     * @return array Exported logs
     */
    public function exportLogs(array $filters = [], string $format = self::FORMAT_JSON): array
    {
        $result = $this->getLogs($filters, self::MAX_LOG_HISTORY, 0);

        return match ($format) {
            self::FORMAT_JSON => [
                'format' => 'json',
                'data' => json_encode($result['logs'], JSON_PRETTY_PRINT),
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
            self::FORMAT_CSV => [
                'format' => 'csv',
                'data' => $this->convertToCsv($result['logs']),
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
            default => [
                'format' => 'array',
                'data' => $result['logs'],
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
        };
    }

    /**
     * Get log summary for a date range
     *
     * @param array $dateRange Date range with 'start' and 'end' keys
     * @return array Log summary
     */
    public function getLogSummary(array $dateRange): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $summary = [
            'tenant_id' => $tenantId,
            'date_range' => $dateRange,
            'generated_at' => now()->toIso8601String(),
            'total_logs' => 0,
            'by_type' => [],
            'by_severity' => [],
            'by_category' => [],
            'top_events' => [],
            'top_errors' => [],
            'performance_stats' => [],
            'query_stats' => [],
        ];

        $logs = $this->getLogsFromStorage();

        // Filter by tenant and date range
        $logs = array_filter($logs, function ($log) use ($tenantId, $dateRange) {
            if (($log['tenant_id'] ?? null) !== $tenantId) {
                return false;
            }

            $timestamp = strtotime($log['timestamp'] ?? 0);
            $start = strtotime($dateRange['start'] ?? 'now');
            $end = strtotime($dateRange['end'] ?? 'now');

            return $timestamp >= $start && $timestamp <= $end;
        });

        // Calculate summary statistics
        foreach ($logs as $log) {
            $summary['total_logs']++;

            // By type
            $type = $log['type'] ?? 'unknown';
            $summary['by_type'][$type] = ($summary['by_type'][$type] ?? 0) + 1;

            // By severity
            $severity = $log['severity'] ?? self::SEVERITY_INFO;
            $summary['by_severity'][$severity] = ($summary['by_severity'][$severity] ?? 0) + 1;

            // By category
            $category = $log['category'] ?? 'general';
            $summary['by_category'][$category] = ($summary['by_category'][$category] ?? 0) + 1;

            // Track top events
            if ($type === self::TYPE_EVENT) {
                $name = $log['name'] ?? 'unknown';
                if (!isset($summary['top_events'][$name])) {
                    $summary['top_events'][$name] = [
                        'name' => $name,
                        'count' => 0,
                    ];
                }
                $summary['top_events'][$name]['count']++;
            }

            // Track errors
            if ($type === self::TYPE_ERROR) {
                $message = $log['message'] ?? 'Unknown error';
                if (!isset($summary['top_errors'][$message])) {
                    $summary['top_errors'][$message] = [
                        'message' => $message,
                        'count' => 0,
                        'severity' => $severity,
                    ];
                }
                $summary['top_errors'][$message]['count']++;
            }

            // Performance stats
            if ($type === self::TYPE_PERFORMANCE) {
                $duration = $log['duration_ms'] ?? 0;
                $summary['performance_stats'][] = $duration;
            }

            // Query stats
            if ($type === self::TYPE_QUERY) {
                $executionTime = $log['execution_time_ms'] ?? 0;
                $summary['query_stats'][] = $executionTime;
            }
        }

        // Sort and format
        arsort($summary['by_type']);
        arsort($summary['by_severity']);
        arsort($summary['by_category']);

        uasort($summary['top_events'], fn($a, $b) => $b['count'] <=> $a['count']);
        $summary['top_events'] = array_slice(array_values($summary['top_events']), 0, 10);

        uasort($summary['top_errors'], fn($a, $b) => $b['count'] <=> $a['count']);
        $summary['top_errors'] = array_slice(array_values($summary['top_errors']), 0, 10);

        // Calculate averages
        if (!empty($summary['performance_stats'])) {
            $summary['avg_performance_ms'] = round(array_sum($summary['performance_stats']) / count($summary['performance_stats']), 2);
            $summary['max_performance_ms'] = max($summary['performance_stats']);
        }

        if (!empty($summary['query_stats'])) {
            $summary['avg_query_time_ms'] = round(array_sum($summary['query_stats']) / count($summary['query_stats']), 2);
            $summary['max_query_time_ms'] = max($summary['query_stats']);
        }

        return $summary;
    }

    /**
     * Search logs with query string
     *
     * @param string $query Search query
     * @param array $filters Additional filters
     * @param int $limit Maximum results
     * @param int $offset Offset for pagination
     * @return array Search results
     */
    public function searchLogs(string $query, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $logs = $this->getLogsFromStorage();

        // Filter by tenant
        $logs = array_filter($logs, function ($log) use ($tenantId) {
            return ($log['tenant_id'] ?? null) === $tenantId;
        });

        // Apply search query
        $searchTerms = explode(' ', strtolower($query));
        $logs = array_filter($logs, function ($log) use ($searchTerms) {
            $searchableText = strtolower(json_encode($log));
            foreach ($searchTerms as $term) {
                if (str_contains($searchableText, $term)) {
                    return true;
                }
            }
            return false;
        });

        // Apply additional filters
        if (!empty($filters)) {
            $logs = $this->applyFilters($logs, $filters);
        }

        // Sort by timestamp descending
        usort($logs, function ($a, $b) {
            $timeA = strtotime($a['timestamp'] ?? 0);
            $timeB = strtotime($b['timestamp'] ?? 0);
            return $timeB <=> $timeA;
        });

        // Paginate
        $total = count($logs);
        $logs = array_slice($logs, $offset, $limit);

        return [
            'query' => $query,
            'results' => array_values($logs),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    /**
     * Clear logs based on filters
     *
     * @param array $filters Filters to determine which logs to clear
     * @return array Result of clearing operation
     */
    public function clearLogs(array $filters = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $logs = $this->getLogsFromStorage();

        $result = [
            'success' => false,
            'cleared_count' => 0,
            'message' => '',
        ];

        try {
            $logsToKeep = array_filter($logs, function ($log) use ($tenantId, $filters) {
                // Always keep other tenants' logs
                if (($log['tenant_id'] ?? null) !== $tenantId) {
                    return true;
                }

                // Check if log matches any filter criteria
                if (isset($filters['type']) && ($log['type'] ?? null) === $filters['type']) {
                    return false;
                }

                if (isset($filters['since']) && isset($filters['until'])) {
                    $timestamp = strtotime($log['timestamp'] ?? 0);
                    $start = strtotime($filters['since']);
                    $end = strtotime($filters['until']);
                    if ($timestamp >= $start && $timestamp <= $end) {
                        return false;
                    }
                }

                if (isset($filters['older_than'])) {
                    $timestamp = strtotime($log['timestamp'] ?? 0);
                    $threshold = strtotime($filters['older_than']);
                    if ($timestamp < $threshold) {
                        return false;
                    }
                }

                return true;
            });

            $clearedCount = count($logs) - count($logsToKeep);
            $this->logStorage = array_values($logsToKeep);
            $this->updateLogStorage();

            $result['success'] = true;
            $result['cleared_count'] = $clearedCount;
            $result['message'] = "Successfully cleared {$clearedCount} logs";

            Log::info('Analytics logs cleared', [
                'tenant_id' => $tenantId,
                'filters' => $filters,
                'cleared_count' => $clearedCount,
            ]);

        } catch (Exception $e) {
            $result['message'] = 'Failed to clear logs: ' . $e->getMessage();
            Log::error('Failed to clear analytics logs', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Configure logging settings
     *
     * @param array $config Configuration options
     * @return array Updated configuration
     */
    public function configureLogging(array $config): array
    {
        $this->logConfig = array_merge($this->logConfig, $config);

        // Apply configuration changes
        $this->applyConfiguration($config);

        return $this->logConfig;
    }

    // ==================== Private Helper Methods ====================

    /**
     * Get the default configuration
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_log_history' => self::MAX_LOG_HISTORY,
            'log_retention_days' => self::LOG_RETENTION_DAYS,
            'cache_ttl' => self::LOG_CACHE_TTL,
            'log_slow_queries' => true,
            'slow_query_threshold_ms' => 500,
            'log_performance' => true,
            'performance_threshold_ms' => 1000,
            'log_errors' => true,
            'error_retention_days' => 30,
            'export_chunk_size' => 1000,
        ];
    }

    /**
     * Apply configuration changes
     *
     * @param array $config Configuration to apply
     */
    private function applyConfiguration(array $config): void
    {
        if (isset($config['log_retention_days'])) {
            Cache::put('analytics_log_retention', $config['log_retention_days'], 86400);
        }

        if (isset($config['max_log_history'])) {
            Cache::put('analytics_max_log_history', $config['max_log_history'], 86400);
        }

        Log::info('Analytics logging configuration updated', [
            'config' => $this->logConfig,
        ]);
    }

    /**
     * Store a log entry
     *
     * @param array $log Log entry to store
     */
    private function storeLog(array $log): void
    {
        $this->logStorage[] = $log;

        // Trim history if exceeding limit
        if (count($this->logStorage) > self::MAX_LOG_HISTORY) {
            $this->logStorage = array_slice($this->logStorage, -self::MAX_LOG_HISTORY);
        }

        // Update storage
        $this->updateLogStorage();
    }

    /**
     * Get logs from storage
     *
     * @return array Logs from storage
     */
    private function getLogsFromStorage(): array
    {
        if (empty($this->logStorage)) {
            $this->logStorage = Cache::get(self::LOG_CACHE_KEY, []);
        }

        return $this->logStorage;
    }

    /**
     * Update log storage
     */
    private function updateLogStorage(): void
    {
        Cache::put(self::LOG_CACHE_KEY, $this->logStorage, self::LOG_CACHE_TTL);
    }

    /**
     * Apply filters to logs
     *
     * @param array $logs Logs to filter
     * @param array $filters Filters to apply
     * @return array Filtered logs
     */
    private function applyFilters(array $logs, array $filters): array
    {
        if (isset($filters['type'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['type'] ?? null) === $filters['type'];
            });
        }

        if (isset($filters['severity'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['severity'] ?? self::SEVERITY_INFO) === $filters['severity'];
            });
        }

        if (isset($filters['category'])) {
            $logs = array_filter($logs, function ($log) use ($filters) {
                return ($log['category'] ?? null) === $filters['category'];
            });
        }

        return array_values($logs);
    }

    /**
     * Sanitize trace for logging
     *
     * @param string $trace Trace to sanitize
     * @return string Sanitized trace
     */
    private function sanitizeTrace(string $trace): string
    {
        // Remove sensitive information from trace
        $sanitized = preg_replace('/password[\'"]?\s*[:=]\s*[\'"]?([^\'"}\s]+)/i', 'password=[HIDDEN]', $trace);
        $sanitized = preg_replace('/token[\'"]?\s*[:=]\s*[\'"]?([^\'"}\s]+)/i', 'token=[HIDDEN]', $sanitized ?? $trace);
        $sanitized = preg_replace('/api_key[\'"]?\s*[:=]\s*[\'"]?([^\'"}\s]+)/i', 'api_key=[HIDDEN]', $sanitized ?? $trace);

        return $sanitized ?? $trace;
    }

    /**
     * Convert logs to CSV format
     *
     * @param array $logs Logs to convert
     * @return string CSV formatted logs
     */
    private function convertToCsv(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $headers = ['id', 'tenant_id', 'type', 'name', 'message', 'severity', 'category', 'timestamp'];
        $csv = implode(',', $headers) . "\n";

        foreach ($logs as $log) {
            $row = [
                $log['id'] ?? '',
                $log['tenant_id'] ?? '',
                $log['type'] ?? '',
                $log['name'] ?? '',
                $log['message'] ?? '',
                $log['severity'] ?? '',
                $log['category'] ?? '',
                $log['timestamp'] ?? '',
            ];

            // Escape values
            $row = array_map(function ($value) {
                if (is_string($value)) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }
                return $value;
            }, $row);

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }
}
