<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'title',
        'description',
        'target_audience',
        'campaign_type',
        'campaign_name',
        'status',
        'content',
        'settings',
        'form_config',
        'template_id',
        'created_by',
        'updated_by',
        'published_at',
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'form_config' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($landingPage) {
            if (empty($landingPage->slug)) {
                $landingPage->slug = Str::slug($landingPage->name);
            }
        });

        static::updating(function ($landingPage) {
            if ($landingPage->isDirty('name') && empty($landingPage->slug)) {
                $landingPage->slug = Str::slug($landingPage->name);
            }
        });
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
     * Get the template used for this landing page
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(LandingPageTemplate::class, 'template_id');
    }

    /**
     * Get all submissions for this landing page
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class);
    }

    /**
     * Get all analytics events for this landing page
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(LandingPageAnalytics::class);
    }

    /**
     * Get recent submissions
     */
    public function recentSubmissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class)->latest()->limit(10);
    }

    /**
     * Check if the landing page is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Check if the landing page is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish the landing page
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
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
     * Get the public URL for this landing page
     */
    public function getPublicUrlAttribute(): string
    {
        return route('landing-page.show', $this->slug);
    }

    /**
     * Get conversion rate
     */
    public function getConversionRateAttribute(): float
    {
        $views = $this->analytics()->where('event_type', 'page_view')->count();
        $conversions = $this->submissions()->count();

        return $views > 0 ? ($conversions / $views) * 100 : 0;
    }

    /**
     * Get total views
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->analytics()->where('event_type', 'page_view')->count();
    }

    /**
     * Get total submissions
     */
    public function getTotalSubmissionsAttribute(): int
    {
        return $this->submissions()->count();
    }

    /**
     * Scope for published pages
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at');
    }

    /**
     * Scope for draft pages
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope by target audience
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where('target_audience', $audience);
    }

    /**
     * Scope by campaign type
     */
    public function scopeByCampaignType($query, string $campaignType)
    {
        return $query->where('campaign_type', $campaignType);
    }

    /**
     * Scope by campaign name
     */
    public function scopeByCampaign($query, string $campaignName)
    {
        return $query->where('campaign_name', $campaignName);
    }

    /**
     * Get analytics summary
     */
    public function getAnalyticsSummary(): array
    {
        $analytics = $this->analytics()
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        return [
            'page_views' => $analytics['page_view'] ?? 0,
            'form_submissions' => $analytics['form_submit'] ?? 0,
            'button_clicks' => $analytics['button_click'] ?? 0,
            'conversion_rate' => $this->conversion_rate,
            'total_submissions' => $this->total_submissions,
        ];
    }

    /**
     * Clone the landing page
     */
    public function duplicate(?string $newName = null): self
    {
        $newName = $newName ?: $this->name.' (Copy)';

        return self::create([
            'name' => $newName,
            'title' => $this->title.' (Copy)',
            'description' => $this->description,
            'target_audience' => $this->target_audience,
            'campaign_type' => $this->campaign_type,
            'campaign_name' => $this->campaign_name,
            'content' => $this->content,
            'settings' => $this->settings,
            'form_config' => $this->form_config,
            'template_id' => $this->template_id,
            'created_by' => auth()->id(),
            'status' => 'draft',
        ]);
    }
}
