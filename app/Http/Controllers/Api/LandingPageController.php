<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLandingPageRequest;
use App\Http\Requests\Api\UpdateLandingPageRequest;
use App\Http\Resources\LandingPageResource;
use App\Http\Resources\LandingPageAnalyticsResource;
use App\Models\LandingPage;
use App\Services\LandingPageService;
use App\Services\PublishingWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LandingPageController extends Controller
{
    public function __construct(
        private LandingPageService $landingPageService,
        private PublishingWorkflowService $publishingWorkflowService
    ) {}

    /**
     * Display a listing of landing pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = LandingPage::query();

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        if ($request->audience_type) {
            $query->where('audience_type', $request->audience_type);
        }

        if ($request->campaign_type) {
            $query->where('campaign_type', $request->campaign_type);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->template_id) {
            $query->where('template_id', $request->template_id);
        }

        // Apply sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDirection = $request->sort_direction ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Pagination
        $perPage = min((int) $request->per_page ?? 15, 100);
        $landingPages = $query->paginate($perPage);

        return response()->json([
            'landing_pages' => LandingPageResource::collection($landingPages->items()),
            'pagination' => [
                'current_page' => $landingPages->currentPage(),
                'last_page' => $landingPages->lastPage(),
                'per_page' => $landingPages->perPage(),
                'total' => $landingPages->total(),
                'from' => $landingPages->firstItem(),
                'to' => $landingPages->lastItem(),
            ],
            'filters' => [
                'statuses' => LandingPage::STATUSES,
                'categories' => LandingPage::CATEGORIES,
                'audience_types' => ['individual', 'institution', 'employer'],
                'campaign_types' => [
                    'onboarding', 'event_promotion', 'networking', 'career_services',
                    'recruiting', 'donation', 'leadership', 'marketing'
                ],
            ]
        ]);
    }

    /**
     * Display the specified landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function show(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $landingPage->load(['template', 'creator', 'updater']);

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage),
            'effective_config' => $landingPage->getEffectiveConfig(),
            'seo_metadata' => $landingPage->getSEOMetadata(),
            'performance_stats' => $landingPage->getPerformanceStats(),
            'public_urls' => [
                'preview' => $landingPage->getFullPreviewUrl(),
                'public' => $landingPage->getFullPublicUrl(),
            ],
        ]);
    }

    /**
     * Store a newly created landing page
     *
     * @param StoreLandingPageRequest $request
     * @return JsonResponse
     */
    public function store(StoreLandingPageRequest $request): JsonResponse
    {
        $landingPage = $this->landingPageService->createLandingPage(array_merge(
            $request->validated(),
            ['created_by' => Auth::id()]
        ));

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage),
            'message' => 'Landing page created successfully',
        ], 201);
    }

    /**
     * Update the specified landing page
     *
     * @param UpdateLandingPageRequest $request
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function update(UpdateLandingPageRequest $request, LandingPage $landingPage): JsonResponse
    {
        $this->authorize('update', $landingPage);

        $updatedLandingPage = $this->landingPageService->customizeContent(
            $landingPage->id,
            $request->validated()
        );

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage->fresh()),
            'message' => 'Landing page updated successfully',
        ]);
    }

    /**
     * Publish the landing page
     *
     * @param LandingPage $landingPage
     * @param Request $request
     * @return JsonResponse
     */
    public function publish(LandingPage $landingPage, Request $request): JsonResponse
    {
        $this->authorize('publish', $landingPage);

        $request->validate([
            'publish_at' => 'nullable|date|after:now',
            'custom_domain' => 'nullable|string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'public_url' => 'nullable|url',
        ]);

        try {
            $options = array_filter([
                'publish_at' => $request->publish_at,
                'custom_domain' => $request->custom_domain,
                'public_url' => $request->public_url,
            ]);

            $publishedLandingPage = $this->publishingWorkflowService->publishLandingPage($landingPage->id, $options);

            return response()->json([
                'landing_page' => new LandingPageResource($publishedLandingPage),
                'message' => 'Landing page published successfully',
                'public_url' => $publishedLandingPage->getFullPublicUrl(),
                'cache_cleared' => true,
                'version' => $publishedLandingPage->version,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to publish landing page: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unpublish the landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function unpublish(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('update', $landingPage);

        $landingPage->unpublish();

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage),
            'message' => 'Landing page unpublished successfully',
        ]);
    }

    /**
     * Remove the specified landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function destroy(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('delete', $landingPage);

        // Check if landing page has submissions
        if ($landingPage->submissions()->exists()) {
            return response()->json([
                'message' => 'Cannot delete landing page with form submissions. Archive instead.',
            ], 422);
        }

        $landingPage->delete();

        return response()->json([
            'message' => 'Landing page deleted successfully',
        ]);
    }

    /**
     * Duplicate the landing page
     *
     * @param LandingPage $landingPage
     * @param Request $request
     * @return JsonResponse
     */
    public function duplicate(LandingPage $landingPage, Request $request): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        $duplicate = $this->landingPageService->duplicate($landingPage->id, [
            'name' => $request->name,
        ]);

        return response()->json([
            'landing_page' => new LandingPageResource($duplicate),
            'message' => 'Landing page duplicated successfully',
        ], 201);
    }

    /**
     * Archive the landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function archive(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('update', $landingPage);

        $this->landingPageService->archive($landingPage->id);

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage->fresh()),
            'message' => 'Landing page archived successfully',
        ]);
    }

    /**
     * Get landing page analytics
     *
     * @param LandingPage $landingPage
     * @param Request $request
     * @return JsonResponse
     */
    public function analytics(LandingPage $landingPage, Request $request): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $timeframe = $request->timeframe ?? '30d';
        $metrics = $this->landingPageService->getPerformanceMetrics($landingPage->id, $timeframe);

        $submissions = $landingPage->submissions()
            ->where('created_at', '>=', now()->subDays($this->getDaysFromTimeframe($timeframe)))
            ->get();

        $analytics = [
            'landing_page_id' => $landingPage->id,
            'timeframe' => $timeframe,
            'metrics' => $metrics,
            'submissions_count' => $submissions->count(),
            'conversion_rate' => $metrics['page_views'] > 0
                ? round(($submissions->count() / $metrics['page_views']) * 100, 2)
                : 0,
            'submission_trends' => $this->getSubmissionTrends($submissions, $timeframe),
            'top_referrers' => $metrics['top_referrers'] ?? [],
            'device_breakdown' => $metrics['device_breakdown'] ?? [],
        ];

        return response()->json($analytics);
    }

    /**
     * Get pages by status
     *
     * @param string $status
     * @param Request $request
     * @return JsonResponse
     */
    public function byStatus(string $status, Request $request): JsonResponse
    {
        if (!in_array($status, LandingPage::STATUSES)) {
            return response()->json([
                'message' => 'Invalid status provided',
                'valid_statuses' => LandingPage::STATUSES,
            ], 422);
        }

        $landingPages = LandingPage::where('status', $status)
            ->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'status' => $status,
            'landing_pages' => LandingPageResource::collection($landingPages->items()),
            'pagination' => [
                'current_page' => $landingPages->currentPage(),
                'last_page' => $landingPages->lastPage(),
                'per_page' => $landingPages->perPage(),
                'total' => $landingPages->total(),
            ],
        ]);
    }

    /**
     * Get draft pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function drafts(Request $request): JsonResponse
    {
        $landingPages = LandingPage::drafts()
            ->orderBy('updated_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'landing_pages' => LandingPageResource::collection($landingPages->items()),
            'pagination' => [
                'current_page' => $landingPages->currentPage(),
                'last_page' => $landingPages->lastPage(),
                'per_page' => $landingPages->perPage(),
                'total' => $landingPages->total(),
            ],
        ]);
    }

    /**
     * Get published pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function published(Request $request): JsonResponse
    {
        $landingPages = LandingPage::published()
            ->orderBy('published_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'landing_pages' => LandingPageResource::collection($landingPages->items()),
            'pagination' => [
                'current_page' => $landingPages->currentPage(),
                'last_page' => $landingPages->lastPage(),
                'per_page' => $landingPages->perPage(),
                'total' => $landingPages->total(),
            ],
        ]);
    }

    /**
     * Create landing page from template
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createFromTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'template_id' => 'required|exists:templates,id',
            'name' => 'required|string|max:255',
            'customizations' => 'nullable|array',
        ]);

        $landingPage = $this->landingPageService->createFromTemplate(
            $request->template_id,
            array_merge($request->customizations ?? [], [
                'name' => $request->name,
                'created_by' => Auth::id(),
            ])
        );

        return response()->json([
            'landing_page' => new LandingPageResource($landingPage),
            'message' => 'Landing page created from template successfully',
        ], 201);
    }

    /**
     * Bulk operations on landing pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|string|in:delete,publish,unpublish,archive',
            'landing_page_ids' => 'required|array|min:1',
            'landing_page_ids.*' => 'exists:landing_pages,id'
        ]);

        $landingPages = LandingPage::whereIn('id', $request->landing_page_ids)->get();
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($landingPages as $landingPage) {
            try {
                switch ($request->action) {
                    case 'delete':
                        if (!$landingPage->submissions()->exists()) {
                            $landingPage->delete();
                            $results[] = ['id' => $landingPage->id, 'status' => 'deleted'];
                            $successCount++;
                        } else {
                            $results[] = ['id' => $landingPage->id, 'status' => 'skipped', 'reason' => 'Has submissions'];
                            $errorCount++;
                        }
                        break;
                    case 'publish':
                        $this->landingPageService->publishPage($landingPage->id);
                        $results[] = ['id' => $landingPage->id, 'status' => 'published'];
                        $successCount++;
                        break;
                    case 'unpublish':
                        $landingPage->unpublish();
                        $results[] = ['id' => $landingPage->id, 'status' => 'unpublished'];
                        $successCount++;
                        break;
                    case 'archive':
                        $this->landingPageService->archive($landingPage->id);
                        $results[] = ['id' => $landingPage->id, 'status' => 'archived'];
                        $successCount++;
                        break;
                }
            } catch (\Exception $e) {
                $results[] = ['id' => $landingPage->id, 'status' => 'error', 'message' => $e->getMessage()];
                $errorCount++;
            }
        }

        return response()->json([
            'message' => "Bulk {$request->action} operation completed",
            'results' => $results,
            'summary' => [
                'success' => $successCount,
                'errors' => $errorCount,
                'total' => count($landingPages)
            ]
        ]);
    }

    /**
     * Get submission trends data
     *
     * @param mixed $submissions
     * @param string $timeframe
     * @return array
     */
    private function getSubmissionTrends($submissions, string $timeframe): array
    {
        $days = $this->getDaysFromTimeframe($timeframe);
        $trends = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = $submissions->where('created_at', '>=', $date . ' 00:00:00')
                                ->where('created_at', '<', $date . ' 23:59:59')
                                ->count();
            $trends[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        return $trends;
    }

    /**
     * Convert timeframe to days
     *
     * @param string $timeframe
     * @return int
     */
    private function getDaysFromTimeframe(string $timeframe): int
    {
        return match ($timeframe) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };
    }

    /**
     * Get performance metrics for a landing page
     *
     * @param LandingPage $landingPage
     * @param Request $request
     * @return JsonResponse
     */
    public function performance(LandingPage $landingPage, Request $request): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $request->validate([
            'timeframe' => 'nullable|string|in:7d,30d,90d,1y',
        ]);

        $timeframe = $request->timeframe ?? '30d';
        $performance = $this->publishingWorkflowService->getLandingPagePerformance($landingPage);

        return response()->json([
            'landing_page_id' => $landingPage->id,
            'timeframe' => $timeframe,
            'performance' => $performance,
            'generated_at' => now(),
        ]);
    }

    /**
     * Get cached content for a landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function cachedContent(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $cachedContent = $this->publishingWorkflowService->getCachedLandingPageContent($landingPage);

        return response()->json([
            'cached_content' => $cachedContent,
            'cache_info' => [
                'generated_at' => now(),
                'landing_page_version' => $landingPage->version,
            ],
        ]);
    }

    /**
     * Bulk publish landing pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkPublish(Request $request): JsonResponse
    {
        $request->validate([
            'landing_page_ids' => 'required|array|min:1|max:50',
            'landing_page_ids.*' => 'exists:landing_pages,id',
            'publish_at' => 'nullable|date|after:now',
            'custom_domain' => 'nullable|string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
        ]);

        $options = array_filter([
            'publish_at' => $request->publish_at,
            'custom_domain' => $request->custom_domain,
            'tenant_id' => $request->tenant_id ?? null,
        ]);

        $results = $this->publishingWorkflowService->bulkPublish($request->landing_page_ids, $options);

        return response()->json([
            'message' => 'Bulk publish operation completed',
            'results' => $results,
            'summary' => [
                'requested' => count($request->landing_page_ids),
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'success_rate' => count($request->landing_page_ids) > 0
                    ? round((count($results['successful']) / count($request->landing_page_ids)) * 100, 1)
                    : 0,
            ],
        ]);
    }

    /**
     * Bulk unpublish landing pages
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUnpublish(Request $request): JsonResponse
    {
        $request->validate([
            'landing_page_ids' => 'required|array|min:1|max:50',
            'landing_page_ids.*' => 'exists:landing_pages,id',
        ]);

        $options = ['tenant_id' => $request->tenant_id ?? null];
        $results = $this->publishingWorkflowService->bulkUnpublish($request->landing_page_ids, $options);

        return response()->json([
            'message' => 'Bulk unpublish operation completed',
            'results' => $results,
            'summary' => [
                'requested' => count($request->landing_page_ids),
                'successful' => count($results['successful']),
                'failed' => count($results['failed']),
                'success_rate' => count($request->landing_page_ids) > 0
                    ? round((count($results['successful']) / count($request->landing_page_ids)) * 100, 1)
                    : 0,
            ],
        ]);
    }

    /**
     * Archive a landing page
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function archivePage(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('update', $landingPage);

        if ($landingPage->status === 'archived') {
            return response()->json([
                'message' => 'Landing page is already archived',
            ], 422);
        }

        try {
            $archivedLandingPage = $this->publishingWorkflowService->archiveLandingPage($landingPage->id);

            return response()->json([
                'landing_page' => new LandingPageResource($archivedLandingPage),
                'message' => 'Landing page archived successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to archive landing page: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get publishing workflow statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function publishingStats(Request $request): JsonResponse
    {
        $request->validate([
            'timeframe' => 'nullable|string|in:7d,30d,90d,1y',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $timeframe = $request->timeframe ?? '30d';
        $tenantId = $request->tenant_id ?? null;

        $query = LandingPage::query();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $stats = [
            'total_pages' => $query->count(),
            'published_pages' => (clone $query)->published()->count(),
            'draft_pages' => (clone $query)->drafts()->count(),
            'archived_pages' => (clone $query)->where('status', 'archived')->count(),
            'reviewing_pages' => (clone $query)->where('status', 'reviewing')->count(),
            'suspended_pages' => (clone $query)->where('status', 'suspended')->count(),
            'timeframe' => $timeframe,
            'generated_at' => now(),
        ];

        // Add publishing activity in the selected timeframe
        $startDate = now()->subDays($this->getDaysFromTimeframe($timeframe));
        $stats['published_recently'] = (clone $query)->where('published_at', '>=', $startDate)->count();

        return response()->json([
            'publishing_stats' => $stats,
            'filters' => [
                'tenant_id' => $tenantId,
                'timeframe' => $timeframe,
            ],
        ]);
    }

    /**
     * Get published landing page URL suggestions
     *
     * @param LandingPage $landingPage
     * @return JsonResponse
     */
    public function urlSuggestions(LandingPage $landingPage): JsonResponse
    {
        $this->authorize('view', $landingPage);

        $autoGeneratedUrls = [];

        // Try different URL generation patterns
        $tenant = $landingPage->tenant;

        // If custom domain is available
        if ($tenant && !empty($tenant->custom_domain)) {
            $autoGeneratedUrls[] = "https://{$tenant->custom_domain}/{$landingPage->slug}";
        }

        // If subdomain isolation is enabled
        if ($tenant && config('database.multi_tenant') && !empty($tenant->domain)) {
            $baseDomain = parse_url(config('app.url'), PHP_URL_HOST);
            $autoGeneratedUrls[] = "https://{$landingPage->slug}.{$baseDomain}";
        }

        // Path-based URL as fallback
        $autoGeneratedUrls[] = config('app.url') . "/p/{$landingPage->slug}";

        $suggestions = [
            'current' => $landingPage->public_url,
            'auto_generated' => $autoGeneratedUrls,
            'custom_options' => [
                'path_based' => config('app.url') . "/p/{$landingPage->slug}",
                'multi_tenant_enabled' => config('database.multi_tenant'),
            ],
            'validation_rules' => [
                'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
                'custom_domain' => 'nullable|string|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
        ];

        return response()->json($suggestions);
    }
}