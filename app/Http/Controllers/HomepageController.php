<?php

// ABOUTME: Main homepage controller for individual and institutional audiences with personalized content delivery
// ABOUTME: Handles personalization service integration, A/B testing variants, and audience-specific content routing

namespace App\Http\Controllers;

use App\Services\ABTestingService;
use App\Services\HomepageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomepageController extends Controller
{
    public function __construct(
        private HomepageService $homepageService,
        private ABTestingService $abTestingService
    ) {}

    /**
     * Display the main homepage for individual alumni
     */
    public function index(Request $request)
    {
        $audience = 'individual';
        $userId = $this->getUserId($request);

        // Build context for personalization
        $context = $this->buildContext($request);

        // Get personalized content; if not array, replace with default content
        $content = $this->homepageService->getPersonalizedContent($audience, $context);
        if (! is_array($content)) {
            $content = $this->getDefaultContent();
        }

        // Ensure content has required structure with safe defaults
        if (! is_array($content) || empty($content)) {
            $content = $this->getDefaultContent();
        }

        // Ensure all required keys exist with safe defaults
        if (! isset($content['hero']) || ! is_array($content['hero'])) {
            $content['hero'] = ['headline' => '', 'subtitle' => ''];
        }
        if (! isset($content['cta']) || ! is_array($content['cta'])) {
            $content['cta'] = ['primary' => ['text' => ''], 'secondary' => ['text' => '']];
        }
        if (! isset($content['sections'])) {
            $content['sections'] = [];
        }

        // Fetch A/B tests; if not array, treat as []
        $abTests = $this->abTestingService->getActiveTests($userId, $audience);
        if (! is_array($abTests)) {
            $abTests = [];
        }

        // Pass content through applyABTestVariants only if abTests is non-empty array
        if (! empty($abTests) && is_array($abTests)) {
            $content = $this->homepageService->applyABTestVariants($content, $abTests, $audience);
        }

        // When tracking events, wrap in try/catch and log warnings
        try {
            $this->homepageService->trackPersonalizationEvent($audience, 'page_view', [
                'user_id' => $userId,
                'context' => $context,
                'ab_tests' => is_array($abTests) ? array_keys($abTests) : [],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track personalization event', [
                'error' => $e->getMessage(),
                'audience' => $audience,
                'user_id' => $userId,
            ]);
        }

        return Inertia::render('Homepage/Index', [
            'audience' => $audience,
            'content' => $content,
            'abTests' => $this->formatABTestsForFrontend($abTests),
            'userId' => $userId,
            'meta' => $this->getMetaData($content, $request),
        ]);
    }

    /**
     * Display the institutional homepage for administrators
     */
    public function institutional(Request $request)
    {
        $audience = 'institutional';
        $userId = $this->getUserId($request);

        // Build context for personalization
        $context = $this->buildContext($request);

        // Get personalized content; if not array, replace with default content
        $content = $this->homepageService->getPersonalizedContent($audience, $context);
        if (! is_array($content)) {
            $content = $this->getDefaultContent();
        }

        // Ensure content has required structure with safe defaults
        if (! is_array($content) || empty($content)) {
            $content = $this->getDefaultContent();
        }

        // Ensure all required keys exist with safe defaults
        if (! isset($content['hero']) || ! is_array($content['hero'])) {
            $content['hero'] = ['headline' => '', 'subtitle' => ''];
        }
        if (! isset($content['cta']) || ! is_array($content['cta'])) {
            $content['cta'] = ['primary' => ['text' => ''], 'secondary' => ['text' => '']];
        }
        if (! isset($content['sections'])) {
            $content['sections'] = [];
        }

        // Fetch A/B tests; if not array, treat as []
        $abTests = $this->abTestingService->getActiveTests($userId, $audience);
        if (! is_array($abTests)) {
            $abTests = [];
        }

        // Pass content through applyABTestVariants only if abTests is non-empty array
        if (! empty($abTests) && is_array($abTests)) {
            $content = $this->homepageService->applyABTestVariants($content, $abTests, $audience);
        }

        // When tracking events, wrap in try/catch and log warnings
        try {
            $this->homepageService->trackPersonalizationEvent($audience, 'page_view', [
                'user_id' => $userId,
                'context' => $context,
                'ab_tests' => is_array($abTests) ? array_keys($abTests) : [],
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track personalization event', [
                'error' => $e->getMessage(),
                'audience' => $audience,
                'user_id' => $userId,
            ]);
        }

        return Inertia::render('Homepage/Index', [
            'audience' => $audience,
            'content' => $content,
            'abTests' => $this->formatABTestsForFrontend($abTests),
            'userId' => $userId,
            'meta' => $this->getMetaData($content, $request),
        ]);
    }

    /**
     * Handle CTA clicks and track conversions
     */
    public function trackCTAClick(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string',
            'section' => 'required|string',
            'audience' => 'required|in:individual,institutional',
            'additional_data' => 'array',
            'ab_tests' => 'array',
        ]);

        // Normalize ab_tests to [] if missing or invalid
        $abTests = $validated['ab_tests'] ?? [];
        if (! is_array($abTests)) {
            $abTests = [];
        }

        $userId = $this->getUserId($request);

        // Track the CTA click event - wrap in try/catch
        try {
            $this->homepageService->trackPersonalizationEvent(
                $validated['audience'],
                'cta_click',
                [
                    'user_id' => $userId,
                    'action' => $validated['action'],
                    'section' => $validated['section'],
                    'additional_data' => $validated['additional_data'] ?? [],
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to track CTA click event', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'action' => $validated['action'],
            ]);
        }

        // Track A/B test conversions with enhanced validation and error handling
        if (is_array($abTests) && ! empty($abTests)) {
            foreach ($abTests as $testId => $variantId) {
                // Only process if both testId and variantId are scalar strings
                if (! is_scalar($testId) || ! is_string($testId) || ! is_scalar($variantId) || ! is_string($variantId)) {
                    Log::warning('Skipping invalid A/B test data - keys/values must be scalar strings', [
                        'test_id' => $testId,
                        'variant_id' => $variantId,
                        'test_id_type' => gettype($testId),
                        'variant_id_type' => gettype($variantId),
                        'user_id' => $userId,
                    ]);

                    continue;
                }

                // Guard ABTrackingService::trackConversion calls within try/catch with context
                try {
                    $this->abTestingService->trackConversion(
                        $testId,
                        $variantId,
                        'hero_cta_click',
                        $userId,
                        [
                            'action' => $validated['action'],
                            'section' => $validated['section'],
                            'audience' => $validated['audience'],
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Failed to track A/B test conversion', [
                        'error' => $e->getMessage(),
                        'test_id' => $testId,
                        'variant_id' => $variantId,
                        'user_id' => $userId,
                        'audience' => $validated['audience'],
                        'section' => $validated['section'],
                        'action' => $validated['action'],
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'CTA click tracked successfully',
        ]);
    }

    /**
     * Handle conversion tracking for A/B tests
     */
    public function trackConversion(Request $request)
    {
        $validated = $request->validate([
            'test_id' => 'required|string',
            'variant_id' => 'required|string',
            'goal' => 'required|string',
            'audience' => 'required|in:individual,institutional',
            'additional_data' => 'array',
        ]);

        // Validate strings are non-empty after trimming
        $testId = trim($validated['test_id']);
        $variantId = trim($validated['variant_id']);
        $goal = trim($validated['goal']);

        if (empty($testId) || empty($variantId) || empty($goal)) {
            return response()->json([
                'success' => false,
                'message' => 'test_id, variant_id, and goal cannot be empty',
            ], 400);
        }

        $userId = $this->getUserId($request);

        // Guard service call in try/catch and log on failure
        try {
            $this->abTestingService->trackConversion(
                $testId,
                $variantId,
                $goal,
                $userId,
                array_merge($validated['additional_data'] ?? [], [
                    'audience' => $validated['audience'],
                ])
            );
        } catch (\Exception $e) {
            Log::warning('Failed to track conversion in trackConversion endpoint', [
                'error' => $e->getMessage(),
                'test_id' => $testId,
                'variant_id' => $variantId,
                'goal' => $goal,
                'user_id' => $userId,
                'audience' => $validated['audience'],
            ]);
        }

        // Ensure responses remain JSON success irrespective of telemetry errors, for stability
        return response()->json([
            'success' => true,
            'message' => 'Conversion tracked successfully',
        ]);
    }

    /**
     * Get A/B test results (admin only)
     */
    public function getABTestResults(Request $request, string $testId)
    {
        // In production, add proper authorization
        $results = $this->abTestingService->getTestResults($testId);

        return response()->json($results);
    }

    /**
     * Get or generate user ID for tracking
     */
    private function getUserId(Request $request): string
    {
        // Use authenticated user ID if available
        if (auth()->check()) {
            return 'user_'.auth()->id();
        }

        // Use session ID for anonymous users
        if ($request->session()->has('tracking_user_id')) {
            return $request->session()->get('tracking_user_id');
        }

        // Generate new tracking ID
        $trackingId = 'anon_'.Str::random(16);
        $request->session()->put('tracking_user_id', $trackingId);

        return $trackingId;
    }

    /**
     * Build context for personalization
     */
    private function buildContext(Request $request): array
    {
        return [
            'referrer' => $request->header('referer'),
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_content' => $request->get('utm_content'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'timestamp' => now(),
            'session_id' => $request->session()->getId(),
        ];
    }

    /**
     * Apply A/B test variants to content
     */
    private function applyABTestVariants(array $content, array $abTests, string $audience): array
    {
        foreach ($abTests as $testId => $testData) {
            // Ensure testData is an array
            if (! is_array($testData)) {
                continue;
            }

            $variant = $testData['variant'] ?? [];
            $overrides = $variant['component_overrides'] ?? [];

            // Apply audience-specific overrides
            if (isset($overrides[$audience]) && is_array($overrides[$audience])) {
                $content = $this->mergeContentOverrides($content, $overrides[$audience]);
            } elseif (isset($overrides['headline']) || isset($overrides['subtitle'])) {
                // Apply general overrides to hero section
                if (isset($overrides['headline'])) {
                    // Ensure hero section exists
                    if (! isset($content['hero'])) {
                        $content['hero'] = [];
                    }
                    $content['hero']['headline'] = $overrides['headline'];
                }
                if (isset($overrides['subtitle'])) {
                    // Ensure hero section exists
                    if (! isset($content['hero'])) {
                        $content['hero'] = [];
                    }
                    $content['hero']['subtitle'] = $overrides['subtitle'];
                }
            }
        }

        return $content;
    }

    /**
     * Merge content overrides with proper null checks
     */
    private function mergeContentOverrides(array $content, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'headline' || $key === 'subtitle') {
                // Ensure hero section exists
                if (! isset($content['hero'])) {
                    $content['hero'] = [];
                }
                $content['hero'][$key] = $value;
            } elseif ($key === 'primary_cta_text') {
                // Ensure CTA structure exists
                if (! isset($content['cta'])) {
                    $content['cta'] = [];
                }
                if (! isset($content['cta']['primary'])) {
                    $content['cta']['primary'] = [];
                }
                $content['cta']['primary']['text'] = $value;
            } elseif ($key === 'secondary_cta_text') {
                // Ensure CTA structure exists
                if (! isset($content['cta'])) {
                    $content['cta'] = [];
                }
                if (! isset($content['cta']['secondary'])) {
                    $content['cta']['secondary'] = [];
                }
                $content['cta']['secondary']['text'] = $value;
            } else {
                // Handle nested overrides
                $this->setNestedValue($content, $key, $value);
            }
        }

        return $content;
    }

    /**
     * Set nested array value using dot notation with null checks
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (! isset($current[$k]) || ! is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Format A/B tests for frontend consumption with null checks
     */
    private function formatABTestsForFrontend(array $abTests): array
    {
        $formatted = [];

        foreach ($abTests as $testId => $testData) {
            // Ensure testData is an array
            if (! is_array($testData)) {
                continue;
            }

            $test = $testData['test'] ?? [];
            $variant = $testData['variant'] ?? [];

            $formatted[$testId] = [
                'id' => $testId,
                'name' => is_array($test) && isset($test['name']) ? $test['name'] : 'Unknown Test',
                'variant_id' => is_array($variant) && isset($variant['id']) ? $variant['id'] : 'control',
                'variant_name' => is_array($variant) && isset($variant['name']) ? $variant['name'] : 'Control',
            ];
        }

        return $formatted;
    }

    /**
     * Returns minimal safe structure with default content
     */
    private function getDefaultContent(): array
    {
        return [
            'hero' => [
                'headline' => 'Connect. Engage. Thrive.',
                'subtitle' => 'Join the alumni community that transforms careers and builds lasting connections',
            ],
            'cta' => [
                'primary' => [
                    'text' => 'Get Started',
                ],
                'secondary' => [
                    'text' => 'Learn More',
                ],
            ],
            'sections' => [],
            'meta' => [
                'title' => 'Alumate - Alumni Networking Platform',
                'description' => 'Connect with alumni, find mentors, and advance your career through meaningful networking',
                'canonical' => config('app.url'),
                'og' => [
                    'title' => 'Alumate - Alumni Networking Platform',
                    'description' => 'Connect with alumni, find mentors, and advance your career through meaningful networking',
                    'type' => 'website',
                    'url' => config('app.url'),
                ],
                'twitter' => [
                    'card' => 'summary_large_image',
                    'title' => 'Alumate - Alumni Networking Platform',
                    'description' => 'Connect with alumni, find mentors, and advance your career through meaningful networking',
                ],
            ],
        ];
    }

    /**
     * Safely derive metadata from content with fallbacks and proper validation
     */
    private function getMetaData(?array $content, \Illuminate\Http\Request $request): array
    {
        // Ensure we have valid content array
        if (! is_array($content)) {
            $content = $this->getDefaultContent();
        }

        // Safely extract title with fallback chain
        $title = 'Alumate';
        if (is_array($content) && isset($content['meta']) && is_array($content['meta']) && isset($content['meta']['title'])) {
            $title = $content['meta']['title'];
        } elseif (is_array($content) && isset($content['hero']) && is_array($content['hero']) && isset($content['hero']['headline'])) {
            $title = $content['hero']['headline'];
        }

        // Safely extract description with fallback chain
        $description = 'Transform your alumni network with powerful engagement tools and meaningful connections.';
        if (is_array($content) && isset($content['meta']) && is_array($content['meta']) && isset($content['meta']['description'])) {
            $description = $content['meta']['description'];
        } elseif (is_array($content) && isset($content['hero']) && is_array($content['hero']) && isset($content['hero']['subtitle'])) {
            $description = $content['hero']['subtitle'];
        }

        // Safely extract canonical URL with fallback
        $canonical = $request->fullUrl();
        if (is_array($content) && isset($content['meta']) && is_array($content['meta']) && isset($content['meta']['canonical'])) {
            $canonical = $content['meta']['canonical'];
        }

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'og' => [
                'title' => $title,
                'description' => $description,
                'type' => 'website',
                'url' => $canonical,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $description,
            ],
        ];
    }

    /**
     * Get meta data with proper null checks (legacy method for backward compatibility)
     */
    private function getLegacyMetaData(array $content, string $audience): array
    {
        // Check if content has meta data and it's valid
        if (isset($content['meta']) && is_array($content['meta']) && ! empty($content['meta'])) {
            // Ensure required meta fields exist
            $meta = $content['meta'];
            $meta['title'] = $meta['title'] ?? $this->getDefaultMeta($audience)['title'];
            $meta['description'] = $meta['description'] ?? $this->getDefaultMeta($audience)['description'];
            $meta['keywords'] = $meta['keywords'] ?? $this->getDefaultMeta($audience)['keywords'];

            return $meta;
        }

        // Fall back to default meta
        return $this->getDefaultMeta($audience);
    }

    /**
     * Get default meta data when content is null or invalid
     */
    private function getDefaultMeta(string $audience): array
    {
        if ($audience === 'institutional') {
            return [
                'title' => 'Alumni Engagement Platform for Universities & Organizations | AlumniConnect',
                'description' => 'Transform your alumni community with branded mobile apps, comprehensive analytics, and powerful engagement tools.',
                'keywords' => 'alumni engagement, university alumni platform, branded alumni app, institutional alumni solutions',
            ];
        }

        return [
            'title' => 'Professional Alumni Networking Platform | AlumniConnect',
            'description' => 'Connect with alumni, find mentors, discover job opportunities, and advance your career through meaningful networking.',
            'keywords' => 'alumni networking, career advancement, professional mentorship, job opportunities, alumni connections',
        ];
    }
}
