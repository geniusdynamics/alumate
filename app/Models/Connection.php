<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'connected_user_id',
        'status',
        'message',
        'connected_at',
    ];

    protected $casts = [
        'connected_at' => 'datetime',
    ];

    /**
     * Get the user who initiated the connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who received the connection request.
     */
    public function connectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_user_id');
    }

    /**
     * Scope to get pending connections.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get accepted connections.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope to get blocked connections.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Accept the connection request.
     */
    public function accept(): bool
    {
        $this->status = 'accepted';
        $this->connected_at = now();

        $saved = $this->save();

        if ($saved) {
            // Create the reciprocal connection
            static::updateOrCreate([
                'user_id' => $this->connected_user_id,
                'connected_user_id' => $this->user_id,
            ], [
                'status' => 'accepted',
                'connected_at' => now(),
                'message' => null,
            ]);
        }

        return $saved;
    }

    /**
     * Reject the connection request.
     */
    public function reject(): bool
    {
        return $this->delete();
    }

    /**
     * Block the connection.
     */
    public function block(): bool
    {
        $this->status = 'blocked';

        $saved = $this->save();

        if ($saved) {
            // Remove any reciprocal connection
            static::where('user_id', $this->connected_user_id)
                ->where('connected_user_id', $this->user_id)
                ->delete();
        }

        return $saved;
    }

    /**
     * Unblock the connection.
     */
    public function unblock(): bool
    {
        return $this->delete();
    }

    /**
     * Get the other user in the connection.
     */
    public function getOtherUser(User $currentUser): User
    {
        return $this->user_id === $currentUser->id
            ? $this->connectedUser
            : $this->user;
    }

    /**
     * Check if the connection is mutual (both users have accepted).
     */
    public function isMutual(): bool
    {
        if ($this->status !== 'accepted') {
            return false;
        }

        return static::where('user_id', $this->connected_user_id)
            ->where('connected_user_id', $this->user_id)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * Get the status with human-readable format.
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'accepted' => 'Connected',
            'blocked' => 'Blocked',
            default => 'Unknown',
        };
    }

    /**
     * Scope to get connections for a specific user (both sent and received).
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere('connected_user_id', $user->id);
        });
    }

    /**
     * Scope to get mutual connections between two users.
     */
    public function scopeMutualConnections($query, User $user1, User $user2)
    {
        $user1Connections = static::where('user_id', $user1->id)
            ->where('status', 'accepted')
            ->pluck('connected_user_id');

        $user2Connections = static::where('user_id', $user2->id)
            ->where('status', 'accepted')
            ->pluck('connected_user_id');

        $mutualIds = $user1Connections->intersect($user2Connections);

        return User::whereIn('id', $mutualIds);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Prevent duplicate connections
        static::creating(function ($connection) {
            $existing = static::where('user_id', $connection->user_id)
                ->where('connected_user_id', $connection->connected_user_id)
                ->first();

            if ($existing) {
                return false;
            }
        });
    }
}
