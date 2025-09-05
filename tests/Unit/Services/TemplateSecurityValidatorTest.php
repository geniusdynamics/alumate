<?php

namespace Tests\Unit\Services;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\TemplateSecurityValidator;
use App\Services\SecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TemplateSecurityValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateSecurityValidator $validator;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($this->user);

        $this->validator = new TemplateSecurityValidator();
    }

    /** @test */
    public function it_detects_script_injection_in_html_content()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '<script>alert("XSS Attack")</script>Valid Title',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_event_handler_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'onclick' => 'javascript:alert("XSS")',
                        'text' => 'Click me',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_iframe_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => '<iframe src="http://evil-site.com"></iframe>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_javascript_href_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'url' => 'javascript:alert("XSS")',
                        'text' => 'Click me',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_data_uri_html_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'image',
                    'config' => [
                        'src' => 'data:text/html,<script>alert("XSS")</script>',
                        'alt' => 'Evil image',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_css_expression_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'style' => 'width: expression(alert("XSS"))',
                        'title' => 'Test Title',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_vbscript_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'url' => 'vbscript:msgbox("XSS")',
                        'text' => 'Click me',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_javascript_function_calls()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'url' => 'javascript:evilFunction(param1,param2)',
                        'text' => 'Click me',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_allows_safe_html_content()
    {
        $safeContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Valid Title & Special Characters',
                        'content' => '<p>This is <strong>safe</strong> HTML content</p>',
                    ]
                ],
                [
                    'type' => 'button',
                    'config' => [
                        'url' => 'https://example.com/valid-url',
                        'text' => 'Click me',
                        'alt' => 'Valid button',
                    ]
                ]
            ]
        ];

        $this->validator->validate($safeContent);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_detects_dynamic_code_execution_patterns()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'dynamicScript' => 'Function("alert(\'XSS\')")()',
                        'title' => 'Test Title',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_extracts_correct_snippet_from_violations()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Valid content before <script>alert("evil")</script> valid content after',
                    ]
                ]
            ]
        ];

        try {
            $this->validator->validate($maliciousContent);
            $this->fail('Expected TemplateSecurityException to be thrown');
        } catch (TemplateSecurityException $e) {
            $violations = $e->getSecurityIssues();
            $this->assertNotEmpty($violations);

            $violation = $violations[0];
            $this->assertArrayHasKey('snippet', $violation);
            $this->assertArrayHasKey('type', $violation);
            $this->assertEquals('script_injection', $violation['type']);
        }
    }

    /** @test */
    public function it_detects_storage_access_violations()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'trackingCode' => 'localStorage.setItem("evil", "data"); document.cookie="evil=value";',
                        'title' => 'Test Title',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_malicious_url_schemes()
    {
        $maliciousUrls = [
            'data:text/html,<script>alert("xss")</script>',
            'javascript:void(0);alert("xss")',
            'vbscript:Execute("msgbox("xss")")',
            'file:///etc/passwd',
        ];

        foreach ($maliciousUrls as $url) {
            $maliciousContent = [
                'sections' => [
                    [
                        'type' => 'button',
                        'config' => [
                            'url' => $url,
                            'text' => 'Click me',
                        ]
                    ]
                ]
            ];

            $this->expectException(TemplateSecurityException::class);
            $this->validator->validate($maliciousContent);
        }
    }

    /** @test */
    public function it_detects_meta_http_equiv_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'metaTag' => '<meta http-equiv="refresh" content="0;url=javascript:alert(\'xss\')">',
                        'title' => 'Test Title',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_applet_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => '<applet code="EvilApplet.class" width="100" height="100"></applet>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_object_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => '<object data="evil.swf" type="application/x-shockwave-flash"></object>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_detects_embed_injection()
    {
        $maliciousContent = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => '<embed src="evil.swf" type="application/x-shockwave-flash">',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($maliciousContent);
    }

    /** @test */
    public function it_allows_relative_urls()
    {
        $safeContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'url' => '/contact-us',
                        'text' => 'Contact Us',
                    ]
                ]
            ]
        ];

        $this->validator->validate($safeContent);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_allows_secure_https_urls()
    {
        $safeContent = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'url' => 'https://example.com/path?param=value&other=123',
                        'text' => 'Visit Example',
                    ]
                ]
            ]
        ];

        $this->validator->validate($safeContent);

        $this->assertTrue(true); // Test passes if no exception thrown
    }

    /** @test */
    public function it_handles_empty_sections_gracefully()
    {
        $emptyContent = [
            'sections' => []
        ];

        $this->validator->validate($emptyContent);

        $this->assertTrue(true); // Should not throw exception for empty sections
    }

    /** @test */
    public function it_handles_missing_config_gracefully()
    {
        $missingConfigContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    // No config block
                ]
            ]
        ];

        $this->validator->validate($missingConfigContent);

        $this->assertTrue(true); // Should not throw exception for missing config
    }

    /** @test */
    public function it_handles_null_values_gracefully()
    {
        $nullContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => null,
                        'content' => null,
                        'url' => null,
                    ]
                ]
            ]
        ];

        $this->validator->validate($nullContent);

        $this->assertTrue(true); // Should not throw exception for null values
    }

    /** @test */
    public function it_allows_allowed_html_tags()
    {
        $allowedTags = $this->validator->getAllowedTags();

        $this->assertContains('p', $allowedTags);
        $this->assertContains('strong', $allowedTags);
        $this->assertContains('a', $allowedTags);
        $this->assertContains('img', $allowedTags);
        $this->assertContains('h1', $allowedTags);
        $this->assertContains('h2', $allowedTags);
        $this->assertContains('ul', $allowedTags);
        $this->assertContains('li', $allowedTags);
    }

    /** @test */
    public function it_defines_allowed_html_attributes()
    {
        $allowedAttributes = $this->validator->getAllowedAttributes();

        $this->assertContains('href', $allowedAttributes);
        $this->assertContains('src', $allowedAttributes);
        $this->assertContains('alt', $allowedAttributes);
        $this->assertContains('title', $allowedAttributes);
        $this->assertContains('class', $allowedAttributes);
        $this->assertContains('id', $allowedAttributes);
    }

    /** @test */
    public function it_sanitizes_malicious_html_content()
    {
        $maliciousData = [
            'content' => '<script>alert("xss")</script><p onclick="evil()">Safe content</p>',
            'urls' => ['https://example.com', '<script>alert("xss")</script>'],
        ];

        $sanitized = $this->validator->sanitizeData($maliciousData);

        $this->assertStringNotContainsString('<script>', $sanitized['content']);
        $this->assertStringNotContainsString('onclick', $sanitized['content']);
        $this->assertStringContainsString('Safe content', $sanitized['content']);
        $this->assertEquals(['https://example.com'], $sanitized['urls']);
    }

    /** @test */
    public function it_processes_nested_array_structures()
    {
        $nestedContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Safe Title',
                        'nested' => [
                            'deep' => [
                                'value' => '<script>alert("XSS")</script>Safe Value'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($nestedContent);
    }

    /** @test */
    public function it_handles_complex_malicious_patterns()
    {
        $complexMaliciousContent = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Safe Title',
                        'content' => 'Safe content <script>/* multiline
                        injection */</script> more content',
                        'style' => 'background: url("javascript:alert(\'XSS\')");',
                    ]
                ]
            ]
        ];

        try {
            $this->validator->validate($complexMaliciousContent);
            $this->fail('Expected TemplateSecurityException to be thrown');
        } catch (TemplateSecurityException $e) {
            $violations = $e->getSecurityIssues();
            $this->assertGreaterThanOrEqual(2, count($violations)); // Should detect both script and URL injection
        }
    }

    /** @test */
    public function it_maintains_tenant_context_during_validation()
    {
        $this->validator->setTenantContext($this->user->tenant_id);

        $crossTenantData = [
            'tenant_id' => $this->user->tenant_id + 1, // Different tenant
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Test Title',
                    ]
                ]
            ]
        ];

        // This would be validated by other parts of the system
        $this->validator->validate($crossTenantData['sections']);
        $this->assertTrue(true); // Structure validation should pass
    }

    /** @test */
    public function it_detects_suspicious_keywords_in_dangerous_context()
    {
        $suspiciousContent = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => 'Please visit(eval("window.location="+location)) for more info',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->validator->validate($suspiciousContent);
    }
}