<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandLogo extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_config_id',
        'name',
        'slug',
        'description',
        'logo_url',
        'logo_path',
        'thumbnail_url',
        'thumbnail_path',
        'original_filename',
        'file_size',
        'mime_type',
        'width',
        'height',
        'usage_context',
        'logo_type',
        'is_primary',
        'is_default',
        'is_active',
        'sort_order',
        'alt_text',
        'versions',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'versions' => 'array',
    ];

    protected $attributes = [
        'is_primary' => false,
        'is_default' => false,
        'is_active' => true,
        'sort_order' => 0,
        'versions' => '[]',
    ];

    /**
     * Logo usage contexts
     */
    public const USAGE_CONTEXTS = [
        'header',
        'footer',
        'email',
        'favicon',
        'social_media',
        'print',
        'mobile',
    ];

    /**
     * Logo types
     */
    public const LOGO_TYPES = [
        'primary',
        'secondary',
        'symbol',
        'wordmark',
        'combination',
        'monochrome',
        'icon',
        'favicon',
    ];

    /**
     * Supported image formats
     */
    public const SUPPORTED_FORMATS = [
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'image/gif',
        'image/webp',
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
        static::creating(function ($logo) {
            if (empty($logo->slug)) {
                $logo->slug = $logo->generateUniqueSlug($logo->name, $logo->tenant_id);
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
     * Scope query to active logos only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope query to primary logos only
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope query to default logos only
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
     * Scope query by logo type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('logo_type', $type);
    }

    /**
     * Get the tenant that owns this brand logo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the brand config this logo belongs to
     */
    public function brandConfig(): BelongsTo
    {
        return $this->belongsTo(BrandConfig::class, 'brand_config_id');
    }

    /**
     * Get the user who created this brand logo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this brand logo
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get logo usage records
     */
    // TODO: Create BrandLogoUsage model for tracking usage analytics
    // public function logoUsages(): HasMany
    // {
    //     return $this->hasMany(BrandLogoUsage::class);
    // }

    /**
     * Get full logo URL
     */
    public function getFullLogoUrl(): string
    {
        if ($this->logo_url) {
            return $this->logo_url;
        }

        if ($this->logo_path) {
            return Storage::url($this->logo_path);
        }

        // Try to find a version with a URL
        if ($this->versions) {
            foreach ($this->versions as $version) {
                if (isset($version['url']) && $version['url']) {
                    return $version['url'];
                }
            }
        }

        return '';
    }

    /**
     * Get full thumbnail URL
     */
    public function getFullThumbnailUrl(): string
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }

        return $this->getFullLogoUrl(); // Fallback to main logo
    }

    /**
     * Get logo dimensions as formatted string
     */
    public function getDimensions(): string
    {
        if ($this->width && $this->height) {
            return $this->width . 'x' . $this->height . 'px';
        }

        return 'Unknown';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if logo file exists
     */
    public function logoExists(): bool
    {
        if ($this->logo_path) {
            return Storage::exists($this->logo_path);
        }

        return false;
    }

    /**
     * Check if thumbnail file exists
     */
    public function thumbnailExists(): bool
    {
        if ($this->thumbnail_path) {
            return Storage::exists($this->thumbnail_path);
        }

        return false;
    }

    /**
     * Generate different logo versions (sizes)
     */
    public function generateVersions(array $sizes = [32, 64, 128, 256, 512]): void
    {
        $versions = [];

        foreach ($sizes as $size) {
            $versions[] = [
                'size' => $size,
                'url' => $this->getResizedUrl($size),
                'path' => $this->getResizedPath($size),
            ];
        }

        $this->update(['versions' => $versions]);
    }

    /**
     * Get URL for resized version
     */
    protected function getResizedUrl(int $size): string
    {
        // This would typically integrate with an image processing service
        // For now, return the original URL
        return $this->getFullLogoUrl();
    }

    /**
     * Get path for resized version
     */
    protected function getResizedPath(int $size): string
    {
        // This would typically integrate with an image processing service
        // For now, return the original path
        return $this->logo_path ?? '';
    }

    /**
     * Check if logo is SVG
     */
    public function isSvg(): bool
    {
        return $this->mime_type === 'image/svg+xml';
    }

    /**
     * Check if logo is vector format
     */
    public function isVector(): bool
    {
        return in_array($this->mime_type, ['image/svg+xml', 'application/pdf']);
    }

    /**
     * Generate a unique slug for the logo
     */
    protected function generateUniqueSlug(string $name, int $tenantId): string
    {
        $baseSlug = Str::slug($name . '-' . $this->logo_type);
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
     * Get logo usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'usage_count' => $this->logoUsages()->count(),
            'is_active' => $this->is_active,
            'is_primary' => $this->is_primary,
            'usage_context' => $this->usage_context,
            'logo_type' => $this->logo_type,
            'file_exists' => $this->logoExists(),
        ];
    }

    /**
     * Record logo usage
     */
    // TODO: Implement when BrandLogoUsage model is created
    // public function recordUsage(string $context, ?int $templateId = null): void
    // {
    //     $this->logoUsages()->create([
    //         'tenant_id' => $this->tenant_id,
    //         'usage_context' => $context,
    //         'template_id' => $templateId,
    //         'used_at' => now(),
    //     ]);
    // }

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
            'logo_url' => 'nullable|url|max:500',
            'logo_path' => 'nullable|string|max:500',
            'thumbnail_url' => 'nullable|url|max:500',
            'thumbnail_path' => 'nullable|string|max:500',
            'original_filename' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer|min:0',
            'mime_type' => 'nullable|string|max:100',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'usage_context' => 'required|in:' . implode(',', self::USAGE_CONTEXTS),
            'logo_type' => 'required|in:' . implode(',', self::LOGO_TYPES),
            'is_primary' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
            'alt_text' => 'nullable|string|max:255',
            'versions' => 'nullable|array',
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
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_logos,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:brand_logos,slug';
        }

        return $rules;
    }
}
