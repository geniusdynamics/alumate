<?php

namespace Tests\Feature;

use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PricingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_can_get_individual_pricing_plans()
    {
        $response = $this->getJson('/api/pricing/plans?audience=individual');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'audience',
                    'plans' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'billing_period',
                            'cta_text',
                            'featured',
                            'features' => [
                                '*' => [
                                    'name',
                                    'included',
                                ],
                            ],
                        ],
                    ],
                    'comparison_features' => [
                        '*' => [
                            'name',
                            'key',
                            'description',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('individual', $data['audience']);
        $this->assertCount(3, $data['plans']); // Free, Professional, Executive
        $this->assertGreaterThan(0, count($data['comparison_features']));
    }

    /** @test */
    public function it_can_get_institutional_pricing_plans()
    {
        $response = $this->getJson('/api/pricing/plans?audience=institutional');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('institutional', $data['audience']);
        $this->assertCount(3, $data['plans']); // Professional, Enterprise, Custom

        // Check for institutional-specific features
        $this->assertContains('Branded Mobile App', array_column($data['comparison_features'], 'name'));
        $this->assertContains('Admin Dashboard', array_column($data['comparison_features'], 'name'));
    }

    /** @test */
    public function it_validates_audience_parameter()
    {
        $response = $this->getJson('/api/pricing/plans?audience=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['audience']);
    }

    /** @test */
    public function it_requires_audience_parameter()
    {
        $response = $this->getJson('/api/pricing/plans');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['audience']);
    }

    /** @test */
    public function it_can_get_feature_comparison()
    {
        $response = $this->getJson('/api/pricing/feature-comparison?audience=individual');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'plans',
                    'features',
                    'comparison_matrix' => [
                        '*' => [
                            'feature',
                            'plan_values',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data['comparison_matrix']);
        $this->assertGreaterThan(0, count($data['comparison_matrix']));
    }

    /** @test */
    public function it_can_track_pricing_interactions()
    {
        $interactionData = [
            'event' => 'plan_viewed',
            'audience' => 'individual',
            'plan_id' => 'professional',
            'section' => 'pricing_cards',
            'additional_data' => [
                'scroll_depth' => 75,
                'time_on_section' => 30,
            ],
        ];

        $response = $this->postJson('/api/pricing/track-interaction', $interactionData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Interaction tracked successfully',
            ]);
    }

    /** @test */
    public function it_validates_tracking_data()
    {
        $response = $this->postJson('/api/pricing/track-interaction', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['event', 'audience']);
    }

    /** @test */
    public function it_can_get_pricing_statistics()
    {
        $response = $this->getJson('/api/pricing/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_plans',
                    'individual_plans',
                    'institutional_plans',
                    'featured_plans' => [
                        'individual',
                        'institutional',
                        'total',
                    ],
                    'price_ranges' => [
                        'individual' => [
                            'min',
                            'max',
                            'average',
                        ],
                        'institutional' => [
                            'min',
                            'max',
                            'average',
                        ],
                    ],
                    'last_updated',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(6, $data['total_plans']); // 3 individual + 3 institutional
        $this->assertEquals(3, $data['individual_plans']);
        $this->assertEquals(3, $data['institutional_plans']);
    }

    /** @test */
    public function individual_plans_have_correct_structure()
    {
        $response = $this->getJson('/api/pricing/plans?audience=individual');
        $plans = $response->json('data.plans');

        foreach ($plans as $plan) {
            $this->assertArrayHasKey('id', $plan);
            $this->assertArrayHasKey('name', $plan);
            $this->assertArrayHasKey('description', $plan);
            $this->assertArrayHasKey('price', $plan);
            $this->assertArrayHasKey('billing_period', $plan);
            $this->assertArrayHasKey('cta_text', $plan);
            $this->assertArrayHasKey('featured', $plan);
            $this->assertArrayHasKey('features', $plan);
            $this->assertIsArray($plan['features']);
            $this->assertIsBool($plan['featured']);
        }

        // Check specific plans exist
        $planIds = array_column($plans, 'id');
        $this->assertContains('free', $planIds);
        $this->assertContains('professional', $planIds);
        $this->assertContains('executive', $planIds);
    }

    /** @test */
    public function institutional_plans_have_correct_structure()
    {
        $response = $this->getJson('/api/pricing/plans?audience=institutional');
        $plans = $response->json('data.plans');

        foreach ($plans as $plan) {
            $this->assertArrayHasKey('id', $plan);
            $this->assertArrayHasKey('name', $plan);
            $this->assertArrayHasKey('description', $plan);
            $this->assertArrayHasKey('billing_period', $plan);
            $this->assertArrayHasKey('cta_text', $plan);
            $this->assertArrayHasKey('featured', $plan);
            $this->assertArrayHasKey('features', $plan);
        }

        // Check specific plans exist
        $planIds = array_column($plans, 'id');
        $this->assertContains('professional_inst', $planIds);
        $this->assertContains('enterprise_inst', $planIds);
        $this->assertContains('custom_inst', $planIds);

        // Custom plan should have null price
        $customPlan = collect($plans)->firstWhere('id', 'custom_inst');
        $this->assertNull($customPlan['price']);
    }

    /** @test */
    public function featured_plans_are_correctly_identified()
    {
        // Individual plans
        $response = $this->getJson('/api/pricing/plans?audience=individual');
        $plans = $response->json('data.plans');

        $featuredPlans = array_filter($plans, fn ($plan) => $plan['featured']);
        $this->assertCount(1, $featuredPlans);

        $featuredPlan = array_values($featuredPlans)[0];
        $this->assertEquals('professional', $featuredPlan['id']);

        // Institutional plans
        $response = $this->getJson('/api/pricing/plans?audience=institutional');
        $plans = $response->json('data.plans');

        $featuredPlans = array_filter($plans, fn ($plan) => $plan['featured']);
        $this->assertCount(1, $featuredPlans);

        $featuredPlan = array_values($featuredPlans)[0];
        $this->assertEquals('enterprise_inst', $featuredPlan['id']);
    }

    /** @test */
    public function comparison_matrix_includes_all_plans_and_features()
    {
        $response = $this->getJson('/api/pricing/feature-comparison?audience=individual');
        $data = $response->json('data');

        $plans = $data['plans'];
        $features = $data['features'];
        $matrix = $data['comparison_matrix'];

        // Each feature should have values for all plans
        foreach ($matrix as $featureRow) {
            $this->assertArrayHasKey('feature', $featureRow);
            $this->assertArrayHasKey('plan_values', $featureRow);

            foreach ($plans as $plan) {
                $this->assertArrayHasKey($plan['id'], $featureRow['plan_values']);
            }
        }

        // Number of matrix rows should equal number of features
        $this->assertCount(count($features), $matrix);
    }

    /** @test */
    public function pricing_data_is_cached()
    {
        // First request
        $response1 = $this->getJson('/api/pricing/plans?audience=individual');
        $response1->assertStatus(200);

        // Mock the service to return different data
        $this->mock(PricingService::class, function ($mock) {
            $mock->shouldReceive('getPlansForAudience')
                ->with('individual')
                ->never(); // Should not be called due to caching
        });

        // Second request should use cached data
        $response2 = $this->getJson('/api/pricing/plans?audience=individual');
        $response2->assertStatus(200);

        // Responses should be identical
        $this->assertEquals($response1->json(), $response2->json());
    }

    /** @test */
    public function statistics_include_correct_calculations()
    {
        $response = $this->getJson('/api/pricing/statistics');
        $data = $response->json('data');

        // Price ranges should be calculated correctly
        $individualRange = $data['price_ranges']['individual'];
        $this->assertEquals(0, $individualRange['min']); // Free plan
        $this->assertEquals(79, $individualRange['max']); // Executive plan
        $this->assertGreaterThan(0, $individualRange['average']);

        $institutionalRange = $data['price_ranges']['institutional'];
        $this->assertEquals(2500, $institutionalRange['min']); // Professional plan
        $this->assertEquals(7500, $institutionalRange['max']); // Enterprise plan
        $this->assertGreaterThan(0, $institutionalRange['average']);

        // Featured plans count
        $featuredPlans = $data['featured_plans'];
        $this->assertEquals(1, $featuredPlans['individual']);
        $this->assertEquals(1, $featuredPlans['institutional']);
        $this->assertEquals(2, $featuredPlans['total']);
    }

    /** @test */
    public function tracking_handles_optional_fields()
    {
        $minimalData = [
            'event' => 'section_viewed',
            'audience' => 'institutional',
        ];

        $response = $this->postJson('/api/pricing/track-interaction', $minimalData);
        $response->assertStatus(200);

        $fullData = [
            'event' => 'plan_selected',
            'audience' => 'individual',
            'plan_id' => 'professional',
            'section' => 'comparison_table',
            'additional_data' => [
                'previous_plan' => 'free',
                'conversion_source' => 'feature_comparison',
            ],
        ];

        $response = $this->postJson('/api/pricing/track-interaction', $fullData);
        $response->assertStatus(200);
    }
}
