<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

class RealtimeService
{
    private ?Pusher $pusher = null;

    public function __construct()
    {
        $this->initializePusher();
    }

    /**
     * Initialize Pusher client.
     */
    private function initializePusher(): void
    {
        try {
            $this->pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => true,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to initialize Pusher', [
                'error' => $e->getMessage(),
            ]);
            $this->pusher = null;
        }
    }

    /**
     * Check if real-time service is available.
     */
    public function isAvailable(): bool
    {
        return $this->pusher !== null;
    }

    /**
     * Broadcast an event to a channel.
     */
    public function broadcast(string $channel, string $event, array $data): bool
    {
        if (!$this->pusher) {
            Log::warning('Realtime service not available, event not broadcasted', [
                'channel' => $channel,
                'event' => $event,
            ]);
            return false;
        }

        try {
            $this->pusher->trigger($channel, $event, $data);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to broadcast event', [
                'channel' => $channel,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Broadcast to a tenant-specific channel.
     */
    public function broadcastToTenant(Tenant $tenant, string $event, array $data): bool
    {
        $channel = 'tenant.' . $tenant->id;
        return $this->broadcast($channel, $event, $data);
    }

    /**
     * Broadcast to a user-specific channel.
     */
    public function broadcastToUser(User $user, string $event, array $data): bool
    {
        $channel = 'user.' . $user->id;
        return $this->broadcast($channel, $event, $data);
    }

    /**
     * Broadcast a notification.
     */
    public function broadcastNotification(User $user, array $notification): bool
    {
        return $this->broadcastToUser($user, 'notification', [
            'notification' => $notification,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast a new message.
     */
    public function broadcastMessage(int $conversationId, array $message): bool
    {
        $channel = 'conversation.' . $conversationId;
        return $this->broadcast($channel, 'message.new', [
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast typing indicator.
     */
    public function broadcastTyping(int $conversationId, User $user, bool $isTyping): bool
    {
        $channel = 'conversation.' . $conversationId;
        return $this->broadcast($channel, 'typing', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'is_typing' => $isTyping,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast presence update.
     */
    public function broadcastPresence(User $user, string $status): bool
    {
        if (!$user->currentTenant) {
            return false;
        }

        $channel = 'presence.tenant.' . $user->currentTenant->id;
        return $this->broadcast($channel, 'presence.update', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'status' => $status, // online, away, offline
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast a post update.
     */
    public function broadcastPost(Tenant $tenant, string $action, array $post): bool
    {
        return $this->broadcastToTenant($tenant, 'post.' . $action, [
            'post' => $post,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast a comment.
     */
    public function broadcastComment(int $postId, array $comment): bool
    {
        $channel = 'post.' . $postId;
        return $this->broadcast($channel, 'comment.new', [
            'comment' => $comment,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast a reaction/like.
     */
    public function broadcastReaction(int $postId, User $user, string $reactionType): bool
    {
        $channel = 'post.' . $postId;
        return $this->broadcast($channel, 'reaction.new', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'type' => $reactionType,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast event update.
     */
    public function broadcastEvent(Tenant $tenant, string $action, array $event): bool
    {
        return $this->broadcastToTenant($tenant, 'event.' . $action, [
            'event' => $event,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast job posting update.
     */
    public function broadcastJob(Tenant $tenant, string $action, array $job): bool
    {
        return $this->broadcastToTenant($tenant, 'job.' . $action, [
            'job' => $job,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Broadcast dashboard update.
     */
    public function broadcastDashboardUpdate(User $user, array $data): bool
    {
        return $this->broadcastToUser($user, 'dashboard.update', [
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get channel authentication signature.
     */
    public function auth(string $channel, string $socketId): ?string
    {
        if (!$this->pusher) {
            return null;
        }

        try {
            return $this->pusher->socketAuth($channel, $socketId);
        } catch (\Exception $e) {
            Log::error('Channel auth failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get presence channel authentication.
     */
    public function presenceAuth(string $channel, string $socketId, User $user): ?string
    {
        if (!$this->pusher) {
            return null;
        }

        try {
            $userData = [
                'user_id' => $user->id,
                'user_info' => [
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ],
            ];

            return $this->pusher->presenceAuth($channel, $socketId, $user->id, $userData);
        } catch (\Exception $e) {
            Log::error('Presence auth failed', [
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get Pusher app key for client.
     */
    public function getPublicConfig(): array
    {
        return [
            'key' => config('broadcasting.connections.pusher.key'),
            'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            'forceTLS' => true,
            'authEndpoint' => '/broadcasting/auth',
        ];
    }
}
