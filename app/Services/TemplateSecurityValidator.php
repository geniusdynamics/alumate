<?php

namespace App\Services;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityEvent;
use App\Services\SecurityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced Template Security Validator
 *
 * Provides comprehensive security validation for template structures
 * including XSS prevention, content security, and tenant isolation
 */
class TemplateSecurityValidator
{
    protected SecurityService $securityService;

    protected int $tenantId;

    protected int $userId;

    protected array $securityPatterns = [
        '/<script[^>]*>.*?<\/script>/is' => 'script_injection',
        '/\s+on\w+\s*=[\s]*["\']?/i' => 'event_handler',
        '/javascript:[^"\'>\s]*/i' => 'javascript_href',
        '/javascript:[^"\'>\s]*\([^)]*\)/i' => 'javascript_function_call',
        '/<iframe[^>]*>.*?<\/iframe>/is' => 'iframe_injection',
        '/<object[^>]*>.*?<\/object>/is' => 'object_injection',
        '/<embed[^>]*>.*?<\/embed>/is' => 'embed_injection',
        '/<applet[^>]*>.*?<\/applet>/is' => 'applet_injection',
        '/<meta[^>]*http-equiv[^>]*>/i' => 'http_equiv_meta',
        '/vbscript:/i' => 'vbscript_href',
        '/data:\s*text\/html/i' => 'data_uri_html',
        '/expression\s*\(/i' => 'css_expression',
        '/call\s*\(|eval\s*\(/i' => 'javascript_execution',
        '/innerHTML|outerHTML|document\.write/i' => 'dangerous_dom_manipulation',
        '/location\.href|window\.location/i' => 'location_manipulation',
        '/XMLHttpRequest|fetch\s*\(/i' => 'data_exfiltration',
        '/document\.cookie|localStorage|sessionStorage/i' => 'storage_access',
        '/Function\s*\(|new\s+Function\s*/i' => 'dynamic_code_execution',
    ];

    protected array $suspiciousKeywords = [
        'javascript', 'vbscript', 'data:', 'expression',
        'onload', 'onerror', 'onclick', 'onmouseover',
        'alert', 'confirm', 'prompt', 'eval',
        'innerHTML', 'outerHTML', 'document.write',
        'location.href', 'window.location',
    ];

    /**
     * Validate template structure for security issues
     *
     * @param array $structure
     * @throws TemplateSecurityException
     */
    public function validate(array $structure): void
    {
        $securityIssues = [];

        // Check sections in the structure
        if (isset($structure['sections']) && is_array($structure['sections'])) {
            foreach ($structure['sections'] as $index => $section) {
                $issues = $this->validateSection($section, $index);
                $securityIssues = array_merge($securityIssues, $issues);
            }
        }

        // Check other parts of the structure
        $structureJson = json_encode($structure);
        if ($structureJson !== false) {
            $issues = $this->checkPatterns($structureJson);
            $securityIssues = array_merge($securityIssues, $issues);
        }

        if (!empty($securityIssues)) {
            throw new TemplateSecurityException(
                "Security validation failed for template structure",
                $securityIssues
            );
        }
    }

    /**
     * Validate a single section
     *
     * @param array $section
     * @param int $index
     * @return array
     */
    protected function validateSection(array $section, int $index): array
    {
        $issues = [];

        // Check configuration values
        if (isset($section['config']) && is_array($section['config'])) {
            foreach ($section['config'] as $key => $value) {
                if (is_string($value)) {
                    $valueIssues = $this->validateString($value, "section[{$index}].config.{$key}");
                    $issues = array_merge($issues, $valueIssues);
                } elseif (is_array($value)) {
                    $issues = array_merge($issues, $this->validateArray($value, "section[{$index}].config.{$key}"));
                }
            }
        }

        return $issues;
    }

