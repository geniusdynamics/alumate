<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when template security validation fails
 */
class TemplateSecurityException extends Exception
{
    protected array $securityIssues = [];

    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param array $securityIssues
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = "Template contains security issues",
        array $securityIssues = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->securityIssues = $securityIssues;

        if (!empty($securityIssues)) {
            $issues = implode(', ', $securityIssues);
            $message .= ": {$issues}";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get security issues
     *
     * @return array
     */
    public function getSecurityIssues(): array
    {
        return $this->securityIssues;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        \Illuminate\Support\Facades\Log::warning('Template security validation failed: ' . $this->getMessage(), [
            'exception' => get_class($this),
            'security_issues' => $this->securityIssues,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ]);
    }

    /**
     * Render the exception for API responses
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'template_security_violation',
            'security_issues' => $this->securityIssues,
            'status_code' => 422,
        ], 422);
    }
}