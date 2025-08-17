<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Notifications\PostReactionNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $otherUser;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->notificationService = app(NotificationService::class);
    }

    /** @test */
    public function user_can_get_notifications()
    {
        // Create a notification
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'notifications' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'data',
                            'read_at',
                            'created_at',
                        ],
                    ],
                ],
                'unread_count',
            ]);
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        // Create a notification
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        $notificationId = $this->user->notifications()->first()->id;

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification marked as read',
            ]);

        // Verify notification is marked as read
        $this->assertNotNull($this->user->notifications()->first()->read_at);
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read()
    {
        // Create multiple notifications
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        for ($i = 0; $i < 3; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'marked_count' => 3,
                'unread_count' => 0,
            ]);

        // Verify all notifications are marked as read
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    /** @test */
    public function user_can_get_unread_count()
    {
        // Create notifications
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        for ($i = 0; $i < 5; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'unread_count' => 5,
            ]);
    }

    /** @test */
    public function user_can_get_notification_preferences()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/preferences');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'preferences' => [
                    'email_enabled',
                    'push_enabled',
                    'email_frequency',
                    'types',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_notification_preferences()
    {
        $preferences = [
            'email_enabled' => false,
            'push_enabled' => true,
            'email_frequency' => 'daily',
            'types' => [
                'post_reaction' => ['email' => false, 'push' => true, 'database' => true],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/notifications/preferences', $preferences);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
            ]);

        // Verify preferences were saved
        $this->user->refresh();
        $this->assertFalse($this->user->notification_preferences['email_enabled']);
        $this->assertEquals('daily', $this->user->notification_preferences['email_frequency']);
    }

    /** @test */
    public function user_can_delete_notification()
    {
        // Create a notification
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        $notificationId = $this->user->notifications()->first()->id;

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notificationId}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ]);

        // Verify notification was deleted
        $this->assertEquals(0, $this->user->notifications()->count());
    }

    /** @test */
    public function user_can_get_notification_stats()
    {
        // Create various notifications
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create some read and unread notifications
        for ($i = 0; $i < 3; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        // Mark one as read
        $this->user->notifications()->first()->markAsRead();

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'stats' => [
                    'total',
                    'unread',
                    'read',
                    'by_type',
                ],
            ]);

        $stats = $response->json('stats');
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['unread']);
        $this->assertEquals(1, $stats['read']);
    }

    /** @test */
    public function notification_service_can_send_notification()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');

        $this->notificationService->sendNotification($this->user, $notification);

        // Verify notification was created
        $this->assertEquals(1, $this->user->notifications()->count());
        $this->assertEquals(1, $this->notificationService->getUnreadCount($this->user));
    }

    /** @test */
    public function notification_service_respects_user_preferences()
    {
        // Set user preferences to disable email notifications
        $this->user->update([
            'notification_preferences' => [
                'email_enabled' => false,
                'push_enabled' => true,
                'types' => [
                    'post_reaction' => ['email' => false, 'push' => true, 'database' => true],
                ],
            ],
        ]);

        $preferences = $this->notificationService->getUserPreferences($this->user);

        $this->assertFalse($preferences['email_enabled']);
        $this->assertTrue($preferences['push_enabled']);
        $this->assertFalse($preferences['types']['post_reaction']['email']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);

        $response = $this->getJson('/api/notifications/unread-count');
        $response->assertStatus(401);

        $response = $this->getJson('/api/notifications/preferences');
        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_access_other_users_notifications()
    {
        // Create notification for other user
        $post = Post::factory()->create(['user_id' => $this->otherUser->id]);
        $notification = new PostReactionNotification($post, $this->user, 'like');
        $this->otherUser->notify($notification);

        $otherUserNotificationId = $this->otherUser->notifications()->first()->id;

        // Try to mark other user's notification as read
        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$otherUserNotificationId}/read");

        $response->assertStatus(404);
    }
}
