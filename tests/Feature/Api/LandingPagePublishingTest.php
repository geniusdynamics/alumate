<?php

namespace Tests\Feature\Api;

use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Feature tests for Landing Page Publishing API
 *
 * @covers \App\Http\Controllers\Api\LandingPageController
 */
class LandingPagePublishingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->landingPage = LandingPage::factory()->create([
            'status' => 'draft',
            'name' => 'Test Landing Page',
            'slug' => 'test-landing-page',
            'config' => ['title' => 'Test Page'],
            'created_by' => $this->user->id,
        ]);
    }

    public function test_user_can_publish_landing_page()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish");

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'message' => 'Landing page published successfully',
                    'landing_page.status' => 'published',
                    'landing_page.version' => 2,
                ])
                ->assertJsonStructure([
                    'landing_page' => [
                        'id', 'name', 'slug', 'status', 'published_at',
                        'public_url', 'version', 'updated_by'
                    ]
                ]);

        // Verify database changes
        $this->landingPage->refresh();
        $this->assertEquals('published', $this->landingPage->status);
        $this->assertNotNull($this->landingPage->published_at);
        $this->assertNotNull($this->landingPage->public_url);
    }

    public function test_user_can_publish_with_custom_domain()
    {
        // Arrange
        $this->actingAs($this->user);
        $customDomain = 'custom.example.com';

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish", [
            'custom_domain' => $customDomain
        ]);

        // Assert
        $response->assertSuccessful();
        $this->landingPage->refresh();

        $expectedUrl = "https://{$customDomain}/{$this->landingPage->slug}";
        $this->assertEquals($expectedUrl, $this->landingPage->public_url);
    }

    public function test_user_can_publish_with_scheduled_date()
    {
        // Arrange
        $this->actingAs($this->user);
        $scheduledTime = now()->addHours(2);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish", [
            'publish_at' => $scheduledTime->toISOString()
        ]);

        // Assert
        $response->assertSuccessful();
        $this->landingPage->refresh();

        $this->assertEquals($scheduledTime->timestamp, $this->landingPage->published_at->timestamp);
    }

    public function test_user_can_unpublish_landing_page()
    {
        // Arrange
        $this->landingPage->update([
            'status' => 'published',
            'public_url' => 'https://example.com/test',
            'published_at' => now()
        ]);
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/unpublish");

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'message' => 'Landing page unpublished successfully',
                    'landing_page.status' => 'draft',
                ]);

        $this->landingPage->refresh();
        $this->assertEquals('draft', $this->landingPage->status);
        $this->assertNull($this->landingPage->published_at);
        $this->assertNull($this->landingPage->public_url);
    }

    public function test_user_requires_publish_permission()
    {
        // Arrange - Different user who doesn't own the page
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish");

        // Assert
        $response->assertForbidden();
    }

    public function test_publish_validates_required_fields()
    {
        // Arrange
        $this->landingPage->update(['name' => '']);
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish");

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name' => ['Landing page name is required']]);
    }

    public function test_publish_validates_custom_domain_format()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish", [
            'custom_domain' => 'invalid-domain'
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['custom_domain']);
    }

    public function test_publish_validates_future_publish_date()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish", [
            'publish_at' => now()->subHour()->toISOString() // Past date
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['publish_at']);
    }

    public function test_user_can_archive_landing_page()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/archive");

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'message' => 'Landing page archived successfully',
                    'landing_page.status' => 'archived',
                ]);

        $this->landingPage->refresh();
        $this->assertEquals('archived', $this->landingPage->status);
    }

    public function test_user_can_get_performance_metrics()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);
        $this->actingAs($this->user);

        // Act
        $response = $this->getJson("/api/landing-pages/{$this->landingPage->id}/performance");

        // Assert
        $response->assertSuccessful()
                ->assertJsonStructure([
                    'landing_page_id',
                    'timeframe',
                    'performance' => [
                        'page_views',
                        'unique_visitors',
                        'conversion_count',
                        'average_session_duration',
                        'bounce_rate',
                        'load_time',
                        'last_updated',
                    ],
                ]);
    }

    public function test_user_can_get_performance_with_custom_timeframe()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);
        $this->actingAs($this->user);

        // Act
        $response = $this->getJson("/api/landing-pages/{$this->landingPage->id}/performance?timeframe=30d");

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'timeframe' => '30d'
                ]);
    }

    public function test_user_can_get_cached_content()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);
        $this->actingAs($this->user);

        // Act
        $response = $this->getJson("/api/landing-pages/{$this->landingPage->id}/cached-content");

        // Assert
        $response->assertSuccessful()
                ->assertJsonStructure([
                    'cached_content' => [
                        'id', 'name', 'slug', 'config', 'seo_title',
                        'version', 'cache_timestamp'
                    ],
                    'cache_info' => [
                        'generated_at',
                        'landing_page_version'
                    ]
                ]);
    }

    public function test_user_can_bulk_publish_landing_pages()
    {
        // Arrange
        $pages = LandingPage::factory()->count(3)->create([
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $ids = $pages->pluck('id')->toArray();
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson('/api/landing-pages/bulk/publish', [
            'landing_page_ids' => $ids
        ]);

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'message' => 'Bulk publish operation completed',
                    'results.successful' => count($ids),
                    'results.failed' => 0,
                ]);

        // Verify all pages were published
        $updatedPages = LandingPage::whereIn('id', $ids)->get();
        $this->assertEquals(3, $updatedPages->where('status', 'published')->count());
    }

    public function test_user_can_bulk_unpublish_landing_pages()
    {
        // Arrange
        $pages = LandingPage::factory()->count(2)->create([
            'status' => 'published',
            'created_by' => $this->user->id,
        ]);

        $ids = $pages->pluck('id')->toArray();
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson('/api/landing-pages/bulk/unpublish', [
            'landing_page_ids' => $ids
        ]);

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'message' => 'Bulk unpublish operation completed',
                    'results.successful' => count($ids),
                    'results.failed' => 0,
                ]);

        // Verify all pages were unpublished
        $updatedPages = LandingPage::whereIn('id', $ids)->get();
        $this->assertEquals(2, $updatedPages->where('status', 'draft')->count());
    }

    public function test_bulk_publish_validates_max_limit()
    {
        // Arrange
        $pages = LandingPage::factory()->count(55)->create(['status' => 'draft']); // Over limit
        $ids = $pages->pluck('id')->toArray();
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson('/api/landing-pages/bulk/publish', [
            'landing_page_ids' => $ids
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['landing_page_ids']);
    }

    public function test_bulk_publish_validates_min_limit()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson('/api/landing-pages/bulk/publish', [
            'landing_page_ids' => [] // Empty array
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['landing_page_ids']);
    }

    public function test_bulk_publish_validates_existing_ids()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->postJson('/api/landing-pages/bulk/publish', [
            'landing_page_ids' => [999999] // Non-existent ID
        ]);

        // Assert
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['landing_page_ids.0']);
    }

    public function test_user_can_get_publishing_stats()
    {
        // Arrange
        LandingPage::factory()->count(3)->create(['status' => 'published']);
        LandingPage::factory()->count(2)->create(['status' => 'draft']);
        LandingPage::factory()->count(1)->create(['status' => 'archived']);

        $this->actingAs($this->user);

        // Act
        $response = $this->getJson('/api/landing-pages/publishing-stats');

        // Assert
        $response->assertSuccessful()
                ->assertJsonStructure([
                    'publishing_stats' => [
                        'total_pages',
                        'published_pages',
                        'draft_pages',
                        'archived_pages',
                        'reviewing_pages',
                        'suspended_pages',
                        'published_recently',
                        'timeframe',
                        'generated_at',
                    ],
                    'filters' => [
                        'tenant_id',
                        'timeframe'
                    ]
                ]);
    }

    public function test_user_can_get_publishing_stats_with_timeframe()
    {
        // Arrange
        $this->actingAs($this->user);

        // Act
        $response = $this->getJson('/api/landing-pages/publishing-stats?timeframe=30d');

        // Assert
        $response->assertSuccessful()
                ->assertJson([
                    'publishing_stats.timeframe' => '30d'
                ]);
    }

    public function test_user_can_get_url_suggestions()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);
        $this->actingAs($this->user);

        // Act
        $response = $this->getJson("/api/landing-pages/{$this->landingPage->id}/url-suggestions");

        // Assert
        $response->assertSuccessful()
                ->assertJsonStructure([
                    'current',
                    'auto_generated' => [],
                    'custom_options' => [
                        'path_based',
                        'multi_tenant_enabled'
                    ],
                    'validation_rules' => [
                        'slug',
                        'custom_domain'
                    ]
                ]);
    }

    public function test_unauthenticated_user_cannot_publish()
    {
        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish");

        // Assert
        $response->assertUnauthorized();
    }

    public function test_user_cannot_publish_others_landing_page()
    {
        // Arrange
        $anotherUser = User::factory()->create();
        $anotherPage = LandingPage::factory()->create([
            'created_by' => $anotherUser->id,
            'status' => 'draft',
        ]);

        $this->actingAs($this->user);

        // Act
        $response = $this->postJson("/api/landing-pages/{$anotherPage->id}/publish");

        // Assert
        $response->assertForbidden();
    }

    public function test_publish_handles_database_transaction_errors()
    {
        // Arrange
        $this->actingAs($this->user);

        // Mock a scenario that would cause transaction failure
        // This is hard to test directly, but we can test error handling
        $this->landingPage->update(['config' => null]);

        // Act
        $response = $this->postJson("/api/landing-pages/{$this->landingPage->id}/publish");

        // Assert
        $response->assertStatus(422);
    }
}