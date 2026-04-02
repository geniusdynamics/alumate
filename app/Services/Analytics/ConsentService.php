<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Consent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Consent Service for managing user consent for analytics tracking
 */
class ConsentService
{
    private const CONSENT_CACHE_KEY = 'analytics_consent_';

    private const CACHE_TTL_MINUTES = 60;

    public function __construct(
        private PrivacyAuditService $auditService
    ) {}

    /**
     * Check if the current user has given consent for analytics tracking
     *
     * @param  int|null  $userId  User ID to check (null for current user)
     * @param  string  $type  Consent type (default: 'analytics')
     * @return bool True if consent is given, false otherwise
     */
    public function hasConsent(?int $userId = null, string $type = 'analytics'): bool
    {
        $user = $userId ? null : Auth::user();

        if (! $user && ! $userId) {
            // For guest users, check session or default to no consent
            return session('analytics_consent', false);
        }

        $targetUserId = $userId ?? $user->id;
        $cacheKey = self::CONSENT_CACHE_KEY.$targetUserId.'_'.$type;

        // Check if consent status is cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Query database for active consent
        $hasConsent = Consent::active()
            ->byUser($targetUserId)
            ->byType($type)
            ->exists();

        // Cache the consent status
        Cache::put($cacheKey, $hasConsent, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $hasConsent;
    }

    /**
     * Grant consent for analytics tracking
     *
     * @param  int|null  $userId  User ID to grant consent for (null for current user)
     * @param  string  $type  Consent type (default: 'analytics')
     * @return bool True if consent was successfully granted
     */
    public function grantConsent(?int $userId = null, string $type = 'analytics'): bool
    {
        $user = $userId ? null : Auth::user();

        if (! $user && ! $userId) {
            // For guest users, set session consent
            session(['analytics_consent' => true]);

            return true;
        }

        $targetUserId = $userId ?? $user->id;

        try {
            // Revoke any existing consent first
            Consent::active()
                ->byUser($targetUserId)
                ->byType($type)
                ->update(['revoked_at' => now()]);

            // Create new consent record
            Consent::create([
                'user_id' => $targetUserId,
                'type' => $type,
                'granted_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            // Clear cache
            $this->clearCachedConsent($targetUserId, $type);

            Log::info('Analytics consent granted', [
                'user_id' => $targetUserId,
                'type' => $type,
                'ip_address' => request()->ip(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to grant analytics consent', [
                'user_id' => $targetUserId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Revoke consent for analytics tracking
     *
     * @param  int|null  $userId  User ID to revoke consent for (null for current user)
     * @param  string  $type  Consent type (default: 'analytics')
     * @return bool True if consent was successfully revoked
     */
    public function revokeConsent(?int $userId = null, string $type = 'analytics'): bool
    {
        $user = $userId ? null : Auth::user();

        if (! $user && ! $userId) {
            // For guest users, remove session consent
            session()->forget('analytics_consent');

            return true;
        }

        $targetUserId = $userId ?? $user->id;

        try {
            // Update active consents to revoked
            $updated = Consent::active()
                ->byUser($targetUserId)
                ->byType($type)
                ->update(['revoked_at' => now()]);

            if ($updated > 0) {
                // Dispatch data purge job
                // ConsentPurgeJob::dispatch($targetUserId, $type);

                // Clear cache
                $this->clearCachedConsent($targetUserId, $type);

                Log::info('Analytics consent revoked', [
                    'user_id' => $targetUserId,
                    'type' => $type,
                    'ip_address' => request()->ip(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to revoke analytics consent', [
                'user_id' => $targetUserId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check consent before performing an action and auto-prompt if needed
     *
     * @param  string  $action  Action being performed
     * @param  mixed  $user  User instance or ID
     * @param  string  $type  Consent type
     * @return bool True if action can proceed
     */
    public function checkConsentBeforeAction(string $action, $user, string $type = 'analytics'): bool
    {
        $userId = is_int($user) ? $user : $user->id;

        if (! $this->hasConsent($userId, $type)) {
            // Log the attempt for audit
            $this->generateAuditLog($userId, 'consent_required', [
                'action' => $action,
                'type' => $type,
                'timestamp' => now(),
            ]);

            // Auto-prompt would be handled by frontend, return false to block action
            return false;
        }

        return true;
    }

    /**
     * Generate audit log for privacy events
     *
     * @param  int  $userId  User ID
     * @param  string  $action  Action performed
     * @param  array  $details  Additional details
     */
    public function generateAuditLog(int $userId, string $action, array $details = []): void
    {
        $this->auditService->logPrivacyEvent($action, $userId, $details);
    }

    /**
     * Handle data export request for GDPR right to portability
     *
     * @param  mixed  $user  User instance or ID
     * @return array Exported data
     */
    public function handleDataExportRequest($user): array
    {
        $userId = is_int($user) ? $user : $user->id;

        $this->generateAuditLog($userId, 'data_export_requested', [
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $exportData = [
            'user_id' => $userId,
            'export_timestamp' => now(),
            'consent_history' => Consent::byUser($userId)->get()->toArray(),
        ];

        // Add analytics data if consent is active
        if ($this->hasConsent($userId, 'analytics')) {
            $exportData['analytics_events'] = \App\Models\AnalyticsEvent::byUser($userId)->get()->toArray();
            $exportData['insights'] = \App\Models\Insight::where('user_id', $userId)->get()->toArray();
        } else {
            $exportData['analytics_events'] = 'consent_not_granted';
            $exportData['insights'] = 'consent_not_granted';
        }

        return $exportData;
    }

    /**
     * Integrate CCPA opt-out functionality
     *
     * @param  mixed  $user  User instance or ID
     * @param  bool  $optOut  Whether to opt out (true) or opt in (false)
     * @return bool Success status
     */
    public function integrateCCPAOptOut($user, bool $optOut = true): bool
    {
        $userId = is_int($user) ? $user : $user->id;

        if ($optOut) {
            // Revoke all consents for CCPA compliance
            $this->revokeConsent($userId, 'analytics');

            $this->generateAuditLog($userId, 'ccpa_opt_out', [
                'opt_out' => true,
                'ip_address' => request()->ip(),
            ]);

            // Dispatch purge job
            \App\Jobs\ConsentPurgeJob::dispatch($userId, 'analytics');
        } else {
            // Opt back in
            $this->grantConsent($userId, 'analytics');

            $this->generateAuditLog($userId, 'ccpa_opt_in', [
                'opt_out' => false,
                'ip_address' => request()->ip(),
            ]);
        }

        return true;
    }

    /**
     * Clear cached consent for a user
     *
     * @param  int|null  $userId  User ID to clear consent for (null for current user)
     * @param  string  $type  Consent type (default: 'analytics')
     */
    public function clearCachedConsent(?int $userId = null, string $type = 'analytics'): void
    {
        $user = $userId ? null : Auth::user();

        if ($userId) {
            $cacheKey = self::CONSENT_CACHE_KEY.$userId.'_'.$type;
            Cache::forget($cacheKey);
        } elseif ($user) {
            $cacheKey = self::CONSENT_CACHE_KEY.$user->id.'_'.$type;
            Cache::forget($cacheKey);
        }
    }
}
