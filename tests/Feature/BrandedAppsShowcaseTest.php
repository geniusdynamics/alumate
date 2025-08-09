<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\HomepageService;

class BrandedAppsShowcaseTest extends TestCase
{
    use RefreshDatabase;

    private HomepageService $homepageService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->homepageService = app(HomepageService::class);
    }

    /** @test */
    public function it_can_get_branded_apps_data()
    {
        $data = $this->homepageService->getBrandedAppsData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('featured_apps', $data);
        $this->assertArrayHasKey('customization_options', $data);
        $this->assertArrayHasKey('app_store_integration', $data);
        $this->assertArrayHasKey('development_timeline', $data);
    }

    /** @test */
    public function it_returns_featured_apps_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $featuredApps = $data['featured_apps'];

        $this->assertIsArray($featuredApps);
        $this->assertNotEmpty($featuredApps);

        $firstApp = $featuredApps[0];
        $this->assertArrayHasKey('id', $firstApp);
        $this->assertArrayHasKey('institution_name', $firstApp);
        $this->assertArrayHasKey('institution_type', $firstApp);
        $this->assertArrayHasKey('logo', $firstApp);
        $this->assertArrayHasKey('app_icon', $firstApp);
        $this->assertArrayHasKey('screenshots', $firstApp);
        $this->assertArrayHasKey('customizations', $firstApp);
        $this->assertArrayHasKey('user_count', $firstApp);
        $this->assertArrayHasKey('engagement_stats', $firstApp);
        $this->assertArrayHasKey('launch_date', $firstApp);
        $this->assertArrayHasKey('featured', $firstApp);
    }

    /** @test */
    public function it_returns_screenshots_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $firstApp = $data['featured_apps'][0];
        $screenshots = $firstApp['screenshots'];

        $this->assertIsArray($screenshots);
        $this->assertNotEmpty($screenshots);

        $firstScreenshot = $screenshots[0];
        $this->assertArrayHasKey('id', $firstScreenshot);
        $this->assertArrayHasKey('url', $firstScreenshot);
        $this->assertArrayHasKey('title', $firstScreenshot);
        $this->assertArrayHasKey('description', $firstScreenshot);
        $this->assertArrayHasKey('device', $firstScreenshot);
        $this->assertArrayHasKey('category', $firstScreenshot);
    }

    /** @test */
    public function it_returns_customization_options_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $customizationOptions = $data['customization_options'];

        $this->assertIsArray($customizationOptions);
        $this->assertNotEmpty($customizationOptions);

        $firstOption = $customizationOptions[0];
        $this->assertArrayHasKey('id', $firstOption);
        $this->assertArrayHasKey('category', $firstOption);
        $this->assertArrayHasKey('name', $firstOption);
        $this->assertArrayHasKey('description', $firstOption);
        $this->assertArrayHasKey('options', $firstOption);
        $this->assertArrayHasKey('examples', $firstOption);
        $this->assertArrayHasKey('level', $firstOption);
    }

    /** @test */
    public function it_returns_app_store_integration_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $appStoreIntegration = $data['app_store_integration'];

        $this->assertIsArray($appStoreIntegration);
        $this->assertArrayHasKey('apple_app_store', $appStoreIntegration);
        $this->assertArrayHasKey('google_play_store', $appStoreIntegration);
        $this->assertArrayHasKey('custom_domain', $appStoreIntegration);
        $this->assertArrayHasKey('white_label', $appStoreIntegration);
        $this->assertArrayHasKey('institution_branding', $appStoreIntegration);
        $this->assertArrayHasKey('review_management', $appStoreIntegration);
        $this->assertArrayHasKey('analytics_integration', $appStoreIntegration);

        // Verify all values are boolean
        foreach ($appStoreIntegration as $key => $value) {
            $this->assertIsBool($value, "Expected {$key} to be boolean");
        }
    }

    /** @test */
    public function it_returns_development_timeline_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $developmentTimeline = $data['development_timeline'];

        $this->assertIsArray($developmentTimeline);
        $this->assertArrayHasKey('phases', $developmentTimeline);
        $this->assertArrayHasKey('total_duration', $developmentTimeline);
        $this->assertArrayHasKey('estimated_cost', $developmentTimeline);
        $this->assertArrayHasKey('maintenance_cost', $developmentTimeline);

        $phases = $developmentTimeline['phases'];
        $this->assertIsArray($phases);
        $this->assertNotEmpty($phases);

        $firstPhase = $phases[0];
        $this->assertArrayHasKey('id', $firstPhase);
        $this->assertArrayHasKey('name', $firstPhase);
        $this->assertArrayHasKey('description', $firstPhase);
        $this->assertArrayHasKey('duration', $firstPhase);
        $this->assertArrayHasKey('deliverables', $firstPhase);
        $this->assertArrayHasKey('dependencies', $firstPhase);
        $this->assertArrayHasKey('milestones', $firstPhase);
    }

    /** @test */
    public function it_returns_engagement_stats_with_correct_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $firstApp = $data['featured_apps'][0];
        $engagementStats = $firstApp['engagement_stats'];

        $this->assertIsArray($engagementStats);
        $this->assertNotEmpty($engagementStats);

        $firstStat = $engagementStats[0];
        $this->assertArrayHasKey('metric', $firstStat);
        $this->assertArrayHasKey('value', $firstStat);
        $this->assertArrayHasKey('unit', $firstStat);
        $this->assertArrayHasKey('trend', $firstStat);
        $this->assertArrayHasKey('period', $firstStat);

        // Verify metric types
        $validMetrics = ['daily_active_users', 'session_duration', 'feature_usage', 'retention_rate'];
        $this->assertContains($firstStat['metric'], $validMetrics);

        // Verify units
        $validUnits = ['count', 'minutes', 'percentage'];
        $this->assertContains($firstStat['unit'], $validUnits);

        // Verify trends
        $validTrends = ['up', 'down', 'stable'];
        $this->assertContains($firstStat['trend'], $validTrends);
    }

    /** @test */
    public function it_includes_multiple_institution_types()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $featuredApps = $data['featured_apps'];

        $institutionTypes = array_column($featuredApps, 'institution_type');
        $uniqueTypes = array_unique($institutionTypes);

        // Should include both university and corporate types
        $this->assertContains('university', $uniqueTypes);
        $this->assertContains('corporate', $uniqueTypes);
    }

    /** @test */
    public function it_includes_both_ios_and_android_apps()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $featuredApps = $data['featured_apps'];

        $hasAppStore = false;
        $hasPlayStore = false;

        foreach ($featuredApps as $app) {
            if (!empty($app['app_store_url'])) {
                $hasAppStore = true;
            }
            if (!empty($app['play_store_url'])) {
                $hasPlayStore = true;
            }
        }

        $this->assertTrue($hasAppStore, 'Should include apps with App Store URLs');
        $this->assertTrue($hasPlayStore, 'Should include apps with Play Store URLs');
    }

    /** @test */
    public function it_includes_different_customization_categories()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $customizationOptions = $data['customization_options'];

        $categories = array_column($customizationOptions, 'category');
        $uniqueCategories = array_unique($categories);

        $expectedCategories = ['branding', 'features', 'integrations', 'analytics'];
        foreach ($expectedCategories as $expectedCategory) {
            $this->assertContains($expectedCategory, $uniqueCategories, 
                "Should include {$expectedCategory} customization category");
        }
    }

    /** @test */
    public function it_includes_development_phases_in_logical_order()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $phases = $data['development_timeline']['phases'];

        $this->assertGreaterThanOrEqual(3, count($phases), 'Should have at least 3 development phases');

        // Check that first phase is discovery/planning
        $firstPhase = $phases[0];
        $this->assertStringContainsString('Discovery', $firstPhase['name']);

        // Check that last phase is deployment/launch
        $lastPhase = end($phases);
        $this->assertStringContainsString('Deployment', $lastPhase['name']);
    }

    /** @test */
    public function it_includes_realistic_user_counts_and_engagement_metrics()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $featuredApps = $data['featured_apps'];

        foreach ($featuredApps as $app) {
            $this->assertIsInt($app['user_count']);
            $this->assertGreaterThan(0, $app['user_count']);
            $this->assertLessThan(100000, $app['user_count']); // Reasonable upper bound

            foreach ($app['engagement_stats'] as $stat) {
                $this->assertIsNumeric($stat['value']);
                $this->assertGreaterThan(0, $stat['value']);
            }
        }
    }

    /** @test */
    public function it_includes_proper_milestone_structure()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $phases = $data['development_timeline']['phases'];

        foreach ($phases as $phase) {
            $this->assertArrayHasKey('milestones', $phase);
            $milestones = $phase['milestones'];

            if (!empty($milestones)) {
                $firstMilestone = $milestones[0];
                $this->assertArrayHasKey('id', $firstMilestone);
                $this->assertArrayHasKey('name', $firstMilestone);
                $this->assertArrayHasKey('description', $firstMilestone);
                $this->assertArrayHasKey('due_date', $firstMilestone);
                $this->assertArrayHasKey('status', $firstMilestone);

                $validStatuses = ['pending', 'in_progress', 'completed', 'delayed'];
                $this->assertContains($firstMilestone['status'], $validStatuses);
            }
        }
    }

    /** @test */
    public function it_includes_proper_customization_complexity_levels()
    {
        $data = $this->homepageService->getBrandedAppsData();
        $featuredApps = $data['featured_apps'];

        foreach ($featuredApps as $app) {
            foreach ($app['customizations'] as $customization) {
                $this->assertArrayHasKey('complexity', $customization);
                $validComplexities = ['basic', 'advanced', 'custom'];
                $this->assertContains($customization['complexity'], $validComplexities);
            }
        }
    }
}