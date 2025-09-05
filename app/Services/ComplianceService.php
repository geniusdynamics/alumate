<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailPreference;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * ComplianceService
 *
 * Core service for managing email compliance, unsubscribe functionality,
 * and preference management with GDPR and CAN-SPAM compliance.
 */
class ComplianceService
{
    /**
     * Generate a secure unsubscribe link with signed URL.
     */
    public function generateUnsubscribeLink(EmailPreference $preference, array $metadata = []): string
    {
        $token = $preference->generateUnsubscribeToken();

        return URL::signedRoute('api.unsubscribe.confirm', [
            'token' => $token,
            'email' => $preference->email,
        ], now()->addDays(30)); // Link expires in 30 days
    }

    /**
     * Process unsubscribe request with validation.
     */
    public function processUnsubscribe(string $token, string $email, array $categories = []): array
    {
        $preference = $this->findPreferenceByTokenAndEmail($token, $email);

        if (!$preference) {
            Log::warning('Invalid unsubscribe attempt', [
                'token' => substr($token, 0, 8) . '...',
                'email' => $email,
                'ip' => request()->ip(),
            ]);

            return [
                'success' => false,
                'message' => 'Invalid or expired unsubscribe link.',
            ];
        }

        // If specific categories provided, unsubscribe from those only
        if (!empty($categories)) {
            $currentPreferences = $preference->preferences ?? [];
            foreach ($categories as $category) {
                $currentPreferences[$category] = false;
            }
            $preference->updatePreferences($currentPreferences);

            return [
                'success' => true,
                'message' => 'Successfully unsubscribed from selected categories.',
                'categories' => $categories,
            ];
        }

        // Full unsubscribe - withdraw consent
        $preference->withdrawConsent();

        Log::info('User unsubscribed', [
            'email' => $email,
            'tenant_id' => $preference->tenant_id,
            'ip' => request()->ip(),
        ]);

        return [
            'success' => true,
            'message' => 'Successfully unsubscribed from all communications.',
        ];
    }

    /**
     * Create or update email preferences for a user.
     */
    public function createOrUpdatePreferences(
        string $email,
        Tenant $tenant,
        array $preferences = [],
        ?User $user = null
    ): EmailPreference {
        $preference = EmailPreference::firstOrNew([
            'email' => $email,
            'tenant_id' => $tenant->id,
        ]);

        if ($preference->exists) {
            // Update existing preferences
            $preference->updatePreferences($preferences);
        } else {
            // Create new preference record
            $preference->fill([
                'user_id' => $user?->id,
                'preferences' => $preferences,
                'consent_given_at' => now(),
                'gdpr_compliant' => true,
                'can_spam_compliant' => true,
                'audit_trail' => [
                    [
                        'action' => 'preferences_created',
                        'timestamp' => now()->toISOString(),
                        'preferences' => $preferences,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]
                ]
            ]);
            $preference->save();
        }

        return $preference;
    }

