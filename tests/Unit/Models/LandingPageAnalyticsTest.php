<?php

namespace Tests\Unit\Models;

use App\Models\LandingPage;
use App\Models\LandingPageAnalytics;
use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Template $template;
    protected LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->landingPage = LandingPage::factory()->create([
            'template_id' => $this->template->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_landing_page_analytics_event()
    {
        $analytics = LandingPageAnalytics::create([
            'tenant_id' => $this->tenant->id,
            'template_id' => $this->template->id,
            'landing_page_id' => $this->landingPage->id,
            'event_type' => 'page_view',
            'event_name' => 'landing_page_view',
            'event_data' => ['source' => 'direct'],
            'session_id' => 'session_123',
            'visitor_id' => 'visitor_456',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent/1.0',
            'referrer' => 'https://google.com',
            'utm_data' => ['utm_source' => 'google'],
            'device_type' => 'desktop',
            'browser' => 'chrome',
            'os' => 'windows',
            'country' => 'Kenya',
            'city' => 'Nairobi',
            'event_time' => now(),
        ]);

        expect($analytics->tenant_id)->toBe($this->tenant->id);
        expect($analytics->template_id)->toBe($this->template->id);
        expect($analytics->landing_page_id)->toBe($this->landingPage->id);
        expect($analytics->event_type)->toBe('page_view');
        expect($analytics->device_type)->toBe('desktop');
        expect($analytics->is_compliant)->toBe(true);
        expect($analytics->consent_given)->toBe(true);
        expect($analytics->canRetainData())->toBe(true);
    }

    public function test_belongs_to_landing_page_relationship()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'landing_page_id' => $this->landingPage->id,
        ]);

        expect($analytics->landingPage)->not->toBeNull();
        expect($analytics->landingPage->id)->toBe($this->landingPage->id);
    }

    public function test_belongs_to_tenant_relationship()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        expect($analytics->tenant)->not->toBeNull();
        expect($analytics->tenant->id)->toBe($this->tenant->id);
    }

    public function test_belongs_to_template_relationship()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'template_id' => $this->template->id,
        ]);

        expect($analytics->template)->not->toBeNull();
        expect($analytics->template->id)->toBe($this->template->id);
    }

    public function test_scope_by_tenant()
    {
        LandingPageAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'tenant_id' => $this->tenant->id,
            'landing_page_id' => $this->landingPage->id,
        ]);

        $otherTenant = Tenant::factory()->create();
        $otherLandingPage = LandingPage::factory()->create(['tenant_id' => $otherTenant->id]);
        LandingPageAnalytics::factory()->create([
            'tenant_id' => $otherTenant->id,
            'landing_page_id' => $otherLandingPage->id,
        ]);

        $tenantEvents = LandingPageAnalytics::byTenant($this->tenant->id)->get();

        expect($tenantEvents)->toHaveCount(2);
        $tenantEvents->each(function ($analytics) {
            expect($analytics->tenant_id)->toBe($this->tenant->id);
        });
    }

    public function test_scope_by_template()
    {
        LandingPageAnalytics::factory()->create([
            'template_id' => $this->template->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'template_id' => $this->template->id,
        ]);

        $otherTemplate = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        LandingPageAnalytics::factory()->create([
            'template_id' => $otherTemplate->id,
        ]);

        $templateEvents = LandingPageAnalytics::byTemplate($this->template->id)->get();

        expect($templateEvents)->toHaveCount(2);
        $templateEvents->each(function ($analytics) {
            expect($analytics->template_id)->toBe($this->template->id);
        });
    }

    public function test_scope_by_event_type()
    {
        LandingPageAnalytics::factory()->create([
            'event_type' => 'page_view',
        ]);
        LandingPageAnalytics::factory()->create([
            'event_type' => 'page_view',
        ]);
        LandingPageAnalytics::factory()->create([
            'event_type' => 'conversion',
        ]);

        $pageViewEvents = LandingPageAnalytics::byEventType('page_view')->get();

        expect($pageViewEvents)->toHaveCount(2);
        $pageViewEvents->each(function ($analytics) {
            expect($analytics->event_type)->toBe('page_view');
        });
    }

    public function test_scope_by_date_range()
    {
        $startDate = now()->subDays(5);
        $endDate = now();

        LandingPageAnalytics::factory()->create([
            'event_time' => now()->subDays(3),
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'event_time' => now()->subDays(10), // Outside range
            'landing_page_id' => $this->landingPage->id,
        ]);

        $dateRangeEvents = LandingPageAnalytics::byDateRange($startDate, $endDate)->get();

        expect($dateRangeEvents)->toHaveCount(1);
        expect($dateRangeEvents->first()->event_time)->toBeBetween($startDate, $endDate);
    }

    public function test_scope_compliant()
    {
        LandingPageAnalytics::factory()->create([
            'is_compliant' => true,
            'consent_given' => true,
        ]);
        LandingPageAnalytics::factory()->create([
            'is_compliant' => false,
        ]);
        LandingPageAnalytics::factory()->create([
            'consent_given' => false,
        ]);

        $compliantEvents = LandingPageAnalytics::compliant()->get();

        expect($compliantEvents)->toHaveCount(1);
        expect($compliantEvents->first()->is_compliant)->toBe(true);
        expect($compliantEvents->first()->consent_given)->toBe(true);
    }

    public function test_privacy_anonymization()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'visitor_id' => 'visitor_123',
            'country' => 'United States',
            'city' => 'New York',
            'is_compliant' => true,
        ]);

        $analytics->anonymize();

        expect($analytics->fresh()->ip_address)->toBeNull();
        expect($analytics->fresh()->user_agent)->toBe('anonymized');
        expect($analytics->fresh()->visitor_id)->toBeNull();
        expect($analytics->fresh()->country)->toBeNull();
        expect($analytics->fresh()->city)->toBeNull();
        expect($analytics->fresh()->is_compliant)->toBe(false);
    }

    public function test_can_retain_data_logic()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'data_retention_until' => now()->addDays(30),
        ]);

        expect($analytics->canRetainData())->toBe(true);

        // Update retention date to past
        $analytics->update(['data_retention_until' => now()->subDays(1)]);

        expect($analytics->fresh()->canRetainData())->toBe(false);
    }

    public function test_casts_properties_correctly()
    {
        $eventData = ['form_id' => 'contact_form', 'source' => 'hero'];

        $analytics = LandingPageAnalytics::factory()->create([
            'event_data' => $eventData,
            'utm_data' => ['utm_source' => 'google'],
            'is_compliant' => '0', // String that should be cast to boolean
            'consent_given' => '1',
            'data_retention_until' => '2025-12-01 00:00:00',
            'event_time' => '2024-01-01 12:00:00',
        ]);

        expect($analytics->event_data)->toBe($eventData);
        expect($analytics->utm_data)->toBe(['utm_source' => 'google']);
        expect($analytics->is_compliant)->toBe(false); // Casted to boolean
        expect($analytics->consent_given)->toBe(true);
        expect($analytics->data_retention_until)->toBeInstanceOf(\Carbon\Carbon::class);
        expect($analytics->event_time)->toBeInstanceOf(\Carbon\Carbon::class);
    }

    public function test_fillable_attributes_include_tenant_fields()
    {
        $expectedFillable = [
            'tenant_id',
            'landing_page_id',
            'template_id',
            'event_type',
            'event_name',
            'event_data',
            'session_id',
            'visitor_id',
            'ip_address',
            'user_agent',
            'referrer',
            'utm_data',
            'device_type',
            'browser',
            'os',
            'country',
            'city',
            'event_time',
            'is_compliant',
            'consent_given',
            'data_retention_until',
            'analytics_version',
        ];

        $analytics = new LandingPageAnalytics();

        // Allow for both exact match and subset check
        foreach ($expectedFillable as $attribute) {
            expect($analytics->getFillable())->toContain($attribute);
        }
    }

    public function test_device_detection_analytics()
    {
        // Create events with different device types
        LandingPageAnalytics::factory()->create([
            'device_type' => 'desktop',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'device_type' => 'mobile',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'device_type' => 'tablet',
            'landing_page_id' => $this->landingPage->id,
        ]);

        $desktopEvents = LandingPageAnalytics::where('device_type', 'desktop')->get();
        $mobileEvents = LandingPageAnalytics::where('device_type', 'mobile')->get();
        $tabletEvents = LandingPageAnalytics::where('device_type', 'tablet')->get();

        expect($desktopEvents)->toHaveCount(1);
        expect($mobileEvents)->toHaveCount(1);
        expect($tabletEvents)->toHaveCount(1);
    }

    public function test_broswer_detection_analytics()
    {
        // Create events with different browsers
        LandingPageAnalytics::factory()->create([
            'browser' => 'chrome',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'browser' => 'firefox',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'browser' => 'safari',
            'landing_page_id' => $this->landingPage->id,
        ]);

        $chromeEvents = LandingPageAnalytics::where('browser', 'chrome')->get();
        $firefoxEvents = LandingPageAnalytics::where('browser', 'firefox')->get();
        $safariEvents = LandingPageAnalytics::where('browser', 'safari')->get();

        expect($chromeEvents)->toHaveCount(1);
        expect($firefoxEvents)->toHaveCount(1);
        expect($safariEvents)->toHaveCount(1);
    }

    public function test_geographic_distribution_tracking()
    {
        // Create events with different countries
        LandingPageAnalytics::factory()->create([
            'country' => 'Kenya',
            'city' => 'Nairobi',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'country' => 'United States',
            'city' => 'New York',
            'landing_page_id' => $this->landingPage->id,
        ]);
        LandingPageAnalytics::factory()->create([
            'country' => 'United Kingdom',
            'city' => 'London',
            'landing_page_id' => $this->landingPage->id,
        ]);

        $kenyaEvents = LandingPageAnalytics::where('country', 'Kenya')->get();
        $usEvents = LandingPageAnalytics::where('country', 'United States')->get();
        $ukEvents = LandingPageAnalytics::where('country', 'United Kingdom')->get();

        expect($kenyaEvents)->toHaveCount(1);
        expect($kenyaEvents->first()->city)->toBe('Nairobi');
        expect($usEvents)->toHaveCount(1);
        expect($ukEvents)->toHaveCount(1);
    }

    public function test_handles_missing_template_relationship()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'template_id' => null, // Landing page without template
            'landing_page_id' => $this->landingPage->id,
        ]);

        expect($analytics->template)->toBeNull();
        expect($analytics->canRetainData())->toBe(true);
    }

    public function test_handles_missing_tenant_context()
    {
        $analytics = LandingPageAnalytics::factory()->create([
            'tenant_id' => null, // System-level event
            'landing_page_id' => $this->landingPage->id,
        ]);

        expect($analytics->tenant)->toBeNull();
        expect($analytics->canRetainData())->toBe(true);
        expect($analytics->is_compliant)->toBe(true);
    }
}