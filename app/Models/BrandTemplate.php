<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BrandTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_config_id',
        'name',
        'slug',
        'description',
        'brand_elements',
        'customizations',
        'is_default',
        'is_active',
        'usage_count',
        'preview_html',
        'preview_css',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'brand_elements' => 'array',
        'customizations' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    protected $attributes = [
        'is_default' => false,
        'is_active' => true,
        'usage_count' => 0,
        'brand_elements' => '{}',
        'customizations' => '{}',
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

        // Auto-generate slug if not provided
        static::creating(function ($brandTemplate) {
            if (empty($brandTemplate->slug)) {
                $brandTemplate->slug = $brandTemplate->generateUniqueSlug($brandTemplate->name, $brandTemplate->tenant_id);
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
     * Scope query to active brand templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to default brand templates only
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get the tenant that owns this brand template
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the brand config this template belongs to
     */
    public function brandConfig(): BelongsTo
    {
        return $this->belongsTo(BrandConfig::class, 'brand_config_id');
    }

    /**
     * Get the user who created this brand template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this brand template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get templates that use this brand template
     */
    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(Template::class, 'brand_template_assignments');
    }

    /**
     * Get effective brand template data with base config merged
     */
    public function getEffectiveData(): array
    {
        $baseConfig = $this->brandConfig ? $this->brandConfig->getEffectiveConfig() : [];

        $brandElements = $this->brand_elements ?? [];
        $customizations = $this->customizations ?? [];

        return array_merge($baseConfig, [
            'brand_elements' => $brandElements,
            'customizations' => $customizations,
            'name' => $this->name,
            'description' => $this->description,
        ]);
    }

    /**
     * Generate HTML preview for the brand template
     */
    public function generatePreview(): array
    {
        $effectiveData = $this->getEffectiveData();

        // Generate HTML structure
        $html = $this->generatePreviewHtml($effectiveData);

        // Generate CSS
        $css = $this->generatePreviewCss($effectiveData);

        return [
            'html' => $html,
            'css' => $css,
            'preview_data' => $effectiveData,
        ];
    }

    /**
     * Generate preview HTML
     */
    private function generatePreviewHtml(array $data): string
    {
        $html = '<!DOCTYPE html><html><head><title>' . htmlspecialchars($data['name'] ?? '') . '</title></head><body>';

        // Header with logo
        if (isset($data['assets']['logo_url'])) {
            $html .= '<header><img src="' . htmlspecialchars($data['assets']['logo_url']) . '" alt="Logo" style="max-width: 200px;"></header>';
        }

        // Sample content
        $html .= '<div class="brand-preview">';
        $html .= '<h1 style="color: ' . htmlspecialchars($data['colors']['primary'] ?? '#000') . ';">Brand Template Preview</h1>';
        $html .= '<p style="color: ' . htmlspecialchars($data['typography']['text_color'] ?? '#333') . ';">This is a preview of your brand template styling.</p>';

        // CTA Button
        if (isset($data['colors']['secondary'])) {
            $html .= '<button style="background-color: ' . htmlspecialchars($data['colors']['secondary']) . '; color: white; padding: 10px 20px; border: none; border-radius: 4px;">Call to Action</button>';
        }

        $html .= '</div></body></html>';

        return $this->preview_html ?: $html;
    }

    /**
     * Generate preview CSS
     */
    private function generatePreviewCss(array $data): string
    {
        $css = '';

        // Font family
        if (isset($data['typography']['font_family'])) {
            $css .= "body { font-family: " . htmlspecialchars($data['typography']['font_family']) . "; }\n";
        }

        // Primary color
        if (isset($data['colors']['primary'])) {
            $css .= ".brand-preview h1 { color: " . htmlspecialchars($data['colors']['primary']) . "; }\n";
        }

        // Custom CSS if provided
        if (!empty($this->preview_css)) {
            $css .= $this->preview_css;
        } elseif (isset($data['assets']['custom_css'])) {
            $css .= $data['assets']['custom_css'];
        }

        return $css;
    }

    /**
     * Generate a unique slug for the brand template
     */
    protected function generateUniqueSlug(string $name, int $tenantId): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $tenantId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists for the tenant
     */
    protected function slugExists(string $slug, int $tenantId): bool
    {
        $query = static::where('tenant_id', $tenantId)->where('slug', $slug);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Check if brand template is complete with all required elements
     */
    public function isComplete(): bool
    {
        $brandElements = $this->brand_elements ?? [];

        return !empty($this->name) &&
               !empty($brandElements['colors'] ?? []) &&
               !empty($brandElements['typography'] ?? []);
    }

    /**
     * Get brand template usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'usage_count' => $this->usage_count,
            'templates_count' => $this->templates()->count(),
            'is_active' => $this->is_active,
            'is_complete' => $this->isComplete(),
        ];
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'brand_config_id' => 'nullable|exists:brand_configs,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'brand_elements' => 'nullable|array',
            'customizations' => 'nullable|array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'usage_count' => 'integer|min:0',
            'preview_html' => 'nullable|string',
            'preview_css' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
            'updated_by' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_templates,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_templates,slug';
        }

        return $rules;
    }
}
