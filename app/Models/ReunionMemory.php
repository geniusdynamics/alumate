<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReunionMemory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'submitted_by',
        'title',
        'content',
        'type',
        'media_urls',
        'tagged_users',
        'is_featured',
        'is_approved',
        'visibility',
        'likes_count',
        'comments_count',
        'memory_date',
    ];

    protected $casts = [
        'media_urls' => 'array',
        'tagged_users' => 'array',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'memory_date' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReunionMemoryLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReunionMemoryComment::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByVisibility($query, $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public')
              ->orWhere('visibility', 'alumni_only')
              ->orWhere(function ($subQ) use ($user) {
                  $subQ->where('visibility', 'class_only')
                       ->whereHas('event', function ($eventQ) use ($user) {
                           $eventQ->where('graduation_year', $user->graduation_year ?? null)
                                  ->where('institution_id', $user->institution_id);
                       });
              });
        });
    }

    // Helper methods
    public function getTaggedUsers()
    {
        if (empty($this->tagged_users)) {
            return collect();
        }

        return User::whereIn('id', $this->tagged_users)->get();
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function canBeViewedBy(User $user): bool
    {
        switch ($this->visibility) {
            case 'public':
                return true;
            case 'alumni_only':
                return $user->hasRole('alumni') || $user->hasRole('admin');
            case 'class_only':
                return $this->event->graduation_year === $user->graduation_year &&
                       $this->event->institution_id === $user->institution_id;
            default:
                return false;
        }
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->id === $this->submitted_by || 
               $user->hasRole('admin') || 
               $this->event->canUserEdit($user);
    }

    public function incrementLikes(): void
    {
        $this->increment('likes_count');
    }

    public function decrementLikes(): void
    {
        $this->decrement('likes_count');
    }

    public function incrementComments(): void
    {
        $this->increment('comments_count');
    }

    public function decrementComments(): void
    {
        $this->decrement('comments_count');
    }

    public function getExcerpt(int $length = 150): string
    {
        return \Str::limit(strip_tags($this->content), $length);
    }
}