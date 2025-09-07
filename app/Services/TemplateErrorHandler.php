<?php

namespace App\Services;

use App\Exceptions\TemplateException;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Exception;
use Throwable;

/**
 * Comprehensive Template Error Handler Service
 *
 * Provides centralized error management for template system with:
 * - Centralized error handling and logging
 * - Tenant-aware error isolation
 * - User-friendly error messages with recovery suggestions
 * - Error monitoring and alerting capabilities
 * - Performance impact tracking
 */
class TemplateErrorHandler
{
    protected LogManager $logger;
    protected array $criticalErrors = [];
    protected array $errorMetrics = [];
    protected array $recoverySuggestions = [];

    public function __construct(LogManager $logger)
    {
        $this->logger = $logger;
        $this->initializeRecoverySuggestions();
    }

    /**
     * Handle template-related errors with comprehensive logging and recovery
     *
     * @param Throwable $exception The exception to handle
     * @param array $context Additional context for error handling
     * @param string|null $tenantId Tenant identifier for isolation
     * @return array Error response with user-friendly message
     */
    public function handleError(
        Throwable $exception,
        array $context = [],
        ?string $tenantId = null
    ): array {
        // Determine error type and severity
        $errorType = $this->categorizeError($exception);
        $severity = $this->determineSeverity($exception);

        // Enrich context with tenant and system information
        $enhancedContext = $this->enhanceContext($context, $tenantId);

        // Log detailed error information
        $this->logError($exception, $severity, $enhancedContext);

        // Track error metrics for monitoring
        $this->trackErrorMetrics($errorType, $severity, $tenantId);

        // Check if this triggers critical error alerting
        $this->checkCriticalThresholds($errorType, $tenantId);

        // Generate user-friendly response
        $userResponse = $this->generateUserResponse($exception, $errorType, $enhancedContext);

        return $userResponse;
    }

    /**
     * Handle template system warnings
     *
     * @param string $message Warning message
     * @param array $context Warning context
     * @param string|null $tenantId Tenant identifier
     */
    public function handleWarning(
        string $message,
        array $context = [],
        ?string $tenantId = null
    ): void {
        $enhancedContext = $this->enhanceContext($context, $tenantId);

        $this->logger->getLogger('template-warnings')->warning($message, $enhancedContext);

        // Store warning for potential escalation
        $this->storeWarning($message, $enhancedContext, $tenantId);
    }

    /**
     * Log performance issues related to template operations
     *
     * @param string $operation Operation name
     * @param float $duration Duration in seconds
     * @param int $thresholdMs Threshold in milliseconds
     * @param string|null $tenantId Tenant identifier
     */
    public function logPerformanceIssue(
        string $operation,
        float $duration,
        int $thresholdMs = 1000,
        ?string $tenantId = null
    ): void {
        $durationMs = $duration * 1000;

        if ($durationMs > $thresholdMs) {
            $this->logger->getLogger('template-performance')->warning(
                "Template performance issue: {$operation} took {$durationMs}ms",
                [
                    'operation' => $operation,
                    'duration_ms' => $durationMs,
                    'threshold_ms' => $thresholdMs,
                    'tenant_id' => $tenantId,
                    'timestamp' => now(),
                ]
            );

            $this->trackPerformanceMetric($operation, $durationMs, $tenantId);
        }
    }

    /**
     * Get error statistics for monitoring dashboard
     *
     * @param string|null $tenantId Tenant identifier (null for global stats)
     * @param int $timeRange Hours to look back
     * @return array Error statistics
     */
    public function getErrorStatistics(?string $tenantId = null, int $timeRange = 24): array
    {
        $cacheKey = $tenantId ? "template_errors_{$tenantId}_{$timeRange}h" : "template_errors_global_{$timeRange}h";

        return Cache::remember($cacheKey, 300, function () use ($tenantId, $timeRange) {
            $startTime = now()->subHours($timeRange);

            return [
                'error_counts' => $this->getErrorCounts($tenantId, $startTime),
                'error_trends' => $this->getErrorTrends($tenantId, $startTime),
                'critical_errors' => $this->getCriticalErrorSummary($tenantId, $startTime),
                'performance_issues' => $this->getPerformanceIssueSummary($tenantId, $startTime),
                'recovery_suggestions' => $this->getActiveRecoverySuggestions($tenantId),
            ];
        });
    }

