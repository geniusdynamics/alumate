<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackTouchRequest;
use App\Models\AttributionTouch;
use App\Services\Analytics\AttributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Attribution Analytics API Controller
 *
 * Provides RESTful endpoints for attribution modeling, including touch tracking,
 * attribution calculation, and reporting for marketing analytics.
 */
class AttributionController extends Controller
{
    public function __construct(
        private readonly AttributionService $attributionService
    ) {}

    /**
     * Retrieve paginated list of attribution touches with optional filtering
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $page = request()->input('page', 1);
            $perPage = request()->input('per_page', 50);
            $userId = request()->input('user_id');
            $source = request()->input('source');
            $startDate = request()->input('start_date');
            $endDate = request()->input('end_date');
            $model = request()->input('model', 'last_touch');

            $query = AttributionTouch::byTenant($tenantId)
                ->with(['user:id,name,email'])
                ->latest();

            // Apply filters
            if ($userId) {
                $query->byUser($userId);
            }

            if ($source) {
                $query->where('source', $source);
            }

            if ($startDate && $endDate) {
                $query->byPeriod($startDate, $endDate);
            }

            $touches = $query->paginate($perPage, ['*'], 'page', $page);

            // Calculate attribution for the filtered data if user_id is specified
            $attribution = null;
            if ($userId && $startDate && $endDate) {
                $attribution = $this->attributionService->calculateAttribution(
                    (int) $userId,
                    $startDate,
                    $endDate,
                    $model
                );
            }

            return response()->json([
                'success' => true,
                'data' => $touches->items(),
                'attribution' => $attribution,
                'pagination' => [
                    'current_page' => $touches->currentPage(),
                    'per_page' => $touches->perPage(),
                    'total' => $touches->total(),
                    'last_page' => $touches->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution touches', [
                'error' => $e->getMessage(),
                'tenant_id' => session('tenant_id'),
                'filters' => request()->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution touches',
            ], 500);
        }
    }

    /**
     * Track a new attribution touch
     *
     * @param TrackTouchRequest $request
     * @return JsonResponse
     */
    public function store(TrackTouchRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Add user_id from authenticated user if not provided
            if (!isset($validated['user_id'])) {
                $validated['user_id'] = auth()->id();
            }

            // Track the touch using service
            $touch = $this->attributionService->trackTouch($validated);

            return response()->json([
                'success' => true,
                'data' => $touch->load(['user:id,name,email']),
                'message' => 'Attribution touch tracked successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to track attribution touch', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to track attribution touch',
            ], 500);
        }
    }

    /**
     * Get attribution report for a specific user
     *
     * @param int|null $userId
     * @return JsonResponse
     */
    public function show(?int $userId = null): JsonResponse
    {
        try {
            $tenantId = session('tenant_id', 'default');
            $startDate = request()->input('start_date', now()->subDays(30)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $model = request()->input('model', 'last_touch');

            // Use authenticated user if no user_id provided
            $targetUserId = $userId ?: auth()->id();

            if (!$targetUserId) {
                return response()->json([
                    'success' => false,
                    'error' => 'User ID is required',
                ], 400);
            }

            // Get attribution calculation
            $attribution = $this->attributionService->calculateAttribution(
                $targetUserId,
                $startDate,
                $endDate,
                $model
            );

            // Get touch history
            $touchHistory = $this->attributionService->getTouchHistory($targetUserId, 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'attribution' => $attribution ?: [],
                    'touch_history' => $touchHistory,
                    'period' => ['start' => $startDate, 'end' => $endDate],
                    'model' => $model,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution report', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution report',
            ], 500);
        }
    }

    /**
     * Get attribution summary for multiple users
     *
     * @return JsonResponse
     */
    public function summary(): JsonResponse
    {
        try {
            $userIds = request()->input('user_ids', []);
            $startDate = request()->input('start_date', now()->subDays(30)->toDateString());
            $endDate = request()->input('end_date', now()->toDateString());
            $model = request()->input('model', 'last_touch');

            if (empty($userIds)) {
                return response()->json([
                    'success' => false,
                    'error' => 'User IDs are required for summary',
                ], 400);
            }

            $summary = $this->attributionService->getAttributionSummary(
                $userIds,
                $startDate,
                $endDate,
                $model
            );

            return response()->json([
                'success' => true,
                'data' => $summary ?: [],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve attribution summary', [
                'error' => $e->getMessage(),
                'user_ids' => request()->input('user_ids', []),
                'tenant_id' => session('tenant_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve attribution summary',
            ], 500);
        }
    }
}