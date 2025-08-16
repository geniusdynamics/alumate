<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventConnectionRecommendation;
use App\Models\EventHighlight;
use App\Services\EventFollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventFollowUpController extends Controller
{
    protected EventFollowUpService $followUpService;

    public function __construct(EventFollowUpService $followUpService)
    {
        $this->followUpService = $followUpService;
    }

    /**
     * Submit feedback for an event
     */
    public function submitFeedback(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'overall_rating' => 'required|integer|min:1|max:5',
            'content_rating' => 'nullable|integer|min:1|max:5',
            'organization_rating' => 'nullable|integer|min:1|max:5',
            'networking_rating' => 'nullable|integer|min:1|max:5',
            'venue_rating' => 'nullable|integer|min:1|max:5',
            'feedback_text' => 'nullable|string|max:2000',
            'feedback_categories' => 'nullable|array',
            'would_recommend' => 'boolean',
            'would_attend_again' => 'boolean',
            'improvement_suggestions' => 'nullable|array',
            'is_anonymous' => 'boolean',
        ]);

        try {
            $feedback = $this->followUpService->submitFeedback(
                $event,
                $request->user(),
                $request->all()
            );

            return response()->json([
                'message' => 'Feedback submitted successfully',
                'feedback' => $feedback,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get event feedback analytics
     */
    public function getFeedbackAnalytics(Request $request, Event $event): JsonResponse
    {
        // Check if user can view analytics (organizer or admin)
        if (! $event->canUserEdit($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $analytics = $this->followUpService->getEventFeedbackAnalytics($event);

        return response()->json($analytics);
    }

    /**
     * Create an event highlight
     */
    public function createHighlight(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['photo', 'video', 'quote', 'moment', 'achievement'])],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'media_urls' => 'nullable|array',
            'media_urls.*' => 'url',
            'metadata' => 'nullable|array',
        ]);

        try {
            $highlight = $this->followUpService->createHighlight(
                $event,
                $request->user(),
                $request->all()
            );

            return response()->json([
                'message' => 'Highlight created successfully',
                'highlight' => $highlight->load('creator'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get event highlights
     */
    public function getHighlights(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in(['photo', 'video', 'quote', 'moment', 'achievement'])],
            'featured_only' => 'boolean',
            'sort_by' => ['nullable', Rule::in(['recent', 'popular', 'featured'])],
        ]);

        $highlights = $this->followUpService->getEventHighlights($event, $request->all());

        return response()->json($highlights);
    }

    /**
     * Interact with a highlight (like, share, comment)
     */
    public function interactWithHighlight(Request $request, EventHighlight $highlight): JsonResponse
    {
        $request->validate([
            'type' => ['required', Rule::in(['like', 'share', 'comment'])],
            'content' => 'required_if:type,comment|nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        $user = $request->user();
        $type = $request->input('type');

        try {
            switch ($type) {
                case 'like':
                    $liked = $highlight->toggleLike($user);
                    $message = $liked ? 'Highlight liked' : 'Like removed';
                    break;

                case 'share':
                    $highlight->addShare($user, $request->input('metadata', []));
                    $message = 'Highlight shared';
                    break;

                case 'comment':
                    $comment = $highlight->addComment($user, $request->input('content'));
                    $message = 'Comment added';
                    break;
            }

            return response()->json([
                'message' => $message,
                'highlight' => $highlight->fresh(['creator', 'interactions.user']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to interact with highlight',
            ], 400);
        }
    }

    /**
     * Create a networking connection
     */
    public function createConnection(Request $request, Event $event): JsonResponse
    {
        $request->validate([
            'connected_user_id' => 'required|exists:users,id',
            'connection_type' => ['nullable', Rule::in(['met_at_event', 'mutual_interest', 'follow_up', 'collaboration'])],
            'connection_note' => 'nullable|string|max:500',
            'shared_interests' => 'nullable|array',
            'follow_up_requested' => 'boolean',
        ]);

        try {
            $connectedUser = \App\Models\User::findOrFail($request->input('connected_user_id'));

            $connection = $this->followUpService->createNetworkingConnection(
                $event,
                $request->user(),
                $connectedUser,
                $request->all()
            );

            return response()->json([
                'message' => 'Connection created successfully',
                'connection' => $connection->load(['user', 'connectedUser']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get networking connections for an event
     */
    public function getConnections(Request $request, Event $event): JsonResponse
    {
        $connections = $this->followUpService->getNetworkingConnections($event, $request->user());

        return response()->json($connections);
    }

    /**
     * Generate connection recommendations
     */
    public function generateRecommendations(Request $request, Event $event): JsonResponse
    {
        try {
            $recommendations = $this->followUpService->generateConnectionRecommendations(
                $event,
                $request->user()
            );

            return response()->json([
                'message' => 'Recommendations generated successfully',
                'recommendations' => $recommendations->load('recommendedUser'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate recommendations',
            ], 400);
        }
    }

    /**
     * Get connection recommendations
     */
    public function getRecommendations(Request $request, Event $event): JsonResponse
    {
        $recommendations = $this->followUpService->getConnectionRecommendations($event, $request->user());

        return response()->json($recommendations);
    }

    /**
     * Act on a connection recommendation
     */
    public function actOnRecommendation(Request $request, EventConnectionRecommendation $recommendation): JsonResponse
    {
        $request->validate([
            'action' => ['required', Rule::in(['connect', 'dismiss'])],
        ]);

        // Verify the recommendation belongs to the current user
        if ($recommendation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $action = $request->input('action');

            if ($action === 'connect') {
                // Create the actual connection
                $connection = $this->followUpService->createNetworkingConnection(
                    $recommendation->event,
                    $request->user(),
                    $recommendation->recommendedUser,
                    [
                        'connection_type' => 'mutual_interest',
                        'connection_note' => 'Connected through event recommendation',
                    ]
                );

                $recommendation->markAsConnected();
                $message = 'Connection created successfully';
            } else {
                $recommendation->dismiss();
                $message = 'Recommendation dismissed';
            }

            return response()->json([
                'message' => $message,
                'recommendation' => $recommendation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get follow-up activities
     */
    public function getFollowUpActivities(Request $request, Event $event): JsonResponse
    {
        $activities = $this->followUpService->getFollowUpActivities(
            $event,
            $request->query('user_only') ? $request->user() : null
        );

        return response()->json($activities);
    }

    /**
     * Get follow-up analytics
     */
    public function getFollowUpAnalytics(Request $request, Event $event): JsonResponse
    {
        // Check if user can view analytics
        if (! $event->canUserEdit($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $analytics = $this->followUpService->getFollowUpAnalytics($event);

        return response()->json($analytics);
    }

    /**
     * Feature/unfeature a highlight
     */
    public function toggleHighlightFeature(Request $request, EventHighlight $highlight): JsonResponse
    {
        // Check if user can manage the event
        if (! $highlight->event->canUserEdit($request->user())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($highlight->is_featured) {
            $highlight->unfeature();
            $message = 'Highlight unfeatured';
        } else {
            $highlight->feature();
            $message = 'Highlight featured';
        }

        return response()->json([
            'message' => $message,
            'highlight' => $highlight->fresh(),
        ]);
    }

    /**
     * Mark recommendation as viewed
     */
    public function markRecommendationViewed(Request $request, EventConnectionRecommendation $recommendation): JsonResponse
    {
        // Verify the recommendation belongs to the current user
        if ($recommendation->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $recommendation->markAsViewed();

        return response()->json([
            'message' => 'Recommendation marked as viewed',
            'recommendation' => $recommendation->fresh(),
        ]);
    }
}
