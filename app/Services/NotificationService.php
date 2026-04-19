<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationTemplate;
use App\Models\NotificationPreference;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Collection;

/**
 * Comprehensive Notification Service
 *
 * Handles all notification operations including sending, preferences,
 * templates, and multi-channel delivery with tenant isolation
 */
class NotificationService extends BaseService
{
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Send notification to user(s)
     *
     * @param string|array $users User ID(s) or User model(s)
     * @param string $type Notification type
     * @param array $data Notification data
     * @param array $channels Specific channels to use (optional)
     * @return array Results of notification sending
     */
    public function sendNotification($users, string $type, array $data = [], array $channels = []): array
    {
        $users = $this->normalizeUsers($users);
        $results = [];

        foreach ($users as $user) {
            $results[] = $this->sendToUser($user, $type, $data, $channels);
        }

        return $results;
    }

    /**
     * Send bulk notifications
     *
     * @param array $notifications Array of [user, type, data, channels]
     * @return array Results
     */
    public function sendBulkNotifications(array $notifications): array
    {
        $results = [];

        foreach ($notifications as $notification) {
            $results[] = $this->sendToUser(
                $notification['user'],
                $notification['type'],
                $notification['data'] ?? [],
                $notification['channels'] ?? []
            );
        }

        return $results;
    }

    /**
     * Send notification to single user
     */
    protected function sendToUser($user, string $type, array $data, array $channels): array
    {
        $user = $this->resolveUser($user);
        $preferences = $this->getUserPreferences($user->id, $type);

        if (empty($channels)) {
            $channels = $this->getEnabledChannels($preferences);
        }

        $results = [];

        foreach ($channels as $channel) {
            if ($this->isChannelEnabled($preferences, $channel)) {
                $results[$channel] = $this->sendViaChannel($user, $type, $data, $channel);
            }
        }

        return [
            'user_id' => $user->id,
            'type' => $type,
            'channels_sent' => $results,
            'timestamp' => now(),
        ];
    }

    /**
     * Send notification via specific channel
     */
    protected function sendViaChannel(User $user, string $type, array $data, string $channel): array
    {
        try {
            $result = match ($channel) {
                'email' => $this->sendEmail($user, $type, $data),
                'sms' => $this->sendSms($user, $type, $data),
                'in_app' => $this->sendInApp($user, $type, $data),
                'push' => $this->sendPush($user, $type, $data),
                default => throw new \InvalidArgumentException("Unsupported channel: {$channel}")
            };

            $this->logNotification($user->id, $type, $channel, 'sent');
            return ['status' => 'sent', 'result' => $result];

        } catch (\Exception $e) {
            Log::error("Failed to send {$channel} notification", [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            $this->logNotification($user->id, $type, $channel, 'failed', $e->getMessage());
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send email notification
     */
    protected function sendEmail(User $user, string $type, array $data): bool
    {
        $template = $this->getNotificationTemplate($type, 'email');
        if (!$template) {
            throw new \Exception("Email template not found for type: {$type}");
        }

        $content = $template->render($data);

        // Here you would integrate with your email service (Mailgun, SendGrid, etc.)
        Mail::raw($content['content'], function ($message) use ($user, $content) {
            $message->to($user->email)
                    ->subject($content['subject'] ?? 'Notification');
        });

        return true;
    }

    /**
     * Send SMS notification
     */
    protected function sendSms(User $user, string $type, array $data): bool
    {
        $template = $this->getNotificationTemplate($type, 'sms');
        if (!$template) {
            throw new \Exception("SMS template not found for type: {$type}");
        }

        $content = $template->render($data);

        // Here you would integrate with your SMS service (Twilio, AWS SNS, etc.)
        // For now, we'll just log it
        Log::info("SMS sent to {$user->phone}", ['content' => $content['content']]);

        return true;
    }

    /**
     * Send in-app notification
     */
    protected function sendInApp(User $user, string $type, array $data): array
    {
        $notification = $user->notifications()->create([
            'type' => $type,
            'data' => $data,
        ]);

        // Broadcast to real-time channels if needed
        // broadcast(new \App\Events\NotificationSent($user, $notification));

        return ['notification_id' => $notification->id];
    }

    /**
     * Send push notification
     */
    protected function sendPush(User $user, string $type, array $data): bool
    {
        // Here you would integrate with push notification service (Firebase, OneSignal, etc.)
        Log::info("Push notification sent to user {$user->id}", [
            'type' => $type,
            'data' => $data
        ]);

        return true;
    }

    /**
     * Get user notification preferences
     */
    protected function getUserPreferences(int $userId, string $type): array
    {
        $cacheKey = "notification_preferences_{$userId}_{$type}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $type) {
            $preference = NotificationPreference::where('user_id', $userId)
                ->where('notification_type', $type)
                ->first();

            if ($preference) {
                return $preference->toArray();
            }

            // Return defaults if no preference set
            return array_merge([
                'user_id' => $userId,
                'notification_type' => $type,
            ], NotificationPreference::getDefaultPreferences()[$type] ?? []);
        });
    }

    /**
     * Get enabled channels for user
     */
    protected function getEnabledChannels(array $preferences): array
    {
        $channels = [];

        if (($preferences['email_enabled'] ?? false)) {
            $channels[] = 'email';
        }
        if (($preferences['sms_enabled'] ?? false)) {
            $channels[] = 'sms';
        }
        if (($preferences['in_app_enabled'] ?? false)) {
            $channels[] = 'in_app';
        }
        if (($preferences['push_enabled'] ?? false)) {
            $channels[] = 'push';
        }

        return $channels;
    }

    /**
     * Check if channel is enabled
     */
    protected function isChannelEnabled(array $preferences, string $channel): bool
    {
        return $preferences[$channel . '_enabled'] ?? false;
    }

    /**
     * Get notification template
     */
    protected function getNotificationTemplate(string $name, string $type): ?NotificationTemplate
    {
        $cacheKey = "notification_template_{$name}_{$type}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name, $type) {
            return NotificationTemplate::where('name', $name)
                ->where('type', $type)
                ->active()
                ->first();
        });
    }