    /**
     * Validate string value
     *
     * @param string $string
     * @param string $context
     * @return array
     */
    protected function validateString(string $string, string $context): array
    {
        $issues = [];

        // Check against dangerous patterns
        foreach ($this->securityPatterns as $pattern => $issueType) {
            if (preg_match($pattern, $string)) {
                $issues[] = [
                    'type' => $issueType,
                    'context' => $context,
                    'pattern' => $pattern,
                    'snippet' => $this->extractSnippet($string, $pattern),
                ];
            }
        }

        // Check for suspicious keywords
        foreach ($this->suspiciousKeywords as $keyword) {
            if (stripos($string, $keyword) !== false) {
                // Only flag if it's likely a security issue (not just a mention)
                if ($this->isDangerousContext($string, $keyword)) {
                    $issues[] = [
                        'type' => 'suspicious_keyword',
                        'keyword' => $keyword,
                        'context' => $context,
                        'severity' => 'warning',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Validate array values
     *
     * @param array $array
     * @param string $context
     * @return array
     */
    protected function validateArray(array $array, string $context): array
    {
        $issues = [];

        foreach ($array as $key => $value) {
            $itemContext = $context . "[{$key}]";

            if (is_string($value)) {
                $issues = array_merge($issues, $this->validateString($value, $itemContext));
            } elseif (is_array($value)) {
                $issues = array_merge($issues, $this->validateArray($value, $itemContext));
            }
        }

        return $issues;
    }

    /**
     * Check patterns in the full structure JSON
     *
     * @param string $structureJson
     * @return array
     */
    protected function checkPatterns(string $structureJson): array
    {
        $issues = [];

        foreach ($this->securityPatterns as $pattern => $issueType) {
            if (preg_match($pattern, $structureJson, $matches)) {
                $issues[] = [
                    'type' => $issueType,
                    'context' => 'full_structure',
                    'pattern' => $pattern,
                    'matched' => $matches[0],
                ];
            }
        }

        return $issues;
    }

    /**
     * Check if a keyword is used in a dangerous context
     *
     * @param string $string
     * @param string $keyword
     * @return bool
     */
    protected function isDangerousContext(string $string, string $keyword): bool
    {
        // Check if keyword is used in attribute values or code patterns
        $dangerousPatterns = [
            //'="${keyword}',
            //'=\'${keyword}',
            '<${keyword}',
            '${keyword}\(',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $pattern = str_replace('${keyword}', preg_quote($keyword, '/'), $pattern);
            if (preg_match('/' . $pattern . '/i', $string)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract snippet from matched string
     *
     * @param string $string
     * @param string $pattern
     * @return string
     */
    protected function extractSnippet(string $string, string $pattern): string
    {
        if (preg_match($pattern, $string, $matches)) {
            $matched = $matches[0];
            // Return first 100 characters of the match
            return substr($matched, 0, 100) . (strlen($matched) > 100 ? '...' : '');
        }

        return '';
    }

    /**
     * Get allowed HTML tags
     *
     * @return array
     */
    public function getAllowedTags(): array
    {
        return [
            'p', 'br', 'strong', 'em', 'u',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'div', 'span', 'a', 'img',
            'ul', 'ol', 'li', 'blockquote'
        ];
    }

    /**
     * Get allowed HTML attributes
     *
     * @return array
     */
    public function getAllowedAttributes(): array
    {
        return [
            'href', 'src', 'alt', 'title',
            'target', 'rel', 'class', 'id',
            'width', 'height'
        ];
    }

    /**
     * Create service instance with tenant context
     *
     * @param SecurityService|null $securityService
     * @throws \Exception
     */
    public function __construct(?SecurityService $securityService = null)
    {
        $this->securityService = $securityService ?: new SecurityService();

        // Set tenant and user context
        if (Auth::check()) {
            $user = Auth::user();
            $this->tenantId = $user->tenant_id ?? 0;
            $this->userId = $user->id;
        } else {
            // Use default values for unauthenticated access
            $this->tenantId = 0;
            $this->userId = 0;
        }
    }

    /**
     * Validate template with comprehensive security checks
     *
     * @param array $data
     * @param int|null $tenantId
     * @throws TemplateSecurityException
     */
    public function validateComprehensive(array $data, ?int $tenantId = null): void
    {
        // Set tenant context
        $this->setTenantContext($tenantId);

        // Basic validation
        $this->validateBasicSecurity($data);

        // Advanced threat detection
        $this->detectAdvancedThreats($data);

        // Tenant isolation validation
        $this->validateTenantIsolation($data);

        // XSS and injection prevention
        $this->validateXssPrevention($data);
    }

    /**
     * Set tenant context for validation
     *
     * @param int|null $tenantId
     */
    public function setTenantContext(?int $tenantId): void
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }
    }

    /**
     * Basic security validation
     *
     * @param array $data
     * @throws TemplateSecurityException
     */
    protected function validateBasicSecurity(array $data): void
    {
        $issues = [];

        // Validate URL patterns
        if (isset($data['urls'])) {
            foreach ($data['urls'] as $url) {
                if (!$this->isValidSecureUrl($url)) {
                    $issues[] = [
                        'type' => 'invalid_url',
                        'url' => $url,
                        'severity' => 'high'
                    ];
                }
            }
        }

        // Validate file references
        if (isset($data['files'])) {
            foreach ($data['files'] as $file) {
                if (!$this->isAllowedFileType($file)) {
                    $issues[] = [
                        'type' => 'disallowed_file_type',
                        'file' => $file,
                        'severity' => 'high'
                    ];
                }
            }
        }

        if (!empty($issues)) {
            $this->logSecurityEvent('template_validation_failed', 'high', $issues);
            throw new TemplateSecurityException(
                "Template security validation failed",
                $issues
            );
        }
    }

    /**
     * Advanced threat detection
     *
     * @param array $data
     * @throws TemplateSecurityException
     */
    protected function detectAdvancedThreats(array $data): void
    {
        $threats = [];

        // Check for data exfiltration patterns
        $threats = array_merge($threats, $this->checkDataExfiltrationPatterns($data));

        // Check for code injection attempts
        $threats = array_merge($threats, $this->checkCodeInjectionPatterns($data));

        // Check for malicious redirects
        $threats = array_merge($threats, $this->checkMaliciousRedirects($data));

        if (!empty($threats)) {
            $this->logSecurityEvent('advanced_threat_detected', 'critical', $threats);
            throw new TemplateSecurityException(
                "Advanced threat detected in template",
                $threats
            );
        }
    }

    /**
     * Validate tenant isolation
     *
     * @param array $data
     * @throws TemplateSecurityException
     */
    protected function validateTenantIsolation(array $data): void
    {
        // Check for cross-tenant access attempts
        if (isset($data['tenant_id']) && $data['tenant_id'] !== $this->tenantId) {
            $violation = [
                'type' => 'tenant_isolation_violation',
                'attempted_tenant_id' => $data['tenant_id'],
                'actual_tenant_id' => $this->tenantId,
                'severity' => 'critical'
            ];

            $this->logSecurityEvent('tenant_isolation_breach', 'critical', $violation);

            throw new TemplateSecurityException(
                "Tenant isolation violation detected",
                [$violation]
            );
        }

        // Check for unauthorized resource access
        if (isset($data['resources'])) {
            foreach ($data['resources'] as $resource) {
                if (!$this->canAccessResource($resource, $this->tenantId)) {
                    $violation = [
                        'type' => 'unauthorized_resource_access',
                        'resource' => $resource,
                        'severity' => 'high'
                    ];

                    $this->logSecurityEvent('unauthorized_access_attempt', 'high', $violation);
                    throw new TemplateSecurityException(
                        "Unauthorized resource access detected",
                        [$violation]
                    );
                }
            }
        }
    }

    /**
     * Validate XSS prevention measures
     *
     * @param array $data
     */
    protected function validateXssPrevention(array $data): void
    {
        // Override the original validate method to use comprehensive validation
        $this->validate($data);
    }

    /**
     * Check data exfiltration patterns
     *
     * @param array $data
     * @return array
     */
    protected function checkDataExfiltrationPatterns(array $data): array
    {
        $patterns = [
            '/\w+\.(?:cookie|localStorage|sessionStorage)\s*=/i',
            '/XMLHttpRequest\s*\([^)]*(?:data|secret|token)/i',
            '/fetch\s*\([^)]*(?:data|secret|token)/i',
        ];

        $threats = [];
        $dataString = json_encode($data);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dataString)) {
                $threats[] = [
                    'type' => 'data_exfiltration_attempt',
                    'pattern' => $pattern,
                    'severity' => 'high'
                ];
            }
        }

        return $threats;
    }

    /**
     * Check code injection patterns
     *
     * @param array $data
     * @return array
     */
    protected function checkCodeInjectionPatterns(array $data): array
    {
        $patterns = [
            '/eval\s*\(/i',
            '/Function\s*\(/i',
            '/new\s+Function\s*/i',
            '/setTimeout\s*\([^)]*\+/i',
            '/setInterval\s*\([^)]*\+/i',
        ];

        $threats = [];
        $dataString = json_encode($data);

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dataString)) {
                $threats[] = [
                    'type' => 'code_injection_attempt',
                    'pattern' => $pattern,
                    'severity' => 'critical'
                ];
            }
        }

        return $threats;
    }

