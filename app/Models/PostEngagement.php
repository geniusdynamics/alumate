<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostEngagement extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the post that was engaged with.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who made the engagement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get engagements of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get engagements by a specific user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope to get reaction engagements (likes, loves, etc.).
     */
    public function scopeReactions($query)
    {
        return $query->whereIn('type', ['like', 'love', 'celebrate', 'support', 'insightful']);
    }

    /**
     * Scope to get non-reaction engagements.
     */
    public function scopeActions($query)
    {
        return $query->whereIn('type', ['comment', 'share', 'bookmark']);
    }

    /**
     * Get the emoji representation of the engagement type.
     */
    public function getEmojiAttribute(): string
    {
        return match ($this->type) {
            'like' => '👍',
            'love' => '❤️',
            'celebrate' => '🎉',
            'support' => '🤝',
            'insightful' => '💡',
            'comment' => '💬',
            'share' => '🔄',
            'bookmark' => '🔖',
            default => '👍',
        };
    }

    /**
     * Get the human-readable name of the engagement type.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            'like' => 'Like',
            'love' => 'Love',
            'celebrate' => 'Celebrate',
            'support' => 'Support',
            'insightful' => 'Insightful',
            'comment' => 'Comment',
            'share' => 'Share',
            'bookmark' => 'Bookmark',
            default => 'Like',
        };
    }

    /**
     * Check if this is a reaction type engagement.
     */
    public function isReaction(): bool
    {
        return in_array($this->type, ['like', 'love', 'celebrate', 'support', 'insightful']);
    }

    /**
     * Check if this is an action type engagement.
     */
    public function isAction(): bool
    {
        return in_array($this->type, ['comment', 'share', 'bookmark']);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($engagement) {
            $engagement->created_at = now();
        });
    }
}
