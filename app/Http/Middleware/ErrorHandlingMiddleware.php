<?php

namespace App\Http\Middleware;

use App\Services\TemplateErrorHandler;
use App\Exceptions\TemplateException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Middleware for centralized template error handling
 *
 * This middleware intercepts template-related errors and processes them through
 * the TemplateErrorHandler service, providing comprehensive error management
 * with tenant isolation and monitoring capabilities.
 */
class ErrorHandlingMiddleware
{
    protected TemplateErrorHandler $templateErrorHandler;

    // Request patterns that indicate template-related operations
    protected array $templateRoutes = [
        'api/template',
        'api/landing-pages',
        'api/components',
        'api/brand-config',
        'api/template-preview',
    ];

    // Request method patterns for template operations
    protected array $templateMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(TemplateErrorHandler $templateErrorHandler)
    {
        $this->templateErrorHandler = $templateErrorHandler;
    }

    /**
     * Handle an incoming request and process any template errors
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $isTemplateRoute = $this->isTemplateRoute($request);
        $tenantId = $this->getTenantId($request);
        $requestId = $this->getRequestId($request);

        try {
            // Add request ID to response headers for tracking
            $request->headers->set('X-Request-ID', $requestId);

            $response = $next($request);

            // Log performance if it's a template operation
            if ($isTemplateRoute && $request->isMethod('get', 'post', 'put', 'patch')) {
                $this->logPerformanceIfNeeded($request, $startTime, $tenantId);
            }

            // Add request ID to successful responses too
            if ($response instanceof Response) {
                $response->headers->set('X-Request-ID', $requestId);
            }

            return $response;

        } catch (TemplateException $e) {
            return $this->handleTemplateError($e, $request, $tenantId, $startTime);

        } catch (Throwable $e) {
            // Check if this might be a template-related error in disguise
            if ($this->mightBeTemplateError($e, $request)) {
                return $this->handleNonTemplateError($e, $request, $tenantId, $startTime);
            }

            // Re-throw non-template errors to allow other handlers to process them
            throw $e;
        }
    }

    /**
     * Handle template-specific exceptions
     *
     * @param TemplateException $exception
     * @param Request $request
     * @param string|null $tenantId
     * @param float $startTime
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleTemplateError(
        TemplateException $exception,
        Request $request,
        ?string $tenantId,
        float $startTime
    ): \Illuminate\Http\JsonResponse {
        // Set tenant ID if not already set
        if (!$exception->getTenantId() && $tenantId) {
            $exception->setTenantId($tenantId);
        }

        // Log performance before error processing
        $this->logPerformanceIfNeeded($request, $startTime, $tenantId);

        // Use TemplateErrorHandler to process the error
        $context = $this->buildErrorContext($request, $tenantId, $startTime);
        $errorResponse = $this->templateErrorHandler->handleError($exception, $context, $tenantId);

        return response()->json($errorResponse, $this->determineStatusCode($errorResponse));
    }

    /**
     * Handle potentially template-related errors
     *
     * @param Throwable $exception
     * @param Request $request
     * @param string|null $tenantId
     * @param float $startTime
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNonTemplateError(
        Throwable $exception,
        Request $request,
        ?string $tenantId,
        float $startTime
    ): \Illuminate\Http\JsonResponse {
        // Log performance before error processing
        $this->logPerformanceIfNeeded($request, $startTime, $tenantId);

        // Convert to template error if possible
        $context = $this->buildErrorContext($request, $tenantId, $startTime);
        $errorResponse = $this->templateErrorHandler->handleError($exception, $context, $tenantId);

        // Add additional context for non-template errors
        $errorResponse['original_error_type'] = get_class($exception);

        return response()->json($errorResponse, $this->determineStatusCode($errorResponse));
    }

    /**
     * Determine if this is a template-related route
     *
     * @param Request $request
     * @return bool
     */
    protected function isTemplateRoute(Request $request): bool
    {
        $path = $request->getPathInfo();
        $method = strtoupper($request->getMethod());

        // Check path patterns
        foreach ($this->templateRoutes as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        // Check URL parameters for template elements
        if ($request->hasAny(['template_id', 'component_id', 'landing_page_id', 'brand_config_id'])) {
            return true;
        }

        // Check if requested method is a template operation
        return in_array($method, $this->templateMethods) &&
               (str_contains($path, '/template') ||
                str_contains($path, '/component') ||
                str_contains($path, '/landing-page') ||
                str_contains($path, '/brand'));
    }

    /**
     * Extract tenant ID from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTenantId(Request $request): ?string
    {
        // Try different sources for tenant ID
        return $request->input('tenant_id') ??
               $request->header('X-Tenant-ID') ??
               $request->route('tenant_id') ??
               $request->route('institution') ??
               ($request->route('institutionId') ? $request->route('institutionId') : null);
    }

    /**
     * Generate or extract request ID for tracking
     *
     * @param Request $request
     * @return string
     */
    protected function getRequestId(Request $request): string
    {
        return $request->header('X-Request-ID') ??
               'req_' . substr(uniqid(true), 0, 8);
    }

    /**
     * Determine if error might be template-related
     *
     * @param Throwable $exception
     * @param Request $request
     * @return bool
     */
    protected function mightBeTemplateError(Throwable $exception, Request $request): bool
    {
        $isTemplateRoute = $this->isTemplateRoute($request);
        $exceptionClass = get_class($exception);
        $message = strtolower($exception->getMessage());

        // Check if we should handle this even though it's not a TemplateException
        $templateIndicators = [
            'template',
            'component',
            'landing',
            'brand',
            'structure',
            'validation',
            'security'
        ];

        foreach ($templateIndicators as $indicator) {
            if (str_contains($message, $indicator)) {
                return true;
            }
        }

        // Handle database errors on template routes
        if ($isTemplateRoute && $exception instanceof \Illuminate\Database\QueryException) {
            return true;
        }

        return false;
    }

    /**
     * Build context for error handling
     *
     * @param Request $request
     * @param string|null $tenantId
     * @param float $startTime
     * @return array
     */
    protected function buildErrorContext(Request $request, ?string $tenantId, float $startTime): array
    {
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        return [
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'request_params' => $this->sanitizeRequestParams($request),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'user_id' => Auth::check() ? Auth::id() : null,
            'session_id' => $request->session()?->getId(),
            'execution_time' => $duration,
            'memory_usage' => memory_get_peak_usage(true),
            'php_version' => PHP_VERSION,
            'request_id' => $this->getRequestId($request),
            'route_name' => $request->route()?->getName(),
            'is_ajax' => $request->ajax(),
            'is_secure' => $request->isSecure(),
            'content_type' => $request->getContentType(),
        ];
    }

    /**
     * Sanitize request parameters for logging/privacy
     *
     * @param Request $request
     * @return array
     */
    protected function sanitizeRequestParams(Request $request): array
    {
        $allParams = $request->all();

        // List of sensitive or large parameters to exclude
        $sensitiveKeys = [
            'password',
            'password_confirmation',
            'credit_card',
            'api_key',
            'secret',
            'token',
            'file', // Large file content
            'image', // Large image content
            'content', // Potentially large content
        ];

        $sanitized = [];
        foreach ($allParams as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArrayParam($value);
            } elseif (is_string($value) && strlen($value) > 255) {
                // Truncate long strings
                $sanitized[$key] = substr($value, 0, 252) . '...';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize array parameters recursively
     *
     * @param array $array
     * @return array
     */
    protected function sanitizeArrayParam(array $array): array
    {
        $result = [];
        $maxItems = 10; // Limit array size in logs
        $count = 0;

        foreach ($array as $key => $value) {
            if (++$count > $maxItems) {
                $result['...'] = 'truncated';
                break;
            }

            if (is_array($value)) {
                $result[$key] = $this->sanitizeArrayParam($value);
            } elseif (is_string($value) && strlen($value) > 100) {
                $result[$key] = substr($value, 0, 97) . '...';
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Determine HTTP status code from error response
     *
     * @param array $errorResponse
     * @return int
     */
    protected function determineStatusCode(array $errorResponse): int
    {
        return $errorResponse['status_code'] ?? 500;
    }

    /**
     * Log performance if the operation was slow
     *
     * @param Request $request
     * @param float $startTime
     * @param string|null $tenantId
     */
    protected function logPerformanceIfNeeded(Request $request, float $startTime, ?string $tenantId): void
    {
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Convert duration to milliseconds
        $durationMs = $duration * 1000;

        // Log performance for GET/POST/PUT/PATCH operations that take > 1 second
        if ($durationMs > 1000 && in_array(strtoupper($request->method()), ['GET', 'POST', 'PUT', 'PATCH'])) {
            $this->templateErrorHandler->logPerformanceIssue(
                $request->getPathInfo(),
                $duration,
                1000,
                $tenantId
            );
        }
    }

    /**
     * Handle middleware termination (summary logging)
     *
     * @param Request $request
     * @param Response $response
     */
    public function terminate(Request $request, $response): void
    {
        // Log endpoint access patterns for monitoring
        $this->logAccessPattern($request, $response);

        // Log warnings for slow requests
        $this->logSlowRequestWarnings($request);
    }

    /**
     * Log access patterns for monitoring
     *
     * @param Request $request
     * @param mixed $response
     */
    protected function logAccessPattern(Request $request, $response): void
    {
        // Only log for template-related requests
        if ($this->isTemplateRoute($request)) {
            $pattern = [
                'path' => $request->getPathInfo(),
                'method' => $request->method(),
                'response_code' => $response instanceof Response ? $response->getStatusCode() : 200,
                'tenant_id' => $this->getTenantId($request),
                'user_id' => Auth::check() ? Auth::id() : null,
                'timestamp' => now()->toISOString(),
            ];

            // Store in cache for analysis, keeping last 100 entries
            $cacheKey = 'template_access_patterns';
            $patterns = cache()->get($cacheKey, []);
            array_push($patterns, $pattern);

            if (count($patterns) > 100) {
                $patterns = array_slice($patterns, -100);
            }

            cache()->put($cacheKey, $patterns, 3600); // 1 hour
        }
    }

    /**
     * Log warnings for slow-running requests
     *
     * @param Request $request
     */
    protected function logSlowRequestWarnings(Request $request): void
    {
        // This could be enhanced with request start time tracking
        // For now, this is a placeholder for future slow request detection
    }
}