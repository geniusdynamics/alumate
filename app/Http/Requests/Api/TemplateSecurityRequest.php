<?php

namespace App\Http\Requests\Api;

use App\Exceptions\TemplateSecurityException;
use App\Services\TemplateSecurityValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Template Security Request
 *
 * Comprehensive validation and security checks for template data
 */
class TemplateSecurityRequest extends FormRequest
{
    protected TemplateSecurityValidator $securityValidator;

    /**
     * Constructor with dependency injection
     */
    public function __construct(TemplateSecurityValidator $securityValidator)
    {
        $this->securityValidator = $securityValidator;
        parent::__construct();
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Basic template validation
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\-_\s]+$/',
            'category' => ['required', Rule::in(['marketing', 'sales', 'support', 'educational', 'promotional'])],
            'audience_type' => ['required', Rule::in(['b2b', 'b2c', 'mixed'])],
            'campaign_type' => ['required', Rule::in(['awareness', 'conversion', 'retention', 'education'])],

            // Structure validation with nested rules
            'structure' => 'nullable|array',
            'structure.sections' => 'required_with:structure|array|min:1|max:20',
            'structure.sections.*.type' => 'required|string|max:100',
            'structure.sections.*.config' => 'nullable|array|max:100',
            'structure.sections.*.config.title' => 'nullable|string|max:500',
            'structure.sections.*.config.content' => 'nullable|string|max:10000',
            'structure.sections.*.config.url' => 'nullable|url|regex:/^(https?):\/\/[^\s\/$.?#].[^\s]*$/i',
            'structure.sections.*.config.image_url' => 'nullable|url|regex:/^(https?):\/\/[^\s\/$.?#].[^\s]*$/i',
            'structure.sections.*.config.background_url' => 'nullable|url|regex:/^(https?):\/\/[^\s\/$.?#].[^\s]*$/i',
            'structure.sections.*.config.button_text' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\-_\s]+$/',
            'structure.sections.*.config.link_url' => 'nullable|url|regex:/^(https?):\/\/[^\s\/$.?#].[^\s]*$/i',

            // Security-specific validations
            'metadata' => 'nullable|array',
            'metadata.keywords' => 'nullable|array|max:20',
            'metadata.keywords.*' => 'string|max:100|regex:/^[a-zA-Z0-9\-_\s]+$/',
            'metadata.description' => 'nullable|string|max:500',
            'metadata.author' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9\-_\s]+$/',

            // Tenant validation (internal, handled by authorization)
            'tenant_id' => 'nullable|integer|min:1',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required.',
            'name.regex' => 'Template name can only contain letters, numbers, spaces, dashes, and underscores.',
            'category.required' => 'Template category is required.',
            'category.in' => 'Selected category is not valid.',
            'audience_type.required' => 'Audience type is required.',
            'audience_type.in' => 'Selected audience type is not valid.',
            'campaign_type.required' => 'Campaign type is required.',
            'campaign_type.in' => 'Selected campaign type is not valid.',
            'structure.array' => 'Template structure must be a valid array.',
            'structure.sections.required_with' => 'Template sections are required when structure is provided.',
            'structure.sections.array' => 'Template sections must be an array.',
            'structure.sections.min' => 'Template must have at least one section.',
            'structure.sections.max' => 'Template cannot have more than 20 sections.',
            'structure.sections.*.type.required' => 'Each section must have a valid type.',
            'structure.sections.*.config.title.max' => 'Section title is too long.',
            'structure.sections.*.config.content.max' => 'Section content is too long.',
            'structure.sections.*.config.url.url' => 'URL must be a valid URL.',
            'structure.sections.*.config.url.regex' => 'URL must use HTTP or HTTPS protocol.',
            'metadata.keywords.max' => 'Too many keywords provided.',
            'metadata.keywords.*.regex' => 'Keywords can only contain letters, numbers, spaces, dashes, and underscores.',
            'metadata.description.max' => 'Description is too long.',
            'metadata.author.regex' => 'Author can only contain letters, numbers, spaces, dashes, and underscores.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'structure.sections.*.type' => 'section type',
            'structure.sections.*.config.title' => 'section title',
            'structure.sections.*.config.content' => 'section content',
            'structure.sections.*.config.url' => 'URL',
            'structure.sections.*.config.image_url' => 'image URL',
            'structure.sections.*.config.background_url' => 'background URL',
            'structure.sections.*.config.button_text' => 'button text',
            'structure.sections.*.config.link_url' => 'link URL',
            'metadata.keywords' => 'keywords',
            'metadata.description' => 'description',
            'metadata.author' => 'author',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input data
        $data = $this->all();

        // Clean string inputs
        $data = $this->sanitizeInputData($data);

        // Set tenant_id from authenticated user
        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->hasRole(['super-admin', 'admin'])) {
                // For non-admin users, force tenant isolation
                $data['tenant_id'] = $user->tenant_id;
            }
        }

        $this->merge($data);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            try {
                // Perform comprehensive security validation
                $this->validateTemplateSecurity();

            } catch (TemplateSecurityException $e) {
                $securityIssues = $e->getSecurityIssues();

                // Add security violations as validation errors
                foreach ($securityIssues as $issue) {
                    $field = $issue['context'] ?? 'structure';
                    $message = $this->getSecurityViolationMessage($issue);

                    // Add errors to the validator
                    if (isset($issue['section'])) {
                        $field = "structure.sections.{$issue['section']}";
                    }

                    $validator->errors()->add($field, $message);
                }
            }

            // Additional custom validations
            $this->performCustomValidations($validator);
        });
    }

    /**
     * Perform comprehensive TemplateSecurity validation using the service
     *
     * @throws TemplateSecurityException
     */
    protected function validateTemplateSecurity(): void
    {
        $data = $this->validated();

        // Skip security validation if no structure provided
        if (!isset($data['structure'])) {
            return;
        }

        // Validate template structure with comprehensive security checks
        $this->securityValidator->validate($data['structure']);

        // Additional security checks for the entire request
        $this->performSecurityChecks($data);
    }

    /**
     * Perform additional security checks on the data
     *
     * @param array $data
     * @throws TemplateSecurityException
     */
    protected function performSecurityChecks(array $data): void
    {
        $issues = [];

        // Check for potentially dangerous input patterns
        $suspiciousPatterns = [
            '/\b(?:admin|root)\b/i' => 'suspicious_terms_detected',
            '/\b(?:password|credential|secret)\b/i' => 'sensitive_terms_detected',
            '/<script[^>]*>[\s\S]*?<\/script>/i' => 'script_tags_detected',
            '/<iframe[^>]*>[\s\S]*?<\/iframe>/i' => 'iframe_tags_detected',
            '/on\w+\s*=/i' => 'event_handlers_detected',
            '/javascript:/i' => 'javascript_href_detected',
        ];

        $dataString = json_encode($data);

        foreach ($suspiciousPatterns as $pattern => $issueType) {
            if (preg_match($pattern, $dataString)) {
                $issues[] = [
                    'type' => $issueType,
                    'pattern' => $pattern,
                    'severity' => 'high',
                    'context' => 'input_validation'
                ];
            }
        }

        if (!empty($issues)) {
            throw new TemplateSecurityException(
                "Template security validation failed",
                $issues
            );
        }
    }

    /**
     * Sanitize input data
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeInputData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Basic sanitization while preserving valid HTML
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeInputData($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize string input
     *
     * @param string $string
     * @return string
     */
    protected function sanitizeString(string $string): string
    {
        // Remove potential null bytes
        $string = str_replace("\0", "", $string);

        // Trim and normalize whitespace
        $string = trim($string);

        // Decode HTML entities that might hide malicious content
        $string = htmlspecialchars_decode($string, ENT_QUOTES);

        // Re-encode potentially dangerous characters
        $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');

        return $string;
    }

    /**
     * Get user-friendly message for security violations
     *
     * @param array $issue
     * @return string
     */
    protected function getSecurityViolationMessage(array $issue): string
    {
        $messages = [
            'script_injection' => 'Script injection detected. JavaScript code is not allowed.',
            'event_handler' => 'Event handlers detected in template content.',
            'javascript_href' => 'JavaScript links are not allowed.',
            'iframe_injection' => 'Iframe injection detected. Iframes are not allowed.',
            'object_injection' => 'Object injection detected.',
            'embed_injection' => 'Embed injection detected.',
            'vbscript_href' => 'VBScript links are not allowed.',
            'data_uri_html' => 'Data URI HTML detected. This may be dangerous.',
            'css_expression' => 'CSS expressions detected.',
            'javascript_execution' => 'JavaScript execution patterns detected.',
            'dangerous_dom_manipulation' => 'Dangerous DOM manipulation patterns detected.',
            'location_manipulation' => 'Location manipulation detected.',
            'storage_access' => 'Storage access patterns detected.',
            'suspicious_terms_detected' => 'Suspicious terms detected in content.',
            'sensitive_terms_detected' => 'Sensitive terms detected. Please avoid including passwords or credentials.',
            'script_tags_detected' => 'Script tags detected.',
        ];

        return $messages[$issue['type']] ?? 'Security violation detected.';
    }

    /**
     * Perform additional custom validations
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function performCustomValidations($validator): void
    {
        // Check for duplicate section types that shouldn't be duplicated
        if ($this->has('structure.sections')) {
            $types = array_column($this->input('structure.sections'), 'type');

            // Some section types should not be duplicated
            $uniqueTypes = ['hero', 'footer'];
            $counts = array_count_values($types);

            foreach ($uniqueTypes as $type) {
                if (($counts[$type] ?? 0) > 1) {
                    $validator->errors()->add(
                        'structure.sections',
                        "Section type '{$type}' can only be used once per template."
                    );
                }
            }
        }

        // Validate URL domains for security
        $this->validateUrlSecurity($validator);
    }

    /**
     * Validate URL security
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    protected function validateUrlSecurity($validator): void
    {
        $sections = $this->input('structure.sections', []);

        foreach ($sections as $index => $section) {
            if (isset($section['config'])) {
                $urls = array_filter([
                    $section['config']['url'] ?? null,
                    $section['config']['image_url'] ?? null,
                    $section['config']['background_url'] ?? null,
                    $section['config']['link_url'] ?? null,
                ]);

                foreach ($urls as $url) {
                    if (!$this->isUrlAllowed($url)) {
                        $validator->errors()->add(
                            "structure.sections.{$index}.config",
                            "The provided URL is not allowed or may be unsafe."
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * Check if URL is allowed
     *
     * @param string $url
     * @return bool
     */
    protected function isUrlAllowed(string $url): bool
    {
        $parsed = parse_url($url);

        if (!$parsed || !isset($parsed['host'])) {
            // Allow relative URLs
            return true;
        }

        $host = strtolower($parsed['host']);

        // Block common malicious domains (can be extended)
        $blockedDomains = [
            'localhost', '0.0.0.0', '127.0.0.1',
            '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
        ];

        foreach ($blockedDomains as $blocked) {
            if (strpos($host, $blocked) !== false) {
                return false;
            }
        }

        // Allow HTTPS only for external domains
        if (!isset($parsed['scheme'])) {
            return false;
        }

        return $parsed['scheme'] === 'https';
    }
}