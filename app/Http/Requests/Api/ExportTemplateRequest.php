<?php

namespace App\Http\Requests\Api;

use App\Services\TemplateImportExportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * Export Template Request Validation
 *
 * Validates template export requests with format and option parameters
 */
class ExportTemplateRequest extends FormRequest
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
            'template_ids' => 'required|array|min:1|max:50',
            'template_ids.*' => 'required|integer|exists:templates,id',
            'format' => ['required', 'string', Rule::in(array_keys($this->getSupportedFormats()))],
            'options' => 'nullable|array',
            'options.include_assets' => 'nullable|boolean',
            'options.include_dependencies' => 'nullable|boolean',
            'options.compress' => 'nullable|boolean',
            'options.filename' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9\-_\.]+$/',
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
            'template_ids.required' => 'At least one template must be selected for export.',
            'template_ids.array' => 'Template IDs must be provided as an array.',
            'template_ids.min' => 'At least one template must be selected.',
            'template_ids.max' => 'Cannot export more than 50 templates at once.',
            'template_ids.*.exists' => 'One or more selected templates do not exist or are not accessible.',
            'format.required' => 'Export format is required.',
            'format.in' => 'Selected export format is not supported.',
            'options.array' => 'Export options must be provided as an object.',
            'options.filename.regex' => 'Filename can only contain letters, numbers, hyphens, underscores, and dots.',
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
            'template_ids' => 'template selection',
            'template_ids.*' => 'template',
            'format' => 'export format',
            'options.include_assets' => 'include assets option',
            'options.include_dependencies' => 'include dependencies option',
            'options.compress' => 'compression option',
            'options.filename' => 'export filename',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure template_ids is an array
        if (is_string($this->template_ids)) {
            $this->merge([
                'template_ids' => explode(',', $this->template_ids)
            ]);
        }

        // Set default options if not provided
        $this->merge([
            'options' => array_merge([
                'include_assets' => true,
                'include_dependencies' => true,
                'compress' => false,
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
            $this->validateTemplateAccess($validator);
            $this->validateExportOptions($validator);
        });
    }

    /**
     * Validate that user has access to all selected templates.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateTemplateAccess($validator): void
    {
        if ($this->has('template_ids') && is_array($this->template_ids)) {
            $userId = Auth::id();

            foreach ($this->template_ids as $templateId) {
                $template = \App\Models\Template::find($templateId);

                if (!$template) {
                    $validator->errors()->add('template_ids', "Template with ID {$templateId} does not exist.");
                    continue;
                }

                // Check if template belongs to user's tenant
                if ($template->tenant_id !== Auth::user()->tenant_id && !$this->isAdmin()) {
                    $validator->errors()->add('template_ids', "You do not have access to template with ID {$templateId}.");
                }
            }
        }
    }

    /**
     * Validate export options for the selected format.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateExportOptions($validator): void
    {
        $format = $this->format;
        $options = $this->options ?? [];

        // Validate format-specific options
        if ($format === 'xml' && isset($options['compress']) && $options['compress']) {
            $validator->errors()->add('options.compress', 'XML format does not support compression in this implementation.');
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
     * Get supported export formats with their descriptions.
     *
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return app(TemplateImportExportService::class)->getSupportedFormats();
    }

    /**
     * Get the validated export parameters.
     *
     * @return array
     */
    public function getExportParameters(): array
    {
        return [
            'template_ids' => $this->validated()['template_ids'],
            'format' => $this->validated()['format'],
            'options' => $this->validated()['options'] ?? [],
        ];
    }
}