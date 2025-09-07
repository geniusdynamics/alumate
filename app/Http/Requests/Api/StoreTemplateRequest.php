<?php

namespace App\Http\Requests\Api;

use App\Models\Template;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
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
        $rules = Template::getValidationRules();

        // Remove tenant_id and related user fields as they are handled internally
        unset($rules['tenant_id'], $rules['created_by'], $rules['updated_by']);

        // Make slug optional for creation (auto-generated if not provided)
        $rules['slug'][] = 'nullable';

        // Add custom validation for structure validation
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
            'name.required' => 'Template name is required.',
            'name.max' => 'Template name cannot be longer than :max characters.',
            'category.required' => 'Template category is required.',
            'category.in' => 'Selected category is invalid.',
            'audience_type.required' => 'Audience type is required.',
            'audience_type.in' => 'Selected audience type is invalid.',
            'campaign_type.required' => 'Campaign type is required.',
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
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'seo_keywords' => 'SEO keywords',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Set default tenant_id from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            $this->merge(['tenant_id' => auth()->user()->tenant_id]);
        }
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
            // Validate template structure securely
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

        if (count($structure['sections']) === 0) {
            throw new \Exception('Template must have at least one section');
        }

        foreach ($structure['sections'] as $key => $section) {
            if (!isset($section['type'])) {
                throw new \Exception("Section {$key} must have a type");
            }

            // Validate section type
            $allowedTypes = [
                'hero', 'text', 'image', 'video', 'form', 'button',
                'statistics', 'testimonials', 'accordion', 'tabs',
                'social_proof', 'pricing', 'newsletter', 'contact'
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
            'image' => ['url', 'alt'],
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
        $urlFields = ['url', 'image_url', 'background_url', 'link_url'];
        foreach ($urlFields as $field) {
            if (isset($config[$field]) && !empty($config[$field])) {
                if (!filter_var($config[$field], FILTER_VALIDATE_URL)) {
                    throw new \Exception("{$field} must be a valid URL");
                }
            }
        }
    }
}