    /**
     * Reset error metrics (used for testing or manual reset)
     *
     * @param string|null $tenantId Tenant identifier
     */
    public function resetMetrics(?string $tenantId = null): void
    {
        $cacheKey = $tenantId ? "template_metrics_{$tenantId}" : "template_metrics_global";

        Cache::forget($cacheKey);
        Cache::forget("template_errors_{$tenantId}_*");
        Cache::forget("template_performance_{$tenantId}_*");
    }

    // Private methods for error categorization and processing

    private function categorizeError(Throwable $exception): string
    {
        $className = get_class($exception);

        // Specific template exceptions
        if (str_contains($className, 'Template')) {
            if (str_contains($className, 'Validation')) return 'validation';
            if (str_contains($className, 'Security')) return 'security';
            if (str_contains($className, 'NotFound')) return 'not_found';
            return 'template';
        }

        // Generic error categories
        if ($exception instanceof \Illuminate\Database\QueryException) return 'database';
        if ($exception instanceof \Illuminate\Validation\ValidationException) return 'validation';

        return 'general';
    }

    private function determineSeverity(Throwable $exception): string
    {
        $errorType = $this->categorizeError($exception);

        // Critical errors
        if ($errorType === 'security') return 'critical';
        if ($errorType === 'database' && $exception->getCode() === 23000) return 'critical'; // Integrity constraints

        // High severity
        if ($errorType === 'validation') return 'high';
        if ($exception instanceof \Error) return 'high';

        // Medium severity
        if ($errorType === 'template') return 'medium';

        // Default to low
        return 'low';
    }

    private function enhanceContext(array $context, ?string $tenantId = null): array
    {
        return array_merge($context, [
            'tenant_id' => $tenantId,
            'environment' => config('app.env'),
            'user_id' => Auth::check() ? Auth::id() : null,
            'request_id' => request()->header('X-Request-ID') ?? uniqid('req_'),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
        ]);
    }

    private function logError(Throwable $exception, string $severity, array $context): void
    {
        $channel = $this->getLogChannel($severity);

        $logContext = array_merge($context, [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
            'severity' => $severity,
        ]);

        match($severity) {
            'critical' => $this->logger->getLogger($channel)->critical($exception->getMessage(), $logContext),
            'high' => $this->logger->getLogger($channel)->error($exception->getMessage(), $logContext),
            'medium' => $this->logger->getLogger($channel)->warning($exception->getMessage(), $logContext),
            default => $this->logger->getLogger($channel)->info($exception->getMessage(), $logContext),
        };
    }

    private function getLogChannel(string $severity): string
    {
        return match($severity) {
            'critical' => 'template-alerts',
            'high' => 'template-errors',
            'medium' => 'template-warnings',
            default => 'template-info',
        };
    }

    private function trackErrorMetrics(string $errorType, string $severity, ?string $tenantId): void
    {
        $cacheKey = $tenantId ? "template_metrics_{$tenantId}" : "template_metrics_global";

        $metrics = Cache::get($cacheKey, [
            'total_errors' => 0,
            'by_type' => [],
            'by_severity' => [],
            'timeline' => [],
        ]);

        $metrics['total_errors']++;
        $metrics['by_type'][$errorType] = ($metrics['by_type'][$errorType] ?? 0) + 1;
        $metrics['by_severity'][$severity] = ($metrics['by_severity'][$severity] ?? 0) + 1;
        $metrics['timeline'][] = [
            'timestamp' => now()->toISOString(),
            'type' => $errorType,
            'severity' => $severity,
        ];

        // Keep only last 100 entries in timeline
        $metrics['timeline'] = array_slice($metrics['timeline'], -100);

        Cache::put($cacheKey, $metrics, 86400); // 24 hours
    }

