<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'content',
        'type',
        'attachments',
        'metadata',
        'reply_to_id',
        'is_edited',
        'edited_at',
        // Legacy fields for backward compatibility
        'sender_id',
        'recipient_id',
        'subject',
        'related_job_id',
        'related_application_id',
        'read_at',
        'replied_at',
        'is_archived',
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        // Legacy casts
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    /**
     * Get the conversation this message belongs to
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the message this is a reply to
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'reply_to_id');
    }

    /**
     * Get all replies to this message
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'reply_to_id');
    }

    /**
     * Get all read receipts for this message
     */
    public function reads(): HasMany
    {
        return $this->hasMany(MessageRead::class);
    }

    // Legacy relationships for backward compatibility
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function relatedJob()
    {
        return $this->belongsTo(Job::class, 'related_job_id');
    }

    public function relatedApplication()
    {
        return $this->belongsTo(JobApplication::class, 'related_application_id');
    }

    /**
     * Check if message has been read by a specific user
     */
    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark message as read by a specific user
     */
    public function markAsReadBy(User $user): MessageRead
    {
        return $this->reads()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'read_at' => now(),
        ]);
    }

    /**
     * Get read count for this message
     */
    public function getReadCount(): int
    {
        return $this->reads()->count();
    }

    /**
     * Get users who have read this message
     */
    public function getReadByUsers()
    {
        return User::whereIn('id', $this->reads()->pluck('user_id'))->get();
    }

    /**
     * Check if message has attachments
     */
    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCount(): int
    {
        return count($this->attachments ?? []);
    }

    /**
     * Edit message content
     */
    public function editContent(string $newContent): void
    {
        $this->update([
            'content' => $newContent,
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return ! is_null($this->reply_to_id);
    }

    /**
     * Check if message is a system message
     */
    public function isSystemMessage(): bool
    {
        return $this->type === 'system';
    }

    // Scopes
    public function scopeInConversation($query, $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachments');
    }

    public function scopeReplies($query)
    {
        return $query->whereNotNull('reply_to_id');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('reply_to_id');
    }

    public function scopeEdited($query)
    {
        return $query->where('is_edited', true);
    }

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    // Legacy scopes for backward compatibility
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('recipient_id', $userId);
        });
    }

    public function scopeInbox($query, $userId)
    {
        return $query->where('recipient_id', $userId);
    }

    public function scopeSent($query, $userId)
    {
        return $query->where('sender_id', $userId);
    }

    // Legacy helper methods
    public function markAsRead()
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsReplied()
    {
        $this->update(['replied_at' => now()]);
    }

    public function archive()
    {
        $this->update(['is_archived' => true]);
    }

    public function unarchive()
    {
        $this->update(['is_archived' => false]);
    }

    public function isUnreadBy($userId)
    {
        return $this->recipient_id === $userId && $this->read_at === null;
    }
}
