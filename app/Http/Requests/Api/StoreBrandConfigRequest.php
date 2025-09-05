<?php

namespace App\Http\Requests\Api;

use App\Models\BrandConfig;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return optional(auth()->user())->can ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = BrandConfig::getValidationRules();

        // Remove tenant_id and related user fields as they are handled internally
        unset($rules['tenant_id'], $rules['created_by'], $rules['updated_by']);

        // Add custom validation for logo URLs
        if ($this->has('logo_url')) {
            $rules['logo_url'][] = 'nullable';
            $rules['logo_url'][] = 'url';
            $rules['logo_url'][] = 'regex:/\.(jpg|jpeg|png|gif|webp|svg)$/i';
        }

        if ($this->has('favicon_url')) {
            $rules['favicon_url'][] = 'nullable';
            $rules['favicon_url'][] = 'url';
            $rules['favicon_url'][] = 'regex:/\.(ico|png|jpg|jpeg|gif)$/i';
        }

        // Add validation for font weights
        if ($this->has('font_weights')) {
            $rules['font_weights.*'] = 'integer|min:100|max:900';
        }

        // Add validation for custom CSS
        if ($this->has('custom_css')) {
            $rules['custom_css'][] = 'nullable';
            $rules['custom_css'][] = 'string';
            $rules['custom_css'][] = 'max:50000'; // 50KB limit
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
            'name.required' => 'Brand configuration name is required.',
            'name.max' => 'Brand configuration name cannot be longer than :max characters.',
            'primary_color.regex' => 'Primary color must be a valid hex color code.',
            'secondary_color.regex' => 'Secondary color must be a valid hex color code.',
            'accent_color.regex' => 'Accent color must be a valid hex color code.',
            'logo_url.url' => 'Logo URL must be a valid URL.',
            'logo_url.regex' => 'Logo URL must point to an image file (jpg, jpeg, png, gif, webp, svg).',
            'favicon_url.url' => 'Favicon URL must be a valid URL.',
            'favicon_url.regex' => 'Favicon URL must point to an image file (ico, png, jpg, jpeg, gif).',
            'font_weights.*.integer' => 'Font weights must be numeric values.',
            'font_weights.*.min' => 'Font weights must be at least 100.',
            'font_weights.*.max' => 'Font weights cannot exceed 900.',
            'custom_css.max' => 'Custom CSS cannot exceed 50KB.',
            'typography_settings.array' => 'Typography settings must be a valid JSON array.',
            'spacing_settings.array' => 'Spacing settings must be a valid JSON array.',
            'brand_colors.array' => 'Brand colors must be a valid JSON array.',
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
            'primary_color' => 'primary color',
            'secondary_color' => 'secondary color',
            'accent_color' => 'accent color',
            'font_family' => 'font family',
            'heading_font_family' => 'heading font family',
            'body_font_family' => 'body font family',
            'logo_url' => 'logo URL',
            'favicon_url' => 'favicon URL',
            'custom_css' => 'custom CSS',
            'font_weights' => 'font weights',
            'typography_settings' => 'typography settings',
            'spacing_settings' => 'spacing settings',
            'brand_colors' => 'brand colors',
            'is_default' => 'default status',
            'is_active' => 'active status',
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
        if (optional(auth()->user())->tenant_id) {
            $this->merge(['tenant_id' => auth()->user()->tenant_id]);
        }

        // Normalize hex colors to uppercase
        $colorFields = ['primary_color', 'secondary_color', 'accent_color'];
        foreach ($colorFields as $field) {
            if ($this->has($field) && $this->input($field)) {
                $this->merge([$field => strtoupper($this->input($field))]);
            }
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
            // Validate brand configuration completeness
            if ($this->has('name') && !empty($this->name)) {
                $this->validateBrandConfigCompleteness($validator);
            }

            // Validate CSS syntax if provided
            if ($this->has('custom_css') && !empty($this->custom_css)) {
                $this->validateCustomCss($validator);
            }

            // Validate color contrast ratios
            if ($this->has(['primary_color', 'secondary_color'])) {
                $this->validateColorContrast($validator);
            }
        });
    }

    /**
     * Validate that the brand configuration has sufficient branding elements.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateBrandConfigCompleteness($validator): void
    {
        $hasColors = !empty($this->input('primary_color'));
        $hasFonts = !empty($this->input('font_family'));
        $hasLogo = !empty($this->input('logo_url'));

        // At least one branding element should be provided
        if (!$hasColors && !$hasFonts && !$hasLogo) {
            $validator->errors()->add(
                'brand_config',
                'Brand configuration should include at least colors, fonts, or a logo.'
            );
        }
    }

    /**
     * Validate custom CSS for basic syntax.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateCustomCss($validator): void
    {
        $css = $this->input('custom_css');

        // Basic CSS syntax validation
        if (!preg_match('/^[^{}]*\{[^}]*\}[^{}]*$/s', $css)) {
            $validator->errors()->add(
                'custom_css',
                'Custom CSS contains invalid syntax. Please check your CSS rules.'
            );
        }

        // Check for potentially harmful CSS
        $dangerousPatterns = [
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',
            '/expression\s*\(/i',
            '/@import/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $css)) {
                $validator->errors()->add(
                    'custom_css',
                    'Custom CSS contains potentially unsafe content.'
                );
                break;
            }
        }
    }

    /**
     * Validate color contrast ratios for accessibility.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    private function validateColorContrast($validator): void
    {
        $primaryColor = $this->input('primary_color');
        $secondaryColor = $this->input('secondary_color');

        if ($primaryColor && $secondaryColor) {
            $contrastRatio = $this->calculateContrastRatio($primaryColor, $secondaryColor);

            // WCAG AA requires minimum 4.5:1 contrast ratio
            if ($contrastRatio < 4.5) {
                $validator->errors()->add(
                    'color_contrast',
                    "Color contrast ratio between primary (#{$primaryColor}) and secondary (#{$secondaryColor}) colors is too low: {$contrastRatio}:1. WCAG AA requires minimum 4.5:1 ratio."
                );
            }
        }
    }

    /**
     * Calculate contrast ratio between two hex colors.
     *
     * @param string $color1
     * @param string $color2
     * @return float
     */
    private function calculateContrastRatio(string $color1, string $color2): float
    {
        $luminance1 = $this->getRelativeLuminance($color1);
        $luminance2 = $this->getRelativeLuminance($color2);

        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);

        return round(($lighter + 0.05) / ($darker + 0.05), 2);
    }

    /**
     * Calculate relative luminance of a hex color.
     *
     * @param string $hex
     * @return float
     */
    private function getRelativeLuminance(string $hex): float
    {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        return 0.2126 * $this->getLuminanceComponent($r) +
               0.7152 * $this->getLuminanceComponent($g) +
               0.0722 * $this->getLuminanceComponent($b);
    }

    /**
     * Get luminance component for contrast calculation.
     *
     * @param float $component
     * @return float
     */
    private function getLuminanceComponent(float $component): float
    {
        return $component <= 0.03928
            ? $component / 12.92
            : pow(($component + 0.055) / 1.055, 2.4);
    }
}