<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\NotificationService;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * Unit tests for NotificationService
 */
class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService();
        $this->user = User::factory()->create();

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test sending notification to user via multiple channels
     */
    public function test_send_notification()
    {
        // Mock notification
        $notificationMock = Mockery::mock(TestNotification::class);
        $notificationMock->shouldReceive('via')->andReturn(['database', 'mail']);

        // Mock Laravel Notification facade
        NotificationFacade::shouldReceive('send')
            ->once()
            ->andReturnNull();

        // Test sending notification
        $this->notificationService->sendNotification($this->user, $notificationMock);

        // Verify notification was stored in database
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->user->id,
            'notifiable_type' => get_class($this->user),
        ]);
    }

    /**
     * Test marking notification as read
     */
    public function test_mark_as_read()
    {
        // Create a notification first
        $this->user->notify(new TestNotification());

        $notification = $this->user->notifications()->first();

        // Initially unread
        $this->assertNull($notification->read_at);

        // Mark as read
        $result = $this->notificationService->markAsRead($notification->id, $this->user);

        $this->assertTrue($result);

        // Verify notification is now read
        $updatedNotification = $this->user->notifications()->find($notification->id);
        $this->assertNotNull($updatedNotification->read_at);
    }

    /**
     * Test mark as read with invalid notification ID
     */
    public function test_mark_as_read_invalid_id()
    {
        $result = $this->notificationService->markAsRead('invalid-id', $this->user);

        $this->assertFalse($result);
    }

    /**
     * Test mark as read on already read notification
     */
    public function test_mark_as_read_already_read()
    {
        // Create and mark notification as read
        $this->user->notify(new TestNotification());
        $notification = $this->user->notifications()->first();
        $notification->markAsRead();

        // Try to mark as read again
        $result = $this->notificationService->markAsRead($notification->id, $this->user);

        $this->assertFalse($result);
    }

    /**
     * Test marking all notifications as read
     */
    public function test_mark_all_as_read()
    {
        // Create multiple unread notifications
        $this->user->notify(new TestNotification());
        $this->user->notify(new TestNotification());
        $this->user->notify(new TestNotification());

        $unreadCount = $this->user->unreadNotifications()->count();
        $this->assertEquals(3, $unreadCount);

        // Mark all as read
        $markedCount = $this->notificationService->markAllAsRead($this->user);

        $this->assertEquals(3, $markedCount);

        // Verify all are now read
        $remainingUnread = $this->user->unreadNotifications()->count();
        $this->assertEquals(0, $remainingUnread);
    }

    /**
     * Test getting unread count
     */
    public function test_get_unread_count()
    {
        // Create unread notifications
        $this->user->notify(new TestNotification());
        $this->user->notify(new TestNotification());

        $unreadCount = $this->notificationService->getUnreadCount($this->user);
        $this->assertEquals(2, $unreadCount);

        // Test caching
        $cachedCount = $this->notificationService->getUnreadCount($this->user);
        $this->assertEquals(2, $cachedCount);
    }

    /**
     * Test get unread count with caching
     */
    public function test_get_unread_count_caching()
    {
        $cacheKey = "unread_notifications_count_{$this->user->id}";

        // Initially cache should be empty
        $this->assertFalse(Cache::has($cacheKey));

        // Create notification and get count
        $this->user->notify(new TestNotification());
        $count = $this->notificationService->getUnreadCount($this->user);

        // Cache should now be set
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertEquals(1, $count);

        // Add another notification
        $this->user->notify(new TestNotification());

        // Count should still be cached (old value)
        $cachedCount = $this->notificationService->getUnreadCount($this->user);
        $this->assertEquals(1, $cachedCount); // Still shows cached value
    }

    /**
     * Test getting user notifications with pagination
     */
    public function test_get_user_notifications_pagination()
    {
        // Create multiple notifications
        for ($i = 0; $i < 5; $i++) {
            $this->user->notify(new TestNotification());
        }

        $notifications = $this->notificationService->getUserNotifications($this->user, 3);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $notifications);
        $this->assertEquals(3, $notifications->perPage());
        $this->assertEquals(5, $notifications->total());

        // Should be ordered by creation date descending
        $this->assertTrue($notifications->first()->created_at > $notifications->last()->created_at);
    }

    /**
     * Test getting user preferences with defaults
     */
    public function test_get_user_preferences_defaults()
    {
        // User with no custom preferences
        $preferences = $this->notificationService->getUserPreferences($this->user);

        $this->assertEquals(true, $preferences['email_enabled']);
        $this->assertEquals(true, $preferences['push_enabled']);
        $this->assertEquals('immediate', $preferences['email_frequency']);

        // Check default types structure
        $this->assertArrayHasKey('types', $preferences);
        $this->assertArrayHasKey('post_reaction', $preferences['types']);
        $this->assertArrayHasKey('email', $preferences['types']['post_reaction']);
    }

    /**
     * Test getting user preferences with custom settings
     */
    public function test_get_user_preferences_custom()
    {
        $customPreferences = [
            'email_enabled' => false,
            'push_enabled' => true,
            'custom_setting' => 'test_value'
        ];

        $this->user->update(['notification_preferences' => $customPreferences]);

        $preferences = $this->notificationService->getUserPreferences($this->user);

        $this->assertEquals(false, $preferences['email_enabled']);
        $this->assertEquals(true, $preferences['push_enabled']);
        $this->assertEquals('test_value', $preferences['custom_setting']);
    }

    /**
     * Test updating user preferences
     */
    public function test_update_user_preferences()
    {
        $newPreferences = [
            'email_enabled' => false,
            'push_enabled' => false,
            'custom_setting' => 'updated_value'
        ];

        $this->notificationService->updateUserPreferences($this->user, $newPreferences);

        $this->user->refresh();
        $this->assertEquals($newPreferences, $this->user->notification_preferences);
    }

    /**
     * Test clearing preferences cache when updating
     */
    public function test_update_preferences_clears_cache()
    {
        $cacheKey = "notification_preferences_{$this->user->id}";

        // Get preferences to cache them
        $oldPreferences = $this->notificationService->getUserPreferences($this->user);
        $this->assertTrue(Cache::has($cacheKey));

        // Update preferences
        $this->notificationService->updateUserPreferences($this->user, ['email_enabled' => false]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test cleanup old notifications
     */
    public function test_cleanup_old_notifications()
    {
        // Create old notification
        DatabaseNotification::create([
            'id' => fake()->uuid(),
            'notifiable_id' => $this->user->id,
            'notifiable_type' => get_class($this->user),
            'data' => ['test' => 'data'],
            'read_at' => null,
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100)
        ]);

        // Create new notification
        DatabaseNotification::create([
            'id' => fake()->uuid(),
            'notifiable_id' => $this->user->id,
            'notifiable_type' => get_class($this->user),
            'data' => ['test' => 'data'],
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $totalBefore = DatabaseNotification::count();
        $deletedCount = $this->notificationService->cleanupOldNotifications(50);

        $this->assertGreaterThan(0, $deletedCount);
        $this->assertEquals($totalBefore - 1, DatabaseNotification::count());
    }

    /**
     * Test getting notification statistics
     */
    public function test_get_notification_stats()
    {
        // Create some notifications
        $this->user->notify(new TestNotification()); // unread
        $this->user->notify(new TestNotification()); // unread

        // Mark one as read
        $notification = $this->user->notifications()->first();
        $notification->markAsRead();

        $stats = $this->notificationService->getNotificationStats($this->user);

        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['unread']);
        $this->assertEquals(1, $stats['read']);
        $this->assertIsArray($stats['by_type']);
    }

    /**
     * Test sending bulk notifications
     */
    public function test_send_bulk_notification()
    {
        $users = [
            User::factory()->create(),
            User::factory()->create(),
            User::factory()->create()
        ];

        $notificationMock = Mockery::mock(TestNotification::class);

        // Mock bulk send
        NotificationFacade::shouldReceive('send')
            ->with($users, $notificationMock)
            ->once()
            ->andReturnNull();

        $this->notificationService->sendBulkNotification($users, $notificationMock);
    }

    /**
     * Test clearing unread count cache
     */
    public function test_clear_unread_count_cache()
    {
        $cacheKey = "unread_notifications_count_{$this->user->id}";

        // Set cache value
        Cache::put($cacheKey, 5, 300);

        $this->assertTrue(Cache::has($cacheKey));

        // Call private method to clear cache (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('clearUnreadCountCache');
        $method->setAccessible(true);
        $method->invokeArgs($this->notificationService, [$this->user]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test clearing preferences cache
     */
    public function test_clear_preferences_cache()
    {
        $cacheKey = "notification_preferences_{$this->user->id}";

        // Set cache value
        Cache::put($cacheKey, ['email_enabled' => true], 300);

        $this->assertTrue(Cache::has($cacheKey));

        // Call private method to clear cache (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('clearPreferencesCache');
        $method->setAccessible(true);
        $method->invokeArgs($this->notificationService, [$this->user]);

        // Cache should be cleared
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Test get notification type from notification class
     */
    public function test_get_notification_type()
    {
        $notificationMock = Mockery::mock(TestNotification::class);

        // Call private method to get type (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('getNotificationType');
        $method->setAccessible(true);
        $type = $method->invokeArgs($this->notificationService, [$notificationMock]);

        $this->assertEquals('test', $type);
    }

    /**
     * Test should send email method
     */
    public function test_should_send_email()
    {
        $notificationMock = Mockery::mock(TestNotification::class);
        $preferences = [
            'types' => [
                'test' => ['email' => true, 'push' => false]
            ]
        ];

        // Call private method (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('shouldSendEmail');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->notificationService, [$notificationMock, $preferences]);

        $this->assertTrue($result);
    }

    /**
     * Test should send email when disabled in preferences
     */
    public function test_should_send_email_disabled()
    {
        $notificationMock = Mockery::mock(TestNotification::class);
        $preferences = [
            'types' => [
                'test' => ['email' => false, 'push' => false]
            ]
        ];

        // Call private method (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('shouldSendEmail');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->notificationService, [$notificationMock, $preferences]);

        $this->assertFalse($result);
    }

    /**
     * Test should send push notification
     */
    public function test_should_send_push()
    {
        $notificationMock = Mockery::mock(TestNotification::class);
        $preferences = [
            'types' => [
                'test' => ['email' => false, 'push' => true]
            ]
        ];

        // Call private method (using reflection)
        $reflection = new \ReflectionClass($this->notificationService);
        $method = $reflection->getMethod('shouldSendPush');
        $method->setAccessible(true);
        $result = $method->invokeArgs($this->notificationService, [$notificationMock, $preferences]);

        $this->assertTrue($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}