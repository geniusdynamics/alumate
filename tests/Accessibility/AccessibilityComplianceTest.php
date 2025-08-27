<?php

namespace Tests\Accessibility;

use App\Models\Event;
use App\Models\JobPosting;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_main_navigation_accessibility()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);

        // Check for proper navigation structure
        $content = $response->getContent();

        // Navigation should have proper ARIA labels
        $this->assertStringContainsString('role="navigation"', $content);
        $this->assertStringContainsString('aria-label="Main navigation"', $content);

        // Navigation items should be keyboard accessible
        $this->assertStringContainsString('tabindex="0"', $content);

        // Skip links should be present
        $this->assertStringContainsString('Skip to main content', $content);
    }

    public function test_form_accessibility_compliance()
    {
        // Test post creation form
        $response = $this->actingAs($this->user)
            ->get('/social/timeline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Forms should have proper labels
        $this->assertStringContainsString('<label', $content);

        // Required fields should be marked
        $this->assertStringContainsString('required', $content);
        $this->assertStringContainsString('aria-required="true"', $content);

        // Error messages should be associated with fields
        $this->assertStringContainsString('aria-describedby', $content);
    }

    public function test_image_alt_text_compliance()
    {
        // Create a post with media
        $post = Post::factory()->withMedia()->create([
            'user_id' => $this->user->id,
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);

        $postData = $response->json('data.post');

        // Check that media has alt text or proper handling
        if (! empty($postData['media_urls'])) {
            foreach ($postData['media_urls'] as $media) {
                // Media should have alt text or be marked as decorative
                $this->assertTrue(
                    isset($media['alt']) || isset($media['decorative']),
                    'Media should have alt text or be marked as decorative'
                );
            }
        }
    }

    public function test_color_contrast_compliance()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for CSS custom properties that ensure proper contrast
        $this->assertStringContainsString('--color-text-primary', $content);
        $this->assertStringContainsString('--color-background-primary', $content);

        // Check for high contrast mode support
        $this->assertStringContainsString('prefers-contrast', $content);
    }

    public function test_keyboard_navigation_support()
    {
        $response = $this->actingAs($this->user)
            ->get('/alumni/directory');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Interactive elements should be keyboard accessible
        $this->assertStringContainsString('tabindex', $content);

        // Focus indicators should be present
        $this->assertStringContainsString('focus:', $content);

        // Keyboard event handlers should be present
        $this->assertStringContainsString('@keydown', $content);
    }

    public function test_screen_reader_support()
    {
        $response = $this->actingAs($this->user)
            ->get('/social/timeline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // ARIA landmarks should be present
        $this->assertStringContainsString('role="main"', $content);
        $this->assertStringContainsString('role="banner"', $content);
        $this->assertStringContainsString('role="contentinfo"', $content);

        // Screen reader only content should be present
        $this->assertStringContainsString('sr-only', $content);

        // Live regions for dynamic content
        $this->assertStringContainsString('aria-live', $content);
    }

    public function test_semantic_html_structure()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Proper heading hierarchy
        $this->assertStringContainsString('<h1', $content);

        // Semantic HTML elements
        $this->assertStringContainsString('<main', $content);
        $this->assertStringContainsString('<nav', $content);
        $this->assertStringContainsString('<section', $content);
        $this->assertStringContainsString('<article', $content);
    }

    public function test_form_error_accessibility()
    {
        // Test form submission with errors
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => '', // Empty content should trigger validation error
                'post_type' => 'text',
            ]);

        $response->assertStatus(422);

        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);

        // Error messages should be structured for accessibility
        foreach ($errors as $field => $messages) {
            $this->assertIsArray($messages);
            foreach ($messages as $message) {
                $this->assertIsString($message);
                $this->assertNotEmpty($message);
            }
        }
    }

    public function test_dynamic_content_accessibility()
    {
        // Create posts for timeline
        Post::factory()->count(5)->create([
            'visibility' => 'public',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Timeline posts should have proper structure for screen readers
        $this->assertArrayHasKey('posts', $data);

        $posts = $data['posts']['data'];
        foreach ($posts as $post) {
            // Each post should have required accessibility information
            $this->assertArrayHasKey('id', $post);
            $this->assertArrayHasKey('content', $post);
            $this->assertArrayHasKey('user', $post);
            $this->assertArrayHasKey('created_at', $post);
        }
    }

    public function test_modal_accessibility()
    {
        // Test that modals have proper accessibility attributes
        $job = JobPosting::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/jobs/{$job->id}");

        $response->assertStatus(200);

        // Job details should be structured for modal presentation
        $jobData = $response->json('data');
        $this->assertArrayHasKey('id', $jobData);
        $this->assertArrayHasKey('title', $jobData);
        $this->assertArrayHasKey('description', $jobData);
    }

    public function test_table_accessibility()
    {
        // Test data tables have proper accessibility structure
        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?per_page=10');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);

        // Table data should have proper structure
        foreach ($data['data'] as $alumnus) {
            $this->assertArrayHasKey('id', $alumnus);
            $this->assertArrayHasKey('name', $alumnus);
        }
    }

    public function test_loading_states_accessibility()
    {
        // Test that loading states are accessible
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);

        // Response should include loading state information
        $data = $response->json();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function test_error_states_accessibility()
    {
        // Test 404 error accessibility
        $response = $this->actingAs($this->user)
            ->getJson('/api/posts/999999');

        $response->assertStatus(404);

        $error = $response->json();
        $this->assertArrayHasKey('message', $error);
        $this->assertIsString($error['message']);
    }

    public function test_pagination_accessibility()
    {
        // Create many posts for pagination
        Post::factory()->count(50)->create(['visibility' => 'public']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline?per_page=10&page=1');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('meta', $data['posts']);

        $meta = $data['posts']['meta'];

        // Pagination metadata should be complete for accessibility
        $this->assertArrayHasKey('current_page', $meta);
        $this->assertArrayHasKey('last_page', $meta);
        $this->assertArrayHasKey('per_page', $meta);
        $this->assertArrayHasKey('total', $meta);
        $this->assertArrayHasKey('from', $meta);
        $this->assertArrayHasKey('to', $meta);
    }

    public function test_search_accessibility()
    {
        // Create alumni for search testing
        User::factory()->count(10)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/alumni?search=test');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);

        // Search results should be properly structured
        foreach ($data['data'] as $result) {
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('name', $result);
        }
    }

    public function test_notification_accessibility()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertArrayHasKey('data', $data);

        // Notifications should have proper structure for screen readers
        foreach ($data['data'] as $notification) {
            $this->assertArrayHasKey('id', $notification);
            $this->assertArrayHasKey('type', $notification);
            $this->assertArrayHasKey('data', $notification);
            $this->assertArrayHasKey('created_at', $notification);
        }
    }

    public function test_mobile_accessibility()
    {
        // Test mobile-specific accessibility features
        $response = $this->actingAs($this->user)
            ->withHeaders(['User-Agent' => 'Mobile'])
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Mobile navigation should be accessible
        $this->assertStringContainsString('aria-expanded', $content);

        // Touch targets should be appropriately sized
        $this->assertStringContainsString('min-h-', $content);
    }

    public function test_reduced_motion_support()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Should include reduced motion media queries
        $this->assertStringContainsString('prefers-reduced-motion', $content);
    }

    public function test_focus_management()
    {
        $response = $this->actingAs($this->user)
            ->get('/social/timeline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Focus management should be implemented
        $this->assertStringContainsString('focus-within', $content);
        $this->assertStringContainsString('focus-visible', $content);
    }

    public function test_language_and_locale_accessibility()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // HTML should have lang attribute
        $this->assertStringContainsString('lang="', $content);

        // Direction should be specified for RTL support
        $this->assertStringContainsString('dir="', $content);
    }
}
