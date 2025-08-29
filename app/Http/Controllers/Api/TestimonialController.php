<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestimonialRequest;
use App\Http\Resources\TestimonialResource;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TestimonialController extends Controller
{
    public function __construct(
        protected TestimonialService $testimonialService
    ) {
        $this->middleware('auth:sanctum');
        $this->middleware('tenant.scope');
    }

    /**
     * Display a listing of testimonials with filtering
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Testimonial::class);

        $filters = $request->only([
            'audience_type',
            'industry', 
            'graduation_year',
            'graduation_year_range',
            'status',
            'featured',
            'has_video',
            'sort_by'
        ]);

        $perPage = min($request->get('per_page', 15), 100);
        $testimonials = $this->testimonialService->getTestimonials($filters, $perPage);

        return TestimonialResource::collection($testimonials);
    }

    /**
     * Get testimonials for rotation/display
     */
    public function rotation(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'audience_type',
            'industry',
            'graduation_year_range'
        ]);

        $limit = min($request->get('limit', 10), 50);
        $testimonials = $this->testimonialService->getTestimonialsForRotation($filters, $limit);

        return TestimonialResource::collection($testimonials);
    }

    /**
     * Store a newly created testimonial
     */
    public function store(TestimonialRequest $request): TestimonialResource
    {
        Gate::authorize('create', Testimonial::class);

        $testimonial = $this->testimonialService->createTestimonial($request->validated());

        return new TestimonialResource($testimonial);
    }

    /**
     * Display the specified testimonial
     */
    public function show(Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('view', $testimonial);

        // Track view for analytics
        $this->testimonialService->trackView($testimonial);

        return new TestimonialResource($testimonial);
    }

    /**
     * Update the specified testimonial
     */
    public function update(TestimonialRequest $request, Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('update', $testimonial);

        $testimonial = $this->testimonialService->updateTestimonial($testimonial, $request->validated());

        return new TestimonialResource($testimonial);
    }

    /**
     * Remove the specified testimonial
     */
    public function destroy(Testimonial $testimonial): JsonResponse
    {
        Gate::authorize('delete', $testimonial);

        $this->testimonialService->deleteTestimonial($testimonial);

        return response()->json(['message' => 'Testimonial deleted successfully']);
    }

    /**
     * Approve a testimonial
     */
    public function approve(Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('moderate', $testimonial);

        $this->testimonialService->approveTestimonial($testimonial);

        return new TestimonialResource($testimonial->fresh());
    }

    /**
     * Reject a testimonial
     */
    public function reject(Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('moderate', $testimonial);

        $this->testimonialService->rejectTestimonial($testimonial);

        return new TestimonialResource($testimonial->fresh());
    }

    /**
     * Archive a testimonial
     */
    public function archive(Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('moderate', $testimonial);

        $this->testimonialService->archiveTestimonial($testimonial);

        return new TestimonialResource($testimonial->fresh());
    }

    /**
     * Set testimonial as featured
     */
    public function setFeatured(Request $request, Testimonial $testimonial): TestimonialResource
    {
        Gate::authorize('moderate', $testimonial);

        $request->validate([
            'featured' => 'required|boolean'
        ]);

        $this->testimonialService->setFeatured($testimonial, $request->boolean('featured'));

        return new TestimonialResource($testimonial->fresh());
    }

    /**
     * Track testimonial click
     */
    public function trackClick(Testimonial $testimonial): JsonResponse
    {
        // No authorization needed for public tracking
        $this->testimonialService->trackClick($testimonial);

        return response()->json(['message' => 'Click tracked successfully']);
    }

    /**
     * Get performance analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        Gate::authorize('viewAnalytics', Testimonial::class);

        $filters = $request->only([
            'audience_type',
            'industry',
            'date_range'
        ]);

        $analytics = $this->testimonialService->getPerformanceAnalytics($filters);

        return response()->json($analytics);
    }

    /**
     * Get filter options
     */
    public function filterOptions(): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $options = $this->testimonialService->getFilterOptions($tenantId);

        return response()->json($options);
    }

    /**
     * Export testimonials
     */
    public function export(Request $request): JsonResponse
    {
        Gate::authorize('export', Testimonial::class);

        $filters = $request->only([
            'audience_type',
            'industry',
            'graduation_year',
            'graduation_year_range',
            'status',
            'featured',
            'has_video'
        ]);

        $testimonials = $this->testimonialService->exportTestimonials($filters);

        return response()->json([
            'data' => $testimonials,
            'count' => count($testimonials),
            'exported_at' => now()->toISOString(),
        ]);
    }

    /**
     * Import testimonials
     */
    public function import(Request $request): JsonResponse
    {
        Gate::authorize('import', Testimonial::class);

        $request->validate([
            'testimonials' => 'required|array|min:1|max:1000',
            'testimonials.*.author_name' => 'required|string|max:255',
            'testimonials.*.content' => 'required|string|min:10|max:2000',
            'testimonials.*.audience_type' => 'required|in:individual,institution,employer',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $results = $this->testimonialService->importTestimonials($request->testimonials, $tenantId);

        return response()->json([
            'message' => 'Import completed',
            'success_count' => $results['success'],
            'error_count' => count($results['errors']),
            'errors' => $results['errors'],
        ]);
    }
}