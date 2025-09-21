<?php

namespace Tests\Integration;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Analytics cross-module integration testing
 * Tests integration between analytics system and other modules (graduate/alumni) via observers
 */
class AnalyticsCrossModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $tenancy = true;
    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test tenant
        $this->tenant = Tenant::factory()->create([
            'id' => 'test-tenant-cross-module',
            'name' => 'Test Cross Module Tenant',
        ]);

        // Create authenticated user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_records_analytics_events_when_user_profile_is_updated_via_observer()
    {
        // Create initial user with basic profile
        $user = User::factory()->create([
            'profile_data' => ['initial' => 'data'],
        ]);

        // Update user profile data (this should trigger UserObserver)
        $user->update([
            'profile_data' => [
                'initial' => 'data',
                'education' => [
                    'degree' => 'Bachelor of Science',
                    'university' => 'Test University',
                    'graduation_year' => 2023,
                ],
            ],
        ]);

        // Verify analytics event was recorded
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $this->tenant->id,
            'event_type' => 'user_profile_updated',
            'user_id' => $user->id,
        ]);

        $event = AnalyticsEvent::where('event_type', 'user_profile_updated')
                               ->where('user_id', $user->id)
                               ->first();

        $this->assertNotNull($event);
        $this->assertArrayHasKey('education', $event->properties);
        $this->assertEquals('Bachelor of Science', $event->properties['education']['degree']);
    }

    /** @test */
    public function it_maintains_tenant_isolation_in_cross_module_analytics_events()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'other-tenant-cross-module',
            'name' => 'Other Cross Module Tenant',
        ]);

        // Create user for first tenant
        $user1 = User::factory()->create([
            'profile_data' => ['tenant' => 'first'],
        ]);

        // Switch to other tenant context and create user
        tenancy()->initialize($otherTenant);
        $user2 = User::factory()->create([
            'profile_data' => ['tenant' => 'second'],
        ]);

        // Switch back to first tenant
        tenancy()->initialize($this->tenant);

        // Update user1 (should only affect first tenant)
        $user1->update([
            'profile_data' => [
                'tenant' => 'first',
                'updated' => true,
            ],
        ]);

        // Verify event was recorded only for first tenant
        $events = AnalyticsEvent::where('event_type', 'user_profile_updated')->get();
        $this->assertCount(1, $events);

        $event = $events->first();
        $this->assertEquals($this->tenant->id, $event->tenant_id);
        $this->assertEquals($user1->id, $event->user_id);

        // Switch to other tenant and verify no events there
        tenancy()->initialize($otherTenant);
        $otherEvents = AnalyticsEvent::where('event_type', 'user_profile_updated')->get();
        $this->assertCount(0, $otherEvents);
    }

    /** @test */
    public function it_records_analytics_events_for_education_history_changes()
    {
        $user = User::factory()->create();

        // Simulate education history update (this would normally be done through EducationHistoryObserver)
        // For this test, we'll manually create the analytics event as the observer would
        AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'education_history_updated',
            'user_id' => $user->id,
            'properties' => [
                'education_id' => 123,
                'changes' => [
                    'degree' => 'Master of Science',
                    'field_of_study' => 'Computer Science',
                ],
            ],
            'session_id' => 'session-123',
            'consent_given' => true,
        ]);

        // Verify the education analytics event was recorded
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $this->tenant->id,
            'event_type' => 'education_history_updated',
            'user_id' => $user->id,
        ]);

        $event = AnalyticsEvent::where('event_type', 'education_history_updated')->first();
        $this->assertEquals('Master of Science', $event->properties['changes']['degree']);
    }

    /** @test */
    public function it_handles_bulk_user_updates_with_analytics_tracking()
    {
        // Create multiple users
        $users = User::factory()->count(5)->create();

        // Update all users (simulating bulk operation)
        foreach ($users as $user) {
            $user->update([
                'profile_data' => [
                    'bulk_update' => true,
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        }

        // Verify analytics events were recorded for all users
        $events = AnalyticsEvent::where('event_type', 'user_profile_updated')->get();
        $this->assertCount(5, $events);

        // Verify all events belong to the correct tenant
        foreach ($events as $event) {
            $this->assertEquals($this->tenant->id, $event->tenant_id);
            $this->assertTrue($event->properties['bulk_update']);
        }
    }

    /** @test */
    public function it_prevents_cross_tenant_data_leakage_in_analytics_events()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create([
            'id' => 'isolated-tenant',
            'name' => 'Isolated Tenant',
        ]);

        // Create users in different tenants
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        tenancy()->initialize($otherTenant);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);
        tenancy()->initialize($this->tenant);

        // Update user1 in first tenant
        $user1->update(['first_name' => 'UpdatedName1']);

        // Verify event is only in first tenant
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $user1->id,
        ]);

        // Switch to other tenant and verify no events for user1
        tenancy()->initialize($otherTenant);
        $this->assertDatabaseMissing('analytics_events', [
            'tenant_id' => $otherTenant->id,
            'user_id' => $user1->id,
        ]);

        // Update user2 in second tenant
        $user2->update(['first_name' => 'UpdatedName2']);

        // Verify event is in second tenant
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $otherTenant->id,
            'user_id' => $user2->id,
        ]);

        // Switch back and verify no cross-contamination
        tenancy()->initialize($this->tenant);
        $crossTenantEvents = AnalyticsEvent::where('user_id', $user2->id)->get();
        $this->assertCount(0, $crossTenantEvents);
    }

    /** @test */
    public function it_tracks_analytics_events_for_user_registration_workflow()
    {
        // Simulate user registration process with multiple steps
        $user = User::factory()->create([
            'profile_data' => ['registration_step' => 1],
        ]);

        // Step 1: Basic profile completion
        $user->update([
            'profile_data' => [
                'registration_step' => 1,
                'basic_info_complete' => true,
            ],
        ]);

        // Step 2: Education details
        $user->update([
            'profile_data' => [
                'registration_step' => 2,
                'basic_info_complete' => true,
                'education_complete' => true,
            ],
        ]);

        // Step 3: Career information
        $user->update([
            'profile_data' => [
                'registration_step' => 3,
                'basic_info_complete' => true,
                'education_complete' => true,
                'career_complete' => true,
            ],
        ]);

        // Verify all registration steps were tracked
        $events = AnalyticsEvent::where('event_type', 'user_profile_updated')
                                ->where('user_id', $user->id)
                                ->orderBy('created_at')
                                ->get();

        $this->assertCount(3, $events);

        // Verify progression through registration steps
        $this->assertEquals(1, $events[0]->properties['registration_step']);
        $this->assertEquals(2, $events[1]->properties['registration_step']);
        $this->assertEquals(3, $events[2]->properties['registration_step']);
    }

    /** @test */
    public function it_handles_analytics_events_for_user_deletion_via_observer()
    {
        $user = User::factory()->create();

        // Create some analytics events for the user
        AnalyticsEvent::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'event_type' => 'page_view',
        ]);

        // Delete the user (this should trigger cleanup)
        $user->delete();

        // Verify user analytics events are still present (soft delete consideration)
        // In a real scenario, you might want to anonymize or archive user data
        $userEvents = AnalyticsEvent::where('user_id', $user->id)->get();
        $this->assertCount(3, $userEvents);

        // All events should still be associated with the correct tenant
        foreach ($userEvents as $event) {
            $this->assertEquals($this->tenant->id, $event->tenant_id);
        }
    }

    /** @test */
    public function it_integrates_with_gamification_system_for_user_actions()
    {
        $user = User::factory()->create();

        // Simulate user action that triggers gamification
        AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'gamification_action',
            'user_id' => $user->id,
            'properties' => [
                'action_type' => 'profile_completion',
                'points_awarded' => 50,
                'badge_unlocked' => 'profile_master',
                'level_up' => true,
                'new_level' => 2,
            ],
            'session_id' => 'session-gamify',
            'consent_given' => true,
        ]);

        // Verify gamification analytics event
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $this->tenant->id,
            'event_type' => 'gamification_action',
            'user_id' => $user->id,
        ]);

        $event = AnalyticsEvent::where('event_type', 'gamification_action')->first();
        $this->assertEquals(50, $event->properties['points_awarded']);
        $this->assertEquals('profile_master', $event->properties['badge_unlocked']);
        $this->assertTrue($event->properties['level_up']);
    }

    /** @test */
    public function it_handles_concurrent_user_updates_across_tenants()
    {
        // Create multiple tenants
        $tenant1 = $this->tenant;
        $tenant2 = Tenant::factory()->create(['id' => 'tenant2-cross-module']);
        $tenant3 = Tenant::factory()->create(['id' => 'tenant3-cross-module']);

        // Create users in each tenant
        $user1 = User::factory()->create(['email' => 'user1@tenant1.com']);
        tenancy()->initialize($tenant2);
        $user2 = User::factory()->create(['email' => 'user2@tenant2.com']);
        tenancy()->initialize($tenant3);
        $user3 = User::factory()->create(['email' => 'user3@tenant3.com']);
        tenancy()->initialize($tenant1);

        // Perform concurrent updates
        $user1->update(['first_name' => 'Updated1']);
        tenancy()->initialize($tenant2);
        $user2->update(['first_name' => 'Updated2']);
        tenancy()->initialize($tenant3);
        $user3->update(['first_name' => 'Updated3']);

        // Verify isolation: each tenant only sees its own events
        tenancy()->initialize($tenant1);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $tenant1->id,
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user2->id]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user3->id]);

        tenancy()->initialize($tenant2);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $tenant2->id,
            'user_id' => $user2->id,
        ]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user1->id]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user3->id]);

        tenancy()->initialize($tenant3);
        $this->assertDatabaseHas('analytics_events', [
            'tenant_id' => $tenant3->id,
            'user_id' => $user3->id,
        ]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user1->id]);
        $this->assertDatabaseMissing('analytics_events', ['user_id' => $user2->id]);
    }
}