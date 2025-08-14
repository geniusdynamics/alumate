<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MessagingController extends Controller
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'conversation_id' => 'required|integer|exists:conversations,id',
                'content' => 'required|string|max:10000',
                'type' => 'sometimes|string|in:text,image,file,system',
                'attachments' => 'sometimes|array',
                'attachments.*' => 'string',
                'reply_to_id' => 'sometimes|integer|exists:messages,id',
            ]);

            $message = $this->messagingService->sendMessage(
                $request->user(),
                $validated['conversation_id'],
                $validated['content'],
                $validated['type'] ?? 'text',
                $validated['attachments'] ?? null,
                $validated['reply_to_id'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => $message->load(['user', 'replyTo.user']),
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
     * Mark message as read
     */
    public function markAsRead(Request $request, int $messageId): JsonResponse
    {
        try {
            $this->messagingService->markMessageAsRead($request->user(), $messageId);

            return response()->json([
                'success' => true,
                'message' => 'Message marked as read',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mark conversation as read
     */
    public function markConversationAsRead(Request $request, int $conversationId): JsonResponse
    {
        try {
            $this->messagingService->markConversationAsRead($request->user(), $conversationId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as read',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Send typing indicator
     */
    public function typing(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'conversation_id' => 'required|integer|exists:conversations,id',
                'is_typing' => 'required|boolean',
            ]);

            $this->messagingService->sendTypingIndicator(
                $request->user(),
                $validated['conversation_id'],
                $validated['is_typing']
            );

            return response()->json([
                'success' => true,
                'message' => 'Typing indicator sent',
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
     * Search messages
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => 'required|string|min:2|max:255',
                'conversation_id' => 'sometimes|integer|exists:conversations,id',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $messages = $this->messagingService->searchMessages(
                $request->user(),
                $validated['query'],
                $validated['conversation_id'] ?? null,
                $validated['per_page'] ?? 20
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
     * Edit message
     */
    public function editMessage(Request $request, int $messageId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:10000',
            ]);

            $message = $this->messagingService->editMessage(
                $request->user(),
                $messageId,
                $validated['content']
            );

            return response()->json([
                'success' => true,
                'message' => $message->load(['user', 'replyTo.user']),
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
     * Delete message
     */
    public function deleteMessage(Request $request, int $messageId): JsonResponse
    {
        try {
            $this->messagingService->deleteMessage($request->user(), $messageId);

            return response()->json([
                'success' => true,
                'message' => 'Message deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get unread message count
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        try {
            $count = $this->messagingService->getUnreadCount($request->user());

            return response()->json([
                'success' => true,
                'unread_count' => $count,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
