<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'registered_at',
        'checked_in_at',
        'guests_count',
        'guest_details',
        'special_requirements',
        'registration_data',
        'amount_paid',
        'payment_status',
        'payment_reference',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'guest_details' => 'array',
        'registration_data' => 'array',
        'guests_count' => 'integer',
        'amount_paid' => 'decimal:2',
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
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['registered', 'attended']);
    }

    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', 'waitlisted');
    }

    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Helper methods
    public function isActive(): bool
    {
        return in_array($this->status, ['registered', 'attended']);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['registered', 'waitlisted']) &&
               $this->event->start_date->isFuture();
    }

    public function canCheckIn(): bool
    {
        return $this->status === 'registered' &&
               ! $this->checked_in_at &&
               $this->event->enable_checkin;
    }

    public function getTotalAttendeesCount(): int
    {
        return 1 + $this->guests_count;
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        // Update event attendee count
        $this->event->updateAttendeeCount();
    }

    public function checkIn(): void
    {
        $this->update([
            'status' => 'attended',
            'checked_in_at' => now(),
        ]);
    }
}
