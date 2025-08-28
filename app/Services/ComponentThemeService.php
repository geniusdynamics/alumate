<?php

namespace App\Services;

use App\Models\ComponentTheme;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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

        // Store compiled CSS file
        $cssFilePath = "themes/{$theme->tenant_id}/theme_{$theme->id}.css";
        Storage::disk('public')->put($cssFilePath, $theme->compileToCss());

        return [
            'css_variables' => $cssVariables,
            'affected_components' => $affectedComponents,
            'css_file_path' => $cssFilePath,
            'cache_key' => $cacheKey,
        ];
    }

    /**
     * Create theme with inheritance from default theme
     */
    public function createThemeWithInheritance(
        $tenantId,
        string $name,
        array $config,
        ?ComponentTheme $parentTheme = null
    ): ComponentTheme {
        // Get or create default theme if no parent specified
        if (! $parentTheme) {
            $parentTheme = $this->getOrCreateDefaultTheme($tenantId);
        }

        // Merge configuration with parent theme
        $mergedConfig = $this->mergeThemeConfigs($parentTheme->config, $config);

        // Validate the merged configuration
        $this->validateThemeConfig($mergedConfig);

        // Create the new theme
        $theme = ComponentTheme::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => str($name)->slug(),
            'config' => $mergedConfig,
            'is_default' => false,
        ]);

        // Clear related caches
        $this->clearThemeCaches($tenantId);

        return $theme;
    }

    /**
     * Validate theme configuration for color schemes, typography, and spacing
     */
    public function validateThemeConfig(array $config): bool
    {
        // Basic structure validation
        if (! isset($config['colors']) || ! is_array($config['colors'])) {
            throw ValidationException::withMessages(['colors' => 'Colors configuration is required']);
        }

        if (! isset($config['typography']) || ! is_array($config['typography'])) {
            throw ValidationException::withMessages(['typography' => 'Typography configuration is required']);
        }

        if (! isset($config['spacing']) || ! is_array($config['spacing'])) {
            throw ValidationException::withMessages(['spacing' => 'Spacing configuration is required']);
        }

        // Validate required fields
        if (! isset($config['colors']['primary'])) {
            throw ValidationException::withMessages(['colors.primary' => 'Primary color is required']);
        }

        if (! isset($config['typography']['font_family'])) {
            throw ValidationException::withMessages(['typography.font_family' => 'Font family is required']);
        }

        if (! isset($config['spacing']['base'])) {
            throw ValidationException::withMessages(['spacing.base' => 'Base spacing is required']);
        }

        // Validate color formats
        foreach ($config['colors'] as $key => $color) {
            if ($color && ! $this->isValidHexColor($color)) {
                throw ValidationException::withMessages(["colors.{$key}" => 'Invalid hex color format']);
            }
        }

        // Custom validation for accessibility
        $this->validateAccessibilitySimple($config);

        return true;
    }

    /**
     * Check if a string is a valid hex color
     */
    private function isValidHexColor(string $color): bool
    {
        return preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color) === 1;
    }

    /**
     * Validate accessibility requirements (simplified)
     */
    private function validateAccessibilitySimple(array $config): void
    {
        if (! isset($config['colors'])) {
            return;
        }

        $colors = $config['colors'];

        // Check contrast ratios
        if (isset($colors['primary']) && isset($colors['background'])) {
            $contrast = $this->calculateContrastRatio($colors['primary'], $colors['background']);
            if ($contrast < 3.0) {
                throw ValidationException::withMessages(['colors.primary' => 'Primary color must have sufficient contrast with background (minimum 3:1 ratio)']);
            }
        }

        if (isset($colors['text']) && isset($colors['background'])) {
            $contrast = $this->calculateContrastRatio($colors['text'], $colors['background']);
            if ($contrast < 4.5) {
                throw ValidationException::withMessages(['colors.text' => 'Text color must meet WCAG AA standards (minimum 4.5:1 contrast ratio)']);
            }
        }
    }

    /**
     * Implement multi-tenant theme isolation and access control
     */
    public function getThemesForTenant($tenantId, bool $includeDefault = true): Collection
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
    public function validateTenantAccess(ComponentTheme $theme, $tenantId): bool
    {
        return $theme->tenant_id === $tenantId;
    }

    /**
     * Generate theme preview with sample components
     */
    public function generateThemePreview(ComponentTheme $theme, array $components = []): array
    {
        $previewHtml = $theme->generatePreviewHtml();

        // Generate component-specific previews
        $componentPreviews = [];
        foreach ($components as $componentType) {
            $componentPreviews[$componentType] = $this->generateComponentPreview($theme, $componentType);
        }

        // Generate responsive previews
        $responsivePreviews = $this->generateResponsivePreviews($theme);

        return [
            'html' => $previewHtml,
            'css' => $theme->generateCssVariables(),
            'component_previews' => $componentPreviews,
            'responsive_previews' => $responsivePreviews,
            'accessibility_report' => $theme->checkAccessibility(),
        ];
    }

    /**
     * Compile theme CSS with optimization
     */
    public function compileThemeCss(ComponentTheme $theme, bool $minify = false): string
    {
        $css = $theme->compileToCss();

        if ($minify) {
            $css = $this->minifyCss($css);
        }

        // Add theme metadata as comment
        $metadata = "/* Theme: {$theme->name} | Generated: ".now()->toISOString()." */\n";

        return $metadata.$css;
    }

    /**
     * Create theme backup
     */
    public function backupTheme(ComponentTheme $theme): string
    {
        $backup = [
            'theme_data' => $theme->toArray(),
            'created_at' => now()->toISOString(),
            'version' => '1.0',
        ];

        $backupPath = "theme_backups/{$theme->tenant_id}/theme_{$theme->id}_".now()->format('Y-m-d_H-i-s').'.json';

        Storage::disk('local')->put($backupPath, json_encode($backup, JSON_PRETTY_PRINT));

        return $backupPath;
    }

    /**
     * Restore theme from backup
     */
    public function restoreTheme(string $backupPath, $tenantId): ComponentTheme
    {
        if (! Storage::disk('local')->exists($backupPath)) {
            throw new \Exception('Backup file not found');
        }

        $backupData = json_decode(Storage::disk('local')->get($backupPath), true);

        if (! $backupData || ! isset($backupData['theme_data'])) {
            throw new \Exception('Invalid backup file format');
        }

        $themeData = $backupData['theme_data'];

        // Ensure tenant isolation
        $themeData['tenant_id'] = $tenantId;
        unset($themeData['id'], $themeData['created_at'], $themeData['updated_at']);

        // Validate configuration
        $this->validateThemeConfig($themeData['config']);

        // Create restored theme
        $restoredTheme = ComponentTheme::create($themeData);

        // Clear caches
        $this->clearThemeCaches($tenantId);

        return $restoredTheme;
    }

    /**
     * Get or create default theme for tenant
     */
    public function getOrCreateDefaultTheme($tenantId): ComponentTheme
    {
        $defaultTheme = ComponentTheme::forTenant($tenantId)
            ->where('is_default', true)
            ->first();

        if (! $defaultTheme) {
            $defaultTheme = ComponentTheme::createDefaultTheme($tenantId);
        }

        return $defaultTheme;
    }

    /**
     * Merge theme configurations with proper inheritance
     */
    private function mergeThemeConfigs(array $parentConfig, array $childConfig): array
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
     * Generate component-specific preview
     */
    private function generateComponentPreview(ComponentTheme $theme, string $componentType): string
    {
        $css = $theme->generateCssVariables();

        $templates = [
            'hero' => '<div class="hero-preview" style="background: var(--color-primary); color: white; padding: var(--spacing-lg);"><h1 style="font-family: var(--font-heading);">Hero Section</h1><p>Sample hero content</p></div>',
            'button' => '<button style="background: var(--color-primary); color: white; padding: var(--spacing-sm) var(--spacing-base); border-radius: var(--border-radius);">Sample Button</button>',
            'form' => '<form style="padding: var(--spacing-base);"><input type="text" placeholder="Sample Input" style="padding: var(--spacing-sm); border-radius: var(--border-radius);"><button type="submit" style="background: var(--color-primary); color: white; padding: var(--spacing-sm) var(--spacing-base);">Submit</button></form>',
        ];

        $template = $templates[$componentType] ?? '<div>Component preview not available</div>';

        return "<style>{$css}</style>{$template}";
    }

    /**
     * Generate responsive previews for different screen sizes
     */
    private function generateResponsivePreviews(ComponentTheme $theme): array
    {
        $css = $theme->generateCssVariables();

        return [
            'desktop' => ['width' => '1200px', 'css' => $css],
            'tablet' => ['width' => '768px', 'css' => $css],
            'mobile' => ['width' => '375px', 'css' => $css],
        ];
    }

    /**
     * Calculate color contrast ratio for accessibility
     */
    private function calculateContrastRatio(string $color1, string $color2): float
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
    private function hexToRgb(string $hex): array
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

    /**
     * Minify CSS for production
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // Remove unnecessary whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove whitespace around specific characters
        $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);

        return trim($css);
    }

    /**
     * Clear theme-related caches
     */
    private function clearThemeCaches($tenantId): void
    {
        $themes = ComponentTheme::forTenant($tenantId)->get();

        foreach ($themes as $theme) {
            Cache::forget("theme_css_{$theme->id}");
            Cache::forget("theme_preview_{$theme->id}");
        }

        Cache::forget("tenant_themes_{$tenantId}");
    }

    /**
     * Get theme usage statistics
     */
    public function getThemeUsageStats(ComponentTheme $theme): array
    {
        $componentsCount = $theme->components()->count();
        $lastUsed = $theme->components()->latest('updated_at')->first()?->updated_at;

        return [
            'components_using_theme' => $componentsCount,
            'last_used' => $lastUsed,
            'is_active' => $componentsCount > 0,
            'accessibility_score' => $this->calculateAccessibilityScore($theme),
        ];
    }

    /**
     * Calculate accessibility score for theme
     */
    private function calculateAccessibilityScore(ComponentTheme $theme): int
    {
        $issues = $theme->checkAccessibility();
        $maxScore = 100;
        $deductionPerIssue = 20;

        return max(0, $maxScore - (count($issues) * $deductionPerIssue));
    }

    /**
     * Duplicate theme with modifications
     */
    public function duplicateTheme(ComponentTheme $originalTheme, string $newName, array $configOverrides = []): ComponentTheme
    {
        $newConfig = array_merge($originalTheme->config, $configOverrides);

        // Validate new configuration
        $this->validateThemeConfig($newConfig);

        $duplicatedTheme = ComponentTheme::create([
            'tenant_id' => $originalTheme->tenant_id,
            'name' => $newName,
            'slug' => str($newName)->slug(),
            'config' => $newConfig,
            'is_default' => false,
        ]);

        // Clear caches
        $this->clearThemeCaches($originalTheme->tenant_id);

        return $duplicatedTheme;
    }

    /**
     * Export theme configuration
     */
    public function exportTheme(ComponentTheme $theme): array
    {
        return [
            'name' => $theme->name,
            'config' => $theme->config,
            'metadata' => [
                'exported_at' => now()->toISOString(),
                'version' => '1.0',
                'accessibility_score' => $this->calculateAccessibilityScore($theme),
            ],
        ];
    }

    /**
     * Import theme configuration
     */
    public function importTheme(array $themeData, $tenantId, string $name): ComponentTheme
    {
        if (! isset($themeData['config'])) {
            throw new \Exception('Invalid theme data: missing configuration');
        }

        // Validate configuration
        $this->validateThemeConfig($themeData['config']);

        $importedTheme = ComponentTheme::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => str($name)->slug(),
            'config' => $themeData['config'],
            'is_default' => false,
        ]);

        // Clear caches
        $this->clearThemeCaches($tenantId);

        return $importedTheme;
    }
}
