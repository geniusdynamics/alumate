<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Notifications\PostReactionNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new NotificationService;
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function can_send_notification_to_user()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');

        $this->service->sendNotification($this->user, $notification);

        $this->assertEquals(1, $this->user->notifications()->count());
        $this->assertEquals(1, $this->service->getUnreadCount($this->user));
    }

    /** @test */
    public function can_mark_notification_as_read()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        $notificationId = $this->user->notifications()->first()->id;
        $result = $this->service->markAsRead($notificationId, $this->user);

        $this->assertTrue($result);
        $this->assertNotNull($this->user->notifications()->first()->read_at);
        $this->assertEquals(0, $this->service->getUnreadCount($this->user));
    }

    /** @test */
    public function can_mark_all_notifications_as_read()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create multiple notifications
        for ($i = 0; $i < 3; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        $count = $this->service->markAllAsRead($this->user);

        $this->assertEquals(3, $count);
        $this->assertEquals(0, $this->service->getUnreadCount($this->user));
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    /** @test */
    public function can_get_unread_count()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create notifications
        for ($i = 0; $i < 5; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        $count = $this->service->getUnreadCount($this->user);

        $this->assertEquals(5, $count);
    }

    /** @test */
    public function unread_count_is_cached()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        // First call should cache the result
        $count1 = $this->service->getUnreadCount($this->user);

        // Second call should use cache
        $count2 = $this->service->getUnreadCount($this->user);

        $this->assertEquals($count1, $count2);
        $this->assertEquals(1, $count1);

        // Verify cache key exists
        $cacheKey = "unread_notifications_count_{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function can_get_user_preferences_with_defaults()
    {
        $preferences = $this->service->getUserPreferences($this->user);

        $this->assertIsArray($preferences);
        $this->assertTrue($preferences['email_enabled']);
        $this->assertTrue($preferences['push_enabled']);
        $this->assertEquals('immediate', $preferences['email_frequency']);
        $this->assertArrayHasKey('types', $preferences);
        $this->assertArrayHasKey('post_reaction', $preferences['types']);
    }

    /** @test */
    public function can_update_user_preferences()
    {
        $newPreferences = [
            'email_enabled' => false,
            'push_enabled' => true,
            'email_frequency' => 'daily',
            'types' => [
                'post_reaction' => ['email' => false, 'push' => true, 'database' => true],
            ],
        ];

        $this->service->updateUserPreferences($this->user, $newPreferences);

        $this->user->refresh();
        $this->assertEquals($newPreferences, $this->user->notification_preferences);

        // Verify preferences are returned correctly
        $preferences = $this->service->getUserPreferences($this->user);
        $this->assertFalse($preferences['email_enabled']);
        $this->assertEquals('daily', $preferences['email_frequency']);
    }

    /** @test */
    public function can_get_notification_stats()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create notifications
        for ($i = 0; $i < 3; $i++) {
            $notification = new PostReactionNotification($post, $this->otherUser, 'like');
            $this->user->notify($notification);
        }

        // Mark one as read
        $this->user->notifications()->first()->markAsRead();

        $stats = $this->service->getNotificationStats($this->user);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['unread']);
        $this->assertEquals(1, $stats['read']);
        $this->assertArrayHasKey('by_type', $stats);
    }

    /** @test */
    public function can_cleanup_old_notifications()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        // Create old notification
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        // Manually update created_at to be old
        $this->user->notifications()->update(['created_at' => now()->subDays(100)]);

        $deletedCount = $this->service->cleanupOldNotifications(90);

        $this->assertEquals(1, $deletedCount);
        $this->assertEquals(0, $this->user->notifications()->count());
    }

    /** @test */
    public function can_send_bulk_notifications()
    {
        $users = User::factory()->count(3)->create();
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->user, 'like');

        $this->service->sendBulkNotification($users->toArray(), $notification);

        foreach ($users as $user) {
            $this->assertEquals(1, $user->notifications()->count());
        }
    }

    /** @test */
    public function preferences_are_cached()
    {
        // Set preferences
        $preferences = [
            'email_enabled' => false,
            'push_enabled' => true,
        ];
        $this->service->updateUserPreferences($this->user, $preferences);

        // First call should cache
        $prefs1 = $this->service->getUserPreferences($this->user);

        // Second call should use cache
        $prefs2 = $this->service->getUserPreferences($this->user);

        $this->assertEquals($prefs1, $prefs2);

        // Verify cache key exists
        $cacheKey = "notification_preferences_{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function cache_is_cleared_when_marking_as_read()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $notification = new PostReactionNotification($post, $this->otherUser, 'like');
        $this->user->notify($notification);

        // Get count to cache it
        $this->service->getUnreadCount($this->user);

        $cacheKey = "unread_notifications_count_{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));

        // Mark as read should clear cache
        $notificationId = $this->user->notifications()->first()->id;
        $this->service->markAsRead($notificationId, $this->user);

        $this->assertFalse(Cache::has($cacheKey));
    }
}
