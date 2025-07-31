<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventNetworkingConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'connected_user_id',
        'connection_type',
        'connection_note',
        'shared_interests',
        'follow_up_requested',
        'connected_at',
        'last_interaction_at',
    ];

    protected $casts = [
        'shared_interests' => 'array',
        'follow_up_requested' => 'boolean',
        'connected_at' => 'datetime',
        'last_interaction_at' => 'datetime',
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

    public function connectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_user_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('connection_type', $type);
    }

    public function scopeWithFollowUp($query)
    {
        return $query->where('follow_up_requested', true);
    }

    public function scopeRecentConnections($query, int $days = 7)
    {
        return $query->where('connected_at', '>=', now()->subDays($days));
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('connected_user_id', $user->id);
        });
    }

    // Helper methods
    public function getOtherUser(User $currentUser): User
    {
        return $currentUser->id === $this->user_id 
            ? $this->connectedUser 
            : $this->user;
    }

    public function updateLastInteraction(): void
    {
        $this->update(['last_interaction_at' => now()]);
    }

    public function requestFollowUp(): void
    {
        $this->update(['follow_up_requested' => true]);
    }

    public function completeFollowUp(): void
    {
        $this->update([
            'follow_up_requested' => false,
            'last_interaction_at' => now(),
        ]);
    }

    public function addSharedInterest(string $interest): void
    {
        $interests = $this->shared_interests ?? [];
        if (!in_array($interest, $interests)) {
            $interests[] = $interest;
            $this->update(['shared_interests' => $interests]);
        }
    }

    public function removeSharedInterest(string $interest): void
    {
        $interests = $this->shared_interests ?? [];
        $interests = array_filter($interests, fn($i) => $i !== $interest);
        $this->update(['shared_interests' => array_values($interests)]);
    }

    public function getConnectionTypeLabel(): string
    {
        return match($this->connection_type) {
            'met_at_event' => 'Met at Event',
            'mutual_interest' => 'Mutual Interest',
            'follow_up' => 'Follow-up Connection',
            'collaboration' => 'Collaboration Opportunity',
            default => 'Connection'
        };
    }

    public function getDaysSinceConnection(): int
    {
        return $this->connected_at->diffInDays(now());
    }

    public function getDaysSinceLastInteraction(): ?int
    {
        return $this->last_interaction_at?->diffInDays(now());
    }

    public function isRecentConnection(int $days = 7): bool
    {
        return $this->getDaysSinceConnection() <= $days;
    }

    public function needsFollowUp(int $days = 14): bool
    {
        if ($this->follow_up_requested) {
            return true;
        }

        $daysSinceLastInteraction = $this->getDaysSinceLastInteraction();
        return $daysSinceLastInteraction === null || $daysSinceLastInteraction >= $days;
    }
}