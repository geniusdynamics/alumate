<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Throwable;

/**
 * Comprehensive Error Handler Service
 *
 * Provides centralized error handling, logging, and user-friendly error responses
 * with support for different error types and contexts.
 */
class ErrorHandlerService
{
    private const ERROR_CACHE_KEY = 'error_stats';
    private const ERROR_CACHE_TTL = 3600; // 1 hour

    /**
     * Handle and log application errors
     *
     * @param Throwable $exception
     * @param array $context Additional context information
     * @return array
     */
    public function handleError(Throwable $exception, array $context = []): array
    {
        // Log the error with full context
        $this->logError($exception, $context);

        // Track error statistics
        $this->trackErrorStats($exception);

        // Generate user-friendly error response
        return $this->generateErrorResponse($exception, $context);
    }

    /**
     * Handle validation errors
     *
     * @param \Illuminate\Validation\ValidationException $exception
     * @return array
     */
    public function handleValidationError(\Illuminate\Validation\ValidationException $exception): array
    {
        $errors = $exception->errors();
        $this->logValidationErrors($errors);

        return [
            'type' => 'validation_error',
            'message' => 'Validation failed',
            'errors' => $errors,
            'status_code' => 422,
        ];
    }

    /**
     * Handle template-specific errors
     *
     * @param Throwable $exception
     * @param array $context
     * @return array
     */
    public function handleTemplateError(Throwable $exception, array $context = []): array
    {
        $templateId = $context['template_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        Log::error('Template error occurred', [
            'template_id' => $templateId,
            'user_id' => $userId,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // Check for common template errors
        $errorType = $this->categorizeTemplateError($exception);

        return [
            'type' => 'template_error',
            'error_type' => $errorType,
            'message' => $this->getTemplateErrorMessage($errorType),
            'template_id' => $templateId,
            'status_code' => $this->getTemplateErrorStatusCode($errorType),
        ];
    }

    /**
     * Handle API errors with proper HTTP status codes
     *
     * @param Throwable $exception
     * @param array $context
     * @return JsonResponse
     */
    public function handleApiError(Throwable $exception, array $context = []): JsonResponse
    {
        $errorResponse = $this->handleError($exception, $context);

        return response()->json([
            'success' => false,
            'error' => $errorResponse,
            'timestamp' => now()->toISOString(),
        ], $errorResponse['status_code'] ?? 500);
    }

    /**
     * Handle database errors
     *
     * @param \Illuminate\Database\QueryException $exception
     * @return array
     */
    public function handleDatabaseError(\Illuminate\Database\QueryException $exception): array
    {
        $errorCode = $exception->getCode();

        // Don't expose sensitive database information
        $safeMessage = $this->getSafeDatabaseErrorMessage($errorCode);

        Log::error('Database error', [
            'code' => $errorCode,
            'message' => $exception->getMessage(),
            'sql' => $this->sanitizeSql($exception->getSql()),
        ]);

        return [
            'type' => 'database_error',
            'message' => $safeMessage,
            'status_code' => 500,
        ];
    }

    /**
     * Handle file upload errors
     *
     * @param Throwable $exception
     * @param array $context
     * @return array
     */
    public function handleFileUploadError(Throwable $exception, array $context = []): array
    {
        $fileName = $context['filename'] ?? 'unknown';
        $fileSize = $context['size'] ?? 0;

        Log::warning('File upload error', [
            'filename' => $fileName,
            'size' => $fileSize,
            'error' => $exception->getMessage(),
        ]);

        return [
            'type' => 'file_upload_error',
            'message' => 'File upload failed. Please check file size and format.',
            'filename' => $fileName,
            'status_code' => 400,
        ];
    }

    /**
     * Generate recovery suggestions for errors
     *
     * @param Throwable $exception
     * @return array
     */
    public function generateRecoverySuggestions(Throwable $exception): array
    {
        $suggestions = [];

        if ($exception instanceof \Illuminate\Database\QueryException) {
            $suggestions[] = 'Check database connection and table structure';
            $suggestions[] = 'Verify data types and constraints';
        }

        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            $suggestions[] = 'Review input data format and requirements';
            $suggestions[] = 'Check field lengths and allowed values';
        }

        if (str_contains($exception->getMessage(), 'template')) {
            $suggestions[] = 'Verify template structure and required fields';
            $suggestions[] = 'Check template permissions and access rights';
        }

        if (str_contains($exception->getMessage(), 'cache')) {
            $suggestions[] = 'Clear application cache';
            $suggestions[] = 'Check Redis/Memcached connection';
        }

        return $suggestions;
    }

    /**
     * Get error statistics
     *
     * @return array
     */
    public function getErrorStats(): array
    {
        return Cache::remember(self::ERROR_CACHE_KEY, self::ERROR_CACHE_TTL, function () {
            return [
                'total_errors' => 0,
                'errors_by_type' => [],
                'errors_by_hour' => [],
                'recent_errors' => [],
                'top_error_messages' => [],
            ];
        });
    }

    /**
     * Clear error statistics
     */
    public function clearErrorStats(): void
    {
        Cache::forget(self::ERROR_CACHE_KEY);
    }

