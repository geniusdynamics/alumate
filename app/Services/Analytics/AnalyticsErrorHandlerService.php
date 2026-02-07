<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Services\TenantContextService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Analytics Error Handler Service
 *
 * Provides comprehensive error handling, logging, and recovery mechanisms
 * for analytics operations. Implements tenant isolation for secure multi-tenant
 * operations with proper error tracking and alerting.
 */
class AnalyticsErrorHandlerService
{
    private const ERROR_CACHE_KEY = 'analytics_error_stats';

    private const ERROR_CACHE_TTL = 3600; // 1 hour

    private const MAX_ERROR_HISTORY = 1000;

    private const ERROR_RETENTION_DAYS = 30;

    private TenantContextService $tenantContextService;

    private array $errorConfig;

    private array $errorHistory = [];

    private array $errorMetrics = [];

    private array $errorTrends = [];

    /**
     * Error severity levels
     */
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_LOW = 'low';

    /**
     * Error categories
     */
    public const CATEGORY_DATA_VALIDATION = 'data_validation';

    public const CATEGORY_QUERY = 'query';

    public const CATEGORY_CALCULATION = 'calculation';

    public const CATEGORY_INTEGRATION = 'integration';

    public const CATEGORY_PERFORMANCE = 'performance';

    public const CATEGORY_SECURITY = 'security';

    public const CATEGORY_UNKNOWN = 'unknown';

    /**
     * Recovery strategies
     */
    public const RECOVERY_RETRY = 'retry';

    public const RECOVERY_FALLBACK = 'fallback';

    public const RECOVERY_SKIP = 'skip';

    public const RECOVERY_ABORT = 'abort';

    /**
     * @param  array  $errorConfig  Error handling configuration
     */
    public function __construct(
        TenantContextService $tenantContextService,
        array $errorConfig = []
    ) {
        $this->tenantContextService = $tenantContextService;
        $this->errorConfig = array_merge($this->getDefaultConfig(), $errorConfig);
    }

    /**
     * Handle analytics errors
     *
     * @param  Throwable  $error  The error to handle
     * @param  array  $context  Additional context information
     * @return array Error handling result
     */
    public function handleError(Throwable $error, array $context = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        // Categorize the error
        $category = $this->categorizeError($error);
        $severity = $this->determineSeverity($error, $category);

        // Create error record
        $errorRecord = [
            'id' => uniqid('analytics_error_', true),
            'tenant_id' => $tenantId,
            'message' => $error->getMessage(),
            'code' => $error->getCode(),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'category' => $category,
            'severity' => $severity,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
            'trace' => $this->sanitizeTrace($error->getTraceAsString()),
        ];

        // Log the error
        $this->logError($errorRecord);

        // Track error metrics
        $this->trackErrorMetrics($errorRecord);

        // Add to error history
        $this->addToErrorHistory($errorRecord);

        // Attempt recovery if configured
        $recoveryResult = $this->attemptRecovery($error, $context);

        return [
            'error_id' => $errorRecord['id'],
            'handled' => true,
            'category' => $category,
            'severity' => $severity,
            'recovery' => $recoveryResult,
            'message' => $this->getUserFriendlyMessage($error, $severity),
            'timestamp' => $errorRecord['timestamp'],
        ];
    }

    /**
     * Log analytics errors
     *
     * @param  array  $errorRecord  Error record to log
     */
    public function logError(array $errorRecord): void
    {
        $tenantId = $errorRecord['tenant_id'] ?? null;
        $severity = $errorRecord['severity'] ?? self::SEVERITY_MEDIUM;
        $category = $errorRecord['category'] ?? self::CATEGORY_UNKNOWN;

        $logContext = [
            'error_id' => $errorRecord['id'],
            'tenant_id' => $tenantId,
            'category' => $category,
            'severity' => $severity,
            'file' => $errorRecord['file'],
            'line' => $errorRecord['line'],
            'context' => $errorRecord['context'] ?? [],
        ];

        // Log based on severity
        match ($severity) {
            self::SEVERITY_CRITICAL => Log::critical("Analytics Critical Error: {$errorRecord['message']}", $logContext),
            self::SEVERITY_HIGH => Log::error("Analytics High Error: {$errorRecord['message']}", $logContext),
            self::SEVERITY_MEDIUM => Log::warning("Analytics Medium Error: {$errorRecord['message']}", $logContext),
            self::SEVERITY_LOW => Log::info("Analytics Low Error: {$errorRecord['message']}", $logContext),
        };
    }

