<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'author_name',
        'author_title',
        'author_company',
        'author_photo',
        'graduation_year',
        'industry',
        'audience_type',
        'content',
        'video_url',
        'video_thumbnail',
        'rating',
        'status',
        'featured',
        'view_count',
        'click_count',
        'conversion_rate',
        'metadata',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'rating' => 'integer',
        'featured' => 'boolean',
        'view_count' => 'integer',
        'click_count' => 'integer',
        'conversion_rate' => 'decimal:4',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'status' => 'pending',
        'featured' => false,
        'view_count' => 0,
        'click_count' => 0,
        'conversion_rate' => 0.0000,
        'metadata' => '{}',
    ];

    /**
     * The audience types that testimonials can target
     */
    public const AUDIENCE_TYPES = [
        'individual',
        'institution',
        'employer',
    ];

    /**
     * The status options for testimonial moderation
     */
    public const STATUSES = [
        'pending',
        'approved',
        'rejected',
        'archived',
    ];

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Apply tenant scoping automatically (skip in testing)
        try {
            if (app()->bound('auth') && auth()->check() && auth()->user() && auth()->user()->tenant_id) {
                static::addGlobalScope('tenant', function ($builder) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                });
            }
        } catch (\Exception $e) {
            // Skip tenant scoping in test environment or when auth is not available
        }
    }

    /**
     * Scope query to specific tenant
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to approved testimonials only
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope query to featured testimonials
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('featured', true);
    }

    /**
     * Scope query by audience type
     */
    public function scopeByAudienceType(Builder $query, string $audienceType): Builder
    {
        return $query->where('audience_type', $audienceType);
    }

    /**
     * Scope query by industry
     */
    public function scopeByIndustry(Builder $query, string $industry): Builder
    {
        return $query->where('industry', $industry);
    }

    /**
     * Scope query by graduation year
     */
    public function scopeByGraduationYear(Builder $query, int $year): Builder
    {
        return $query->where('graduation_year', $year);
    }

    /**
     * Scope query by graduation year range
     */
    public function scopeByGraduationYearRange(Builder $query, int $startYear, int $endYear): Builder
    {
        return $query->whereBetween('graduation_year', [$startYear, $endYear]);
    }

    /**
     * Scope query to video testimonials
     */
    public function scopeWithVideo(Builder $query): Builder
    {
        return $query->whereNotNull('video_url');
    }

    /**
     * Scope query to text-only testimonials
     */
    public function scopeTextOnly(Builder $query): Builder
    {
        return $query->whereNull('video_url');
    }

    /**
     * Scope query with randomized order for rotation
     */
    public function scopeRandomized(Builder $query): Builder
    {
        return $query->inRandomOrder();
    }

    /**
     * Scope query ordered by performance (conversion rate, then view count)
     */
    public function scopeByPerformance(Builder $query): Builder
    {
        return $query->orderByDesc('conversion_rate')
                    ->orderByDesc('view_count');
    }

    /**
     * Get the tenant that owns the testimonial
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if testimonial is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if testimonial is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if testimonial is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if testimonial has video content
     */
    public function hasVideo(): bool
    {
        return !empty($this->video_url);
    }

    /**
     * Get the author's display name with title and company
     */
    public function getAuthorDisplayNameAttribute(): string
    {
        $name = $this->author_name;
        
        if ($this->author_title && $this->author_company) {
            $name .= ", {$this->author_title} at {$this->author_company}";
        } elseif ($this->author_title) {
            $name .= ", {$this->author_title}";
        } elseif ($this->author_company) {
            $name .= " at {$this->author_company}";
        }

        return $name;
    }

    /**
     * Get truncated content for previews
     */
    public function getTruncatedContentAttribute(): string
    {
        return strlen($this->content) > 150 
            ? substr($this->content, 0, 147) . '...'
            : $this->content;
    }

    /**
     * Increment view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment click count
     */
    public function incrementClickCount(): void
    {
        $this->increment('click_count');
    }

    /**
     * Calculate and update conversion rate
     */
    public function updateConversionRate(): void
    {
        if ($this->view_count > 0) {
            $this->conversion_rate = $this->click_count / $this->view_count;
            $this->save();
        }
    }

    /**
     * Approve testimonial
     */
    public function approve(): bool
    {
        $this->status = 'approved';
        return $this->save();
    }

    /**
     * Reject testimonial
     */
    public function reject(): bool
    {
        $this->status = 'rejected';
        return $this->save();
    }

    /**
     * Archive testimonial
     */
    public function archive(): bool
    {
        $this->status = 'archived';
        return $this->save();
    }

    /**
     * Set as featured
     */
    public function setFeatured(bool $featured = true): bool
    {
        $this->featured = $featured;
        return $this->save();
    }

    /**
     * Get metadata value with fallback
     */
    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * Set metadata value
     */
    public function setMetadataValue(string $key, mixed $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
    }

    /**
     * Get model validation rules
     */
    public static function getValidationRules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'author_name' => 'required|string|max:255|min:2',
            'author_title' => 'nullable|string|max:255',
            'author_company' => 'nullable|string|max:255',
            'author_photo' => 'nullable|string|max:500|url',
            'graduation_year' => 'nullable|integer|min:1900|max:2100',
            'industry' => 'nullable|string|max:100',
            'audience_type' => ['required', Rule::in(self::AUDIENCE_TYPES)],
            'content' => 'required|string|min:10|max:2000',
            'video_url' => 'nullable|string|max:500|url',
            'video_thumbnail' => 'nullable|string|max:500|url',
            'rating' => 'nullable|integer|min:1|max:5',
            'status' => ['required', Rule::in(self::STATUSES)],
            'featured' => 'boolean',
            'metadata' => 'nullable|array',
        ];
    }

    /**
     * Get unique validation rules (for updates)
     */
    public static function getUniqueValidationRules(?int $ignoreId = null): array
    {
        return self::getValidationRules();
    }

    /**
     * Validate video testimonial requirements
     */
    public function validateVideoRequirements(): bool
    {
        if ($this->video_url && !$this->video_thumbnail) {
            return false;
        }
        return true;
    }
}