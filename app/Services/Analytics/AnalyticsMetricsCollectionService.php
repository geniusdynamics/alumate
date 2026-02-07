<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Analytics Metrics Collection Service
 *
 * Provides comprehensive analytics metrics collection capabilities including
 * single metric collection, batch metrics, aggregation, trends analysis,
 * and metrics export. Implements proper tenant isolation for secure multi-tenant
 * operations.
 */
class AnalyticsMetricsCollectionService
{
    private const METRICS_CACHE_KEY = 'analytics_metrics';

    private const METRICS_CACHE_TTL = 3600; // 1 hour

    private const MAX_METRICS_HISTORY = 5000;

    private const METRICS_RETENTION_DAYS = 90;

    private TenantContextService $tenantContextService;

    private array $metricsConfig;

    private array $metricsStorage = [];

    /**
     * Aggregation types
     */
    public const AGGREGATION_SUM = 'sum';

    public const AGGREGATION_AVG = 'avg';

    public const AGGREGATION_MIN = 'min';

    public const AGGREGATION_MAX = 'max';

    public const AGGREGATION_COUNT = 'count';

    public const AGGREGATION_PERCENTILE = 'percentile';

    /**
     * Time grain options for trends
     */
    public const TIME_GRAIN_HOUR = 'hour';

    public const TIME_GRAIN_DAY = 'day';

    public const TIME_GRAIN_WEEK = 'week';

    public const TIME_GRAIN_MONTH = 'month';

    public const TIME_GRAIN_YEAR = 'year';

    /**
     * Export formats
     */
    public const FORMAT_JSON = 'json';

    public const FORMAT_CSV = 'csv';

    public const FORMAT_ARRAY = 'array';

    public const FORMAT_EXCEL = 'excel';

    /**
     * Metric types
     */
    public const TYPE_COUNTER = 'counter';

    public const TYPE_GAUGE = 'gauge';

    public const TYPE_HISTOGRAM = 'histogram';

    public const TYPE_SUMMARY = 'summary';

    /**
     * @param  array  $metricsConfig  Metrics configuration
     */
    public function __construct(
        TenantContextService $tenantContextService,
        array $metricsConfig = []
    ) {
        $this->tenantContextService = $tenantContextService;
        $this->metricsConfig = array_merge($this->getDefaultConfig(), $metricsConfig);
    }

    /**
     * Collect a single analytics metric
     *
     * @param  array  $metric  Metric data containing name, value, dimensions, etc.
     * @return array Collected metric record
     */
    public function collectMetric(array $metric): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $metricRecord = [
            'id' => uniqid('metric_', true),
            'tenant_id' => $tenantId,
            'name' => $metric['name'] ?? 'unknown',
            'value' => $metric['value'] ?? 0,
            'type' => $metric['type'] ?? self::TYPE_GAUGE,
            'dimensions' => $metric['dimensions'] ?? [],
            'unit' => $metric['unit'] ?? 'count',
            'source' => $metric['source'] ?? 'unknown',
            'description' => $metric['description'] ?? null,
            'tags' => $metric['tags'] ?? [],
            'timestamp' => now()->toIso8601String(),
            'collected_at' => now()->toIso8601String(),
        ];

        $this->storeMetric($metricRecord);

        Log::info("Analytics Metric Collected: {$metricRecord['name']}", [
            'metric_id' => $metricRecord['id'],
            'tenant_id' => $tenantId,
            'name' => $metricRecord['name'],
            'value' => $metricRecord['value'],
            'unit' => $metricRecord['unit'],
        ]);

