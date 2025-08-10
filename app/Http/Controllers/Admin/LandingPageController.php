<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LandingPageService;
use App\Models\LandingPage;
use App\Models\LandingPageTemplate;
use App\Models\LandingPageComponent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService
    ) {}

    /**
     * Display landing page builder dashboard
     */
    public function index(): Response
    {
        $landingPages = LandingPage::with(['creator', 'template'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_pages' => LandingPage::count(),
            'published_pages' => LandingPage::published()->count(),
            'draft_pages' => LandingPage::draft()->count(),
            'total_submissions' => \App\Models\LandingPageSubmission::count(),
        ];

        return Inertia::render('Admin/LandingPage/Index', [
            'landingPages' => $landingPages,
            'stats' => $stats,
        ]);
    }

    /**
     * Show the landing page builder
     */
    public function create(): Response
    {
        $templates = LandingPageTemplate::active()
            ->orderBy('usage_count', 'desc')
            ->get();

        $components = LandingPageComponent::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return Inertia::render('Admin/LandingPage/Builder', [
            'templates' => $templates,
            'components' => $components,
            'audiences' => $this->getAudienceOptions(),
            'campaignTypes' => $this->getCampaignTypeOptions(),
        ]);
    }

    /**
     * Store a new landing page
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_audience' => 'required|in:institution,employer,partner,alumni,general',
            'campaign_type' => 'required|in:onboarding,marketing,event,product_launch,trial,demo',
            'campaign_name' => 'nullable|string|max:255',
            'content' => 'required|array',
            'settings' => 'nullable|array',
            'form_config' => 'nullable|array',
            'template_id' => 'nullable|exists:landing_page_templates,id',
        ]);

        try {
            $landingPage = $this->landingPageService->createLandingPage($validated);

            return response()->json([
                'success' => true,
                'message' => 'Landing page created successfully',
                'landing_page' => $landingPage->load(['creator', 'template']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show landing page details
     */
    public function show(LandingPage $landingPage): Response
    {
        $landingPage->load(['creator', 'template', 'submissions.lead', 'analytics']);

        $analytics = [
            'total_views' => $landingPage->analytics()->byEventType('page_view')->count(),
            'total_submissions' => $landingPage->submissions()->count(),
            'conversion_rate' => $landingPage->conversion_rate,
            'recent_submissions' => $landingPage->recentSubmissions()->with('lead')->get(),
        ];

        return Inertia::render('Admin/LandingPage/Show', [
            'landingPage' => $landingPage,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Show the landing page editor
     */
    public function edit(LandingPage $landingPage): Response
    {
        $components = LandingPageComponent::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return Inertia::render('Admin/LandingPage/Builder', [
            'landingPage' => $landingPage,
            'components' => $components,
            'audiences' => $this->getAudienceOptions(),
            'campaignTypes' => $this->getCampaignTypeOptions(),
            'isEditing' => true,
        ]);
    }

    /**
     * Update landing page
     */
    public function update(Request $request, LandingPage $landingPage): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_audience' => 'sometimes|in:institution,employer,partner,alumni,general',
            'campaign_type' => 'sometimes|in:onboarding,marketing,event,product_launch,trial,demo',
            'campaign_name' => 'nullable|string|max:255',
            'content' => 'sometimes|array',
            'settings' => 'nullable|array',
            'form_config' => 'nullable|array',
        ]);

        try {
            $updatedPage = $this->landingPageService->updateLandingPage($landingPage, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Landing page updated successfully',
                'landing_page' => $updatedPage->load(['creator', 'template']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish landing page
     */
    public function publish(LandingPage $landingPage): JsonResponse
    {
        try {
            $landingPage->publish();

            return response()->json([
                'success' => true,
                'message' => 'Landing page published successfully',
                'landing_page' => $landingPage->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unpublish landing page
     */
    public function unpublish(LandingPage $landingPage): JsonResponse
    {
        try {
            $landingPage->unpublish();

            return response()->json([
                'success' => true,
                'message' => 'Landing page unpublished successfully',
                'landing_page' => $landingPage->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate landing page
     */
    public function duplicate(Request $request, LandingPage $landingPage): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $duplicatedPage = $this->landingPageService->duplicateLandingPage(
                $landingPage,
                $validated['name'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Landing page duplicated successfully',
                'landing_page' => $duplicatedPage->load(['creator', 'template']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete landing page
     */
    public function destroy(LandingPage $landingPage): JsonResponse
    {
        try {
            $landingPage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Landing page deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete landing page: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get landing page analytics
     */
    public function analytics(LandingPage $landingPage): JsonResponse
    {
        $analytics = $landingPage->getAnalyticsSummary();

        return response()->json([
            'success' => true,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get templates
     */
    public function getTemplates(): JsonResponse
    {
        $templates = LandingPageTemplate::active()
            ->orderBy('usage_count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Get components
     */
    public function getComponents(): JsonResponse
    {
        $components = LandingPageComponent::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'components' => $components,
        ]);
    }

    /**
     * Get audience options
     */
    private function getAudienceOptions(): array
    {
        return [
            'institution' => 'Educational Institutions',
            'employer' => 'Employers',
            'partner' => 'Partners',
            'alumni' => 'Alumni',
            'general' => 'General Public',
        ];
    }

    /**
     * Get campaign type options
     */
    private function getCampaignTypeOptions(): array
    {
        return [
            'onboarding' => 'Onboarding Campaign',
            'marketing' => 'Marketing Campaign',
            'event' => 'Event Promotion',
            'product_launch' => 'Product Launch',
            'trial' => 'Free Trial',
            'demo' => 'Demo Request',
        ];
    }
}
