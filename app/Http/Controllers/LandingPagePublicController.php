<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageAnalytics;
use App\Services\LandingPageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LandingPagePublicController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {}

    /**
     * Display a landing page
     */
    public function show(string $slug, Request $request): Response
    {
        $landingPage = LandingPage::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Track page view
        $this->trackPageView($landingPage, $request);

        return Inertia::render('LandingPage/Show', [
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
        ]);
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
            'event_type' => 'required|string',
            'event_name' => 'nullable|string',
            'event_data' => 'nullable|array',
        ]);

        try {
            LandingPageAnalytics::create([
                'landing_page_id' => $landingPage->id,
                'event_type' => $validated['event_type'],
                'event_name' => $validated['event_name'] ?? null,
                'event_data' => $validated['event_data'] ?? [],
                'session_id' => $request->session()->getId(),
                'visitor_id' => $this->getVisitorId($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'utm_data' => $this->extractUtmData($request),
                'device_type' => $this->getDeviceType($request),
                'browser' => $this->getBrowser($request),
                'os' => $this->getOS($request),
                'event_time' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Event tracked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to track event',
            ], 500);
        }
    }

    /**
     * Track page view
     */
    private function trackPageView(LandingPage $landingPage, Request $request): void
    {
        LandingPageAnalytics::create([
            'landing_page_id' => $landingPage->id,
            'event_type' => 'page_view',
            'session_id' => $request->session()->getId(),
            'visitor_id' => $this->getVisitorId($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'utm_data' => $this->extractUtmData($request),
            'device_type' => $this->getDeviceType($request),
            'browser' => $this->getBrowser($request),
            'os' => $this->getOS($request),
            'event_time' => now(),
        ]);
    }

    /**
     * Track form submission
     */
    private function trackFormSubmission(LandingPage $landingPage, Request $request, array $formData): void
    {
        LandingPageAnalytics::create([
            'landing_page_id' => $landingPage->id,
            'event_type' => 'form_submit',
            'event_name' => $formData['form_name'] ?? 'default',
            'event_data' => [
                'form_fields' => array_keys($formData),
                'form_name' => $formData['form_name'] ?? 'default',
            ],
            'session_id' => $request->session()->getId(),
            'visitor_id' => $this->getVisitorId($request),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'utm_data' => $this->extractUtmData($request),
            'device_type' => $this->getDeviceType($request),
            'browser' => $this->getBrowser($request),
            'os' => $this->getOS($request),
            'event_time' => now(),
        ]);
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

        $visitorId = 'visitor_'.\Str::random(16);
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
