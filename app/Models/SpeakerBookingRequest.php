<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeakerBookingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'speaker_id',
        'requester_id',
        'event_id',
        'event_title',
        'event_description',
        'event_date',
        'event_start_time',
        'event_end_time',
        'event_location',
        'event_format',
        'topic_requested',
        'expected_audience_size',
        'audience_demographics',
        'event_type',
        'budget_offered',
        'special_requirements',
        'additional_notes',
        'status',
        'speaker_response',
        'requested_at',
        'responded_at',
        'confirmed_at',
        'booking_details',
        'final_fee',
        'feedback',
        'rating',
    ];

    protected $casts = [
        'event_date' => 'date',
        'event_start_time' => 'datetime:H:i',
        'event_end_time' => 'datetime:H:i',
        'audience_demographics' => 'array',
        'budget_offered' => 'decimal:2',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'booking_details' => 'array',
        'final_fee' => 'decimal:2',
        'feedback' => 'array',
        'rating' => 'decimal:2',
    ];

    // Relationships
    public function speaker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'speaker_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'accepted')
            ->where('event_date', '>=', now());
    }

    public function scopeForSpeaker($query, $speakerId)
    {
        return $query->where('speaker_id', $speakerId);
    }

    public function scopeForRequester($query, $requesterId)
    {
        return $query->where('requester_id', $requesterId);
    }

    // Methods
    public function accept(?string $response = null, ?array $bookingDetails = null): bool
    {
        $this->status = 'accepted';
        $this->speaker_response = $response;
        $this->booking_details = $bookingDetails;
        $this->responded_at = now();
        $this->confirmed_at = now();

        return $this->save();
    }

    public function decline(?string $response = null): bool
    {
        $this->status = 'declined';
        $this->speaker_response = $response;
        $this->responded_at = now();

        return $this->save();
    }

    public function cancel(?string $reason = null): bool
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->speaker_response = $reason;
        }

        return $this->save();
    }

    public function complete(?array $feedback = null, ?float $rating = null): bool
    {
        $this->status = 'completed';
        if ($feedback) {
            $this->feedback = $feedback;
        }
        if ($rating) {
            $this->rating = $rating;
        }

        return $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isUpcoming(): bool
    {
        return $this->isAccepted() && $this->event_date >= now()->toDateString();
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Response',
            'accepted' => 'Confirmed',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst($this->status)
        };
    }

    public function getEventTypeLabel(): string
    {
        return match ($this->event_type) {
            'virtual' => 'Virtual Event',
            'in_person' => 'In-Person Event',
            'hybrid' => 'Hybrid Event',
            default => ucfirst($this->event_type)
        };
    }

    public function getFormatLabel(): string
    {
        return match ($this->event_format) {
            'keynote' => 'Keynote Speech',
            'workshop' => 'Workshop',
            'panel' => 'Panel Discussion',
            'webinar' => 'Webinar',
            'seminar' => 'Seminar',
            'other' => 'Other',
            default => ucfirst($this->event_format)
        };
    }

    public function getDuration(): int
    {
        if ($this->event_start_time && $this->event_end_time) {
            return $this->event_start_time->diffInMinutes($this->event_end_time);
        }

        return 0;
    }

    public function getBudgetDisplay(): string
    {
        if ($this->budget_offered) {
            return '$'.number_format($this->budget_offered, 2);
        }

        return 'Not specified';
    }
}
