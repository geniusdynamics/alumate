<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Http\Requests\SessionQueryRequest;
use App\Http\Requests\StoreSessionRequest;
use App\Services\Analytics\SessionRecordingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Session Recording API Controller
 *
 * Provides RESTful endpoints for session recording functionality including
 * event tracking, session retrieval, and privacy-compliant data management.
 */
class SessionRecordingController extends Controller
{
    private const MAX_PAYLOAD_SIZE = 1048576; // 1MB

    public function __construct(
        private readonly SessionRecordingService $sessionRecordingService
    ) {}

    /**
     * Track session events with privacy validation
     */
    public function track(StoreSessionRequest $request): JsonResponse
    {
        try {
            // Validate payload size
            $payloadSize = strlen(json_encode($request->all()));
            if ($payloadSize > self::MAX_PAYLOAD_SIZE) {
                return response()->json([
                    'success' => false,
                    'error' => 'Payload size exceeds maximum limit of 1MB',
                ], 413);
            }

            $sessionId = $request->input('session_id');
            $eventData = $request->input('event_data');

            // Track each event
            $successCount = 0;
            foreach ($eventData as $event) {
                if ($this->sessionRecordingService->captureSessionEvent($sessionId, $event)) {
                    $successCount++;
                }
            }

            return response()->json([
                'success' => true,
                'processed' => $successCount,
                'total' => count($eventData),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to track session events', [
                'error' => $e->getMessage(),
                'session_id' => $request->input('session_id'),
                'events_count' => count($request->input('event_data', [])),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process session events',
            ], 500);
        }
    }

    /**
     * Retrieve session with playback data
     */
    public function show(string $sessionId): JsonResponse
    {
        try {
            $session = $this->sessionRecordingService->getSessionRecording($sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $session,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve session recording', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve session data',
            ], 500);
        }
    }

    /**
     * List sessions with filtering and pagination
     */
    public function index(SessionQueryRequest $request): JsonResponse
    {
        try {
            $filters = [];

            // Apply filters
            if ($request->has('user_segment')) {
                $filters['user_segment'] = $request->input('user_segment');
            }

            if ($request->has('device_type')) {
                $filters['device_type'] = $request->input('device_type');
            }

            if ($request->has('privacy_masked')) {
                $filters['privacy_masked'] = $request->boolean('privacy_masked');
            }

            // Date range filtering
            $startDate = null;
            $endDate = null;

            if ($request->has('date_range.from')) {
                $startDate = $request->input('date_range.from');
            }

            if ($request->has('date_range.to')) {
                $endDate = $request->input('date_range.to');
            }

            // Get sessions
            $sessions = $this->sessionRecordingService->getSessionsInRange(
                $startDate ? \Carbon\Carbon::parse($startDate) : \Carbon\Carbon::now()->subDays(30),
                $endDate ? \Carbon\Carbon::parse($endDate) : \Carbon\Carbon::now(),
                $filters
            );

            // Apply pagination
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);
            $paginated = $sessions->forPage($page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated->values(),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $sessions->count(),
                    'last_page' => ceil($sessions->count() / $perPage),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list session recordings', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve sessions',
            ], 500);
        }
    }

    /**
     * Opt-out session deletion
     */
    public function destroy(string $sessionId): JsonResponse
    {
        try {
            // Check if session exists
            $session = $this->sessionRecordingService->getSessionRecording($sessionId);

            if (! $session) {
                return response()->json([
                    'success' => false,
                    'error' => 'Session not found',
                ], 404);
            }

            // Soft delete the session recording
            $recording = \App\Models\SessionRecording::byTenant(
                session('tenant_id', 'default')
            )->bySessionId($sessionId)->first();

            if ($recording) {
                $recording->delete();

                Log::info('Session recording deleted for privacy opt-out', [
                    'session_id' => $sessionId,
                    'user_id' => $recording->user_id,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session recording has been deleted',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete session recording', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete session recording',
            ], 500);
        }
    }

    /**
     * Get session analytics summary
     */
    public function analytics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_range.from' => 'nullable|date',
            'date_range.to' => 'nullable|date',
            'user_segment' => 'nullable|in:alumni,employer,student',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $startDate = $request->input('date_range.from')
                ? \Carbon\Carbon::parse($request->input('date_range.from'))
                : \Carbon\Carbon::now()->subDays(30);

            $endDate = $request->input('date_range.to')
                ? \Carbon\Carbon::parse($request->input('date_range.to'))
                : \Carbon\Carbon::now();

            $filters = [];
            if ($request->has('user_segment')) {
                $filters['user_segment'] = $request->input('user_segment');
            }

            $sessions = $this->sessionRecordingService->getSessionsInRange($startDate, $endDate, $filters);

            $analytics = [
                'total_sessions' => $sessions->count(),
                'total_duration' => $sessions->sum('duration_seconds'),
                'total_page_views' => $sessions->sum('page_views'),
                'total_interactions' => $sessions->sum('interactions_count'),
                'privacy_masked_count' => $sessions->where('privacy_masked', true)->count(),
                'avg_session_duration' => $sessions->avg('duration_seconds') ?? 0,
                'avg_page_views' => $sessions->avg('page_views') ?? 0,
                'avg_interactions' => $sessions->avg('interactions_count') ?? 0,
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics,
                'date_range' => [
                    'from' => $startDate->toDateString(),
                    'to' => $endDate->toDateString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate session analytics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate analytics',
            ], 500);
        }
    }
}
