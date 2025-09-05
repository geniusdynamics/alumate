<?php

namespace App\Services;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityLog;
use Illuminate\Support\Facades\Log;

/**
 * Enhanced XSS Prevention Service for Template Rendering
 *
 * Provides comprehensive XSS prevention measures specifically designed
 * for template rendering with context-aware sanitization
 */
class TemplateXssPreventionService
{
    protected array $dangerousTags = [
        'script', 'iframe', 'object', 'embed', 'form', 'input', 'meta', 'link'
    ];

    protected array $dangerousAttributes = [
        'onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout',
        'onkeydown', 'onkeyup', 'onkeypress', 'onsubmit', 'onreset',
        'onchange', 'onfocus', 'onblur', 'onselect', 'onunload',
        'javascript:', 'vbscript:', 'data:', 'expression('
    ];

    protected array $allowedTags = [
        'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'div', 'span', 'a', 'img', 'ul', 'ol', 'li', 'blockquote',
        'table', 'thead', 'tbody', 'tr', 'th', 'td'
    ];

    protected array $allowedAttributes = [
        'href', 'src', 'alt', 'title', 'target', 'rel', 'class', 'id',
        'width', 'height', 'style', 'data-*', 'aria-*', 'role'
    ];

    /**
     * Sanitize template content for rendering
     *
     * @param string $content
     * @param array $options Sanitization options
     * @return string
     * @throws TemplateSecurityException
     */
    public function sanitizeForRendering(string $content, array $options = []): string
    {
        $tenantId = $options['tenant_id'] ?? null;
        $userId = $options['user_id'] ?? null;

        // Pre-sanitization checks
        $this->performSecurityChecks($content, $tenantId, $userId);

        // Remove dangerous tags completely
        $content = $this->removeDangerousTags($content);

        // Sanitize attributes
        $content = $this->sanitizeAttributes($content);

        // Sanitize URLs
        $content = $this->sanitizeUrls($content);

        // Sanitize CSS
        $content = $this->sanitizeCss($content);

        // Final encoding of suspicious content
        $content = $this->encodeSuspiciousContent($content);

        return $content;
    }

    /**
     * Perform comprehensive security checks
     *
     * @param string $content
     * @param int|null $tenantId
     * @param int|null $userId
     * @throws TemplateSecurityException
     */
    protected function performSecurityChecks(string $content, ?int $tenantId, ?int $userId): void
    {
        $threats = [];

        // Check for script injections
        if ($this->containsScriptInjection($content)) {
            $threats[] = [
                'type' => 'script_injection',
                'severity' => 'critical',
                'description' => 'Script tag or JavaScript injection detected'
            ];
        }

        // Check for event handler injections
        if ($this->containsEventHandlers($content)) {
            $threats[] = [
                'type' => 'event_handler_injection',
                'severity' => 'high',
                'description' => 'Dangerous event handler detected'
            ];
        }

        // Check for data exfiltration patterns
        if ($this->containsDataExfiltration($content)) {
            $threats[] = [
                'type' => 'data_exfiltration',
                'severity' => 'critical',
                'description' => 'Potential data exfiltration pattern detected'
            ];
        }

        if (!empty($threats)) {
            // Log security event
            $this->logSecurityEvent('xss_prevention_triggered', 'high', [
                'threats' => $threats,
                'content_length' => strlen($content),
                'tenant_id' => $tenantId,
                'user_id' => $userId
            ], $tenantId, $userId);

            throw new TemplateSecurityException(
                'XSS prevention: Dangerous content detected in template',
                $threats
            );
        }
    }

    /**
     * Remove dangerous HTML tags
     *
     * @param string $content
     * @return string
     */
    protected function removeDangerousTags(string $content): string
    {
        foreach ($this->dangerousTags as $tag) {
            // Remove opening tags
            $content = preg_replace('/<' . preg_quote($tag, '/') . '[^>]*>/i', '', $content);
            // Remove closing tags
            $content = preg_replace('/<\/' . preg_quote($tag, '/') . '>/i', '', $content);
            // Remove self-closing tags
            $content = preg_replace('/<' . preg_quote($tag, '/') . '[^>]*\/>/i', '', $content);
        }

        return $content;
    }