    /**
     * Recover from analytics errors
     *
     * @param  Throwable  $error  The error to recover from
     * @param  array  $context  Error context
     * @return array Recovery result
     */
    public function recoverFromError(Throwable $error, array $context = []): array
    {
        $category = $this->categorizeError($error);
        $strategy = $this->determineRecoveryStrategy($category);

        $recoveryResult = [
            'success' => false,
            'strategy' => $strategy,
            'attempts' => 0,
            'message' => '',
            'fallback_value' => null,
        ];

        switch ($strategy) {
            case self::RECOVERY_RETRY:
                $recoveryResult = $this->performRetryRecovery($error, $context);
                break;

            case self::RECOVERY_FALLBACK:
                $recoveryResult = $this->performFallbackRecovery($error, $context);
                break;

            case self::RECOVERY_SKIP:
                $recoveryResult = $this->performSkipRecovery($error, $context);
                break;

            case self::RECOVERY_ABORT:
                $recoveryResult = $this->performAbortRecovery($error, $context);
                break;
        }

        return $recoveryResult;
    }

    /**
     * Get error report for analytics
     *
     * @param  array  $filters  Filters to apply
     * @return array Error report
     */
    public function getErrorReport(array $filters = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'tenant_id' => $tenantId,
            'filters' => $filters,
            'summary' => [
                'total_errors' => 0,
                'critical_errors' => 0,
                'high_errors' => 0,
                'medium_errors' => 0,
                'low_errors' => 0,
                'resolved_errors' => 0,
                'unresolved_errors' => 0,
            ],
            'by_category' => [],
            'by_service' => [],
            'recent_errors' => [],
            'top_errors' => [],
            'recommendations' => [],
        ];

        try {
            // Get error statistics
            $stats = $this->getErrorStatistics();
            $report['summary'] = array_merge($report['summary'], $stats['summary'] ?? []);

            // Get error history filtered by criteria
            $filteredHistory = $this->filterErrorHistory($filters);
            $report['recent_errors'] = array_slice($filteredHistory, 0, 20);

            // Group errors by category
            $report['by_category'] = $this->groupErrorsByCategory($filteredHistory);

            // Group errors by service/component
            $report['by_service'] = $this->groupErrorsByService($filteredHistory);

            // Get top errors
            $report['top_errors'] = $this->getTopErrors($filteredHistory, 10);

            // Generate recommendations
            $report['recommendations'] = $this->generateRecommendations($filteredHistory);

        } catch (Exception $e) {
            Log::error('Failed to generate error report', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
        }

        return $report;
    }

    /**
     * Get error metrics for analytics
     *
     * @param  string  $period  Period for metrics (hourly, daily, weekly, monthly)
     * @return array Error metrics
     */
    public function getErrorMetrics(string $period = 'daily'): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $metrics = [
            'tenant_id' => $tenantId,
            'period' => $period,
            'generated_at' => now()->toIso8601String(),
            'total_errors' => 0,
            'error_rate' => 0,
            'by_severity' => [],
            'by_category' => [],
            'by_hour' => [],
            'mttr' => 0, // Mean Time To Recovery
            'mtta' => 0, // Mean Time To Acknowledge
            'resolution_rate' => 0,
        ];

        try {
            // Calculate time range based on period
            $timeRange = $this->getTimeRangeForPeriod($period);

            // Get cached or calculate metrics
            $cacheKey = "analytics_error_metrics_{$tenantId}_{$period}";
            $metrics = Cache::remember($cacheKey, 600, function () use ($metrics, $timeRange) {
                return $this->calculateErrorMetrics($metrics, $timeRange);
            });

        } catch (Exception $e) {
            Log::error('Failed to calculate error metrics', [
                'period' => $period,
                'error' => $e->getMessage(),
            ]);
        }

