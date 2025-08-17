<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ConversationController extends Controller
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Get user's conversations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $conversations = $this->messagingService->getUserConversations(
                $request->user(),
                $validated['per_page'] ?? 20
            );

            return response()->json([
                'success' => true,
                'data' => $conversations,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get messages in a conversation
     */
    public function show(Request $request, int $conversationId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $messages = $this->messagingService->getConversationMessages(
                $request->user(),
                $conversationId,
                $validated['per_page'] ?? 50
            );

            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a direct conversation
     */
    public function createDirect(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id|different:' . $request->user()->id,
            ]);

            $otherUser = \App\Models\User::findOrFail($validated['user_id']);
            $conversation = $this->messagingService->createDirectConversation($request->user(), $otherUser);

            return response()->json([
                'success' => true,
                'conversation' => $conversation->load(['participants']),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a group conversation
     */
    public function createGroup(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'participant_ids' => 'required|array|min:1|max:50',
                'participant_ids.*' => 'integer|exists:users,id|different:' . $request->user()->id,
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|max:1000',
            ]);

            $conversation = $this->messagingService->createGroupConversation(
                $request->user(),
                $validated['participant_ids'],
                $validated['title'] ?? null,
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'conversation' => $conversation->load(['participants']),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create a circle conversation
     */
    public function createCircle(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'circle_id' => 'required|integer|exists:circles,id',
                'title' => 'sometimes|string|max:255',
            ]);

            $conversation = $this->messagingService->createCircleConversation(
                $request->user(),
                $validated['circle_id'],
                $validated['title'] ?? null
            );

            return response()->json([
                'success' => true,
                'conversation' => $conversation->load(['participants', 'circle']),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add participant to conversation
     */
    public function addParticipant(Request $request, int $conversationId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'role' => 'sometimes|string|in:participant,moderator,admin',
            ]);

            $participant = $this->messagingService->addParticipant(
                $request->user(),
                $conversationId,
                $validated['user_id'],
                $validated['role'] ?? 'participant'
            );

            return response()->json([
                'success' => true,
                'participant' => $participant->load('user'),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove participant from conversation
     */
    public function removeParticipant(Request $request, int $conversationId, int $userId): JsonResponse
    {
        try {
            $this->messagingService->removeParticipant($request->user(), $conversationId, $userId);

            return response()->json([
                'success' => true,
                'message' => 'Participant removed successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Leave conversation
     */
    public function leave(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->messagingService->leaveConversation($request->user(), $conversationId);

            return response()->json([
                'success' => true,
                'message' => 'Left conversation successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Archive conversation
     */
    public function archive(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->messagingService->archiveConversation($request->user(), $conversationId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation archived successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle mute conversation
     */
    public function toggleMute(Request $request, int $conversationId): JsonResponse
    {
        try {
            $isMuted = $this->messagingService->toggleMuteConversation($request->user(), $conversationId);

            return response()->json([
                'success' => true,
                'is_muted' => $isMuted,
                'message' => $isMuted ? 'Conversation muted' : 'Conversation unmuted',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Toggle pin conversation
     */
    public function togglePin(Request $request, int $conversationId): JsonResponse
    {
        try {
            $isPinned = $this->messagingService->togglePinConversation($request->user(), $conversationId);

            return response()->json([
                'success' => true,
                'is_pinned' => $isPinned,
                'message' => $isPinned ? 'Conversation pinned' : 'Conversation unpinned',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
