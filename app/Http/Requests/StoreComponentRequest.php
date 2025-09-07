<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreComponentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'theme_id' => 'nullable|exists:component_themes,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:components,name,NULL,id,tenant_id,' . Auth::user()->tenant_id
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                'unique:components,slug,NULL,id,tenant_id,' . Auth::user()->tenant_id
            ],
            'category' => ['required', Rule::in(['hero', 'forms', 'testimonials', 'statistics', 'ctas', 'media'])],
            'type' => 'required|string|max:100',
            'description' => 'nullable|string|max:100',
            'config' => 'nullable|array',
            'metadata' => 'nullable|array',
            'version' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            
            // Category-specific validation rules
            'config.headline' => 'required_if:category,hero|string|max:255',
            'config.subheading' => 'nullable|string|max:500',
            'config.cta_text' => 'required_if:category,hero|string|max:50',
            'config.cta_url' => 'required_if:category,hero|string|url|max:255',
            'config.background_type' => 'required_if:category,hero|in:image,video,gradient',
            'config.show_statistics' => 'boolean',
            
            'config.fields' => 'required_if:category,forms|array',
            'config.fields.*.type' => 'required|in:text,email,phone,select,checkbox,textarea',
            'config.fields.*.label' => 'required|string|max:255',
            'config.fields.*.required' => 'boolean',
            'config.submit_text' => 'string|max:50',
            'config.success_message' => 'string|max:500',
            'config.crm_integration' => 'boolean',
            
            'config.testimonials' => 'required_if:category,testimonials|array',
            'config.testimonials.*.quote' => 'required|string|max:500',
            'config.testimonials.*.author' => 'required|string|max:100',
            'config.testimonials.*.title' => 'nullable|string|max:100',
            'config.testimonials.*.company' => 'nullable|string|max:100',
            'config.testimonials.*.photo' => 'nullable|string|url',
            
            'config.metrics' => 'required_if:category,statistics|array',
            'config.metrics.*.label' => 'required|string|max:100',
            'config.metrics.*.value' => 'required|numeric',
            'config.metrics.*.suffix' => 'nullable|string|max:10',
            'config.animation_type' => 'in:counter,progress,chart',
            'config.trigger_on_scroll' => 'boolean',
            
            'config.buttons' => 'required_if:category,ctas|array',
            'config.buttons.*.text' => 'required|string|max:50',
            'config.buttons.*.url' => 'required|string|url|max:255',
            'config.buttons.*.style' => 'in:primary,secondary,outline,text',
            
            'config.sources' => 'required_if:category,media|array',
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
            'name.required' => 'Component name is required.',
            'name.unique' => 'A component with this name already exists.',
            'slug.unique' => 'A component with this slug already exists.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'category.required' => 'Component category is required.',
            'category.in' => 'Invalid component category.',
            'type.required' => 'Component type is required.',
            'config.headline.required_if' => 'Headline is required for hero components.',
            'config.fields.required_if' => 'Fields are required for form components.',
            'config.testimonials.required_if' => 'Testimonials are required for testimonial components.',
            'config.metrics.required_if' => 'Metrics are required for statistics components.',
            'config.buttons.required_if' => 'Buttons are required for CTA components.',
            'config.sources.required_if' => 'Media sources are required for media components.',
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
        // Set tenant_id if not provided
        if (!$this->has('tenant_id')) {
            $this->merge(['tenant_id' => Auth::user()->tenant_id]);
        }

        // Generate slug if not provided
        if (!$this->has('slug') && $this->has('name')) {
            $this->merge(['slug' => str($this->name)->slug()]);
        }

        // Set default version if not provided
        if (!$this->has('version')) {
            $this->merge(['version' => '1.0.0']);
        }

        // Ensure config structure exists
        if (!$this->has('config')) {
            $this->merge(['config' => []]);
        }

        // Set default active status
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
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
    }

    /**
     * Validate accessibility requirements
     */
    private function validateAccessibility(): void
    {
        $config = $this->config ?? [];

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
        $config = $this->config ?? [];

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
}