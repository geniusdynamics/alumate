<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * EmailPreference Model
 *
 * Manages email preferences and subscription controls for users
 * Supports tenant-specific preferences and granular subscription controls
 */
class EmailPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'tenant_id',
        'email',
        'preferences',
        'frequency_settings',
        'consent_given_at',
        'consent_withdrawn_at',
        'double_opt_in_verified_at',
        'double_opt_in_token',
        'unsubscribe_token',
        'gdpr_compliant',
        'can_spam_compliant',
        'audit_trail',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preferences' => 'json',
        'frequency_settings' => 'json',
        'consent_given_at' => 'datetime',
        'consent_withdrawn_at' => 'datetime',
        'double_opt_in_verified_at' => 'datetime',
        'gdpr_compliant' => 'boolean',
        'can_spam_compliant' => 'boolean',
        'audit_trail' => 'json',
    ];

    /**
     * Get the user that owns the email preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant that owns the email preference.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user has consented to email communications.
     */
    public function hasConsented(): bool
    {
        return $this->consent_given_at !== null &&
               $this->consent_withdrawn_at === null &&
               $this->double_opt_in_verified_at !== null;
    }

    /**
     * Check if user has double opt-in verified.
     */
    public function isDoubleOptInVerified(): bool
    {
        return $this->double_opt_in_verified_at !== null;
    }

    /**
     * Check if user is subscribed to a specific category.
     */
    public function isSubscribedTo(string $category): bool
    {
        return $this->hasConsented() &&
               ($this->preferences[$category] ?? true);
    }

    /**
     * Get frequency setting for a category.
     */
    public function getFrequencySetting(string $category, string $default = 'weekly'): string
    {
        return $this->frequency_settings[$category] ?? $default;
    }

    /**
     * Withdraw consent for email communications.
     */
    public function withdrawConsent(): void
    {
        $this->update([
            'consent_withdrawn_at' => now(),
            'unsubscribe_token' => null,
            'audit_trail' => array_merge($this->audit_trail ?? [], [
                [
                    'action' => 'consent_withdrawn',
                    'timestamp' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ])
        ]);
    }

    /**
     * Verify double opt-in.
     */
    public function verifyDoubleOptIn(): void
    {
        $this->update([
            'double_opt_in_verified_at' => now(),
            'double_opt_in_token' => null,
            'audit_trail' => array_merge($this->audit_trail ?? [], [
                [
                    'action' => 'double_opt_in_verified',
                    'timestamp' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ])
        ]);
    }

    /**
     * Update preferences for specific categories.
     */
    public function updatePreferences(array $preferences): void
    {
        $currentPreferences = $this->preferences ?? [];
        $updatedPreferences = array_merge($currentPreferences, $preferences);

        $this->update([
            'preferences' => $updatedPreferences,
            'audit_trail' => array_merge($this->audit_trail ?? [], [
                [
                    'action' => 'preferences_updated',
                    'timestamp' => now()->toISOString(),
                    'old_preferences' => $currentPreferences,
                    'new_preferences' => $updatedPreferences,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ])
        ]);
    }

    /**
     * Generate unsubscribe token.
     */
    public function generateUnsubscribeToken(): string
    {
        $token = bin2hex(random_bytes(32));

        $this->update([
            'unsubscribe_token' => $token,
            'audit_trail' => array_merge($this->audit_trail ?? [], [
                [
                    'action' => 'unsubscribe_token_generated',
                    'timestamp' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ])
        ]);

        return $token;
    }

    /**
     * Check if compliance requirements are met.
     */
    public function isCompliant(): bool
    {
        return $this->gdpr_compliant && $this->can_spam_compliant;
    }
}