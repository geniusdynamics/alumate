<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BrandedAppsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_branded_apps_data_via_api()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'featured_apps' => [
                            '*' => [
                                'id',
                                'institution_name',
                                'institution_type',
                                'logo',
                                'app_icon',
                                'app_store_url',
                                'play_store_url',
                                'screenshots' => [
                                    '*' => [
                                        'id',
                                        'url',
                                        'title',
                                        'description',
                                        'device',
                                        'category'
                                    ]
                                ],
                                'customizations' => [
                                    '*' => [
                                        'category',
                                        'name',
                                        'description',
                                        'implemented',
                                        'complexity'
                                    ]
                                ],
                                'user_count',
                                'engagement_stats' => [
                                    '*' => [
                                        'metric',
                                        'value',
                                        'unit',
                                        'trend',
                                        'period'
                                    ]
                                ],
                                'launch_date',
                                'featured'
                            ]
                        ],
                        'customization_options' => [
                            '*' => [
                                'id',
                                'category',
                                'name',
                                'description',
                                'options' => [
                                    '*' => [
                                        'id',
                                        'name',
                                        'description',
                                        'type',
                                        'required'
                                    ]
                                ],
                                'examples',
                                'level'
                            ]
                        ],
                        'app_store_integration',
                        'development_timeline' => [
                            'phases' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'description',
                                    'duration',
                                    'deliverables',
                                    'dependencies',
                                    'milestones' => [
                                        '*' => [
                                            'id',
                                            'name',
                                            'description',
                                            'due_date',
                                            'status'
                                        ]
                                    ]
                                ]
                            ],
                            'total_duration',
                            'estimated_cost',
                            'maintenance_cost'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
    }

    /** @test */
    public function it_returns_featured_apps_with_valid_data()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        $this->assertIsArray($featuredApps);
        $this->assertNotEmpty($featuredApps);

        foreach ($featuredApps as $app) {
            $this->assertIsString($app['id']);
            $this->assertIsString($app['institution_name']);
            $this->assertIsString($app['institution_type']);
            $this->assertIsString($app['logo']);
            $this->assertIsString($app['app_icon']);
            $this->assertIsInt($app['user_count']);
            $this->assertIsBool($app['featured']);
            $this->assertIsArray($app['screenshots']);
            $this->assertIsArray($app['customizations']);
            $this->assertIsArray($app['engagement_stats']);

            // Validate institution types
            $validInstitutionTypes = ['university', 'college', 'corporate', 'nonprofit'];
            $this->assertContains($app['institution_type'], $validInstitutionTypes);
        }
    }

    /** @test */
    public function it_returns_customization_options_with_valid_categories()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $customizationOptions = $data['customization_options'];

        $this->assertIsArray($customizationOptions);
        $this->assertNotEmpty($customizationOptions);

        $validCategories = ['branding', 'features', 'integrations', 'analytics'];
        $validLevels = ['basic', 'advanced', 'enterprise'];

        foreach ($customizationOptions as $option) {
            $this->assertContains($option['category'], $validCategories);
            $this->assertContains($option['level'], $validLevels);
            $this->assertIsArray($option['options']);
            $this->assertIsArray($option['examples']);
        }
    }

    /** @test */
    public function it_returns_app_store_integration_with_boolean_values()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $appStoreIntegration = $data['app_store_integration'];

        $this->assertIsArray($appStoreIntegration);

        $expectedKeys = [
            'apple_app_store',
            'google_play_store',
            'custom_domain',
            'white_label',
            'institution_branding',
            'review_management',
            'analytics_integration'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $appStoreIntegration);
            $this->assertIsBool($appStoreIntegration[$key]);
        }
    }

    /** @test */
    public function it_returns_development_timeline_with_valid_phases()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $developmentTimeline = $data['development_timeline'];

        $this->assertIsArray($developmentTimeline);
        $this->assertArrayHasKey('phases', $developmentTimeline);
        $this->assertArrayHasKey('total_duration', $developmentTimeline);
        $this->assertArrayHasKey('estimated_cost', $developmentTimeline);
        $this->assertArrayHasKey('maintenance_cost', $developmentTimeline);

        $phases = $developmentTimeline['phases'];
        $this->assertIsArray($phases);
        $this->assertNotEmpty($phases);

        foreach ($phases as $phase) {
            $this->assertIsString($phase['id']);
            $this->assertIsString($phase['name']);
            $this->assertIsString($phase['description']);
            $this->assertIsString($phase['duration']);
            $this->assertIsArray($phase['deliverables']);
            $this->assertIsArray($phase['dependencies']);
            $this->assertIsArray($phase['milestones']);

            foreach ($phase['milestones'] as $milestone) {
                $validStatuses = ['pending', 'in_progress', 'completed', 'delayed'];
                $this->assertContains($milestone['status'], $validStatuses);
            }
        }
    }

    /** @test */
    public function it_returns_engagement_stats_with_valid_metrics()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        foreach ($featuredApps as $app) {
            $engagementStats = $app['engagement_stats'];
            $this->assertIsArray($engagementStats);

            foreach ($engagementStats as $stat) {
                $validMetrics = ['daily_active_users', 'session_duration', 'feature_usage', 'retention_rate'];
                $validUnits = ['count', 'minutes', 'percentage'];
                $validTrends = ['up', 'down', 'stable'];

                $this->assertContains($stat['metric'], $validMetrics);
                $this->assertContains($stat['unit'], $validUnits);
                $this->assertContains($stat['trend'], $validTrends);
                $this->assertIsNumeric($stat['value']);
                $this->assertIsString($stat['period']);
            }
        }
    }

    /** @test */
    public function it_returns_screenshots_with_valid_device_types()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        $validDevices = ['iphone', 'android', 'tablet'];
        $validCategories = ['home', 'profile', 'networking', 'events', 'messaging'];

        foreach ($featuredApps as $app) {
            $screenshots = $app['screenshots'];
            $this->assertIsArray($screenshots);

            foreach ($screenshots as $screenshot) {
                $this->assertContains($screenshot['device'], $validDevices);
                $this->assertContains($screenshot['category'], $validCategories);
                $this->assertIsString($screenshot['url']);
                $this->assertIsString($screenshot['title']);
                $this->assertIsString($screenshot['description']);
            }
        }
    }

    /** @test */
    public function it_returns_customizations_with_valid_complexity_levels()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        $validCategories = ['branding', 'features', 'integrations', 'analytics'];
        $validComplexities = ['basic', 'advanced', 'custom'];

        foreach ($featuredApps as $app) {
            $customizations = $app['customizations'];
            $this->assertIsArray($customizations);

            foreach ($customizations as $customization) {
                $this->assertContains($customization['category'], $validCategories);
                $this->assertContains($customization['complexity'], $validComplexities);
                $this->assertIsBool($customization['implemented']);
                $this->assertIsString($customization['name']);
                $this->assertIsString($customization['description']);
            }
        }
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        // Mock a service that throws an exception
        $this->mock(\App\Services\HomepageService::class, function ($mock) {
            $mock->shouldReceive('getBrandedAppsData')
                 ->andThrow(new \Exception('Service unavailable'));
        });

        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(500)
                ->assertJson([
                    'success' => false,
                    'message' => 'Failed to fetch branded apps data'
                ]);
    }

    /** @test */
    public function it_returns_consistent_data_structure_across_requests()
    {
        $response1 = $this->getJson('/api/homepage/branded-apps');
        $response2 = $this->getJson('/api/homepage/branded-apps');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $data1 = $response1->json('data');
        $data2 = $response2->json('data');

        // Structure should be identical
        $this->assertEquals(array_keys($data1), array_keys($data2));
        $this->assertEquals(count($data1['featured_apps']), count($data2['featured_apps']));
        $this->assertEquals(count($data1['customization_options']), count($data2['customization_options']));
    }

    /** @test */
    public function it_includes_all_required_app_store_urls()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        $hasAppStoreUrl = false;
        $hasPlayStoreUrl = false;

        foreach ($featuredApps as $app) {
            if (isset($app['app_store_url']) && !empty($app['app_store_url'])) {
                $hasAppStoreUrl = true;
                $this->assertStringContainsString('apps.apple.com', $app['app_store_url']);
            }
            
            if (isset($app['play_store_url']) && !empty($app['play_store_url'])) {
                $hasPlayStoreUrl = true;
                $this->assertStringContainsString('play.google.com', $app['play_store_url']);
            }
        }

        $this->assertTrue($hasAppStoreUrl, 'Should include at least one App Store URL');
        $this->assertTrue($hasPlayStoreUrl, 'Should include at least one Play Store URL');
    }

    /** @test */
    public function it_returns_realistic_user_counts()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        foreach ($featuredApps as $app) {
            $userCount = $app['user_count'];
            $this->assertIsInt($userCount);
            $this->assertGreaterThan(0, $userCount);
            $this->assertLessThan(100000, $userCount); // Reasonable upper bound for alumni apps
        }
    }

    /** @test */
    public function it_includes_proper_launch_dates()
    {
        $response = $this->getJson('/api/homepage/branded-apps');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $featuredApps = $data['featured_apps'];

        foreach ($featuredApps as $app) {
            $this->assertArrayHasKey('launch_date', $app);
            $this->assertIsString($app['launch_date']);
            
            // Validate date format
            $date = \DateTime::createFromFormat('Y-m-d', $app['launch_date']);
            $this->assertInstanceOf(\DateTime::class, $date);
            
            // Should be a reasonable launch date (not in the future, not too old)
            $now = new \DateTime();
            $twoYearsAgo = (clone $now)->modify('-2 years');
            
            $this->assertLessThanOrEqual($now, $date, 'Launch date should not be in the future');
            $this->assertGreaterThanOrEqual($twoYearsAgo, $date, 'Launch date should be within reasonable timeframe');
        }
    }
}