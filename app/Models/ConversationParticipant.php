<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationParticipant extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
        'is_pinned',
        'settings',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
        'is_pinned' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the conversation this participant belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who is the participant
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if participant is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if participant is a moderator
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['admin', 'moderator']);
    }

    /**
     * Check if participant has muted the conversation
     */
    public function isMuted(): bool
    {
        return $this->is_muted;
    }

    /**
     * Check if participant has pinned the conversation
     */
    public function isPinned(): bool
    {
        return $this->is_pinned;
    }

    /**
     * Mute the conversation for this participant
     */
    public function mute(): void
    {
        $this->update(['is_muted' => true]);
    }

    /**
     * Unmute the conversation for this participant
     */
    public function unmute(): void
    {
        $this->update(['is_muted' => false]);
    }

    /**
     * Pin the conversation for this participant
     */
    public function pin(): void
    {
        $this->update(['is_pinned' => true]);
    }

    /**
     * Unpin the conversation for this participant
     */
    public function unpin(): void
    {
        $this->update(['is_pinned' => false]);
    }

    /**
     * Update last read timestamp
     */
    public function updateLastRead(): void
    {
        $this->update(['last_read_at' => now()]);
    }

    /**
     * Get unread message count for this participant
     */
    public function getUnreadCount(): int
    {
        if (! $this->last_read_at) {
            return $this->conversation->messages()->count();
        }

        return $this->conversation->messages()
            ->where('created_at', '>', $this->last_read_at)
            ->where('user_id', '!=', $this->user_id) // Don't count own messages
            ->count();
    }

    /**
     * Get participant setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set participant setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    // Scopes
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeModerators($query)
    {
        return $query->whereIn('role', ['admin', 'moderator']);
    }

    public function scopeParticipants($query)
    {
        return $query->where('role', 'participant');
    }

    public function scopeMuted($query)
    {
        return $query->where('is_muted', true);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('last_read_at');
    }
}
