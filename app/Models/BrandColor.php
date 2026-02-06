<?php
// ABOUTME: Brand color model for managing brand color palettes and accessibility
// ABOUTME: Updated for schema-based multi-tenancy without tenant_id column

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\TenantContextService;

class BrandColor extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_config_id',
        'name',
        'hex_value',
        'rgb_value',
        'hsl_value',
        'cmyk_value',
        'usage_context',
        'accessibility_rating',
        'contrast_ratios',
        'is_accessible',
        'is_default',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'contrast_ratios' => 'array',
        'is_accessible' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'accessibility_rating' => 'integer',
    ];

    protected $attributes = [
        'is_accessible' => true,
        'is_default' => false,
        'sort_order' => 0,
        'contrast_ratios' => '[]',
    ];

    /**
     * Available usage contexts for brand colors
     */
    public const USAGE_CONTEXTS = [
        'primary',
        'secondary',
        'accent',
        'success',
        'warning',
        'error',
        'info',
        'neutral',
        'background',
        'text',
        'border',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context for schema-based multi-tenancy
        static::addGlobalScope('tenant_context', function ($builder) {
            TenantContextService::applyTenantContext($builder);
        });
    }

    /**
     * Scope query to specific tenant (legacy compatibility)
     */
    public function scopeForTenant($query, int $tenantId = null)
    {
        // For schema-based tenancy, this is handled by global scope
        return $query;
    }

    /**
     * Scope query to accessible colors only
     */
    public function scopeAccessible($query)
    {
        return $query->where('is_accessible', true);
    }

    /**
     * Scope query to default colors only
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
     * Get the current tenant context
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Get the brand config this color belongs to
     */
    public function brandConfig(): BelongsTo
    {
        return $this->belongsTo(BrandConfig::class, 'brand_config_id');
    }

    /**
     * Get the user who created this brand color
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this brand color
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get templates that use this brand color
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'template_brand_colors');
    }

    /**
     * Convert hex to RGB
     */
    public function getRgbArray(): array
    {
        $hex = ltrim($this->hex_value, '#');

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Get RGB value as comma-separated string
     */
    public function getRgbString(): string
    {
        if ($this->rgb_value) {
            return $this->rgb_value;
        }

        $rgb = $this->getRgbArray();
        return implode(', ', $rgb);
    }

    /**
     * Calculate contrast ratio with another color
     */
    public function contrastRatioWith(BrandColor $otherColor): float
    {
        $rgb1 = $this->getRgbArray();
        $rgb2 = $otherColor->getRgbArray();

        $l1 = $this->calculateLuminance($rgb1);
        $l2 = $this->calculateLuminance($rgb2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Calculate relative luminance
     */
    private function calculateLuminance(array $rgb): float
    {
        $rsRGB = $rgb['r'] / 255;
        $gsRGB = $rgb['g'] / 255;
        $bsRGB = $rgb['b'] / 255;

        $r = $rsRGB <= 0.03928 ? $rsRGB / 12.92 : pow(($rsRGB + 0.055) / 1.055, 2.4);
        $g = $gsRGB <= 0.03928 ? $gsRGB / 12.92 : pow(($gsRGB + 0.055) / 1.055, 2.4);
        $b = $bsRGB <= 0.03928 ? $bsRGB / 12.92 : pow(($bsRGB + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Check if color passes AA accessibility standard
     */
    public function passesAA(): bool
    {
        // Assume we compare against white background for text colors
        // In practice, this should compare against the actual background colors used
        $ratios = $this->contrast_ratios ?? [];

        foreach ($ratios as $ratio) {
            if ($ratio >= 4.5) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if color passes AAA accessibility standard
     */
    public function passesAAA(): bool
    {
        $ratios = $this->contrast_ratios ?? [];

        foreach ($ratios as $ratio) {
            if ($ratio >= 7.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get color usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'templates_count' => $this->templates()->count(),
            'is_accessible' => $this->is_accessible,
            'passes_aa' => $this->passesAA(),
            'passes_aaa' => $this->passesAAA(),
            'usage_context' => $this->usage_context,
            'contrast_score' => $this->accessibility_rating ?? 0,
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
            'hex_value' => 'required|string|regex:/^#[a-fA-F0-9]{6}$/',
            'rgb_value' => 'nullable|string|max:255',
            'hsl_value' => 'nullable|string|max:255',
            'cmyk_value' => 'nullable|string|max:255',
            'usage_context' => 'required|in:' . implode(',', self::USAGE_CONTEXTS),
            'accessibility_rating' => 'nullable|integer|min:1|max:5',
            'contrast_ratios' => 'nullable|array',
            'is_accessible' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer|min:0',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}
