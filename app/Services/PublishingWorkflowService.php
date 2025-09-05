<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * PublishingWorkflowService
 *
 * Handles landing page publishing workflow with comprehensive state management,
 * URL generation, caching, and tenant isolation
 */
class PublishingWorkflowService
{
    /**
     * Publish a landing page
     *
     * @param int $landingPageId
     * @param array $options
     * @return LandingPage
     * @throws ValidationException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function publishLandingPage(int $landingPageId, array $options = []): LandingPage
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        // Validate before publishing
        $this->validateForPublishing($landingPage);

        // Generate URLs if not provided
        $urls = $this->generatePublicUrls($landingPage, $options['custom_domain'] ?? null);

        DB::transaction(function () use ($landingPage, $options, $urls) {
            // Update landing page status
            $landingPage->update([
                'status' => 'published',
                'published_at' => $options['publish_at'] ?? now(),
                'public_url' => $urls['public_url'],
                'version' => $landingPage->version + 1,
                'updated_by' => Auth::id(),
            ]);

            // Update draft hash to reflect published state
            $landingPage->updateDraftHash();

            // Clear any existing cache for this landing page
            $this->clearLandingPageCache($landingPage);

            // Log publishing event
            Log::info('Landing page published', [
                'landing_page_id' => $landingPage->id,
                'published_at' => $landingPage->published_at,
                'public_url' => $landingPage->public_url,
                'tenant_id' => $landingPage->tenant_id,
            ]);

            // Trigger post-publish hooks
            $this->executePostPublishActions($landingPage);
        });

        return $landingPage->fresh();
    }

    /**
     * Unpublish a landing page
     *
     * @param int $landingPageId
     * @param array $options
     * @return LandingPage
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function unpublishLandingPage(int $landingPageId, array $options = []): LandingPage
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        DB::transaction(function () use ($landingPage) {
            $landingPage->update([
                'status' => 'draft',
                'published_at' => null,
                'public_url' => null,
                'updated_by' => Auth::id(),
            ]);

            // Clear cache
            $this->clearLandingPageCache($landingPage);

            // Log unpublishing event
            Log::info('Landing page unpublished', [
                'landing_page_id' => $landingPage->id,
                'tenant_id' => $landingPage->tenant_id,
            ]);
        });

        return $landingPage->fresh();
    }

    /**
     * Archive a landing page
     *
     * @param int $landingPageId
     * @return LandingPage
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function archiveLandingPage(int $landingPageId): LandingPage
    {
        $landingPage = LandingPage::findOrFail($landingPageId);

        DB::transaction(function () use ($landingPage) {
            $landingPage->update([
                'status' => 'archived',
                'published_at' => null,
                'public_url' => null,
                'updated_by' => Auth::id(),
            ]);

            // Clear all caches
            $this->clearLandingPageCache($landingPage);

            Log::info('Landing page archived', [
                'landing_page_id' => $landingPage->id,
                'tenant_id' => $landingPage->tenant_id,
            ]);
        });

        return $landingPage->fresh();
    }

    /**
     * Get published landing page for serving
     *
     * @param string $slug
     * @param int|null $tenantId
     * @return LandingPage|null
     */
    public function getPublishedLandingPage(string $slug, ?int $tenantId = null): ?LandingPage
    {
        $cacheKey = "published_landing_page:{$tenantId}:{$slug}";

        return Cache::remember($cacheKey, 3600, function () use ($slug, $tenantId) {
            $query = LandingPage::where('slug', $slug)
                ->where('status', 'published')
                ->where('published_at', '<=', now());

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            return $query->with(['template', 'tenant'])->first();
        });
    }

    /**
     * Get cached published landing page content
     *
     * @param LandingPage $landingPage
     * @return array
     */
    public function getCachedLandingPageContent(LandingPage $landingPage): array
    {
        $cacheKey = "landing_page_content:{$landingPage->id}:{$landingPage->version}";
        $cacheTtl = config('cache.landing_page_ttl', 3600); // 1 hour default

        return Cache::remember($cacheKey, $cacheTtl, function () use ($landingPage) {
            return [
                'id' => $landingPage->id,
                'name' => $landingPage->name,
                'slug' => $landingPage->slug,
                'description' => $landingPage->description,
                'config' => $landingPage->getEffectiveConfig(),
                'brand_config' => $landingPage->brand_config,
                'seo_title' => $landingPage->seo_title ?: $landingPage->name,
                'seo_description' => $landingPage->seo_description ?: $landingPage->description,
                'seo_keywords' => $landingPage->seo_keywords,
                'social_image' => $landingPage->social_image,
                'tracking_id' => $landingPage->tracking_id,
                'favicon_url' => $landingPage->favicon_url,
                'custom_css' => $landingPage->custom_css,
                'custom_js' => $landingPage->custom_js,
                'template_structure' => $landingPage->template?->getEffectiveStructure(),
                'published_at' => $landingPage->published_at,
                'version' => $landingPage->version,
                'cache_timestamp' => now()->timestamp,
            ];
        });
    }

