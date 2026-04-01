<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomTrackRequest;
use App\Http\Requests\DefineEventRequest;
use App\Http\Requests\FunnelRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\CustomEvent;
use App\Models\CustomEventDefinition;
use App\Services\Analytics\BehaviorFlowService;
use App\Services\Analytics\CustomEventTrackingService;
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
        private readonly CustomEventTrackingService $customEventService,
        private readonly CustomEventTrackingService $customEventTrackingService,
        private readonly BehaviorFlowService $behaviorFlowService
    ) {}

    /**
     * Retrieve paginated list of event definitions with aggregates
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
     */
    public function store(DefineEventRequest $request): JsonResponse
    {
        return $this->storeDefinition($request);
    }

    /**
     * Define a new custom event (alias for store)
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
     * Get a specific custom event definition
     */
    public function show(int $id): JsonResponse
    {
        try {
            $definition = CustomEventDefinition::byTenant($this->getCurrentTenantId())
                ->findOrFail($id);

            // Get aggregate data
            $aggregates = $this->customEventService->aggregateEvents($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'definition' => $definition,
                    'aggregates' => $aggregates ?: [],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve custom event definition', [
                'error' => $e->getMessage(),
                'definition_id' => $id,
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve custom event definition',
            ], 500);
        }
    }

    /**
     * Update a custom event definition
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $definition = CustomEventDefinition::byTenant($this->getCurrentTenantId())
                ->findOrFail($id);

            // Update the definition
            $definition->update([
                'name' => $validated['name'] ?? $definition->name,
                'description' => $validated['description'] ?? $definition->description,
                'parameters_json' => $validated['parameters_json'] ?? $definition->parameters_json,
                'status' => $validated['status'] ?? $definition->status,
            ]);

            // Clear cache
            $this->clearEventDefinitionCache($id);

            return response()->json([
                'success' => true,
                'data' => $definition->fresh(),
                'message' => 'Custom event definition updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update custom event definition', [
                'error' => $e->getMessage(),
                'definition_id' => $id,
                'user_id' => Auth::id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update custom event definition',
            ], 500);
        }
    }

    /**
     * Delete a custom event definition
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $definition = CustomEventDefinition::byTenant($this->getCurrentTenantId())
                ->findOrFail($id);

            // Delete related events first
            CustomEvent::where('definition_id', $id)->delete();

            // Soft delete the definition
            $definition->delete();

            // Clear cache
            $this->clearEventDefinitionCache($id);

            return response()->json([
                'success' => true,
                'message' => 'Custom event definition deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete custom event definition', [
                'error' => $e->getMessage(),
                'definition_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete custom event definition',
            ], 500);
        }
    }

    /**
     * Track a custom event
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
     */
    public function analyze(int $definitionId): JsonResponse
    {
        return $this->analytics($definitionId);
    }

    /**
     * Get analytics report for a specific event definition (alias for analyze)
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

    /**
     * Get behavior flow analysis for a specific event definition
     */
    public function behaviorFlow(int $definitionId): JsonResponse
    {
        try {
            $filters = request()->only(['user_id', 'start_date', 'end_date']);

            $flowData = $this->behaviorFlowService->analyzeBehaviorFlow($definitionId, $filters);

            return response()->json([
                'success' => true,
                'data' => $flowData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve behavior flow analysis', [
                'error' => $e->getMessage(),
                'definition_id' => $definitionId,
                'filters' => request()->only(['user_id', 'start_date', 'end_date']),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve behavior flow analysis',
            ], 500);
        }
    }

    /**
     * Analyze user behavior flow for a specific user
     */
    public function behaviorFlowByUser(int $userId): JsonResponse
    {
        try {
            $startDate = request()->input('start_date');
            $endDate = request()->input('end_date');

            $flowData = $this->customEventTrackingService->analyzeBehaviorFlow(
                $userId,
                $startDate,
                $endDate
            );

            return response()->json([
                'success' => true,
                'data' => $flowData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user behavior flow', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'start_date' => request()->input('start_date'),
                'end_date' => request()->input('end_date'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve user behavior flow',
            ], 500);
        }
    }

    /**
     * Analyze funnel for a sequence of events
     */
    public function funnel(FunnelRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $eventSequence = $validated['event_sequence'];
            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;

            $funnelData = $this->customEventTrackingService->createFunnel($eventSequence);

            return response()->json([
                'success' => true,
                'data' => $funnelData,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to analyze funnel', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze funnel',
            ], 500);
        }
    }

    /**
     * Get optimization suggestions based on event analysis
     */
    public function optimizationSuggestions(int $definitionId): JsonResponse
    {
        try {
            $aggregates = $this->customEventService->aggregateEvents($definitionId);
            $events = CustomEvent::where('definition_id', $definitionId)->get();

            $suggestions = $this->generateOptimizationSuggestions($events, $aggregates);

            return response()->json([
                'success' => true,
                'data' => [
                    'definition_id' => $definitionId,
                    'suggestions' => $suggestions,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate optimization suggestions', [
                'error' => $e->getMessage(),
                'definition_id' => $definitionId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate optimization suggestions',
            ], 500);
        }
    }

    /**
     * Get the current tenant ID
     */
    private function getCurrentTenantId(): int
    {
        return (int) session('tenant_id', 1);
    }

    /**
     * Clear event definition cache for a specific definition
     */
    private function clearEventDefinitionCache(int $definitionId): void
    {
        $tenantId = $this->getCurrentTenantId();
        \Illuminate\Support\Facades\Cache::forget("custom_event_definitions_{$tenantId}");
        \Illuminate\Support\Facades\Cache::forget("custom_event_aggregate_{$tenantId}_{$definitionId}");
    }

    /**
     * Generate optimization suggestions based on event data
     *
     * @param  \Illuminate\Support\Collection  $events
     */
    private function generateOptimizationSuggestions($events, array $aggregates): array
    {
        $suggestions = [];

        if ($events->isEmpty()) {
            return [
                [
                    'type' => 'info',
                    'title' => 'No Event Data',
                    'description' => 'There is no event data to analyze. Start tracking events to receive optimization suggestions.',
                    'priority' => 'low',
                ],
            ];
        }

        // Analyze engagement patterns
        $uniqueUsers = $events->unique('user_id')->count();
        $totalEvents = $events->count();

        if ($totalEvents > 0 && $uniqueUsers > 0) {
            $eventsPerUser = $totalEvents / $uniqueUsers;

            if ($eventsPerUser < 2) {
                $suggestions[] = [
                    'type' => 'improvement',
                    'title' => 'Low User Engagement',
                    'description' => 'Users are triggering this event less than twice on average. Consider reviewing the event triggers and user experience.',
                    'priority' => 'high',
                    'action' => 'Review event triggers and user flow',
                ];
            }
        }

        // Analyze time distribution
        $hourlyDistribution = $events->groupBy(function ($event) {
            return $event->timestamp->hour;
        });

        $peakHours = $hourlyDistribution->sortByDesc('count')->take(3)->keys()->toArray();

        if (! empty($peakHours)) {
            $suggestions[] = [
                'type' => 'insight',
                'title' => 'Peak Activity Hours',
                'description' => 'Most event activity occurs between '.min($peakHours).':00 and '.max($peakHours).':00. Consider scheduling campaigns or notifications during these hours.',
                'priority' => 'medium',
                'action' => 'Optimize campaign scheduling',
            ];
        }

        // Analyze parameter values if available
        if (! empty($aggregates['aggregates'])) {
            foreach ($aggregates['aggregates'] as $paramName => $paramData) {
                if (isset($paramData['type']) && $paramData['type'] === 'categorical') {
                    $topValues = $paramData['top_values'] ?? [];
                    if (! empty($topValues)) {
                        $topValue = array_key_first($topValues);
                        $topCount = $topValues[$topValue];

                        if ($topCount > ($paramData['count'] * 0.8)) {
                            $suggestions[] = [
                                'type' => 'insight',
                                'title' => 'Highly Concentrated Parameter Value',
                                'description' => "The parameter '{$paramName}' has 80%+ of events using value '{$topValue}'. This may indicate limited variation in user behavior.",
                                'priority' => 'low',
                                'action' => 'Review parameter values for anomalies',
                            ];
                        }
                    }
                }
            }
        }

        // Add general suggestions
        $suggestions[] = [
            'type' => 'recommendation',
            'title' => 'Regular Monitoring',
            'description' => 'Continue monitoring event patterns to identify trends and optimize user experience.',
            'priority' => 'low',
            'action' => 'Set up regular analytics reviews',
        ];

        return $suggestions;
    }
}
