<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VideoCall extends Model
{
    protected $fillable = [
        'host_user_id',
        'title',
        'description',
        'type',
        'provider',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'max_participants',
        'room_id',
        'jitsi_room_name',
        'livekit_room_token',
        'settings',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($call) {
            if (empty($call->room_id)) {
                $call->room_id = 'room_' . Str::uuid();
            }
        });
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(VideoCallParticipant::class, 'call_id');
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(CallRecording::class, 'call_id');
    }

    public function screenSharingSessions(): HasMany
    {
        return $this->hasMany(ScreenSharingSession::class, 'call_id');
    }

    public function coffeeChatRequest(): HasMany
    {
        return $this->hasMany(CoffeeChatRequest::class, 'call_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('host_user_id', $userId)
              ->orWhereHas('participants', function ($participantQuery) use ($userId) {
                  $participantQuery->where('user_id', $userId);
              });
        });
    }

    public function isHost(User $user): bool
    {
        return $this->host_user_id === $user->id;
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function canUserAccess(User $user): bool
    {
        return $this->isHost($user) || $this->hasParticipant($user);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return $this->ended_at->diffInMinutes($this->started_at);
        }
        return null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
