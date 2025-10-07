<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\DefineEventRequest;
use App\Http\Requests\CustomTrackRequest;
use App\Models\CustomEventDefinition;
use App\Services\Analytics\CustomEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Custom Event Analytics API Controller
 *
 * Provides RESTful endpoints for custom event tracking, including definition,
 * tracking, and analytics reporting with tenant isolation.
 */
class CustomEventController extends Controller
{
    public function __construct(
        private readonly CustomEventService $customEventService
    ) {}

    /**
     * Retrieve paginated list of event definitions with aggregates
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $page = request()->input('page', 1);
            $perPage = request()->input('per_page', 20);

            $definitions = $this->customEventService->getEventDefinitions();

            // Paginate manually since we're getting from cache
            $total = $definitions->count();
            $offset = ($page - 1) * $perPage;
            $paginatedDefinitions = $definitions->slice($offset, $perPage)->values();

            // Add aggregate data for each definition
            $paginatedDefinitions->transform(function ($definition) {
                $aggregates = $this->customEventService->aggregateEvents($definition->id);
                $definition->aggregates = $aggregates ?: [];
                return $definition;
            });

            return response()->json([
                'success' => true,
                'data' => $paginatedDefinitions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve custom event definitions', [
                'error' => $e->getMessage(),
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve custom event definitions',
            ], 500);
        }
    }

    /**
     * Define a new custom event
     *
     * @param DefineEventRequest $request
     * @return JsonResponse
     */
    public function storeDefinition(DefineEventRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $definition = $this->customEventService->defineEvent($validated);

            return response()->json([
                'success' => true,
                'data' => $definition,
                'message' => 'Custom event definition created successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create custom event definition', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create custom event definition',
            ], 500);
        }
    }

    /**
     * Track a custom event
     *
     * @param CustomTrackRequest $request
     * @return JsonResponse
     */
    public function track(CustomTrackRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $event = $this->customEventService->trackEvent($validated);

            return response()->json([
                'success' => true,
                'data' => $event,
                'message' => 'Custom event tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track custom event', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track custom event',
            ], 500);
        }
    }

    /**
     * Get analytics report for a specific event definition
     *
     * @param int $definitionId
     * @return JsonResponse
     */
    public function analytics(int $definitionId): JsonResponse
    {
        try {
            $filters = request()->only(['user_id', 'start_date', 'end_date']);

            $aggregates = $this->customEventService->aggregateEvents($definitionId, $filters);

            return response()->json([
                'success' => true,
                'data' => $aggregates ?: [],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve custom event analytics', [
                'error' => $e->getMessage(),
                'definition_id' => $definitionId,
                'filters' => request()->only(['user_id', 'start_date', 'end_date']),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve custom event analytics',
            ], 500);
        }
    }
}