    /**
     * Sanitize HTML attributes
     *
     * @param string $content
     * @return string
     */
    protected function sanitizeAttributes(string $content): string
    {
        // Remove dangerous attributes
        foreach ($this->dangerousAttributes as $attribute) {
            $content = preg_replace('/\s+' . preg_quote($attribute, '/') . '[^=]*=[^>\s]*/i', '', $content);
        }

        // Sanitize href attributes
        $content = preg_replace_callback(
            '/href\s*=\s*["\']([^"\']*)["\']/i',
            function ($matches) {
                $url = $matches[1];
                if ($this->isDangerousUrl($url)) {
                    return 'href="#"';
                }
                return $matches[0];
            },
            $content
        );

        return $content;
    }

    /**
     * Sanitize URLs in content
     *
     * @param string $content
     * @return string
     */
    protected function sanitizeUrls(string $content): string
    {
        // Replace dangerous protocols
        $dangerousProtocols = ['javascript:', 'vbscript:', 'data:', 'file:'];
        foreach ($dangerousProtocols as $protocol) {
            $content = str_ireplace($protocol, '#', $content);
        }

        return $content;
    }

    /**
     * Sanitize CSS content
     *
     * @param string $content
     * @return string
     */
    protected function sanitizeCss(string $content): string
    {
        // Remove CSS expressions
        $content = preg_replace('/expression\s*\([^)]*\)/i', '', $content);

        // Remove JavaScript in CSS
        $content = preg_replace('/javascript\s*:/i', '', $content);

        // Remove VBscript in CSS
        $content = preg_replace('/vbscript\s*:/i', '', $content);

        return $content;
    }

    /**
     * Encode suspicious content
     *
     * @param string $content
     * @return string
     */
    protected function encodeSuspiciousContent(string $content): string
    {
        // Encode angle brackets in suspicious contexts
        $content = preg_replace('/<(?![\/]?(' . implode('|', $this->allowedTags) . ')[\s>])/i', '<', $content);

        return $content;
    }

    /**
     * Check for script injection patterns
     *
     * @param string $content
     * @return bool
     */
    protected function containsScriptInjection(string $content): bool
    {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:[^"\'>\s]*/i',
            '/vbscript:[^"\'>\s]*/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for event handler injections
     *
     * @param string $content
     * @return bool
     */
    protected function containsEventHandlers(string $content): bool
    {
        $eventPatterns = [
            '/on\w+\s*=\s*["\'][^"\']*["\']/i',
            '/on\w+\s*=\s*[^>\s]*/i',
        ];

        foreach ($eventPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for data exfiltration patterns
     *
     * @param string $content
     * @return bool
     */
    protected function containsDataExfiltration(string $content): bool
    {
        $patterns = [
            '/XMLHttpRequest/i',
            '/fetch\s*\(/i',
            '/document\.cookie/i',
            '/localStorage/i',
            '/sessionStorage/i',
            '/location\.href/i',
            '/window\.location/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if URL is dangerous
     *
     * @param string $url
     * @return bool
     */
    protected function isDangerousUrl(string $url): bool
    {
        $dangerousSchemes = ['javascript', 'vbscript', 'data', 'file'];

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            return in_array(strtolower($scheme), $dangerousSchemes);
        }

        // Check for protocol-relative URLs that might be dangerous
        return preg_match('/^\s*(javascript|vbscript|data):/i', $url);
    }

    /**
     * Sanitize template structure recursively
     *
     * @param array $structure
     * @param array $options
     * @return array
     */
    public function sanitizeTemplateStructure(array $structure, array $options = []): array
    {
        $sanitized = [];

        foreach ($structure as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeForRendering($value, $options);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeTemplateStructure($value, $options);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Log security event
     *
     * @param string $eventType
     * @param string $severity
     * @param array $details
     * @param int|null $tenantId
     * @param int|null $userId
     */
    protected function logSecurityEvent(string $eventType, string $severity, array $details, ?int $tenantId, ?int $userId): void
    {
        try {
            SecurityLog::logXssAttempt($tenantId ?? 0, $userId, null, $details);
        } catch (\Exception $e) {
            Log::error('Failed to log XSS security event', [
                'error' => $e->getMessage(),
                'event_type' => $eventType,
                'details' => $details
            ]);
        }
    }

    /**
     * Get sanitization statistics
     *
     * @return array
     */
    public function getSanitizationStats(): array
    {
        return [
            'allowed_tags' => $this->allowedTags,
            'allowed_attributes' => $this->allowedAttributes,
            'dangerous_tags' => $this->dangerousTags,
            'dangerous_attributes' => $this->dangerousAttributes,
        ];
    }
}