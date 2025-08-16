<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'category',
        'tags',
        'created_by',
        'course_id',
        'views_count',
        'replies_count',
        'last_activity_at',
        'is_pinned',
        'is_locked',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_activity_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function replies()
    {
        return $this->hasMany(DiscussionReply::class);
    }

    public function topLevelReplies()
    {
        return $this->hasMany(DiscussionReply::class)->whereNull('parent_id');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('replies_count');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('last_activity_at');
    }

    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Helper methods
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function updateActivity()
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function pin()
    {
        $this->update(['is_pinned' => true]);
    }

    public function unpin()
    {
        $this->update(['is_pinned' => false]);
    }

    public function lock()
    {
        $this->update(['is_locked' => true]);
    }

    public function unlock()
    {
        $this->update(['is_locked' => false]);
    }

    public function getLastReply()
    {
        return $this->replies()->latest()->first();
    }

    public function hasReplies()
    {
        return $this->replies_count > 0;
    }

    public function canReply()
    {
        return ! $this->is_locked;
    }
}
