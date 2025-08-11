<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorshipSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentorship_id',
        'scheduled_at',
        'duration',
        'notes',
        'status',
        'feedback',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration' => 'integer',
        'feedback' => 'array',
    ];

    public function mentorship(): BelongsTo
    {
        return $this->belongsTo(MentorshipRequest::class, 'mentorship_id');
    }

    public function mentor()
    {
        return $this->mentorship->mentor();
    }

    public function mentee()
    {
        return $this->mentorship->mentee();
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function complete(?array $feedback = null): bool
    {
        $this->status = 'completed';
        if ($feedback) {
            $this->feedback = $feedback;
        }

        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = 'cancelled';

        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';

        return $this->save();
    }

    public function getEndTime()
    {
        return $this->scheduled_at->copy()->addMinutes($this->duration);
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at > now();
    }

    public function isPast(): bool
    {
        return $this->scheduled_at < now();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'scheduled' && $this->isPast();
    }
}
