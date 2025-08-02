<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'user_id',
        'status',
        'applied_at',
        'cover_letter',
        'resume_url',
        'introduction_requested',
        'introduction_contact_id',
        'notes',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'introduction_requested' => 'boolean',
    ];

    protected $dates = [
        'applied_at',
        'created_at',
        'updated_at',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_INTERVIEWING = 'interviewing';
    const STATUS_OFFERED = 'offered';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Get the job posting for this application
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * Get the user who applied
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the contact for introduction (if requested)
     */
    public function introductionContact(): BelongsTo
    {
        return $this->belongsTo(User::class, 'introduction_contact_id');
    }

    /**
     * Get the graduate profile for this application
     * This assumes the user_id corresponds to a graduate
     */
    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class, 'user_id', 'user_id');
    }

    /**
     * Check if application is still active
     */
    public function isActive(): bool
    {
        return !in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ]);
    }

    /**
     * Check if introduction was requested
     */
    public function hasIntroductionRequest(): bool
    {
        return $this->introduction_requested && $this->introduction_contact_id;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_REVIEWING => 'blue',
            self::STATUS_INTERVIEWING => 'purple',
            self::STATUS_OFFERED => 'green',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_WITHDRAWN => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get human-readable status
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Application Submitted',
            self::STATUS_REVIEWING => 'Under Review',
            self::STATUS_INTERVIEWING => 'Interview Process',
            self::STATUS_OFFERED => 'Offer Extended',
            self::STATUS_ACCEPTED => 'Offer Accepted',
            self::STATUS_REJECTED => 'Not Selected',
            self::STATUS_WITHDRAWN => 'Application Withdrawn',
            default => 'Unknown Status',
        };
    }

    /**
     * Scope to active applications
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
            self::STATUS_WITHDRAWN,
        ]);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}