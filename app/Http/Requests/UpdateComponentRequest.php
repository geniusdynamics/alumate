<?php

namespace App\Http\Requests;

use App\Models\Component;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateComponentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return false;
        }

        // Check if component exists and belongs to user's tenant
        $component = $this->route('component');
        if (!$component instanceof Component) {
            return false;
        }

        return $component->tenant_id === Auth::user()->tenant_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $component = $this->route('component');
        $componentId = $component ? $component->id : null;

        return [
            'theme_id' => 'nullable|exists:component_themes,id',
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'unique:components,name,' . $componentId . ',id,tenant_id,' . Auth::user()->tenant_id
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:components,slug,' . $componentId . ',id,tenant_id,' . Auth::user()->tenant_id
            ],
            'category' => ['sometimes', Rule::in(['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'])],
            'type' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:1000',
            'config' => 'nullable|array',
            'metadata' => 'nullable|array',
            'version' => 'sometimes|string|max:20',
            'is_active' => 'sometimes|boolean',
            
            // Category-specific validation rules (only when category is being updated)
            'config.headline' => 'required_with:category|string|max:255',
            'config.subheading' => 'nullable|string|max:500',
            'config.cta_text' => 'required_with:category|string|max:50',
            'config.cta_url' => 'required_with:category|string|url|max:255',
            'config.background_type' => 'required_with:category|in:image,video,gradient',
            'config.show_statistics' => 'boolean',
            
            'config.fields' => 'required_with:category|array',
            'config.fields.*.type' => 'required|in:text,email,phone,select,checkbox,textarea',
            'config.fields.*.label' => 'required|string|max:255',
            'config.fields.*.required' => 'boolean',
            'config.submit_text' => 'string|max:50',
            'config.success_message' => 'string|max:500',
            'config.crm_integration' => 'boolean',
            
            'config.testimonials' => 'required_with:category|array',
            'config.testimonials.*.quote' => 'required|string|max:500',
            'config.testimonials.*.author' => 'required|string|max:100',
            'config.testimonials.*.title' => 'nullable|string|max:100',
            'config.testimonials.*.company' => 'nullable|string|max:100',
            'config.testimonials.*.photo' => 'nullable|string|url',
            
            'config.metrics' => 'required_with:category|array',
            'config.metrics.*.label' => 'required|string|max:100',
            'config.metrics.*.value' => 'required|numeric',
            'config.metrics.*.suffix' => 'nullable|string|max:10',
            'config.animation_type' => 'in:counter,progress,chart',
            'config.trigger_on_scroll' => 'boolean',
            
            'config.buttons' => 'required_with:category|array',
            'config.buttons.*.text' => 'required|string|max:50',
            'config.buttons.*.url' => 'required|string|url|max:255',
            'config.buttons.*.style' => 'in:primary,secondary,outline,text',
            
            'config.sources' => 'required_with:category|array',
            'config.sources.*.url' => 'required|string|url',
            'config.sources.*.type' => 'in:image,video',
            'config.sources.*.alt' => 'nullable|string|max:255',
            'config.lazy_load' => 'boolean',
            'config.responsive' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A component with this name already exists.',
            'slug.unique' => 'A component with this slug already exists.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'category.in' => 'Invalid component category.',
            'config.headline.required_with' => 'Headline is required when updating category.',
            'config.fields.required_with' => 'Fields are required when updating category.',
            'config.testimonials.required_with' => 'Testimonials are required when updating category.',
            'config.metrics.required_with' => 'Metrics are required when updating category.',
            'config.buttons.required_with' => 'Buttons are required when updating category.',
            'config.sources.required_with' => 'Media sources are required when updating category.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'config.headline' => 'headline',
            'config.subheading' => 'subheading',
            'config.cta_text' => 'CTA text',
            'config.cta_url' => 'CTA URL',
            'config.background_type' => 'background type',
            'config.fields' => 'form fields',
            'config.testimonials' => 'testimonials',
            'config.metrics' => 'metrics',
            'config.buttons' => 'buttons',
            'config.sources' => 'media sources',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug if name is being updated but slug is not
        if ($this->has('name') && !$this->has('slug')) {
            $this->merge(['slug' => str($this->name)->slug()]);
        }

        // Ensure config structure exists if config is being updated
        if ($this->has('config') && !is_array($this->config)) {
            $this->merge(['config' => []]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional validation for accessibility
        $this->validateAccessibility();

        // Additional validation for mobile responsiveness
        $this->validateMobileResponsiveness();

        // Additional validation for version updates
        $this->validateVersionUpdate();
    }

    /**
     * Validate accessibility requirements
     */
    private function validateAccessibility(): void
    {
        if (!$this->has('config')) {
            return;
        }

        $config = $this->config;

        // Check for required accessibility attributes
        if (!isset($config['accessibility'])) {
            $config['accessibility'] = [];
        }

        $accessibility = $config['accessibility'];

        // Ensure semantic HTML usage
        if (!isset($accessibility['semanticTag'])) {
            $accessibility['semanticTag'] = 'div';
        }

        // Ensure keyboard navigation support
        if (!isset($accessibility['keyboardNavigation'])) {
            $accessibility['keyboardNavigation'] = ['focusable' => false];
        }

        $config['accessibility'] = $accessibility;
        $this->merge(['config' => $config]);
    }

    /**
     * Validate mobile responsiveness requirements
     */
    private function validateMobileResponsiveness(): void
    {
        if (!$this->has('config')) {
            return;
        }

        $config = $this->config;

        // Check for responsive configuration
        if (!isset($config['responsive'])) {
            $config['responsive'] = [
                'desktop' => [],
                'tablet' => [],
                'mobile' => []
            ];
        }

        $responsive = $config['responsive'];

        // Ensure all breakpoints have configuration
        foreach (['desktop', 'tablet', 'mobile'] as $breakpoint) {
            if (!isset($responsive[$breakpoint])) {
                $responsive[$breakpoint] = [];
            }
        }

        $config['responsive'] = $responsive;
        $this->merge(['config' => $config]);
    }

    /**
     * Validate version update requirements
     */
    private function validateVersionUpdate(): void
    {
        if (!$this->has('version')) {
            return;
        }

        $version = $this->version;

        // Validate version format (semantic versioning)
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $this->validator->errors()->add(
                'version',
                'Version must follow semantic versioning format (e.g., 1.0.0).'
            );
        }
    }
}