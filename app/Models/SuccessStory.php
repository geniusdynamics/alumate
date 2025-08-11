<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuccessStory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'summary',
        'content',
        'featured_image',
        'media_urls',
        'industry',
        'achievement_type',
        'current_role',
        'current_company',
        'graduation_year',
        'degree_program',
        'tags',
        'demographics',
        'status',
        'is_featured',
        'allow_social_sharing',
        'view_count',
        'share_count',
        'like_count',
        'published_at',
        'featured_at',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'tags' => 'array',
        'demographics' => 'array',
        'is_featured' => 'boolean',
        'allow_social_sharing' => 'boolean',
        'published_at' => 'datetime',
        'featured_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'featured_at',
        'deleted_at',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)
            ->published();
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeByAchievementType($query, $type)
    {
        return $query->where('achievement_type', $type);
    }

    public function scopeByGraduationYear($query, $year)
    {
        return $query->where('graduation_year', $year);
    }

    // Methods
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function incrementShareCount()
    {
        $this->increment('share_count');
    }

    public function incrementLikeCount()
    {
        $this->increment('like_count');
    }

    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function feature()
    {
        $this->update([
            'is_featured' => true,
            'featured_at' => now(),
        ]);
    }

    public function unfeature()
    {
        $this->update([
            'is_featured' => false,
            'featured_at' => null,
        ]);
    }

    public function getShareUrl()
    {
        return route('success-stories.show', $this->id);
    }

    public function getSocialShareData()
    {
        return [
            'title' => $this->title,
            'description' => $this->summary,
            'image' => $this->featured_image,
            'url' => $this->getShareUrl(),
        ];
    }
}
