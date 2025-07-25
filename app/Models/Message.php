<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'recipient_id',
        'subject',
        'content',
        'type',
        'related_job_id',
        'related_application_id',
        'read_at',
        'replied_at',
        'is_archived',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'is_archived' => 'boolean',
    ];

    // Relationships
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

    // Scopes
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

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function($q) use ($userId) {
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

    // Helper methods
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

    public function isReadBy($userId)
    {
        return $this->recipient_id === $userId && $this->read_at !== null;
    }

    public function isUnreadBy($userId)
    {
        return $this->recipient_id === $userId && $this->read_at === null;
    }
}