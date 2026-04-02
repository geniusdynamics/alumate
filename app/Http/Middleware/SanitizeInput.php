<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Handle an incoming request and sanitize input data.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize all input data
        $this->sanitizeRequestData($request);

        return $next($request);
    }

    /**
     * Sanitize request input data to prevent XSS and other injection attacks.
     */
    private function sanitizeRequestData(Request $request): void
    {
        // Sanitize query parameters
        $queryParams = $request->query();
        if (! empty($queryParams)) {
            $sanitizedQuery = $this->sanitizeArray($queryParams);
            $request->query->replace($sanitizedQuery);
        }

        // Sanitize POST data
        $postData = $request->post();
        if (! empty($postData)) {
            $sanitizedPost = $this->sanitizeArray($postData);
            $request->request->replace($sanitizedPost);
        }

        // Sanitize JSON data if present
        if ($request->isJson()) {
            $jsonData = $request->json()->all();
            if (! empty($jsonData)) {
                $sanitizedJson = $this->sanitizeArray($jsonData);
                $request->json()->replace($sanitizedJson);
            }
        }

        // Sanitize route parameters
        $routeParams = $request->route() ? $request->route()->parameters() : [];
        if (! empty($routeParams)) {
            $sanitizedRoute = $this->sanitizeArray($routeParams);
            foreach ($sanitizedRoute as $key => $value) {
                $request->route()->setParameter($key, $value);
            }
        }
    }

    /**
     * Recursively sanitize an array of data.
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Sanitize key
            $sanitizedKey = $this->sanitizeString($key);

            if (is_array($value)) {
                // Recursively sanitize nested arrays
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                // Sanitize string values
                $sanitized[$sanitizedKey] = $this->sanitizeString($value);
            } else {
                // Keep non-string values as-is (numbers, booleans, etc.)
                $sanitized[$sanitizedKey] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a string value to prevent XSS and injection attacks.
     */
    private function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Convert special characters to HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);

        // Remove potentially dangerous SQL keywords (basic protection)
        $sqlKeywords = [
            'SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'EXEC', 'EXECUTE', 'UNION', 'JOIN', 'WHERE', 'FROM', 'INTO',
            'SCRIPT', 'JAVASCRIPT', 'VBSCRIPT', 'ONLOAD', 'ONERROR',
        ];

        foreach ($sqlKeywords as $keyword) {
            $value = preg_replace('/\b'.preg_quote($keyword, '/').'\b/i', '', $value);
        }

        // Remove script tags
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $value);

        // Remove event handlers
        $value = preg_replace('/\bon\w+\s*=/i', '', $value);

        return trim($value);
    }
}
