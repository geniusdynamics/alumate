<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Has Authentication Security Trait
 *
 * Extracts authentication and security-related fields from the monolithic User model.
 * Contains: password, two_factor, login tracking, suspension
 *
 * Used by: User
 */
trait HasAuthenticationSecurity
{
    /**
     * Record successful login
     */
    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'login_count' => ($this->login_count ?? 0) + 1,
        ]);
    }

    /**
     * Check if password needs rotation
     */
    public function needsPasswordRotation(int $maxDays = 90): bool
    {
        if (! $this->password_changed_at) {
            return true;
        }

        return $this->password_changed_at->diffInDays(now()) > $maxDays;
    }

    /**
     * Check if account is locked
     */
    public function isLocked(): bool
    {
        return $this->is_suspended ?? false;
    }

    /**
     * Suspend the user account
     */
    public function suspend(string $reason = ''): void
    {
        $this->update([
            'is_suspended' => true,
            'metadata' => array_merge($this->metadata ?? [], [
                'suspended_at' => now()->toISOString(),
                'suspension_reason' => $reason,
            ]),
        ]);
    }

    /**
     * Unsuspend the user account
     */
    public function unsuspend(): void
    {
        $metadata = $this->metadata ?? [];
        unset($metadata['suspended_at'], $metadata['suspension_reason']);

        $this->update([
            'is_suspended' => false,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if two-factor is enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled ?? false;
    }

    /**
     * Enable two-factor authentication
     */
    public function enableTwoFactor(string $secret): void
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => $secret,
        ]);
    }

    /**
     * Disable two-factor authentication
     */
    public function disableTwoFactor(): void
    {
        $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);
    }

    /**
     * Scope: Filter active users only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_suspended', false);
    }

    /**
     * Scope: Filter suspended users
     */
    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('is_suspended', true);
    }

    /**
     * Scope: Filter users who haven't logged in recently
     */
    public function scopeInactiveForDays(Builder $query, int $days): Builder
    {
        return $query->where('last_login_at', '<', now()->subDays($days));
    }

    /**
     * Scope: Filter users needing password rotation
     */
    public function scopeNeedsPasswordRotation(Builder $query, int $maxDays = 90): Builder
    {
        return $query->where(function ($q) use ($maxDays) {
            $q->whereNull('password_changed_at')
                ->orWhere('password_changed_at', '<', now()->subDays($maxDays));
        });
    }
}
