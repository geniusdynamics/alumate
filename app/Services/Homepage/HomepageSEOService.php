<?php

declare(strict_types=1);

namespace App\Services\Homepage;

use Illuminate\Support\Facades\Cache;

/**
 * Homepage SEO Service
 *
 * Manages SEO metadata, social sharing tags, and structured data for the homepage.
 * Replaces the getSEOMetadata() method from the monolithic HomepageService.
 */
class HomepageSEOService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get SEO metadata for the homepage based on audience
     */
    public function getSEOMetadata(string $audience): array
    {
        return Cache::remember("homepage.seo.{$audience}", self::CACHE_TTL, function () use ($audience) {
            $baseMeta = [
                'title' => 'Alumate - Connect, Grow, Succeed Together',
                'description' => 'The all-in-one alumni engagement platform that connects graduates, institutions, and employers for lifelong success.',
                'keywords' => ['alumni', 'networking', 'career', 'mentorship', 'engagement'],
                'canonical' => url('/'),
                'robots' => 'index, follow',
            ];

            if ($audience === 'institutional') {
                return array_merge($baseMeta, [
                    'title' => 'Alumate for Institutions - Transform Alumni Engagement',
                    'description' => 'Boost alumni engagement by 400% with our branded mobile app platform. Trusted by 150+ institutions worldwide.',
                    'keywords' => ['alumni engagement', 'institutional platform', 'branded app', 'alumni relations'],
                    'og_type' => 'website',
                    'structured_data' => $this->getInstitutionalStructuredData(),
                ]);
            }

            if ($audience === 'employer') {
                return array_merge($baseMeta, [
                    'title' => 'Alumate for Employers - Find Top Talent',
                    'description' => 'Connect with qualified alumni from top institutions. Streamline your hiring process with our talent network.',
                    'keywords' => ['hiring', 'talent acquisition', 'alumni network', 'recruitment'],
                    'og_type' => 'website',
                    'structured_data' => $this->getEmployerStructuredData(),
                ]);
            }

            return array_merge($baseMeta, [
                'og_type' => 'website',
                'structured_data' => $this->getGeneralStructuredData(),
            ]);
        });
    }

    /**
     * Get social sharing metadata
     */
    public function getSocialSharingTags(string $audience): array
    {
        $baseTags = [
            'og:title' => 'Alumate - Connect, Grow, Succeed Together',
            'og:description' => 'The all-in-one alumni engagement platform.',
            'og:image' => asset('/images/og-image.jpg'),
            'og:url' => url('/'),
            'twitter:card' => 'summary_large_image',
            'twitter:title' => 'Alumate - Connect, Grow, Succeed Together',
            'twitter:description' => 'The all-in-one alumni engagement platform.',
            'twitter:image' => asset('/images/twitter-card.jpg'),
        ];

        if ($audience === 'institutional') {
            $baseTags['og:title'] = 'Alumate for Institutions - Transform Alumni Engagement';
            $baseTags['og:description'] = 'Boost alumni engagement by 400% with our branded mobile app platform.';
            $baseTags['og:image'] = asset('/images/og-institutional.jpg');
            $baseTags['twitter:title'] = 'Alumate for Institutions - Transform Alumni Engagement';
            $baseTags['twitter:description'] = 'Boost alumni engagement by 400% with our branded mobile app platform.';
            $baseTags['twitter:image'] = asset('/images/twitter-institutional.jpg');
        }

        return $baseTags;
    }

    /**
     * Get structured data for search engines
     */
    private function getGeneralStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Alumate',
            'url' => url('/'),
            'logo' => asset('/images/logo.png'),
            'description' => 'The all-in-one alumni engagement platform.',
            'sameAs' => [
                'https://twitter.com/alumate',
                'https://linkedin.com/company/alumate',
                'https://facebook.com/alumate',
            ],
        ];
    }

    /**
     * Get institutional structured data
     */
    private function getInstitutionalStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'SoftwareApplication',
            'name' => 'Alumate for Institutions',
            'applicationCategory' => 'BusinessApplication',
            'description' => 'Boost alumni engagement by 400% with our branded mobile app platform.',
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'description' => 'Contact for pricing',
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '150',
            ],
        ];
    }

    /**
     * Get employer structured data
     */
    private function getEmployerStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'EmployerAggregateRating',
            'name' => 'Alumate for Employers',
            'description' => 'Connect with qualified alumni from top institutions.',
        ];
    }
}
