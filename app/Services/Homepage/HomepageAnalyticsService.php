<?php

declare(strict_types=1);

namespace App\Services\Homepage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Homepage Analytics Service
 *
 * Tracks page views, click events, and user interactions on the homepage.
 * Replaces analytics tracking methods from the monolithic HomepageService.
 */
class HomepageAnalyticsService
{
    /**
     * Track a page view on the homepage
     */
    public function trackPageView(string $page, string $audience, ?int $userId = null): void
    {
        try {
            // Increment page view counter
            $key = "homepage.analytics.pageviews.{$page}.{$audience}";
            $count = Cache::get($key, 0);
            Cache::put($key, $count + 1, 86400); // 24 hours

            // Track unique visitors
            if ($userId) {
                $visitorKey = "homepage.analytics.visitors.{$page}.{$audience}.{$userId}";
                Cache::put($visitorKey, true, 86400);
            }

            // Log for debugging (remove in production)
            Log::debug('Homepage page view tracked', [
                'page' => $page,
                'audience' => $audience,
                'user_id' => $userId,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track homepage page view', [
                'error' => $e->getMessage(),
                'page' => $page,
                'audience' => $audience,
            ]);
        }
    }

    /**
     * Track a click event on the homepage
     */
    public function trackClickEvent(string $element, string $audience, ?int $userId = null): void
    {
        try {
            $key = "homepage.analytics.clicks.{$element}.{$audience}";
            $count = Cache::get($key, 0);
            Cache::put($key, $count + 1, 86400);

            Log::debug('Homepage click event tracked', [
                'element' => $element,
                'audience' => $audience,
                'user_id' => $userId,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track homepage click event', [
                'error' => $e->getMessage(),
                'element' => $element,
            ]);
        }
    }

    /**
     * Get page view statistics
     */
    public function getPageViewStats(string $page, string $audience): array
    {
        $pageViewKey = "homepage.analytics.pageviews.{$page}.{$audience}";
        $pageViews = Cache::get($pageViewKey, 0);

        // Count unique visitors (approximate)
        $visitorPattern = "homepage.analytics.visitors.{$page}.{$audience}.*";
        $visitorKeys = Cache::store('redis')->keys($visitorPattern);
        $uniqueVisitors = count($visitorKeys);

        return [
            'page_views' => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'period' => '24h',
        ];
    }

    /**
     * Get click event statistics
     */
    public function getClickStats(string $audience): array
    {
        $pattern = "homepage.analytics.clicks.*.{$audience}";
        $keys = Cache::store('redis')->keys($pattern);

        $stats = [];
        foreach ($keys as $key) {
            $parts = explode('.', $key);
            $element = $parts[3] ?? 'unknown';
            $count = Cache::get($key, 0);
            $stats[$element] = $count;
        }

        return $stats;
    }
}
