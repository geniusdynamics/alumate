<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MentorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'expertise_areas',
        'availability',
        'max_mentees',
        'is_active',
    ];

    protected $casts = [
        'expertise_areas' => 'array',
        'is_active' => 'boolean',
        'max_mentees' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentorshipRequests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id', 'user_id');
    }

    public function activeMentorships(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id', 'user_id')
            ->where('status', 'accepted');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->whereHas('user', function ($q) {
                $q->whereRaw('(SELECT COUNT(*) FROM mentorship_requests WHERE mentor_id = users.id AND status = "accepted") < mentor_profiles.max_mentees');
            });
    }

    public function getCurrentMenteeCount(): int
    {
        return $this->activeMentorships()->count();
    }

    public function hasAvailableSlots(): bool
    {
        return $this->getCurrentMenteeCount() < $this->max_mentees;
    }

    public function getAvailabilityStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if (! $this->hasAvailableSlots()) {
            return 'full';
        }

        return $this->availability;
    }
}
