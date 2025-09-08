<?php
// ABOUTME: This model manages brand fonts for multi-tenant applications using schema-based tenancy
// ABOUTME: Handles font configurations, loading, and CSS generation for brand consistency

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\TenantContextService;

class BrandFont extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_config_id',
        'name',
        'family_name',
        'font_family_css',
        'font_weight',
        'font_style',
        'font_source',
        'font_url',
        'font_file_path',
        'google_font_family',
        'adobe_font_family',
        'custom_font_css',
        'font_formats',
        'supported_weights',
        'fallback_fonts',
        'usage_context',
        'is_system_font',
        'is_google_font',
        'is_adobe_font',
        'is_custom_font',
        'is_web_safe',
        'load_async',
        'preload_font',
        'is_default',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'font_formats' => 'array',
        'supported_weights' => 'array',
        'fallback_fonts' => 'array',
        'is_system_font' => 'boolean',
        'is_google_font' => 'boolean',
        'is_adobe_font' => 'boolean',
        'is_custom_font' => 'boolean',
        'is_web_safe' => 'boolean',
        'load_async' => 'boolean',
        'preload_font' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'font_weight' => 'integer',
    ];

    protected $attributes = [
        'is_system_font' => false,
        'is_google_font' => false,
        'is_adobe_font' => false,
        'is_custom_font' => false,
        'is_web_safe' => true,
        'load_async' => true,
        'preload_font' => false,
        'is_default' => false,
        'sort_order' => 0,
        'font_formats' => '[]',
        'supported_weights' => '[]',
        'fallback_fonts' => '[]',
    ];

    /**
     * Font source types
     */
    public const FONT_SOURCES = [
        'system',
        'google_fonts',
        'adobe_fonts',
        'custom_upload',
        'custom_url',
    ];

    /**
     * Font usage contexts
     */
    public const USAGE_CONTEXTS = [
        'heading',
        'body',
        'accent',
        'monospace',
        'display',
    ];

    /**
     * Common font weights
     */
    public const COMMON_WEIGHTS = [
        100 => 'Thin',
        200 => 'Extra Light',
        300 => 'Light',
        400 => 'Regular',
        500 => 'Medium',
        600 => 'Semi Bold',
        700 => 'Bold',
        800 => 'Extra Bold',
        900 => 'Black',
    ];

    /**
     * Web-safe fonts
     */
    public const WEB_SAFE_FONTS = [
        'Arial',
        'Arial Black',
        'Comic Sans MS',
        'Courier New',
        'Georgia',
        'Impact',
        'Lucida Console',
        'Lucida Sans Unicode',
        'Palatino Linotype',
        'Tahoma',
        'Times New Roman',
        'Trebuchet MS',
        'Verdana',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context global scope for schema-based tenancy
        static::addGlobalScope('tenantContext', function ($builder) {
            TenantContextService::applyTenantScope($builder);
        });
    }

    /**
     * Scope query to specific tenant (legacy compatibility)
     */
    public function scopeForTenant($query, int $tenantId)
    {
        // Legacy method for backward compatibility
        // In schema-based tenancy, tenant context is handled automatically
        return $query;
    }

    /**
     * Scope query to web-safe fonts only
     */
    public function scopeWebSafe($query)
    {
        return $query->where('is_web_safe', true);
    }

    /**
     * Scope query to Google Fonts only
     */
    public function scopeGoogleFonts($query)
    {
        return $query->where('is_google_font', true);
    }

    /**
     * Scope query to system fonts only
     */
    public function scopeSystemFonts($query)
    {
        return $query->where('is_system_font', true);
    }

    /**
     * Scope query to default fonts only
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope query by usage context
     */
    public function scopeByContext($query, string $context)
    {
        return $query->where('usage_context', $context);
    }

    /**
     * Get current tenant information (schema-based tenancy)
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Get the brand config this font belongs to
     */
    public function brandConfig(): BelongsTo
    {
        return $this->belongsTo(BrandConfig::class, 'brand_config_id');
    }

    /**
     * Get the user who created this brand font
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this brand font
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get templates that use this brand font
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_brand_fonts');
    }

    /**
     * Get the full font-family CSS declaration
     */
    public function getFullFontFamily(): string
    {
        $fonts = [$this->font_family_css];

        if ($this->fallback_fonts && is_array($this->fallback_fonts)) {
            $fonts = array_merge($fonts, $this->fallback_fonts);
        } else {
            // Add generic fallbacks based on font style
            $genericFallbacks = match ($this->usage_context) {
                'monospace' => ['monospace'],
                default => ['sans-serif'],
            };
            $fonts = array_merge($fonts, $genericFallbacks);
        }

        return implode(', ', array_map(fn($font) => '"' . $font . '"', $fonts));
    }

    /**
     * Get font loading CSS
     */
    public function getFontLoadingCss(): string
    {
        $css = '';

        // Google Fonts
        if ($this->is_google_font && $this->google_font_family) {
            $weights = $this->supported_weights ? implode(',', array_keys($this->supported_weights)) : '400';
            $css .= "@import url('https://fonts.googleapis.com/css2?family=" .
                   urlencode($this->google_font_family) . ":wght@" . $weights . "&display=swap');\n";
        }

        // Adobe Fonts
        if ($this->is_adobe_font && $this->adobe_font_family) {
            $css .= "/* Adobe Font: " . $this->adobe_font_family . " */\n";
            $css .= "/* Include Adobe Fonts script in your HTML head */\n";
        }

        // Custom font
        if ($this->is_custom_font && $this->custom_font_css) {
            $css .= $this->custom_font_css . "\n";
        }

        return $css;
    }

    /**
     * Get font-face CSS for custom fonts
     */
    public function getFontFaceCss(): string
    {
        if (!$this->is_custom_font || empty($this->font_file_path)) {
            return '';
        }

        $formats = $this->font_formats ?? ['woff2', 'woff'];

        $css = "@font-face {\n";
        $css .= "    font-family: '" . $this->name . "';\n";
        $css .= "    src: ";

        $srcParts = [];
        foreach ($formats as $format) {
            $srcParts[] = "url('" . $this->font_file_path . "." . $format . "') format('" . $format . "')";
        }
        $css .= implode(", ", $srcParts) . ";\n";

        if ($this->font_weight) {
            $css .= "    font-weight: " . $this->font_weight . ";\n";
        }

        if ($this->font_style) {
            $css .= "    font-style: " . $this->font_style . ";\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Check if font is available for use
     */
    public function isAvailable(): bool
    {
        if ($this->is_system_font || $this->is_web_safe) {
            return true;
        }

        if ($this->is_google_font && $this->google_font_family) {
            return true;
        }

        if ($this->is_custom_font && $this->font_file_path) {
            return true;
        }

        return false;
    }

    /**
     * Get supported font weights as array
     */
    public function getSupportedWeightsArray(): array
    {
        return $this->supported_weights ?? [400 => 'Regular'];
    }

    /**
     * Check if specific font weight is supported
     */
    public function supportsWeight(int $weight): bool
    {
        $weights = $this->getSupportedWeightsArray();
        return isset($weights[$weight]);
    }

    /**
     * Get font usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'templates_count' => $this->templates()->count(),
            'is_available' => $this->isAvailable(),
            'font_source' => $this->font_source,
            'usage_context' => $this->usage_context,
            'supported_weights_count' => count($this->getSupportedWeightsArray()),
        ];
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            // tenant_id removed for schema-based tenancy
            'brand_config_id' => 'nullable|exists:brand_configs,id',
            'name' => 'required|string|max:255',
            'family_name' => 'required|string|max:255',
            'font_family_css' => 'nullable|string|max:500',
            'font_weight' => 'nullable|integer|min:100|max:900',
            'font_style' => 'nullable|in:normal,italic,oblique',
            'font_source' => 'required|in:' . implode(',', self::FONT_SOURCES),
            'font_url' => 'nullable|url|max:500',
            'font_file_path' => 'nullable|string|max:500',
            'google_font_family' => 'nullable|string|max:255',
            'adobe_font_family' => 'nullable|string|max:255',
            'custom_font_css' => 'nullable|string',
            'font_formats' => 'nullable|array',
            'supported_weights' => 'nullable|array',
            'fallback_fonts' => 'nullable|array',
            'usage_context' => 'required|in:' . implode(',', self::USAGE_CONTEXTS),
            'is_system_font' => 'boolean',
            'is_google_font' => 'boolean',
            'is_adobe_font' => 'boolean',
            'is_custom_font' => 'boolean',
            'is_web_safe' => 'boolean',
            'load_async' => 'boolean',
            'preload_font' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}
