<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLandingPageRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:landing_pages,slug',
            'description' => 'nullable|string|max:1000',
            'config' => 'nullable|array',
            'brand_config' => 'nullable|array',
            'audience_type' => ['required', Rule::in(['individual', 'institution', 'employer'])],
            'campaign_type' => ['required', Rule::in([
                'onboarding', 'event_promotion', 'donation', 'networking',
                'career_services', 'recruiting', 'leadership', 'marketing'
            ])],
            'category' => ['required', Rule::in(['individual', 'institution', 'employer'])],
            'status' => ['sometimes', 'in:draft,reviewing,published,archived,suspended'],
            'preview_url' => 'nullable|string|url|max:255',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|array|max:10',
            'seo_keywords.*' => 'string|max:50',
            'social_image' => 'nullable|string|url|max:255',
            'tracking_id' => 'nullable|string|max:255',
            'favicon_url' => 'nullable|string|url|max:255',
            'custom_css' => 'nullable|string|max:50000',
            'custom_js' => 'nullable|string|max:100000',
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
            'name.required' => 'Landing page name is required.',
            'name.max' => 'Landing page name cannot be longer than :max characters.',
            'slug.unique' => 'A landing page with this slug already exists.',
            'slug.regex' => 'Slug must contain only lowercase letters, numbers, and hyphens.',
            'category.required' => 'Landing page category is required.',
            'category.in' => 'Selected category is invalid.',
            'audience_type.required' => 'Audience type is required.',
            'audience_type.in' => 'Selected audience type is invalid.',
            'campaign_type.required' => 'Campaign type is required.',
            'campaign_type.in' => 'Selected campaign type is invalid.',
            'status.in' => 'Selected status is invalid.',
            'seo_title.max' => 'SEO title cannot be longer than :max characters.',
            'seo_description.max' => 'SEO description cannot be longer than :max characters.',
            'seo_keywords.array' => 'SEO keywords must be provided as an array.',
            'seo_keywords.max' => 'Cannot have more than :max SEO keywords.',
            'seo_keywords.*.max' => 'Each SEO keyword cannot be longer than :max characters.',
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
            'category' => 'landing page category',
            'audience_type' => 'audience type',
            'campaign_type' => 'campaign type',
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
            'seo_keywords' => 'SEO keywords',
            'preview_url' => 'preview URL',
            'public_url' => 'public URL',
            'tracking_id' => 'tracking ID',
            'favicon_url' => 'favicon URL',
            'custom_css' => 'custom CSS',
            'custom_js' => 'custom JavaScript',
            'social_image' => 'social media image',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Set tenant_id automatically from authenticated user
        if (auth()->check() && auth()->user()->tenant_id) {
            $this->merge(['tenant_id' => auth()->user()->tenant_id]);
        }

        // Set default status if not provided
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }

        // Set created_by
        if (auth()->check()) {
            $this->merge(['created_by' => auth()->id()]);
        }

        // Ensure slug is auto-generated if not provided
        if (empty($this->slug) && $this->has('name')) {
            $this->merge(['slug' => $this->generateSlug($this->name)]);
        }
    }

    /**
     * Generate a unique slug for the landing page
     *
     * @param string $name
     * @return string
     */
    private function generateSlug(string $name): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (\App\Models\LandingPage::where('slug', $slug)->where('tenant_id', $this->tenant_id ?? null)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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
            // Validate config structure if provided
            if ($this->has('config') && !empty($this->config)) {
                $this->validateConfigStructure($validator);
            }

            // Validate brand config if provided
            if ($this->has('brand_config') && !empty($this->brand_config)) {
                $this->validateBrandConfig($validator);
            }

            // Validate custom CSS and JS
            if ($this->has('custom_css')) {
                $this->validateCustomCode($validator, $this->custom_css, 'custom_css', 'CSS');
            }

            if ($this->has('custom_js')) {
                $this->validateCustomCode($validator, $this->custom_js, 'custom_js', 'JavaScript');
            }
        });
    }

    /**
     * Validate landing page configuration structure
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateConfigStructure($validator): void
    {
        $config = $this->config;

        // Configuration should be an associative array with expected keys
        $requiredKeys = ['sections']; // At minimum, should have sections

        foreach ($requiredKeys as $key) {
            if (!isset($config[$key])) {
                $validator->errors()->add('config', "Landing page configuration must include '{$key}'");
                break;
            }
        }
    }

    /**
     * Validate brand configuration
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    private function validateBrandConfig($validator): void
    {
        $brandConfig = $this->brand_config;

        if (!is_array($brandConfig)) {
            $validator->errors()->add('brand_config', 'Brand configuration must be a valid array');
            return;
        }

        // Basic brand config validation
        $requiredKeys = ['colors', 'fonts'];
        foreach ($requiredKeys as $key) {
            if (!isset($brandConfig[$key])) {
                $validator->errors()->add('brand_config', "Brand configuration must include '{$key}'");
            }
        }

        // Validate color format if provided
        if (isset($brandConfig['colors']['primary'])) {
            if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $brandConfig['colors']['primary'])) {
                $validator->errors()->add('brand_config', 'Primary color must be a valid hex color code');
            }
        }
    }

    /**
     * Validate custom CSS or JavaScript code
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $code
     * @param string $field
     * @param string $type
     */
    private function validateCustomCode($validator, string $code, string $field, string $type): void
    {
        if (empty($code)) {
            return;
        }

        // Basic security checks for custom code
        $dangerousPatterns = [
            '/<script[^>]*>.*?<\/script>/is', // Script tags
            '/javascript:/i', // JavaScript protocol
            '/on\w+\s*=/i', // Event handlers
            '/expression\s*\(/i', // CSS expressions
            '/vbscript:/i', // VBScript protocol
            '/data:\s*text\/html/i', // Data URLs with HTML
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $code)) {
                $validator->errors()->add($field, "Custom {$type} contains potentially dangerous code and is not allowed");
                break;
            }
        }

        // Check for base64 encoded content
        if (preg_match('/data:[^;]+;base64,/', $code)) {
            $validator->errors()->add($field, "Custom {$type} cannot contain base64 encoded content");
        }
    }
}