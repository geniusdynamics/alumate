<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorshipRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'mentee_id',
        'message',
        'status',
        'goals',
        'duration_months',
        'accepted_at',
        'completed_at',
        'student_profile_data',
    ];

    protected $casts = [
        'duration_months' => 'integer',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'student_profile_data' => 'array',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class, 'mentorship_id');
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->where('status', 'completed');
    }

    public function upcomingSessions(): HasMany
    {
        return $this->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'accepted')
            ->whereNull('completed_at');
    }

    public function accept(): bool
    {
        $this->status = 'accepted';
        $this->accepted_at = now();

        return $this->save();
    }

    public function decline(): bool
    {
        $this->status = 'declined';

        return $this->save();
    }

    public function complete(): bool
    {
        $this->status = 'completed';
        $this->completed_at = now();

        return $this->save();
    }

    public function getProgressPercentage(): float
    {
        if ($this->status !== 'accepted' || ! $this->accepted_at) {
            return 0;
        }

        $startDate = $this->accepted_at;
        $endDate = $startDate->copy()->addMonths($this->duration_months);
        $now = now();

        if ($now >= $endDate) {
            return 100;
        }

        $totalDays = $startDate->diffInDays($endDate);
        $elapsedDays = $startDate->diffInDays($now);

        return min(100, ($elapsedDays / $totalDays) * 100);
    }

    public function getExpectedEndDate()
    {
        return $this->accepted_at?->copy()->addMonths($this->duration_months);
    }
}
