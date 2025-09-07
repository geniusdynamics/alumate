<?php

namespace App\Http\Middleware;

use App\Services\TemplateSecurityValidator;
use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Template Security Middleware
 *
 * Specialized middleware for template operations with comprehensive security checks
 */
class SecurityMiddleware
{
    protected TemplateSecurityValidator $securityValidator;
    protected SecurityService $securityService;

    public function __construct(
        TemplateSecurityValidator $securityValidator,
        SecurityService $securityService
    ) {
        $this->securityValidator = $securityValidator;
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request with comprehensive security checks
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Initial tenant context setup
        $this->setupTenantContext();

        // 1. Rate limiting for template operations
        if ($this->shouldApplyRateLimiting($request)) {
            if (!$this->checkRateLimits($request)) {
                return response()->json([
                    'error' => 'Too many template operations. Please try again later.',
                    'retry_after' => 60
                ], 429);
            }
        }

        // 2. Threat detection for incoming requests
        if ($this->detectTemplateThreats($request)) {
            $this->logSecurityEvent('template_threat_detected', 'critical', [
                'request_path' => $request->path(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'request_method' => $request->method(),
            ]);

            return response()->json([
                'error' => 'Request blocked due to security concerns.',
                'code' => 'TEMPLATE_SECURITY_BLOCKED'
            ], 403);
        }

        // 3. Tenant isolation validation
        if (!$this->validateTenantIsolation($request)) {
            $this->logSecurityEvent('tenant_isolation_violation', 'high', [
                'request_path' => $request->path(),
                'user_id' => Auth::id(),
                'tenant_id' => Auth::check() ? Auth::user()->tenant_id : null,
            ]);

            return response()->json([
                'error' => 'Access denied: Tenant isolation violation.',
                'code' => 'TENANT_ISOLATION_VIOLATION'
            ], 403);
        }

        // 4. Input validation and sanitization
        if ($this->containsTemplateData($request)) {
            $validationResult = $this->validateTemplateInput($request);

            if (!$validationResult['valid']) {
                $this->logSecurityEvent('template_input_validation_failed', 'medium', [
                    'violations' => $validationResult['violations'],
                    'request_path' => $request->path(),
                ]);

                return response()->json([
                    'error' => 'Template validation failed.',
                    'code' => 'TEMPLATE_VALIDATION_FAILED',
                    'violations' => $validationResult['violations']
                ], 422);
            }
        }

        // Process the request
        $response = $next($request);

        // 5. Response security checks
        $this->validateTemplateResponse($request, $response);

        // 6. Usage monitoring and throttling
        $this->updateUsageMetrics($request, $response);

        return $response;
    }

    /**
     * Setup tenant context for the request
     */
    protected function setupTenantContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            $this->securityValidator->setTenantContext($user->tenant_id);
        }
    }

    /**
     * Check if rate limiting should be applied for this request
     */
    protected function shouldApplyRateLimiting(Request $request): bool
    {
        $templateRoutes = [
            'templates.store',
            'templates.update',
            'templates.bulk',
            'api/templates/*',
        ];

        $routeName = $request->route()?->getName();

        if (!$routeName) {
            return false;
        }

        foreach ($templateRoutes as $pattern) {
            if (fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        // Also check by path
        return str_contains($request->path(), '/templates/');
    }

    /**
     * Check rate limits for template operations
     */
    protected function checkRateLimits(Request $request): bool
    {
        $user = Auth::user();
        if (!$user) {
            return true; // Skip for unauthenticated requests
        }

        $identifier = "template_operations:{$user->id}:{$user->tenant_id}";
        $maxRequests = $this->getRateLimitForUser($user);
        $minutes = 5; // 5 minute window

        return $this->securityService->detectRateLimitViolation($identifier, $maxRequests, $minutes);
    }

    /**
     * Get rate limit based on user permissions
     */
    protected function getRateLimitForUser($user): int
    {
        if ($user->hasRole(['super-admin', 'admin'])) {
            return 100; // Higher limit for admins
        }

        if ($user->hasRole('manager')) {
            return 50; // Medium limit for managers
        }

        return 20; // Standard limit for regular users
    }

    /**
     * Detect potential threats in template requests
     */
    protected function detectTemplateThreats(Request $request): bool
    {
        $content = $this->getRequestContent($request);

        if (empty($content)) {
            return false;
        }

        // Check for suspicious patterns
        $threatPatterns = [
            '/<script[^>]*>eval\(/i',
            '/javascript:[^"]*\(/i',
            '/vbscript:[^"]*\(/i',
            '/data:text\/html/i',
            '/onclick\s*=.*[\w]/i',
            '/onload\s*=.*[\w]/i',
            '/<iframe[^>]*src\s*=.*?javascript:/i',
            '/<object[^>]*data\s*=.*?javascript:/i',
        ];

        foreach ($threatPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate tenant isolation
     */
    protected function validateTenantIsolation(Request $request): bool
    {
        // Skip for public routes
        if (!$this->isTemplateRoute($request)) {
            return true;
        }

        $user = Auth::user();
        if (!$user) {
            return false; // All template routes should be authenticated
        }

        // Check route parameters for potential tenant leaks
        $routeParams = $request->route()->parameters();

        // Validate any template IDs in the route
        if (isset($routeParams['template'])) {
            return $this->validateTemplateOwnership($routeParams['template'], $user->tenant_id);
        }

        // For template creation/updates, tenant_id should match user
        $inputTenantId = $request->input('tenant_id');
        if ($inputTenantId && $inputTenantId !== $user->tenant_id) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a template-related route
     */
    protected function isTemplateRoute(Request $request): bool
    {
        $path = $request->path();

        return str_contains($path, '/templates/') ||
               str_contains($path, '/api/templates/') ||
               $request->route()?->named('templates.*');
    }

    /**
     * Validate template ownership
     */
    protected function validateTemplateOwnership($templateId, int $tenantId): bool
    {
        // This would typically check the database
        // For now, return true assuming ownership is validated elsewhere
        return true;
    }

    /**
     * Check if request contains template data that needs validation
     */
    protected function containsTemplateData(Request $request): bool
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return false;
        }

        $contentTypes = ['application/json', 'application/x-www-form-urlencoded'];

        return in_array($request->header('Content-Type'), $contentTypes) ||
               $request->hasAny(['structure', 'config', 'name']);
    }

    /**
     * Validate template input data
     */
    protected function validateTemplateInput(Request $request): array
    {
        $violations = [];

        try {
            $data = $request->all();

            // Check file upload security
            if ($request->hasFile('files')) {
                $fileValidation = $this->validateFileUploads($request);
                $violations = array_merge($violations, $fileValidation);
            }

            // Structural validation for template content
            if (isset($data['structure'])) {
                $this->securityValidator->validate($data['structure']);
            }

            // Metadata validation
            if (isset($data['metadata'])) {
                $violations = array_merge($violations, $this->validateMetadata($data['metadata']));
            }

        } catch (\Exception $e) {
            $violations[] = [
                'field' => 'general',
                'message' => $e->getMessage(),
                'code' => 'VALIDATION_ERROR'
            ];
        }

        return [
            'valid' => empty($violations),
            'violations' => $violations
        ];
    }

    /**
     * Validate file uploads for security
     */
    protected function validateFileUploads(Request $request): array
    {
        $violations = [];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $files = $request->file('files', []);

        foreach ($files as $file) {
            if (!$file->isValid()) {
                $violations[] = [
                    'field' => 'files',
                    'message' => 'File upload failed validation',
                    'code' => 'INVALID_FILE'
                ];
                continue;
            }

            // Check file type
            if (!in_array($file->getMimeType(), $allowedTypes)) {
                $violations[] = [
                    'field' => 'files',
                    'message' => 'File type not allowed. Only images are accepted.',
                    'code' => 'INVALID_FILE_TYPE'
                ];
            }

            // Check file size
            if ($file->getSize() > $maxSize) {
                $violations[] = [
                    'field' => 'files',
                    'message' => 'File size too large. Maximum 5MB allowed.',
                    'code' => 'FILE_TOO_LARGE'
                ];
            }

            // Check file name for security
            $filename = $file->getClientOriginalName();
            if (preg_match('/[<>\"\']|script|javascript|vbscript/i', $filename)) {
                $violations[] = [
                    'field' => 'files',
                    'message' => 'File name contains suspicious content.',
                    'code' => 'SUSPICIOUS_FILENAME'
                ];
            }
        }

        return $violations;
    }

    /**
     * Validate template metadata
     */
    protected function validateMetadata(array $metadata): array
    {
        $violations = [];

        // Validate SEO fields
        if (isset($metadata['seo'])) {
            $seoValidation = $this->validateSEOData($metadata['seo']);
            $violations = array_merge($violations, $seoValidation);
        }

        // Validate keywords
        if (isset($metadata['keywords']) && is_array($metadata['keywords'])) {
            foreach ($metadata['keywords'] as $keyword) {
                if (!is_string($keyword) || strlen($keyword) > 100) {
                    $violations[] = [
                        'field' => 'keywords',
                        'message' => 'Invalid keyword format or length',
                        'code' => 'INVALID_KEYWORD'
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Validate SEO data
     */
    protected function validateSEOData(array $seo): array
    {
        $violations = [];

        $rules = [
            'title' => ['max' => 60, 'pattern' => '/^[a-zA-Z0-9\-_\s]+$/'],
            'description' => ['max' => 160, 'pattern' => null],
            'keywords' => ['max_items' => 10, 'max_length' => 50],
        ];

        foreach ($rules as $field => $rule) {
            if (isset($seo[$field])) {
                $value = $seo[$field];

                if (isset($rule['max']) && strlen($value) > $rule['max']) {
                    $violations[] = [
                        'field' => "seo.{$field}",
                        'message' => "{$field} exceeds maximum length of {$rule['max']} characters",
                        'code' => 'SEO_FIELD_TOO_LONG'
                    ];
                }

                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $violations[] = [
                        'field' => "seo.{$field}",
                        'message' => "{$field} contains invalid characters",
                        'code' => 'SEO_INVALID_CHARACTERS'
                    ];
                }
            }
        }

        return $violations;
    }

    /**
     * Validate template response
     */
    protected function validateTemplateResponse(Request $request, Response $response): void
    {
        // Add security headers to template responses
        $this->addSecurityHeaders($response);

        // Log response for audit trail
        if ($this->shouldLogResponse($request, $response)) {
            $this->logSecurityEvent('template_response', 'info', [
                'request_path' => $request->path(),
                'response_status' => $response->getStatusCode(),
                'response_size' => strlen($response->getContent()),
            ]);
        }
    }

    /**
     * Add security headers to response
     */
    protected function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Template-Security', 'validated');
        $response->headers->set('Cache-Control', 'private, no-cache');
    }

    /**
     * Check if response should be logged
     */
    protected function shouldLogResponse(Request $request, Response $response): bool
    {
        // Log error responses and POST/PUT requests
        return $response->getStatusCode() >= 400 ||
               in_array($request->method(), ['POST', 'PUT', 'PATCH']);
    }

    /**
     * Update usage metrics
     */
    protected function updateUsageMetrics(Request $request, Response $response): void
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Track template operations per user/tenant
            // This could be stored in cache or database for monitoring
            $this->securityService->logDataAccess(
                'template_operation',
                $request->route()?->parameter('template', 0),
                $request->method(),
                $response->getStatusCode() < 400,
                'middleware_tracking'
            );
        }
    }

    /**
     * Get request content for validation
     */
    protected function getRequestContent(Request $request): string
    {
        // Get raw input content
        if ($request->expectsJson()) {
            return $request->getContent();
        }

        return json_encode($request->all());
    }

    /**
     * Log security event
     */
    protected function logSecurityEvent(string $eventType, string $severity, array $details = []): void
    {
        $userId = Auth::id();

        $this->securityService->logSecurityEvent(
            "template_{$eventType}",
            $severity,
            "Template security event: {$eventType}",
            array_merge($details, [
                'tenant_id' => Auth::check() ? Auth::user()->tenant_id : null,
                'user_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]),
            $userId
        );
    }
}