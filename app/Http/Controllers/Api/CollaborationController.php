<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LandingPage;
use App\Models\PageChange;
use App\Services\CollaborationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollaborationController extends Controller
{
    public function __construct(
        private CollaborationService $collaborationService
    ) {}

    /**
     * Start a collaboration session.
     */
    public function startSession(Request $request, LandingPage $page): JsonResponse
    {
        try {
            $session = $this->collaborationService->startSession($page, $request->user());

            return response()->json([
                'success' => true,
                'data' => $session,
                'message' => 'Collaboration session started',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * End a collaboration session.
     */
    public function endSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
        ]);

        try {
            $this->collaborationService->endSession($validated['session_id']);

            return response()->json([
                'success' => true,
                'message' => 'Session ended successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to end session: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active collaboration sessions for a page.
     */
    public function activeSessions(LandingPage $page): JsonResponse
    {
        $sessions = $this->collaborationService->getActiveSessions($page);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Update session activity.
     */
    public function updateActivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'cursor_position' => 'nullable|array',
            'selected_component' => 'nullable|array',
        ]);

        try {
            $this->collaborationService->updateSessionActivity(
                $validated['session_id'],
                $validated['cursor_position'] ?? null,
                $validated['selected_component'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Activity updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update activity: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record a change operation.
     */
    public function recordChange(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'operation_type' => 'required|string|in:add,update,delete,move',
            'operation_data' => 'required|array',
            'component_id' => 'nullable|string',
            'previous_state' => 'nullable|array',
            'new_state' => 'nullable|array',
        ]);

        try {
            $change = $this->collaborationService->recordChange(
                $page,
                $request->user(),
                $validated['session_id'],
                $validated['operation_type'],
                $validated['operation_data'],
                $validated['component_id'] ?? null,
                $validated['previous_state'] ?? null,
                $validated['new_state'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => $change->load('user:id,name,email'),
                'message' => 'Change recorded',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record change: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply pending changes.
     */
    public function applyChanges(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'change_ids' => 'required|array',
            'change_ids.*' => 'integer|exists:page_changes,id',
        ]);

        try {
            $result = $this->collaborationService->applyChanges($page, $validated['change_ids']);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Changes applied',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to apply changes: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recent changes for a page.
     */
    public function recentChanges(Request $request, LandingPage $page): JsonResponse
    {
        $limit = $request->query('limit', 100);
        $changes = $this->collaborationService->getRecentChanges($page, $limit);

        return response()->json([
            'success' => true,
            'data' => $changes,
        ]);
    }

    /**
     * Get changes since a specific sequence number.
     */
    public function changesSince(Request $request, LandingPage $page): JsonResponse
    {
        $validated = $request->validate([
            'sequence_number' => 'required|integer|min:0',
        ]);

        $changes = $this->collaborationService->getChangesSince(
            $page,
            $validated['sequence_number']
        );

        return response()->json([
            'success' => true,
            'data' => $changes,
        ]);
    }

    /**
     * Resolve a conflict.
     */
    public function resolveConflict(Request $request, PageChange $change): JsonResponse
    {
        $validated = $request->validate([
            'resolution' => 'required|string|in:accept,reject,merge',
            'merged_data' => 'nullable|array',
        ]);

        try {
            $resolved = $this->collaborationService->resolveConflict(
                $change,
                $validated['resolution'],
                $validated['merged_data'] ?? null
            );

            if ($resolved) {
                return response()->json([
                    'success' => true,
                    'message' => 'Conflict resolved successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve conflict',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to resolve conflict: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user activity for a page.
     */
    public function userActivity(Request $request, LandingPage $page): JsonResponse
    {
        $hours = $request->query('hours', 24);
        $activity = $this->collaborationService->getUserActivity($page, $request->user(), $hours);

        return response()->json([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Cleanup inactive sessions (admin endpoint).
     */
    public function cleanupSessions(): JsonResponse
    {
        try {
            $cleaned = $this->collaborationService->cleanupInactiveSessions();

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$cleaned} inactive sessions",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup sessions: '.$e->getMessage(),
            ], 500);
        }
    }
}
