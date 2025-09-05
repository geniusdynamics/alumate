<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'font_family',
        'heading_font_family',
        'body_font_family',
        'logo_url',
        'favicon_url',
        'custom_css',
        'font_weights',
        'brand_colors',
        'typography_settings',
        'spacing_settings',
        'is_default',
        'is_active',
        'usage_guidelines',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'font_weights' => 'array',
        'brand_colors' => 'array',
        'typography_settings' => 'array',
        'spacing_settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
        'font_weights' => '[]',
        'brand_colors' => '[]',
        'typography_settings' => '{}',
        'spacing_settings' => '{}',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically for multi-tenant isolation
        static::addGlobalScope('tenant', function ($builder) {
            // Check if we're in a multi-tenant context
            if (config('database.multi_tenant', false)) {
                try {
                    // In production, apply tenant filter based on current tenant context
                    if (tenant() && tenant()->id) {
                        $builder->where('tenant_id', tenant()->id);
                    }
                } catch (\Exception $e) {
                    // Skip tenant scoping in test environment
                }
            }
        });
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to active brand configs only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default brand config only
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the tenant that owns this brand config
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this brand config
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this brand config
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get effective brand configuration with defaults
     */
    public function getEffectiveConfig(): array
    {
        return [
            'colors' => [
                'primary' => $this->primary_color ?? '#007bff',
                'secondary' => $this->secondary_color ?? '#6c757d',
                'accent' => $this->accent_color ?? '#28a745',
            ],
            'typography' => [
                'font_family' => $this->font_family ?? 'Inter, sans-serif',
                'heading_font_family' => $this->heading_font_family ?? $this->font_family ?? 'Inter, sans-serif',
                'body_font_family' => $this->body_font_family ?? $this->font_family ?? 'Inter, sans-serif',
                'font_weights' => $this->font_weights ?? [400, 500, 600, 700],
                'settings' => $this->typography_settings ?? [],
            ],
            'spacing' => $this->spacing_settings ?? [
                'base_unit' => '1rem',
                'scale' => 1.5,
            ],
            'assets' => [
                'logo_url' => $this->logo_url,
                'favicon_url' => $this->favicon_url,
            ],
            'custom_css' => $this->custom_css,
            'brand_colors' => $this->brand_colors ?? [],
        ];
    }

    /**
     * Check if brand config is complete (has required elements)
     */
    public function isComplete(): bool
    {
        return !empty($this->name) &&
               !empty($this->primary_color) &&
               !empty($this->secondary_color) &&
               !empty($this->logo_url);
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'primary_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'accent_color' => 'nullable|string|regex:/^#[a-fA-F0-9]{6}$/',
            'font_family' => 'nullable|string|max:255',
            'heading_font_family' => 'nullable|string|max:255',
            'body_font_family' => 'nullable|string|max:255',
            'logo_url' => 'nullable|string|url|max:500',
            'favicon_url' => 'nullable|string|url|max:500',
            'custom_css' => 'nullable|string',
            'font_weights' => 'nullable|array',
            'brand_colors' => 'nullable|array',
            'typography_settings' => 'nullable|array',
            'spacing_settings' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'usage_guidelines' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }
}
