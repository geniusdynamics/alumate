<?php
// ABOUTME: LandingPage model for schema-based multi-tenancy without tenant_id column
// ABOUTME: Manages landing pages with automatic tenant context resolution

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'name',
        'slug',
        'description',
        'config',
        'brand_config',
        'audience_type',
        'campaign_type',
        'category',
        'status',
        'published_at',
        'draft_hash',
        'version',
        'usage_count',
        'conversion_count',
        'preview_url',
        'public_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'social_image',
        'tracking_id',
        'favicon_url',
        'custom_css',
        'custom_js',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array',
        'brand_config' => 'array',
        'published_at' => 'datetime',
        'version' => 'integer',
        'usage_count' => 'integer',
        'conversion_count' => 'integer',
        'seo_keywords' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
        'usage_count' => 0,
        'conversion_count' => 0,
        'config' => '{}',
        'brand_config' => '{}',
        'seo_keywords' => '[]',
    ];

    /**
     * Available statuses for landing pages
     */
    public const STATUSES = [
        'draft',
        'reviewing',
        'published',
        'archived',
        'suspended',
    ];

    /**
     * Categories for landing pages
     */
    public const CATEGORIES = [
        'individual',
        'institution',
        'employer',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant context for schema-based tenancy
        static::addGlobalScope('tenant_context', function ($builder) {
            app(TenantContextService::class)->applyTenantContext($builder);
        });

        // Auto-generate slug if not provided
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = $page->generateUniqueSlug($page->name);
            }

            // Generate draft hash for tracking changes
            if (empty($page->draft_hash)) {
                $page->draft_hash = Str::random(32);
            }
        });
    }

    /**
     * Scope query to specific tenant (for schema-based tenancy)
     * Note: This is primarily for administrative purposes
     */
    public function scopeForTenant($query, string $tenantId)
    {
        // In schema-based tenancy, this would switch schema context
        return app(TenantContextService::class)->scopeToTenant($query, $tenantId);
    }

    /**
     * Scope query to published pages only
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope query to draft pages only
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope query by audience type
     */
    public function scopeByAudience($query, string $audienceType)
    {
        return $query->where('audience_type', $audienceType);
    }

    /**
     * Scope query by campaign type
     */
    public function scopeByCampaign($query, string $campaignType)
    {
        return $query->where('campaign_type', $campaignType);
    }

    /**
     * Get the current tenant context
     * Note: In schema-based tenancy, tenant relationship is contextual
     */
    public function getCurrentTenant()
    {
        return app(TenantContextService::class)->getCurrentTenant();
    }

    /**
     * Get the template used by this landing page
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get the user who created this landing page
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this landing page
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get submissions for this landing page
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class);
    }

    /**
     * Get analytics for this landing page
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(LandingPageAnalytics::class);
    }

    /**
     * Get components used in this landing page
     */
    public function components(): HasMany
    {
        return $this->hasMany(LandingPageComponent::class);
    }

    /**
     * Get effective configuration (template config + custom config)
     */
    public function getEffectiveConfig(): array
    {
        $templateConfig = $this->template ? $this->template->default_config ?? [] : [];
        $pageConfig = $this->config ?? [];
        $brandConfig = $this->brand_config ?? [];

        return array_merge($templateConfig, $brandConfig, $pageConfig);
    }

    /**
     * Check if the landing page is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Check if the landing page is public
     */
    public function isPublic(): bool
    {
        return $this->isPublished() && $this->published_at->isPast();
    }

    /**
     * Publish the landing page
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'version' => $this->version + 1,
        ]);

        // Update draft hash to reflect published state
        $this->update(['draft_hash' => Str::random(32)]);
    }

    /**
     * Unpublish the landing page
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Archive the landing page
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Suspend the landing page
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Get the full public URL for this landing page
     */
    public function getFullPublicUrl(): string
    {
        if (!$this->isPublished() || empty($this->public_url)) {
            return '';
        }

        // If using subdomain isolation
        if (config('database.multi_tenant')) {
            try {
                $tenantDomain = tenant()->domain;
                return "https://{$this->slug}.{$tenantDomain}";
            } catch (\Exception $e) {
                // Fallback to path-based URL
            }
        }

        return $this->public_url;
    }

    /**
     * Get the full preview URL for this landing page
     */
    public function getFullPreviewUrl(): string
    {
        if (empty($this->preview_url)) {
            return '';
        }

        // Include draft hash for cache busting
        return $this->preview_url . '?draft=' . $this->draft_hash;
    }

    /**
     * Generate a unique slug for the landing page
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug exists in current tenant context
     */
    protected function slugExists(string $slug): bool
    {
        $query = static::where('slug', $slug);

        if ($this->exists) {
            $query->where('id', '!=', $this->id);
        }

        return $query->exists();
    }

    /**
     * Get SEO metadata array
     */
    public function getSEOMetadata(): array
    {
        return [
            'title' => $this->seo_title ?? $this->name,
            'description' => $this->seo_description ?? $this->description,
            'keywords' => $this->seo_keywords ?? [],
            'image' => $this->social_image,
            'url' => $this->getFullPublicUrl(),
            'site_name' => tenant()->name ?? config('app.name'),
            'locale' => 'en_US',
        ];
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        return [
            'usage_count' => $this->usage_count,
            'conversion_count' => $this->conversion_count,
            'conversion_rate' => $this->usage_count > 0
                ? round(($this->conversion_count / $this->usage_count) * 100, 2)
                : 0,
            'is_performing' => $this->conversion_rate > 5, // 5% conversion rate threshold
        ];
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Increment conversion count
     */
    public function incrementConversion(): void
    {
        $this->increment('conversion_count');
    }

    /**
     * Update draft hash when configuration changes
     */
    public function updateDraftHash(): void
    {
        $this->update(['draft_hash' => Str::random(32)]);
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'template_id' => 'required|exists:templates,id',
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'config' => 'nullable|array',
            'brand_config' => 'nullable|array',
            'audience_type' => 'required|in:' . implode(',', ['individual', 'institution', 'employer']),
            'campaign_type' => 'required|in:' . implode(',', [
                'onboarding', 'event_promotion', 'networking', 'career_services',
                'recruiting', 'donation', 'leadership', 'marketing'
            ]),
            'category' => 'required|in:' . implode(',', self::CATEGORIES),
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'published_at' => 'nullable|date',
            'version' => 'integer|min:1',
            'usage_count' => 'integer|min:0',
            'conversion_count' => 'integer|min:0',
            'preview_url' => 'nullable|string|url|max:255',
            'public_url' => 'nullable|string|url|max:255',
            'seo_title' => 'nullable|string|max:60',
            'seo_description' => 'nullable|string|max:160',
            'seo_keywords' => 'nullable|array',
            'social_image' => 'nullable|string|url|max:255',
            'tracking_id' => 'nullable|string|max:255',
            'favicon_url' => 'nullable|string|url|max:255',
            'custom_css' => 'nullable|string',
            'custom_js' => 'nullable|string',
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
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:landing_pages,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:landing_pages,slug';
        }

        return $rules;
    }
}
