<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ForumTopic extends Model
{
    protected $fillable = [
        'forum_id',
        'user_id',
        'title',
        'slug',
        'content',
        'status',
        'is_sticky',
        'is_announcement',
        'is_approved',
        'approved_by',
        'approved_at',
        'posts_count',
        'views_count',
        'likes_count',
        'last_post_user_id',
        'last_post_at',
    ];

    protected $casts = [
        'is_sticky' => 'boolean',
        'is_announcement' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'last_post_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->title);
            }
        });

        static::created(function ($topic) {
            $topic->forum->incrementStats('topics');
        });

        static::deleted(function ($topic) {
            $topic->forum->decrement('topics_count');
        });
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lastPostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_post_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'topic_id');
    }

    public function approvedPosts(): HasMany
    {
        return $this->posts()->where('is_approved', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_topic_tags', 'topic_id', 'tag_id')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ForumTopicSubscription::class, 'topic_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->where('is_approved', true);
    }

    public function scopeSticky($query)
    {
        return $query->where('is_sticky', true);
    }

    public function scopeAnnouncements($query)
    {
        return $query->where('is_announcement', true);
    }

    public function scopeWithTag($query, $tagSlug)
    {
        return $query->whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function updateLastPost(ForumPost $post): void
    {
        $this->update([
            'last_post_user_id' => $post->user_id,
            'last_post_at' => $post->created_at,
        ]);

        $this->forum->update(['last_activity_at' => $post->created_at]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
