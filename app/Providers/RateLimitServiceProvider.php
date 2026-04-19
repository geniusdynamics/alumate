<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        // Global API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $key = $user ? $user->id : $request->ip();
            $tenantId = $user?->currentTenant?->id ?? 'global';

            return Limit::perMinute(100)->by("api:{$tenantId}:{$key}");
        });

        // Authentication rate limiter (login attempts)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by('auth:'.$request->ip());
        });

        // Tenant-scoped rate limiter
        RateLimiter::for('tenant', function (Request $request) {
            $user = $request->user();
            $tenantId = $user?->currentTenant?->id ?? 'global';
            $userId = $user?->id ?? $request->ip();

            return Limit::perMinute(60)->by("tenant:{$tenantId}:{$userId}");
        });

        // Upload rate limiter
        RateLimiter::for('upload', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(10)->by('upload:'.($user?->id ?? $request->ip()));
        });

        // Search rate limiter
        RateLimiter::for('search', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(30)->by('search:'.($user?->id ?? $request->ip()));
        });

        // Webhook rate limiter
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(100)->by('webhook:'.$request->ip());
        });

        // Analytics event tracking rate limiter
        RateLimiter::for('analytics_events', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(100)->by('analytics:'.($user?->id ?? $request->ip()));
        });

        // Social actions rate limiter (likes, comments, etc.)
        RateLimiter::for('social', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(50)->by('social:'.($user?->id ?? $request->ip()));
        });

        // Messaging rate limiter
        RateLimiter::for('messaging', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(60)->by('messaging:'.($user?->id ?? $request->ip()));
        });

        // Payment rate limiter
        RateLimiter::for('payment', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(20)->by('payment:'.($user?->id ?? $request->ip()));
        });

        // Export rate limiter
        RateLimiter::for('export', function (Request $request) {
            $user = $request->user();

            return Limit::perHour(10)->by('export:'.($user?->id ?? $request->ip()));
        });

        // Admin actions rate limiter
        RateLimiter::for('admin', function (Request $request) {
            $user = $request->user();
            $tenantId = $user?->currentTenant?->id ?? 'global';

            return Limit::perMinute(200)->by("admin:{$tenantId}:".$user?->id);
        });
    }
}
