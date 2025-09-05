<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'category',
        'audience_type',
        'campaign_type',
        'structure',
        'default_config',
        'performance_metrics',
        'preview_image',
        'preview_url',
        'version',
        'is_active',
        'is_premium',
        'usage_count',
        'last_used_at',
        'tags',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'structure' => 'array',
        'default_config' => 'array',
        'performance_metrics' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_premium' => 'boolean',
        'version' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => true,
        'version' => 1,
        'usage_count' => 0,
        'structure' => '{}',
        'default_config' => '{}',
        'performance_metrics' => '{}',
        'tags' => '[]',
    ];

    /**
     * Template categories for different audiences
     */
    public const AUDIENCE_TYPES = [
        'individual',
        'institution',
        'employer',
        'general',
    ];

    /**
     * Campaign types for templates
     */
    public const CAMPAIGN_TYPES = [
        'onboarding',
        'event_promotion',
        'donation',
        'networking',
        'career_services',
        'recruiting',
        'leadership',
        'marketing',
    ];

    /**
     * Template categories
     */
    public const CATEGORIES = [
        'landing',
        'homepage',
        'form',
        'email',
        'social',
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
        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = $template->generateUniqueSlug($template->name, $template->tenant_id);
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
     * Scope query to active templates only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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
     * Scope query for premium templates
     */
    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    /**
     * Get the tenant that owns the template
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get landing pages using this template
     */
    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'template_id');
    }

    /**
     * Get template usage statistics
     */
    public function getUsageStats(): array
    {
        return [
            'usage_count' => $this->usage_count,
            'last_used_at' => $this->last_used_at,
            'landing_page_count' => $this->landingPages()->count(),
            'is_popular' => $this->usage_count > 100,
            'recently_used' => $this->last_used_at && $this->last_used_at->isAfter(now()->subDays(30)),
        ];
    }

    /**
     * Get converted rate from performance metrics
     */
    public function getConversionRate(): float
    {
        $metrics = $this->performance_metrics ?? [];
        return $metrics['conversion_rate'] ?? 0.0;
    }

    /**
     * Get average load time from performance metrics
     */
    public function getLoadTime(): float
    {
        $metrics = $this->performance_metrics ?? [];
        return $metrics['avg_load_time'] ?? 0.0;
    }

    /**
     * Update performance metrics
     */
    public function updatePerformanceMetrics(array $metrics): void
    {
        $currentMetrics = $this->performance_metrics ?? [];
        $updatedMetrics = array_merge($currentMetrics, $metrics);
        $updatedMetrics['updated_at'] = now()->toISOString();

        $this->update(['performance_metrics' => $updatedMetrics]);
    }

    /**
     * Generate a unique slug for the template
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
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'audience_type' => ['required', 'string', Rule::in(self::AUDIENCE_TYPES)],
            'campaign_type' => ['required', 'string', Rule::in(self::CAMPAIGN_TYPES)],
            'structure' => 'nullable|array',
            'default_config' => 'nullable|array',
            'performance_metrics' => 'nullable|array',
            'preview_image' => 'nullable|string|url|max:255',
            'preview_url' => 'nullable|string|url|max:255',
            'version' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
            'usage_count' => 'integer|min:0',
            'tags' => 'nullable|array',
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
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:templates,slug,' . $ignoreId;
        } else {
            $rules['slug'] = 'nullable|string|max:255|regex:/^[a-z0-9-]+$/|unique:templates,slug';
        }

        return $rules;
    }

    /**
     * Check if template structure is valid
     */
    public function validateStructure(): bool
    {
        if (empty($this->structure)) {
            return true;
        }

        $rules = [
            'sections' => 'array',
            'sections.*.type' => 'required|string',
            'sections.*.config' => 'nullable|array',
        ];

        $validator = Validator::make($this->structure, $rules);

        return $validator->passes();
    }

    /**
     * Get template structure with defaults applied
     */
    public function getEffectiveStructure(): array
    {
        $structure = $this->structure ?? [];

        if (empty($structure)) {
            return $this->getDefaultStructure();
        }

        return $structure;
    }

    /**
     * Get default structure for template category
     */
    protected function getDefaultStructure(): array
    {
        return match ($this->category) {
            'landing' => $this->getDefaultLandingStructure(),
            'homepage' => $this->getDefaultHomepageStructure(),
            'form' => $this->getDefaultFormStructure(),
            'email' => $this->getDefaultEmailStructure(),
            'social' => $this->getDefaultSocialStructure(),
            default => [],
        };
    }

    /**
     * Get default landing page structure
     */
    private function getDefaultLandingStructure(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '',
                        'subtitle' => '',
                        'cta_text' => 'Get Started',
                        'background_type' => 'image',
                    ]
                ],
                [
                    'type' => 'form',
                    'config' => [
                        'fields' => [],
                        'submit_text' => 'Submit',
                    ]
                ]
            ]
        ];
    }

    /**
     * Get default homepage structure
     */
    private function getDefaultHomepageStructure(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '',
                        'subtitle' => '',
                        'cta_text' => 'Learn More',
                    ]
                ],
                [
                    'type' => 'statistics',
                    'config' => [
                        'items' => []
                    ]
                ],
                [
                    'type' => 'testimonials',
                    'config' => []
                ]
            ]
        ];
    }

    /**
     * Get default form structure
     */
    private function getDefaultFormStructure(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'form',
                    'config' => [
                        'title' => '',
                        'description' => '',
                        'fields' => [],
                        'submit_text' => 'Submit',
                    ]
                ]
            ]
        ];
    }

    /**
     * Get default email structure
     */
    private function getDefaultEmailStructure(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'header',
                    'config' => [
                        'logo' => '',
                        'title' => '',
                    ]
                ],
                [
                    'type' => 'content',
                    'config' => [
                        'body' => '',
                        'cta_text' => '',
                        'cta_url' => '',
                    ]
                ],
                [
                    'type' => 'footer',
                    'config' => [
                        'copyright' => '',
                        'unsubscribe_link' => '',
                    ]
                ]
            ]
        ];
    }

    /**
     * Get default social structure
     */
    private function getDefaultSocialStructure(): array
    {
        return [
            'sections' => [
                [
                    'type' => 'image',
                    'config' => [
                        'url' => '',
                        'alt' => '',
                        'caption' => '',
                    ]
                ],
                [
                    'type' => 'text',
                    'config' => [
                        'headline' => '',
                        'body' => '',
                        'hashtag' => '',
                    ]
                ]
            ]
        ];
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}