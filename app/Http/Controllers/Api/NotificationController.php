<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Notification Controller
 *
 * Handles notification-related API endpoints including sending,
 * preferences management, and real-time updates
 */
class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Send a notification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'users' => 'required|array',
            'users.*' => 'integer|exists:users,id',
            'type' => 'required|string|max:255',
            'data' => 'nullable|array',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,sms,in_app,push',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->notificationService->sendNotification(
                $request->users,
                $request->type,
                $request->data ?? [],
                $request->channels ?? []
            );

            return response()->json([
                'message' => 'Notifications sent successfully',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send bulk notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notifications' => 'required|array',
            'notifications.*.user' => 'required|integer|exists:users,id',
            'notifications.*.type' => 'required|string|max:255',
            'notifications.*.data' => 'nullable|array',
            'notifications.*.channels' => 'nullable|array',
            'notifications.*.channels.*' => 'in:email,sms,in_app,push',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->notificationService->sendBulkNotifications($request->notifications);

            return response()->json([
                'message' => 'Bulk notifications sent successfully',
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send bulk notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'read' => 'nullable|boolean',
            'type' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $user->notifications()->where('tenant_id', $user->tenant_id ?? null);

        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $this->notificationService->getUnreadCount($user->id),
        ]);
    }

    /**
     * Mark notification as read
     *
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(string $id): JsonResponse
    {
        $user = Auth::user();

        if ($this->notificationService->markAsRead($id, $user->id)) {
            return response()->json([
                'message' => 'Notification marked as read'
            ]);
        }

        return response()->json([
            'message' => 'Notification not found'
        ], 404);
    }

    /**
     * Mark all notifications as read
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read'
        ]);
    }

    /**
     * Get notification preferences
     *
     * @return JsonResponse
     */
    public function getPreferences(): JsonResponse
    {
        $user = Auth::user();
        $preferences = $this->notificationService->getAllUserPreferences($user->id);

        return response()->json([
            'preferences' => $preferences
        ]);
    }

    /**
     * Update notification preferences
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'push_enabled' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        try {
            $preference = $this->notificationService->updatePreferences(
                $user->id,
                $request->type,
                $request->only(['email_enabled', 'sms_enabled', 'in_app_enabled', 'push_enabled'])
            );

            return response()->json([
                'message' => 'Notification preferences updated successfully',
                'preference' => $preference
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTemplates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:email,sms,in_app,push',
            'active_only' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = NotificationTemplate::query();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->boolean('active_only', true)) {
            $query->active();
        }

        $templates = $query->orderBy('name')->get();

        return response()->json([
            'templates' => $templates
        ]);
    }

    /**
     * Get notification statistics
     *
     * @return JsonResponse
     */
    public function getStats(): JsonResponse
    {
        $user = Auth::user();

        $tenantId = $user->tenant_id ?? null;
        $stats = [
            'total_notifications' => $user->notifications()->where('tenant_id', $tenantId)->count(),
            'unread_count' => $user->unreadNotifications()->where('tenant_id', $tenantId)->count(),
            'read_count' => $user->notifications()->where('tenant_id', $tenantId)->whereNotNull('read_at')->count(),
            'today_count' => $user->notifications()->where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
            'this_week_count' => $user->notifications()->where('tenant_id', $tenantId)->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
        ];

        return response()->json([
            'stats' => $stats
        ]);
    }

    /**
     * Delete notification
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully'
        ]);
    }

    /**
     * Schedule notification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function schedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'users' => 'required|array',
            'users.*' => 'integer|exists:users,id',
            'type' => 'required|string|max:255',
            'data' => 'nullable|array',
            'channels' => 'nullable|array',
            'channels.*' => 'in:email,sms,in_app,push',
            'scheduled_at' => 'required|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $this->notificationService->scheduleNotification(
                $request->users,
                $request->type,
                $request->data ?? [],
                \Carbon\Carbon::parse($request->scheduled_at),
                $request->channels ?? []
            );

            return response()->json([
                'message' => 'Notification scheduled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to schedule notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
