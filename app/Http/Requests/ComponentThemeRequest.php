<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ComponentThemeRequest extends FormRequest
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
        $themeId = $this->route('theme')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:component_themes,name,' . $themeId . ',id,tenant_id,' . Auth::user()->tenant_id
            ],
            'is_default' => 'boolean',
            'config' => 'required|array',
            
            // Colors validation
            'config.colors' => 'required|array',
            'config.colors.primary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'config.colors.secondary' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'config.colors.accent' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'config.colors.background' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'config.colors.text' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            
            // Typography validation
            'config.typography' => 'required|array',
            'config.typography.font_family' => 'required|string|max:100',
            'config.typography.heading_font' => 'nullable|string|max:100',
            'config.typography.font_sizes' => 'nullable|array',
            'config.typography.font_sizes.base' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em|%)$/',
            'config.typography.font_sizes.heading' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em|%)$/',
            'config.typography.line_height' => 'nullable|numeric|min:1|max:3',
            
            // Spacing validation
            'config.spacing' => 'required|array',
            'config.spacing.base' => 'required|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'config.spacing.small' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'config.spacing.large' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'config.spacing.section_padding' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            
            // Borders validation
            'config.borders' => 'nullable|array',
            'config.borders.radius' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em|%)$/',
            'config.borders.width' => 'nullable|string|regex:/^\d+(\.\d+)?px$/',
            
            // Shadows validation
            'config.shadows' => 'nullable|array',
            
            // Animations validation
            'config.animations' => 'nullable|array',
            'config.animations.duration' => 'nullable|string|regex:/^\d+(\.\d+)?s$/',
            'config.animations.easing' => 'nullable|string|in:ease,ease-in,ease-out,ease-in-out,linear,cubic-bezier',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Theme name is required.',
            'name.unique' => 'A theme with this name already exists.',
            'config.required' => 'Theme configuration is required.',
            
            // Color messages
            'config.colors.required' => 'Color configuration is required.',
            'config.colors.primary.required' => 'Primary color is required.',
            'config.colors.primary.regex' => 'Primary color must be a valid hex color (e.g., #007bff).',
            'config.colors.secondary.regex' => 'Secondary color must be a valid hex color.',
            'config.colors.accent.regex' => 'Accent color must be a valid hex color.',
            'config.colors.background.regex' => 'Background color must be a valid hex color.',
            'config.colors.text.regex' => 'Text color must be a valid hex color.',
            
            // Typography messages
            'config.typography.required' => 'Typography configuration is required.',
            'config.typography.font_family.required' => 'Font family is required.',
            'config.typography.font_family.max' => 'Font family name is too long.',
            'config.typography.heading_font.max' => 'Heading font name is too long.',
            'config.typography.font_sizes.base.regex' => 'Base font size must be a valid CSS size (e.g., 16px, 1rem).',
            'config.typography.font_sizes.heading.regex' => 'Heading font size must be a valid CSS size.',
            'config.typography.line_height.numeric' => 'Line height must be a number.',
            'config.typography.line_height.min' => 'Line height must be at least 1.',
            'config.typography.line_height.max' => 'Line height cannot exceed 3.',
            
            // Spacing messages
            'config.spacing.required' => 'Spacing configuration is required.',
            'config.spacing.base.required' => 'Base spacing is required.',
            'config.spacing.base.regex' => 'Base spacing must be a valid CSS size (e.g., 1rem, 16px).',
            'config.spacing.small.regex' => 'Small spacing must be a valid CSS size.',
            'config.spacing.large.regex' => 'Large spacing must be a valid CSS size.',
            'config.spacing.section_padding.regex' => 'Section padding must be a valid CSS size.',
            
            // Border messages
            'config.borders.radius.regex' => 'Border radius must be a valid CSS size.',
            'config.borders.width.regex' => 'Border width must be a valid pixel value.',
            
            // Animation messages
            'config.animations.duration.regex' => 'Animation duration must be a valid time value (e.g., 0.3s).',
            'config.animations.easing.in' => 'Animation easing must be a valid CSS easing function.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'config.colors.primary' => 'primary color',
            'config.colors.secondary' => 'secondary color',
            'config.colors.accent' => 'accent color',
            'config.colors.background' => 'background color',
            'config.colors.text' => 'text color',
            'config.typography.font_family' => 'font family',
            'config.typography.heading_font' => 'heading font',
            'config.typography.font_sizes.base' => 'base font size',
            'config.typography.font_sizes.heading' => 'heading font size',
            'config.typography.line_height' => 'line height',
            'config.spacing.base' => 'base spacing',
            'config.spacing.small' => 'small spacing',
            'config.spacing.large' => 'large spacing',
            'config.spacing.section_padding' => 'section padding',
            'config.borders.radius' => 'border radius',
            'config.borders.width' => 'border width',
            'config.animations.duration' => 'animation duration',
            'config.animations.easing' => 'animation easing',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure config structure exists
        if (!$this->has('config')) {
            $this->merge(['config' => []]);
        }

        // Set default values for required sections
        $config = $this->config ?? [];
        
        if (!isset($config['colors'])) {
            $config['colors'] = [];
        }
        
        if (!isset($config['typography'])) {
            $config['typography'] = [];
        }
        
        if (!isset($config['spacing'])) {
            $config['spacing'] = [];
        }

        $this->merge(['config' => $config]);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Additional validation for accessibility
        $this->validateAccessibility();
        
        // Additional validation for GrapeJS compatibility
        $this->validateGrapeJSCompatibility();
    }

    /**
     * Validate accessibility requirements
     */
    private function validateAccessibility(): void
    {
        $colors = $this->config['colors'] ?? [];
        
        if (isset($colors['primary']) && isset($colors['background'])) {
            $contrast = $this->calculateContrast($colors['primary'], $colors['background']);
            if ($contrast < 3.0) { // Minimum contrast for large text
                $this->validator->errors()->add(
                    'config.colors.primary',
                    'Primary color contrast with background is too low for accessibility standards.'
                );
            }
        }
        
        if (isset($colors['text']) && isset($colors['background'])) {
            $contrast = $this->calculateContrast($colors['text'], $colors['background']);
            if ($contrast < 4.5) { // WCAG AA standard
                $this->validator->errors()->add(
                    'config.colors.text',
                    'Text color contrast with background does not meet WCAG AA standards (4.5:1).'
                );
            }
        }
    }

    /**
     * Validate GrapeJS compatibility
     */
    private function validateGrapeJSCompatibility(): void
    {
        $config = $this->config ?? [];
        
        // Check for required GrapeJS properties
        $requiredColors = ['primary', 'background', 'text'];
        foreach ($requiredColors as $color) {
            if (!isset($config['colors'][$color])) {
                $this->validator->errors()->add(
                    "config.colors.{$color}",
                    "The {$color} color is required for GrapeJS compatibility."
                );
            }
        }
        
        // Check typography requirements
        if (!isset($config['typography']['font_family'])) {
            $this->validator->errors()->add(
                'config.typography.font_family',
                'Font family is required for GrapeJS compatibility.'
            );
        }
        
        // Check spacing requirements
        if (!isset($config['spacing']['base'])) {
            $this->validator->errors()->add(
                'config.spacing.base',
                'Base spacing is required for GrapeJS compatibility.'
            );
        }
    }

    /**
     * Calculate color contrast ratio
     */
    private function calculateContrast(string $color1, string $color2): float
    {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);
        
        if (!$rgb1 || !$rgb2) {
            return 0;
        }
        
        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);
        
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb(string $hex): ?array
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        
        if (strlen($hex) !== 6) {
            return null;
        }
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Calculate relative luminance
     */
    private function getRelativeLuminance(array $rgb): float
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;
        
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
}