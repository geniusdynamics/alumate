<?php

namespace App\Services;

use App\Models\ComponentTheme;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ComponentThemeService
{
    /**
     * Apply theme to components with CSS variable generation
     */
    public function applyTheme(ComponentTheme $theme, array $componentIds = []): array
    {
        // Generate CSS variables
        $cssVariables = $theme->generateCssVariables();

        // Apply theme to components
        $affectedComponents = $theme->applyToComponents($componentIds);

        // Cache the compiled CSS for performance
        $cacheKey = "theme_css_{$theme->id}";
        Cache::put($cacheKey, $cssVariables, now()->addHours(24));

        return [
            'css_variables' => $cssVariables,
            'affected_components' => $affectedComponents,
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Create theme with inheritance from default theme
     */
    public function createThemeWithInheritance(string $tenantId, array $themeData, ?ComponentTheme $parentTheme = null): ComponentTheme
    {
        // Get default theme if no parent specified
        if (! $parentTheme) {
            $parentTheme = $this->getDefaultTheme($tenantId);
        }

        // Merge configuration with parent theme
        $mergedConfig = $this->mergeThemeConfigs(
            $parentTheme ? $parentTheme->config : [],
            $themeData['config'] ?? []
        );

        // Validate the merged configuration
        $this->validateThemeConfig($mergedConfig);

        // Create the theme
        $theme = ComponentTheme::create([
            'tenant_id' => $tenantId,
            'name' => $themeData['name'],
            'slug' => str($themeData['name'])->slug(),
            'config' => $mergedConfig,
            'is_default' => $themeData['is_default'] ?? false,
        ]);

        // Clear theme cache
        $this->clearThemeCache($tenantId);

        return $theme;
    }

    /**
     * Validate theme configuration for color schemes, typography, and spacing
     */
    public function validateThemeConfig(array $config): bool
    {
        $validator = Validator::make($config, [
            // Color scheme validation
            'colors' => 'required|array',
            'colors.primary' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.secondary' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.accent' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.background' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.text' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.success' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.warning' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'colors.error' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

            // Typography validation
            'typography' => 'required|array',
            'typography.font_family' => 'required|string|max:100',
            'typography.heading_font' => 'nullable|string|max:100',
            'typography.font_sizes' => 'nullable|array',
            'typography.font_sizes.xs' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.font_sizes.sm' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.font_sizes.base' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.font_sizes.lg' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.font_sizes.xl' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.font_sizes.heading' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'typography.line_height' => 'nullable|numeric|min:1|max:3',
            'typography.font_weight' => 'nullable|array',

            // Spacing validation
            'spacing' => 'required|array',
            'spacing.xs' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'spacing.sm' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'spacing.base' => ['required', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'spacing.lg' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'spacing.xl' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'spacing.section_padding' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],

            // Border validation
            'borders' => 'nullable|array',
            'borders.radius' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?(px|rem|em)$/'],
            'borders.width' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?px$/'],

            // Shadow validation
            'shadows' => 'nullable|array',
            'shadows.sm' => 'nullable|string',
            'shadows.md' => 'nullable|string',
            'shadows.lg' => 'nullable|string',

            // Animation validation
            'animations' => 'nullable|array',
            'animations.duration' => ['nullable', 'string', 'regex:/^\d+(\.\d+)?s$/'],
            'animations.easing' => 'nullable|string|in:ease,ease-in,ease-out,ease-in-out,linear',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional accessibility validation
        $this->validateAccessibility($config);

        return true;
    }

    /**
     * Validate accessibility requirements
     */
    protected function validateAccessibility(array $config): void
    {
        $errors = [];

        // Check color contrast ratios
        if (isset($config['colors']['primary']) && isset($config['colors']['background'])) {
            $contrast = $this->calculateColorContrast($config['colors']['primary'], $config['colors']['background']);
            if ($contrast < 3.0) {
                $errors[] = 'Primary color contrast ratio must be at least 3:1 for accessibility';
            }
        }

        if (isset($config['colors']['text']) && isset($config['colors']['background'])) {
            $contrast = $this->calculateColorContrast($config['colors']['text'], $config['colors']['background']);
            if ($contrast < 4.5) {
                $errors[] = 'Text color contrast ratio must be at least 4.5:1 for WCAG AA compliance';
            }
        }

        // Check font size minimums
        if (isset($config['typography']['font_sizes']['base'])) {
            $baseFontSize = (float) str_replace(['px', 'rem', 'em'], '', $config['typography']['font_sizes']['base']);
            if (str_contains($config['typography']['font_sizes']['base'], 'px') && $baseFontSize < 14) {
                $errors[] = 'Base font size should be at least 14px for accessibility';
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages(['accessibility' => $errors]);
        }
    }

    /**
     * Implement multi-tenant theme isolation and access control
     */
    public function getThemesForTenant(string $tenantId, bool $includeDefault = true): Collection
    {
        $query = ComponentTheme::forTenant($tenantId);

        if (! $includeDefault) {
            $query->where('is_default', false);
        }

        return $query->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Ensure tenant can only access their themes
     */
    public function validateTenantAccess(ComponentTheme $theme, string $tenantId): bool
    {
        if ($theme->tenant_id !== $tenantId) {
            throw new \InvalidArgumentException('Theme does not belong to the specified tenant');
        }

        return true;
    }

    /**
     * Generate theme preview with sample components
     */
    public function generateThemePreview(ComponentTheme $theme, array $options = []): array
    {
        $previewHtml = $theme->generatePreviewHtml();
        $cssVariables = $theme->generateCssVariables();

        // Generate component-specific previews
        $componentPreviews = $this->generateComponentPreviews($theme, $options);

        return [
            'html' => $previewHtml,
            'css' => $cssVariables,
            'components' => $componentPreviews,
            'accessibility_report' => $theme->checkAccessibility(),
        ];
    }

    /**
     * Generate previews for different component types
     */
    protected function generateComponentPreviews(ComponentTheme $theme, array $options = []): array
    {
        $config = $theme->getMergedConfig();
        $previews = [];

        // Hero component preview
        $previews['hero'] = $this->generateHeroPreview($config);

        // Form component preview
        $previews['form'] = $this->generateFormPreview($config);

        // Button component preview
        $previews['button'] = $this->generateButtonPreview($config);

        // Card component preview
        $previews['card'] = $this->generateCardPreview($config);

        return $previews;
    }

    /**
     * Compile theme to optimized CSS
     */
    public function compileThemeCss(ComponentTheme $theme, array $options = []): string
    {
        $css = $theme->generateCssVariables();

        // Add responsive breakpoints
        $css .= $this->generateResponsiveCss($theme->getMergedConfig());

        // Add component-specific styles
        $css .= $this->generateComponentStyles($theme->getMergedConfig());

        // Minify CSS if requested
        if ($options['minify'] ?? false) {
            $css = $this->minifyCss($css);
        }

        return $css;
    }

    /**
     * Create theme backup
     */
    public function backupTheme(ComponentTheme $theme): string
    {
        $backupData = [
            'theme' => $theme->toArray(),
            'created_at' => now()->toISOString(),
            'version' => '1.0',
        ];

        $filename = "theme_backup_{$theme->id}_{$theme->slug}_".now()->format('Y-m-d_H-i-s').'.json';
        $path = "theme-backups/{$theme->tenant_id}/{$filename}";

        Storage::disk('local')->put($path, json_encode($backupData, JSON_PRETTY_PRINT));

        return $path;
    }

    /**
     * Restore theme from backup
     */
    public function restoreTheme(string $backupPath, string $tenantId): ComponentTheme
    {
        if (! Storage::disk('local')->exists($backupPath)) {
            throw new \InvalidArgumentException('Backup file not found');
        }

        $backupData = json_decode(Storage::disk('local')->get($backupPath), true);

        if (! isset($backupData['theme'])) {
            throw new \InvalidArgumentException('Invalid backup file format');
        }

        $themeData = $backupData['theme'];

        // Validate tenant access
        if ($themeData['tenant_id'] !== $tenantId) {
            throw new \InvalidArgumentException('Backup does not belong to the specified tenant');
        }

        // Validate configuration
        $this->validateThemeConfig($themeData['config']);

        // Create restored theme
        $restoredTheme = ComponentTheme::create([
            'tenant_id' => $tenantId,
            'name' => $themeData['name'].' (Restored)',
            'slug' => str($themeData['name'].' restored')->slug(),
            'config' => $themeData['config'],
            'is_default' => false, // Never restore as default
        ]);

        // Clear theme cache
        $this->clearThemeCache($tenantId);

        return $restoredTheme;
    }

    /**
     * Get default theme for tenant
     */
    public function getDefaultTheme(string $tenantId): ?ComponentTheme
    {
        return ComponentTheme::forTenant($tenantId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Set theme as default for tenant
     */
    public function setAsDefault(ComponentTheme $theme): bool
    {
        // Remove default flag from other themes
        ComponentTheme::forTenant($theme->tenant_id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this theme as default
        $theme->update(['is_default' => true]);

        // Clear cache
        $this->clearThemeCache($theme->tenant_id);

        return true;
    }

    /**
     * Merge theme configurations with inheritance
     */
    protected function mergeThemeConfigs(array $parentConfig, array $childConfig): array
    {
        $merged = $parentConfig;

        foreach ($childConfig as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Calculate color contrast ratio
     */
    protected function calculateColorContrast(string $color1, string $color2): float
    {
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Convert hex color to RGB
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
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
    protected function getRelativeLuminance(array $rgb): float
    {
        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Clear theme cache for tenant
     */
    protected function clearThemeCache(string $tenantId): void
    {
        Cache::forget("themes_tenant_{$tenantId}");
        Cache::forget("default_theme_{$tenantId}");

        // Clear individual theme caches
        $themes = ComponentTheme::forTenant($tenantId)->get();
        foreach ($themes as $theme) {
            Cache::forget("theme_css_{$theme->id}");
        }
    }

    /**
     * Generate responsive CSS
     */
    protected function generateResponsiveCss(array $config): string
    {
        $css = "\n/* Responsive Styles */\n";

        // Mobile styles
        $css .= "@media (max-width: 768px) {\n";
        $css .= "  :root {\n";
        if (isset($config['spacing']['base'])) {
            $css .= "    --spacing-base: calc({$config['spacing']['base']} * 0.8);\n";
        }
        if (isset($config['typography']['font_sizes']['base'])) {
            $css .= "    --font-size-base: calc({$config['typography']['font_sizes']['base']} * 0.9);\n";
        }
        $css .= "  }\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Generate component-specific styles
     */
    protected function generateComponentStyles(array $config): string
    {
        $css = "\n/* Component Styles */\n";

        $css .= ".component-hero {\n";
        $css .= "  background-color: var(--color-primary);\n";
        $css .= "  color: white;\n";
        $css .= "  padding: var(--spacing-xl, 3rem) var(--spacing-base);\n";
        $css .= "}\n";

        $css .= ".component-button {\n";
        $css .= "  background-color: var(--color-primary);\n";
        $css .= "  color: white;\n";
        $css .= "  padding: var(--spacing-sm) var(--spacing-base);\n";
        $css .= "  border: none;\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  font-family: var(--font-family);\n";
        $css .= "  cursor: pointer;\n";
        $css .= "  transition: all var(--animation-duration) var(--animation-easing);\n";
        $css .= "}\n";

        $css .= ".component-button:hover {\n";
        $css .= "  background-color: var(--color-secondary);\n";
        $css .= "  transform: translateY(-2px);\n";
        $css .= "}\n";

        return $css;
    }

    /**
     * Generate hero component preview
     */
    protected function generateHeroPreview(array $config): string
    {
        return '<div class="component-hero"><h1>Hero Section</h1><p>Sample hero content</p></div>';
    }

    /**
     * Generate form component preview
     */
    protected function generateFormPreview(array $config): string
    {
        return '<form class="component-form"><input type="text" placeholder="Sample Input"><button type="submit" class="component-button">Submit</button></form>';
    }

    /**
     * Generate button component preview
     */
    protected function generateButtonPreview(array $config): string
    {
        return '<button class="component-button">Sample Button</button>';
    }

    /**
     * Generate card component preview
     */
    protected function generateCardPreview(array $config): string
    {
        return '<div class="component-card"><h3>Card Title</h3><p>Card content goes here</p></div>';
    }

    /**
     * Minify CSS
     */
    protected function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);

        return trim($css);
    }
}
