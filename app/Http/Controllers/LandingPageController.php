<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use App\Services\PublishingWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * LandingPageController
 *
 * Handles serving published landing pages to end users with caching and performance optimization
 */
class LandingPageController extends Controller
{
    public function __construct(
        private PublishingWorkflowService $publishingWorkflowService
    ) {}

    /**
     * Serve a published landing page
     *
     * @param Request $request
     * @param string $slug
     * @return View|Response
     */
    public function show(Request $request, string $slug)
    {
        try {
            // Determine tenant context for multi-tenancy
            $tenantId = $this->getTenantIdFromRequest($request, $slug);

            // Get published landing page
            $landingPage = $this->publishingWorkflowService->getPublishedLandingPage($slug, $tenantId);

            if (!$landingPage) {
                Log::info('Landing page not found', [
                    'slug' => $slug,
                    'tenant_id' => $tenantId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                abort(404, 'Landing page not found');
            }

            // Check if landing page has expired or is scheduled
            if (!$this->isLandingPageAvailable($landingPage)) {
                abort(404, 'Landing page not available');
            }

            // Track page view for analytics
            $this->trackPageView($landingPage, $request);

            // Get cached content for performance
            $content = $this->publishingWorkflowService->getCachedLandingPageContent($landingPage);

            // Set SEO meta tags
            $this->setSeoHeaders($content);

            // Set cache headers for performance
            $this->setCacheHeaders($request, $landingPage);

            // Render the landing page view
            return $this->renderLandingPage($landingPage, $content);

        } catch (\Exception $e) {
            Log::error('Error serving landing page', [
                'slug' => $slug,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            abort(500, 'Unable to load landing page');
        }
    }

    /**
     * Handle form submission for landing page
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function submitForm(Request $request, string $slug): JsonResponse
    {
        try {
            $tenantId = $this->getTenantIdFromRequest($request, $slug);
            $landingPage = $this->publishingWorkflowService->getPublishedLandingPage($slug, $tenantId);

            if (!$landingPage) {
                return response()->json(['error' => 'Landing page not found'], 404);
            }

            // Validate form data
            $validated = $request->validate([
                'form_name' => 'nullable|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'job_title' => 'nullable|string|max:255',
                'custom_fields' => 'nullable|array',
                'consent' => 'nullable|boolean',
                'newsletter_subscription' => 'nullable|boolean',
                'utm_source' => 'nullable|string|max:255',
                'utm_medium' => 'nullable|string|max:255',
                'utm_campaign' => 'nullable|string|max:255',
                'utm_term' => 'nullable|string|max:255',
                'utm_content' => 'nullable|string|max:255',
            ]);

            // Create submission record
            $submission = $this->createSubmission($landingPage, $validated, $request);

            if ($submission) {
                $this->trackConversion($landingPage, $submission, $request);

                return response()->json([
                    'success' => true,
                    'message' => 'Form submitted successfully',
                    'submission_id' => $submission->id,
                    'redirect_url' => $this->getThankYouUrl($landingPage, $request),
                ], 200);
            }

            return response()->json([
                'error' => 'Form submission failed',
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Form submission error', [
                'slug' => $slug,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'An error occurred while submitting the form',
            ], 500);
        }
    }

    /**
     * Track event (page view, click, etc.)
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function trackEvent(Request $request, string $slug): JsonResponse
    {
        try {
            $tenantId = $this->getTenantIdFromRequest($request, $slug);
            $landingPage = $this->publishingWorkflowService->getPublishedLandingPage($slug, $tenantId);

            if (!$landingPage) {
                return response()->json(['error' => 'Landing page not found'], 404);
            }

            $validated = $request->validate([
                'event_type' => 'required|string|in:page_view,button_click,form_start,scroll_depth',
                'event_data' => 'nullable|array',
                'element' => 'nullable|string|max:255',
                'url' => 'nullable|url',
                'timestamp' => 'nullable|date',
            ]);

            // Track analytics event
            $this->trackAnalyticsEvent($landingPage, $validated, $request);

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Analytics tracking error', [
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Tracking failed'], 500);
        }
    }

    /**
     * Serve preview of landing page (for authenticated users)
     *
     * @param Request $request
     * @param string $slug
     * @return View|Response
     */
    public function preview(Request $request, string $slug): View|Response
    {
        if (!Auth::check() || !Auth::user()->can('viewAny', LandingPage::class)) {
            abort(403, 'Unauthorized to preview this landing page');
        }

        try {
            $tenantId = $this->getTenantIdFromRequest($request, $slug);

            // Find landing page (not necessarily published for preview)
            $landingPage = LandingPage::where('slug', $slug)
                ->when($tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                ->with(['template', 'tenant'])
                ->first();

            if (!$landingPage) {
                abort(404, 'Landing page not found');
            }

            // Authorize preview access
            if (!Auth::user()->can('view', $landingPage)) {
                abort(403, 'Unauthorized to preview this landing page');
            }

            // Get content (bypass caching for real-time preview)
            $content = [
                'id' => $landingPage->id,
                'name' => $landingPage->name,
                'slug' => $landingPage->slug,
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
                'preview_mode' => true,
                'version' => $landingPage->version,
            ];

            // Render preview with debugging information
            return $this->renderLandingPage($landingPage, $content, true);

        } catch (\Exception $e) {
            Log::error('Preview error', [
                'slug' => $slug,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Unable to load preview');
        }
    }

    /**
     * Get tenant ID from request context
     *
     * @param Request $request
     * @param string $slug
     * @return int|null
     */
    private function getTenantIdFromRequest(Request $request, string $slug): ?int
    {
        // In multi-tenant setup, try to determine tenant from domain
        if (config('database.multi_tenant')) {
            try {
                if (function_exists('tenant') && tenant()) {
                    return tenant()->id;
                }

                // Try to determine from subdomain pattern
                $host = $request->getHost();
                $baseDomain = config('app.domain', parse_url(config('app.url'), PHP_URL_HOST));

                if (str_contains($host, '.' . $baseDomain)) {
                    $subdomain = str_replace('.' . $baseDomain, '', $host);
                    if (is_numeric($subdomain)) {
                        // subdomain might be tenant ID
                        return (int) $subdomain;
                    }
                }
            } catch (\Exception $e) {
                // Skip tenant detection in single-tenant mode
            }
        }

        return null;
    }

    /**
     * Check if landing page is available for viewing
     *
     * @param LandingPage $landingPage
     * @return bool
     */
    private function isLandingPageAvailable(LandingPage $landingPage): bool
    {
        return $landingPage->isPublished() &&
               $landingPage->published_at->isPast() &&
               $landingPage->status === 'published';
    }

    /**
     * Track page view for analytics
     *
     * @param LandingPage $landingPage
     * @param Request $request
     */
    private function trackPageView(LandingPage $landingPage, Request $request): void
    {
        try {
            $landingPage->analytics()->create([
                'event_type' => 'page_view',
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'utm_data' => $this->extractUtmData($request),
                'device_type' => $this->detectDeviceType($request),
                'country' => $this->getCountryFromIp($request->ip()),
            ]);

            // Increment usage count (with rate limiting to prevent abuse)
            $landingPage->increment('usage_count');

        } catch (\Exception $e) {
            Log::warning('Failed to track page view', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create form submission
     *
     * @param LandingPage $landingPage
     * @param array $data
     * @param Request $request
     * @return LandingPageSubmission|null
     */
    private function createSubmission(LandingPage $landingPage, array $data, Request $request): ?LandingPageSubmission
    {
        try {
            return LandingPageSubmission::create([
                'landing_page_id' => $landingPage->id,
                'form_name' => $data['form_name'] ?? 'default',
                'form_data' => $data,
                'utm_data' => $this->extractUtmData($request),
                'session_data' => $this->extractSessionData($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'status' => 'new',
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create submission', [
                'landing_page_id' => $landingPage->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Track conversion event
     *
     * @param LandingPage $landingPage
     * @param LandingPageSubmission $submission
     * @param Request $request
     */
    private function trackConversion(LandingPage $landingPage, LandingPageSubmission $submission, Request $request): void
    {
        try {
            $landingPage->analytics()->create([
                'event_type' => 'conversion',
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'form_submission_id' => $submission->id,
                'conversion_value' => $this->calculateConversionValue($landingPage, $submission),
            ]);

            // Mark as converted
            $submission->markAsProcessed();

            // Increment conversion count
            $landingPage->increment('conversion_count');

        } catch (\Exception $e) {
            Log::warning('Failed to track conversion', [
                'landing_page_id' => $landingPage->id,
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Set SEO headers
     *
     * @param array $content
     */
    private function setSeoHeaders(array $content): void
    {
        // Set page title
        if (!empty($content['seo_title'])) {
            view()->share('title', $content['seo_title']);
        }

        // Set meta description
        if (!empty($content['seo_description'])) {
            view()->share('description', $content['seo_description']);
        }

        // Set canonical URL
        if (isset($content['public_url'])) {
            view()->share('canonical_url', $content['public_url']);
        }
    }

    /**
     * Set cache headers for performance
     *
     * @param Request $request
     * @param LandingPage $landingPage
     */
    private function setCacheHeaders(Request $request, LandingPage $landingPage): void
    {
        // Set cache control headers based on whether it's a preview request
        if ($request->has('preview')) {
            // No cache for preview mode
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        } else {
            // Browser cache for 1 hour
            header('Cache-Control: public, max-age=3600');
            header("ETag: {$landingPage->slug}-{$landingPage->version}");
        }
    }

    /**
     * Render landing page view
     *
     * @param LandingPage $landingPage
     * @param array $content
     * @param bool $previewMode
     * @return View
     */
    private function renderLandingPage(LandingPage $landingPage, array $content, bool $previewMode = false): View
    {
        return view('landing-pages.show', [
            'landingPage' => $landingPage,
            'content' => $content,
            'isPreview' => $previewMode,
            'tenant' => $landingPage->tenant,
        ]);
    }

    /**
     * Extract UTM data from request
     *
     * @param Request $request
     * @return array
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
     * Extract session data
     *
     * @param Request $request
     * @return array
     */
    private function extractSessionData(Request $request): array
    {
        return [
            'session_id' => $request->session()->getId(),
            'previous_url' => $request->session()->previousUrl(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'referrer' => $request->header('referer'),
        ];
    }

    /**
     * Detect device type from request
     *
     * @param Request $request
     * @return string
     */
    private function detectDeviceType(Request $request): string
    {
        $userAgent = $request->userAgent();

        if (preg_match('/mobile/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet/i', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    /**
     * Get country from IP address (placeholder implementation)
     *
     * @param string $ip
     * @return string|null
     */
    private function getCountryFromIp(string $ip): ?string
    {
        // Implementation would use a geo-IP service
        // For now, return null
        return null;
    }

    /**
     * Track analytics event
     *
     * @param LandingPage $landingPage
     * @param array $eventData
     * @param Request $request
     */
    private function trackAnalyticsEvent(LandingPage $landingPage, array $eventData, Request $request): void
    {
        try {
            $landingPage->analytics()->create([
                'event_type' => $eventData['event_type'],
                'session_id' => $request->session()->getId(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'event_data' => $eventData['event_data'] ?? [],
                'element' => $eventData['element'] ?? null,
                'url' => $eventData['url'] ?? null,
                'available_at' => $eventData['timestamp'] ?? now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to track analytics event', [
                'landing_page_id' => $landingPage->id,
                'event_type' => $eventData['event_type'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate conversion value (placeholder implementation)
     *
     * @param LandingPage $landingPage
     * @param LandingPageSubmission $submission
     * @return float
     */
    private function calculateConversionValue(LandingPage $landingPage, LandingPageSubmission $submission): float
    {
        // Implementation would calculate business value of conversion
        // For now, return 0 as placeholder
        return 0.0;
    }

    /**
     * Get thank you page URL
     *
     * @param LandingPage $landingPage
     * @param Request $request
     * @return string|null
     */
    private function getThankYouUrl(LandingPage $landingPage, Request $request): ?string
    {
        // Check if landing page config has thank you page URL
        $config = $landingPage->config ?? [];
        if (isset($config['thank_you_url'])) {
            return $config['thank_you_url'];
        }

        // Default thank you behavior - stay on same page with success message
        return $request->url() . '#success';
    }
}