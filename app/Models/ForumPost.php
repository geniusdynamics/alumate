<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    protected $fillable = [
        'topic_id',
        'user_id',
        'content',
        'content_html',
        'parent_id',
        'depth',
        'thread_path',
        'is_approved',
        'approved_by',
        'approved_at',
        'likes_count',
        'is_solution',
        'edited_at',
        'edited_by',
        'edit_reason',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_solution' => 'boolean',
        'approved_at' => 'datetime',
        'edited_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            // Set thread path for nested replies
            if ($post->parent_id) {
                $parent = static::find($post->parent_id);
                $post->depth = $parent->depth + 1;
                $post->thread_path = $parent->thread_path.'.'.$parent->id;
            } else {
                $post->depth = 0;
                $post->thread_path = '';
            }
        });

        static::created(function ($post) {
            // Update topic stats
            $post->topic->increment('posts_count');
            $post->topic->updateLastPost($post);

            // Update forum stats
            $post->topic->forum->incrementStats('posts');
        });

        static::deleted(function ($post) {
            $post->topic->decrement('posts_count');
            $post->topic->forum->decrement('posts_count');
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function editedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumPostLike::class, 'post_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeInThread($query, $threadPath)
    {
        return $query->where('thread_path', 'like', $threadPath.'%');
    }

    public function getThreadReplies()
    {
        if ($this->parent_id) {
            return collect();
        }

        return static::where('topic_id', $this->topic_id)
            ->where('thread_path', 'like', $this->id.'%')
            ->where('is_approved', true)
            ->orderBy('thread_path')
            ->orderBy('created_at')
            ->get();
    }

    public function markAsSolution(): void
    {
        // Remove solution status from other posts in the topic
        static::where('topic_id', $this->topic_id)
            ->where('id', '!=', $this->id)
            ->update(['is_solution' => false]);

        $this->update(['is_solution' => true]);
    }

    public function hasUserLiked(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