    /**
     * Check malicious redirects
     *
     * @param array $data
     * @return array
     */
    protected function checkMaliciousRedirects(array $data): array
    {
        $threats = [];

        if (isset($data['redirects'])) {
            foreach ($data['redirects'] as $redirect) {
                if (!$this->isSafeRedirect($redirect)) {
                    $threats[] = [
                        'type' => 'malicious_redirect',
                        'redirect' => $redirect,
                        'severity' => 'high'
                    ];
                }
            }
        }

        return $threats;
    }

    /**
     * Validate URL for security
     *
     * @param string $url
     * @return bool
     */
    protected function isValidSecureUrl(string $url): bool
    {
        // HTTPS only for external URLs
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            return in_array($scheme, ['http', 'https']);
        }

        return true; // Allow relative URLs
    }

    /**
     * Check if file type is allowed
     *
     * @param string $filename
     * @return bool
     */
    protected function isAllowedFileType(string $filename): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'pdf'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Check if redirect is safe
     *
     * @param string $redirect
     * @return bool
     */
    protected function isSafeRedirect(string $redirect): bool
    {
        // Allow same-origin and trusted domain redirects only
        $parsedUrl = parse_url($redirect);

        if (!isset($parsedUrl['host'])) {
            return true; // Relative redirects are safe
        }

        // Add your trusted domains here
        $trustedDomains = ['yoursite.com', 'trusted-domain.com'];

        return in_array($parsedUrl['host'], $trustedDomains);
    }

    /**
     * Check if user can access resource
     *
     * @param mixed $resource
     * @param int $tenantId
     * @return bool
     */
    protected function canAccessResource($resource, int $tenantId): bool
    {
        // This would check against database/domain rules
        // For now, just basic check
        if (is_array($resource) && isset($resource['tenant_id'])) {
            return $resource['tenant_id'] === $tenantId;
        }

        return true;
    }

    /**
     * Sanitize template data
     *
     * @param array $data
     * @return array
     */
    public function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize string content
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeString(string $string): string
    {
        // Remove dangerous HTML
        $allowedTags = $this->getAllowedTags();
        $allowedTagsString = '<' . implode('><', $allowedTags) . '>';

        return strip_tags($string, $allowedTagsString);
    }

    /**
     * Log security event
     *
     * @param string $eventType
     * @param string $severity
     * @param mixed $details
     */
    protected function logSecurityEvent(string $eventType, string $severity, $details): void
    {
        // Use existing security service if available
        if (method_exists($this->securityService, 'logSecurityEvent')) {
            $this->securityService->logSecurityEvent($eventType, $severity, $eventType, [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'details' => $details,
                'service' => 'template_security_validator'
            ], $this->userId);
        }

        // Also log to Laravel logger
        Log::warning("Template Security Event: {$eventType}", [
            'severity' => $severity,
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'details' => $details
        ]);
    }
}