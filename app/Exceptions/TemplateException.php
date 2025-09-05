<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Base Template Exception with comprehensive tenant and context support
 *
 * This exception serves as the base class for all template-related exceptions,
 * providing enhanced error handling with tenant isolation and contextual information.
 */
class TemplateException extends Exception
{
    protected ?string $tenantId = null;
    protected array $contextualData = [];
    protected ?string $templateId = null;
    protected ?string $templateCategory = null;
    protected array $recoverySuggestion = [];
    protected ?string $errorCategory = null;
    protected ?string $severity = null;

    /**
     * Create a new Template exception instance
     *
     * @param string $message Error message
     * @param string|null $tenantId Tenant identifier
     * @param string|null $templateId Template identifier
     * @param string|null $templateCategory Template category
     * @param array $contextualData Additional context data
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "Template operation failed",
        ?string $tenantId = null,
        ?string $templateId = null,
        ?string $templateCategory = null,
        array $contextualData = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->tenantId = $tenantId;
        $this->templateId = $templateId;
        $this->templateCategory = $templateCategory;
        $this->contextualData = $contextualData;
        $this->enhanceContextWithSystemInfo();

        parent::__construct($message, $code, $previous);
    }

    /**
     * Set tenant isolation context
     *
     * @param string|null $tenantId
     * @return static
     */
    public function setTenantId(?string $tenantId): static
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Get tenant identifier
     *
     * @return string|null
     */
    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    /**
     * Set template identifier
     *
     * @param string|null $templateId
     * @return static
     */
    public function setTemplateId(?string $templateId): static
    {
        $this->templateId = $templateId;
        return $this;
    }

    /**
     * Get template identifier
     *
     * @return string|null
     */
    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    /**
     * Set template category
     *
     * @param string|null $templateCategory
     * @return static
     */
    public function setTemplateCategory(?string $templateCategory): static
    {
        $this->templateCategory = $templateCategory;
        return $this;
    }

    /**
     * Get template category
     *
     * @return string|null
     */
    public function getTemplateCategory(): ?string
    {
        return $this->templateCategory;
    }

    /**
     * Add contextual data
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function addContextualData(string $key, mixed $value): static
    {
        $this->contextualData[$key] = $value;
        return $this;
    }

    /**
     * Get contextual data
     *
     * @return array
     */
    public function getContextualData(): array
    {
        return $this->contextualData;
    }

    /**
     * Set recovery suggestion
     *
     * @param array $suggestion
     * @return static
     */
    public function setRecoverySuggestion(array $suggestion): static
    {
        $this->recoverySuggestion = $suggestion;
        return $this;
    }

    /**
     * Get recovery suggestion
     *
     * @return array
     */
    public function getRecoverySuggestion(): array
    {
        return $this->recoverySuggestion;
    }

    /**
     * Set error category
     *
     * @param string $category
     * @return static
     */
    public function setErrorCategory(string $category): static
    {
        $this->errorCategory = $category;
        return $this;
    }

    /**
     * Get error category
     *
     * @return string|null
     */
    public function getErrorCategory(): ?string
    {
        return $this->errorCategory;
    }

    /**
     * Set severity level
     *
     * @param string $severity
     * @return static
     */
    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * Get severity level
     *
     * @return string|null
     */
    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    /**
     * Create exception with contextual information
     *
     * @param string $message
     * @param array $context
     * @return static
     */
    public static function withContext(string $message, array $context = []): static
    {
        $instance = new static($message);

        // Extract tenant and template information from context
        if (isset($context['tenant_id'])) {
            $instance->setTenantId($context['tenant_id']);
        }
        if (isset($context['template_id'])) {
            $instance->setTemplateId($context['template_id']);
        }
        if (isset($context['template_category'])) {
            $instance->setTemplateCategory($context['template_category']);
        }
        if (isset($context['recovery_suggestion'])) {
            $instance->setRecoverySuggestion($context['recovery_suggestion']);
        }
        if (isset($context['error_category'])) {
            $instance->setErrorCategory($context['error_category']);
        }
        if (isset($context['severity'])) {
            $instance->setSeverity($context['severity']);
        }

        // Add remaining context as additional data
        foreach ($context as $key => $value) {
            if (!in_array($key, ['tenant_id', 'template_id', 'template_category', 'recovery_suggestion', 'error_category', 'severity'])) {
                $instance->addContextualData($key, $value);
            }
        }

        return $instance;
    }

