<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageAnalytics;
use App\Services\LandingPageService;
use App\Services\TemplateAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingPagePublicController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService,
        private TemplateAnalyticsService $analyticsService
    ) {}

    /**
     * Display a landing page
     */
    public function show(string $slug, Request $request): Response
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Track page view using the new analytics service
        $this->trackPageView($landingPage, $request);

        // Generate analytics tracking code if template exists
        $analyticsCode = '';
        if ($landingPage->template_id) {
            $analyticsCode = $this->analyticsService->generateTrackingCode(
                $landingPage->template_id,
                $landingPage->id
            );
        }

        $responseData = [
            'landingPage' => [
                'id' => $landingPage->id,
                'name' => $landingPage->name,
                'title' => $landingPage->title,
                'description' => $landingPage->description,
                'content' => $landingPage->content,
                'settings' => $landingPage->settings,
                'form_config' => $landingPage->form_config,
                'target_audience' => $landingPage->target_audience,
                'campaign_type' => $landingPage->campaign_type,
                'campaign_name' => $landingPage->campaign_name,
            ],
            'meta' => [
                'title' => $landingPage->title,
                'description' => $landingPage->description,
                'og_title' => $landingPage->settings['seo']['og_title'] ?? $landingPage->title,
                'og_description' => $landingPage->settings['seo']['og_description'] ?? $landingPage->description,
                'og_image' => $landingPage->settings['seo']['og_image'] ?? null,
            ],
        ];

        // Inject analytics tracking code if available
        if (!empty($analyticsCode)) {
            $responseData['analytics_tracking'] = [
                'enabled' => true,
                'code' => base64_encode($analyticsCode),
                'last_updated' => now()->toISOString(),
            ];
        }

        return Inertia::render('LandingPage/Show', $responseData);
    }

    /**
     * Handle form submission
     */
    public function submitForm(string $slug, Request $request): JsonResponse
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Validate form data based on form config
        $formConfig = $landingPage->form_config ?? [];
        $validationRules = $this->buildValidationRules($formConfig);

        $validated = $request->validate($validationRules);

        try {
            $submission = $this->landingPageService->handleFormSubmission(
                $landingPage,
                $validated,
                $request
            );

            // Track form submission
            $this->trackFormSubmission($landingPage, $request, $validated);

            return response()->json([
                'success' => true,
                'message' => $landingPage->settings['behavior']['thank_you_message'] ?? 'Thank you for your submission!',
                'redirect_url' => $landingPage->settings['behavior']['redirect_after_submit'] ?? null,
                'submission_id' => $submission->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit form. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Track analytics event
     */
    public function trackEvent(string $slug, Request $request): JsonResponse
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->published()
            ->firstOrFail();

        $validated = $request->validate([
            'event_type' => 'required|string|in:' . implode(',', \App\Models\TemplateAnalyticsEvent::EVENT_TYPES),
            'event_data' => 'nullable|array',
            'conversion_value' => 'nullable|numeric|min:0|max:999999.99',
            'session_id' => 'nullable|string|max:255',
        ]);

        try {
            $eventData = array_merge($validated, [
                'template_id' => $landingPage->template_id,
                'landing_page_id' => $landingPage->id,
                'user_identifier' => $this->getVisitorId($request) . '_et',
                'referrer_url' => $request->header('referer'),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);

            $event = $this->analyticsService->trackEvent($eventData);

            return response()->json([
                'success' => true,
                'message' => 'Event tracked successfully',
                'event_id' => $event ? $event->id : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track event',
            ], 500);
        }
    }

    /**
     * Track page view using the new analytics service
     */
    private function trackPageView(LandingPage $landingPage, Request $request): void
    {
        try {
            $this->analyticsService->trackEvent([
                'event_type' => 'page_view',
                'template_id' => $landingPage->template_id,
                'landing_page_id' => $landingPage->id,
                'user_identifier' => $this->getVisitorId($request) . '_pv',
                'session_id' => $request->session()->getId(),
                'referrer_url' => $request->header('referer'),
                'user_agent' => $request->userAgent(),
                'event_data' => [
                    'page_title' => $landingPage->title,
                    'page_path' => '/' . $landingPage->slug,
                    'campaign_type' => $landingPage->campaign_type,
                    'target_audience' => $landingPage->target_audience,
                ],
            ]);
        } catch (\Exception $e) {
            // Log error but don't prevent page loading
            \Illuminate\Support\Facades\Log::warning('Failed to track page view', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track form submission using the new analytics service
     */
    private function trackFormSubmission(LandingPage $landingPage, Request $request, array $formData): void
    {
        try {
            $this->analyticsService->trackEvent([
                'event_type' => 'form_submit',
                'template_id' => $landingPage->template_id,
                'landing_page_id' => $landingPage->id,
                'user_identifier' => $this->getVisitorId($request) . '_fs',
                'session_id' => $request->session()->getId(),
                'referrer_url' => $request->header('referer'),
                'user_agent' => $request->userAgent(),
                'event_data' => [
                    'form_name' => $formData['form_name'] ?? 'default',
                    'form_fields' => array_keys($formData),
                    'campaign_type' => $landingPage->campaign_type,
                    'target_audience' => $landingPage->target_audience,
                ],
            ]);
        } catch (\Exception $e) {
            // Log error but don't prevent form submission
            \Illuminate\Support\Facades\Log::warning('Failed to track form submission', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build validation rules from form config
     */
    private function buildValidationRules(array $formConfig): array
    {
        $rules = [];
        $fields = $formConfig['fields'] ?? [];

        foreach ($fields as $field) {
            $fieldRules = [];

            if ($field['required'] ?? false) {
                $fieldRules[] = 'required';
            }

            switch ($field['type'] ?? 'text') {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'phone':
                    $fieldRules[] = 'string';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                default:
                    $fieldRules[] = 'string';
            }

            if (isset($field['max_length'])) {
                $fieldRules[] = 'max:'.$field['max_length'];
            }

            $rules[$field['name']] = implode('|', $fieldRules);
        }

        return $rules;
    }

    /**
     * Get visitor ID for tracking
     */
    private function getVisitorId(Request $request): string
    {
        if ($request->session()->has('visitor_id')) {
            return $request->session()->get('visitor_id');
        }

        $visitorId = 'visitor_' . \Illuminate\Support\Str::random(16);
        $request->session()->put('visitor_id', $visitorId);

        return $visitorId;
    }

    /**
     * Extract UTM data from request
     */
    private function extractUtmData(Request $request): array
    {
        return [
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
        ];
    }

    /**
     * Get device type from user agent
     */
    private function getDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            return preg_match('/iPad/', $userAgent) ? 'tablet' : 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get browser from user agent
     */
    private function getBrowser(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Chrome/', $userAgent)) {
            return 'Chrome';
        }
        if (preg_match('/Firefox/', $userAgent)) {
            return 'Firefox';
        }
        if (preg_match('/Safari/', $userAgent)) {
            return 'Safari';
        }
        if (preg_match('/Edge/', $userAgent)) {
            return 'Edge';
        }

        return 'Other';
    }

    /**
     * Get OS from user agent
     */
    private function getOS(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        }
        if (preg_match('/Mac/', $userAgent)) {
            return 'macOS';
        }
        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }
        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }
        if (preg_match('/iOS/', $userAgent)) {
            return 'iOS';
        }

        return 'Other';
    }
}
