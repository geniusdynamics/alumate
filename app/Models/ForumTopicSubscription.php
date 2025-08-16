<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumTopicSubscription extends Model
{
    protected $fillable = [
        'topic_id',
        'user_id',
        'email_notifications',
        'last_read_at',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'last_read_at' => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ForumTopic::class, 'topic_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    public function hasUnreadPosts(): bool
    {
        if (!$this->last_read_at) {
            return $this->topic->posts()->approved()->exists();
        }

        return $this->topic->posts()
            ->approved()
            ->where('created_at', '>', $this->last_read_at)
            ->exists();
    }
}