    /**
     * Enhance context with system information
     *
     * @return void
     */
    protected function enhanceContextWithSystemInfo(): void
    {
        $this->contextualData['environment'] = config('app.env');
        $this->contextualData['user_id'] = Auth::check() ? Auth::id() : null;
        $this->contextualData['current_time'] = now()->toISOString();
        $this->contextualData['request_id'] = $this->generateRequestId();
        $this->contextualData['memory_usage'] = memory_get_peak_usage(true);
        $this->contextualData['php_version'] = PHP_VERSION;

        if (request()->has('template_id')) {
            $this->templateId = $this->templateId ?? request()->template_id;
        }
        if (request()->has('tenant_id')) {
            $this->tenantId = $this->tenantId ?? request()->tenant_id;
        }
    }

    /**
     * Generate unique request identifier
     *
     * @return string
     */
    protected function generateRequestId(): string
    {
        // Try to get existing request ID from headers
        $existingId = request()->header('X-Request-ID');
        if ($existingId) {
            return $existingId;
        }

        // Generate new request ID
        return 'req_' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
    }

    /**
     * Get detailed exception information for logging
     *
     * @return array
     */
    public function getDetailedInfo(): array
    {
        return [
            'exception' => get_class($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'tenant_id' => $this->tenantId,
            'template_id' => $this->templateId,
            'template_category' => $this->templateCategory,
            'error_category' => $this->errorCategory,
            'severity' => $this->severity,
            'contextual_data' => $this->contextualData,
            'recovery_suggestion' => $this->recoverySuggestion,
            'trace' => config('app.debug') ? $this->getTraceAsString() : null,
            'request_id' => $this->contextualData['request_id'] ?? null,
            'user_id' => $this->contextualData['user_id'] ?? null,
            'ip_address' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];
    }

    /**
     * Convert exception to user-friendly JSON response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(): \Illuminate\Http\JsonResponse
    {
        $statusCode = $this->determineStatusCode();
        $response = [
            'message' => $this->getUserFriendlyMessage(),
            'error' => $this->errorCategory ?? 'template_error',
            'status_code' => $statusCode,
            'error_id' => $this->contextualData['request_id'] ?? uniqid('err_'),
            'timestamp' => $this->contextualData['current_time'] ?? null,
        ];

        if (!empty($this->recoverySuggestion)) {
            $response['recovery_suggestion'] = $this->recoverySuggestion;
        }

        if (!empty($this->contextualData['template_id'])) {
            $response['template_id'] = $this->contextualData['template_id'];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get user-friendly error message
     *
     * @return string
     */
    protected function getUserFriendlyMessage(): string
    {
        if ($this->errorCategory === 'security') {
            return 'Security violation detected. Your request cannot be processed.';
        }

        if ($this->errorCategory === 'validation') {
            return 'The provided template data is invalid. Please correct the errors and try again.';
        }

        if ($this->errorCategory === 'not_found') {
            return 'The requested template was not found. It may have been moved or deleted.';
        }

        return $this->getMessage();
    }

    /**
     * Determine HTTP status code based on error category
     *
     * @return int
     */
    protected function determineStatusCode(): int
    {
        switch ($this->errorCategory) {
            case 'not_found':
                return 404;
            case 'validation':
            case 'security':
                return 422;
            case 'permission':
                return 403;
            default:
                return $this->getCode() ?: 500;
        }
    }

    /**
     * Report the exception (template for child classes to override)
     *
     * @return void
     */
    public function report(): void
    {
        // Child classes should override this method for specific reporting behavior
        \Illuminate\Support\Facades\Log::error('Template exception occurred: ' . $this->getMessage(), $this->getDetailedInfo());
    }
}