<?php

namespace Tests\Unit\Services;

use App\Models\AnalyticsEvent;
use App\Models\LandingPage;
use App\Models\LandingPageAnalytics;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TemplateAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TemplateAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateAnalyticsService $service;
    protected Tenant $tenant;
    protected User $user;
    protected Template $template;
    protected LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TemplateAnalyticsService::class);

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['institution_id' => $this->tenant->id]);
        $this->template = Template::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->landingPage = LandingPage::factory()->create([
            'template_id' => $this->template->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Act as the authenticated user
        $this->actingAs($this->user);
    }

    public function test_can_track_template_usage()
    {
        $result = $this->service->trackTemplateUsage(
            $this->template->id,
            $this->tenant->id,
            'template_view'
        );

        expect($result)->toBe(true);

        // Verify template usage was tracked
        expect($this->template->fresh()->usage_count)->toBe(1);

        // Verify analytics event was created
        expect(AnalyticsEvent::where('properties->template_id', $this->template->id)->count())->toBe(1);
        $event = AnalyticsEvent::first();
        expect($event->tenant_id)->toEqual($this->tenant->id);
        expect($event->properties['tenant_id'])->toEqual($this->tenant->id);
    }

    public function test_can_track_conversion()
    {
        $result = $this->service->trackConversion(
            $this->landingPage->id,
            'form_submit',
            ['source' => 'hero_section']
        );

        expect($result)->toBe(true);

        // Verify landing page conversion count
        expect($this->landingPage->fresh()->conversion_count)->toBe(1);

        // Verify analytics events were created
        expect(AnalyticsEvent::where('properties->landing_page_id', $this->landingPage->id)->count())->toBe(1);
        expect(LandingPageAnalytics::where('landing_page_id', $this->landingPage->id)->count())->toBe(1);

        // Verify tenant isolation in landing page analytics
        $lpEvent = LandingPageAnalytics::first();
        expect($lpEvent->tenant_id)->toEqual($this->tenant->id);
        expect($lpEvent->template_id)->toEqual($this->template->id);
    }

    public function test_can_track_page_view()
    {
        $result = $this->service->trackPageView(
            $this->landingPage->id,
            ['campaign' => 'winter_promo']
        );

        expect($result)->toBe(true);

        // Verify landing page analytics event
        expect(LandingPageAnalytics::where('event_type', 'page_view')->count())->toBe(1);

        // Verify template usage tracking
        expect(AnalyticsEvent::where('event_name', 'template_page_view')->count())->toBe(1);

        $event = AnalyticsEvent::where('event_name', 'template_page_view')->first();
        expect($event->properties['tracking_type'])->toBe('automatic');
    }

    public function test_can_get_template_analytics()
    {
        // Create some test data
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_use');

        $analytics = $this->service->getTemplateAnalytics($this->template->id);

        expect($analytics)->toBeArray();
        expect($analytics['template_id'])->toBe($this->template->id);
        expect($analytics['usage_stats']['total_views'])->toBe(1);
        expect($analytics['usage_stats']['total_uses'])->toBe(1);
    }

    public function test_can_get_analytics_dashboard()
    {
        // Create test data
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');
        $this->service->trackConversion($this->landingPage->id, 'form_submit');

        $dashboard = $this->service->getAnalyticsDashboard([
            'tenant_id' => $this->tenant->id
        ]);

        expect($dashboard)->toBeArray();
        expect($dashboard['tenant_isolation'])->toBe(true);
        expect($dashboard['template_usage'])->toBeArray();
        expect($dashboard['landing_page_performance'])->toBeArray();
    }

    public function test_handles_tenant_isolation_correctly()
    {
        // Create second tenant
        $otherTenant = Tenant::factory()->create();
        $otherTemplate = Template::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherUser = User::factory()->create(['institution_id' => $otherTenant->id]);

        $this->actingAs($otherUser);

        // Track usage for second tenant
        $this->service->trackTemplateUsage($otherTemplate->id, $otherTenant->id, 'template_view');

        // Verify tenant isolation - first tenant should not see second tenant's data
        $analytics = $this->service->getTemplateAnalytics($this->template->id, [
            'tenant_id' => $this->tenant->id
        ]);

        expect($analytics['usage_stats']['total_views'])->toBe(0);

        // Verify second tenant sees their data
        $otherAnalytics = $this->service->getTemplateAnalytics($otherTemplate->id, [
            'tenant_id' => $otherTenant->id
        ]);

        expect($otherAnalytics['usage_stats']['total_views'])->toBe(1);
    }

    public function test_generates_performance_recommendations()
    {
        // Create low-performing template data
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');

        $analytics = $this->service->getTemplateAnalytics($this->template->id);
        expect($analytics['recommendations'])->toBeArray();
        expect(count($analytics['recommendations']))->toBeGreaterThan(0);
    }

    public function test_caches_analytics_data()
    {
        // Track usage
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');

        // Get analytics (this will cache)
        $analytics1 = $this->service->getTemplateAnalytics($this->template->id);

        // Modify underlying data directly
        Template::where('id', $this->template->id)->update(['usage_count' => 10]);

        // Get analytics again (should get cached data)
        $analytics2 = $this->service->getTemplateAnalytics($this->template->id);

        // Should be same as cached data (not updated)
        expect($analytics1['usage_stats']['total_views'])->toBe($analytics2['usage_stats']['total_views']);
    }

    public function test_handles_edge_cases_gracefully()
    {
        // Test with invalid template ID
        $result = $this->service->trackTemplateUsage(999999, $this->tenant->id, 'template_view');
        expect($result)->toBe(false);

        // Test with invalid landing page ID
        $result = $this->service->trackConversion(999999, 'form_submit');
        expect($result)->toBe(false);

        // Test analytics for non-existent template
        $analytics = $this->service->getTemplateAnalytics(999999);
        expect($analytics)->toBe([]);

        // Test dashboard with no data
        $dashboard = $this->service->getAnalyticsDashboard();
        expect($dashboard)->toBeArray();
        expect($dashboard['template_usage']['total_views'])->toBe(0);
    }

    public function test_generates_comprehensive_analytics_reports()
    {
        // Create comprehensive test data
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_use');
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_customize');
        $this->service->trackConversion($this->landingPage->id, 'form_submit');
        $this->service->trackPageView($this->landingPage->id);

        $report = $this->service->generateTemplateReport($this->template->id);

        expect($report)->toBeArray();
        expect($report['report_type'])->toBe('template_performance');
        expect($report['data'])->toBeArray();
        expect(count($report['recommendations']))->toBeGreaterThan(0);
        expect($report['tenant_context'])->toBe(true);
    }

    public function test_comparative_analysis_between_templates()
    {
        // Create second template
        $template2 = Template::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create different usage patterns
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');
        $this->service->trackTemplateUsage($template2->id, $this->tenant->id, 'template_view');
        $this->service->trackTemplateUsage($template2->id, $this->tenant->id, 'template_use');

        $comparison = $this->service->generateComparativeAnalysis([
            $this->template->id,
            $template2->id
        ], [
            'tenant_id' => $this->tenant->id
        ]);

        expect($comparison)->toBeArray();
        expect($comparison['analysis_type'])->toBe('template_comparison');
        expect(count($comparison['templates_compared']))->toBe(2);
        expect($comparison['insights'])->toBeArray();
        expect($comparison['recommendations'])->toBeArray();
    }

    public function test_privacy_compliance_features()
    {
        // Track usage
        $this->service->trackTemplateUsage($this->template->id, $this->tenant->id, 'template_view');

        // Get analytics event
        $event = AnalyticsEvent::first();

        // Test privacy compliance
        expect($event->is_compliant)->toBe(true);
        expect($event->consent_given)->toBe(true);
        expect($event->analytics_version)->toBe('v1.0');
        expect($event->data_retention_until)->not->toBeNull();

        // Test can retain data method
        expect($event->canRetainData())->toBe(true);
    }

    public function test_gdpr_data_anonymization()
    {
        // Create event with personal data
        $event = AnalyticsEvent::factory()->create([
            'user_identifier' => 'user123',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'is_compliant' => true,
        ]);

        // Anonymize the data
        $event->anonymize();

        // Verify data is anonymized
        expect($event->fresh()->ip_address)->toBeNull();
        expect($event->fresh()->user_agent)->toBe('anonymized');
        expect($event->fresh()->user_identifier)->toBeNull();
        expect($event->fresh()->is_compliant)->toBe(false);
    }

    public function test_gdpr_data_retention_policy()
    {
        // Create event with past retention date
        $event = AnalyticsEvent::factory()->create([
            'data_retention_until' => now()->subDays(1),
        ]);

        expect($event->canRetainData())->toBe(false);

        // Create event with future retention date
        $event2 = AnalyticsEvent::factory()->create([
            'data_retention_until' => now()->addDays(30),
        ]);

        expect($event2->canRetainData())->toBe(true);
    }

    public function test_gdpr_compliance_scopes()
    {
        // Create compliant and non-compliant events
        AnalyticsEvent::factory()->count(3)->create(['is_compliant' => true, 'consent_given' => true]);
        AnalyticsEvent::factory()->count(2)->create(['is_compliant' => false, 'consent_given' => true]);
        AnalyticsEvent::factory()->count(2)->create(['is_compliant' => true, 'consent_given' => false]);

        $compliantEvents = AnalyticsEvent::gdprCompliant()->get();
        expect($compliantEvents)->toHaveCount(3);

        $retainableEvents = AnalyticsEvent::retainable()->get();
        expect($retainableEvents->count())->toBeGreaterThan(0);
    }

    public function test_gdpr_data_export()
    {
        $userIdentifier = 'test_user_123';

        // Create events for user
        AnalyticsEvent::factory()->count(3)->create([
            'user_identifier' => $userIdentifier,
            'is_compliant' => true,
            'consent_given' => true,
        ]);

        $exportData = $this->service->exportUserData($userIdentifier);

        expect($exportData)->toBeArray();
        expect($exportData['user_identifier'])->toBe($userIdentifier);
        expect($exportData['analytics_events'])->toHaveCount(3);
        expect($exportData['gdpr_rights'])->toBeArray();
    }

    public function test_gdpr_data_deletion()
    {
        $userIdentifier = 'test_user_delete';

        // Create events for user
        AnalyticsEvent::factory()->count(5)->create([
            'user_identifier' => $userIdentifier,
        ]);

        $deletedCount = $this->service->deleteUserData($userIdentifier);

        expect($deletedCount)->toBe(5);
        expect(AnalyticsEvent::where('user_identifier', $userIdentifier)->count())->toBe(0);
    }

    public function test_gdpr_compliance_statistics()
    {
        // Create test data
        AnalyticsEvent::factory()->count(10)->create(['is_compliant' => true, 'consent_given' => true]);
        AnalyticsEvent::factory()->count(5)->create(['is_compliant' => false, 'consent_given' => true]);
        AnalyticsEvent::factory()->count(3)->create([
            'is_compliant' => true,
            'consent_given' => true,
            'data_retention_until' => now()->addDays(15),
        ]);

        $stats = $this->service->getGdprComplianceStats();

        expect($stats)->toBeArray();
        expect($stats['total_events'])->toBe(18);
        expect($stats['compliant_events'])->toBe(13);
        expect($stats['consent_given_events'])->toBe(15);
        expect($stats['expiring_soon'])->toBeGreaterThanOrEqual(3);
    }

    public function test_gdpr_data_anonymization_cleanup()
    {
        // Create old events that should be anonymized
        AnalyticsEvent::factory()->count(3)->create([
            'created_at' => now()->subDays(100),
            'user_identifier' => 'old_user',
            'ip_address' => '192.168.1.100',
        ]);

        $anonymizedCount = $this->service->anonymizeOldData(90);

        expect($anonymizedCount)->toBe(3);

        $oldEvents = AnalyticsEvent::where('created_at', '<', now()->subDays(90))->get();
        foreach ($oldEvents as $event) {
            expect($event->ip_address)->toBeNull();
            expect($event->user_identifier)->toBeNull();
        }
    }

    public function test_gdpr_expired_data_cleanup()
    {
        // Create events with expired retention
        AnalyticsEvent::factory()->count(4)->create([
            'data_retention_until' => now()->subDays(1),
        ]);

        $deletedCount = $this->service->deleteExpiredData();

        expect($deletedCount)->toBe(4);
    }

    public function test_device_and_geographic_analytics()
    {
        // Track with manual device data
        $this->service->trackPageView($this->landingPage->id, [
            'device_type' => 'mobile',
            'country' => 'Kenya',
            'city' => 'Nairobi'
        ]);

        $event = LandingPageAnalytics::first();
        expect($event->device_type)->toBe('mobile');
        expect($event->country)->toBe('Kenya');
        expect($event->city)->toBe('Nairobi');
    }

    public function test_template_conversions_affect_template_metrics()
    {
        $initialMetrics = $this->template->performance_metrics ?? [];

        $this->service->trackConversion($this->landingPage->id, 'form_submit');

        $updatedTemplate = Template::find($this->template->id);
        $updatedMetrics = $updatedTemplate->performance_metrics ?? [];

        expect($updatedMetrics)->not->toBe($initialMetrics);
        expect($updatedMetrics['total_conversions'] ?? 0)->toBeGreaterThanOrEqual(1);
    }
}