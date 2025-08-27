<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ComponentTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'config',
        'is_default',
    ];

    protected $casts = [
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model and add global scopes
     */
    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Get the tenant that owns this theme
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get components using this theme
     */
    public function components(): HasMany
    {
        return $this->hasMany(Component::class, 'theme_id');
    }

    /**
     * Validate theme configuration
     */
    public function validateConfig(array $config): bool
    {
        $validator = Validator::make($config, [
            'colors' => 'required|array',
            'colors.primary' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'colors.secondary' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'colors.accent' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'colors.background' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'colors.text' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'typography' => 'required|array',
            'typography.font_family' => 'required|string|max:100',
            'typography.heading_font' => 'nullable|string|max:100',
            'typography.font_sizes' => 'nullable|array',
            'typography.font_sizes.base' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'typography.font_sizes.heading' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'typography.line_height' => 'nullable|numeric|min:1|max:3',
            'spacing' => 'required|array',
            'spacing.base' => 'required|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'spacing.small' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'spacing.large' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'spacing.section_padding' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'borders' => 'nullable|array',
            'borders.radius' => 'nullable|string|regex:/^\d+(\.\d+)?(px|rem|em)$/',
            'borders.width' => 'nullable|string|regex:/^\d+(\.\d+)?px$/',
            'shadows' => 'nullable|array',
            'animations' => 'nullable|array',
            'animations.duration' => 'nullable|string|regex:/^\d+(\.\d+)?s$/',
            'animations.easing' => 'nullable|string|in:ease,ease-in,ease-out,ease-in-out,linear',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return true;
    }

    /**
     * Apply theme to components
     */
    public function applyToComponents(array $componentIds = []): int
    {
        $query = Component::query();

        if (! empty($componentIds)) {
            $query->whereIn('id', $componentIds);
        } else {
            $query->where('tenant_id', $this->tenant_id);
        }

        return $query->update(['theme_id' => $this->id]);
    }

    /**
     * Get theme inheritance chain (for default theme inheritance)
     */
    public function getInheritanceChain(): array
    {
        $chain = [$this->config];

        // If this is not a default theme, inherit from default theme
        if (! $this->is_default) {
            $defaultTheme = static::where('tenant_id', $this->tenant_id)
                ->where('is_default', true)
                ->first();

            if ($defaultTheme && $defaultTheme->id !== $this->id) {
                $chain[] = $defaultTheme->config;
            }
        }

        return $chain;
    }

    /**
     * Get merged configuration with inheritance
     */
    public function getMergedConfig(): array
    {
        $chain = $this->getInheritanceChain();

        // Merge configurations from most general to most specific
        $mergedConfig = [];
        foreach (array_reverse($chain) as $config) {
            $mergedConfig = array_merge_recursive($mergedConfig, $config ?? []);
        }

        return $mergedConfig;
    }

    /**
     * Generate CSS variables from theme configuration
     */
    public function generateCssVariables(): string
    {
        $config = $this->getMergedConfig();
        $css = ":root {\n";

        // Colors
        if (isset($config['colors'])) {
            foreach ($config['colors'] as $name => $value) {
                $css .= "  --color-{$name}: {$value};\n";
            }
        }

        // Typography
        if (isset($config['typography'])) {
            if (isset($config['typography']['font_family'])) {
                $css .= "  --font-family: {$config['typography']['font_family']};\n";
            }
            if (isset($config['typography']['heading_font'])) {
                $css .= "  --font-heading: {$config['typography']['heading_font']};\n";
            }
            if (isset($config['typography']['font_sizes'])) {
                foreach ($config['typography']['font_sizes'] as $size => $value) {
                    $css .= "  --font-size-{$size}: {$value};\n";
                }
            }
            if (isset($config['typography']['line_height'])) {
                $css .= "  --line-height: {$config['typography']['line_height']};\n";
            }
        }

        // Spacing
        if (isset($config['spacing'])) {
            foreach ($config['spacing'] as $name => $value) {
                $css .= "  --spacing-{$name}: {$value};\n";
            }
        }

        // Borders
        if (isset($config['borders'])) {
            foreach ($config['borders'] as $name => $value) {
                $css .= "  --border-{$name}: {$value};\n";
            }
        }

        // Animations
        if (isset($config['animations'])) {
            foreach ($config['animations'] as $name => $value) {
                $css .= "  --animation-{$name}: {$value};\n";
            }
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Generate theme preview HTML
     */
    public function generatePreviewHtml(): string
    {
        $config = $this->getMergedConfig();
        $css = $this->generateCssVariables();

        $html = "<!DOCTYPE html>\n<html>\n<head>\n";
        $html .= "<style>\n{$css}\n";
        $html .= "
        body { 
            font-family: var(--font-family, 'Arial, sans-serif'); 
            line-height: var(--line-height, 1.6);
            margin: var(--spacing-base, 1rem);
            background-color: var(--color-background, #ffffff);
            color: var(--color-text, #333333);
        }
        .preview-section { 
            margin-bottom: var(--spacing-large, 2rem); 
            padding: var(--spacing-section-padding, 1.5rem);
            border-radius: var(--border-radius, 8px);
        }
        .preview-heading { 
            font-family: var(--font-heading, var(--font-family, 'Arial, sans-serif'));
            font-size: var(--font-size-heading, 2rem);
            color: var(--color-primary, #007bff);
            margin-bottom: var(--spacing-base, 1rem);
        }
        .preview-button {
            background-color: var(--color-primary, #007bff);
            color: white;
            padding: var(--spacing-small, 0.5rem) var(--spacing-base, 1rem);
            border: var(--border-width, 1px) solid var(--color-primary, #007bff);
            border-radius: var(--border-radius, 4px);
            cursor: pointer;
            transition: all var(--animation-duration, 0.3s) var(--animation-easing, ease);
        }
        .preview-button:hover {
            background-color: var(--color-secondary, #0056b3);
        }
        ";
        $html .= "</style>\n</head>\n<body>\n";
        $html .= "<div class='preview-section'>\n";
        $html .= "<h1 class='preview-heading'>Theme Preview: {$this->name}</h1>\n";
        $html .= "<p>This is a sample paragraph showing the typography and spacing settings.</p>\n";
        $html .= "<button class='preview-button'>Sample Button</button>\n";
        $html .= "</div>\n";
        $html .= "</body>\n</html>";

        return $html;
    }

    /**
     * Compile theme to CSS file
     */
    public function compileToCss(): string
    {
        $css = $this->generateCssVariables();

        // Add component-specific styles
        $css .= "\n/* Component Styles */\n";
        $css .= ".component { font-family: var(--font-family); }\n";
        $css .= ".component-heading { font-family: var(--font-heading, var(--font-family)); }\n";
        $css .= ".component-button { \n";
        $css .= "  background-color: var(--color-primary);\n";
        $css .= "  color: white;\n";
        $css .= "  padding: var(--spacing-small) var(--spacing-base);\n";
        $css .= "  border-radius: var(--border-radius);\n";
        $css .= "  transition: all var(--animation-duration) var(--animation-easing);\n";
        $css .= "}\n";
        $css .= ".component-button:hover { background-color: var(--color-secondary); }\n";

        return $css;
    }

    /**
     * Create a default theme for a tenant
     */
    public static function createDefaultTheme(int $tenantId, string $name = 'Default Theme'): self
    {
        $defaultConfig = [
            'colors' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'accent' => '#28a745',
                'background' => '#ffffff',
                'text' => '#333333',
            ],
            'typography' => [
                'font_family' => 'Arial, sans-serif',
                'heading_font' => 'Georgia, serif',
                'font_sizes' => [
                    'base' => '16px',
                    'heading' => '2rem',
                ],
                'line_height' => 1.6,
            ],
            'spacing' => [
                'base' => '1rem',
                'small' => '0.5rem',
                'large' => '2rem',
                'section_padding' => '1.5rem',
            ],
            'borders' => [
                'radius' => '4px',
                'width' => '1px',
            ],
            'animations' => [
                'duration' => '0.3s',
                'easing' => 'ease',
            ],
        ];

        return static::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => str($name)->slug(),
            'config' => $defaultConfig,
            'is_default' => true,
        ]);
    }

    /**
     * Get color contrast ratio for accessibility
     */
    public function getColorContrast(string $color1, string $color2): float
    {
        // Convert hex to RGB
        $rgb1 = $this->hexToRgb($color1);
        $rgb2 = $this->hexToRgb($color2);

        // Calculate relative luminance
        $l1 = $this->getRelativeLuminance($rgb1);
        $l2 = $this->getRelativeLuminance($rgb2);

        // Calculate contrast ratio
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Convert hex color to RGB array
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
     * Calculate relative luminance for accessibility
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
     * Check if theme meets accessibility standards
     */
    public function checkAccessibility(): array
    {
        $config = $this->getMergedConfig();
        $issues = [];

        if (isset($config['colors']['primary']) && isset($config['colors']['background'])) {
            $contrast = $this->getColorContrast($config['colors']['primary'], $config['colors']['background']);
            if ($contrast < 4.5) {
                $issues[] = "Primary color contrast ratio ({$contrast}) is below WCAG AA standard (4.5:1)";
            }
        }

        if (isset($config['colors']['text']) && isset($config['colors']['background'])) {
            $contrast = $this->getColorContrast($config['colors']['text'], $config['colors']['background']);
            if ($contrast < 4.5) {
                $issues[] = "Text color contrast ratio ({$contrast}) is below WCAG AA standard (4.5:1)";
            }
        }

        return $issues;
    }
}
