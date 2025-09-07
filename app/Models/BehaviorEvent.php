<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BehaviorEvent Model
 *
 * Tracks user behaviors that can trigger email sequences and automated communications.
 * This model is essential for the Email Integration System to monitor user actions
 * and trigger appropriate email campaigns based on behavior patterns.
 *
 * @property int $id
 * @property string $tenant_id
 * @property int $user_id
 * @property string $event_type
 * @property array $event_data
 * @property \Carbon\Carbon $timestamp
 * @property array $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Tenant $tenant
 */
class BehaviorEvent extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event_type',
        'event_data',
        'timestamp',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the user that performed this behavior event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant this behavior event belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to filter behavior events by event type.
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter behavior events by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter behavior events by tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter behavior events within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent behavior events.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('timestamp', '>=', now()->subDays($days));
    }

    /**
     * Get the table name with tenant context.
     */
    public function getTable(): string
    {
        // This ensures tenant isolation if using database-per-tenant
        return 'behavior_events';
    }

    /**
     * Common behavior event types.
     */
    public const EVENT_TYPES = [
        'login' => 'User login',
        'logout' => 'User logout',
        'profile_update' => 'Profile information updated',
        'job_application' => 'Job application submitted',
        'job_view' => 'Job posting viewed',
        'event_registration' => 'Event registration',
        'event_attendance' => 'Event attendance',
        'course_enrollment' => 'Course enrollment',
        'course_completion' => 'Course completion',
        'forum_post' => 'Forum post created',
        'forum_reply' => 'Forum reply posted',
        'connection_request' => 'Connection request sent',
        'connection_accepted' => 'Connection request accepted',
        'mentorship_request' => 'Mentorship request sent',
        'mentorship_accepted' => 'Mentorship request accepted',
        'donation_made' => 'Donation completed',
        'fundraising_campaign_view' => 'Fundraising campaign viewed',
        'scholarship_application' => 'Scholarship application submitted',
        'email_opened' => 'Email opened',
        'email_clicked' => 'Email link clicked',
        'page_view' => 'Page viewed',
        'search_performed' => 'Search query executed',
        'document_download' => 'Document downloaded',
        'video_watched' => 'Video content watched',
        'survey_completed' => 'Survey completed',
        'feedback_submitted' => 'Feedback submitted',
        'account_created' => 'New account created',
        'password_reset' => 'Password reset requested',
        'profile_completion' => 'Profile completion milestone',
        'anniversary' => 'Account anniversary',
        'inactivity_warning' => 'Inactivity warning period',
        'custom' => 'Custom behavior event',
    ];

    /**
     * Validate event type.
     */
    public static function isValidEventType(string $eventType): bool
    {
        return array_key_exists($eventType, self::EVENT_TYPES);
    }

    /**
     * Get event type label.
     */
    public function getEventTypeLabel(): string
    {
        return self::EVENT_TYPES[$this->event_type] ?? 'Unknown Event Type';
    }

    /**
     * Check if this is a conversion event.
     */
    public function isConversionEvent(): bool
    {
        $conversionEvents = [
            'job_application',
            'event_registration',
            'course_enrollment',
            'donation_made',
            'scholarship_application',
            'mentorship_accepted',
            'connection_accepted',
        ];

        return in_array($this->event_type, $conversionEvents);
    }

    /**
     * Check if this is an engagement event.
     */
    public function isEngagementEvent(): bool
    {
        $engagementEvents = [
            'job_view',
            'event_attendance',
            'forum_post',
            'forum_reply',
            'email_opened',
            'email_clicked',
            'page_view',
            'video_watched',
            'document_download',
        ];

        return in_array($this->event_type, $engagementEvents);
    }
}