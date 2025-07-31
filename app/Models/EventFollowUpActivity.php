<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFollowUpActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'activity_type',
        'activity_data',
        'completed_at',
    ];

    protected $casts = [
        'activity_data' => 'array',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('completed_at', '>=', now()->subDays($days));
    }

    public function scopeForEvent($query, Event $event)
    {
        return $query->where('event_id', $event->id);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    // Helper methods
    public function getActivityTypeLabel(): string
    {
        return match($this->activity_type) {
            'survey_completed' => 'Survey Completed',
            'connections_made' => 'Connections Made',
            'content_shared' => 'Content Shared',
            'feedback_given' => 'Feedback Given',
            'highlight_created' => 'Highlight Created',
            'follow_up_sent' => 'Follow-up Sent',
            default => 'Activity'
        };
    }

    public function getActivityDescription(): string
    {
        $data = $this->activity_data ?? [];
        
        return match($this->activity_type) {
            'survey_completed' => 'Completed post-event survey',
            'connections_made' => sprintf('Made %d new connections', $data['count'] ?? 1),
            'content_shared' => sprintf('Shared %s', $data['content_type'] ?? 'content'),
            'feedback_given' => sprintf('Provided feedback with %d-star rating', $data['rating'] ?? 0),
            'highlight_created' => sprintf('Created %s highlight', $data['highlight_type'] ?? 'event'),
            'follow_up_sent' => sprintf('Sent follow-up to %d connections', $data['count'] ?? 1),
            default => 'Completed activity'
        };
    }

    public function getActivityValue(string $key): mixed
    {
        $data = $this->activity_data ?? [];
        return $data[$key] ?? null;
    }

    public function setActivityValue(string $key, mixed $value): void
    {
        $data = $this->activity_data ?? [];
        $data[$key] = $value;
        $this->update(['activity_data' => $data]);
    }

    public function getDaysAgo(): int
    {
        return $this->completed_at->diffInDays(now());
    }

    public function isRecent(int $days = 7): bool
    {
        return $this->getDaysAgo() <= $days;
    }
}