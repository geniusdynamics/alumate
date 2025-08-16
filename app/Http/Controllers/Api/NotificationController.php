<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:50',
            'unread_only' => 'nullable|boolean',
        ]);

        try {
            $user = $request->user();
            $perPage = $request->get('per_page', 20);
            $unreadOnly = $request->boolean('unread_only');

            if ($unreadOnly) {
                $notifications = $user->unreadNotifications()
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
            } else {
                $notifications = $this->notificationService->getUserNotifications($user, $perPage);
            }

            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            $success = $this->notificationService->markAsRead($notificationId, $user);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'unread_count' => $this->notificationService->getUnreadCount($user),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found or already read',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $this->notificationService->markAllAsRead($user);

            return response()->json([
                'success' => true,
                'message' => "Marked {$count} notifications as read",
                'marked_count' => $count,
                'unread_count' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $this->notificationService->getUnreadCount($user);

            return response()->json([
                'success' => true,
                'unread_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get unread count',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's notification preferences.
     */
    public function getPreferences(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $preferences = $this->notificationService->getUserPreferences($user);

            return response()->json([
                'success' => true,
                'preferences' => $preferences,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user's notification preferences.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'email_frequency' => Rule::in(['immediate', 'daily', 'weekly']),
            'types' => 'array',
            'types.*.email' => 'boolean',
            'types.*.push' => 'boolean',
            'types.*.database' => 'boolean',
        ]);

        try {
            $user = $request->user();
            $preferences = $request->only([
                'email_enabled',
                'push_enabled',
                'email_frequency',
                'types',
            ]);

            $this->notificationService->updateUserPreferences($user, $preferences);

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'preferences' => $this->notificationService->getUserPreferences($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $stats = $this->notificationService->getNotificationStats($user);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get notification statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = $request->user();
            $notification = $user->notifications()->find($notificationId);

            if (! $notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found',
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
                'unread_count' => $this->notificationService->getUnreadCount($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test notification (for development).
     */
    public function test(Request $request): JsonResponse
    {
        if (! app()->environment('local')) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications only available in local environment',
            ], 403);
        }

        $request->validate([
            'type' => Rule::in(['post_reaction', 'post_comment', 'connection_request']),
        ]);

        try {
            $user = $request->user();
            $type = $request->get('type', 'post_reaction');

            // Create a test notification based on type
            switch ($type) {
                case 'post_reaction':
                    $notification = new \App\Notifications\PostReactionNotification(
                        new \App\Models\Post(['id' => 1, 'content' => 'Test post']),
                        $user,
                        'like'
                    );
                    break;
                case 'post_comment':
                    $notification = new \App\Notifications\PostCommentNotification(
                        new \App\Models\Post(['id' => 1, 'content' => 'Test post']),
                        new \App\Models\Comment(['id' => 1, 'content' => 'Test comment']),
                        $user
                    );
                    break;
                case 'connection_request':
                    $notification = new \App\Notifications\ConnectionRequestNotification(
                        $user,
                        new \App\Models\Connection(['id' => 1]),
                        'Test connection request'
                    );
                    break;
            }

            $this->notificationService->sendNotification($user, $notification);

            return response()->json([
                'success' => true,
                'message' => "Test {$type} notification sent",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
