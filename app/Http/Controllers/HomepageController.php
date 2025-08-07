<?php

namespace App\Http\Controllers;

use App\Services\HomepageService;
use App\Services\ABTestingService;
use Illuminate\Http\Request;
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
        
        // Get personalized content
        $context = $this->buildContext($request);
        $content = $this->homepageService->getPersonalizedContent($audience, $context);
        
        // Get A/B test variants
        $abTests = $this->abTestingService->getActiveTests($userId, $audience);
        $content = $this->applyABTestVariants($content, $abTests, $audience);
        
        // Track page view
        $this->homepageService->trackPersonalizationEvent($audience, 'page_view', [
            'user_id' => $userId,
            'context' => $context,
            'ab_tests' => array_keys($abTests)
        ]);

        return Inertia::render('Homepage/Index', [
            'audience' => $audience,
            'content' => $content,
            'abTests' => $this->formatABTestsForFrontend($abTests),
            'userId' => $userId,
            'meta' => $content['meta']
        ]);
    }

    /**
     * Display the institutional homepage for administrators
     */
    public function institutional(Request $request)
    {
        $audience = 'institutional';
        $userId = $this->getUserId($request);
        
        // Get personalized content
        $context = $this->buildContext($request);
        $content = $this->homepageService->getPersonalizedContent($audience, $context);
        
        // Get A/B test variants
        $abTests = $this->abTestingService->getActiveTests($userId, $audience);
        $content = $this->applyABTestVariants($content, $abTests, $audience);
        
        // Track page view
        $this->homepageService->trackPersonalizationEvent($audience, 'page_view', [
            'user_id' => $userId,
            'context' => $context,
            'ab_tests' => array_keys($abTests)
        ]);

        return Inertia::render('Homepage/Index', [
            'audience' => $audience,
            'content' => $content,
            'abTests' => $this->formatABTestsForFrontend($abTests),
            'userId' => $userId,
            'meta' => $content['meta']
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
            'ab_tests' => 'array'
        ]);

        $userId = $this->getUserId($request);
        
        // Track the CTA click event
        $this->homepageService->trackPersonalizationEvent(
            $validated['audience'], 
            'cta_click', 
            [
                'user_id' => $userId,
                'action' => $validated['action'],
                'section' => $validated['section'],
                'additional_data' => $validated['additional_data'] ?? []
            ]
        );

        // Track A/B test conversions
        if (!empty($validated['ab_tests'])) {
            foreach ($validated['ab_tests'] as $testId => $variantId) {
                $this->abTestingService->trackConversion(
                    $testId,
                    $variantId,
                    'hero_cta_click',
                    $userId,
                    [
                        'action' => $validated['action'],
                        'section' => $validated['section'],
                        'audience' => $validated['audience']
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'CTA click tracked successfully'
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
            'additional_data' => 'array'
        ]);

        $userId = $this->getUserId($request);
        
        $this->abTestingService->trackConversion(
            $validated['test_id'],
            $validated['variant_id'],
            $validated['goal'],
            $userId,
            array_merge($validated['additional_data'] ?? [], [
                'audience' => $validated['audience']
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Conversion tracked successfully'
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
            return 'user_' . auth()->id();
        }
        
        // Use session ID for anonymous users
        if ($request->session()->has('tracking_user_id')) {
            return $request->session()->get('tracking_user_id');
        }
        
        // Generate new tracking ID
        $trackingId = 'anon_' . Str::random(16);
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
            'session_id' => $request->session()->getId()
        ];
    }

    /**
     * Apply A/B test variants to content
     */
    private function applyABTestVariants(array $content, array $abTests, string $audience): array
    {
        foreach ($abTests as $testId => $testData) {
            $variant = $testData['variant'];
            $overrides = $variant['component_overrides'] ?? [];
            
            // Apply audience-specific overrides
            if (isset($overrides[$audience])) {
                $content = $this->mergeContentOverrides($content, $overrides[$audience]);
            } elseif (isset($overrides['headline']) || isset($overrides['subtitle'])) {
                // Apply general overrides to hero section
                if (isset($overrides['headline'])) {
                    $content['hero']['headline'] = $overrides['headline'];
                }
                if (isset($overrides['subtitle'])) {
                    $content['hero']['subtitle'] = $overrides['subtitle'];
                }
            }
        }
        
        return $content;
    }

    /**
     * Merge content overrides
     */
    private function mergeContentOverrides(array $content, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            if ($key === 'headline' || $key === 'subtitle') {
                $content['hero'][$key] = $value;
            } elseif ($key === 'primary_cta_text') {
                $content['cta']['primary']['text'] = $value;
            } elseif ($key === 'secondary_cta_text') {
                $content['cta']['secondary']['text'] = $value;
            } else {
                // Handle nested overrides
                $this->setNestedValue($content, $key, $value);
            }
        }
        
        return $content;
    }

    /**
     * Set nested array value using dot notation
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    /**
     * Format A/B tests for frontend consumption
     */
    private function formatABTestsForFrontend(array $abTests): array
    {
        $formatted = [];
        
        foreach ($abTests as $testId => $testData) {
            $formatted[$testId] = [
                'id' => $testId,
                'name' => $testData['test']['name'],
                'variant_id' => $testData['variant']['id'],
                'variant_name' => $testData['variant']['name']
            ];
        }
        
        return $formatted;
    }
}