    /**
     * Validate landing page before publishing
     *
     * @param LandingPage $landingPage
     * @throws ValidationException
     */
    private function validateForPublishing(LandingPage $landingPage): void
    {
        $errors = [];

        if (empty(trim($landingPage->name))) {
            $errors['name'] = ['Landing page name is required'];
        }

        if (empty($landingPage->config) && empty($landingPage->template)) {
            $errors['content'] = ['Landing page must have content or template'];
        }

        if ($landingPage->status === 'published') {
            $errors['status'] = ['Landing page is already published'];
        }

        if (empty($landingPage->slug)) {
            $errors['slug'] = ['Landing page slug is required'];
        }

        // Check for duplicate published slugs within the same tenant
        if ($this->slugExistsForPublishedPages($landingPage->slug, $landingPage->tenant_id, $landingPage->id)) {
            $errors['slug'] = ['This slug is already in use by another published landing page'];
        }

        // Validate template exists and is published
        if ($landingPage->template_id && (!$landingPage->template || $landingPage->template->status !== 'published')) {
            $errors['template'] = ['Template must exist and be published'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Generate public URLs for landing page
     *
     * @param LandingPage $landingPage
     * @param string|null $customDomain
     * @return array
     */
    private function generatePublicUrls(LandingPage $landingPage, ?string $customDomain = null): array
    {
        $tenant = $landingPage->tenant;

        // If custom domain is provided
        if ($customDomain) {
            $publicUrl = "https://{$customDomain}/{$landingPage->slug}";
        }
        // If tenant has custom domain configured
        elseif ($tenant && config('database.multi_tenant') && tenant() && tenant()->custom_domain) {
            $publicUrl = "https://{$tenant->custom_domain}/{$landingPage->slug}";
        }
        // If subdomain isolation is enabled (default tenant domain)
        elseif ($tenant && config('database.multi_tenant')) {
            $defaultDomain = config('app.url');
            $domain = parse_url($defaultDomain, PHP_URL_HOST);
            $publicUrl = "https://{$landingPage->slug}.{$domain}";
        }
        // Fallback to path-based URL
        else {
            $baseUrl = config('app.url');
            $publicUrl = "{$baseUrl}/p/{$landingPage->slug}";
        }

        return [
            'public_url' => $publicUrl,
            'preview_url' => $publicUrl . '?preview=1',
        ];
    }

    /**
     * Check if slug exists for other published pages
     *
     * @param string $slug
     * @param int|null $tenantId
     * @param int|null $excludeId
     * @return bool
     */
    private function slugExistsForPublishedPages(string $slug, ?int $tenantId = null, ?int $excludeId = null): bool
    {
        $query = LandingPage::where('slug', $slug)
            ->where('status', 'published');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Clear all caches for a landing page
     *
     * @param LandingPage $landingPage
     */
    private function clearLandingPageCache(LandingPage $landingPage): void
    {
        $cacheKeys = [
            "published_landing_page:{$landingPage->tenant_id}:{$landingPage->slug}",
            "landing_page_content:{$landingPage->id}:{$landingPage->version}",
            "landing_page_content:{$landingPage->id}:" . ($landingPage->version - 1), // Previous version
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }

        // Clear tenant-specific cache tags if available
        if ($landingPage->tenant_id) {
            Cache::tags(["tenant:{$landingPage->tenant_id}"])->flush();
        }

        // Clear general landing page cache tags
        Cache::tags(['landing_pages'])->flush();
    }

    /**
     * Execute post-publish actions
     *
     * @param LandingPage $landingPage
     */
    private function executePostPublishActions(LandingPage $landingPage): void
    {
        // Invalidate CDN cache if CDN integration is enabled
        if (config('cache.cdn_enabled')) {
            $this->invalidateCdnCache($landingPage);
        }

        // Send notifications to relevant stakeholders
        $this->sendPublishNotifications($landingPage);

        // Update landing page metrics
        $this->updatePublishMetrics($landingPage);
    }

    /**
     * Invalidate CDN cache
     *
     * @param LandingPage $landingPage
     */
    private function invalidateCdnCache(LandingPage $landingPage): void
    {
        try {
            // Implementation would depend on CDN provider (CloudFlare, AWS CloudFront, etc.)
            // For now, log the intent
            Log::info('CDN cache invalidation requested', [
                'landing_page_id' => $landingPage->id,
                'public_url' => $landingPage->public_url,
            ]);
        } catch (\Exception $e) {
            Log::warning('CDN cache invalidation failed', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send publish notifications
     *
     * @param LandingPage $landingPage
     */
    private function sendPublishNotifications(LandingPage $landingPage): void
    {
        // Implementation would send notifications to:
        // - Landing page creator
        // - Admin users for the tenant
        // - Stakeholders based on notification preferences

        Log::info('Publish notification triggered', [
            'landing_page_id' => $landingPage->id,
            'creator_id' => $landingPage->created_by,
            'tenant_id' => $landingPage->tenant_id,
        ]);
    }

    /**
     * Update publish metrics
     *
     * @param LandingPage $landingPage
     */
    private function updatePublishMetrics(LandingPage $landingPage): void
    {
        // Track publication metrics
        // Implementation would update metrics like:
        // - Total published pages for tenant
        // - Publication frequency
        // - Template usage stats

        $landingPage->increment('usage_count');
    }

    /**
     * Get landing page performance statistics
     *
     * @param LandingPage $landingPage
     * @return array
     */
    public function getLandingPagePerformance(LandingPage $landingPage): array
    {
        $cacheKey = "landing_page_performance:{$landingPage->id}";
        $cacheTtl = 1800; // 30 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($landingPage) {
            return [
                'page_views' => $landingPage->analytics()
                    ->where('event_type', 'page_view')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'unique_visitors' => $landingPage->analytics()
                    ->where('event_type', 'page_view')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->distinct('session_id')
                    ->count(),
                'conversion_count' => $landingPage->submissions()
                    ->where('created_at', '>=', now()->subDays(30))
                    ->count(),
                'average_session_duration' => $this->calculateAverageSessionDuration($landingPage),
                'bounce_rate' => $this->calculateBounceRate($landingPage),
                'load_time' => $this->getAverageLoadTime($landingPage),
                'last_updated' => now(),
            ];
        });
    }

    /**
     * Calculate average session duration
     *
     * @param LandingPage $landingPage
     * @return float
     */
    public function calculateAverageSessionDuration(LandingPage $landingPage): float
    {
        $analytics = $landingPage->analytics()
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy('session_id');

        if ($analytics->isEmpty()) {
            return 0;
        }

        $totalDuration = 0;
        foreach ($analytics as $sessionEvents) {
            $firstVisit = $sessionEvents->min('created_at');
            $lastVisit = $sessionEvents->max('created_at');

            if ($firstVisit && $lastVisit) {
                $totalDuration += $firstVisit->diffInSeconds($lastVisit);
            }
        }

        return round($totalDuration / $analytics->count(), 2);
    }

    /**
     * Calculate bounce rate
     *
     * @param LandingPage $landingPage
     * @return float
     */
    public function calculateBounceRate(LandingPage $landingPage): float
    {
        $pageViews = $landingPage->analytics()
            ->where('event_type', 'page_view')
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $sessions = $pageViews->unique('session_id');

        if ($sessions->count() === 0) {
            return 0;
        }

        $bouncedSessions = $sessions->filter(function ($session) use ($pageViews) {
            $sessionViews = $pageViews->where('session_id', $session->session_id)->count();
            return $sessionViews === 1; // Only one page view = bounce
        })->count();

        return round(($bouncedSessions / $sessions->count()) * 100, 2);
    }

    /**
     * Get average load time
     *
     * @param LandingPage $landingPage
     * @return float
     */
    private function getAverageLoadTime(LandingPage $landingPage): float
    {
        // Implementation would track page load times
        // For now, return a default value
        return 1.5; // seconds
    }

    /**
     * Bulk publish multiple landing pages
     *
     * @param array $landingPageIds
     * @param array $options
     * @return array
     */
    public function bulkPublish(array $landingPageIds, array $options = []): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'errors' => [],
        ];

        foreach ($landingPageIds as $id) {
            try {
                $landingPage = $this->publishLandingPage($id, $options);
                $results['successful'][] = $landingPage->id;
            } catch (\Exception $e) {
                $results['failed'][] = $id;
                $results['errors'][$id] = $e->getMessage();
            }
        }

        Log::info('Bulk landing page publishing completed', [
            'total' => count($landingPageIds),
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'tenant_id' => $options['tenant_id'] ?? null,
        ]);

        return $results;
    }

    /**
     * Bulk unpublish multiple landing pages
     *
     * @param array $landingPageIds
     * @param array $options
     * @return array
     */
    public function bulkUnpublish(array $landingPageIds, array $options = []): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'errors' => [],
        ];

        foreach ($landingPageIds as $id) {
            try {
                $landingPage = $this->unpublishLandingPage($id, $options);
                $results['successful'][] = $landingPage->id;
            } catch (\Exception $e) {
                $results['failed'][] = $id;
                $results['errors'][$id] = $e->getMessage();
            }
        }

        Log::info('Bulk landing page unpublishing completed', [
            'total' => count($landingPageIds),
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'tenant_id' => $options['tenant_id'] ?? null,
        ]);

        return $results;
    }
}