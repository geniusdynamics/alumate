<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeakerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'speaker_title',
        'bio',
        'speaking_experience',
        'expertise_topics',
        'speaking_formats',
        'target_audiences',
        'industries',
        'speaking_fee',
        'travel_willing',
        'max_travel_distance',
        'virtual_speaking',
        'availability_preferences',
        'preferred_contact_method',
        'special_requirements',
        'past_speaking_engagements',
        'demo_video_url',
        'testimonials',
        'rating',
        'total_engagements',
        'is_active',
        'is_featured',
        'last_engagement_at',
    ];

    protected $casts = [
        'expertise_topics' => 'array',
        'speaking_formats' => 'array',
        'target_audiences' => 'array',
        'industries' => 'array',
        'speaking_fee' => 'decimal:2',
        'max_travel_distance' => 'integer',
        'travel_willing' => 'boolean',
        'virtual_speaking' => 'boolean',
        'availability_preferences' => 'array',
        'past_speaking_engagements' => 'array',
        'testimonials' => 'array',
        'rating' => 'decimal:2',
        'total_engagements' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'last_engagement_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(SpeakerBookingRequest::class, 'speaker_id', 'user_id');
    }

    public function completedBookings(): HasMany
    {
        return $this->bookingRequests()->where('status', 'completed');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->active();
    }

    public function scopeVirtualAvailable($query)
    {
        return $query->where('virtual_speaking', true);
    }

    public function scopeTravelWilling($query)
    {
        return $query->where('travel_willing', true);
    }

    public function scopeByTopic($query, $topic)
    {
        return $query->whereJsonContains('expertise_topics', $topic);
    }

    public function scopeByFormat($query, $format)
    {
        return $query->whereJsonContains('speaking_formats', $format);
    }

    public function scopeByAudience($query, $audience)
    {
        return $query->whereJsonContains('target_audiences', $audience);
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->whereJsonContains('industries', $industry);
    }

    public function scopeWithinBudget($query, $budget)
    {
        return $query->where(function ($q) use ($budget) {
            $q->whereNull('speaking_fee')
                ->orWhere('speaking_fee', '<=', $budget);
        });
    }

    // Methods
    public function isFree(): bool
    {
        return is_null($this->speaking_fee) || $this->speaking_fee == 0;
    }

    public function canTravelTo($distance): bool
    {
        return $this->travel_willing &&
               (is_null($this->max_travel_distance) || $this->max_travel_distance >= $distance);
    }

    public function updateRating(): void
    {
        $completedBookings = $this->completedBookings()->whereNotNull('rating');

        if ($completedBookings->count() > 0) {
            $this->rating = $completedBookings->avg('rating');
            $this->total_engagements = $completedBookings->count();
            $this->last_engagement_at = $completedBookings->latest('event_date')->first()?->event_date;
            $this->save();
        }
    }

    public function getAvailabilityStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        // Check for upcoming bookings in the next 30 days
        $upcomingBookings = $this->bookingRequests()
            ->where('status', 'accepted')
            ->where('event_date', '>=', now())
            ->where('event_date', '<=', now()->addDays(30))
            ->count();

        if ($upcomingBookings >= 4) {
            return 'busy';
        } elseif ($upcomingBookings >= 2) {
            return 'limited';
        }

        return 'available';
    }

    public function getFeeDisplay(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        return '$'.number_format($this->speaking_fee, 2);
    }

    public function getRatingDisplay(): string
    {
        if ($this->rating > 0) {
            return number_format($this->rating, 1).'/5.0 ('.$this->total_engagements.' events)';
        }

        return 'No ratings yet';
    }
}
