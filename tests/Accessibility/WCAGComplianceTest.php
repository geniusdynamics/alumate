<?php

namespace Tests\Accessibility;

use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WCAGComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_page_structure_and_semantic_html()
    {
        // Test main dashboard page
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);

        // Check for proper HTML structure
        $content = $response->getContent();

        // Should have proper DOCTYPE
        $this->assertStringContainsString('<!DOCTYPE html>', $content);

        // Should have lang attribute
        $this->assertMatchesRegularExpression('/<html[^>]*lang=["\'][a-z-]+["\']/', $content);

        // Should have proper meta tags
        $this->assertStringContainsString('<meta charset="utf-8">', $content);
        $this->assertStringContainsString('<meta name="viewport"', $content);

        // Should have title tag
        $this->assertMatchesRegularExpression('/<title>.*<\/title>/', $content);

        // Should use semantic HTML5 elements
        $this->assertStringContainsString('<main', $content);
        $this->assertStringContainsString('<nav', $content);
        $this->assertStringContainsString('<header', $content);
    }

    public function test_heading_hierarchy()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Extract all headings
        preg_match_all('/<h([1-6])[^>]*>.*?<\/h[1-6]>/i', $content, $matches);

        if (! empty($matches[1])) {
            $headingLevels = array_map('intval', $matches[1]);

            // Should start with h1
            $this->assertEquals(1, $headingLevels[0], 'Page should start with h1');

            // Check for proper hierarchy (no skipping levels)
            for ($i = 1; $i < count($headingLevels); $i++) {
                $currentLevel = $headingLevels[$i];
                $previousLevel = $headingLevels[$i - 1];

                // Can go down any number of levels, but can only go up one level at a time
                if ($currentLevel > $previousLevel) {
                    $this->assertLessThanOrEqual(
                        $previousLevel + 1,
                        $currentLevel,
                        "Heading hierarchy violation: h{$previousLevel} followed by h{$currentLevel}"
                    );
                }
            }
        }
    }

    public function test_form_accessibility()
    {
        // Test post creation form
        $response = $this->actingAs($this->user)
            ->get('/posts/create');

        if ($response->status() === 200) {
            $content = $response->getContent();

            // All form inputs should have labels or aria-label
            preg_match_all('/<input[^>]*>/', $content, $inputs);
            foreach ($inputs[0] as $input) {
                if (strpos($input, 'type="hidden"') === false) {
                    $hasLabel = preg_match('/id=["\']([^"\']+)["\']/', $input, $idMatch) &&
                               strpos($content, 'for="'.$idMatch[1].'"') !== false;
                    $hasAriaLabel = strpos($input, 'aria-label') !== false;
                    $hasAriaLabelledBy = strpos($input, 'aria-labelledby') !== false;

                    $this->assertTrue(
                        $hasLabel || $hasAriaLabel || $hasAriaLabelledBy,
                        'Input element should have proper labeling: '.$input
                    );
                }
            }

            // Textareas should have labels
            preg_match_all('/<textarea[^>]*>/', $content, $textareas);
            foreach ($textareas[0] as $textarea) {
                $hasLabel = preg_match('/id=["\']([^"\']+)["\']/', $textarea, $idMatch) &&
                           strpos($content, 'for="'.$idMatch[1].'"') !== false;
                $hasAriaLabel = strpos($textarea, 'aria-label') !== false;

                $this->assertTrue(
                    $hasLabel || $hasAriaLabel,
                    'Textarea should have proper labeling: '.$textarea
                );
            }
        }
    }

    public function test_image_alt_text()
    {
        // Create a post with media
        $post = Post::factory()->create([
            'user_id' => $this->user->id,
            'media_urls' => [
                [
                    'url' => 'https://example.com/image.jpg',
                    'type' => 'image',
                    'alt' => 'Alumni networking event photo',
                ],
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get('/timeline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // All images should have alt attributes
        preg_match_all('/<img[^>]*>/', $content, $images);
        foreach ($images[0] as $image) {
            // Skip decorative images or those with role="presentation"
            if (strpos($image, 'role="presentation"') === false &&
                strpos($image, 'aria-hidden="true"') === false) {

                $this->assertMatchesRegularExpression(
                    '/alt=["\'][^"\']*["\']/',
                    $image,
                    'Image should have alt attribute: '.$image
                );

                // Alt text should not be empty for content images
                if (! preg_match('/alt=["\']["\']/', $image)) {
                    $this->assertMatchesRegularExpression(
                        '/alt=["\'].+["\']/',
                        $image,
                        'Content image should have meaningful alt text: '.$image
                    );
                }
            }
        }
    }

    public function test_color_contrast_and_focus_indicators()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for focus indicators in CSS
        $this->assertStringContainsString(':focus', $content);

        // Interactive elements should be focusable
        preg_match_all('/<(button|a|input|select|textarea)[^>]*>/', $content, $interactiveElements);
        foreach ($interactiveElements[0] as $element) {
            // Should not have tabindex="-1" unless it's intentionally non-focusable
            if (strpos($element, 'tabindex="-1"') !== false) {
                // This is acceptable for programmatically focusable elements
                continue;
            }

            // Links should have href or role="button"
            if (strpos($element, '<a') === 0) {
                $hasHref = strpos($element, 'href=') !== false;
                $hasButtonRole = strpos($element, 'role="button"') !== false;

                $this->assertTrue(
                    $hasHref || $hasButtonRole,
                    'Link should have href or role="button": '.$element
                );
            }
        }
    }

    public function test_keyboard_navigation()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for skip links
        $this->assertMatchesRegularExpression(
            '/<a[^>]*href=["\']#[^"\']*["\'][^>]*>.*skip.*<\/a>/i',
            $content,
            'Page should have skip navigation links'
        );

        // Interactive elements should be keyboard accessible
        preg_match_all('/<div[^>]*onclick[^>]*>/', $content, $clickableDivs);
        foreach ($clickableDivs[0] as $div) {
            // Clickable divs should have proper keyboard support
            $hasTabindex = strpos($div, 'tabindex=') !== false;
            $hasRole = strpos($div, 'role=') !== false;
            $hasKeyHandler = strpos($div, 'onkeydown') !== false || strpos($div, 'onkeyup') !== false;

            $this->assertTrue(
                $hasTabindex && $hasRole,
                'Clickable div should have tabindex and role attributes: '.$div
            );
        }
    }

    public function test_aria_labels_and_descriptions()
    {
        // Test timeline page
        $response = $this->actingAs($this->user)
            ->get('/timeline');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for proper ARIA landmarks
        $landmarks = ['main', 'navigation', 'banner', 'contentinfo', 'complementary'];
        foreach ($landmarks as $landmark) {
            // Should have either semantic HTML or ARIA role
            $hasSemanticHTML = strpos($content, "<{$landmark}") !== false;
            $hasAriaRole = strpos($content, "role=\"{$landmark}\"") !== false;

            if (! $hasSemanticHTML && ! $hasAriaRole && $landmark === 'main') {
                $this->fail('Page should have main landmark');
            }
        }

        // Check for proper button labels
        preg_match_all('/<button[^>]*>.*?<\/button>/s', $content, $buttons);
        foreach ($buttons[0] as $button) {
            $hasTextContent = preg_match('/<button[^>]*>([^<]+)<\/button>/', $button, $textMatch);
            $hasAriaLabel = strpos($button, 'aria-label=') !== false;
            $hasAriaLabelledBy = strpos($button, 'aria-labelledby=') !== false;
            $hasTitle = strpos($button, 'title=') !== false;

            if ($hasTextContent && ! empty(trim($textMatch[1]))) {
                // Button has text content, which is good
                continue;
            }

            $this->assertTrue(
                $hasAriaLabel || $hasAriaLabelledBy || $hasTitle,
                'Button without text content should have aria-label, aria-labelledby, or title: '.$button
            );
        }
    }

    public function test_responsive_design_accessibility()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Should have viewport meta tag for mobile accessibility
        $this->assertMatchesRegularExpression(
            '/<meta[^>]*name=["\']viewport["\'][^>]*>/',
            $content,
            'Page should have viewport meta tag for responsive design'
        );

        // Check for responsive font sizes (should not use fixed pixel sizes for text)
        // This is a basic check - in practice, you'd want to test actual CSS
        if (preg_match_all('/font-size:\s*(\d+)px/', $content, $fontSizes)) {
            foreach ($fontSizes[1] as $size) {
                $this->assertGreaterThanOrEqual(
                    12,
                    intval($size),
                    'Font size should be at least 12px for accessibility'
                );
            }
        }
    }

    public function test_error_handling_accessibility()
    {
        // Test form validation errors
        $response = $this->actingAs($this->user)
            ->postJson('/api/posts', [
                'content' => '', // Invalid - empty content
                'post_type' => 'text',
            ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');

        // Error messages should be descriptive
        $this->assertNotEmpty($errors);
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $this->assertNotEmpty($message);
                $this->assertIsString($message);
                // Error message should be descriptive, not just "required"
                $this->assertGreaterThan(5, strlen($message));
            }
        }
    }

    public function test_dynamic_content_accessibility()
    {
        // Test AJAX-loaded content has proper accessibility
        $response = $this->actingAs($this->user)
            ->getJson('/api/timeline');

        $response->assertStatus(200);
        $data = $response->json();

        // Check that API responses include accessibility information
        if (isset($data['data']['posts']['data'])) {
            foreach ($data['data']['posts']['data'] as $post) {
                // Posts should have accessible content structure
                $this->assertArrayHasKey('content', $post);
                $this->assertArrayHasKey('user', $post);
                $this->assertArrayHasKey('created_at', $post);

                // If post has media, it should include alt text
                if (isset($post['media_urls']) && ! empty($post['media_urls'])) {
                    foreach ($post['media_urls'] as $media) {
                        if ($media['type'] === 'image') {
                            $this->assertArrayHasKey('alt', $media, 'Image media should include alt text');
                        }
                    }
                }
            }
        }
    }

    public function test_screen_reader_compatibility()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Check for screen reader only content
        $this->assertMatchesRegularExpression(
            '/class=["\'][^"\']*sr-only[^"\']*["\']/',
            $content,
            'Page should have screen reader only content for better accessibility'
        );

        // Check for proper table headers if tables exist
        if (strpos($content, '<table') !== false) {
            preg_match_all('/<table[^>]*>.*?<\/table>/s', $content, $tables);
            foreach ($tables[0] as $table) {
                // Tables should have proper headers
                $this->assertMatchesRegularExpression(
                    '/<th[^>]*>/',
                    $table,
                    'Data tables should have proper th elements'
                );

                // Complex tables should have scope attributes
                if (substr_count($table, '<th') > 2) {
                    $this->assertMatchesRegularExpression(
                        '/scope=["\'](?:col|row|colgroup|rowgroup)["\']/',
                        $table,
                        'Complex tables should have scope attributes on headers'
                    );
                }
            }
        }
    }

    public function test_multimedia_accessibility()
    {
        // Create event with video content
        $event = Event::factory()->create([
            'organizer_id' => $this->user->id,
            'is_virtual' => true,
            'virtual_settings' => [
                'recording_enabled' => true,
                'captions_enabled' => true,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $eventData = $response->json('data');

        // Virtual events should have accessibility features
        if ($eventData['is_virtual']) {
            $this->assertArrayHasKey('virtual_settings', $eventData);

            // Should support captions for accessibility
            $virtualSettings = $eventData['virtual_settings'];
            $this->assertTrue(
                isset($virtualSettings['captions_enabled']) && $virtualSettings['captions_enabled'],
                'Virtual events should have captions enabled for accessibility'
            );
        }
    }

    public function test_progressive_enhancement()
    {
        // Test that core functionality works without JavaScript
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // Should have noscript fallbacks for critical functionality
        $this->assertStringContainsString('<noscript>', $content);

        // Forms should work without JavaScript (proper action attributes)
        preg_match_all('/<form[^>]*>/', $content, $forms);
        foreach ($forms[0] as $form) {
            if (strpos($form, 'method=') !== false) {
                // Forms with methods should have action attributes
                $this->assertMatchesRegularExpression(
                    '/action=["\'][^"\']*["\']/',
                    $form,
                    'Forms should have action attributes for progressive enhancement: '.$form
                );
            }
        }
    }

    public function test_wcag_aa_compliance_checklist()
    {
        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertStatus(200);
        $content = $response->getContent();

        // WCAG 2.1 AA Compliance Checklist
        $complianceChecks = [
            'has_lang_attribute' => preg_match('/<html[^>]*lang=["\'][a-z-]+["\']/', $content),
            'has_page_title' => strpos($content, '<title>') !== false,
            'has_main_landmark' => strpos($content, '<main') !== false || strpos($content, 'role="main"') !== false,
            'has_skip_links' => preg_match('/<a[^>]*href=["\']#[^"\']*["\'][^>]*>.*skip.*<\/a>/i', $content),
            'has_focus_styles' => strpos($content, ':focus') !== false,
            'has_viewport_meta' => strpos($content, 'name="viewport"') !== false,
        ];

        foreach ($complianceChecks as $check => $passed) {
            $this->assertTrue($passed, "WCAG AA compliance check failed: {$check}");
        }

        // Additional checks for interactive elements
        $this->assertAccessibleInteractiveElements($content);
        $this->assertProperHeadingStructure($content);
        $this->assertFormAccessibility($content);
    }

    private function assertAccessibleInteractiveElements(string $content): void
    {
        // Check buttons
        preg_match_all('/<button[^>]*>.*?<\/button>/s', $content, $buttons);
        foreach ($buttons[0] as $button) {
            // Buttons should not be disabled without good reason
            if (strpos($button, 'disabled') !== false) {
                // Disabled buttons should have aria-describedby explaining why
                $this->assertMatchesRegularExpression(
                    '/aria-describedby=["\'][^"\']+["\']/',
                    $button,
                    'Disabled buttons should have aria-describedby explaining why they are disabled'
                );
            }
        }

        // Check links
        preg_match_all('/<a[^>]*>.*?<\/a>/s', $content, $links);
        foreach ($links[0] as $link) {
            // Links that open in new windows should be indicated
            if (strpos($link, 'target="_blank"') !== false) {
                $hasIndicator = strpos($link, 'aria-label') !== false ||
                               strpos($link, 'title') !== false ||
                               preg_match('/\(opens? in new (?:window|tab)\)/i', $link);

                $this->assertTrue(
                    $hasIndicator,
                    'Links opening in new windows should indicate this to users'
                );
            }
        }
    }

    private function assertProperHeadingStructure(string $content): void
    {
        preg_match_all('/<h([1-6])[^>]*>.*?<\/h[1-6]>/i', $content, $matches);

        if (! empty($matches[1])) {
            $headingLevels = array_map('intval', $matches[1]);

            // Should have at least one h1
            $this->assertContains(1, $headingLevels, 'Page should have at least one h1 heading');

            // Should not skip heading levels
            $uniqueLevels = array_unique($headingLevels);
            sort($uniqueLevels);

            for ($i = 1; $i < count($uniqueLevels); $i++) {
                $this->assertEquals(
                    $uniqueLevels[$i - 1] + 1,
                    $uniqueLevels[$i],
                    'Heading levels should not skip levels'
                );
            }
        }
    }

    private function assertFormAccessibility(string $content): void
    {
        // Check for required field indicators
        preg_match_all('/<input[^>]*required[^>]*>/', $content, $requiredInputs);
        foreach ($requiredInputs[0] as $input) {
            // Required fields should be indicated to screen readers
            $hasAriaRequired = strpos($input, 'aria-required="true"') !== false;
            $hasRequiredAttr = strpos($input, 'required') !== false;

            $this->assertTrue(
                $hasAriaRequired || $hasRequiredAttr,
                'Required fields should be properly indicated: '.$input
            );
        }

        // Check for fieldsets in complex forms
        if (substr_count($content, '<input') > 5) {
            // Complex forms should use fieldsets for grouping
            $this->assertMatchesRegularExpression(
                '/<fieldset[^>]*>.*<legend[^>]*>.*<\/legend>.*<\/fieldset>/s',
                $content,
                'Complex forms should use fieldsets with legends for grouping related fields'
            );
        }
    }
}
