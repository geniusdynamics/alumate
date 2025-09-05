<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TemplateAnalyticsService;
use App\Services\TrackingCodeService;
use App\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Analytics Tracking Controller
 *
 * Handles analytics tracking for templates and landing pages
 */
class AnalyticsTrackingController extends Controller
{
    public function __construct(
        private TemplateAnalyticsService $analyticsService,
        private TrackingCodeService $trackingCodeService
    ) {}

    /**
     * Track analytics events for landing pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function track(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'event_type' => 'required|string|in:page_view,conversion,cta_click,form_submit,bounce,scroll,exit',
                'landing_page_id' => 'sometimes|integer|exists:landing_pages,id',
                'event_data' => 'sometimes|array',
            ]);

            $eventType = $validated['event_type'];
            $landingPageId = $validated['landing_page_id'] ?? null;
            $eventData = $validated['event_data'] ?? [];

            // Track the event using the appropriate service
            if ($landingPageId) {
                if ($eventType === 'page_view') {
                    $this->analyticsService->trackPageView($landingPageId, $eventData);
                } elseif ($eventType === 'conversion') {
                    $conversionType = $eventData['conversion_type'] ?? 'general';
                    $this->analyticsService->trackConversion($landingPageId, $conversionType, $eventData);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Event tracked successfully',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to track analytics event', [
                'event_type' => $request->input('event_type'),
                'landing_page_id' => $request->input('landing_page_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track event',
            ], 500);
        }
    }

    /**
     * Track template usage events
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function trackTemplateUsage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_id' => 'required|integer|exists:templates,id',
                'event_type' => 'required|string|in:template_view,template_use,template_customize',
                'tenant_id' => 'sometimes|integer',
                'additional_data' => 'sometimes|array',
            ]);

            $templateId = $validated['template_id'];
            $eventType = $validated['event_type'];
            $tenantId = $validated['tenant_id'] ?? null;
            $additionalData = $validated['additional_data'] ?? [];

            $success = $this->analyticsService->trackTemplateUsage(
                $templateId,
                $tenantId,
                $eventType,
                $additionalData
            );

            if ($success) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Template usage tracked successfully',
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track template usage',
            ], 500);

        } catch (\Exception $e) {
            \Log::error('Failed to track template usage', [
                'template_id' => $request->input('template_id'),
                'event_type' => $request->input('event_type'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to track template usage',
            ], 500);
        }
    }

    /**
     * Generate tracking pixel for landing pages
     *
     * @param Request $request
     * @param int $landingPageId
     * @return \Illuminate\Http\Response
     */
    public function pixel(Request $request, int $landingPageId): \Illuminate\Http\Response
    {
        try {
            // Extract tracking parameters from URL
            $visitorId = $request->get('visitor_id') ?: $this->trackingCodeService->generateVisitorId();
            $sessionId = $request->get('session_id') ?: $this->trackingCodeService->generateSessionId();

            // Track page view event
            $this->analyticsService->trackPageView($landingPageId, [
                'visitor_id' => $visitorId,
                'session_id' => $sessionId,
                'utm_source' => $request->get('utm_source'),
                'utm_medium' => $request->get('utm_medium'),
                'utm_campaign' => $request->get('utm_campaign'),
                'source' => 'pixel_tracking',
            ]);

            // Log the tracking
            \Log::info('Page view tracked via pixel', [
                'landing_page_id' => $landingPageId,
                'visitor_id' => $visitorId,
                'session_id' => $sessionId,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
            ]);

            // Return a 1x1 transparent pixel
            $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

            return response($pixel, 200, [
                'Content-Type' => 'image/gif',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to track page via pixel', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);

            // Still return a pixel even if tracking fails
            $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            return response($pixel, 200, [
                'Content-Type' => 'image/gif',
            ]);
        }
    }

    /**
     * Get analytics data for a landing page
     *
     * @param Request $request
     * @param int $landingPageId
     * @return JsonResponse
     */
    public function getLandingPageAnalytics(Request $request, int $landingPageId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            // For the existing analytics method, we'll need to check if it exists
            $analytics = $this->analyticsService->getTemplateAnalytics($landingPageId, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get landing page analytics', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve analytics data',
            ], 500);
        }
    }

