<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Collection;

/**
 * Exception thrown when template validation fails
 */
class TemplateValidationException extends Exception
{
    protected Collection $errors;

    /**
     * Create a new exception instance
     *
     * @param string|array $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string|array $message = "Template validation failed", int $code = 0, ?\Throwable $previous = null)
    {
        if (is_array($message)) {
            $this->errors = collect($message);
            $message = "Template validation failed: " . json_encode($message, JSON_PRETTY_PRINT);
        } else {
            $this->errors = collect([$message]);
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get validation errors
     *
     * @return Collection
     */
    public function getErrors(): Collection
    {
        return $this->errors;
    }

    /**
     * Report the exception
     */
    public function report(): void
    {
        \Illuminate\Support\Facades\Log::error('Template validation failed: ' . $this->getMessage(), [
            'exception' => get_class($this),
            'errors' => $this->errors->toArray(),
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
            'message' => 'Template validation failed',
            'error' => 'template_validation_failed',
            'errors' => $this->errors->toArray(),
            'status_code' => 422,
        ], 422);
    }
}