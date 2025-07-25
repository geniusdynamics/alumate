<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscussionReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'discussion_id',
        'user_id',
        'content',
        'parent_id',
        'likes_count',
        'is_solution',
    ];

    protected $casts = [
        'is_solution' => 'boolean',
    ];

    // Relationships
    public function discussion()
    {
        return $this->belongsTo(Discussion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(DiscussionReply::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(DiscussionReply::class, 'parent_id');
    }

    public function likes()
    {
        return $this->hasMany(DiscussionLike::class);
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSolutions($query)
    {
        return $query->where('is_solution', true);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('likes_count');
    }

    // Helper methods
    public function markAsSolution()
    {
        // First, unmark any existing solutions for this discussion
        $this->discussion->replies()->update(['is_solution' => false]);
        
        // Mark this reply as the solution
        $this->update(['is_solution' => true]);
    }

    public function unmarkAsSolution()
    {
        $this->update(['is_solution' => false]);
    }

    public function isLikedBy($user)
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function toggleLike($user)
    {
        $like = $this->likes()->where('user_id', $user->id)->first();
        
        if ($like) {
            $like->delete();
            $this->decrement('likes_count');
            return false; // unliked
        } else {
            $this->likes()->create(['user_id' => $user->id]);
            $this->increment('likes_count');
            return true; // liked
        }
    }

    public function isTopLevel()
    {
        return $this->parent_id === null;
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($reply) {
            // Update discussion reply count and last activity
            $reply->discussion->increment('replies_count');
            $reply->discussion->updateActivity();
        });

        static::deleted(function ($reply) {
            // Update discussion reply count
            $reply->discussion->decrement('replies_count');
        });
    }
}