    /**
     * Log error with full context
     *
     * @param Throwable $exception
     * @param array $context
     */
    private function logError(Throwable $exception, array $context): void
    {
        $logData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        // Log based on severity
        if ($exception instanceof \ErrorException) {
            Log::error('Critical error occurred', $logData);
        } elseif ($exception instanceof \Illuminate\Validation\ValidationException) {
            Log::warning('Validation error occurred', $logData);
        } else {
            Log::error('Application error occurred', $logData);
        }
    }

    /**
     * Track error statistics
     *
     * @param Throwable $exception
     */
    private function trackErrorStats(Throwable $exception): void
    {
        $stats = $this->getErrorStats();

        $stats['total_errors']++;
        $errorType = get_class($exception);
        $stats['errors_by_type'][$errorType] = ($stats['errors_by_type'][$errorType] ?? 0) + 1;

        $hour = now()->format('Y-m-d H:00:00');
        $stats['errors_by_hour'][$hour] = ($stats['errors_by_hour'][$hour] ?? 0) + 1;

        // Keep only recent errors (last 100)
        array_unshift($stats['recent_errors'], [
            'type' => $errorType,
            'message' => $exception->getMessage(),
            'timestamp' => now()->toISOString(),
        ]);
        $stats['recent_errors'] = array_slice($stats['recent_errors'], 0, 100);

        Cache::put(self::ERROR_CACHE_KEY, $stats, self::ERROR_CACHE_TTL);
    }

    /**
     * Generate user-friendly error response
     *
     * @param Throwable $exception
     * @param array $context
     * @return array
     */
    private function generateErrorResponse(Throwable $exception, array $context): array
    {
        $isProduction = app()->environment('production');

        $response = [
            'type' => 'application_error',
            'message' => $isProduction
                ? 'An unexpected error occurred. Please try again later.'
                : $exception->getMessage(),
            'status_code' => $this->getHttpStatusCode($exception),
        ];

        // Add debug information in non-production environments
        if (!$isProduction) {
            $response['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        // Add recovery suggestions
        $response['suggestions'] = $this->generateRecoverySuggestions($exception);

        return $response;
    }

    /**
     * Get appropriate HTTP status code for exception
     *
     * @param Throwable $exception
     * @return int
     */
    private function getHttpStatusCode(Throwable $exception): int
    {
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            return 422;
        }

        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return 404;
        }

        if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return 401;
        }

        return 500;
    }

    /**
     * Categorize template errors
     *
     * @param Throwable $exception
     * @return string
     */
    private function categorizeTemplateError(Throwable $exception): string
    {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'validation')) {
            return 'validation_error';
        }

        if (str_contains($message, 'permission') || str_contains($message, 'access')) {
            return 'permission_error';
        }

        if (str_contains($message, 'structure') || str_contains($message, 'json')) {
            return 'structure_error';
        }

        if (str_contains($message, 'render') || str_contains($message, 'display')) {
            return 'render_error';
        }

        return 'general_error';
    }

    /**
     * Get user-friendly template error message
     *
     * @param string $errorType
     * @return string
     */
    private function getTemplateErrorMessage(string $errorType): string
    {
        $messages = [
            'validation_error' => 'Template validation failed. Please check the template structure.',
            'permission_error' => 'You do not have permission to access this template.',
            'structure_error' => 'Template structure is invalid. Please verify the template format.',
            'render_error' => 'Template could not be rendered. Please try again or contact support.',
            'general_error' => 'An error occurred while processing the template.',
        ];

        return $messages[$errorType] ?? $messages['general_error'];
    }

    /**
     * Get HTTP status code for template errors
     *
     * @param string $errorType
     * @return int
     */
    private function getTemplateErrorStatusCode(string $errorType): int
    {
        $codes = [
            'validation_error' => 422,
            'permission_error' => 403,
            'structure_error' => 400,
            'render_error' => 500,
            'general_error' => 500,
        ];

        return $codes[$errorType] ?? 500;
    }

    /**
     * Get safe database error message
     *
     * @param string $errorCode
     * @return string
     */
    private function getSafeDatabaseErrorMessage(string $errorCode): string
    {
        $messages = [
            '23000' => 'Data constraint violation. Please check your input.',
            '42000' => 'Database query syntax error.',
            'HY000' => 'Database connection error.',
        ];

        return $messages[$errorCode] ?? 'Database operation failed.';
    }

    /**
     * Sanitize SQL for logging
     *
     * @param string $sql
     * @return string
     */
    private function sanitizeSql(string $sql): string
    {
        // Remove sensitive data from SQL for logging
        return preg_replace('/(\'|")\s*(\w+)\s*\1\s*=\s*(\'|")\s*([^\'"]+)\s*\3/i', '$1$2$1 = $3[HIDDEN]$3', $sql);
    }

    /**
     * Log validation errors
     *
     * @param array $errors
     */
    private function logValidationErrors(array $errors): void
    {
        Log::warning('Validation errors occurred', [
            'errors' => $errors,
            'input' => request()->all(),
            'user_id' => auth()->id(),
        ]);
    }
}