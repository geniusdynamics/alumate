<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InputSanitizerMiddleware
{
    /**
     * Handle an incoming request to sanitize inputs and prevent common attacks.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Sanitize inputs to prevent XSS and other injection attacks
        $this->sanitizeInputs($request);

        return $next($request);
    }

    /**
     * Sanitize request inputs to prevent XSS and other injection attacks
     */
    private function sanitizeInputs(Request $request): void
    {
        // Get all inputs
        $inputs = $request->all();

        // Sanitize each input
        $sanitizedInputs = $this->sanitizeArray($inputs);

        // Replace the request inputs with sanitized versions
        foreach ($sanitizedInputs as $key => $value) {
            $request->merge([$key => $value]);
        }
    }

    /**
     * Recursively sanitize an array of inputs
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } else {
                $sanitized[$key] = $this->sanitizeValue($value);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize a single value
     */
    private function sanitizeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove potentially dangerous characters/sequences
        $value = strip_tags($value); // Remove HTML tags
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); // Convert special chars
        $value = stripslashes($value); // Remove backslashes

        // Additional sanitization for common attack vectors
        $value = preg_replace('/javascript:/i', 'javascript&#58;', $value); // Neutralize JS
        $value = preg_replace('/vbscript:/i', 'vbscript&#58;', $value); // Neutralize VBScript
        $value = preg_replace('/onload=/i', 'onload&#61;', $value); // Neutralize onload
        $value = preg_replace('/onerror=/i', 'onerror&#61;', $value); // Neutralize onerror

        return $value;
    }
}
