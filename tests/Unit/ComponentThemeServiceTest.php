<?php

namespace Tests\Unit;

use App\Models\ComponentTheme;
use App\Models\Tenant;
use App\Services\ComponentThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Tests\TestCase;

class ComponentThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ComponentThemeService $service;

    protected Tenant $tenant;

    protected ComponentTheme $defaultTheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ComponentThemeService;
        $this->tenant = Tenant::factory()->create();
        $this->defaultTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => true,
            'config' => [
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
                    'lg' => '2rem',
                ],
            ],
        ]);
    }

    public function test_applies_theme_with_css_variable_generation(): void
    {
        $result = $this->service->applyTheme($this->defaultTheme);

        $this->assertArrayHasKey('css_variables', $result);
        $this->assertArrayHasKey('affected_components', $result);
        $this->assertArrayHasKey('cache_key', $result);
        $this->assertStringContainsString('--color-primary: #007bff', $result['css_variables']);
        $this->assertStringContainsString('--font-family: Arial, sans-serif', $result['css_variables']);
        $this->assertStringContainsString('--spacing-base: 1rem', $result['css_variables']);
    }

    public function test_caches_generated_css_for_performance(): void
    {
        $result = $this->service->applyTheme($this->defaultTheme);

        $cachedCss = Cache::get($result['cache_key']);
        $this->assertEquals($result['css_variables'], $cachedCss);
    }

    public function test_applies_theme_to_specific_components_when_provided(): void
    {
        $components = \App\Models\Component::factory(3)->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $result = $this->service->applyTheme($this->defaultTheme, $components->pluck('id')->toArray());

        $this->assertEquals(3, $result['affected_components']);

        // Verify components have the theme applied
        $components->each(function ($component) {
            $component->refresh();
            $this->assertEquals($this->defaultTheme->id, $component->theme_id);
        });
    }

    public function test_creates_theme_with_inheritance_from_default_theme(): void
    {
        $themeData = [
            'name' => 'Custom Theme',
            'config' => [
                'colors' => [
                    'primary' => '#ff0000', // Override primary color
                ],
                'typography' => [
                    'font_family' => 'Georgia, serif', // Override font
                ],
            ],
        ];

        $theme = $this->service->createThemeWithInheritance($this->tenant->id, $themeData);

        $this->assertEquals('Custom Theme', $theme->name);
        $this->assertEquals('#ff0000', $theme->config['colors']['primary']);
        $this->assertEquals('#6c757d', $theme->config['colors']['secondary']); // Inherited
        $this->assertEquals('Georgia, serif', $theme->config['typography']['font_family']);
        $this->assertEquals('1rem', $theme->config['spacing']['base']); // Inherited
    }

    public function test_creates_theme_with_specific_parent_theme(): void
    {
        $parentTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'config' => [
                'colors' => ['primary' => '#00ff00'],
                'typography' => ['font_family' => 'Helvetica'],
                'spacing' => ['base' => '2rem'],
            ],
        ]);

        $themeData = [
            'name' => 'Child Theme',
            'config' => [
                'colors' => ['secondary' => '#ff00ff'],
            ],
        ];

        $theme = $this->service->createThemeWithInheritance($this->tenant->id, $themeData, $parentTheme);

        $this->assertEquals('#00ff00', $theme->config['colors']['primary']); // From parent
        $this->assertEquals('#ff00ff', $theme->config['colors']['secondary']); // From child
        $this->assertEquals('Helvetica', $theme->config['typography']['font_family']); // From parent
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

        $this->assertTrue($this->service->validateThemeConfig($validConfig));
    }

    public function test_throws_validation_exception_for_invalid_colors(): void
    {
        $this->expectException(ValidationException::class);

        $invalidConfig = [
            'colors' => [
                'primary' => 'invalid-color', // Invalid hex color
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

    public function test_throws_validation_exception_for_invalid_typography(): void
    {
        $this->expectException(ValidationException::class);

        $invalidConfig = [
            'colors' => [
                'primary' => '#007bff',
            ],
            'typography' => [
                'font_family' => '', // Empty font family
                'line_height' => 5, // Too high line height
            ],
            'spacing' => [
                'base' => '1rem',
            ],
        ];

        $this->service->validateThemeConfig($invalidConfig);
    }

    public function test_gets_themes_for_specific_tenant_only(): void
    {
        $otherTenant = Tenant::factory()->create();

        // Create themes for different tenants
        ComponentTheme::factory()->create(['tenant_id' => $this->tenant->id]);
        ComponentTheme::factory()->create(['tenant_id' => $otherTenant->id]);

        $themes = $this->service->getThemesForTenant($this->tenant->id);

        $this->assertCount(2, $themes); // Default theme + new theme
        $themes->each(function ($theme) {
            $this->assertEquals($this->tenant->id, $theme->tenant_id);
        });
    }

    public function test_validates_tenant_access_to_themes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Theme does not belong to the specified tenant');

        $otherTenant = Tenant::factory()->create();
        $otherTheme = ComponentTheme::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->service->validateTenantAccess($otherTheme, $this->tenant->id);
    }

    public function test_allows_access_to_own_themes(): void
    {
        $this->assertTrue($this->service->validateTenantAccess($this->defaultTheme, $this->tenant->id));
    }

    public function test_generates_comprehensive_theme_preview(): void
    {
        $preview = $this->service->generateThemePreview($this->defaultTheme);

        $this->assertArrayHasKey('html', $preview);
        $this->assertArrayHasKey('css', $preview);
        $this->assertArrayHasKey('components', $preview);
        $this->assertArrayHasKey('accessibility_report', $preview);
        $this->assertStringContainsString('Theme Preview', $preview['html']);
        $this->assertStringContainsString('--color-primary', $preview['css']);
        $this->assertArrayHasKey('hero', $preview['components']);
        $this->assertArrayHasKey('form', $preview['components']);
        $this->assertArrayHasKey('button', $preview['components']);
        $this->assertArrayHasKey('card', $preview['components']);
        $this->assertIsArray($preview['accessibility_report']);
    }

    public function test_compiles_theme_to_optimized_css(): void
    {
        $css = $this->service->compileThemeCss($this->defaultTheme);

        $this->assertStringContainsString(':root {', $css);
        $this->assertStringContainsString('--color-primary: #007bff', $css);
        $this->assertStringContainsString('/* Responsive Styles */', $css);
        $this->assertStringContainsString('/* Component Styles */', $css);
        $this->assertStringContainsString('.component-button', $css);
    }

    public function test_creates_theme_backup(): void
    {
        Storage::fake('local');

        $backupPath = $this->service->backupTheme($this->defaultTheme);

        $this->assertTrue(Storage::disk('local')->exists($backupPath));

        $backupData = json_decode(Storage::disk('local')->get($backupPath), true);
        $this->assertArrayHasKey('theme', $backupData);
        $this->assertArrayHasKey('created_at', $backupData);
        $this->assertArrayHasKey('version', $backupData);
        $this->assertEquals($this->defaultTheme->id, $backupData['theme']['id']);
    }

    public function test_restores_theme_from_backup(): void
    {
        Storage::fake('local');

        // Create backup
        $backupPath = $this->service->backupTheme($this->defaultTheme);

        // Restore theme
        $restoredTheme = $this->service->restoreTheme($backupPath, $this->tenant->id);

        $this->assertStringContainsString('Restored', $restoredTheme->name);
        $this->assertEquals($this->defaultTheme->config, $restoredTheme->config);
        $this->assertFalse($restoredTheme->is_default);
        $this->assertEquals($this->tenant->id, $restoredTheme->tenant_id);
    }

    public function test_gets_default_theme_for_tenant(): void
    {
        $defaultTheme = $this->service->getDefaultTheme($this->tenant->id);

        $this->assertEquals($this->defaultTheme->id, $defaultTheme->id);
        $this->assertTrue($defaultTheme->is_default);
    }

    public function test_sets_theme_as_default(): void
    {
        $newTheme = ComponentTheme::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_default' => false,
        ]);

        $result = $this->service->setAsDefault($newTheme);

        $this->assertTrue($result);

        // Verify old default is no longer default
        $this->defaultTheme->refresh();
        $this->assertFalse($this->defaultTheme->is_default);

        // Verify new theme is default
        $newTheme->refresh();
        $this->assertTrue($newTheme->is_default);
    }

    public function test_calculates_color_contrast_correctly(): void
    {
        // Use reflection to test protected method
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateColorContrast');
        $method->setAccessible(true);

        // Black on white should have high contrast
        $contrast = $method->invoke($this->service, '#000000', '#ffffff');
        $this->assertGreaterThan(20, $contrast);

        // Similar colors should have low contrast
        $contrast = $method->invoke($this->service, '#ffffff', '#f0f0f0');
        $this->assertLessThan(2, $contrast);
    }

    public function test_converts_hex_colors_to_rgb_correctly(): void
    {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('hexToRgb');
        $method->setAccessible(true);

        $rgb = $method->invoke($this->service, '#ff0000');
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0], $rgb);

        $rgb = $method->invoke($this->service, '#f00'); // Short hex
        $this->assertEquals(['r' => 255, 'g' => 0, 'b' => 0], $rgb);
    }
}