        return $metricRecord;
    }

    /**
     * Collect multiple metrics in batch
     *
     * @param  array  $metrics  Array of metric data to collect
     * @return array Collection result with success count and records
     */
    public function collectBatchMetrics(array $metrics): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $result = [
            'success' => true,
            'total' => count($metrics),
            'collected' => 0,
            'failed' => 0,
            'records' => [],
            'errors' => [],
            'collected_at' => now()->toIso8601String(),
        ];

        foreach ($metrics as $index => $metric) {
            try {
                $record = $this->collectMetric($metric);
                $result['records'][] = $record;
                $result['collected']++;
            } catch (Exception $e) {
                $result['failed']++;
                $result['errors'][] = [
                    'index' => $index,
                    'metric' => $metric['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                $result['success'] = false;
            }
        }

        Log::info('Batch metrics collection completed', [
            'tenant_id' => $tenantId,
            'total' => $result['total'],
            'collected' => $result['collected'],
            'failed' => $result['failed'],
        ]);

        return $result;
    }

    /**
     * Aggregate metrics based on specified aggregation type
     *
     * @param  array  $metrics  Array of metrics to aggregate
     * @param  string  $aggregation  Aggregation type (sum, avg, min, max, count, percentile)
     * @param  string|null  $percentileValue  Percentile value for percentile aggregation
     * @return array Aggregated metric result
     */
    public function aggregateMetrics(
        array $metrics,
        string $aggregation,
        ?string $percentileValue = null
    ): array {
        if (empty($metrics)) {
            return [
                'aggregation' => $aggregation,
                'value' => 0,
                'count' => 0,
                'min' => null,
                'max' => null,
                'avg' => null,
            ];
        }

        $values = array_column($metrics, 'value');
        $count = count($values);

        $result = [
            'aggregation' => $aggregation,
            'count' => $count,
            'min' => min($values),
            'max' => max($values),
            'sum' => array_sum($values),
        ];

        switch ($aggregation) {
            case self::AGGREGATION_SUM:
                $result['value'] = $result['sum'];
                $result['avg'] = $count > 0 ? $result['sum'] / $count : 0;
                break;

            case self::AGGREGATION_AVG:
                $result['value'] = $count > 0 ? array_sum($values) / $count : 0;
                $result['avg'] = $result['value'];
                break;

            case self::AGGREGATION_MIN:
                $result['value'] = $result['min'];
                break;

            case self::AGGREGATION_MAX:
                $result['value'] = $result['max'];
                break;

            case self::AGGREGATION_COUNT:
                $result['value'] = $count;
                break;

            case self::AGGREGATION_PERCENTILE:
                $percentile = $percentileValue ? (float) $percentileValue : 95.0;
                sort($values);
                $index = floor($percentile / 100 * ($count - 1));
                $result['value'] = $values[(int) $index];
                $result['percentile'] = $percentile;
                break;

            default:
                $result['value'] = $count > 0 ? array_sum($values) / $count : 0;
                $result['avg'] = $result['value'];
        }

        return $result;
    }

    /**
     * Get metrics with filters
     *
     * @param  array  $filters  Filters to apply (name, dimensions, date range, etc.)
     * @param  int  $limit  Maximum number of metrics to return
     * @param  int  $offset  Offset for pagination
     * @param  string|null  $orderBy  Field to order by
     * @param  string  $orderDir  Order direction (asc/desc)
     * @return array Filtered metrics with pagination info
     */
    public function getMetrics(
        array $filters = [],
        int $limit = 100,
        int $offset = 0,
        ?string $orderBy = 'timestamp',
        string $orderDir = 'desc'
    ): array {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $metrics = $this->getMetricsFromStorage();

        // Filter by tenant
        $metrics = array_filter($metrics, function ($metric) use ($tenantId) {
            return ($metric['tenant_id'] ?? null) === $tenantId;
        });

        // Apply filters
        if (isset($filters['name'])) {
            $metrics = array_filter($metrics, function ($metric) use ($filters) {
                return stripos($metric['name'] ?? '', $filters['name']) !== false;
            });
        }

        if (isset($filters['type'])) {
            $metrics = array_filter($metrics, function ($metric) use ($filters) {
                return ($metric['type'] ?? null) === $filters['type'];
            });
        }

        if (isset($filters['source'])) {
            $metrics = array_filter($metrics, function ($metric) use ($filters) {
                return ($metric['source'] ?? null) === $filters['source'];
            });
        }

        if (isset($filters['since'])) {
            $since = strtotime($filters['since']);
            $metrics = array_filter($metrics, function ($metric) use ($since) {
                $timestamp = strtotime($metric['timestamp'] ?? 0);

                return $timestamp >= $since;
            });
        }

        if (isset($filters['until'])) {
            $until = strtotime($filters['until']);
            $metrics = array_filter($metrics, function ($metric) use ($until) {
                $timestamp = strtotime($metric['timestamp'] ?? 0);

                return $timestamp <= $until;
            });
        }

        if (isset($filters['dimensions']) && is_array($filters['dimensions'])) {
            $metrics = array_filter($metrics, function ($metric) use ($filters) {
                foreach ($filters['dimensions'] as $key => $value) {
                    if (($metric['dimensions'][$key] ?? null) !== $value) {
                        return false;
                    }
                }

                return true;
            });
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $metrics = array_filter($metrics, function ($metric) use ($filters) {
                foreach ($filters['tags'] as $tag) {
                    if (! in_array($tag, $metric['tags'] ?? [])) {
                        return false;
                    }
                }

                return true;
            });
        }

        // Sort by specified field
        usort($metrics, function ($a, $b) use ($orderBy, $orderDir) {
            $valueA = $a[$orderBy] ?? 0;
            $valueB = $b[$orderBy] ?? 0;

            if ($orderDir === 'asc') {
                return $valueA <=> $valueB;
            }

            return $valueB <=> $valueA;
        });

        // Paginate
        $total = count($metrics);
        $metrics = array_slice($metrics, $offset, $limit);

        return [
            'metrics' => array_values($metrics),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total,
        ];
    }

    /**
     * Get a metric by ID
     *
     * @param  string  $metricId  Metric ID to retrieve
     * @return array|null Metric record or null if not found
     */
    public function getMetricById(string $metricId): ?array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();
        $metrics = $this->getMetricsFromStorage();

        foreach ($metrics as $metric) {
            if (($metric['id'] ?? null) === $metricId && ($metric['tenant_id'] ?? null) === $tenantId) {
                return $metric;
            }
        }

        return null;
    }

    /**
     * Get metric trends over a date range
     *
     * @param  string  $metricName  Name of the metric to analyze
     * @param  array  $dateRange  Date range with 'start' and 'end' keys
     * @param  string  $timeGrain  Time grain for grouping (hour, day, week, month, year)
     * @return array Trend data with time series
     */
    public function getMetricTrends(
        string $metricName,
        array $dateRange,
        string $timeGrain = self::TIME_GRAIN_DAY
    ): array {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $filters = [
            'name' => $metricName,
            'since' => $dateRange['start'] ?? now()->subDay()->toIso8601String(),
            'until' => $dateRange['end'] ?? now()->toIso8601String(),
        ];

        $result = $this->getMetrics($filters, self::MAX_METRICS_HISTORY, 0, 'timestamp', 'asc');

        $metrics = array_filter($result['metrics'], function ($metric) use ($tenantId) {
            return ($metric['tenant_id'] ?? null) === $tenantId;
        });

        // Group by time grain
        $groupedMetrics = [];
        foreach ($metrics as $metric) {
            $timestamp = strtotime($metric['timestamp'] ?? 0);
            $groupKey = $this->getTimeGroupKey($timestamp, $timeGrain);

            if (! isset($groupedMetrics[$groupKey])) {
                $groupedMetrics[$groupKey] = [
                    'period' => $groupKey,
                    'start_time' => $this->getPeriodStart($timestamp, $timeGrain),
                    'end_time' => $this->getPeriodEnd($timestamp, $timeGrain),
                    'values' => [],
                    'count' => 0,
                    'sum' => 0,
                ];
            }

            $groupedMetrics[$groupKey]['values'][] = $metric['value'];
            $groupedMetrics[$groupKey]['count']++;
            $groupedMetrics[$groupKey]['sum'] += $metric['value'];
        }

        // Calculate aggregates for each period
        $trends = [];
        foreach ($groupedMetrics as $groupKey => $data) {
            $values = $data['values'];
            $trends[] = [
                'period' => $groupKey,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'count' => $data['count'],
                'sum' => $data['sum'],
                'avg' => $data['count'] > 0 ? $data['sum'] / $data['count'] : 0,
                'min' => min($values),
                'max' => max($values),
            ];
        }

        // Sort by period
        usort($trends, function ($a, $b) {
            return strcmp($a['period'], $b['period']);
        });

        return [
            'metric_name' => $metricName,
            'date_range' => $dateRange,
            'time_grain' => $timeGrain,
            'total_data_points' => count($metrics),
            'trends' => $trends,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate a metric based on parameters
     *
     * @param  string  $metricName  Name of the metric to calculate
     * @param  array  $parameters  Parameters for calculation
     * @return array Calculated metric result
     */
    public function calculateMetric(string $metricName, array $parameters = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $baseFilters = [
            'name' => $metricName,
            'since' => $parameters['since'] ?? now()->subDay()->toIso8601String(),
            'until' => $parameters['until'] ?? now()->toIso8601String(),
        ];

        if (isset($parameters['dimensions'])) {
            $baseFilters['dimensions'] = $parameters['dimensions'];
        }

        $result = $this->getMetrics($baseFilters, self::MAX_METRICS_HISTORY, 0, 'timestamp', 'asc');

        $metrics = array_filter($result['metrics'], function ($metric) use ($tenantId) {
            return ($metric['tenant_id'] ?? null) === $tenantId;
        });

        $aggregation = $parameters['aggregation'] ?? self::AGGREGATION_AVG;
        $percentile = $parameters['percentile'] ?? null;

        $aggregatedResult = $this->aggregateMetrics($metrics, $aggregation, $percentile);

        return [
            'metric_name' => $metricName,
            'calculation_type' => 'aggregation',
            'aggregation' => $aggregation,
            'period' => [
                'start' => $baseFilters['since'],
                'end' => $baseFilters['until'],
            ],
            'result' => $aggregatedResult['value'],
            'statistics' => $aggregatedResult,
            'data_points' => count($metrics),
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Export metrics with filters
     *
     * @param  array  $filters  Filters to apply
     * @param  string  $format  Export format (json, csv, array, excel)
     * @param  int  $limit  Maximum records to export
     * @return array Exported metrics
     */
    public function exportMetrics(array $filters = [], string $format = self::FORMAT_JSON, int $limit = 10000): array
    {
        $result = $this->getMetrics($filters, $limit, 0, 'timestamp', 'desc');

        return match ($format) {
            self::FORMAT_JSON => [
                'format' => 'json',
                'data' => json_encode($result['metrics'], JSON_PRETTY_PRINT),
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
            self::FORMAT_CSV => [
                'format' => 'csv',
                'data' => $this->convertToCsv($result['metrics']),
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
            self::FORMAT_EXCEL => [
                'format' => 'excel',
                'data' => $this->convertToExcel($result['metrics']),
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
            default => [
                'format' => 'array',
                'data' => $result['metrics'],
                'count' => $result['total'],
                'exported_at' => now()->toIso8601String(),
            ],
        };
    }

    /**
     * Get metrics summary for a date range
     *
     * @param  array  $dateRange  Date range with 'start' and 'end' keys
     * @param  array|null  $metricNames  Optional list of metric names to include
     * @return array Metrics summary
     */
    public function getMetricsSummary(array $dateRange, ?array $metricNames = null): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $summary = [
            'tenant_id' => $tenantId,
            'date_range' => $dateRange,
            'generated_at' => now()->toIso8601String(),
            'total_metrics' => 0,
            'by_name' => [],
            'by_type' => [],
            'by_source' => [],
            'top_metrics' => [],
            'trend_summary' => [],
        ];

        $filters = [];
        if (isset($dateRange['start'])) {
            $filters['since'] = $dateRange['start'];
        }
        if (isset($dateRange['end'])) {
            $filters['until'] = $dateRange['end'];
        }
        if ($metricNames !== null) {
            // Get metrics for each name
            $allMetrics = [];
            foreach ($metricNames as $name) {
                $filters['name'] = $name;
                $result = $this->getMetrics($filters, self::MAX_METRICS_HISTORY, 0, 'timestamp', 'desc');
                $allMetrics = array_merge($allMetrics, $result['metrics']);
            }
            $metrics = $allMetrics;
        } else {
            $result = $this->getMetrics($filters, self::MAX_METRICS_HISTORY, 0, 'timestamp', 'desc');
            $metrics = $result['metrics'];
        }

        // Filter by tenant
        $metrics = array_filter($metrics, function ($metric) use ($tenantId) {
            return ($metric['tenant_id'] ?? null) === $tenantId;
        });

        // Calculate summary statistics
        foreach ($metrics as $metric) {
            $summary['total_metrics']++;

            // By name
            $name = $metric['name'] ?? 'unknown';
            if (! isset($summary['by_name'][$name])) {
                $summary['by_name'][$name] = [
                    'name' => $name,
                    'count' => 0,
                    'total_value' => 0,
                    'values' => [],
                ];
            }
            $summary['by_name'][$name]['count']++;
            $summary['by_name'][$name]['total_value'] += $metric['value'] ?? 0;
            $summary['by_name'][$name]['values'][] = $metric['value'] ?? 0;

            // By type
            $type = $metric['type'] ?? self::TYPE_GAUGE;
            $summary['by_type'][$type] = ($summary['by_type'][$type] ?? 0) + 1;

            // By source
            $source = $metric['source'] ?? 'unknown';
            $summary['by_source'][$source] = ($summary['by_source'][$source] ?? 0) + 1;
        }

        // Calculate averages and prepare top metrics
        foreach ($summary['by_name'] as $name => $data) {
            $values = $data['values'];
            $summary['by_name'][$name]['avg_value'] = $data['count'] > 0 ? $data['total_value'] / $data['count'] : 0;
            unset($summary['by_name'][$name]['values']);

            $summary['top_metrics'][] = [
                'name' => $name,
                'count' => $data['count'],
                'total_value' => $data['total_value'],
                'avg_value' => $summary['by_name'][$name]['avg_value'],
            ];
        }

        // Sort top metrics by count
        usort($summary['top_metrics'], fn ($a, $b) => $b['count'] <=> $a['count']);
        $summary['top_metrics'] = array_slice($summary['top_metrics'], 0, 10);

        // Sort by name, type, and source
        arsort($summary['by_name']);
        arsort($summary['by_type']);
        arsort($summary['by_source']);

        return $summary;
    }

    /**
     * Configure metrics collection settings
     *
     * @param  array  $config  Configuration options
     * @return array Updated configuration
     */
    public function configureMetrics(array $config): array
    {
        $this->metricsConfig = array_merge($this->metricsConfig, $config);

        // Apply configuration changes
        $this->applyConfiguration($config);

        return $this->metricsConfig;
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
            'max_metrics_history' => self::MAX_METRICS_HISTORY,
            'metrics_retention_days' => self::METRICS_RETENTION_DAYS,
            'cache_ttl' => self::METRICS_CACHE_TTL,
            'auto_aggregation' => true,
            'aggregation_interval' => 300, // 5 minutes
            'export_chunk_size' => 1000,
            'enable_trends' => true,
            'trends_max_points' => 1000,
            'calculation_timeout' => 30,
        ];
    }

    /**
     * Apply configuration changes
     *
     * @param  array  $config  Configuration to apply
     */
    private function applyConfiguration(array $config): void
    {
        if (isset($config['metrics_retention_days'])) {
            Cache::put('analytics_metrics_retention', $config['metrics_retention_days'], 86400);
        }

        if (isset($config['max_metrics_history'])) {
            Cache::put('analytics_max_metrics_history', $config['max_metrics_history'], 86400);
        }

        Log::info('Analytics metrics configuration updated', [
            'config' => $this->metricsConfig,
        ]);
    }

    /**
     * Store a metric entry
     *
     * @param  array  $metric  Metric entry to store
     */
    private function storeMetric(array $metric): void
    {
        $this->metricsStorage[] = $metric;

        // Trim history if exceeding limit
        if (count($this->metricsStorage) > self::MAX_METRICS_HISTORY) {
            $this->metricsStorage = array_slice($this->metricsStorage, -self::MAX_METRICS_HISTORY);
        }

        // Update storage
        $this->updateMetricsStorage();
    }

    /**
     * Get metrics from storage
     *
     * @return array Metrics from storage
     */
    private function getMetricsFromStorage(): array
    {
        if (empty($this->metricsStorage)) {
            $this->metricsStorage = Cache::get(self::METRICS_CACHE_KEY, []);
        }

        return $this->metricsStorage;
    }

    /**
     * Update metrics storage
     */
    private function updateMetricsStorage(): void
    {
        Cache::put(self::METRICS_CACHE_KEY, $this->metricsStorage, self::METRICS_CACHE_TTL);
    }

    /**
     * Get time group key for grouping metrics
     *
     * @param  int  $timestamp  Timestamp
     * @param  string  $timeGrain  Time grain
     * @return string Group key
     */
    private function getTimeGroupKey(int $timestamp, string $timeGrain): string
    {
        $date = date('Y-m-d H:i:s', $timestamp);

        return match ($timeGrain) {
            self::TIME_GRAIN_HOUR => date('Y-m-d H:00:00', $timestamp),
            self::TIME_GRAIN_DAY => date('Y-m-d', $timestamp),
            self::TIME_GRAIN_WEEK => date('Y-m-d', strtotime('monday this week', $timestamp)),
            self::TIME_GRAIN_MONTH => date('Y-m', $timestamp),
            self::TIME_GRAIN_YEAR => date('Y', $timestamp),
            default => date('Y-m-d', $timestamp),
        };
    }

    /**
     * Get period start time
     *
     * @param  int  $timestamp  Timestamp
     * @param  string  $timeGrain  Time grain
     * @return string Period start ISO8601 string
     */
    private function getPeriodStart(int $timestamp, string $timeGrain): string
    {
        return match ($timeGrain) {
            self::TIME_GRAIN_HOUR => date('Y-m-d H:00:00', $timestamp),
            self::TIME_GRAIN_DAY => date('Y-m-d 00:00:00', $timestamp),
            self::TIME_GRAIN_WEEK => date('Y-m-d 00:00:00', strtotime('monday this week', $timestamp)),
            self::TIME_GRAIN_MONTH => date('Y-m-01 00:00:00', $timestamp),
            self::TIME_GRAIN_YEAR => date('Y-01-01 00:00:00', $timestamp),
            default => date('Y-m-d 00:00:00', $timestamp),
        };
    }

    /**
     * Get period end time
     *
     * @param  int  $timestamp  Timestamp
     * @param  string  $timeGrain  Time grain
     * @return string Period end ISO8601 string
     */
    private function getPeriodEnd(int $timestamp, string $timeGrain): string
    {
        return match ($timeGrain) {
            self::TIME_GRAIN_HOUR => date('Y-m-d H:59:59', $timestamp),
            self::TIME_GRAIN_DAY => date('Y-m-d 23:59:59', $timestamp),
            self::TIME_GRAIN_WEEK => date('Y-m-d 23:59:59', strtotime('sunday this week', $timestamp)),
            self::TIME_GRAIN_MONTH => date('Y-m-t 23:59:59', $timestamp),
            self::TIME_GRAIN_YEAR => date('Y-12-31 23:59:59', $timestamp),
            default => date('Y-m-d 23:59:59', $timestamp),
        };
    }

    /**
     * Convert metrics to CSV format
     *
     * @param  array  $metrics  Metrics to convert
     * @return string CSV formatted metrics
     */
    private function convertToCsv(array $metrics): string
    {
        if (empty($metrics)) {
            return '';
        }

        $headers = ['id', 'tenant_id', 'name', 'value', 'type', 'unit', 'source', 'timestamp'];
        $csv = implode(',', $headers)."\n";

        foreach ($metrics as $metric) {
            $row = [
                $metric['id'] ?? '',
                $metric['tenant_id'] ?? '',
                $metric['name'] ?? '',
                $metric['value'] ?? 0,
                $metric['type'] ?? '',
                $metric['unit'] ?? '',
                $metric['source'] ?? '',
                $metric['timestamp'] ?? '',
            ];

            // Escape values
            $row = array_map(function ($value) {
                if (is_string($value)) {
                    return '"'.str_replace('"', '""', $value).'"';
                }

                return $value;
            }, $row);

            $csv .= implode(',', $row)."\n";
        }

        return $csv;
    }

    /**
     * Convert metrics to Excel format (returns array for simplicity, can be extended)
     *
     * @param  array  $metrics  Metrics to convert
     * @return array Excel-ready data structure
     */
    private function convertToExcel(array $metrics): array
    {
        if (empty($metrics)) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = ['id', 'tenant_id', 'name', 'value', 'type', 'unit', 'source', 'timestamp'];
        $rows = [];

        foreach ($metrics as $metric) {
            $rows[] = [
                $metric['id'] ?? '',
                $metric['tenant_id'] ?? '',
                $metric['name'] ?? '',
                $metric['value'] ?? 0,
                $metric['type'] ?? '',
                $metric['unit'] ?? '',
                $metric['source'] ?? '',
                $metric['timestamp'] ?? '',
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'sheet_name' => 'Analytics Metrics',
        ];
    }
}
