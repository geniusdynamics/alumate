<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomepageService;
use App\Services\PersonalizationService;
use App\Services\LeadCaptureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    public function __construct(
        private HomepageService $homepageService,
        private PersonalizationService $personalizationService,
        private \App\Services\ABTestingService $abTestingService
    ) {}

    /**
     * Get platform statistics for homepage display
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
            ]);

            $audience = $validated['audience'] ?? 'individual';
            $statisticsData = $this->homepageService->getPlatformStatistics($audience);
            
            // Transform the data to match the expected format
            $statistics = [];
            foreach ($statisticsData as $key => $value) {
                if ($key === 'last_updated') {
                    continue; // Handle separately
                }
                
                $statistics[] = $this->transformStatistic($key, $value, $audience);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $statistics,
                    'last_updated' => $statisticsData['last_updated'] ?? now()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get testimonials based on audience type
     */
    public function getTestimonials(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
            ]);

            $audience = $validated['audience'] ?? 'individual';
            $testimonials = $this->homepageService->getTestimonials($audience);
            
            return response()->json([
                'success' => true,
                'data' => $testimonials->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch testimonials',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trust badges and company logos
     */
    public function getTrustBadges(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
            ]);

            $audience = $validated['audience'] ?? 'individual';
            $trustData = $this->homepageService->getTrustBadgesAndLogos($audience);
            
            return response()->json([
                'success' => true,
                'data' => $trustData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch trust badges',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get platform preview data including screenshots and tour steps
     */
    public function getPlatformPreview(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
            ]);

            $audience = $validated['audience'] ?? 'individual';
            $previewData = $this->homepageService->getPlatformPreviewData($audience);
            
            return response()->json([
                'success' => true,
                'data' => $previewData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch platform preview data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get success stories filtered by audience
     */
    public function getSuccessStories(Request $request): JsonResponse
    {
        $audience = $request->get('audience', 'individual');
        $filters = $request->only(['industry', 'graduation_year', 'career_stage']);
        
        $stories = $this->homepageService->getSuccessStories($audience, $filters);
        
        return response()->json($stories);
    }

    /**
     * Get platform features based on audience
     */
    public function getFeatures(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
            ]);

            $audience = $validated['audience'] ?? 'individual';
            $features = $this->homepageService->getFeatures($audience);
            
            return response()->json([
                'success' => true,
                'data' => $features->toArray()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch features',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate career value based on user inputs
     */
    public function calculateValue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_role' => 'required|string',
            'industry' => 'required|string',
            'experience_years' => 'required|integer|min:0',
            'career_goals' => 'required|array',
            'location' => 'nullable|string',
            'education_level' => 'nullable|string'
        ]);

        $calculation = $this->homepageService->calculateCareerValue($validated);
        
        return response()->json($calculation);
    }



    /**
     * Capture leads from various homepage interactions
     */
    public function captureLeads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'source' => 'required|string',
            'audience' => 'required|in:individual,institutional',
            'interest_level' => 'nullable|string',
            'additional_data' => 'nullable|array'
        ]);

        $result = $this->homepageService->captureLeads($validated);
        
        return response()->json($result);
    }

    /**
     * Detect audience based on request signals
     */
    public function detectAudience(Request $request): JsonResponse
    {
        $detection = $this->personalizationService->detectAudience($request);
        
        return response()->json($detection);
    }

    /**
     * Get personalized content for specific audience
     */
    public function getPersonalizedContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(['individual', 'institutional'])],
            'sections' => 'nullable|array',
            'sections.*' => Rule::in(['hero', 'features', 'testimonials', 'pricing', 'cta', 'meta'])
        ]);

        $audience = $validated['audience'];
        $content = $this->personalizationService->getPersonalizedContent($audience, $request);

        // Filter to requested sections if specified
        if (!empty($validated['sections'])) {
            $content = array_intersect_key($content, array_flip($validated['sections']));
        }

        return response()->json([
            'audience' => $audience,
            'content' => $content,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Store audience preference
     */
    public function storeAudiencePreference(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(['individual', 'institutional'])],
            'source' => ['nullable', Rule::in(['manual', 'auto_detected', 'url_param'])],
        ]);

        $preference = $this->personalizationService->storeAudiencePreference(
            $validated['audience'],
            $validated['source'] ?? 'manual'
        );

        return response()->json([
            'success' => true,
            'preference' => $preference,
            'message' => 'Audience preference stored successfully'
        ]);
    }

    /**
     * Get stored audience preference
     */
    public function getAudiencePreference(Request $request): JsonResponse
    {
        $preference = $this->personalizationService->getStoredAudiencePreference();

        return response()->json([
            'preference' => $preference,
            'has_preference' => !is_null($preference)
        ]);
    }

    /**
     * Get content variations for A/B testing
     */
    public function getContentVariations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['required', Rule::in(['individual', 'institutional'])],
            'test_id' => 'required|string'
        ]);

        $variations = $this->homepageService->getContentVariations(
            $validated['audience'],
            $validated['test_id']
        );

        return response()->json([
            'test_id' => $validated['test_id'],
            'audience' => $validated['audience'],
            'variations' => $variations
        ]);
    }

    /**
     * Get A/B test variant for user
     */
    public function getABTestVariant(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_id' => 'required|string',
            'user_id' => 'nullable|string',
            'audience' => ['required', Rule::in(['individual', 'institutional'])]
        ]);

        $userId = $validated['user_id'] ?? $this->generateAnonymousUserId($request);
        
        $variant = $this->abTestingService->getVariant(
            $validated['test_id'],
            $userId,
            $validated['audience']
        );

        return response()->json([
            'variant' => $variant,
            'user_id' => $userId,
            'has_variant' => $variant !== null
        ]);
    }

    /**
     * Track A/B test conversion
     */
    public function trackABTestConversion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'test_id' => 'required|string',
            'variant_id' => 'required|string',
            'goal' => 'required|string',
            'user_id' => 'nullable|string',
            'additional_data' => 'nullable|array'
        ]);

        $userId = $validated['user_id'] ?? $this->generateAnonymousUserId($request);

        $this->abTestingService->trackConversion(
            $validated['test_id'],
            $validated['variant_id'],
            $validated['goal'],
            $userId,
            $validated['additional_data'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Conversion tracked successfully'
        ]);
    }

    /**
     * Get A/B test results (admin only)
     */
    public function getABTestResults(Request $request, string $testId): JsonResponse
    {
        // In production, add proper authorization check
        $results = $this->abTestingService->getTestResults($testId);
        
        return response()->json($results);
    }

    /**
     * Get all active A/B tests for a user
     */
    public function getActiveABTests(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'nullable|string',
            'audience' => ['required', Rule::in(['individual', 'institutional'])]
        ]);

        $userId = $validated['user_id'] ?? $this->generateAnonymousUserId($request);
        
        $activeTests = $this->abTestingService->getActiveTests($userId, $validated['audience']);
        
        return response()->json([
            'user_id' => $userId,
            'audience' => $validated['audience'],
            'active_tests' => $activeTests
        ]);
    }

    /**
     * Get content management configuration
     */
    public function getContentManagementConfig(Request $request): JsonResponse
    {
        $config = $this->homepageService->getContentManagementConfig();
        
        return response()->json($config);
    }

    /**
     * Get branded apps showcase data for institutional audience
     */
    public function getBrandedAppsData(Request $request): JsonResponse
    {
        try {
            $brandedAppsData = $this->homepageService->getBrandedAppsData();
            
            return response()->json([
                'success' => true,
                'data' => $brandedAppsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch branded apps data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get enterprise metrics and ROI data for institutional clients
     */
    public function getEnterpriseMetrics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'institution_id' => 'nullable|string',
                'timeframe' => 'nullable|in:3_months,6_months,12_months,18_months,24_months',
                'metrics' => 'nullable|array',
                'metrics.*' => 'in:engagement,financial,operational,growth'
            ]);

            $metricsData = $this->homepageService->getEnterpriseMetrics($validated);
            
            return response()->json([
                'success' => true,
                'data' => $metricsData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch enterprise metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get institutional before/after comparison data
     */
    public function getInstitutionalComparison(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'institution_id' => 'nullable|string',
                'case_study_id' => 'nullable|string'
            ]);

            $comparisonData = $this->homepageService->getInstitutionalComparison($validated);
            
            return response()->json([
                'success' => true,
                'data' => $comparisonData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch institutional comparison data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get implementation timeline data for institutional projects
     */
    public function getImplementationTimeline(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'institution_type' => 'nullable|in:university,college,corporate,nonprofit',
                'alumni_count' => 'nullable|integer',
                'complexity' => 'nullable|in:basic,standard,advanced,enterprise'
            ]);

            $timelineData = $this->homepageService->getImplementationTimeline($validated);
            
            return response()->json([
                'success' => true,
                'data' => $timelineData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch implementation timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get success metrics tracking data for institutions
     */
    public function getSuccessMetricsTracking(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'institution_id' => 'nullable|string',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date',
                'metrics' => 'nullable|array'
            ]);

            $trackingData = $this->homepageService->getSuccessMetricsTracking($validated);
            
            return response()->json([
                'success' => true,
                'data' => $trackingData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch success metrics tracking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personalization analytics
     */
    public function getPersonalizationAnalytics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'audience' => ['nullable', Rule::in(['individual', 'institutional'])],
            'metrics' => 'nullable|array'
        ]);

        $analytics = $this->personalizationService->getPersonalizationAnalytics($validated);
        
        return response()->json($analytics);
    }

    /**
     * Clear personalization cache
     */
    public function clearPersonalizationCache(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'audience' => ['nullable', Rule::in(['individual', 'institutional'])]
        ]);

        $this->personalizationService->clearPersonalizationCache($validated['audience'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Personalization cache cleared successfully'
        ]);
    }

    /**
     * Transform raw statistic data to frontend format
     */
    private function transformStatistic(string $key, mixed $value, string $audience): array
    {
        $statisticMappings = [
            'individual' => [
                'total_alumni' => [
                    'label' => 'Alumni Connected',
                    'icon' => 'users',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'successful_connections' => [
                    'label' => 'Successful Connections',
                    'icon' => 'network',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'job_placements' => [
                    'label' => 'Job Placements',
                    'icon' => 'briefcase',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'average_salary_increase' => [
                    'label' => 'Average Salary Increase',
                    'icon' => 'trending-up',
                    'format' => 'percentage'
                ],
                'mentorship_matches' => [
                    'label' => 'Mentorship Matches',
                    'icon' => 'users',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'events_hosted' => [
                    'label' => 'Events Hosted',
                    'icon' => 'calendar',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'companies_represented' => [
                    'label' => 'Companies Represented',
                    'icon' => 'building',
                    'format' => 'number',
                    'suffix' => '+'
                ]
            ],
            'institutional' => [
                'institutions_served' => [
                    'label' => 'Institutions Served',
                    'icon' => 'building',
                    'format' => 'number',
                    'suffix' => '+'
                ],
                'branded_apps_deployed' => [
                    'label' => 'Branded Apps Deployed',
                    'icon' => 'mobile',
                    'format' => 'number'
                ],
                'average_engagement_increase' => [
                    'label' => 'Average Engagement Increase',
                    'icon' => 'trending-up',
                    'format' => 'percentage'
                ],
                'admin_satisfaction_rate' => [
                    'label' => 'Admin Satisfaction Rate',
                    'icon' => 'star',
                    'format' => 'percentage'
                ]
            ]
        ];

        $mapping = $statisticMappings[$audience][$key] ?? [
            'label' => ucwords(str_replace('_', ' ', $key)),
            'icon' => 'users',
            'format' => 'number'
        ];

        return [
            'key' => $key,
            'value' => is_numeric($value) ? (int) $value : 0,
            'label' => $mapping['label'],
            'icon' => $mapping['icon'],
            'format' => $mapping['format'],
            'suffix' => $mapping['suffix'] ?? null,
            'animateOnScroll' => true
        ];
    }

    /**
     * Generate anonymous user ID for tracking
     */
    private function generateAnonymousUserId(Request $request): string
    {
        if (auth()->check()) {
            return 'user_' . auth()->id();
        }
        
        if ($request->session()->has('tracking_user_id')) {
            return $request->session()->get('tracking_user_id');
        }
        
        $trackingId = 'anon_' . \Illuminate\Support\Str::random(16);
        $request->session()->put('tracking_user_id', $trackingId);
        
        return $trackingId;
    }

    /**
     * Request a demo (updated for lead capture)
     */
    public function requestDemo(Request $request): JsonResponse
    {
        $request->validate([
            'institutionName' => 'required|string|max:255',
            'contactName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'title' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'alumniCount' => 'nullable|string',
            'currentSolution' => 'nullable|string',
            'interests' => 'nullable|array',
            'preferredTime' => 'nullable|string',
            'message' => 'nullable|string|max:1000',
            'planId' => 'nullable|string',
            'source' => 'nullable|string'
        ]);

        try {
            $result = app(LeadCaptureService::class)->processDemoRequest($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Trial signup (updated for lead capture)
     */
    public function trialSignup(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'graduationYear' => 'nullable|integer|min:1950|max:' . (date('Y') + 10),
            'institution' => 'nullable|string|max:255',
            'currentRole' => 'nullable|string|max:255',
            'industry' => 'nullable|string',
            'referralSource' => 'nullable|string',
            'planId' => 'nullable|string',
            'source' => 'nullable|string'
        ]);

        try {
            $result = app(LeadCaptureService::class)->processTrialSignup($request->all());
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get lead capture statistics
     */
    public function getLeadStatistics(): JsonResponse
    {
        try {
            $statistics = app(LeadCaptureService::class)->getLeadStatistics();
            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics'
            ], 500);
        }
    }
}