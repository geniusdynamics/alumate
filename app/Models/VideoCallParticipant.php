<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallParticipant extends Model
{
    protected $fillable = [
        'call_id',
        'user_id',
        'role',
        'joined_at',
        'left_at',
        'connection_quality',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'connection_quality' => 'array',
    ];

    public function call(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('joined_at')->whereNull('left_at');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isActive(): bool
    {
        return $this->joined_at && ! $this->left_at;
    }

    public function getDurationAttribute(): ?int
    {
        if ($this->joined_at) {
            $endTime = $this->left_at ?? now();

            return $endTime->diffInMinutes($this->joined_at);
        }

        return null;
    }

    public function leave(): void
    {
        $this->update(['left_at' => now()]);
    }
}
