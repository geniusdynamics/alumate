<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a requested template is not found
 */
class TemplateNotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "Template not found", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        // Log the error with context
        \Illuminate\Support\Facades\Log::warning('Template not found: ' . $this->getMessage(), [
            'exception' => get_class($this),
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
            'error' => 'template_not_found',
            'status_code' => 404,
        ], 404);
    }
}