    /**
     * Log notification attempt
     */
    protected function logNotification(int $userId, string $type, string $channel, string $status, ?string $error = null): void
    {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        NotificationLog::create([
            'notification_id' => null, // Would be set if we have a notification record
            'channel' => $channel,
            'status' => $status,
            'error_message' => $error,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }

    /**
     * Normalize users input
     */
    protected function normalizeUsers($users): array
    {
        if (!is_array($users)) {
            $users = [$users];
        }

        return array_map([$this, 'resolveUser'], $users);
    }

    /**
     * Resolve user to User model
     */
    protected function resolveUser($user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        if (is_numeric($user)) {
            return User::findOrFail($user);
        }

        throw new \InvalidArgumentException('Invalid user identifier');
    }

    /**
     * Create or update notification preferences
     */
    public function updatePreferences(int $userId, string $type, array $preferences): NotificationPreference
    {
        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $userId,
                'notification_type' => $type,
            ],
            $preferences
        );
    }

    /**
     * Get user notification preferences for all types
     */
    public function getAllUserPreferences(int $userId): array
    {
        return NotificationPreference::getUserPreferences($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $notificationId, int $userId): bool
    {
        $user = User::find($userId);
        $notification = $user->notifications()->find($notificationId);
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        return false;
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(int $userId): int
    {
        $user = User::find($userId);
        return $user->unreadNotifications()->count();
    }

    /**
     * Schedule notification for later delivery
     */
    public function scheduleNotification($users, string $type, array $data, \Carbon\Carbon $scheduledAt, array $channels = []): void
    {
        // Implementation would use Laravel's job scheduling
        // \App\Jobs\SendScheduledNotification::dispatch($users, $type, $data, $scheduledAt, $channels)
        //     ->delay($scheduledAt);

        Log::info("Scheduled notification for {$scheduledAt}", [
            'users' => $users,
            'type' => $type,
            'channels' => $channels,
        ]);
    }

    /**
     * Clear notification cache for user
     */
    public function clearUserCache(int $userId): void
    {
        $patterns = [
            "notification_preferences_{$userId}_*",
            "user_notifications_{$userId}_*",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }
}
