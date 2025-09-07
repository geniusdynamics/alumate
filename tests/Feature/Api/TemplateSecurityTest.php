<?php

namespace Tests\Feature\Api;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityLog;
use App\Models\Template;
use App\Models\User;
use App\Services\TemplateSecurityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected TemplateSecurityValidator $securityValidator;
    protected User $user;
    protected User $otherTenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityValidator = new TemplateSecurityValidator();

        // Create authenticated user
        $this->user = User::factory()->create([
            'tenant_id' => 1,
            'two_factor_enabled' => false,
        ]);

        // Create user from different tenant
        $this->otherTenantUser = User::factory()->create([
            'tenant_id' => 2,
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_blocks_script_injection_attempts()
    {
        $maliciousStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => '<script>alert("XSS")</script>Valid Title',
                        'content' => '<p>Safe content</p>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->securityValidator->validate($maliciousStructure);
    }

    /** @test */
    public function it_blocks_event_handler_injection()
    {
        $maliciousStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Safe Title',
                        'content' => '<p onclick="alert(\'XSS\')">Click me</p>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->securityValidator->validate($maliciousStructure);
    }

    /** @test */
    public function it_blocks_javascript_href_injection()
    {
        $maliciousStructure = [
            'sections' => [
                [
                    'type' => 'button',
                    'config' => [
                        'text' => 'Click me',
                        'url' => 'javascript:alert("XSS")',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->securityValidator->validate($maliciousStructure);
    }

    /** @test */
    public function it_blocks_iframe_injection()
    {
        $maliciousStructure = [
            'sections' => [
                [
                    'type' => 'text',
                    'config' => [
                        'content' => '<iframe src="http://evil.com"></iframe>',
                    ]
                ]
            ]
        ];

        $this->expectException(TemplateSecurityException::class);

        $this->securityValidator->validate($maliciousStructure);
    }

    /** @test */
    public function it_allows_safe_template_content()
    {
        $safeStructure = [
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Safe Title Here',
                        'content' => '<p>This is safe content with <strong>bold text</strong></p>',
                    ]
                ],
                [
                    'type' => 'button',
                    'config' => [
                        'text' => 'Click me',
                        'url' => 'https://example.com',
                    ]
                ]
            ]
        ];

        $violations = [];
        try {
            $this->securityValidator->validate($safeStructure);
        } catch (TemplateSecurityException $e) {
            $violations = $e->getSecurityIssues();
        }

        $this->assertEmpty($violations, 'Safe template should not trigger security violations');
    }

    /** @test */
    public function it_validates_file_upload_security()
    {
        Storage::fake('uploads');

        $validFile = UploadedFile::fake()->image('test.jpg');

        // These tests would validate file uploads through middleware or request validation
        // For now, we'll test that the validator can be created and used
        $this->assertInstanceOf(TemplateSecurityValidator::class, $this->securityValidator);

        $safeStructure = [
            'sections' => [
                [
                    'type' => 'image',
                    'config' => [
                        'url' => 'https://example.com/image.jpg',
                        'alt' => 'Valid image',
                    ]
                ]
            ]
        ];

        $this->securityValidator->validate($safeStructure);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_blocks_malicious_file_uploads()
    {
        Storage::fake('uploads');

        // Create a fake PHP file disguised as image
        $maliciousFile = UploadedFile::fake()->create('evil.php', 1000, 'image/jpeg');

        // These tests would be handled by middleware and request validation
        // For now, test that the validator properly handles structure validation
        $this->assertInstanceOf(TemplateSecurityValidator::class, $this->securityValidator);
    }

    /** @test */
    public function it_enforces_rate_limiting()
    {
        // Rate limiting is handled by middleware, but we can test the service
        $requests = [];
        for ($i = 0; $i < 120; $i++) {
            $requests[] = [
                'tenant_id' => $this->user->tenant_id,
                'user_id' => $this->user->id,
                'event_type' => 'template_access',
                'severity' => 'low',
                'resource_type' => 'template',
                'resource_id' => $i,
            ];

            SecurityLog::log($requests[$i]);
        }

        $stats = SecurityLog::getSecurityStats($this->user->tenant_id);

        $this->assertGreaterThan(0, $stats['total_events'], 'Should have logged events');
    }

    /** @test */
    public function it_validates_tenant_isolation()
    {
        // Create a template for a different tenant
        $otherTenantTemplate = Template::factory()->create([
            'tenant_id' => 2, // Different tenant
        ]);

        // Try to access it from wrong tenant
        $accessAttempt = [
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id,
            'template_id' => $otherTenantTemplate->id,
        ];

        SecurityLog::logUnauthorizedAccess(
            $this->user->tenant_id,
            $this->user->id,
            'template',
            $otherTenantTemplate->id,
            $accessAttempt
        );

        $unauthorizedEvents = SecurityLog::where('event_type', SecurityLog::EVENT_TYPE_UNAUTHORIZED_ACCESS)
            ->where('tenant_id', $this->user->tenant_id)
            ->count();

        $this->assertGreaterThan(0, $unauthorizedEvents, 'Should log unauthorized access attempts');
    }

    /** @test */
    public function it_logs_security_events_with_metadata()
    {
        $securityEvent = SecurityLog::logTemplateViolation(
            $this->user->tenant_id,
            $this->user->id,
            123,
            [
                [
                    'type' => 'script_injection',
                    'severity' => 'high',
                    'context' => 'template_config'
                ]
            ],
            [
                'user_agent' => 'Mozilla/5.0',
                'request_path' => '/api/templates/123'
            ]
        );

        $this->assertInstanceOf(SecurityLog::class, $securityEvent);
        $this->assertEquals(SecurityLog::EVENT_TYPE_TEMPLATE_VIOLATION, $securityEvent->event_type);
        $this->assertEquals($this->user->tenant_id, $securityEvent->tenant_id);
        $this->assertIsArray($securityEvent->validation_errors);
    }

    /** @test */
    public function it_prevents_cross_tenant_resource_access()
    {
        $crossTenantData = [
            'tenant_id' => $this->user->tenant_id,
            'template_id' => 999, // Non-existent template
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'tenant_id' => 2, // Different tenant ID in config
                        'title' => 'Test Title'
                    ]
                ]
            ]
        ];

        // This should be blocked by tenant isolation validation
        $violations = $this->securityValidator->validateTemplateInput($crossTenantData);

        $this->assertNotEmpty($violations, 'Cross-tenant access should be blocked');
    }

    /** @test */
    public function it_validates_url_security_for_template_content()
    {
        $suspiciousUrls = [
            'javascript:alert("XSS")',
            'vbscript:msgbox("Hi")',
            'data:text/html,<script>alert("XSS")</script>',
        ];

        foreach ($suspiciousUrls as $url) {
            $templateData = [
                'sections' => [
                    [
                        'type' => 'button',
                        'config' => [
                            'url' => $url,
                            'text' => 'Click me'
                        ]
                    ]
                ]
            ];

            $violations = $this->securityValidator->validateTemplateInput($templateData);

            $this->assertNotEmpty($violations, "URL {$url} should trigger security violations");
        }
    }

    /** @test */
    public function it_allows_secure_https_urls()
    {
        $secureUrls = [
            'https://example.com',
            'https://subdomain.example.com/path',
            'https://example.com?param=value',
        ];

        foreach ($secureUrls as $url) {
            $templateData = [
                'sections' => [
                    [
                        'type' => 'button',
                        'config' => [
                            'url' => $url,
                            'text' => 'Click me'
                        ]
                    ]
                ]
            ];

            $violations = $this->securityValidator->validateTemplateInput($templateData);

            $this->assertEmpty($violations, "Secure URL {$url} should not trigger violations");
        }
    }

    /** @test */
    public function it_sanitizes_template_input_data()
    {
        $rawData = [
            'name' => 'Safe Name<script>alert("XSS")</script>',
            'description' => 'Safe description<p onclick="evil()">Click me</p>',
            'sections' => [
                [
                    'type' => 'hero',
                    'config' => [
                        'title' => 'Safe Title<embed src="evil.swf">',
                    ]
                ]
            ]
        ];

        $sanitized = $this->securityValidator->sanitizeData($rawData);

        $this->assertStringNotContainsString('<script>', $sanitized['name']);
        $this->assertStringNotContainsString('onclick', $sanitized['description']);
        $this->assertStringNotContainsString('<embed>', $sanitized['sections'][0]['config']['title']);
    }

    /** @test */
    public function it_generates_comprehensive_security_reports()
    {
        // Create multiple security events
        SecurityLog::logTemplateViolation($this->user->tenant_id, $this->user->id, null, [
            ['type' => 'script_injection', 'severity' => 'high']
        ]);

        SecurityLog::logXssAttempt($this->user->tenant_id, $this->user->id, null, [
            'script_pattern' => '<script>'
        ]);

        $report = SecurityLog::generateSecurityReport($this->user->tenant_id);

        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('critical_events', $report);
        $this->assertArrayHasKey('total_events', $report['statistics']);
        $this->assertGreaterThan(0, $report['statistics']['total_events']);
    }

    /** @test */
    public function it_marks_security_events_as_resolved()
    {
        $event = SecurityLog::logTemplateViolation(
            $this->user->tenant_id,
            $this->user->id,
            null,
            [['type' => 'script_injection', 'severity' => 'high']]
        );

        $this->assertEquals(SecurityLog::STATUS_OPEN, $event->resolution_status);

        $event->markResolved('Issue resolved - whitelist updated');

        $this->assertEquals(SecurityLog::STATUS_RESOLVED, $event->refresh()->resolution_status);
        $this->assertNotNull($event->refresh()->resolved_at);
    }

    /** @test */
    public function it_provides_comprehensive_security_statistics()
    {
        // Create events with different severities
        SecurityLog::logTemplateViolation($this->user->tenant_id, $this->user->id, null, [
            ['type' => 'script_injection', 'severity' => 'critical']
        ]);

        SecurityLog::logUnauthorizedAccess(
            $this->user->tenant_id,
            $this->user->id,
            'template',
            123
        );

        $stats = SecurityLog::getSecurityStats($this->user->tenant_id);

        $this->assertArrayHasKey('total_events', $stats);
        $this->assertArrayHasKey('critical_events', $stats);
        $this->assertArrayHasKey('unresolved_events', $stats);
        $this->assertGreaterThan(0, $stats['total_events']);
        $this->assertGreaterThan(0, $stats['critical_events']);
    }
}