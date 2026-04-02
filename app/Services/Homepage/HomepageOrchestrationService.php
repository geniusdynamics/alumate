<?php

declare(strict_types=1);

namespace App\Services\Homepage;

/**
 * Homepage Orchestration Service
 *
 * Coordinates all homepage services to render complete homepage data.
 * This is the single entry point for homepage data, replacing the monolithic HomepageService.
 *
 * Usage:
 *   $service = app(HomepageOrchestrationService::class);
 *   $data = $service->getHomepageData('general');
 */
class HomepageOrchestrationService
{
    public function __construct(
        private HomepageStatisticsService $statistics,
        private HomepageTestimonialService $testimonials,
        private HomepageContentService $content,
        private HomepageSEOService $seo,
        private HomepageABTestingService $abTesting,
        private HomepageAnalyticsService $analytics,
    ) {}

    /**
     * Get complete homepage data
     */
    public function getHomepageData(string $audience = 'general', ?int $userId = null): array
    {
        // Track page view
        $this->analytics->trackPageView('homepage', $audience);

        // Get A/B test variant
        $abVariant = $this->abTesting->assignUserToVariant('homepage_test', $userId);

        // Gather all homepage data
        return [
            'statistics' => $this->statistics->getPlatformStatistics($audience),
            'testimonials' => $this->testimonials->getTestimonials($audience),
            'success_stories' => $this->testimonials->getSuccessStories(),
            'content_blocks' => $this->content->getContentBlocks($audience, $abVariant),
            'navigation' => $this->content->getNavigationItems($audience),
            'footer_links' => $this->content->getFooterLinks(),
            'featured_content' => $this->content->getFeaturedContent($audience),
            'seo' => $this->seo->getSEOMetadata($audience),
            'social_tags' => $this->seo->getSocialSharingTags($audience),
            'ab_variant' => $abVariant,
            'ab_test_results' => $this->abTesting->getTestResults('homepage_test'),
        ];
    }

    /**
     * Get lightweight homepage data (for initial page load)
     */
    public function getHomepageDataLite(string $audience = 'general'): array
    {
        return [
            'statistics' => $this->statistics->getPlatformStatistics($audience),
            'navigation' => $this->content->getNavigationItems($audience),
            'seo' => $this->seo->getSEOMetadata($audience),
        ];
    }
}