    /**
     * Initiate double opt-in process.
     */
    public function initiateDoubleOptIn(string $email, Tenant $tenant, ?User $user = null): EmailPreference
    {
        $token = Str::random(64);

        $preference = EmailPreference::firstOrNew([
            'email' => $email,
            'tenant_id' => $tenant->id,
        ]);

        $preference->fill([
            'user_id' => $user?->id,
            'double_opt_in_token' => $token,
            'audit_trail' => array_merge($preference->audit_trail ?? [], [
                [
                    'action' => 'double_opt_in_initiated',
                    'timestamp' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ])
        ]);

        $preference->save();

        // Send double opt-in email
        $this->sendDoubleOptInEmail($preference, $token);

        return $preference;
    }

    /**
     * Confirm double opt-in.
     */
    public function confirmDoubleOptIn(string $token): array
    {
        $preference = EmailPreference::where('double_opt_in_token', $token)->first();

        if (!$preference) {
            return [
                'success' => false,
                'message' => 'Invalid or expired double opt-in token.',
            ];
        }

        $preference->verifyDoubleOptIn();

        return [
            'success' => true,
            'message' => 'Email address successfully verified.',
            'preference' => $preference,
        ];
    }

    /**
     * Get preference center data for a user.
     */
    public function getPreferenceCenterData(string $email, Tenant $tenant): array
    {
        $preference = EmailPreference::where('email', $email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$preference) {
            return [
                'email' => $email,
                'has_consent' => false,
                'preferences' => $this->getDefaultPreferences(),
                'frequency_settings' => $this->getDefaultFrequencySettings(),
                'compliance_status' => [
                    'gdpr' => false,
                    'can_spam' => false,
                ]
            ];
        }

        return [
            'email' => $email,
            'has_consent' => $preference->hasConsented(),
            'preferences' => $preference->preferences ?? $this->getDefaultPreferences(),
            'frequency_settings' => $preference->frequency_settings ?? $this->getDefaultFrequencySettings(),
            'compliance_status' => [
                'gdpr' => $preference->gdpr_compliant,
                'can_spam' => $preference->can_spam_compliant,
            ],
            'consent_given_at' => $preference->consent_given_at,
            'last_updated' => $preference->updated_at,
        ];
    }

    /**
     * Validate compliance requirements.
     */
    public function validateCompliance(EmailPreference $preference): array
    {
        $issues = [];

        // Check GDPR compliance
        if (!$preference->gdpr_compliant) {
            $issues[] = 'GDPR compliance not confirmed';
        }

        // Check CAN-SPAM compliance
        if (!$preference->can_spam_compliant) {
            $issues[] = 'CAN-SPAM compliance not confirmed';
        }

        // Check consent validity
        if (!$preference->hasConsented()) {
            $issues[] = 'Valid consent not provided';
        }

        // Check for expired consent (if applicable)
        if ($preference->consent_given_at && $preference->consent_given_at->addYears(1)->isPast()) {
            $issues[] = 'Consent requires renewal (annual review)';
        }

        return [
            'compliant' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Generate compliance report for a tenant.
     */
    public function generateComplianceReport(Tenant $tenant, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = EmailPreference::where('tenant_id', $tenant->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $preferences = $query->get();

        $total = $preferences->count();
        $withConsent = $preferences->filter->hasConsented()->count();
        $gdprCompliant = $preferences->filter->gdpr_compliant->count();
        $canSpamCompliant = $preferences->filter->can_spam_compliant->count();
        $unsubscribed = $preferences->filter->consent_withdrawn_at->count();

        return [
            'period' => [
                'start' => $startDate?->toDateString(),
                'end' => $endDate?->toDateString(),
            ],
            'totals' => [
                'total_subscribers' => $total,
                'active_consent' => $withConsent,
                'gdpr_compliant' => $gdprCompliant,
                'can_spam_compliant' => $canSpamCompliant,
                'unsubscribed' => $unsubscribed,
            ],
            'percentages' => [
                'consent_rate' => $total > 0 ? round(($withConsent / $total) * 100, 2) : 0,
                'gdpr_compliance_rate' => $total > 0 ? round(($gdprCompliant / $total) * 100, 2) : 0,
                'can_spam_compliance_rate' => $total > 0 ? round(($canSpamCompliant / $total) * 100, 2) : 0,
                'unsubscribe_rate' => $total > 0 ? round(($unsubscribed / $total) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Find preference by token and email.
     */
    private function findPreferenceByTokenAndEmail(string $token, string $email): ?EmailPreference
    {
        return EmailPreference::where('unsubscribe_token', $token)
            ->where('email', $email)
            ->whereNull('consent_withdrawn_at')
            ->first();
    }

    /**
     * Send double opt-in confirmation email.
     */
    private function sendDoubleOptInEmail(EmailPreference $preference, string $token): void
    {
        // Implementation would send actual email
        // For now, just log the action
        Log::info('Double opt-in email sent', [
            'email' => $preference->email,
            'token' => substr($token, 0, 8) . '...',
        ]);
    }

    /**
     * Get default preferences.
     */
    private function getDefaultPreferences(): array
    {
        return [
            'newsletters' => true,
            'promotions' => false,
            'announcements' => true,
            'events' => true,
            'surveys' => false,
        ];
    }

    /**
     * Get default frequency settings.
     */
    private function getDefaultFrequencySettings(): array
    {
        return [
            'newsletters' => 'weekly',
            'promotions' => 'monthly',
            'announcements' => 'immediate',
            'events' => 'weekly',
            'surveys' => 'quarterly',
        ];
    }
}