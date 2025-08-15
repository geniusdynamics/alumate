<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeeChatRequest extends Model
{
    protected $fillable = [
        'requester_id',
        'recipient_id',
        'call_id',
        'type',
        'proposed_times',
        'selected_time',
        'status',
        'message',
        'matching_criteria',
    ];

    protected $casts = [
        'proposed_times' => 'array',
        'selected_time' => 'datetime',
        'matching_criteria' => 'array',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class, 'call_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('requester_id', $userId)
              ->orWhere('recipient_id', $userId);
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function accept(string $selectedTime): void
    {
        $this->update([
            'status' => 'accepted',
            'selected_time' => $selectedTime,
        ]);
    }

    public function decline(): void
    {
        $this->update(['status' => 'declined']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
