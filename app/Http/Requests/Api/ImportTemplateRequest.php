<?php

namespace App\Http\Requests\Api;

use App\Services\TemplateImportExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * Import Template Request Validation
 *
 * Validates template import requests with file uploads and options
 */
class ImportTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:json,yaml,xml|max:10240', // 10MB max
            'format' => ['nullable', 'string', Rule::in(array_keys($this->getSupportedFormats()))],
            'options' => 'nullable|array',
            'options.override_existing' => 'nullable|boolean',
            'options.skip_validation' => 'nullable|boolean',
            'options.tenant_id' => 'nullable|integer|exists:tenants,id',
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
            'file.required' => 'Import file is required.',
            'file.file' => 'Upload must be a valid file.',
            'file.mimes' => 'File must be one of the supported formats: JSON, YAML, or XML.',
            'file.max' => 'File size cannot exceed 10MB.',
            'format.in' => 'Selected format is not supported.',
            'options.array' => 'Import options must be provided as an object.',
            'options.tenant_id.exists' => 'Selected tenant does not exist.',
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
            'file' => 'import file',
            'format' => 'file format',
            'options.override_existing' => 'override existing option',
            'options.skip_validation' => 'skip validation option',
            'options.tenant_id' => 'tenant selection',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Auto-detect format from file extension if not provided
        if (!$this->has('format') && $this->hasFile('file')) {
            $originalName = $this->file('file')->getClientOriginalName();
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            $formatMap = [
                'json' => 'json',
                'yaml' => 'yaml',
                'yml' => 'yaml',
                'xml' => 'xml',
            ];

            if (isset($formatMap[$extension])) {
                $this->merge(['format' => $formatMap[$extension]]);
            }
        }

        // Set default tenant if not provided and user has tenant access
        $this->merge([
            'options' => array_merge([
                'override_existing' => false,
                'skip_validation' => false,
                'tenant_id' => Auth::user()->tenant_id ?? null,
            ], $this->options ?? [])
        ]);
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
            $this->validateTenantAccess($validator);
            $this->validateFileContent($validator);
        });
    }

    /**
     * Validate tenant access permissions.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateTenantAccess($validator): void
    {
        $user = Auth::user();
        $requestedTenantId = $this->options['tenant_id'] ?? null;

        if ($requestedTenantId && !$this->isAdmin()) {
            if ($user->tenant_id !== $requestedTenantId) {
                $validator->errors()->add('options.tenant_id', 'You do not have permission to import templates for the selected tenant.');
            }
        }
    }

    /**
     * Validate the content of the uploaded file.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateFileContent($validator): void
    {
        if (!$this->hasFile('file') || !$validator->errors()->has('file')) {
            try {
                $file = $this->file('file');
                $content = $file->get();

                // Basic content validation
                if (empty(trim($content))) {
                    $validator->errors()->add('file', 'The uploaded file is empty.');
                    return;
                }

                $format = $this->input('format', 'json');

                // Validate format-specific structure
                switch ($format) {
                    case 'json':
                        $this->validateJsonContent($validator, $content);
                        break;
                    case 'xml':
                        $this->validateXmlContent($validator, $content);
                        break;
                    case 'yaml':
                        $this->validateYamlContent($validator, $content);
                        break;
                }

            } catch (\Exception $e) {
                $validator->errors()->add('file', 'Failed to read the uploaded file: ' . $e->getMessage());
            }
        }
    }

    /**
     * Validate JSON file content.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $content
     * @return void
     */
    private function validateJsonContent($validator, string $content): void
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $validator->errors()->add('file', 'Invalid JSON format: ' . json_last_error_msg());
            return;
        }

        if (!is_array($data)) {
            $validator->errors()->add('file', 'JSON file must contain an object or array at the root level.');
            return;
        }

        $this->validateTemplateStructure($validator, $data);
    }

    /**
     * Validate XML file content.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $content
     * @return void
     */
    private function validateXmlContent($validator, string $content): void
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);

        if ($xml === false) {
            $errors = libxml_get_errors();
            $message = 'Invalid XML format';
            if (!empty($errors)) {
                $message .= ': ' . $errors[0]->message;
            }
            $validator->errors()->add('file', $message);
            return;
        }

        // Convert to array for common validation
        $data = json_decode(json_encode($xml), true);
        $this->validateTemplateStructure($validator, $data);
    }

    /**
     * Validate YAML file content.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $content
     * @return void
     */
    private function validateYamlContent($validator, string $content): void
    {
        try {
            $data = \Symfony\Component\Yaml\Yaml::parse($content);

            if (!is_array($data)) {
                $validator->errors()->add('file', 'YAML file must contain an object or array at the root level.');
                return;
            }

            $this->validateTemplateStructure($validator, $data);
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
            $validator->errors()->add('file', 'Invalid YAML format: ' . $e->getMessage());
        }
    }

    /**
     * Validate common template structure across formats.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param array $data
     * @return void
     */
    private function validateTemplateStructure($validator, array $data): void
    {
        // Check for minimum required structure
        if (!isset($data['version'])) {
            $validator->errors()->add('file', 'Import file is missing version information.');
        }

        if (!isset($data['templates']) || !is_array($data['templates'])) {
            $validator->errors()->add('file', 'Import file must contain a templates array.');
        }

        if (isset($data['templates']) && is_array($data['templates']) && empty($data['templates'])) {
            $validator->errors()->add('file', 'Import file contains no templates.');
        }

        // Validate template count limits
        if (isset($data['templates']) && count($data['templates']) > 50) {
            $validator->errors()->add('file', 'Import file cannot contain more than 50 templates.');
        }
    }

    /**
     * Check if the current user has admin privileges.
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        // Check if user has admin role - adjust based on your user model/roles system
        return isset($user->role) && in_array($user->role, ['admin', 'super-admin']);
    }

    /**
     * Get supported import formats with their descriptions.
     *
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return app(TemplateImportExportService::class)->getSupportedFormats();
    }

    /**
     * Get the validated import parameters.
     *
     * @return array
     */
    public function getImportParameters(): array
    {
        return [
            'file' => $this->file('file'),
            'format' => $this->validated()['format'] ?? 'json',
            'options' => $this->validated()['options'] ?? [],
        ];
    }

    /**
     * Get the file content as a string.
     *
     * @return string
     */
    public function getFileContent(): string
    {
        return $this->file('file')->get();
    }
}