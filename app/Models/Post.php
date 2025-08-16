<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'content',
        'media_urls',
        'post_type',
        'visibility',
        'circle_ids',
        'group_ids',
        'metadata',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'circle_ids' => 'array',
        'group_ids' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all engagements for the post.
     */
    public function engagements(): HasMany
    {
        return $this->hasMany(PostEngagement::class);
    }

    /**
     * Get all comments for the post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Check if the post can be viewed by a specific user.
     */
    public function canBeViewedBy(?User $user): bool
    {
        if (! $user) {
            return $this->visibility === 'public';
        }

        // Post owner can always view
        if ($this->user_id === $user->id) {
            return true;
        }

        return match ($this->visibility) {
            'public' => true,
            'circles' => $this->isUserInPostCircles($user),
            'groups' => $this->isUserInPostGroups($user),
            'private' => false,
            default => false,
        };
    }

    /**
     * Get engagement counts for the post.
     */
    public function getEngagementCounts(): array
    {
        return $this->engagements()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Check if a user has engaged with the post in a specific way.
     */
    public function isEngagedBy(?User $user, string $type): bool
    {
        if (! $user) {
            return false;
        }

        return $this->engagements()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->exists();
    }

    /**
     * Get the visibility attribute with human-readable format.
     */
    public function getVisibilityAttribute($value): string
    {
        return match ($value) {
            'public' => 'Public',
            'circles' => 'Circles',
            'groups' => 'Groups',
            'private' => 'Private',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted media URLs with metadata.
     */
    public function getFormattedMediaAttribute(): array
    {
        if (! $this->media_urls) {
            return [];
        }

        return collect($this->media_urls)->map(function ($media) {
            return [
                'url' => $media['url'] ?? $media,
                'type' => $media['type'] ?? $this->guessMediaType($media['url'] ?? $media),
                'thumbnail' => $media['thumbnail'] ?? null,
                'alt' => $media['alt'] ?? null,
            ];
        })->toArray();
    }

    /**
     * Check if user is in any of the post's circles.
     */
    private function isUserInPostCircles(User $user): bool
    {
        if (! $this->circle_ids) {
            return false;
        }

        return $user->circles()
            ->whereIn('circles.id', $this->circle_ids)
            ->exists();
    }

    /**
     * Check if user is in any of the post's groups.
     */
    private function isUserInPostGroups(User $user): bool
    {
        if (! $this->group_ids) {
            return false;
        }

        return $user->groups()
            ->whereIn('groups.id', $this->group_ids)
            ->exists();
    }

    /**
     * Guess media type from URL.
     */
    private function guessMediaType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'image',
            'mp4', 'avi', 'mov', 'wmv', 'webm' => 'video',
            'pdf' => 'document',
            default => 'file',
        };
    }

    /**
     * Scope to get posts visible to a specific user.
     */
    public function scopeVisibleTo($query, ?User $user)
    {
        if (! $user) {
            return $query->where('visibility', 'public');
        }

        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
                ->orWhere('user_id', $user->id)
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'circles')
                        ->whereRaw('JSON_OVERLAPS(circle_ids, ?)', [
                            json_encode($user->circles()->pluck('circles.id')->toArray()),
                        ]);
                })
                ->orWhere(function ($subQ) use ($user) {
                    $subQ->where('visibility', 'groups')
                        ->whereRaw('JSON_OVERLAPS(group_ids, ?)', [
                            json_encode($user->groups()->pluck('groups.id')->toArray()),
                        ]);
                });
        });
    }

    /**
     * Scope to get posts by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('post_type', $type);
    }

    /**
     * Scope to get recent posts.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
