<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Has User Preferences Trait
 *
 * Extracts preference-related fields from the monolithic User model.
 * Contains: preferences (JSON), timezone, locale, notification settings
 *
 * Used by: User
 */
trait HasUserPreferences
{
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
        return $this->preferences['notifications'] ?? [
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
        return $this->preferences['appearance'] ?? [
            'theme' => 'light',
            'language' => $this->locale ?? 'en',
            'timezone' => $this->timezone ?? 'UTC',
            'compact_mode' => false,
        ];
    }

    /**
     * Get privacy preferences
     */
    public function getPrivacyPreferences(): array
    {
        return $this->preferences['privacy'] ?? [
            'profile_visibility' => 'public',
            'show_email' => false,
            'show_phone' => false,
            'show_location' => true,
            'allow_messages' => true,
            'location_privacy' => 'city',
        ];
    }

    /**
     * Scope: Filter by timezone
     */
    public function scopeWhereTimezone(Builder $query, string $timezone): Builder
    {
        return $query->where('timezone', $timezone);
    }

    /**
     * Scope: Filter by locale
     */
    public function scopeWhereLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }
}
