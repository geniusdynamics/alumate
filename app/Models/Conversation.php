<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'description',
        'created_by',
        'circle_id',
        'group_id',
        'metadata',
        'last_message_at',
        'is_archived',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    protected $dates = [
        'last_message_at',
    ];

    /**
     * Get the user who created the conversation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the circle this conversation belongs to
     */
    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    /**
     * Get the group this conversation belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get all messages in this conversation
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    /**
     * Get the latest message in this conversation
     */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest()->limit(1);
    }

    /**
     * Get all participants in this conversation
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['role', 'joined_at', 'last_read_at', 'is_muted', 'is_pinned', 'settings'])
            ->withTimestamps();
    }

    /**
     * Get conversation participants with their details
     */
    public function participantDetails(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * Check if user is a participant in this conversation
     */
    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Add a participant to the conversation
     */
    public function addParticipant(User $user, string $role = 'participant'): ConversationParticipant
    {
        return $this->participantDetails()->create([
            'user_id' => $user->id,
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Remove a participant from the conversation
     */
    public function removeParticipant(User $user): bool
    {
        return $this->participantDetails()->where('user_id', $user->id)->delete();
    }

    /**
     * Get unread message count for a user
     */
    public function getUnreadCountForUser(User $user): int
    {
        $participant = $this->participantDetails()->where('user_id', $user->id)->first();
        
        if (!$participant) {
            return 0;
        }

        $lastReadAt = $participant->last_read_at;
        
        if (!$lastReadAt) {
            return $this->messages()->count();
        }

        return $this->messages()->where('created_at', '>', $lastReadAt)->count();
    }

    /**
     * Mark conversation as read for a user
     */
    public function markAsReadForUser(User $user): void
    {
        $this->participantDetails()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Get conversation display name
     */
    public function getDisplayName(User $currentUser = null): string
    {
        if ($this->title) {
            return $this->title;
        }

        if ($this->type === 'direct' && $currentUser) {
            $otherParticipant = $this->participants()
                ->where('user_id', '!=', $currentUser->id)
                ->first();
            
            return $otherParticipant ? $otherParticipant->name : 'Unknown User';
        }

        if ($this->circle) {
            return $this->circle->name;
        }

        if ($this->group) {
            return $this->group->name;
        }

        return 'Conversation';
    }

    /**
     * Scope for conversations involving a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    /**
     * Scope for direct conversations
     */
    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    /**
     * Scope for group conversations
     */
    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    /**
     * Scope for circle conversations
     */
    public function scopeCircle($query)
    {
        return $query->where('type', 'circle');
    }

    /**
     * Update last message timestamp
     */
    public function updateLastMessageTime(): void
    {
        $this->update(['last_message_at' => now()]);
    }
}
