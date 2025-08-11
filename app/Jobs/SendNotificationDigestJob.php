<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $frequency;

    protected ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $frequency, ?int $userId = null)
    {
        $this->frequency = $frequency; // 'daily' or 'weekly'
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            if ($this->userId) {
                // Send digest for specific user
                $user = User::find($this->userId);
                if ($user) {
                    $this->sendDigestForUser($user, $notificationService);
                }
            } else {
                // Send digest for all users with this frequency preference
                $this->sendDigestForAllUsers($notificationService);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send notification digest', [
                'frequency' => $this->frequency,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send digest for all users with the specified frequency preference.
     */
    private function sendDigestForAllUsers(NotificationService $notificationService): void
    {
        $users = User::whereJsonContains('notification_preferences->email_frequency', $this->frequency)
            ->whereJsonContains('notification_preferences->email_enabled', true)
            ->chunk(100, function ($users) use ($notificationService) {
                foreach ($users as $user) {
                    $this->sendDigestForUser($user, $notificationService);
                }
            });
    }

    /**
     * Send digest for a specific user.
     */
    private function sendDigestForUser(User $user, NotificationService $notificationService): void
    {
        $preferences = $notificationService->getUserPreferences($user);

        // Skip if user doesn't want email notifications or this frequency
        if (! $preferences['email_enabled'] || $preferences['email_frequency'] !== $this->frequency) {
            return;
        }

        // Get unread notifications from the specified period
        $since = $this->frequency === 'daily' ? now()->subDay() : now()->subWeek();

        $notifications = $user->unreadNotifications()
            ->where('created_at', '>=', $since)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($notifications->isEmpty()) {
            return;
        }

        // Group notifications by type
        $groupedNotifications = $notifications->groupBy(function ($notification) {
            return $notification->data['type'] ?? 'unknown';
        });

        // Send digest email
        try {
            Mail::send('emails.notification-digest', [
                'user' => $user,
                'frequency' => $this->frequency,
                'notifications' => $notifications,
                'groupedNotifications' => $groupedNotifications,
                'totalCount' => $notifications->count(),
                'period' => $this->frequency === 'daily' ? 'yesterday' : 'this week',
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject($this->getSubject($user));
            });

            Log::info('Notification digest sent', [
                'user_id' => $user->id,
                'frequency' => $this->frequency,
                'notification_count' => $notifications->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send digest email', [
                'user_id' => $user->id,
                'frequency' => $this->frequency,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get email subject based on frequency.
     */
    private function getSubject(User $user): string
    {
        $appName = config('app.name', 'Alumni Platform');

        return match ($this->frequency) {
            'daily' => "Your daily digest from {$appName}",
            'weekly' => "Your weekly digest from {$appName}",
            default => "Your notification digest from {$appName}"
        };
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'notification-digest',
            "frequency:{$this->frequency}",
            $this->userId ? "user:{$this->userId}" : 'all-users',
        ];
    }
}
