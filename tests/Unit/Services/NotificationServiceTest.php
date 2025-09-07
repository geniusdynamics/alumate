<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\NotificationTemplate;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $notificationService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new NotificationService();

        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        // Create test notification templates
        NotificationTemplate::create([
            'name' => 'test_notification',
            'type' => 'email',
            'subject' => 'Test Subject',
            'content' => 'Hello {{user_name}}, this is a test notification.',
            'variables' => ['user_name'],
            'is_active' => true,
        ]);

        NotificationTemplate::create([
            'name' => 'test_notification',
            'type' => 'sms',
            'content' => 'Hello {{user_name}}, SMS test.',
            'variables' => ['user_name'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_send_notification_to_single_user()
    {
        $result = $this->notificationService->sendNotification(
            $this->user->id,
            'test_notification',
            ['user_name' => $this->user->name]
        );

        $this->assertCount(1, $result);
        $this->assertEquals($this->user->id, $result[0]['user_id']);
        $this->assertEquals('test_notification', $result[0]['type']);
    }

    /** @test */
    public function it_can_send_bulk_notifications()
    {
        $user2 = User::factory()->create();

        $notifications = [
            [
                'user' => $this->user->id,
                'type' => 'test_notification',
                'data' => ['user_name' => $this->user->name],
            ],
            [
                'user' => $user2->id,
                'type' => 'test_notification',
                'data' => ['user_name' => $user2->name],
            ],
        ];

        $result = $this->notificationService->sendBulkNotifications($notifications);

        $this->assertCount(2, $result);
        $this->assertEquals($this->user->id, $result[0]['user_id']);
        $this->assertEquals($user2->id, $result[1]['user_id']);
    }

    /** @test */
    public function it_respects_user_notification_preferences()
    {
        // Create preference with email disabled
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'test_notification',
            'email_enabled' => false,
            'sms_enabled' => true,
            'in_app_enabled' => true,
            'push_enabled' => false,
        ]);

        $result = $this->notificationService->sendNotification(
            $this->user->id,
            'test_notification',
            ['user_name' => $this->user->name]
        );

        $this->assertCount(1, $result);
        $channels = $result[0]['channels_sent'];

        // Should not have email channel
        $this->assertArrayNotHasKey('email', $channels);
        // Should have SMS and in-app channels
        $this->assertArrayHasKey('sms', $channels);
        $this->assertArrayHasKey('in_app', $channels);
    }

    /** @test */
    public function it_can_update_notification_preferences()
    {
        $preference = $this->notificationService->updatePreferences(
            $this->user->id,
            'test_notification',
            [
                'email_enabled' => false,
                'sms_enabled' => true,
                'in_app_enabled' => true,
                'push_enabled' => false,
            ]
        );

        $this->assertInstanceOf(NotificationPreference::class, $preference);
        $this->assertEquals($this->user->id, $preference->user_id);
        $this->assertEquals('test_notification', $preference->notification_type);
        $this->assertFalse($preference->email_enabled);
        $this->assertTrue($preference->sms_enabled);
    }

    /** @test */
    public function it_can_get_user_preferences()
    {
        // Create a preference
        $this->notificationService->updatePreferences(
            $this->user->id,
            'test_notification',
            ['email_enabled' => false]
        );

        $preferences = $this->notificationService->getAllUserPreferences($this->user->id);

        $this->assertArrayHasKey('test_notification', $preferences);
        $this->assertFalse($preferences['test_notification']['email_enabled']);
    }

    /** @test */
    public function it_returns_default_preferences_when_none_exist()
    {
        $preferences = $this->notificationService->getAllUserPreferences($this->user->id);

        $this->assertArrayHasKey('job_match', $preferences);
        $this->assertArrayHasKey('application_status', $preferences);
        $this->assertArrayHasKey('interview_reminder', $preferences);
    }

    /** @test */
    public function it_caches_notification_preferences()
    {
        // First call should cache
        $preferences1 = $this->notificationService->getAllUserPreferences($this->user->id);

        // Modify database directly
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => 'test_notification',
            'email_enabled' => false,
        ]);

        // Second call should return cached data
        $preferences2 = $this->notificationService->getAllUserPreferences($this->user->id);

        $this->assertEquals($preferences1, $preferences2);
    }

    /** @test */
    public function it_can_clear_user_cache()
    {
        // Set some data
        Cache::put("notification_preferences_{$this->user->id}_test", 'test_data', 3600);

        // Clear cache
        $this->notificationService->clearUserCache($this->user->id);

        // Verify cache is cleared
        $this->assertNull(Cache::get("notification_preferences_{$this->user->id}_test"));
    }

    /** @test */
    public function it_handles_invalid_user_gracefully()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->notificationService->sendNotification('invalid_user', 'test_notification');
    }

    /** @test */
    public function it_handles_missing_template_gracefully()
    {
        $result = $this->notificationService->sendNotification(
            $this->user->id,
            'nonexistent_template'
        );

        $this->assertCount(1, $result);
        // Should still return result but with failed channels
        $this->assertEquals($this->user->id, $result[0]['user_id']);
    }
}