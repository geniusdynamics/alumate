<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreenSharingSession extends Model
{
    protected $fillable = [
        'call_id',
        'presenter_user_id',
        'started_at',
        'ended_at',
        'session_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'session_data' => 'array',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('started_at')->whereNull('ended_at');
    }

    public function scopeForCall($query, $callId)
    {
        return $query->where('call_id', $callId);
    }

    public function isActive(): bool
    {
        return $this->started_at && ! $this->ended_at;
    }

    public function end(): void
    {
        $this->update(['ended_at' => now()]);
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->started_at) {
            $endTime = $this->ended_at ?? now();

            return $endTime->diffInMinutes($this->started_at);
        }

        return null;
    }
}
