<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PersonalizationService
{
    private HomepageService $homepageService;

    public function __construct(HomepageService $homepageService)
    {
        $this->homepageService = $homepageService;
    }

    /**
     * Detect audience based on various signals
     */
    public function detectAudience(Request $request): array
    {
        $factors = [];
        $institutionalScore = 0;
        $totalWeight = 0;

        // Check URL parameters
        if ($request->has('audience')) {
            $audienceParam = $request->get('audience');
            if (in_array($audienceParam, ['institutional', 'admin'])) {
                $factors[] = [
                    'type' => 'url_param',
                    'value' => $audienceParam,
                    'weight' => 0.8,
                    'contribution' => 0.8,
                ];
                $institutionalScore += 0.8;
                $totalWeight += 0.8;
            } elseif (! empty($audienceParam) && ! in_array($audienceParam, ['individual', 'institutional', 'admin'])) {
                // Log malformed audience parameter as warning
                logger()->warning('Invalid audience parameter in request', [
                    'audience_param' => $audienceParam,
                    'user_agent' => $request->userAgent(),
                    'referrer' => $request->header('referer'),
                    'ip' => $request->ip(),
                ]);
            }
        }

        // Check referrer
        $referrer = $request->header('referer');
        if ($referrer) {
            $referrerHost = parse_url($referrer, PHP_URL_HOST);

            // Validate parsed referrer host
            if ($referrerHost === false || $referrerHost === null) {
                logger()->warning('Malformed referrer URL in audience detection', [
                    'referrer' => $referrer,
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                ]);
                $referrerHost = ''; // Set to empty to avoid further processing
            }

            $institutionalDomains = ['.edu', '.ac.', 'university', 'college', 'admin'];

            $isInstitutional = false;
            if (! empty($referrerHost)) {
                foreach ($institutionalDomains as $domain) {
                    if (str_contains($referrerHost, $domain)) {
                        $isInstitutional = true;
                        break;
                    }
                }
            }

            if ($isInstitutional) {
                $factors[] = [
                    'type' => 'referrer',
                    'value' => $referrerHost,
                    'weight' => 0.6,
                    'contribution' => 0.6,
                ];
                $institutionalScore += 0.6;
                $totalWeight += 0.6;
            }
        }

        // Check user agent for admin tools or institutional software
        $userAgent = $request->userAgent();
        $adminIndicators = ['admin', 'dashboard', 'management', 'institutional'];
        foreach ($adminIndicators as $indicator) {
            if (str_contains(strtolower($userAgent), $indicator)) {
                $factors[] = [
                    'type' => 'user_agent',
                    'value' => $indicator,
                    'weight' => 0.3,
                    'contribution' => 0.3,
                ];
                $institutionalScore += 0.3;
                $totalWeight += 0.3;
                break;
            }
        }

        // Check UTM parameters
        if ($request->has('utm_source')) {
            $utmSource = $request->get('utm_source');
            $institutionalSources = ['university', 'college', 'institution', 'admin', 'conference'];

            if (in_array($utmSource, $institutionalSources)) {
                $factors[] = [
                    'type' => 'utm_source',
                    'value' => $utmSource,
                    'weight' => 0.5,
                    'contribution' => 0.5,
                ];
                $institutionalScore += 0.5;
                $totalWeight += 0.5;
            }
        }

        // Calculate confidence and determine audience
        $confidence = $totalWeight > 0 ? min($institutionalScore / $totalWeight, 1) : 0;
        $detectedAudience = $confidence > 0.5 ? 'institutional' : 'individual';

        return [
            'detected_audience' => $detectedAudience,
            'confidence' => $confidence,
            'factors' => $factors,
            'fallback' => 'individual',
        ];
    }

    /**
     * Get personalized content based on audience and context
     */
    public function getPersonalizedContent(string $audience, Request $request): array
    {
        // Validate audience parameter
        if (! in_array($audience, ['individual', 'institutional'])) {
            logger()->warning('Invalid audience type in personalization request', [
                'audience' => $audience,
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ]);
            $audience = 'individual'; // Default to individual for invalid audience
        }

        $context = $this->buildContext($request);
        $cacheKey = $this->generateCacheKey($audience, $context);

        return Cache::remember($cacheKey, 1800, function () use ($audience, $context) {
            try {
                $content = $this->homepageService->getPersonalizedContent($audience, $context);

                // Apply additional personalization layers
                $content = $this->applyGeographicPersonalization($content, $context);
                $content = $this->applyTimeBasedPersonalization($content, $context);
                $content = $this->applyBehavioralPersonalization($content, $context);

                return $content;
            } catch (\Exception $e) {
                logger()->error('Personalized content service call failed', [
                    'error' => $e->getMessage(),
                    'audience' => $audience,
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return default content structure for graceful degradation
                return $this->getDefaultContent($audience);
            }
        });
    }

    /**
     * Store audience preference in session
     */
    public function storeAudiencePreference(string $audience, string $source = 'manual'): array
    {
        $preference = [
            'type' => $audience,
            'timestamp' => now()->toISOString(),
            'source' => $source,
            'session_id' => session()->getId(),
        ];

        session(['homepage_audience_preference' => $preference]);

        // Track the preference change
        $this->homepageService->trackPersonalizationEvent($audience, 'audience_preference_stored', [
            'source' => $source,
            'session_id' => session()->getId(),
        ]);

        return $preference;
    }

    /**
     * Get stored audience preference from session
     */
    public function getStoredAudiencePreference(): ?array
    {
        return session('homepage_audience_preference');
    }

    /**
     * Build context from request
     */
    private function buildContext(Request $request): array
    {
        return [
            'referrer' => $request->header('referer'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_content' => $request->get('utm_content'),
            'utm_term' => $request->get('utm_term'),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
            'locale' => app()->getLocale(),
            'timezone' => $request->header('X-Timezone', 'UTC'),
        ];
    }

    /**
     * Generate cache key for personalized content
     */
    private function generateCacheKey(string $audience, array $context): string
    {
        $keyData = [
            'audience' => $audience,
            'utm_campaign' => $context['utm_campaign'] ?? null,
            'locale' => $context['locale'] ?? 'en',
            'hour' => now()->hour, // Cache varies by hour for time-based personalization
        ];

        return 'homepage_personalized_'.md5(serialize($keyData));
    }

    /**
     * Apply geographic personalization
     */
    private function applyGeographicPersonalization(array $content, array $context): array
    {
        // This would integrate with a geolocation service
        // For now, we'll use a simple IP-based approach

        $timezone = $context['timezone'] ?? 'UTC';

        // Adjust content based on timezone/region
        if (str_contains($timezone, 'America')) {
            // North American specific content adjustments
            if (isset($content['hero']['description'])) {
                $content['hero']['description'] = str_replace(
                    'professional networking',
                    'career networking',
                    $content['hero']['description']
                );
            }
        } elseif (str_contains($timezone, 'Europe')) {
            // European specific content adjustments
            if (isset($content['pricing']['tiers'])) {
                foreach ($content['pricing']['tiers'] as &$tier) {
                    if (isset($tier['price']) && $tier['price'] > 0) {
                        $tier['price_eur'] = round($tier['price'] * 0.85); // Rough USD to EUR conversion
                    }
                }
            }
        }

        return $content;
    }

    /**
     * Apply time-based personalization
     */
    private function applyTimeBasedPersonalization(array $content, array $context): array
    {
        $hour = now()->hour;

        // Adjust messaging based on time of day
        if ($hour >= 9 && $hour <= 17) {
            // Business hours - professional focus
            if (isset($content['hero']['subtitle'])) {
                $content['hero']['subtitle'] = str_replace(
                    'Join thousands',
                    'Join thousands of professionals',
                    $content['hero']['subtitle']
                );
            }
        } elseif ($hour >= 18 && $hour <= 22) {
            // Evening - career development focus
            if (isset($content['hero']['subtitle'])) {
                $content['hero']['subtitle'] = str_replace(
                    'advancing their careers',
                    'building their careers after hours',
                    $content['hero']['subtitle']
                );
            }
        }

        return $content;
    }

    /**
     * Apply behavioral personalization based on session data
     */
    private function applyBehavioralPersonalization(array $content, array $context): array
    {
        $sessionData = session()->all();

        // Check if user has visited specific pages before
        $visitedPages = $sessionData['visited_pages'] ?? [];

        if (in_array('/jobs', $visitedPages)) {
            // User is interested in jobs - emphasize job-related features
            if (isset($content['features']['items'])) {
                foreach ($content['features']['items'] as &$feature) {
                    if ($feature['id'] === 'networking') {
                        $feature['title'] = 'Job-Focused Alumni Networking';
                        $feature['description'] = 'Connect with alumni who can help you find your next career opportunity.';
                    }
                }
            }
        }

        if (in_array('/mentorship', $visitedPages)) {
            // User is interested in mentorship - emphasize mentorship features
            if (isset($content['cta']['primary']['text'])) {
                $content['cta']['primary']['text'] = 'Find Your Mentor Today';
            }
        }

        return $content;
    }

    /**
     * Get A/B test variant for user
     */
    public function getABTestVariant(string $testId, ?string $userId = null): ?array
    {
        if (! $userId) {
            $userId = session()->getId();
        }

        // Simple hash-based assignment for consistent variants
        $hash = crc32($testId.$userId);
        $variants = $this->homepageService->getContentVariations('individual', $testId);

        if (empty($variants)) {
            return null;
        }

        $variantKeys = array_keys($variants);
        $variantIndex = abs($hash) % count($variantKeys);
        $selectedVariant = $variantKeys[$variantIndex];

        return [
            'test_id' => $testId,
            'variant_id' => $selectedVariant,
            'variant_data' => $variants[$selectedVariant],
            'user_id' => $userId,
        ];
    }

    /**
     * Track A/B test conversion
     */
    public function trackABTestConversion(string $testId, string $variantId, string $goal): void
    {
        $this->homepageService->trackPersonalizationEvent('ab_test', 'conversion', [
            'test_id' => $testId,
            'variant_id' => $variantId,
            'goal' => $goal,
            'user_id' => session()->getId(),
        ]);
    }

    /**
     * Clear personalization cache
     */
    public function clearPersonalizationCache(?string $audience = null): void
    {
        if ($audience) {
            Cache::forget("homepage_content_{$audience}_*");
        } else {
            Cache::flush(); // Clear all cache - use with caution
        }
    }

    /**
     * Get personalization analytics
     */
    public function getPersonalizationAnalytics(array $filters = []): array
    {
        // This would integrate with your analytics system
        // For now, return mock data
        return [
            'audience_distribution' => [
                'individual' => 75,
                'institutional' => 25,
            ],
            'detection_accuracy' => 89,
            'conversion_rates' => [
                'individual' => [
                    'trial_signup' => 12.5,
                    'waitlist_join' => 8.3,
                ],
                'institutional' => [
                    'demo_request' => 18.7,
                    'case_study_download' => 24.1,
                ],
            ],
            'ab_test_results' => [
                'hero_message_test' => [
                    'control' => ['conversion_rate' => 10.2, 'sample_size' => 1000],
                    'variant_a' => ['conversion_rate' => 12.8, 'sample_size' => 1000],
                    'variant_b' => ['conversion_rate' => 11.5, 'sample_size' => 1000],
                ],
            ],
        ];
    }

    /**
     * Get default content structure for graceful degradation
     */
    private function getDefaultContent(string $audience): array
    {
        $baseContent = [
            'hero' => [
                'title' => $audience === 'institutional' ? 'Alumni Engagement Platform' : 'Connect with Alumni',
                'subtitle' => $audience === 'institutional' ? 'Build stronger alumni communities' : 'Advance your career through networking',
                'cta' => $audience === 'institutional' ? 'Request Demo' : 'Join Network',
            ],
            'features' => [],
            'testimonials' => [],
            'pricing' => [],
            'meta' => [
                'audience' => $audience,
                'fallback' => true,
                'generated_at' => now()->toISOString(),
            ],
        ];

        return $baseContent;
    }
}
