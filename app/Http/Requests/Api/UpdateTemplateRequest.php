<?php

namespace App\Http\Requests\Api;

use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check() && $this->user()->can('update', $this->route('template'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $templateId = $this->route('template') ? $this->route('template')->id : null;

        // Get model validation rules but remove tenant_id and user fields
        $rules = Template::getValidationRules();

        // Make fields optional for updates
        foreach ($rules as $field => $rule) {
            if ($field !== 'tenant_id') { // Keep tenant_id required for security
                if (is_string($rule)) {
                    $rules[$field] = 'nullable|' . $rule;
                } elseif (is_array($rule)) {
                    array_unshift($rules[$field], 'nullable');
                }
            }
        }

        // Make slug unique excluding current template
        if ($templateId) {
            $rules['slug'] = [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('templates')->ignore($templateId),
            ];
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9]+$/';
        }

        // Custom validation for structure validation
        if ($this->has('structure')) {
            $rules['structure'] = 'nullable|array';
            $rules['structure.sections'] = 'required_with:structure|array';
            $rules['structure.sections.*.type'] = 'required|string|max:255';
            $rules['structure.sections.*.config'] = 'nullable|array';
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'A template with this slug already exists.',
            'category.in' => 'Selected category is invalid.',
            'audience_type.in' => 'Selected audience type is invalid.',
            'campaign_type.in' => 'Selected campaign type is invalid.',
            'structure.array' => 'Template structure must be a valid array.',
            'structure.sections.required_with' => 'Template sections are required when structure is provided.',
            'structure.sections.array' => 'Template sections must be an array.',
            'structure.sections.*.type.required' => 'Each section must have a type.',
            'structure.sections.*.config.array' => 'Section configuration must be an array if provided.',
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
            'category' => 'template category',
            'audience_type' => 'audience type',
            'campaign_type' => 'campaign type',
            'is_active' => 'active status',
            'is_premium' => 'premium status',
            'preview_image' => 'preview image',
            'preview_url' => 'preview URL',
            'default_config' => 'default configuration',
            'performance_metrics' => 'performance metrics',
        ];
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
            // Validate only if structure is being updated
            if ($this->has('structure') && !empty($this->structure)) {
                try {
                    $this->validateTemplateStructure($validator);
                } catch (\Exception $e) {
                    $validator->errors()->add('structure', 'Template structure validation failed: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Validate template structure against security and format rules.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @throws \Exception
     */
    private function validateTemplateStructure($validator): void
    {
        $structure = $this->structure;

        if (!isset($structure['sections']) || !is_array($structure['sections'])) {
            throw new \Exception('Template must have a sections array');
        }

        foreach ($structure['sections'] as $key => $section) {
            if (!isset($section['type'])) {
                throw new \Exception("Section {$key} must have a type");
            }

            // Validate section type
            $allowedTypes = [
                'hero', 'text', 'image', 'video', 'form', 'button',
                'statistics', 'testimonials', 'accordion', 'tabs',
                'social_proof', 'pricing', 'newsletter', 'contact',
                'gallery', 'timeline', 'faq', 'call_to_action'
            ];

            if (!in_array($section['type'], $allowedTypes)) {
                throw new \Exception("Section type '{$section['type']}' is not allowed");
            }

            // Validate config if present
            if (isset($section['config']) && is_array($section['config'])) {
                $this->validateSectionConfig($section['type'], $section['config']);
            }
        }
    }

    /**
     * Validate section configuration based on section type.
     *
     * @param string $sectionType
     * @param array $config
     * @throws \Exception
     */
    private function validateSectionConfig(string $sectionType, array $config): void
    {
        $requiredFields = match ($sectionType) {
            'hero' => ['title'],
            'text' => ['content'],
            'image' => ['url'],
            'video' => ['url'],
            'button' => ['text', 'url'],
            default => [],
        };

        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new \Exception("{$sectionType} section requires '{$field}' configuration");
            }
        }

        // Validate URLs if present
        $urlFields = ['url', 'image_url', 'background_url', 'link_url', 'video_url'];
        foreach ($urlFields as $field) {
            if (isset($config[$field]) && !empty($config[$field])) {
                if (!filter_var($config[$field], FILTER_VALIDATE_URL)) {
                    throw new \Exception("{$field} must be a valid URL");
                }
            }
        }

        // Validate email format if present
        if (isset($config['email']) && !empty($config['email'])) {
            if (!filter_var($config['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Email must be a valid email address");
            }
        }

        // Validate color format if present
        if (isset($config['color']) && !empty($config['color'])) {
            if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $config['color'])) {
                throw new \Exception("Color must be a valid hex color code");
            }
        }
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure tenant_id matches the template's tenant for security
        $template = $this->route('template');
        if ($template && $template->tenant_id) {
            $this->merge(['tenant_id' => $template->tenant_id]);
        }
    }
}