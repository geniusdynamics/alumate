<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Forum extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'color',
        'icon',
        'visibility',
        'is_active',
        'sort_order',
        'group_id',
        'requires_approval',
        'allow_anonymous',
        'topics_count',
        'posts_count',
        'last_activity_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
        'allow_anonymous' => 'boolean',
        'last_activity_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($forum) {
            if (empty($forum->slug)) {
                $forum->slug = Str::slug($forum->name);
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class);
    }

    public function latestTopics(): HasMany
    {
        return $this->topics()
            ->where('is_approved', true)
            ->where('status', 'active')
            ->orderBy('is_sticky', 'desc')
            ->orderBy('last_post_at', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function canUserAccess(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($this->visibility) {
            'public' => true,
            'private' => $user->hasRole('admin') || $user->hasRole('moderator'),
            'group_only' => $this->group && $this->group->members()->where('user_id', $user->id)->exists(),
            default => false,
        };
    }

    public function incrementStats(string $type): void
    {
        $this->increment($type.'_count');
        $this->update(['last_activity_at' => now()]);
    }
}
