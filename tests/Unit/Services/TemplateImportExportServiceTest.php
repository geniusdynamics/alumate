<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TemplateImportExportService;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateImportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private TemplateImportExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TemplateImportExportService();
    }

    public function test_export_templates_json()
    {
        $template = Template::factory()->create([
            'name' => 'Test Template',
            'category' => 'landing',
            'structure' => ['sections' => []]
        ]);

        $result = $this->service->exportTemplates([$template->id], 'json');

        $this->assertIsArray($result);
        $this->assertEquals('json', $result['format']);
        $this->assertEquals(1, $result['count']);
        $this->assertArrayHasKey('data', $result);

        $data = json_decode($result['data'], true);
        $this->assertArrayHasKey('templates', $data);
        $this->assertCount(1, $data['templates']);
        $this->assertEquals('Test Template', $data['templates'][0]['name']);
    }

    public function test_export_templates_xml()
    {
        $template = Template::factory()->create([
            'name' => 'XML Test Template',
            'category' => 'email'
        ]);

        $result = $this->service->exportTemplates([$template->id], 'xml');

        $this->assertEquals('xml', $result['format']);
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $result['data']);
        $this->assertStringContainsString('XML Test Template', $result['data']);
    }

    public function test_export_templates_csv()
    {
        $template = Template::factory()->create([
            'name' => 'CSV Test Template',
            'category' => 'social'
        ]);

        $result = $this->service->exportTemplates([$template->id], 'csv');

        $this->assertEquals('csv', $result['format']);
        $this->assertStringContainsString('CSV Test Template', $result['data']);
        $this->assertStringContainsString('name', $result['data']); // Headers
    }

    public function test_import_templates_json()
    {
        $templateData = [
            'name' => 'Imported Template',
            'description' => 'Test import',
            'category' => 'landing',
            'structure' => ['sections' => []]
        ];

        $importData = json_encode([
            'templates' => [$templateData]
        ]);

        $result = $this->service->importTemplates($importData, 'json');

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        $this->assertDatabaseHas('templates', [
            'name' => 'Imported Template',
            'category' => 'landing'
        ]);
    }

    public function test_import_templates_with_validation_error()
    {
        $invalidData = json_encode([
            'templates' => [
                [
                    'name' => '', // Invalid: empty name
                    'category' => 'invalid_category_too_long_name_that_exceeds_limits'
                ]
            ]
        ]);

        $result = $this->service->importTemplates($invalidData, 'json');

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, count($result['errors']));
    }

    public function test_export_to_file()
    {
        Storage::fake('local');

        $template = Template::factory()->create();

        $filePath = $this->service->exportToFile([$template->id], 'json', 'test_export.json');

        $this->assertTrue(Storage::disk('local')->exists($filePath));

        $content = Storage::disk('local')->get($filePath);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('templates', $data);
        $this->assertCount(1, $data['templates']);
    }

    public function test_import_from_file()
    {
        Storage::fake('local');

        $templateData = [
            'name' => 'File Import Template',
            'category' => 'landing',
            'structure' => ['sections' => []]
        ];

        $fileContent = json_encode(['templates' => [$templateData]]);
        Storage::disk('local')->put('imports/test.json', $fileContent);

        $result = $this->service->importFromFile('imports/test.json');

        $this->assertEquals(1, $result['imported']);
        $this->assertDatabaseHas('templates', [
            'name' => 'File Import Template'
        ]);
    }

    public function test_skip_existing_templates_without_overwrite()
    {
        $existingTemplate = Template::factory()->create([
            'name' => 'Existing Template'
        ]);

        $importData = json_encode([
            'templates' => [
                [
                    'name' => 'Existing Template',
                    'category' => 'landing',
                    'structure' => ['sections' => []]
                ]
            ]
        ]);

        $result = $this->service->importTemplates($importData, 'json');

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertStringContainsString('already exists', $result['warnings'][0]);
    }

    public function test_overwrite_existing_templates()
    {
        $existingTemplate = Template::factory()->create([
            'name' => 'Template to Overwrite',
            'category' => 'email'
        ]);

        $importData = json_encode([
            'templates' => [
                [
                    'name' => 'Template to Overwrite',
                    'category' => 'landing', // Different category
                    'structure' => ['sections' => []]
                ]
            ]
        ]);

        $result = $this->service->importTemplates($importData, 'json', [
            'overwrite_existing' => true
        ]);

        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['skipped']);

        // Verify the template was updated
        $updatedTemplate = Template::where('name', 'Template to Overwrite')->first();
        $this->assertEquals('landing', $updatedTemplate->category);
    }

    public function test_batch_import_processing()
    {
        $templates = [];
        for ($i = 0; $i < 5; $i++) {
            $templates[] = [
                'name' => "Batch Template {$i}",
                'category' => 'landing',
                'structure' => ['sections' => []]
            ];
        }

        $importData = json_encode(['templates' => $templates]);

        $result = $this->service->importTemplates($importData, 'json');

        $this->assertEquals(5, $result['imported']);
        $this->assertDatabaseCount('templates', 5);
    }

    public function test_unsupported_format_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->exportTemplates([1], 'unsupported');
    }

    public function test_empty_template_list_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->exportTemplates([], 'json');
    }

    public function test_get_export_template()
    {
        $template = $this->service->getExportTemplate();

        $this->assertArrayHasKey('formats', $template);
        $this->assertArrayHasKey('options', $template);
        $this->assertContains('json', $template['formats']);
        $this->assertContains('xml', $template['formats']);
        $this->assertContains('csv', $template['formats']);
    }

    public function test_file_size_limit()
    {
        Storage::fake('local');

        // Create a large file
        $largeContent = str_repeat('x', 11 * 1024 * 1024); // 11MB
        Storage::disk('local')->put('large_file.json', $largeContent);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->importFromFile('large_file.json');
    }

    public function test_invalid_file_path_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->importFromFile('nonexistent/file.json');
    }
}