    private function trackPerformanceMetric(string $operation, float $durationMs, ?string $tenantId): void
    {
        $cacheKey = $tenantId ? "template_performance_{$tenantId}" : "template_performance_global";

        $performance = Cache::get($cacheKey, [
            'slow_operations' => [],
            'average_response_times' => [],
        ]);

        $performance['slow_operations'][] = [
            'operation' => $operation,
            'duration' => $durationMs,
            'timestamp' => now()->toISOString(),
        ];

        // Calculate running average
        $key = "{$operation}_avg";
        $current = $performance['average_response_times'][$key] ?? $durationMs;
        $performance['average_response_times'][$key] = ($current + $durationMs) / 2;

        // Keep only last 50 slow operations
        $performance['slow_operations'] = array_slice($performance['slow_operations'], -50);

        Cache::put($cacheKey, $performance, 86400);
    }

    private function checkCriticalThresholds(string $errorType, ?string $tenantId): void
    {
        $cacheKey = $tenantId ? "critical_errors_{$tenantId}" : "critical_errors_global";

        $thresholds = [
            'security' => 3,
            'database' => 10,
            'validation' => 50,
        ];

        if (isset($thresholds[$errorType])) {
            $errorCount = $this->getRecentErrorCount($errorType, $tenantId);

            if ($errorCount >= $thresholds[$errorType]) {
                $this->triggerCriticalAlert($errorType, $errorCount, $tenantId);
            }
        }
    }

    private function getRecentErrorCount(string $errorType, ?string $tenantId): int
    {
        $cacheKey = $tenantId ? "template_metrics_{$tenantId}" : "template_metrics_global";
        $metrics = Cache::get($cacheKey, ['by_type' => []]);

        return $metrics['by_type'][$errorType] ?? 0;
    }

    private function triggerCriticalAlert(string $errorType, int $count, ?string $tenantId): void
    {
        $message = "Critical error threshold exceeded for {$errorType} errors";
        $context = [
            'error_type' => $errorType,
            'count' => $count,
            'tenant_id' => $tenantId,
            'threshold' => [3, 10, 50] // Adjust based on type
        ];

        $this->logger->getLogger('template-alerts')->alert($message, $context);

        // TODO: Send notification to administrators
        // $this->sendAlertNotification($message, $context);
    }

    private function generateUserResponse(Throwable $exception, string $errorType, array $context): array
    {
        $userMessage = $this->getUserFriendlyMessage($exception, $errorType);
        $recoverySuggestion = $this->getRecoverySuggestion($errorType, $context);

        return [
            'message' => $userMessage,
            'error_type' => $errorType,
            'recovery_suggestion' => $recoverySuggestion,
            'error_id' => $context['request_id'] ?? uniqid('err_'),
            'timestamp' => $context['timestamp'],
            'severity' => $context['severity'] ?? 'unknown',
        ];
    }

    private function getUserFriendlyMessage(Throwable $exception, string $errorType): string
    {
        return match($errorType) {
            'validation' => 'The template data you provided contains errors. Please review and correct the highlighted fields.',
            'security' => 'Security violation detected. Your request cannot be processed.',
            'not_found' => 'The requested template could not be found. It may have been deleted or moved.',
            'database' => 'A database error occurred. Please try again later.',
            default => 'An unexpected error occurred while processing your template request.',
        };
    }

    private function getRecoverySuggestion(string $errorType, array $context): array
    {
        return $this->recoverySuggestions[$errorType] ?? $this->recoverySuggestions['default'];
    }

