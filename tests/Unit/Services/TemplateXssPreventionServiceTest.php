<?php

namespace Tests\Unit\Services;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityLog;
use App\Services\TemplateXssPreventionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateXssPreventionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateXssPreventionService $xssService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xssService = new TemplateXssPreventionService();
    }

    /** @test */
    public function it_sanitizes_basic_html_content()
    {
        $content = '<p>Hello <strong>world</strong></p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertEquals($content, $sanitized);
    }

    /** @test */
    public function it_removes_script_tags()
    {
        $content = '<p>Hello</p><script>alert("xss")</script><p>World</p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert("xss")', $sanitized);
        $this->assertStringContainsString('<p>Hello</p>', $sanitized);
        $this->assertStringContainsString('<p>World</p>', $sanitized);
    }

    /** @test */
    public function it_removes_event_handlers()
    {
        $content = '<a href="#" onclick="alert(\'xss\')">Click me</a>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('onclick', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
    }

    /** @test */
    public function it_sanitizes_javascript_urls()
    {
        $content = '<a href="javascript:alert(\'xss\')">Click me</a>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringContainsString('href="#"', $sanitized);
    }

    /** @test */
    public function it_removes_iframe_tags()
    {
        $content = '<p>Content</p><iframe src="malicious.com"></iframe><p>More content</p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<iframe>', $sanitized);
        $this->assertStringNotContainsString('malicious.com', $sanitized);
    }

    /** @test */
    public function it_throws_exception_for_script_injection()
    {
        $this->expectException(TemplateSecurityException::class);

        $content = '<script>document.cookie="session=123"</script>';
        $this->xssService->sanitizeForRendering($content);
    }

    /** @test */
    public function it_throws_exception_for_data_exfiltration()
    {
        $this->expectException(TemplateSecurityException::class);

        $content = '<img src="x" onerror="fetch(\'/api/user/data\')" />';
        $this->xssService->sanitizeForRendering($content);
    }

    /** @test */
    public function it_sanitizes_template_structure_recursively()
    {
        $structure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '<script>alert("xss")</script>Hello World',
                        'description' => '<a href="javascript:evil()">Click me</a>',
                        'nested' => [
                            'content' => '<iframe src="bad.com"></iframe>'
                        ]
                    ]
                ]
            ]
        ];

        $sanitized = $this->xssService->sanitizeTemplateStructure($structure);

        $this->assertStringNotContainsString('<script>', $sanitized['sections'][0]['config']['title']);
        $this->assertStringNotContainsString('javascript:', $sanitized['sections'][0]['config']['description']);
        $this->assertStringNotContainsString('<iframe>', $sanitized['sections'][0]['config']['nested']['content']);
    }

    /** @test */
    public function it_logs_security_events_for_threats()
    {
        $this->expectException(TemplateSecurityException::class);

        $content = '<script>alert("xss")</script>';

        try {
            $this->xssService->sanitizeForRendering($content, [
                'tenant_id' => 1,
                'user_id' => 1
            ]);
        } catch (TemplateSecurityException $e) {
            // Verify security event was logged
            $this->assertDatabaseHas('security_logs', [
                'event_type' => SecurityLog::EVENT_TYPE_XSS_ATTEMPT,
                'tenant_id' => 1,
                'user_id' => 1,
                'severity' => SecurityLog::SEVERITY_HIGH
            ]);
        }
    }

    /** @test */
    public function it_handles_css_expressions()
    {
        $content = '<div style="width: expression(alert(\'xss\'))">Test</div>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('expression', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
    }

    /** @test */
    public function it_sanitizes_vbscript_urls()
    {
        $content = '<a href="vbscript:msgbox(\'xss\')">Click me</a>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('vbscript:', $sanitized);
        $this->assertStringContainsString('href="#"', $sanitized);
    }

    /** @test */
    public function it_handles_data_url_schemes()
    {
        $content = '<img src="data:text/html,<script>alert(\'xss\')</script>" />';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('data:', $sanitized);
    }

    /** @test */
    public function it_preserves_safe_html_tags()
    {
        $content = '<p><strong>Bold text</strong> and <em>italic text</em></p><ul><li>Item 1</li><li>Item 2</li></ul>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertEquals($content, $sanitized);
    }

    /** @test */
    public function it_sanitizes_mixed_safe_and_dangerous_content()
    {
        $content = '<p>Safe content</p><script>alert("danger")</script><p>More safe content</p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringContainsString('<p>Safe content</p>', $sanitized);
        $this->assertStringContainsString('<p>More safe content</p>', $sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert("danger")', $sanitized);
    }

    /** @test */
    public function it_handles_complex_event_handlers()
    {
        $content = '<div onmouseover="javascript:alert(\'xss\')" onload="evil()" onchange="bad()">Test</div>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('onmouseover', $sanitized);
        $this->assertStringNotContainsString('onload', $sanitized);
        $this->assertStringNotContainsString('onchange', $sanitized);
        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringNotContainsString('alert', $sanitized);
        $this->assertStringNotContainsString('evil', $sanitized);
        $this->assertStringNotContainsString('bad', $sanitized);
    }

    /** @test */
    public function it_provides_sanitization_statistics()
    {
        $stats = $this->xssService->getSanitizationStats();

        $this->assertArrayHasKey('allowed_tags', $stats);
        $this->assertArrayHasKey('allowed_attributes', $stats);
        $this->assertArrayHasKey('dangerous_tags', $stats);
        $this->assertArrayHasKey('dangerous_attributes', $stats);

        $this->assertContains('script', $stats['dangerous_tags']);
        $this->assertContains('p', $stats['allowed_tags']);
        $this->assertContains('href', $stats['allowed_attributes']);
        $this->assertContains('onclick', $stats['dangerous_attributes']);
    }

    /** @test */
    public function it_handles_self_closing_dangerous_tags()
    {
        $content = '<p>Content</p><img src="safe.jpg" /><iframe src="bad.com" /><p>More content</p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<iframe', $sanitized);
        $this->assertStringNotContainsString('bad.com', $sanitized);
        $this->assertStringContainsString('<img src="safe.jpg"', $sanitized);
    }

    /** @test */
    public function it_sanitizes_object_and_embed_tags()
    {
        $content = '<object data="malicious.swf"></object><embed src="bad.exe">';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<object>', $sanitized);
        $this->assertStringNotContainsString('<embed>', $sanitized);
        $this->assertStringNotContainsString('malicious.swf', $sanitized);
        $this->assertStringNotContainsString('bad.exe', $sanitized);
    }

    /** @test */
    public function it_handles_case_insensitive_dangerous_tags()
    {
        $content = '<SCRIPT>alert("xss")</SCRIPT><Iframe src="bad.com"></Iframe>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('<iframe>', $sanitized);
        $this->assertStringNotContainsString('alert("xss")', $sanitized);
        $this->assertStringNotContainsString('bad.com', $sanitized);
    }

    /** @test */
    public function it_preserves_safe_attributes()
    {
        $content = '<a href="https://safe.com" target="_blank" rel="noopener" class="safe-link">Safe Link</a>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertEquals($content, $sanitized);
    }

    /** @test */
    public function it_handles_malformed_html()
    {
        $content = '<p>Unclosed paragraph<script>alert("xss")<p>Another paragraph</p>';
        $sanitized = $this->xssService->sanitizeForRendering($content);

        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('alert("xss")', $sanitized);
        $this->assertStringContainsString('<p>Unclosed paragraph', $sanitized);
        $this->assertStringContainsString('<p>Another paragraph</p>', $sanitized);
    }
}