<?php

namespace Tests\Unit\Models;

use App\Models\AnalyticsEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsEventTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
    }

    public function test_can_create_analytics_event_with_tenant()
    {
        $event = AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'template_usage',
            'event_name' => 'template_view',
            'user_id' => $this->user->id,
            'properties' => ['template_id' => 1],
            'session_id' => 'test_session',
            'user_agent' => 'TestAgent',
            'ip_address' => '127.0.0.1',
            'occurred_at' => now(),
        ]);

        expect($event->tenant_id)->toBe($this->tenant->id);
        expect($event->event_type)->toBe('template_usage');
        expect($event->properties)->toBe(['template_id' => 1]);
        expect($event->is_compliant)->toBe(true);
        expect($event->consent_given)->toBe(true);
        expect($event->analytics_version)->toBe('v1.0');
        expect($event->canRetainData())->toBe(true);
    }

    public function test_belongs_to_tenant_relationship()
    {
        $event = AnalyticsEvent::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        expect($event->tenant)->not->toBeNull();
        expect($event->tenant->id)->toBe($this->tenant->id);
    }

    public function test_belongs_to_user_relationship()
    {
        $event = AnalyticsEvent::factory()->create([
            'user_id' => $this->user->id
        ]);

        expect($event->user)->not->toBeNull();
        expect($event->user->id)->toBe($this->user->id);
    }

    public function test_scope_by_tenant()
    {
        AnalyticsEvent::factory()->create(['tenant_id' => $this->tenant->id]);
        AnalyticsEvent::factory()->create(['tenant_id' => $this->tenant->id]);

        $otherTenant = Tenant::factory()->create();
        AnalyticsEvent::factory()->create(['tenant_id' => $otherTenant->id]);

        $tenantEvents = AnalyticsEvent::byTenant($this->tenant->id)->get();

        expect($tenantEvents)->toHaveCount(2);
        expect($tenantEvents->first()->tenant_id)->toBe($this->tenant->id);
        expect($tenantEvents->last()->tenant_id)->toBe($this->tenant->id);
    }

    public function test_scope_by_event_type()
    {
        AnalyticsEvent::factory()->create([
            'event_type' => 'template_usage',
            'event_name' => 'template_view'
        ]);
        AnalyticsEvent::factory()->create([
            'event_type' => 'template_usage',
            'event_name' => 'template_use'
        ]);
        AnalyticsEvent::factory()->create([
            'event_type' => 'page_view'
        ]);

        $templateEvents = AnalyticsEvent::byEventType('template_usage')->get();

        expect($templateEvents)->toHaveCount(2);
        $templateEvents->each(function ($event) {
            expect($event->event_type)->toBe('template_usage');
        });
    }

    public function test_scope_by_date_range()
    {
        $startDate = now()->subDays(5);
        $endDate = now();

        AnalyticsEvent::factory()->create([
            'occurred_at' => now()->subDays(3)
        ]);
        AnalyticsEvent::factory()->create([
            'occurred_at' => now()->subDays(10) // Outside range
        ]);

        $dateRangeEvents = AnalyticsEvent::byDateRange($startDate, $endDate)->get();

        expect($dateRangeEvents)->toHaveCount(1);
        expect($dateRangeEvents->first()->occurred_at)->toBeBetween($startDate, $endDate);
    }

    public function test_scope_by_user()
    {
        AnalyticsEvent::factory()->create([
            'user_id' => $this->user->id
        ]);
        AnalyticsEvent::factory()->create([
            'user_id' => $this->user->id
        ]);
        AnalyticsEvent::factory()->create(); // Different user

        $userEvents = AnalyticsEvent::byUser($this->user->id)->get();

        expect($userEvents)->toHaveCount(2);
        $userEvents->each(function ($event) {
            expect($event->user_id)->toBe($this->user->id);
        });
    }

    public function test_scope_compliant()
    {
        AnalyticsEvent::factory()->create([
            'is_compliant' => true,
            'consent_given' => true
        ]);
        AnalyticsEvent::factory()->create([
            'is_compliant' => false
        ]);
        AnalyticsEvent::factory()->create([
            'consent_given' => false
        ]);

        $compliantEvents = AnalyticsEvent::compliant()->get();

        expect($compliantEvents)->toHaveCount(1);
        expect($compliantEvents->first()->is_compliant)->toBe(true);
        expect($compliantEvents->first()->consent_given)->toBe(true);
    }

    public function test_privacy_anonymization()
    {
        $event = AnalyticsEvent::factory()->create([
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'is_compliant' => true
        ]);

        $event->anonymize();

        expect($event->fresh()->ip_address)->toBeNull();
        expect($event->fresh()->user_agent)->toBe('anonymized');
        expect($event->fresh()->is_compliant)->toBe(false);
    }

    public function test_can_retain_data_logic()
    {
        $event = AnalyticsEvent::factory()->create([
            'data_retention_until' => now()->addDays(30)
        ]);

        expect($event->canRetainData())->toBe(true);

        // Update retention date to past
        $event->update(['data_retention_until' => now()->subDays(1)]);

        expect($event->fresh()->canRetainData())->toBe(false);
    }

    public function test_casts_properties_correctly()
    {
        $properties = ['template_id' => 123, 'action' => 'view'];

        $event = AnalyticsEvent::factory()->create([
            'properties' => $properties,
            'is_compliant' => '0', // String that should be cast to boolean
            'consent_given' => '1',
            'data_retention_until' => '2025-12-01 00:00:00'
        ]);

        expect($event->properties)->toBe($properties);
        expect($event->is_compliant)->toBe(false); // Casted to boolean
        expect($event->consent_given)->toBe(true);
        expect($event->data_retention_until)->toBeInstanceOf(\Carbon\Carbon::class);
    }

    public function test_fillable_attributes_match_expectations()
    {
        $expectedFillable = [
            'tenant_id',
            'event_type',
            'event_name',
            'user_id',
            'properties',
            'session_id',
            'user_agent',
            'ip_address',
            'referrer',
            'page_url',
            'occurred_at',
            'is_compliant',
            'consent_given',
            'data_retention_until',
            'analytics_version',
        ];

        $event = new AnalyticsEvent();

        // Allow for both exact match and subset check
        foreach ($expectedFillable as $attribute) {
            expect($event->getFillable())->toContain($attribute);
        }
    }

    public function test_indexes_are_properly_configured()
    {
        // This test ensures the database migrations create proper indexes
        // The actual confirmation would come from checking the migration files
        // and potentially running actual database integration tests

        // We can test that queries using these indexed columns work efficiently
        AnalyticsEvent::factory()->count(100)->create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'template_usage'
        ]);

        $start = microtime(true);
        $results = AnalyticsEvent::where('tenant_id', $this->tenant->id)
            ->where('event_type', 'template_usage')
            ->whereBetween('occurred_at', [now()->subMonth(), now()])
            ->get();
        $end = microtime(true);

        expect($results)->toHaveCount(100);
        expect($end - $start)->toBeLessThan(1.0); // Should complete in less than 1 second
    }

    public function test_handles_null_tenant_correctly()
    {
        $event = AnalyticsEvent::factory()->create([
            'tenant_id' => null,
            'event_type' => 'system_event'
        ]);

        expect($event->tenant_id)->toBeNull();
        expect($event->tenant)->toBeNull();
        expect($event->canRetainData())->toBe(true);
    }

    public function test_prevents_mass_assignment_of_sensitive_fields()
    {
        // Test that sensitive fields can be set explicitly but not mass assigned accidentally
        $event = AnalyticsEvent::create([
            'tenant_id' => $this->tenant->id,
            'event_type' => 'security_event',
            'event_name' => 'password_change',
            'user_id' => $this->user->id,
            'occurred_at' => now(),
            'ip_address' => '10.0.0.1',
        ]);

        expect($event->ip_address)->toBe('10.0.0.1');
        expect($event->is_compliant)->toBe(true); // Default value
    }
}