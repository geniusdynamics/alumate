<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ReunionPhoto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'uploaded_by',
        'title',
        'description',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'metadata',
        'tagged_users',
        'likes_count',
        'comments_count',
        'is_featured',
        'is_approved',
        'visibility',
        'taken_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tagged_users' => 'array',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'taken_at' => 'datetime',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'file_size' => 'integer',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ReunionPhotoLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReunionPhotoComment::class);
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
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function getThumbnailUrl(): string
    {
        $pathInfo = pathinfo($this->file_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        
        if (Storage::exists($thumbnailPath)) {
            return Storage::url($thumbnailPath);
        }
        
        return $this->getUrl();
    }

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
        return $user->id === $this->uploaded_by || 
               $user->hasRole('admin') || 
               $this->event->canUserEdit($user);
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
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
}