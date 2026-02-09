<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions but avoid exposing sensitive information
            Log::error('Application Error', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e),
                'request_url' => request()->fullUrl() ?? 'unknown',
                'request_method' => request()->method() ?? 'unknown',
                'user_id' => auth()->id() ?? 'guest',
                'ip_address' => request()->ip() ?? 'unknown',
            ]);
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e): Response|JsonResponse
    {
        // Prevent information disclosure in production
        if (!App::isLocal() && !$this->shouldReport($e)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An error occurred while processing your request.',
                    'error_code' => 'INTERNAL_ERROR',
                ], 500);
            }

            return response()->view('errors.500', [], 500);
        }

        // For validation errors, return appropriate response
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->validator->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                ], 422);
            }
        }

        // For authorization errors
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'You are not authorized to perform this action.',
                    'error_code' => 'UNAUTHORIZED',
                ], 403);
            }
        }

        // For authentication errors
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }
        }

        // For model not found errors
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The requested resource was not found.',
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
        }

        // For general HTTP exceptions
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'An error occurred.',
                    'error_code' => 'HTTP_ERROR_'.$e->getStatusCode(),
                ], $e->getStatusCode());
            }
        }

        // Call the parent render method for other exceptions
        return parent::render($request, $e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e): bool
    {
        return ! $this->shouldntReport($e);
    }
}
