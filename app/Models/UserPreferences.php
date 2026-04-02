<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Preferences Model
 *
 * Stores user preferences and settings separate from authentication data.
 * Extracted from the monolithic User model (967 lines).
 */
class UserPreferences extends Model
{
    use HasFactory;

    protected $table = 'user_preferences';

    protected $fillable = [
        'user_id',
        'preferences',
        'notification_preferences',
        'appearance_preferences',
        'privacy_preferences',
        'email_preferences',
        'timezone',
        'locale',
        'language',
    ];

    protected $casts = [
        'preferences' => 'array',
        'notification_preferences' => 'array',
        'appearance_preferences' => 'array',
        'privacy_preferences' => 'array',
        'email_preferences' => 'array',
    ];

    /**
     * Get the user that owns these preferences.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a specific preference value
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->preferences[$key] ?? $default;
    }

    /**
     * Set a specific preference value
     */
    public function setPreference(string $key, mixed $value): void
    {
        $preferences = $this->preferences ?? [];
        $preferences[$key] = $value;
        $this->preferences = $preferences;
        $this->save();
    }

    /**
     * Get notification preferences
     */
    public function getNotificationPreferences(): array
    {
        return $this->notification_preferences ?? [
            'email' => true,
            'push' => true,
            'sms' => false,
            'digest' => 'weekly',
            'job_alerts' => true,
            'event_reminders' => true,
            'mentorship_requests' => true,
            'connection_requests' => true,
        ];
    }

    /**
     * Check if user wants a specific notification type
     */
    public function wantsNotification(string $type): bool
    {
        $prefs = $this->getNotificationPreferences();

        return $prefs[$type] ?? true;
    }

    /**
     * Get appearance preferences
     */
    public function getAppearancePreferences(): array
    {
        return $this->appearance_preferences ?? [
            'theme' => 'light',
            'compact_mode' => false,
        ];
    }

    /**
     * Get privacy preferences
     */
    public function getPrivacyPreferences(): array
    {
        return $this->privacy_preferences ?? [
            'profile_visibility' => 'public',
            'show_email' => false,
            'show_phone' => false,
            'show_location' => true,
            'allow_messages' => true,
        ];
    }

    /**
     * Scope: Filter by timezone
     */
    public function scopeWhereTimezone($query, string $timezone)
    {
        return $query->where('timezone', $timezone);
    }

    /**
     * Scope: Filter by locale
     */
    public function scopeWhereLocale($query, string $locale)
    {
        return $query->where('locale', $locale);
    }
}
