<?php

namespace Tests\Unit\Services;

use App\Models\LandingPage;
use App\Models\Tenant;
use App\Services\PublishingWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Test suite for PublishingWorkflowService
 *
 * @covers \App\Services\PublishingWorkflowService
 */
class PublishingWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private PublishingWorkflowService $service;
    private LandingPage $landingPage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PublishingWorkflowService::class);
        $this->landingPage = LandingPage::factory()->create([
            'status' => 'draft',
            'name' => 'Test Landing Page',
            'slug' => 'test-landing-page',
            'config' => ['title' => 'Test Page'],
        ]);
    }

    public function test_can_publish_landing_page()
    {
        // Act
        $result = $this->service->publishLandingPage($this->landingPage->id);

        // Assert
        $this->assertEquals('published', $result->status);
        $this->assertNotNull($result->published_at);
        $this->assertEquals($this->landingPage->version + 1, $result->version);
        $this->assertNotNull($result->public_url);
    }

    public function test_cannot_publish_without_name()
    {
        // Arrange
        $this->landingPage->update(['name' => '']);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('name');
        $this->service->publishLandingPage($this->landingPage->id);
    }

    public function test_cannot_publish_without_config()
    {
        // Arrange
        $this->landingPage->update(['config' => null]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->service->publishLandingPage($this->landingPage->id);
    }

    public function test_cannot_publish_already_published_page()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already published');
        $this->service->publishLandingPage($this->landingPage->id);
    }

    public function test_cannot_publish_without_slug()
    {
        // Arrange
        $this->landingPage->update(['slug' => null]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('slug');
        $this->service->publishLandingPage($this->landingPage->id);
    }

    public function test_publish_with_custom_domain()
    {
        // Act
        $result = $this->service->publishLandingPage($this->landingPage->id, [
            'custom_domain' => 'custom.example.com'
        ]);

        // Assert
        $this->assertEquals('https://custom.example.com/test-landing-page', $result->public_url);
    }

    public function test_publish_with_scheduled_time()
    {
        // Arrange
        $scheduledTime = now()->addHours(2);

        // Act
        $result = $this->service->publishLandingPage($this->landingPage->id, [
            'publish_at' => $scheduledTime
        ]);

        // Assert
        $this->assertEquals($scheduledTime->timestamp, $result->published_at->timestamp);
    }

    public function test_can_unpublish_landing_page()
    {
        // Arrange
        $this->landingPage->update([
            'status' => 'published',
            'public_url' => 'https://example.com/test',
            'published_at' => now()
        ]);

        // Act
        $result = $this->service->unpublishLandingPage($this->landingPage->id);

        // Assert
        $this->assertEquals('draft', $result->status);
        $this->assertNull($result->published_at);
        $this->assertNull($result->public_url);
    }

    public function test_can_archive_landing_page()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Act
        $result = $this->service->archiveLandingPage($this->landingPage->id);

        // Assert
        $this->assertEquals('archived', $result->status);
    }

    public function test_get_published_landing_page_caches_result()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Act - First call
        $result1 = $this->service->getPublishedLandingPage($this->landingPage->slug);

        // Act - Second call (should use cache)
        $result2 = $this->service->getPublishedLandingPage($this->landingPage->slug);

        // Assert
        $this->assertEquals($this->landingPage->id, $result1->id);
        $this->assertEquals($result1->id, $result2->id);
    }

    public function test_get_non_existent_published_landing_page_returns_null()
    {
        // Act
        $result = $this->service->getPublishedLandingPage('non-existent-slug');

        // Assert
        $this->assertNull($result);
    }

    public function test_get_cached_content_includes_required_fields()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Act
        $content = $this->service->getCachedLandingPageContent($this->landingPage);

        // Assert
        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('name', $content);
        $this->assertArrayHasKey('slug', $content);
        $this->assertArrayHasKey('config', $content);
        $this->assertArrayHasKey('seo_title', $content);
        $this->assertArrayHasKey('seo_description', $content);
        $this->assertArrayHasKey('seo_keywords', $content);
        $this->assertArrayHasKey('version', $content);
        $this->assertArrayHasKey('cache_timestamp', $content);
    }

    public function test_duplicate_slug_validation()
    {
        // Arrange - Create first published page
        $firstPage = LandingPage::factory()->create([
            'status' => 'published',
            'slug' => 'unique-slug',
            'name' => 'First Page',
        ]);

        // Arrange - Try to publish second page with same slug
        $secondPage = LandingPage::factory()->create([
            'status' => 'draft',
            'slug' => 'unique-slug',
            'name' => 'Second Page',
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already in use');
        $this->service->publishLandingPage($secondPage->id);
    }

    public function test_publish_invalidates_cache()
    {
        // Arrange
        $oldCacheKey = "landing_page_content:{$this->landingPage->id}:{$this->landingPage->version}";

        // Put something in cache
        Cache::put($oldCacheKey, ['old' => 'content'], 60);

        // Act
        $result = $this->service->publishLandingPage($this->landingPage->id);

        // Assert - Old cache should be cleared
        $this->assertFalse(Cache::has($oldCacheKey));

        // New cache should exist
        $newCacheKey = "landing_page_content:{$this->landingPage->id}:{$result->version}";
        $this->assertTrue(Cache::has($newCacheKey));
    }

    public function test_publish_enforces_unique_slug_validation()
    {
        // Arrange
        $existingPage = LandingPage::factory()->create([
            'status' => 'published',
            'slug' => 'existing-slug',
        ]);

        $newPage = LandingPage::factory()->create([
            'status' => 'draft',
            'slug' => 'existing-slug', // Same slug as published page
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('already in use');
        $this->service->publishLandingPage($newPage->id);
    }

    public function test_performance_metrics_calculation()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Add some analytics data
        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-1',
            'created_at' => now(),
        ]);

        // Act
        $metrics = $this->service->getLandingPagePerformance($this->landingPage);

        // Assert
        $this->assertArrayHasKey('page_views', $metrics);
        $this->assertArrayHasKey('unique_visitors', $metrics);
        $this->assertArrayHasKey('conversion_count', $metrics);
        $this->assertArrayHasKey('average_session_duration', $metrics);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('last_updated', $metrics);

        $this->assertEquals(1, $metrics['page_views']);
        $this->assertEquals(1, $metrics['unique_visitors']);
    }

    public function test_bulk_publish_operations()
    {
        // Arrange
        $pages = LandingPage::factory()->count(3)->create([
            'status' => 'draft',
        ]);

        $ids = $pages->pluck('id')->toArray();

        // Act
        $results = $this->service->bulkPublish($ids);

        // Assert
        $this->assertEquals(3, $results['successful']);
        $this->assertEmpty($results['failed']);
        $this->assertEquals(0, $results['errors']);

        // Verify all pages were published
        $updatedPages = LandingPage::whereIn('id', $ids)->get();
        $this->assertEquals(3, $updatedPages->where('status', 'published')->count());
    }

    public function test_bulk_unpublish_operations()
    {
        // Arrange
        $pages = LandingPage::factory()->count(2)->create([
            'status' => 'published',
        ]);

        $ids = $pages->pluck('id')->toArray();

        // Act
        $results = $this->service->bulkUnpublish($ids);

        // Assert
        $this->assertEquals(2, $results['successful']);
        $this->assertEmpty($results['failed']);

        // Verify all pages were unpublished
        $updatedPages = LandingPage::whereIn('id', $ids)->get();
        $this->assertEquals(2, $updatedPages->where('status', 'draft')->count());
    }

    public function test_publish_with_template_validation()
    {
        // Arrange
        $this->landingPage->update([
            'template_id' => 999, // Non-existent template
        ]);

        // Act & Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Template must exist and be published');
        $this->service->publishLandingPage($this->landingPage->id);
    }

    public function test_average_session_duration_calculation()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        $baseTime = now();

        // Add multiple page views with time differences
        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-1',
            'created_at' => $baseTime,
        ]);

        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-1',
            'created_at' => $baseTime->copy()->addSeconds(120), // 2 minutes later
        ]);

        // Act
        $duration = $this->service->calculateAverageSessionDuration($this->landingPage);

        // Assert - Should be the difference between first and last visit (120 seconds)
        $this->assertEquals(120, $duration);
    }

    public function test_bounce_rate_calculation()
    {
        // Arrange
        $this->landingPage->update(['status' => 'published']);

        // Add one single page view (bounce)
        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-1',
            'created_at' => now(),
        ]);

        // Add a session with multiple page views
        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-2',
            'created_at' => now(),
        ]);

        $this->landingPage->analytics()->create([
            'event_type' => 'page_view',
            'session_id' => 'session-2',
            'created_at' => now()->addSeconds(30),
        ]);

        // Act
        $bounceRate = $this->service->calculateBounceRate($this->landingPage);

        // Assert - Should be 50% (1 bounce out of 2 sessions)
        $this->assertEquals(50.00, $bounceRate);
    }
}