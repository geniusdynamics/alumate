<?php

namespace Tests\Integration;

use App\Models\LandingPage;
use App\Models\User;
use Tests\TestCase;

/**
 * API endpoint integration testing
 */
class ApiIntegrationTest extends TestCase
{
    public function test_all_api_endpoints_work_end_to_end(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test landing page API endpoints
        $this->test_landing_page_crud_api();
        $this->test_analytics_api();
        $this->test_form_submission_api();
    }

    private function test_landing_page_crud_api(): void
    {
        // Create landing page via API
        $response = $this->postJson('/api/landing-pages', [
            'name' => 'API Test Page',
            'slug' => 'api-test-page',
            'target_audience' => 'alumni',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $landingPageId = $response->json('data.id');

        // Read landing page via API
        $response = $this->getJson("/api/landing-pages/{$landingPageId}");
        $response->assertStatus(200);
        $response->assertJson(['data' => ['name' => 'API Test Page']]);

        // Update landing page via API
        $response = $this->putJson("/api/landing-pages/{$landingPageId}", [
            'name' => 'Updated API Test Page',
            'status' => 'published',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['data' => ['name' => 'Updated API Test Page']]);

        // Delete landing page via API
        $response = $this->deleteJson("/api/landing-pages/{$landingPageId}");
        $response->assertStatus(204);
    }

    private function test_analytics_api(): void
    {
        $landingPage = LandingPage::factory()->create(['status' => 'published']);

        // Generate some analytics data
        $this->get("/landing/{$landingPage->slug}");
        $this->post("/landing/{$landingPage->slug}/submit", [
            'first_name' => 'API',
            'last_name' => 'Test',
            'email' => 'api@test.com',
        ]);

        // Test analytics API
        $response = $this->getJson("/api/landing-pages/{$landingPage->id}/analytics");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'page_views',
                'form_submissions',
                'conversion_rate',
                'analytics' => [
                    '*' => [
                        'event_type',
                        'event_data',
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    private function test_form_submission_api(): void
    {
        $landingPage = LandingPage::factory()->create(['status' => 'published']);

        // Test form submission API
        $response = $this->postJson("/api/landing-pages/{$landingPage->slug}/submit", [
            'first_name' => 'Form',
            'last_name' => 'Test',
            'email' => 'form@test.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify submission was created
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $landingPage->id,
            'status' => 'processed',
        ]);

        // Verify lead was created
        $this->assertDatabaseHas('leads', [
            'email' => 'form@test.com',
            'source' => 'landing_page',
        ]);
    }
}
