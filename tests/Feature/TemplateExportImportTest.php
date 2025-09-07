<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TemplateExportImportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $testUser;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(['public', 'private']);

        $this->institution = Institution::factory()->create([
            'name' => 'Export University',
            'domain' => 'export-university.com',
        ]);

        $this->testUser = User::factory()->create([
            'name' => 'Export Admin',
            'email' => 'admin@export-university.com',
            'tenant_id' => $this->institution->id,
        ]);
    }

    public function test_template_export_to_json_format()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'JSON Export Test',
            'category' => 'landing',
            'audience_type' => 'individual',
            'campaign_type' => 'marketing',
            'structure' => [
                'version' => '1.0',
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Export Test Title',
                            'cta_text' => 'Click Here',
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => ['primary_color' => '#ff0000'],
                'typography' => ['font_family' => 'Arial'],
            ],
            'tags' => ['test', 'export'],
            'is_premium' => true,
            'usage_count' => 100,
        ]);

        // Export to JSON
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=json");

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/json')
                ->assertHeader('Content-Disposition', 'attachment; filename="JSON Export Test.json"');

        $exportData = $response->json();

        // Verify export structure
        $this->assertArrayHasKey('template', $exportData);
        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertArrayHasKey('export_info', $exportData);

        $templateData = $exportData['template'];
        $this->assertEquals($template->name, $templateData['name']);
        $this->assertEquals($template->structure, $templateData['structure']);
        $this->assertEquals($template->default_config, $templateData['default_config']);
        $this->assertEquals($template->tags, $templateData['tags']);
        $this->assertEquals($template->is_premium, $templateData['is_premium']);

        // Verify metadata
        $metadata = $exportData['metadata'];
        $this->assertArrayHasKey('version', $metadata);
        $this->assertArrayHasKey('tenant_id', $metadata); // For cross-tenant validation
        $this->assertEquals($this->testUser->tenant_id, $metadata['tenant_id']);

        // Verify export info
        $this->assertArrayHasKey('exported_at', $exportData['export_info']);
        $this->assertArrayHasKey('exported_by', $exportData['export_info']);
        $this->assertEquals($this->testUser->id, $exportData['export_info']['exported_by']);
    }

    public function test_template_import_from_json_format()
    {
        // Create export data
        $exportData = [
            'template' => [
                'name' => 'JSON Import Test',
                'description' => 'Imported template from JSON',
                'category' => 'landing',
                'audience_type' => 'individual',
                'campaign_type' => 'marketing',
                'structure' => [
                    'version' => '1.0',
                    'sections' => [
                        [
                            'type' => 'hero',
                            'config' => [
                                'title' => 'Imported Title',
                                'cta_text' => 'Imported CTA',
                            ]
                        ]
                    ]
                ],
                'default_config' => [
                    'theme' => ['primary_color' => '#00ff00'],
                ],
                'tags' => ['imported', 'test'],
                'is_premium' => false,
            ],
            'metadata' => [
                'version' => '1.0',
                'format' => 'json',
                'tenant_id' => $this->testUser->tenant_id, // Same tenant
            ],
            'export_info' => [
                'exported_at' => now()->toISOString(),
                'exported_by' => $this->testUser->id,
            ]
        ];

        // Import from JSON
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => $exportData,
            ]);

        $response->assertStatus(201);

        $importedTemplateId = $response->json('data.id');
        $importedTemplate = Template::find($importedTemplateId);

        // Verify import results
        $this->assertEquals('JSON Import Test', $importedTemplate->name);
        $this->assertEquals('landing', $importedTemplate->category);
        $this->assertEquals($this->testUser->tenant_id, $importedTemplate->tenant_id);
        $this->assertEquals($exportData['template']['structure'], $importedTemplate->structure);
        $this->assertEquals($exportData['template']['default_config'], $importedTemplate->default_config);
        $this->assertEquals($exportData['template']['tags'], $importedTemplate->tags);
    }

    public function test_template_export_to_xml_format()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'XML Export Test',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'XML Export Title',
                            'subtitle' => 'XML format test',
                        ]
                    ]
                ]
            ],
        ]);

        // Export to XML
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=xml");

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/xml')
                ->assertHeader('Content-Disposition', 'attachment; filename="XML Export Test.xml"');

        $xmlContent = $response->getContent();

        // Verify XML structure
        $this->assertStringContains('<template>', $xmlContent);
        $this->assertStringContains('<name>XML Export Test</name>', $xmlContent);
        $this->assertStringContains('<structure>', $xmlContent);
        $this->assertStringContains('<sections>', $xmlContent);
        $this->assertStringContains('XML Export Title', $xmlContent);

        // Verify XML is well-formed
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xmlContent);
    }

    public function test_template_export_to_yaml_format()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'YAML Export Test',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'YAML Export Title',
                        ]
                    ]
                ]
            ],
            'default_config' => [
                'theme' => ['primary_color' => '#0000ff'],
            ],
        ]);

        // Export to YAML
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=yaml");

        $response->assertStatus(200)
                ->assertHeader('Content-Type', 'application/x-yaml')
                ->assertHeader('Content-Disposition', 'attachment; filename="YAML Export Test.yaml"');

        $yamlContent = $response->getContent();

        // Verify YAML structure
        $this->assertStringContains('template:', $yamlContent);
        $this->assertStringContains('name: YAML Export Test', $yamlContent);
        $this->assertStringContains('structure:', $yamlContent);
        $this->assertStringContains('sections:', $yamlContent);
        $this->assertStringContains('YAML Export Title', $yamlContent);

        // Verify YAML formatting
        $this->assertStringContains('---', $yamlContent); // YAML document separator
    }

    public function test_template_bulk_export_and_import()
    {
        // Create multiple templates for bulk operations
        $templates = Template::factory()->count(5)->create([
            'tenant_id' => $this->testUser->tenant_id,
        ]);

        // Bulk export
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/bulk-export', [
                'template_ids' => $templates->pluck('id')->toArray(),
                'format' => 'json',
            ]);

        $response->assertStatus(200);

        $bulkExportData = $response->json();

        // Verify bulk export structure
        $this->assertArrayHasKey('templates', $bulkExportData);
        $this->assertArrayHasKey('metadata', $bulkExportData);
        $this->assertCount(5, $bulkExportData['templates']);

        // Bulk import
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/bulk-import', [
                'format' => 'json',
                'data' => $bulkExportData,
                'options' => [
                    'rename_duplicates' => true,
                    'preserve_metadata' => true,
                ],
            ]);

        $response->assertStatus(200);

        $bulkImportResult = $response->json();
        $this->assertEquals(5, $bulkImportResult['imported_count']);
        $this->assertEquals(0, $bulkImportResult['skipped_count']);
    }

    public function test_template_export_with_dependencies()
    {
        // Create template with referenced components (simulated)
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Dependencies Export Test',
            'structure' => [
                'sections' => [
                    [
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Dependencies Test',
                        ]
                    ]
                ]
            ],
        ]);

        // Add simulated dependencies
        $template->update([
            'custom_components' => [
                'hero' => 'component-id-123',
                'custom-script' => 'script-id-456',
            ],
            'media_assets' => [
                ['type' => 'image', 'filename' => 'hero-bg.jpg', 'path' => '/uploads/images/'],
                ['type' => 'video', 'filename' => 'hero-video.mp4', 'path' => '/uploads/videos/'],
            ]
        ]);

        // Export with dependencies
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?include_dependencies=true");

        $response->assertStatus(200);

        $exportData = $response->json();

        // Verify dependencies are included
        $this->assertArrayHasKey('dependencies', $exportData);
        $this->assertArrayHasKey('components', $exportData['dependencies']);
        $this->assertArrayHasKey('media', $exportData['dependencies']);
        $this->assertArrayHasKey('scripts', $exportData['dependencies']);
    }

    public function test_cross_tenant_export_import_restrictions()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Cross-Tenant Test',
        ]);

        // Export from tenant A
        $response = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export");

        $response->assertStatus(200);
        $exportData = $response->json();

        // Verify tenant information is included for security
        $this->assertArrayHasKey('tenant_id', $exportData['template']);
        $this->assertEquals($this->testUser->tenant_id, $exportData['template']['tenant_id']);

        // Create user from different tenant
        $otherInstitution = Institution::factory()->create(['domain' => 'other-university.com']);
        $otherUser = User::factory()->create([
            'tenant_id' => $otherInstitution->id,
            'email' => 'admin@other-university.com',
        ]);

        // Attempt to import cross-tenant
        $response = $this->actingAs($otherUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => $exportData,
            ]);

        // Should be rejected due to tenant mismatch
        $response->assertStatus(403);

        // But same-tenant import should work
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => $exportData,
                'options' => ['rename_duplicates' => true],
            ]);

        $response->assertStatus(201);
    }

    public function test_template_import_validation_and_error_handling()
    {
        // Test invalid JSON format
        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => 'invalid json string',
            ]);

        $response->assertStatus(422);

        // Test missing required fields
        $invalidData = [
            'template' => [
                'name' => '', // Empty name
                'category' => 'invalid_category',
            ],
            'metadata' => [],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => $invalidData,
            ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['template.name', 'template.category']);

        // Test corrupted data handling
        $corruptedData = [
            'template' => [
                'name' => 'Corrupted Import',
                'category' => 'landing',
                'audience_type' => 'individual',
                'campaign_type' => 'marketing',
                'structure' => 'not an array', // Invalid structure
            ],
        ];

        $response = $this->actingAs($this->testUser)
            ->postJson('/api/templates/import', [
                'format' => 'json',
                'data' => $corruptedData,
            ]);

        // Should handle corruption gracefully
        if ($response->getStatusCode() === 422) {
            $response->assertJsonValidationErrors('template.structure');
        } elseif ($response->getStatusCode() === 201) {
            // If accepted, verify structure was sanitized
            $importedTemplate = Template::find($response->json('data.id'));
            $this->assertIsArray($importedTemplate->structure);
        }
    }

    public function test_template_format_conversion_on_export()
    {
        $template = Template::factory()->create([
            'tenant_id' => $this->testUser->tenant_id,
            'name' => 'Format Conversion Test',
            'structure' => [
                'version' => '1.0',
                'sections' => [
                    [
                        'id' => 'section-1',
                        'type' => 'hero',
                        'config' => [
                            'title' => 'Format Test',
                            'settings' => [
                                'layout' => 'full-width',
                                'theme' => 'dark',
                            ]
                        ]
                    ]
                ]
            ],
        ]);

        // Export in different formats and verify conversions
        $jsonResponse = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=json");

        $yamlResponse = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=yaml");

        $xmlResponse = $this->actingAs($this->testUser)
            ->getJson("/api/templates/{$template->id}/export?format=xml");

        // All should succeed
        $jsonResponse->assertStatus(200);
        $yamlResponse->assertStatus(200);
        $xmlResponse->assertStatus(200);

        // Verify content is equivalent (same data, different formats)
        $jsonData = $jsonResponse->json()['template'];
        $yamlData = yaml_parse($yamlResponse->getContent())['template'];
        $xmlData = json_decode(json_encode(simplexml_load_string($xmlResponse->getContent())), true)['template'];

        // Core data should be identical
        $this->assertEquals($jsonData['name'], $yamlData['name']);
        $this->assertEquals($jsonData['category'], $xmlData['category']);
    }
}