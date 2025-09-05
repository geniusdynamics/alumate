<?php

namespace App\Services;

use Illuminate\Support\Arr;

/**
 * Template Structure Sanitizer
 *
 * Sanitizes template structures to prevent XSS and other security issues
 */
class TemplateStructureSanitizer
{
    protected array $allowedTags = [
        'p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'div', 'span', 'a', 'img', 'ul', 'ol', 'li', 'blockquote'
    ];

    protected array $allowedAttributes = [
        'href', 'src', 'alt', 'title', 'target', 'rel', 'class', 'id'
    ];

    /**
     * Sanitize template structure
     *
     * @param array $structure
     * @return array
     */
    public function sanitize(array $structure): array
    {
        $sanitized = [];

        if (isset($structure['sections']) && is_array($structure['sections'])) {
            $sanitized['sections'] = array_map([$this, 'sanitizeSection'], $structure['sections']);
        }

        return $sanitized;
    }

    /**
     * Sanitize a single section
     *
     * @param array $section
     * @return array
     */
    protected function sanitizeSection(array $section): array
    {
        $sanitized = [];

        // Sanitize basic properties
        $allowedSectionKeys = ['type', 'config', 'order'];
        foreach ($allowedSectionKeys as $key) {
            if (isset($section[$key]) && !empty($section[$key])) {
                $sanitized[$key] = $this->sanitizeValue($section[$key]);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize individual values
     *
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        if (is_string($value)) {
            return $this->sanitizeString($value);
        }

        if (is_array($value)) {
            return array_map([$this, 'sanitizeValue'], $value);
        }

        // Return numeric and boolean values as-is
        if (is_numeric($value) || is_bool($value) || is_null($value)) {
            return $value;
        }

        // Convert objects to arrays
        if (is_object($value)) {
            return $this->sanitizeValue((array) $value);
        }

        return null;
    }

    /**
     * Sanitize string values
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeString(string $string): string
    {
        // Remove harmful HTML attributes
        $string = $this->removeHarmfulAttributes($string);

        // Strip unwanted tags
        $string = $this->stripUnwantedTags($string);

        // Convert special characters to HTML entities
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        // Allow back some safe HTML
        $string = $this->allowSafeTags($string);

        return $string;
    }

    /**
     * Remove harmful HTML attributes
     *
     * @param string $string
     * @return string
     */
    protected function removeHarmfulAttributes(string $string): string
    {
        // Remove event handlers
        $string = preg_replace('/\s+on\w+\s*=/i', '', $string);

        // Remove script and style tags
        $string = preg_replace('/<\/?script[^>]*>|<\/?style[^>]*>/i', '', $string);

        return $string;
    }

    /**
     * Strip unwanted HTML tags
     *
     * @param string $string
     * @return string
     */
    protected function stripUnwantedTags(string $string): string
    {
        // Simple regex to strip tags (not perfect but safe)
        $string = preg_replace('/<[^>]*>/', '', $string);

        return $string;
    }

    /**
     * Allow back some safe HTML tags (if we're doing rich text)
     *
     * @param string $string
     * @return string
     */
    protected function allowSafeTags(string $string): string
    {
        // This is a basic implementation. In a real application,
        // you might want to use a library like HTMLPurifier
        // For now, just decode the basic entities we created
        $string = htmlspecialchars_decode($string, ENT_QUOTES);

        return $string;
    }

    /**
     * Check if string contains potentially harmful content
     *
     * @param string $string
     * @return array List of security issues found
     */
    public function detectSecurityIssues(string $string): array
    {
        $issues = [];

        // Check for JavaScript injection patterns
        if (preg_match('/<script[^>]*>.*?<\/script>/is', $string)) {
            $issues[] = 'script_tags_detected';
        }

        // Check for event handlers
        if (preg_match('/\s+on\w+\s*=/i', $string)) {
            $issues[] = 'event_handlers_detected';
        }

        // Check for href with javascript:
        if (preg_match('/href\s*=\s*["\']?\s*javascript:/i', $string)) {
            $issues[] = 'javascript_href_detected';
        }

        return $issues;
    }
}