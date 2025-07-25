<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'scope',
        'target_audience',
        'priority',
        'is_published',
        'is_pinned',
        'published_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'target_audience' => 'array',
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
                    ->where('published_at', '<=', now())
                    ->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeForUser($query, $user)
    {
        return $query->where(function($q) use ($user) {
            $q->where('scope', 'all')
              ->orWhere(function($scopeQuery) use ($user) {
                  $scopeQuery->where('scope', 'role')
                            ->whereJsonContains('target_audience', $user->roles->pluck('name')->toArray());
              })
              ->orWhere(function($scopeQuery) use ($user) {
                  $scopeQuery->where('scope', 'institution')
                            ->whereJsonContains('target_audience', $user->institution_id ?? 0);
              });
        });
    }

    // Helper methods
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isReadBy($user)
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsReadBy($user)
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }

    public function getReadCount()
    {
        return $this->reads()->count();
    }

    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function unpublish()
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}