    private function initializeRecoverySuggestions(): void
    {
        $this->recoverySuggestions = [
            'validation' => [
                'message' => 'Check the validation errors in your request and provide correctly formatted data.',
                'actions' => [
                    'Review the required fields and ensure all data is provided',
                    'Check data formats (JSON structure, date formats, etc.)',
                    'Validate file uploads and sizes if applicable'
                ]
            ],
            'security' => [
                'message' => 'This appears to be a security-related issue.',
                'actions' => [
                    'Try accessing the resource with appropriate permissions',
                    'Contact your administrator if you believe this is an error',
                    'Clear your browser cache and try again'
                ]
            ],
            'not_found' => [
                'message' => 'The requested template is not available.',
                'actions' => [
                    'Check if the template ID is correct',
                    'Verify you have access to this template',
                    'Try searching for the template by name'
                ]
            ],
            'database' => [
                'message' => 'This appears to be a temporary database issue.',
                'actions' => [
                    'Wait a moment and try your request again',
                    'Check your internet connection',
                    'Contact support if the issue persists'
                ]
            ],
            'default' => [
                'message' => 'An unexpected error occurred.',
                'actions' => [
                    'Try your request again in a few minutes',
                    'Check your internet connection',
                    'Contact support if the problem continues'
                ]
            ]
        ];
    }

    private function getErrorCounts(?string $tenantId, $startTime): array
    {
        $cacheKey = $tenantId ? "template_metrics_{$tenantId}" : "template_metrics_global";
        $metrics = Cache::get($cacheKey, ['by_type' => [], 'by_severity' => []]);

        // Filter by time range (simplified implementation)
        return [
            'by_type' => $metrics['by_type'],
            'by_severity' => $metrics['by_severity'],
        ];
    }

    private function getErrorTrends(?string $tenantId, $startTime): array
    {
        $cacheKey = $tenantId ? "template_metrics_{$tenantId}" : "template_metrics_global";
        $metrics = Cache::get($cacheKey, ['timeline' => []]);

        // Group by hour for trending
        $trends = [];
        foreach ($metrics['timeline'] as $entry) {
            $hour = substr($entry['timestamp'], 0, 13); // Get hour part
            if (!isset($trends[$hour])) {
                $trends[$hour] = ['total' => 0, 'by_type' => [], 'by_severity' => []];
            }
            $trends[$hour]['total']++;
            $trends[$hour]['by_type'][$entry['type']] = ($trends[$hour]['by_type'][$entry['type']] ?? 0) + 1;
            $trends[$hour]['by_severity'][$entry['severity']] = ($trends[$hour]['by_severity'][$entry['severity']] ?? 0) + 1;
        }

        return $trends;
    }

    private function getCriticalErrorSummary(?string $tenantId, $startTime): array
    {
        $cacheKey = $tenantId ? "critical_errors_{$tenantId}" : "critical_errors_global";
        $critical = Cache::get($cacheKey, []);

        return array_slice($critical, -10); // Last 10 critical errors
    }

    private function getPerformanceIssueSummary(?string $tenantId, $startTime): array
    {
        $cacheKey = $tenantId ? "template_performance_{$tenantId}" : "template_performance_global";
        $performance = Cache::get($cacheKey, ['slow_operations' => []]);

        return [
            'slow_operations_count' => count($performance['slow_operations']),
            'average_response_times' => $performance['average_response_times'],
        ];
    }

    private function getActiveRecoverySuggestions(?string $tenantId): array
    {
        // In a real implementation, this would check active issues and return relevant suggestions
        return $this->recoverySuggestions;
    }

    private function storeWarning(string $message, array $context, ?string $tenantId): void
    {
        $cacheKey = $tenantId ? "template_warnings_{$tenantId}" : "template_warnings_global";

        $warnings = Cache::get($cacheKey, []);
        $warnings[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        // Keep only last 50 warnings
        $warnings = array_slice($warnings, -50);

        Cache::put($cacheKey, $warnings, 3600); // 1 hour
    }
}