    /**
     * Get earnings report for analytics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEarningsReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            // This would aggregate earnings data from analytics
            // For now, return a placeholder structure
            $earningsData = [
                'total_earnings' => 0.00,
                'period_start' => $filters['start_date'] ?? now()->subMonth()->toDateString(),
                'period_end' => $filters['end_date'] ?? now()->toDateString(),
                'breakdown' => [
                    'page_views' => 0,
                    'conversions' => 0,
                    'estimated_revenue' => 0.00,
                ],
                'generated_at' => now()->toISOString(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $earningsData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate earnings report', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate earnings report',
            ], 500);
        }
    }

    /**
     * Generate tracking code for a landing page
     *
     * @param Request $request
     * @param int $landingPageId
     * @return JsonResponse
     */
    public function getTrackingCode(Request $request, int $landingPageId): JsonResponse
    {
        try {
            $provider = $request->get('provider', 'google');
            $code = $this->trackingCodeService->generateTrackingCode($landingPageId, $provider);

            return response()->json([
                'status' => 'success',
                'tracking_code' => $code,
                'provider' => $provider,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate tracking code', [
                'landing_page_id' => $landingPageId,
                'provider' => $request->get('provider'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate tracking code',
            ], 500);
        }
    }

    /**
     * Get tracking pixel HTML for a landing page
     *
     * @param Request $request
     * @param int $landingPageId
     * @return JsonResponse
     */
    public function getTrackingPixel(Request $request, int $landingPageId): JsonResponse
    {
        try {
            $landingPage = LandingPage::find($landingPageId);

            if (!$landingPage) {
                return response()->json(['status' => 'error', 'message' => 'Landing page not found'], 404);
            }

            $pixelHtml = $this->trackingCodeService->generateTrackingPixel($landingPage);

            return response()->json([
                'status' => 'success',
                'pixel_html' => $pixelHtml,
                'pixel_url' => route('analytics-tracking-pixel', ['landingPageId' => $landingPageId]),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate tracking pixel', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate tracking pixel',
            ], 500);
        }
    }

    /**
     * Generate SEO meta tags with tracking information
     *
     * @param Request $request
     * @param int $landingPageId
     * @return JsonResponse
     */
    public function getSEOMetaTags(Request $request, int $landingPageId): JsonResponse
    {
        try {
            $landingPage = LandingPage::find($landingPageId);

            if (!$landingPage) {
                return response()->json(['status' => 'error', 'message' => 'Landing page not found'], 404);
            }

            $metaTags = $this->trackingCodeService->generateSEOMetaTags($landingPage);

            return response()->json([
                'status' => 'success',
                'meta_tags' => $metaTags,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate SEO meta tags', [
                'landing_page_id' => $landingPageId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate SEO meta tags',
            ], 500);
        }
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalyticsDashboard(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);
            $dashboardData = $this->analyticsService->getAnalyticsDashboard($filters);

            return response()->json([
                'status' => 'success',
                'data' => $dashboardData,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get analytics dashboard', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve dashboard data',
            ], 500);
        }
    }

    /**
     * Get template performance report
     */
    public function generateTemplateReport(Request $request, int $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'period' => 'sometimes|string|in:daily,weekly,monthly,quarterly,yearly',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $period = $validated['period'] ?? 'monthly';
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            $report = $this->analyticsService->generateTemplateReport($templateId, $period, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $report,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate template report', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate report',
            ], 500);
        }
    }

    /**
     * Generate comparative analysis between templates
     */
    public function generateComparativeAnalysis(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_ids' => 'required|array|min:2|max:10',
                'template_ids.*' => 'integer|exists:templates,id',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date',
            ]);

            $templateIds = $validated['template_ids'];
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);

            $analysis = $this->analyticsService->generateComparativeAnalysis($templateIds, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analysis,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to generate comparative analysis', [
                'template_ids' => $request->input('template_ids'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate comparative analysis',
            ], 500);
        }
    }

    /**
     * Get template analytics
     */
    public function getTemplateAnalytics(Request $request, int $templateId): JsonResponse
    {
        try {
            $filters = $request->only(['start_date', 'end_date', 'tenant_id']);
            $analytics = $this->analyticsService->getTemplateAnalytics($templateId, $filters);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get template analytics', [
                'template_id' => $templateId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve template analytics',
            ], 500);
        }
    }
}