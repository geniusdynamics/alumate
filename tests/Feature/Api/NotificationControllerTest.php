<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\NotificationTemplate;
use App\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test notification templates
        NotificationTemplate::create([
            'name' => 'test_notification',
            'type' => 'email',
            'subject' => 'Test Subject',
            'content' => 'Hello {{user_name}}, this is a test notification.',
            'variables' => ['user_name'],
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_send_notification_via_api()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/notifications/send', [
            'users' => [$this->user->id],
            'type' => 'test_notification',
            'data' => ['user_name' => 'Test User'],
            'channels' => ['email'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notifications sent successfully'
            ]);
    }

    /** @test */
    public function it_can_send_bulk_notifications_via_api()
    {
        $this->actingAs($this->user, 'api');

        $user2 = User::factory()->create();

        $response = $this->postJson('/api/notifications/send-bulk', [
            'notifications' => [
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
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Bulk notifications sent successfully'
            ]);
    }

    /** @test */
    public function it_can_get_user_notifications()
    {
        $this->actingAs($this->user, 'api');

        // Create a notification for the user
        $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification'],
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'notifications',
                'pagination',
                'unread_count',
            ]);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $this->actingAs($this->user, 'api');

        $notification = $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification'],
        ]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notification marked as read'
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'read_at' => now(),
        ]);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        $this->actingAs($this->user, 'api');

        // Create multiple notifications
        $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification 1'],
        ]);

        $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification 2'],
        ]);

        $response = $this->patchJson('/api/notifications/mark-all-read');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'All notifications marked as read'
            ]);
    }

    /** @test */
    public function it_can_get_notification_preferences()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/notifications/preferences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'preferences'
            ]);
    }

    /** @test */
    public function it_can_update_notification_preferences()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->putJson('/api/notifications/preferences', [
            'type' => 'job_match',
            'email_enabled' => false,
            'sms_enabled' => true,
            'in_app_enabled' => true,
            'push_enabled' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notification preferences updated successfully'
            ]);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => 'job_match',
            'email_enabled' => false,
            'sms_enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_get_notification_templates()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->getJson('/api/notifications/templates?type=email');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'templates'
            ]);
    }

    /** @test */
    public function it_can_get_notification_stats()
    {
        $this->actingAs($this->user, 'api');

        // Create some notifications
        $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification'],
        ]);

        $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification'],
            'read_at' => now(),
        ]);

        $response = $this->getJson('/api/notifications/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_notifications',
                    'unread_count',
                    'read_count',
                    'today_count',
                    'this_week_count',
                ]
            ]);
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $this->actingAs($this->user, 'api');

        $notification = $this->user->notifications()->create([
            'type' => 'test_notification',
            'data' => ['message' => 'Test notification'],
        ]);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notification deleted successfully'
            ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }

    /** @test */
    public function it_can_schedule_notification()
    {
        $this->actingAs($this->user, 'api');

        $scheduledAt = now()->addHours(2);

        $response = $this->postJson('/api/notifications/schedule', [
            'users' => [$this->user->id],
            'type' => 'test_notification',
            'data' => ['user_name' => $this->user->name],
            'scheduled_at' => $scheduledAt->toISOString(),
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Notification scheduled successfully'
            ]);
    }

    /** @test */
    public function it_validates_send_notification_request()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->postJson('/api/notifications/send', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    /** @test */
    public function it_validates_update_preferences_request()
    {
        $this->actingAs($this->user, 'api');

        $response = $this->putJson('/api/notifications/preferences', [
            // Missing required type field
            'email_enabled' => true,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_endpoints()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertStatus(401);
    }
}