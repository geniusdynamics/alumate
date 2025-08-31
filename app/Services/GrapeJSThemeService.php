<?php

namespace App\Services;

use App\Models\ComponentTheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GrapeJSThemeService
{
    /**
     * Convert theme configuration to GrapeJS Style Manager format
     */
    public function convertToGrapeJSStyleManager(ComponentTheme $theme): array
    {
        $config = $theme->getMergedConfig();
        
        return [
            'sectors' => [
                [
                    'name' => 'Colors',
                    'open' => true,
                    'buildProps' => ['color', 'background-color', 'border-color'],
                    'properties' => $this->buildColorProperties($config['colors'] ?? [])
                ],
                [
                    'name' => 'Typography',
                    'open' => false,
                    'buildProps' => ['font-family', 'font-size', 'font-weight', 'line-height', 'text-align'],
                    'properties' => $this->buildTypographyProperties($config['typography'] ?? [])
                ],
                [
                    'name' => 'Spacing',
                    'open' => false,
                    'buildProps' => ['margin', 'padding'],
                    'properties' => $this->buildSpacingProperties($config['spacing'] ?? [])
                ],
                [
                    'name' => 'Layout',
                    'open' => false,
                    'buildProps' => ['display', 'position', 'width', 'height'],
                    'properties' => $this->buildLayoutProperties($config)
                ],
                [
                    'name' => 'Borders & Effects',
                    'open' => false,
                    'buildProps' => ['border', 'border-radius', 'box-shadow'],
                    'properties' => $this->buildBorderProperties($config['borders'] ?? [], $config['shadows'] ?? [])
                ]
            ]
        ];
    }

    /**
     * Build color properties for GrapeJS Style Manager
     */
    private function buildColorProperties(array $colors): array
    {
        $properties = [];
        
        foreach ($colors as $name => $value) {
            $properties[] = [
                'name' => ucfirst(str_replace('_', ' ', $name)),
                'property' => 'color',
                'type' => 'color',
                'default' => $value,
                'options' => [
                    [
                        'id' => "theme-{$name}",
                        'label' => ucfirst($name),
                        'value' => $value
                    ]
                ]
            ];
        }

        return $properties;
    }

    /**
     * Build typography properties for GrapeJS Style Manager
     */
    private function buildTypographyProperties(array $typography): array
    {
        $properties = [];

        if (isset($typography['font_family'])) {
            $properties[] = [
                'name' => 'Font Family',
                'property' => 'font-family',
                'type' => 'select',
                'default' => $typography['font_family'],
                'options' => [
                    ['value' => $typography['font_family'], 'name' => 'Theme Font'],
                    ['value' => 'Arial, sans-serif', 'name' => 'Arial'],
                    ['value' => 'Georgia, serif', 'name' => 'Georgia'],
                    ['value' => 'Times New Roman, serif', 'name' => 'Times New Roman'],
                    ['value' => 'Helvetica, sans-serif', 'name' => 'Helvetica']
                ]
            ];
        }

        if (isset($typography['font_sizes'])) {
            foreach ($typography['font_sizes'] as $size => $value) {
                $properties[] = [
                    'name' => ucfirst($size) . ' Font Size',
                    'property' => 'font-size',
                    'type' => 'slider',
                    'default' => $value,
                    'min' => 8,
                    'max' => 72,
                    'unit' => 'px'
                ];
            }
        }

        if (isset($typography['line_height'])) {
            $properties[] = [
                'name' => 'Line Height',
                'property' => 'line-height',
                'type' => 'slider',
                'default' => $typography['line_height'],
                'min' => 1,
                'max' => 3,
                'step' => 0.1
            ];
        }

        return $properties;
    }

    /**
     * Build spacing properties for GrapeJS Style Manager
     */
    private function buildSpacingProperties(array $spacing): array
    {
        $properties = [];
        
        foreach ($spacing as $name => $value) {
            $properties[] = [
                'name' => ucfirst(str_replace('_', ' ', $name)) . ' Spacing',
                'property' => $name === 'section_padding' ? 'padding' : 'margin',
                'type' => 'composite',
                'default' => $value,
                'properties' => [
                    ['name' => 'Top', 'property' => 'margin-top', 'type' => 'integer', 'units' => ['px', 'rem', 'em']],
                    ['name' => 'Right', 'property' => 'margin-right', 'type' => 'integer', 'units' => ['px', 'rem', 'em']],
                    ['name' => 'Bottom', 'property' => 'margin-bottom', 'type' => 'integer', 'units' => ['px', 'rem', 'em']],
                    ['name' => 'Left', 'property' => 'margin-left', 'type' => 'integer', 'units' => ['px', 'rem', 'em']]
                ]
            ];
        }

        return $properties;
    }

    /**
     * Build layout properties for GrapeJS Style Manager
     */
    private function buildLayoutProperties(array $config): array
    {
        return [
            [
                'name' => 'Display',
                'property' => 'display',
                'type' => 'select',
                'default' => 'block',
                'options' => [
                    ['value' => 'block', 'name' => 'Block'],
                    ['value' => 'inline-block', 'name' => 'Inline Block'],
                    ['value' => 'flex', 'name' => 'Flex'],
                    ['value' => 'grid', 'name' => 'Grid'],
                    ['value' => 'none', 'name' => 'None']
                ]
            ],
            [
                'name' => 'Position',
                'property' => 'position',
                'type' => 'select',
                'default' => 'static',
                'options' => [
                    ['value' => 'static', 'name' => 'Static'],
                    ['value' => 'relative', 'name' => 'Relative'],
                    ['value' => 'absolute', 'name' => 'Absolute'],
                    ['value' => 'fixed', 'name' => 'Fixed'],
                    ['value' => 'sticky', 'name' => 'Sticky']
                ]
            ]
        ];
    }

    /**
     * Build border and effects properties for GrapeJS Style Manager
     */
    private function buildBorderProperties(array $borders, array $shadows): array
    {
        $properties = [];

        if (isset($borders['radius'])) {
            $properties[] = [
                'name' => 'Border Radius',
                'property' => 'border-radius',
                'type' => 'slider',
                'default' => $borders['radius'],
                'min' => 0,
                'max' => 50,
                'unit' => 'px'
            ];
        }

        if (isset($borders['width'])) {
            $properties[] = [
                'name' => 'Border Width',
                'property' => 'border-width',
                'type' => 'slider',
                'default' => $borders['width'],
                'min' => 0,
                'max' => 10,
                'unit' => 'px'
            ];
        }

        return $properties;
    }

    /**
     * Generate CSS variables for GrapeJS integration
     */
    public function generateGrapeJSCssVariables(ComponentTheme $theme): array
    {
        $config = $theme->getMergedConfig();
        $variables = [];

        // Colors
        if (isset($config['colors'])) {
            foreach ($config['colors'] as $name => $value) {
                $variables["--theme-color-{$name}"] = $value;
            }
        }

        // Typography
        if (isset($config['typography'])) {
            if (isset($config['typography']['font_family'])) {
                $variables['--theme-font-family'] = $config['typography']['font_family'];
            }
            if (isset($config['typography']['heading_font'])) {
                $variables['--theme-heading-font'] = $config['typography']['heading_font'];
            }
            if (isset($config['typography']['font_sizes'])) {
                foreach ($config['typography']['font_sizes'] as $size => $value) {
                    $variables["--theme-font-size-{$size}"] = $value;
                }
            }
            if (isset($config['typography']['line_height'])) {
                $variables['--theme-line-height'] = $config['typography']['line_height'];
            }
        }

        // Spacing
        if (isset($config['spacing'])) {
            foreach ($config['spacing'] as $name => $value) {
                $variables["--theme-spacing-{$name}"] = $value;
            }
        }

        // Borders
        if (isset($config['borders'])) {
            foreach ($config['borders'] as $name => $value) {
                $variables["--theme-border-{$name}"] = $value;
            }
        }

        // Animations
        if (isset($config['animations'])) {
            foreach ($config['animations'] as $name => $value) {
                $variables["--theme-animation-{$name}"] = $value;
            }
        }

        return $variables;
    }

    /**
     * Convert GrapeJS styles back to theme configuration
     */
    public function convertFromGrapeJSStyles(array $styles): array
    {
        $config = [
            'colors' => [],
            'typography' => [],
            'spacing' => [],
            'borders' => [],
            'animations' => []
        ];

        foreach ($styles as $property => $value) {
            if (str_starts_with($property, '--theme-color-')) {
                $colorName = str_replace('--theme-color-', '', $property);
                $config['colors'][$colorName] = $value;
            } elseif (str_starts_with($property, '--theme-font-')) {
                $fontProperty = str_replace('--theme-font-', '', $property);
                if ($fontProperty === 'family') {
                    $config['typography']['font_family'] = $value;
                } elseif (str_starts_with($fontProperty, 'size-')) {
                    $size = str_replace('size-', '', $fontProperty);
                    $config['typography']['font_sizes'][$size] = $value;
                }
            } elseif (str_starts_with($property, '--theme-spacing-')) {
                $spacingName = str_replace('--theme-spacing-', '', $property);
                $config['spacing'][$spacingName] = $value;
            } elseif (str_starts_with($property, '--theme-border-')) {
                $borderName = str_replace('--theme-border-', '', $property);
                $config['borders'][$borderName] = $value;
            } elseif (str_starts_with($property, '--theme-animation-')) {
                $animationName = str_replace('--theme-animation-', '', $property);
                $config['animations'][$animationName] = $value;
            }
        }

        return $config;
    }

    /**
     * Generate Tailwind CSS class mappings for GrapeJS
     */
    public function generateTailwindMappings(ComponentTheme $theme): array
    {
        $config = $theme->getMergedConfig();
        $mappings = [];

        // Color mappings
        if (isset($config['colors'])) {
            foreach ($config['colors'] as $name => $value) {
                $mappings["text-theme-{$name}"] = "color: {$value}";
                $mappings["bg-theme-{$name}"] = "background-color: {$value}";
                $mappings["border-theme-{$name}"] = "border-color: {$value}";
            }
        }

        // Spacing mappings
        if (isset($config['spacing'])) {
            foreach ($config['spacing'] as $name => $value) {
                $mappings["p-theme-{$name}"] = "padding: {$value}";
                $mappings["m-theme-{$name}"] = "margin: {$value}";
            }
        }

        // Typography mappings
        if (isset($config['typography']['font_family'])) {
            $mappings['font-theme'] = "font-family: {$config['typography']['font_family']}";
        }

        if (isset($config['typography']['font_sizes'])) {
            foreach ($config['typography']['font_sizes'] as $size => $value) {
                $mappings["text-theme-{$size}"] = "font-size: {$value}";
            }
        }

        // Border mappings
        if (isset($config['borders']['radius'])) {
            $mappings['rounded-theme'] = "border-radius: {$config['borders']['radius']}";
        }

        return $mappings;
    }

    /**
     * Export theme for GrapeJS page builder integration
     */
    public function exportForGrapeJS(ComponentTheme $theme): array
    {
        return [
            'id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'isDefault' => $theme->is_default,
            'styleManager' => $this->convertToGrapeJSStyleManager($theme),
            'cssVariables' => $this->generateGrapeJSCssVariables($theme),
            'tailwindMappings' => $this->generateTailwindMappings($theme),
            'css' => $theme->compileToCss(),
            'accessibility' => $theme->checkAccessibility(),
            'preview' => $theme->generatePreviewHtml()
        ];
    }

    /**
     * Import theme from GrapeJS configuration
     */
    public function importFromGrapeJS(array $grapeJSConfig, string $tenantId): ComponentTheme
    {
        $config = $this->convertFromGrapeJSStyles($grapeJSConfig['styles'] ?? []);
        
        return ComponentTheme::create([
            'tenant_id' => $tenantId,
            'name' => $grapeJSConfig['name'] ?? 'Imported Theme',
            'slug' => str($grapeJSConfig['name'] ?? 'imported-theme')->slug(),
            'config' => $config,
            'is_default' => false
        ]);
    }

    /**
     * Get cached theme data for GrapeJS
     */
    public function getCachedThemeData(ComponentTheme $theme): array
    {
        return Cache::remember(
            "grapejs_theme_{$theme->id}",
            now()->addHours(24),
            fn() => $this->exportForGrapeJS($theme)
        );
    }

    /**
     * Clear theme cache
     */
    public function clearThemeCache(ComponentTheme $theme): void
    {
        Cache::forget("grapejs_theme_{$theme->id}");
    }

    /**
     * Get all themes for tenant formatted for GrapeJS
     */
    public function getThemesForGrapeJS(string $tenantId): Collection
    {
        return ComponentTheme::forTenant($tenantId)
            ->get()
            ->map(fn($theme) => $this->getCachedThemeData($theme));
    }

    /**
     * Validate theme compatibility with GrapeJS
     */
    public function validateGrapeJSCompatibility(ComponentTheme $theme): array
    {
        $issues = [];
        $config = $theme->getMergedConfig();

        // Check required color properties
        $requiredColors = ['primary', 'background', 'text'];
        foreach ($requiredColors as $color) {
            if (!isset($config['colors'][$color])) {
                $issues[] = "Missing required color: {$color}";
            }
        }

        // Check typography requirements
        if (!isset($config['typography']['font_family'])) {
            $issues[] = "Missing font family configuration";
        }

        // Check spacing requirements
        if (!isset($config['spacing']['base'])) {
            $issues[] = "Missing base spacing configuration";
        }

        // Check accessibility
        $accessibilityIssues = $theme->checkAccessibility();
        $issues = array_merge($issues, $accessibilityIssues);

        return $issues;
    }
}