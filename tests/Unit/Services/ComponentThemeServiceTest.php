<?php

namespace Tests\Unit\Services;

use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Services\ComponentThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComponentThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ComponentThemeService $service;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ComponentThemeService;
        $this->tenant = Tenant::factory()->create();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_applies_theme_with_css_variable_generation(): void
    {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#007bff'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $result = $this->service->applyTheme($theme);

        $this->assertArrayHasKey('css_variables', $result);
        $this->assertArrayHasKey('affected_components', $result);
        $this->assertArrayHasKey('css_file_path', $result);
        $this->assertArrayHasKey('cache_key', $result);
        $this->assertStringContainsString('--color-primary: #007bff', $result['css_variables']);
        $this->assertTrue(Cache::has($result['cache_key']));
        $this->assertTrue(Storage::disk('public')->exists($result['css_file_path']));
    }

    public function test_applies_theme_to_specific_components(): void
    {
        $theme = ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);
        $componentIds = [1, 2, 3];

        $result = $this->service->applyTheme($theme, $componentIds);

        $this->assertArrayHasKey('affected_components', $result);
    }

    public function test_creates_theme_with_default_parent_inheritance(): void
    {
        $defaultTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true,
            'config' => [
                'colors' => ['primary' => '#000000'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $newConfig = [
            'colors' => ['primary' => '#ff0000'],
        ];

        $theme = $this->service->createThemeWithInheritance(
            $this->tenant->id,
            'Custom Theme',
            $newConfig
        );

        $this->assertEquals('#ff0000', $theme->config['colors']['primary']);
        $this->assertEquals('Arial', $theme->config['typography']['font_family']);
        $this->assertFalse($theme->is_default);
    }

    public function test_creates_theme_with_specific_parent_inheritance(): void
    {
        $parentTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#0000ff'],
                'typography' => ['font_family' => 'Georgia'],
                'spacing' => ['base' => '2rem'],
            ],
        ]);

        $newConfig = [
            'colors' => ['secondary' => '#00ff00'],
        ];

        $theme = $this->service->createThemeWithInheritance(
            $this->tenant->id,
            'Child Theme',
            $newConfig,
            $parentTheme
        );

        $this->assertEquals('#0000ff', $theme->config['colors']['primary']);
        $this->assertEquals('#00ff00', $theme->config['colors']['secondary']);
        $this->assertEquals('Georgia', $theme->config['typography']['font_family']);
    }

    public function test_validates_valid_theme_configuration(): void
    {
        $validConfig = [
            'colors' => [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'background' => '#ffffff',
                'text' => '#333333',
            ],
            'typography' => [
                'font_family' => 'Arial, sans-serif',
                'font_sizes' => ['base' => '16px'],
                'line_height' => 1.6,
            ],
            'spacing' => [
                'base' => '1rem',
                'sm' => '0.5rem',
            ],
        ];

        $result = $this->service->validateThemeConfig($validConfig);

        $this->assertTrue($result);
    }

    public function test_throws_validation_exception_for_invalid_colors(): void
    {
        $this->expectException(ValidationException::class);

        $invalidConfig = [
            'colors' => [
                'primary' => 'invalid-color',
            ],
            'typography' => [
                'font_family' => 'Arial',
            ],
            'spacing' => [
                'base' => '1rem',
            ],
        ];

        $this->service->validateThemeConfig($invalidConfig);
    }

    public function test_validates_accessibility_requirements(): void
    {
        $this->expectException(ValidationException::class);

        $lowContrastConfig = [
            'colors' => [
                'primary' => '#ffffff',
                'background' => '#f8f9fa',
                'text' => '#cccccc',
            ],
            'typography' => [
                'font_family' => 'Arial',
            ],
            'spacing' => [
                'base' => '1rem',
            ],
        ];

        $this->service->validateThemeConfig($lowContrastConfig);
    }

    public function test_gets_themes_for_specific_tenant_only(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        ComponentTheme::factory()->create(['tenant_id' => $tenant1->id, 'name' => 'Tenant 1 Theme']);
        ComponentTheme::factory()->create(['tenant_id' => $tenant2->id, 'name' => 'Tenant 2 Theme']);

        $tenant1Themes = $this->service->getThemesForTenant($tenant1->id);
        $tenant2Themes = $this->service->getThemesForTenant($tenant2->id);

        $this->assertCount(1, $tenant1Themes);
        $this->assertCount(1, $tenant2Themes);
        $this->assertEquals('Tenant 1 Theme', $tenant1Themes->first()->name);
        $this->assertEquals('Tenant 2 Theme', $tenant2Themes->first()->name);
    }

    public function test_validates_tenant_access_to_themes(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $theme = ComponentTheme::factory()->create(['tenant_id' => $tenant1->id]);

        $this->assertTrue($this->service->validateTenantAccess($theme, $tenant1->id));
        $this->assertFalse($this->service->validateTenantAccess($theme, $tenant2->id));
    }

    public function test_excludes_default_themes_when_requested(): void
    {
        ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true,
            'name' => 'Default Theme',
        ]);
        ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false,
            'name' => 'Custom Theme',
        ]);

        $customThemes = $this->service->getThemesForTenant($this->tenant->id, false);

        $this->assertCount(1, $customThemes);
        $this->assertEquals('Custom Theme', $customThemes->first()->name);
    }

    public function test_generates_comprehensive_theme_preview(): void
    {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#007bff'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $preview = $this->service->generateThemePreview($theme, ['hero', 'button']);

        $this->assertArrayHasKey('html', $preview);
        $this->assertArrayHasKey('css', $preview);
        $this->assertArrayHasKey('component_previews', $preview);
        $this->assertArrayHasKey('responsive_previews', $preview);
        $this->assertArrayHasKey('accessibility_report', $preview);
        $this->assertArrayHasKey('hero', $preview['component_previews']);
        $this->assertArrayHasKey('button', $preview['component_previews']);
        $this->assertArrayHasKey('desktop', $preview['responsive_previews']);
        $this->assertArrayHasKey('tablet', $preview['responsive_previews']);
        $this->assertArrayHasKey('mobile', $preview['responsive_previews']);
    }

    public function test_compiles_theme_css_without_minification(): void
    {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#007bff'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $css = $this->service->compileThemeCss($theme, false);

        $this->assertStringContainsString('--color-primary: #007bff', $css);
        $this->assertStringContainsString('Theme: '.$theme->name, $css);
        $this->assertStringContainsString('.component-button', $css);
    }

    public function test_compiles_and_minifies_theme_css(): void
    {
        $theme = ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);

        $css = $this->service->compileThemeCss($theme, true);
        $unminified = $this->service->compileThemeCss($theme, false);

        $this->assertLessThan(strlen($unminified), strlen($css));
    }

    public function test_creates_theme_backup(): void
    {
        $theme = ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);

        $backupPath = $this->service->backupTheme($theme);

        $this->assertTrue(Storage::disk('local')->exists($backupPath));

        $backupData = json_decode(Storage::disk('local')->get($backupPath), true);
        $this->assertArrayHasKey('theme_data', $backupData);
        $this->assertArrayHasKey('created_at', $backupData);
        $this->assertArrayHasKey('version', $backupData);
        $this->assertEquals($theme->id, $backupData['theme_data']['id']);
    }

    public function test_restores_theme_from_backup(): void
    {
        $originalTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Theme',
            'config' => [
                'colors' => ['primary' => '#ff0000'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $backupPath = $this->service->backupTheme($originalTheme);
        $originalTheme->delete();

        $restoredTheme = $this->service->restoreTheme($backupPath, $this->tenant->id);

        $this->assertEquals('Original Theme', $restoredTheme->name);
        $this->assertEquals('#ff0000', $restoredTheme->config['colors']['primary']);
        $this->assertEquals($this->tenant->id, $restoredTheme->tenant_id);
        $this->assertNotEquals($originalTheme->id, $restoredTheme->id);
    }

    public function test_throws_exception_for_invalid_backup_file(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Backup file not found');

        $invalidBackupPath = 'invalid/backup/path.json';

        $this->service->restoreTheme($invalidBackupPath, $this->tenant->id);
    }

    public function test_gets_existing_default_theme(): void
    {
        $existingDefault = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true,
        ]);

        $defaultTheme = $this->service->getOrCreateDefaultTheme($this->tenant->id);

        $this->assertEquals($existingDefault->id, $defaultTheme->id);
    }

    public function test_creates_default_theme_when_none_exists(): void
    {
        $defaultTheme = $this->service->getOrCreateDefaultTheme($this->tenant->id);

        $this->assertTrue($defaultTheme->is_default);
        $this->assertEquals($this->tenant->id, $defaultTheme->tenant_id);
        $this->assertArrayHasKey('colors', $defaultTheme->config);
        $this->assertArrayHasKey('typography', $defaultTheme->config);
        $this->assertArrayHasKey('spacing', $defaultTheme->config);
    }

    public function test_calculates_theme_usage_statistics(): void
    {
        $theme = ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);

        $stats = $this->service->getThemeUsageStats($theme);

        $this->assertArrayHasKey('components_using_theme', $stats);
        $this->assertArrayHasKey('last_used', $stats);
        $this->assertArrayHasKey('is_active', $stats);
        $this->assertArrayHasKey('accessibility_score', $stats);
        $this->assertIsInt($stats['accessibility_score']);
        $this->assertGreaterThanOrEqual(0, $stats['accessibility_score']);
        $this->assertLessThanOrEqual(100, $stats['accessibility_score']);
    }

    public function test_duplicates_theme_with_modifications(): void
    {
        $originalTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original',
            'config' => [
                'colors' => ['primary' => '#0000ff'],
                'typography' => ['font_family' => 'Arial'],
                'spacing' => ['base' => '1rem'],
            ],
        ]);

        $configOverrides = [
            'colors' => ['primary' => '#ff0000'],
        ];

        $duplicatedTheme = $this->service->duplicateTheme(
            $originalTheme,
            'Duplicated Theme',
            $configOverrides
        );

        $this->assertEquals('Duplicated Theme', $duplicatedTheme->name);
        $this->assertEquals('#ff0000', $duplicatedTheme->config['colors']['primary']);
        $this->assertEquals('Arial', $duplicatedTheme->config['typography']['font_family']);
        $this->assertEquals($this->tenant->id, $duplicatedTheme->tenant_id);
    }

    public function test_exports_theme_configuration(): void
    {
        $theme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Export Theme',
            'config' => ['colors' => ['primary' => '#ff6600']],
        ]);

        $exported = $this->service->exportTheme($theme);

        $this->assertArrayHasKey('name', $exported);
        $this->assertArrayHasKey('config', $exported);
        $this->assertArrayHasKey('metadata', $exported);
        $this->assertEquals('Export Theme', $exported['name']);
        $this->assertEquals('#ff6600', $exported['config']['colors']['primary']);
        $this->assertArrayHasKey('exported_at', $exported['metadata']);
        $this->assertArrayHasKey('version', $exported['metadata']);
        $this->assertArrayHasKey('accessibility_score', $exported['metadata']);
    }

    public function test_imports_theme_configuration(): void
    {
        $themeData = [
            'config' => [
                'colors' => ['primary' => '#00ccff'],
                'typography' => ['font_family' => 'Imported Font'],
                'spacing' => ['base' => '2rem'],
            ],
        ];

        $importedTheme = $this->service->importTheme($themeData, $this->tenant->id, 'Imported Theme');

        $this->assertEquals('Imported Theme', $importedTheme->name);
        $this->assertEquals('#00ccff', $importedTheme->config['colors']['primary']);
        $this->assertEquals($this->tenant->id, $importedTheme->tenant_id);
    }

    public function test_throws_exception_for_invalid_import_data(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid theme data: missing configuration');

        $invalidData = ['invalid' => 'data'];

        $this->service->importTheme($invalidData, $this->tenant->id, 'Invalid');
    }
}
