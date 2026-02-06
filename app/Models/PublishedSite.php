<?php
// ABOUTME: PublishedSite model for schema-based multi-tenancy without tenant_id column
// ABOUTME: Represents published landing page sites with deployment tracking and performance monitoring

namespace App\Models;

use App\Services\TenantContextService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Published Site Model
 *
 * Represents a published landing page site with deployment tracking,
 * domain management, and performance monitoring capabilities.
 */
class PublishedSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        // 'tenant_id', // Removed for schema-based tenancy
        'name',
        'slug',
        'domain',
        'subdomain',
        'custom_domains',
        'status',
        'deployment_status',
        'build_hash',
        'cdn_url',
        'static_url',
        'build_config',
        'performance_metrics',
        'seo_config',
        'analytics_config',
        'ssl_enabled',
        'ssl_certificate_id',
        'published_at',
        'last_deployed_at',
        'deployment_count',
        'deployment_history',
        'is_ab_test_enabled',
        'ab_test_config',
    ];

    protected $casts = [
        'custom_domains' => 'array',
        'build_config' => 'array',
        'performance_metrics' => 'array',
        'seo_config' => 'array',
        'analytics_config' => 'array',
        'ssl_enabled' => 'boolean',
        'published_at' => 'datetime',
        'last_deployed_at' => 'datetime',
        'deployment_count' => 'integer',
        'deployment_history' => 'array',
        'is_ab_test_enabled' => 'boolean',
        'ab_test_config' => 'array',
    ];

    protected $attributes = [
        'status' => 'draft',
        'deployment_status' => 'pending',
        'ssl_enabled' => false,
        'deployment_count' => 0,
        'is_ab_test_enabled' => false,
        'custom_domains' => '[]',
        'build_config' => '{}',
        'performance_metrics' => '{}',
        'seo_config' => '{}',
        'analytics_config' => '{}',
        'deployment_history' => '[]',
        'ab_test_config' => '{}',
    ];

    /**
     * Available statuses for published sites
     */
    public const STATUSES = [
        'draft',
        'published',
        'suspended',
        'archived',
    ];

    /**
     * Available deployment statuses
     */
    public const DEPLOYMENT_STATUSES = [
        'pending',
        'deploying',
        'deployed',
        'failed',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Schema-based tenancy - no global scope needed as data is isolated by schema
        // Tenant context is handled by TenantContextService

        // Auto-generate slug if not provided
        static::creating(function ($site) {
            if (empty($site->slug)) {
                $site->slug = $site->generateUniqueSlug($site->name);
            }
        });
    }

    /**
     * Scope query to specific tenant (legacy compatibility)
     * Note: With schema-based tenancy, this method returns the query unchanged
     */
    public function scopeForTenant($query, int $tenantId)
    {
        // Schema-based tenancy: data is already isolated by schema
        return $query;
    }

    /**
     * Scope query to published sites only
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope query by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope query by deployment status
     */
    public function scopeByDeploymentStatus($query, string $deploymentStatus)
    {
        return $query->where('deployment_status', $deploymentStatus);
    }

    /**
     * Get the current tenant context (schema-based tenancy)
     */
    public function getCurrentTenant()
    {
        return TenantContextService::getCurrentTenant();
    }

    /**
     * Get the landing page this site is based on
     */
    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    /**
     * Get deployments for this site
     */
    public function deployments(): HasMany
    {
        return $this->hasMany(SiteDeployment::class, 'published_site_id');
    }

    /**
     * Get analytics for this site
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(SiteAnalytics::class, 'published_site_id');
    }

    /**
     * Get publishing workflows for this site
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(PublishingWorkflow::class, 'published_site_id');
    }

    /**
     * Check if the site is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Check if the site is currently deploying
     */
    public function isDeploying(): bool
    {
        return $this->deployment_status === 'deploying';
    }

    /**
     * Get the full public URL for this site
     */
    public function getFullPublicUrl(): string
    {
        if (!$this->isPublished()) {
            return '';
        }

        // Use custom domain if available
        if ($this->domain) {
            return "https://{$this->domain}";
        }

        // Use subdomain if available
        if ($this->subdomain && config('database.multi_tenant')) {
            try {
                $tenantDomain = tenant()->domain;
                return "https://{$this->subdomain}.{$tenantDomain}";
            } catch (\Exception $e) {
                // Fallback to static URL
            }
        }

        return $this->static_url ?? '';
    }

    /**
     * Get the CDN URL for assets
     */
    public function getCdnUrl(): string
    {
        return $this->cdn_url ?? $this->getFullPublicUrl();
    }

    /**
     * Publish the site
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Unpublish the site
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Suspend the site
     */
    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    /**
     * Archive the site
     */
    public function archive(): void
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Update deployment status
     */
    public function updateDeploymentStatus(string $status, ?string $errorMessage = null): void
    {
        $updateData = ['deployment_status' => $status];

        if ($status === 'deployed') {
            $updateData['last_deployed_at'] = now();
            $updateData['deployment_count'] = $this->deployment_count + 1;
        }

        if ($errorMessage) {
            $updateData['deployment_history'] = array_merge(
                $this->deployment_history ?? [],
                [['status' => $status, 'error' => $errorMessage, 'timestamp' => now()->toISOString()]]
            );
        }

        $this->update($updateData);
    }

    /**
     * Generate a unique slug for the site (schema-based tenancy)
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
     * Check if a slug exists (schema-based tenancy)
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
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        $metrics = $this->performance_metrics ?? [];

        return [
            'deployment_count' => $this->deployment_count,
            'last_deployed_at' => $this->last_deployed_at,
            'avg_load_time' => $metrics['avg_load_time'] ?? 0,
            'total_page_views' => $this->analytics()->sum('page_views'),
            'total_unique_visitors' => $this->analytics()->sum('unique_visitors'),
            'is_performing' => ($metrics['avg_load_time'] ?? 0) < 3000, // Less than 3 seconds
        ];
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'landing_page_id' => 'required|exists:landing_pages,id',
            // 'tenant_id' => 'required|exists:tenants,id', // Removed for schema-based tenancy
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'custom_domains' => 'nullable|array',
            'status' => 'required|in:' . implode(',', self::STATUSES),
            'deployment_status' => 'required|in:' . implode(',', self::DEPLOYMENT_STATUSES),
            'build_hash' => 'nullable|string|max:255',
            'cdn_url' => 'nullable|string|url|max:255',
            'static_url' => 'nullable|string|url|max:255',
            'build_config' => 'nullable|array',
            'performance_metrics' => 'nullable|array',
            'seo_config' => 'nullable|array',
            'analytics_config' => 'nullable|array',
            'ssl_enabled' => 'boolean',
            'ssl_certificate_id' => 'nullable|string|max:255',
            'published_at' => 'nullable|date',
            'last_deployed_at' => 'nullable|date',
            'deployment_count' => 'integer|min:0',
            'deployment_history' => 'nullable|array',
            'is_ab_test_enabled' => 'boolean',
            'ab_test_config' => 'nullable|array',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        $rules = self::getValidationRules();

        if ($ignoreId) {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:published_sites,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:published_sites,slug';
        }

        return $rules;
    }
}