        return $metrics;
    }

    /**
     * Get error trends over time
     *
     * @param  string  $interval  Time interval (hour, day, week, month)
     * @param  int  $limit  Number of intervals to return
     * @return array Error trends
     */
    public function getErrorTrends(string $interval = 'day', int $limit = 30): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $trends = [
            'tenant_id' => $tenantId,
            'interval' => $interval,
            'generated_at' => now()->toIso8601String(),
            'data_points' => [],
            'trend_direction' => 'stable',
            'change_percentage' => 0,
            'forecast' => [],
        ];

        try {
            $timeRange = [
                'start' => now()->subDays($limit)->toIso8601String(),
                'end' => now()->toIso8601String(),
            ];

            // Get error history for the time range
            $errors = $this->getErrorHistoryForRange($timeRange);

            // Group errors by time interval
            $trends['data_points'] = $this->groupErrorsByInterval($errors, $interval, $limit);

            // Calculate trend direction
            $trends['trend_direction'] = $this->calculateTrendDirection($trends['data_points']);

            // Calculate change percentage
            $trends['change_percentage'] = $this->calculateChangePercentage($trends['data_points']);

            // Generate forecast
            $trends['forecast'] = $this->generateErrorForecast($trends['data_points'], 7);

        } catch (Exception $e) {
            Log::error('Failed to calculate error trends', [
                'interval' => $interval,
                'error' => $e->getMessage(),
            ]);
        }

        return $trends;
    }

    /**
     * Configure error handling
     *
     * @param  array  $config  Configuration options
     * @return array Updated configuration
     */
    public function configureErrorHandling(array $config): array
    {
        $this->errorConfig = array_merge($this->errorConfig, $config);

        // Apply configuration changes
        $this->applyConfiguration($config);

        return $this->errorConfig;
    }

    /**
     * Get error alerts
     *
     * @param  array  $filters  Filters for alerts
     * @return array Error alerts
     */
    public function getErrorAlerts(array $filters = []): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $alerts = [
            'tenant_id' => $tenantId,
            'generated_at' => now()->toIso8601String(),
            'active_alerts' => [],
            'resolved_alerts' => [],
            'alerts_summary' => [
                'total_active' => 0,
                'critical' => 0,
                'high' => 0,
                'medium' => 0,
                'low' => 0,
            ],
        ];

        try {
            // Get recent errors that may trigger alerts
            $recentErrors = $this->filterErrorHistory([
                'since' => now()->subHours(24)->toIso8601String(),
            ]);

            // Generate alerts based on error patterns
            $alerts['active_alerts'] = $this->generateAlertsFromErrors($recentErrors);

            // Calculate alerts summary
            foreach ($alerts['active_alerts'] as $alert) {
                $severity = $alert['severity'] ?? self::SEVERITY_LOW;
                $alerts['alerts_summary'][$severity]++;
            }
            $alerts['alerts_summary']['total_active'] = count($alerts['active_alerts']);

        } catch (Exception $e) {
            Log::error('Failed to get error alerts', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
        }

        return $alerts;
    }

    /**
     * Clear errors based on filters
     *
     * @param  array  $filters  Filters to determine which errors to clear
     * @return array Result of clearing operation
     */
    public function clearErrors(array $filters = []): array
    {
        $result = [
            'success' => false,
            'cleared_count' => 0,
            'errors' => [],
            'message' => '',
        ];

        try {
            // Get errors to clear
            $errorsToClear = $this->filterErrorHistory($filters);

            // Track cleared errors
            $clearedIds = array_column($errorsToClear, 'id');

            // Remove from history
            $this->errorHistory = array_filter($this->errorHistory, function ($error) use ($clearedIds) {
                return ! in_array($error['id'], $clearedIds);
            });

            // Clear from cache
            Cache::forget(self::ERROR_CACHE_KEY);

            $result['success'] = true;
            $result['cleared_count'] = count($errorsToClear);
            $result['message'] = "Successfully cleared {$result['cleared_count']} errors";

            Log::info('Analytics errors cleared', [
                'filters' => $filters,
                'cleared_count' => $result['cleared_count'],
            ]);

        } catch (Exception $e) {
            $result['message'] = 'Failed to clear errors: '.$e->getMessage();
            Log::error('Failed to clear analytics errors', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Get error statistics
     *
     * @return array Error statistics
     */
    public function getErrorStatistics(): array
    {
        $tenantId = $this->tenantContextService->getCurrentTenantId();

        $statistics = [
            'tenant_id' => $tenantId,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'total_errors' => 0,
                'critical_errors' => 0,
                'high_errors' => 0,
                'medium_errors' => 0,
                'low_errors' => 0,
                'resolved_errors' => 0,
                'unresolved_errors' => 0,
            ],
            'by_category' => [],
            'by_service' => [],
            'by_hour' => [],
            'top_errors' => [],
            'recovery_stats' => [],
        ];

        try {
            // Get all error history
            $allErrors = $this->errorHistory;

            // Calculate summary
            $statistics['summary']['total_errors'] = count($allErrors);

            foreach ($allErrors as $error) {
                $severity = $error['severity'] ?? self::SEVERITY_MEDIUM;
                $category = $error['category'] ?? self::CATEGORY_UNKNOWN;

                $statistics['summary'][$severity.'_errors']++;
                $statistics['summary']['unresolved_errors']++;

                // Group by category
                if (! isset($statistics['by_category'][$category])) {
                    $statistics['by_category'][$category] = 0;
                }
                $statistics['by_category'][$category]++;
            }

            // Sort by count
            arsort($statistics['by_category']);
            arsort($statistics['summary']);

            // Get top errors
            $statistics['top_errors'] = $this->getTopErrors($allErrors, 10);

        } catch (Exception $e) {
            Log::error('Failed to get error statistics', [
                'error' => $e->getMessage(),
            ]);
        }

        return $statistics;
    }

    /**
     * Get the default configuration
     *
     * @return array Default configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            'max_retries' => 3,
            'retry_delay_ms' => 1000,
            'fallback_enabled' => true,
            'alert_threshold_critical' => 1,
            'alert_threshold_high' => 5,
            'alert_threshold_medium' => 20,
            'alert_threshold_low' => 50,
            'auto_recovery_enabled' => true,
            'error_retention_days' => self::ERROR_RETENTION_DAYS,
            'notify_on_critical' => true,
            'notify_on_high' => true,
            'log_to_external' => false,
        ];
    }

    /**
     * Apply configuration changes
     *
     * @param  array  $config  Configuration to apply
     */
    private function applyConfiguration(array $config): void
    {
        // Apply cache settings
        if (isset($config['error_retention_days'])) {
            Cache::put('analytics_error_retention', $config['error_retention_days'], 86400);
        }

        // Apply retry settings
        if (isset($config['max_retries'])) {
            Cache::put('analytics_max_retries', $config['max_retries'], 86400);
        }

        Log::info('Analytics error handling configuration updated', [
            'config' => $this->errorConfig,
        ]);
    }

    /**
     * Categorize an error
     *
     * @return string Error category
     */
    private function categorizeError(Throwable $error): string
    {
        $message = strtolower($error->getMessage());
        $className = get_class($error);

        // Check for specific error types
        if ($error instanceof \Illuminate\Database\QueryException) {
            return self::CATEGORY_QUERY;
        }

        if ($error instanceof \InvalidArgumentException || $error instanceof \LengthException) {
            return self::CATEGORY_DATA_VALIDATION;
        }

        if ($error instanceof \RuntimeException || $error instanceof \UnexpectedValueException) {
            return self::CATEGORY_CALCULATION;
        }

        if (str_contains($message, 'timeout') || str_contains($message, 'memory')) {
            return self::CATEGORY_PERFORMANCE;
        }

        if (str_contains($message, 'permission') || str_contains($message, 'access')) {
            return self::CATEGORY_SECURITY;
        }

        if (str_contains($message, 'integration') || str_contains($message, 'api') || str_contains($message, 'connection')) {
            return self::CATEGORY_INTEGRATION;
        }

        return self::CATEGORY_UNKNOWN;
    }

    /**
     * Determine severity of an error
     *
     * @return string Error severity
     */
    private function determineSeverity(Throwable $error, string $category): string
    {
        // Check for critical error types
        if ($error instanceof \Error) {
            return self::SEVERITY_CRITICAL;
        }

        if ($error instanceof \OverflowException || $error instanceof \RangeException) {
            return self::SEVERITY_CRITICAL;
        }

        // Check category-based severity
        if ($category === self::CATEGORY_SECURITY) {
            return self::SEVERITY_CRITICAL;
        }

        if ($category === self::CATEGORY_PERFORMANCE) {
            return $error->getCode() === -1 ? self::SEVERITY_HIGH : self::SEVERITY_MEDIUM;
        }

        // Check message patterns
        $message = strtolower($error->getMessage());
        if (str_contains($message, 'critical') || str_contains($message, 'fatal')) {
            return self::SEVERITY_CRITICAL;
        }

        if (str_contains($message, 'failed') || str_contains($message, 'error')) {
            return self::SEVERITY_HIGH;
        }

        return self::SEVERITY_MEDIUM;
    }

    /**
     * Determine recovery strategy for an error
     *
     * @return string Recovery strategy
     */
    private function determineRecoveryStrategy(string $category): string
    {
        return match ($category) {
            self::CATEGORY_QUERY => self::RECOVERY_RETRY,
            self::CATEGORY_DATA_VALIDATION => self::RECOVERY_SKIP,
            self::CATEGORY_INTEGRATION => self::RECOVERY_RETRY,
            self::CATEGORY_PERFORMANCE => self::RECOVERY_FALLBACK,
            self::CATEGORY_SECURITY => self::RECOVERY_ABORT,
            default => $this->errorConfig['auto_recovery_enabled'] ? self::RECOVERY_RETRY : self::RECOVERY_ABORT,
        };
    }

    /**
     * Perform retry recovery
     *
     * @return array Recovery result
     */
    private function performRetryRecovery(Throwable $error, array $context): array
    {
        $maxRetries = $this->errorConfig['max_retries'] ?? 3;
        $retryDelay = $this->errorConfig['retry_delay_ms'] ?? 1000;

        $result = [
            'success' => false,
            'strategy' => self::RECOVERY_RETRY,
            'attempts' => 0,
            'max_attempts' => $maxRetries,
            'message' => 'Retry recovery not yet attempted',
        ];

        // In a real implementation, this would attempt actual retry logic
        // For now, return the plan
        $result['message'] = "Retry recovery planned with {$maxRetries} attempts, {$retryDelay}ms delay";
        $result['fallback_available'] = $this->errorConfig['fallback_enabled'];

        return $result;
    }

    /**
     * Perform fallback recovery
     *
     * @return array Recovery result
     */
    private function performFallbackRecovery(Throwable $error, array $context): array
    {
        return [
            'success' => true,
            'strategy' => self::RECOVERY_FALLBACK,
            'message' => 'Using fallback data due to error',
            'fallback_value' => $context['fallback_value'] ?? null,
        ];
    }

    /**
     * Perform skip recovery
     *
     * @return array Recovery result
     */
    private function performSkipRecovery(Throwable $error, array $context): array
    {
        return [
            'success' => true,
            'strategy' => self::RECOVERY_SKIP,
            'message' => 'Skipped invalid data due to validation error',
            'skipped_item' => $context['item_id'] ?? null,
        ];
    }

    /**
     * Perform abort recovery
     *
     * @return array Recovery result
     */
    private function performAbortRecovery(Throwable $error, array $context): array
    {
        return [
            'success' => false,
            'strategy' => self::RECOVERY_ABORT,
            'message' => 'Operation aborted due to unrecoverable error',
            'requires_manual_intervention' => true,
        ];
    }

    /**
     * Attempt to recover from an error
     *
     * @return array Recovery result
     */
    private function attemptRecovery(Throwable $error, array $context): array
    {
        $category = $this->categorizeError($error);

        if (! $this->errorConfig['auto_recovery_enabled']) {
            return [
                'attempted' => false,
                'message' => 'Auto-recovery is disabled',
            ];
        }

        return $this->recoverFromError($error, $context);
    }

    /**
     * Track error metrics
     */
    private function trackErrorMetrics(array $errorRecord): void
    {
        $timestamp = $errorRecord['timestamp'] ?? now()->toIso8601String();
        $severity = $errorRecord['severity'] ?? self::SEVERITY_MEDIUM;
        $category = $errorRecord['category'] ?? self::CATEGORY_UNKNOWN;

        // Initialize metrics structure if needed
        if (! isset($this->errorMetrics['by_severity'][$severity])) {
            $this->errorMetrics['by_severity'][$severity] = 0;
        }
        if (! isset($this->errorMetrics['by_category'][$category])) {
            $this->errorMetrics['by_category'][$category] = 0;
        }

        $this->errorMetrics['by_severity'][$severity]++;
        $this->errorMetrics['by_category'][$category]++;
        $this->errorMetrics['total_errors'] = ($this->errorMetrics['total_errors'] ?? 0) + 1;
        $this->errorMetrics['last_error_at'] = $timestamp;

        // Update cache
        Cache::put(self::ERROR_CACHE_KEY, $this->errorMetrics, self::ERROR_CACHE_TTL);
    }

    /**
     * Add error to history
     */
    private function addToErrorHistory(array $errorRecord): void
    {
        $this->errorHistory[] = $errorRecord;

        // Trim history if exceeding limit
        if (count($this->errorHistory) > self::MAX_ERROR_HISTORY) {
            $this->errorHistory = array_slice($this->errorHistory, -self::MAX_ERROR_HISTORY);
        }

        // Update trends
        $this->updateErrorTrends($errorRecord);
    }

    /**
     * Update error trends
     */
    private function updateErrorTrends(array $errorRecord): void
    {
        $timestamp = $errorRecord['timestamp'] ?? now()->toIso8601String();
        $date = date('Y-m-d', strtotime($timestamp));

        if (! isset($this->errorTrends[$date])) {
            $this->errorTrends[$date] = [
                'date' => $date,
                'total' => 0,
                'by_severity' => [],
                'by_category' => [],
            ];
        }

        $this->errorTrends[$date]['total']++;
        $severity = $errorRecord['severity'] ?? self::SEVERITY_MEDIUM;
        $category = $errorRecord['category'] ?? self::CATEGORY_UNKNOWN;

        if (! isset($this->errorTrends[$date]['by_severity'][$severity])) {
            $this->errorTrends[$date]['by_severity'][$severity] = 0;
        }
        $this->errorTrends[$date]['by_severity'][$severity]++;

        if (! isset($this->errorTrends[$date]['by_category'][$category])) {
            $this->errorTrends[$date]['by_category'][$category] = 0;
        }
        $this->errorTrends[$date]['by_category'][$category]++;
    }

    /**
     * Get user-friendly error message
     *
     * @return string User-friendly message
     */
    private function getUserFriendlyMessage(Throwable $error, string $severity): string
    {
        $isProduction = app()->environment('production');

        if ($isProduction) {
            return match ($severity) {
                self::SEVERITY_CRITICAL => 'A critical error occurred. Our team has been notified and is working on a fix.',
                self::SEVERITY_HIGH => 'An error occurred while processing your request. Please try again.',
                self::SEVERITY_MEDIUM => 'Some data could not be processed. Please refresh and try again.',
                self::SEVERITY_LOW => 'A minor issue was encountered but your session was not affected.',
            };
        }

        return $error->getMessage();
    }

    /**
     * Sanitize error trace for logging
     *
     * @return string Sanitized trace
     */
    private function sanitizeTrace(string $trace): string
    {
        // Remove sensitive information from trace
        $sanitized = preg_replace('/password[\'"]?\s*[:=]\s*[\'"]?([^\'"}\s]+)/i', 'password=[HIDDEN]', $trace);
        $sanitized = preg_replace('/token[\'"]?\s*[:=]\s*[\'"]?([^\'"}\s]+)/i', 'token=[HIDDEN]', $sanitized ?? $trace);

        return $sanitized ?? $trace;
    }

    /**
     * Filter error history based on criteria
     *
     * @return array Filtered errors
     */
    private function filterErrorHistory(array $filters): array
    {
        $errors = $this->errorHistory;

        if (isset($filters['since'])) {
            $since = strtotime($filters['since']);
            $errors = array_filter($errors, function ($error) use ($since) {
                $timestamp = strtotime($error['timestamp'] ?? 0);

                return $timestamp >= $since;
            });
        }

        if (isset($filters['until'])) {
            $until = strtotime($filters['until']);
            $errors = array_filter($errors, function ($error) use ($until) {
                $timestamp = strtotime($error['timestamp'] ?? 0);

                return $timestamp <= $until;
            });
        }

        if (isset($filters['severity'])) {
            $severity = $filters['severity'];
            $errors = array_filter($errors, function ($error) use ($severity) {
                return ($error['severity'] ?? self::SEVERITY_MEDIUM) === $severity;
            });
        }

        if (isset($filters['category'])) {
            $category = $filters['category'];
            $errors = array_filter($errors, function ($error) use ($category) {
                return ($error['category'] ?? self::CATEGORY_UNKNOWN) === $category;
            });
        }

        return array_values($errors);
    }

    /**
     * Get time range for period
     *
     * @return array Time range
     */
    private function getTimeRangeForPeriod(string $period): array
    {
        $end = now();
        $start = match ($period) {
            'hourly' => $end->copy()->subHour(),
            'daily' => $end->copy()->subDay(),
            'weekly' => $end->copy()->subWeek(),
            'monthly' => $end->copy()->subMonth(),
            default => $end->copy()->subDay(),
        };

        return [
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
        ];
    }

    /**
     * Calculate error metrics
     *
     * @return array Calculated metrics
     */
    private function calculateErrorMetrics(array $metrics, array $timeRange): array
    {
        $errors = $this->filterErrorHistory([
            'since' => $timeRange['start'],
            'until' => $timeRange['end'],
        ]);

        $metrics['total_errors'] = count($errors);

        // Group by severity
        foreach ($errors as $error) {
            $severity = $error['severity'] ?? self::SEVERITY_MEDIUM;
            $category = $error['category'] ?? self::CATEGORY_UNKNOWN;
            $hour = date('H', strtotime($error['timestamp'] ?? 'now'));

            $metrics['by_severity'][$severity] = ($metrics['by_severity'][$severity] ?? 0) + 1;
            $metrics['by_category'][$category] = ($metrics['by_category'][$category] ?? 0) + 1;
            $metrics['by_hour'][$hour] = ($metrics['by_hour'][$hour] ?? 0) + 1;
        }

        return $metrics;
    }

    /**
     * Group errors by category
     *
     * @return array Grouped errors
     */
    private function groupErrorsByCategory(array $errors): array
    {
        $grouped = [];

        foreach ($errors as $error) {
            $category = $error['category'] ?? self::CATEGORY_UNKNOWN;
            if (! isset($grouped[$category])) {
                $grouped[$category] = [
                    'category' => $category,
                    'count' => 0,
                    'errors' => [],
                ];
            }
            $grouped[$category]['count']++;
            $grouped[$category]['errors'][] = [
                'id' => $error['id'],
                'message' => $error['message'],
                'severity' => $error['severity'] ?? self::SEVERITY_MEDIUM,
                'timestamp' => $error['timestamp'],
            ];
        }

        uasort($grouped, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_values($grouped);
    }

    /**
     * Group errors by service/component
     *
     * @return array Grouped errors
     */
    private function groupErrorsByService(array $errors): array
    {
        $grouped = [];

        foreach ($errors as $error) {
            $service = $error['context']['service'] ?? 'unknown';
            if (! isset($grouped[$service])) {
                $grouped[$service] = [
                    'service' => $service,
                    'count' => 0,
                    'errors' => [],
                ];
            }
            $grouped[$service]['count']++;
            $grouped[$service]['errors'][] = [
                'id' => $error['id'],
                'message' => $error['message'],
                'severity' => $error['severity'] ?? self::SEVERITY_MEDIUM,
                'timestamp' => $error['timestamp'],
            ];
        }

        uasort($grouped, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_values($grouped);
    }

    /**
     * Get top errors by occurrence
     *
     * @return array Top errors
     */
    private function getTopErrors(array $errors, int $limit = 10): array
    {
        $messageCounts = [];

        foreach ($errors as $error) {
            $message = $error['message'] ?? 'Unknown error';
            if (! isset($messageCounts[$message])) {
                $messageCounts[$message] = [
                    'message' => $message,
                    'count' => 0,
                    'severity' => $error['severity'] ?? self::SEVERITY_MEDIUM,
                    'category' => $error['category'] ?? self::CATEGORY_UNKNOWN,
                    'last_occurrence' => $error['timestamp'],
                ];
            }
            $messageCounts[$message]['count']++;
        }

        uasort($messageCounts, fn ($a, $b) => $b['count'] <=> $a['count']);

        return array_slice(array_values($messageCounts), 0, $limit);
    }

    /**
     * Get error history for a time range
     *
     * @return array Errors in range
     */
    private function getErrorHistoryForRange(array $timeRange): array
    {
        return $this->filterErrorHistory([
            'since' => $timeRange['start'],
            'until' => $timeRange['end'],
        ]);
    }

    /**
     * Group errors by time interval
     *
     * @return array Data points
     */
    private function groupErrorsByInterval(array $errors, string $interval, int $limit): array
    {
        $dataPoints = [];
        $intervalSeconds = match ($interval) {
            'hour' => 3600,
            'day' => 86400,
            'week' => 604800,
            'month' => 2592000,
            default => 86400,
        };

        $endTime = time();
        $startTime = $endTime - ($limit * $intervalSeconds);

        for ($i = 0; $i < $limit; $i++) {
            $pointStart = $startTime + ($i * $intervalSeconds);
            $pointEnd = $pointStart + $intervalSeconds;

            $count = 0;
            foreach ($errors as $error) {
                $errorTime = strtotime($error['timestamp'] ?? 'now');
                if ($errorTime >= $pointStart && $errorTime < $pointEnd) {
                    $count++;
                }
            }

            $dataPoints[] = [
                'timestamp' => date('Y-m-d H:i:s', $pointStart),
                'count' => $count,
            ];
        }

        return $dataPoints;
    }

    /**
     * Calculate trend direction
     *
     * @return string Trend direction
     */
    private function calculateTrendDirection(array $dataPoints): string
    {
        if (count($dataPoints) < 2) {
            return 'stable';
        }

        $recentHalf = array_slice($dataPoints, -(int) ceil(count($dataPoints) / 2));
        $olderHalf = array_slice($dataPoints, 0, (int) floor(count($dataPoints) / 2));

        $recentAvg = array_sum(array_column($recentHalf, 'count')) / count($recentHalf);
        $olderAvg = array_sum(array_column($olderHalf, 'count')) / count($olderHalf);

        if ($olderAvg > 0) {
            $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;
            if ($change > 20) {
                return 'increasing';
            }
            if ($change < -20) {
                return 'decreasing';
            }
        }

        return 'stable';
    }

    /**
     * Calculate change percentage
     *
     * @return float Change percentage
     */
    private function calculateChangePercentage(array $dataPoints): float
    {
        if (count($dataPoints) < 2) {
            return 0;
        }

        $firstHalf = array_slice($dataPoints, 0, (int) ceil(count($dataPoints) / 2));
        $secondHalf = array_slice($dataPoints, (int) ceil(count($dataPoints) / 2));

        $firstAvg = array_sum(array_column($firstHalf, 'count')) / count($firstHalf);
        $secondAvg = array_sum(array_column($secondHalf, 'count')) / count($secondHalf);

        if ($firstAvg > 0) {
            return round((($secondAvg - $firstAvg) / $firstAvg) * 100, 2);
        }

        return 0;
    }

    /**
     * Generate error forecast
     *
     * @return array Forecast
     */
    private function generateErrorForecast(array $dataPoints, int $days): array
    {
        if (count($dataPoints) < 2) {
            return [];
        }

        // Simple moving average forecast
        $windowSize = min(7, count($dataPoints));
        $recentValues = array_slice(array_column($dataPoints, 'count'), -$windowSize);
        $avg = array_sum($recentValues) / count($recentValues);

        $forecast = [];
        for ($i = 1; $i <= $days; $i++) {
            $forecast[] = [
                'day' => $i,
                'predicted_errors' => round($avg + (rand(-2, 2) * $avg * 0.1)),
            ];
        }

        return $forecast;
    }

    /**
     * Generate recommendations based on errors
     *
     * @return array Recommendations
     */
    private function generateRecommendations(array $errors): array
    {
        $recommendations = [];

        // Analyze error patterns
        $categoryCounts = [];
        $severityCounts = [];

        foreach ($errors as $error) {
            $category = $error['category'] ?? self::CATEGORY_UNKNOWN;
            $severity = $error['severity'] ?? self::SEVERITY_MEDIUM;

            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
            $severityCounts[$severity] = ($severityCounts[$severity] ?? 0) + 1;
        }

        // Generate recommendations based on patterns
        if (($severityCounts[self::SEVERITY_CRITICAL] ?? 0) > 0) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'immediate_action',
                'message' => 'Critical errors detected. Immediate investigation required.',
                'action' => 'Review critical error logs and implement fixes.',
            ];
        }

        if (($categoryCounts[self::CATEGORY_QUERY] ?? 0) > 5) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'database_performance',
                'message' => 'High number of query errors detected.',
                'action' => 'Review database queries and add appropriate indexes.',
            ];
        }

        if (($categoryCounts[self::CATEGORY_PERFORMANCE] ?? 0) > 3) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'performance_optimization',
                'message' => 'Performance issues detected in analytics processing.',
                'action' => 'Consider optimizing data processing pipelines.',
            ];
        }

        if (($categoryCounts[self::CATEGORY_DATA_VALIDATION] ?? 0) > 10) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'data_quality',
                'message' => 'High number of data validation errors.',
                'action' => 'Review data source quality and add validation rules.',
            ];
        }

        return $recommendations;
    }

    /**
     * Generate alerts from errors
     *
     * @return array Generated alerts
     */
    private function generateAlertsFromErrors(array $errors): array
    {
        $alerts = [];

        // Check against thresholds
        $criticalCount = 0;
        $highCount = 0;
        $mediumCount = 0;
        $lowCount = 0;

        foreach ($errors as $error) {
            $severity = $error['severity'] ?? self::SEVERITY_MEDIUM;
            switch ($severity) {
                case self::SEVERITY_CRITICAL:
                    $criticalCount++;
                    break;
                case self::SEVERITY_HIGH:
                    $highCount++;
                    break;
                case self::SEVERITY_MEDIUM:
                    $mediumCount++;
                    break;
                case self::SEVERITY_LOW:
                    $lowCount++;
                    break;
            }
        }

        // Generate alerts based on thresholds
        if ($criticalCount >= $this->errorConfig['alert_threshold_critical']) {
            $alerts[] = [
                'id' => uniqid('alert_', true),
                'type' => 'critical_error_spike',
                'severity' => self::SEVERITY_CRITICAL,
                'message' => "Critical error threshold exceeded: {$criticalCount} errors",
                'count' => $criticalCount,
                'threshold' => $this->errorConfig['alert_threshold_critical'],
                'created_at' => now()->toIso8601String(),
                'requires_acknowledgment' => true,
            ];
        }

        if ($highCount >= $this->errorConfig['alert_threshold_high']) {
            $alerts[] = [
                'id' => uniqid('alert_', true),
                'type' => 'high_error_spike',
                'severity' => self::SEVERITY_HIGH,
                'message' => "High error threshold exceeded: {$highCount} errors",
                'count' => $highCount,
                'threshold' => $this->errorConfig['alert_threshold_high'],
                'created_at' => now()->toIso8601String(),
                'requires_acknowledgment' => true,
            ];
        }

        return $alerts;
    }
}
