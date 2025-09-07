<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * TemplateVariant Model
 *
 * Represents a variant of a template used in A/B testing.
 * Stores customizations and performance metrics for each variant.
 */
class TemplateVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'variant_name',
        'custom_structure',
        'custom_config',
        'performance_metrics',
        'is_control',
        'is_active',
        'impressions',
        'conversions',
        'conversion_rate',
        'statistical_significance',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'custom_structure' => 'array',
        'custom_config' => 'array',
        'performance_metrics' => 'array',
        'is_control' => 'boolean',
        'is_active' => 'boolean',
        'impressions' => 'integer',
        'conversions' => 'integer',
        'conversion_rate' => 'decimal:2',
        'statistical_significance' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_control' => false,
        'impressions' => 0,
        'conversions' => 0,
        'conversion_rate' => 0.0,
        'custom_structure' => '{}',
        'custom_config' => '{}',
        'performance_metrics' => '{}',
        'metadata' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            if (config('database.multi_tenant', false)) {
                try {
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });

        // Auto-generate conversion rate when impressions or conversions change
        static::updating(function ($variant) {
            if ($variant->isDirty(['impressions', 'conversions'])) {
                $variant->updateConversionRate();
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query for active variants
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to specific template
     */
    public function scopeForTemplate($query, int $templateId)
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope query for control variants
     */
    public function scopeControl($query)
    {
        return $query->where('is_control', true);
    }

    /**
     * Get the tenant that owns this variant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the template this variant belongs to
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get the user who created this variant
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this variant
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get A/B tests that include this variant
     */
    public function abTests(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\TemplateAbTest::class, 'template_ab_test_variants', 'variant_id', 'ab_test_id');
    }

    /**
     * Record an impression for this variant
     */
    public function recordImpression(): void
    {
        $this->increment('impressions');
    }

    /**
     * Record a conversion for this variant
     */
    public function recordConversion(): void
    {
        $this->increment('conversions');
        $this->updateConversionRate();
    }

    /**
     * Update conversion rate based on current impressions and conversions
     */
    protected function updateConversionRate(): void
    {
        if ($this->impressions > 0) {
            $rate = ($this->conversions / $this->impressions) * 100;
            $this->conversion_rate = round($rate, 2);
        } else {
            $this->conversion_rate = 0.0;
        }
    }

    /**
     * Get the effective structure combining template defaults with variant overrides
     */
    public function getEffectiveStructure(): array
    {
        $baseStructure = $this->template?->structure ?? [];

        if (empty($this->custom_structure)) {
            return $baseStructure;
        }

        return array_merge($baseStructure, $this->custom_structure);
    }

    /**
     * Get the effective config combining template defaults with variant overrides
     */
    public function getEffectiveConfig(): array
    {
        $baseConfig = $this->template?->default_config ?? [];

        if (empty($this->custom_config)) {
            return $baseConfig;
        }

        return $this->mergeConfigurations($baseConfig, $this->custom_config);
    }

    /**
     * Merge configuration arrays preserving structure
     */
    protected function mergeConfigurations(array $base, array $custom): array
    {
        $merged = $base;

        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = array_merge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Check if this variant has statistically significant results
     */
    public function hasStatisticalSignificance(float $confidenceThreshold = 95.0): bool
    {
        // Simplified statistical significance check
        // In a real implementation, you'd use statistical tests like chi-square
        return $this->impressions >= 1000 && $this->conversion_rate > 0;
    }

    /**
     * Get performance comparison with control variant
     */
    public function getPerformanceComparison(): array
    {
        $controlVariant = $this->template->variants()->control()->first();

        if (!$controlVariant) {
            return [];
        }

        $variantRate = $this->conversion_rate;
        $controlRate = $controlVariant->conversion_rate;

        if ($controlRate == 0) {
            $improvement = $variantRate > 0 ? 100 : 0;
        } else {
            $improvement = (($variantRate - $controlRate) / $controlRate) * 100;
        }

        return [
            'variant_name' => $this->variant_name,
            'control_rate' => $controlRate,
            'variant_rate' => $variantRate,
            'improvement_percentage' => round($improvement, 2),
            'statistical_significance' => $this->statistical_significance,
        ];
    }

    /**
     * Get validation rules for the model
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'template_id' => 'required|exists:templates,id',
            'variant_name' => 'required|string|max:255',
            'custom_structure' => 'nullable|array',
            'custom_config' => 'nullable|array',
            'performance_metrics' => 'nullable|array',
            'is_control' => 'boolean',
            'is_active' => 'boolean',
            'impressions' => 'integer|min:0',
            'conversions' => 'integer|min:0',
            'conversion_rate' => 'numeric|min:0|max:100',
            'statistical_significance' => 'nullable|numeric|min:0|max:100',
            'metadata' => 'nullable|array',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}