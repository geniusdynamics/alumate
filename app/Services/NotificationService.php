<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send notification to user via multiple channels.
     */
    public function sendNotification(User $user, $notification): void
    {
        $preferences = $this->getUserPreferences($user);
        
        // Always send database notification
        $user->notify($notification);
        
        // Send email if user has email notifications enabled
        if ($preferences['email_enabled'] && $this->shouldSendEmail($notification, $preferences)) {
            $user->notify($notification->via(['mail']));
        }
        
        // Send push notification if user has push enabled
        if ($preferences['push_enabled'] && $this->shouldSendPush($notification, $preferences)) {
            $this->sendPushNotification($user, $notification);
        }
        
        // Broadcast real-time notification
        $this->broadcastNotification($user, $notification);
        
        // Clear unread count cache
        $this->clearUnreadCountCache($user);
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(string $notificationId, User $user): bool
    {
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
            $this->clearUnreadCountCache($user);
            return true;
        }
        
        return false;
    }

    /**
     * Mark all notifications as read for user.
     */
    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications()->update(['read_at' => now()]);
        $this->clearUnreadCountCache($user);
        
        return $count;
    }

    /**
     * Get count of unread notifications for user.
     */
    public function getUnreadCount(User $user): int
    {
        $cacheKey = "unread_notifications_count_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->unreadNotifications()->count();
        });
    }

    /**
     * Get paginated notifications for user.
     */
    public function getUserNotifications(User $user, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user's notification preferences.
     */
    public function getUserPreferences(User $user): array
    {
        $cacheKey = "notification_preferences_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $preferences = $user->notification_preferences ?? [];
            
            // Default preferences
            return array_merge([
                'email_enabled' => true,
                'push_enabled' => true,
                'email_frequency' => 'immediate', // immediate, daily, weekly
                'types' => [
                    'post_reaction' => ['email' => true, 'push' => true, 'database' => true],
                    'post_comment' => ['email' => true, 'push' => true, 'database' => true],
                    'post_mention' => ['email' => true, 'push' => true, 'database' => true],
                    'connection_request' => ['email' => true, 'push' => true, 'database' => true],
                    'connection_accepted' => ['email' => true, 'push' => true, 'database' => true],
                    'group_invitation' => ['email' => true, 'push' => false, 'database' => true],
                    'event_reminder' => ['email' => true, 'push' => true, 'database' => true],
                ]
            ], $preferences);
        });
    }

    /**
     * Update user's notification preferences.
     */
    public function updateUserPreferences(User $user, array $preferences): void
    {
        $user->update(['notification_preferences' => $preferences]);
        $this->clearPreferencesCache($user);
    }

    /**
     * Delete old notifications.
     */
    public function cleanupOldNotifications(int $daysOld = 90): int
    {
        return DatabaseNotification::where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    /**
     * Get notification statistics for user.
     */
    public function getNotificationStats(User $user): array
    {
        $stats = $user->notifications()
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread'),
                DB::raw('COUNT(CASE WHEN read_at IS NOT NULL THEN 1 END) as read')
            )
            ->first();

        $typeStats = $user->notifications()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total' => $stats->total ?? 0,
            'unread' => $stats->unread ?? 0,
            'read' => $stats->read ?? 0,
            'by_type' => $typeStats
        ];
    }

    /**
     * Send bulk notifications to multiple users.
     */
    public function sendBulkNotification(array $users, $notification): void
    {
        Notification::send($users, $notification);
        
        // Clear cache for all users
        foreach ($users as $user) {
            $this->clearUnreadCountCache($user);
        }
    }

    /**
     * Check if email should be sent based on preferences.
     */
    private function shouldSendEmail($notification, array $preferences): bool
    {
        $notificationType = $this->getNotificationType($notification);
        
        if (!isset($preferences['types'][$notificationType])) {
            return false;
        }
        
        return $preferences['types'][$notificationType]['email'] ?? false;
    }

    /**
     * Check if push notification should be sent.
     */
    private function shouldSendPush($notification, array $preferences): bool
    {
        $notificationType = $this->getNotificationType($notification);
        
        if (!isset($preferences['types'][$notificationType])) {
            return false;
        }
        
        return $preferences['types'][$notificationType]['push'] ?? false;
    }

    /**
     * Send push notification to user.
     */
    private function sendPushNotification(User $user, $notification): void
    {
        // This would integrate with a push notification service like FCM
        // For now, we'll just log it
        \Log::info('Push notification sent', [
            'user_id' => $user->id,
            'notification_type' => get_class($notification)
        ]);
    }

    /**
     * Broadcast real-time notification.
     */
    private function broadcastNotification(User $user, $notification): void
    {
        // This would broadcast via WebSocket/Pusher
        // For now, we'll just log it
        \Log::info('Real-time notification broadcasted', [
            'user_id' => $user->id,
            'notification_type' => get_class($notification)
        ]);
    }

    /**
     * Get notification type from notification class.
     */
    private function getNotificationType($notification): string
    {
        $className = get_class($notification);
        $shortName = class_basename($className);
        
        // Convert class name to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace('Notification', '', $shortName)));
    }

    /**
     * Clear unread count cache for user.
     */
    private function clearUnreadCountCache(User $user): void
    {
        Cache::forget("unread_notifications_count_{$user->id}");
    }

    /**
     * Clear preferences cache for user.
     */
    private function clearPreferencesCache(User $user): void
    {
        Cache::forget("notification_preferences_{$user->id}");
    }
}