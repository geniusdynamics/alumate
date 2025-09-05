<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublishedSite;
use App\Models\LandingPage;
use App\Models\SiteDeployment;
use App\Services\PublishingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Publishing Controller
 *
 * Handles API endpoints for static site publishing and deployment management.
 * Provides comprehensive publishing workflow with tenant isolation.
 */
class PublishingController extends Controller
{
    public function __construct(
        private PublishingService $publishingService
    ) {}

    /**
     * Get published sites for the tenant
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = PublishedSite::query()
            ->with(['landingPage', 'deployments'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) =>
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%')
            )
            ->orderBy('created_at', 'desc');

        $sites = $request->paginate ?? false
            ? $query->paginate($request->per_page ?? 15)
            : $query->get();

        return response()->json([
            'published_sites' => $sites,
            'meta' => [
                'total_published' => PublishedSite::where('status', 'published')->count(),
                'total_deploying' => PublishedSite::where('deployment_status', 'deploying')->count(),
            ]
        ]);
    }

    /**
     * Create a new published site
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'landing_page_id' => 'required|exists:landing_pages,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $landingPage = LandingPage::findOrFail($request->landing_page_id);

            // Check if site already exists for this landing page
            $existingSite = PublishedSite::where('landing_page_id', $landingPage->id)->first();
            if ($existingSite) {
                return response()->json([
                    'message' => 'Published site already exists for this landing page',
                    'published_site' => $existingSite
                ], 409);
            }

            $publishedSite = PublishedSite::create([
                'landing_page_id' => $landingPage->id,
                'tenant_id' => $landingPage->tenant_id,
                'name' => $request->name,
                'slug' => $request->slug ?? $landingPage->slug,
                'domain' => $request->domain,
                'subdomain' => $request->subdomain,
                'created_by' => optional(Auth::user())->id ?? null,
                'updated_by' => optional(Auth::user())->id ?? null,
            ]);

            return response()->json([
                'published_site' => $publishedSite->load(['landingPage']),
                'message' => 'Published site created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create published site', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'Failed to create published site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific published site
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function show(PublishedSite $publishedSite): JsonResponse
    {
        return response()->json([
            'published_site' => $publishedSite->load([
                'landingPage',
                'deployments' => fn($q) => $q->latest()->limit(10),
                'analytics' => fn($q) => $q->latest()->limit(30)
            ]),
            'performance_stats' => $publishedSite->getPerformanceStats(),
        ]);
    }

    /**
     * Update a published site
     *
     * @param Request $request
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function update(Request $request, PublishedSite $publishedSite): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'sometimes|in:' . implode(',', PublishedSite::STATUSES),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $publishedSite->update(array_merge(
            $request->validated(),
            ['updated_by' => optional(Auth::user())->id ?? null]
        ));

        return response()->json([
            'published_site' => $publishedSite->fresh(),
            'message' => 'Published site updated successfully'
        ]);
    }

    /**
     * Delete a published site
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function destroy(PublishedSite $publishedSite): JsonResponse
    {
        // Check if site is currently deploying
        if ($publishedSite->isDeploying()) {
            return response()->json([
                'message' => 'Cannot delete site that is currently deploying'
            ], 422);
        }

        $publishedSite->delete();

        return response()->json([
            'message' => 'Published site deleted successfully'
        ]);
    }

    /**
     * Publish a site
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function publish(PublishedSite $publishedSite): JsonResponse
    {
        try {
            $publishedSite->publish();

            return response()->json([
                'published_site' => $publishedSite->fresh(),
                'message' => 'Site published successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish site', [
                'site_id' => $publishedSite->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to publish site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unpublish a site
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function unpublish(PublishedSite $publishedSite): JsonResponse
    {
        try {
            $publishedSite->unpublish();

            return response()->json([
                'published_site' => $publishedSite->fresh(),
                'message' => 'Site unpublished successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to unpublish site', [
                'site_id' => $publishedSite->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to unpublish site',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deploy a site
     *
     * @param Request $request
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function deploy(Request $request, PublishedSite $publishedSite): JsonResponse
    {
        $request->validate([
            'build_options' => 'nullable|array',
            'build_options.minify' => 'boolean',
            'build_options.format' => 'in:' . implode(',', PublishingService::OUTPUT_FORMATS),
        ]);

        try {
            // Update deployment status
            $publishedSite->updateDeploymentStatus('deploying');

            // Generate static site
            $landingPage = $publishedSite->landingPage;
            $buildData = $this->publishingService->generateStaticSite(
                $landingPage,
                $request->build_options ?? []
            );

            // Deploy to storage
            $deploymentResult = $this->publishingService->deploySite($publishedSite, $buildData);

            // Update deployment status
            $publishedSite->updateDeploymentStatus('deployed');

            return response()->json([
                'published_site' => $publishedSite->fresh(),
                'deployment' => $deploymentResult,
                'message' => 'Site deployed successfully'
            ]);

        } catch (\Exception $e) {
            // Update deployment status on failure
            $publishedSite->updateDeploymentStatus('failed', $e->getMessage());

            Log::error('Site deployment failed', [
                'site_id' => $publishedSite->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Site deployment failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get deployment history for a site
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function deployments(PublishedSite $publishedSite): JsonResponse
    {
        $deployments = $publishedSite->deployments()
            ->with(['publishedSite'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'deployments' => $deployments,
            'meta' => [
                'total_deployments' => $publishedSite->deployments()->count(),
                'successful_deployments' => $publishedSite->deployments()->where('status', 'deployed')->count(),
                'failed_deployments' => $publishedSite->deployments()->where('status', 'failed')->count(),
            ]
        ]);
    }

    /**
     * Get analytics for a site
     *
     * @param Request $request
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function analytics(Request $request, PublishedSite $publishedSite): JsonResponse
    {
        $query = $publishedSite->analytics();

        if ($request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $analytics = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'analytics' => $analytics,
            'summary' => [
                'total_page_views' => $analytics->sum('page_views'),
                'total_unique_visitors' => $analytics->sum('unique_visitors'),
                'avg_bounce_rate' => $analytics->avg('bounce_rate'),
                'avg_session_duration' => $analytics->avg('avg_session_duration'),
            ]
        ]);
    }

    /**
     * Preview site before deployment
     *
     * @param PublishedSite $publishedSite
     * @return JsonResponse
     */
    public function preview(PublishedSite $publishedSite): JsonResponse
    {
        try {
            $landingPage = $publishedSite->landingPage;
            $buildData = $this->publishingService->generateStaticSite($landingPage, ['preview' => true]);

            return response()->json([
                'preview_html' => $buildData['html'],
                'build_manifest' => $buildData['manifest'],
                'message' => 'Site preview generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Site preview failed', [
                'site_id' => $publishedSite->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to generate site preview',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
