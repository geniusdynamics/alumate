<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventHighlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'created_by',
        'type',
        'title',
        'description',
        'media_urls',
        'metadata',
        'likes_count',
        'shares_count',
        'is_featured',
        'is_approved',
        'featured_at',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'metadata' => 'array',
        'likes_count' => 'integer',
        'shares_count' => 'integer',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'featured_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(EventHighlightInteraction::class, 'highlight_id');
    }

    public function likes(): HasMany
    {
        return $this->interactions()->where('type', 'like');
    }

    public function shares(): HasMany
    {
        return $this->interactions()->where('type', 'share');
    }

    public function comments(): HasMany
    {
        return $this->interactions()->where('type', 'comment');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('likes_count');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    // Helper methods
    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function isSharedBy(User $user): bool
    {
        return $this->shares()->where('user_id', $user->id)->exists();
    }

    public function toggleLike(User $user): bool
    {
        $existingLike = $this->likes()->where('user_id', $user->id)->first();

        if ($existingLike) {
            $existingLike->delete();
            $this->decrement('likes_count');

            return false;
        } else {
            $this->interactions()->create([
                'user_id' => $user->id,
                'type' => 'like',
            ]);
            $this->increment('likes_count');

            return true;
        }
    }

    public function addShare(User $user, array $metadata = []): EventHighlightInteraction
    {
        $share = $this->interactions()->create([
            'user_id' => $user->id,
            'type' => 'share',
            'metadata' => $metadata,
        ]);

        $this->increment('shares_count');

        return $share;
    }

    public function addComment(User $user, string $content): EventHighlightInteraction
    {
        return $this->interactions()->create([
            'user_id' => $user->id,
            'type' => 'comment',
            'content' => $content,
        ]);
    }

    public function feature(): void
    {
        $this->update([
            'is_featured' => true,
            'featured_at' => now(),
        ]);
    }

    public function unfeature(): void
    {
        $this->update([
            'is_featured' => false,
            'featured_at' => null,
        ]);
    }

    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }

    public function getEngagementScore(): float
    {
        // Simple engagement score based on likes, shares, and comments
        $likes = $this->likes_count * 1;
        $shares = $this->shares_count * 2;
        $comments = $this->comments()->count() * 3;

        return $likes + $shares + $comments;
    }

    public function hasMedia(): bool
    {
        return ! empty($this->media_urls);
    }

    public function getMediaCount(): int
    {
        return count($this->media_urls ?? []);
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'photo' => 'Photo',
            'video' => 'Video',
            'quote' => 'Quote',
            'moment' => 'Moment',
            'achievement' => 'Achievement',
            default => 'Highlight'
        };
    }
}
