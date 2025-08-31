<?php

namespace Tests\Unit;

use App\Models\ComponentTheme;
use App\Services\GrapeJSThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrapeJSThemeServiceTest extends TestCase
{
    use RefreshDatabase;

    private GrapeJSThemeService $service;
    private ComponentTheme $theme;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new GrapeJSThemeService();
        
        // Create a tenant first
        $tenant = \App\Models\Tenant::factory()->create();
        
        $this->theme = ComponentTheme::factory()->create([
            'tenant_id' => $tenant->id,
            'config' => [
                'colors' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                    'accent' => '#28a745',
                    'background' => '#ffffff',
                    'text' => '#333333'
                ],
                'typography' => [
                    'font_family' => 'Arial, sans-serif',
                    'heading_font' => 'Georgia, serif',
                    'font_sizes' => [
                        'base' => '16px',
                        'heading' => '2rem'
                    ],
                    'line_height' => 1.6
                ],
                'spacing' => [
                    'base' => '1rem',
                    'small' => '0.5rem',
                    'large' => '2rem',
                    'section_padding' => '1.5rem'
                ],
                'borders' => [
                    'radius' => '4px',
                    'width' => '1px'
                ],
                'animations' => [
                    'duration' => '0.3s',
                    'easing' => 'ease'
                ]
            ]
        ]);
    }

    public function test_converts_theme_to_grapejs_style_manager(): void
    {
        $styleManager = $this->service->convertToGrapeJSStyleManager($this->theme);

        $this->assertIsArray($styleManager);
        $this->assertArrayHasKey('sectors', $styleManager);
        $this->assertIsArray($styleManager['sectors']);
        
        // Check that all expected sectors are present
        $sectorNames = array_column($styleManager['sectors'], 'name');
        $this->assertContains('Colors', $sectorNames);
        $this->assertContains('Typography', $sectorNames);
        $this->assertContains('Spacing', $sectorNames);
        $this->assertContains('Layout', $sectorNames);
        $this->assertContains('Borders & Effects', $sectorNames);
    }

    public function test_generates_css_variables(): void
    {
        $variables = $this->service->generateGrapeJSCssVariables($this->theme);

        $this->assertIsArray($variables);
        
        // Check color variables
        $this->assertArrayHasKey('--theme-color-primary', $variables);
        $this->assertEquals('#007bff', $variables['--theme-color-primary']);
        
        // Check typography variables
        $this->assertArrayHasKey('--theme-font-family', $variables);
        $this->assertEquals('Arial, sans-serif', $variables['--theme-font-family']);
        
        // Check spacing variables
        $this->assertArrayHasKey('--theme-spacing-base', $variables);
        $this->assertEquals('1rem', $variables['--theme-spacing-base']);
    }

    public function test_converts_from_grapejs_styles(): void
    {
        $grapeJSStyles = [
            '--theme-color-primary' => '#ff0000',
            '--theme-color-secondary' => '#00ff00',
            '--theme-font-family' => 'Helvetica, sans-serif',
            '--theme-font-size-base' => '18px',
            '--theme-spacing-base' => '1.5rem',
            '--theme-border-radius' => '8px',
            '--theme-animation-duration' => '0.5s'
        ];

        $config = $this->service->convertFromGrapeJSStyles($grapeJSStyles);

        $this->assertIsArray($config);
        
        // Check colors conversion
        $this->assertArrayHasKey('colors', $config);
        $this->assertEquals('#ff0000', $config['colors']['primary']);
        $this->assertEquals('#00ff00', $config['colors']['secondary']);
        
        // Check typography conversion
        $this->assertArrayHasKey('typography', $config);
        $this->assertEquals('Helvetica, sans-serif', $config['typography']['font_family']);
        $this->assertEquals('18px', $config['typography']['font_sizes']['base']);
        
        // Check spacing conversion
        $this->assertArrayHasKey('spacing', $config);
        $this->assertEquals('1.5rem', $config['spacing']['base']);
        
        // Check borders conversion
        $this->assertArrayHasKey('borders', $config);
        $this->assertEquals('8px', $config['borders']['radius']);
        
        // Check animations conversion
        $this->assertArrayHasKey('animations', $config);
        $this->assertEquals('0.5s', $config['animations']['duration']);
    }

    public function test_generates_tailwind_mappings(): void
    {
        $mappings = $this->service->generateTailwindMappings($this->theme);

        $this->assertIsArray($mappings);
        
        // Check color mappings
        $this->assertArrayHasKey('text-theme-primary', $mappings);
        $this->assertEquals('color: #007bff', $mappings['text-theme-primary']);
        
        $this->assertArrayHasKey('bg-theme-primary', $mappings);
        $this->assertEquals('background-color: #007bff', $mappings['bg-theme-primary']);
        
        // Check spacing mappings
        $this->assertArrayHasKey('p-theme-base', $mappings);
        $this->assertEquals('padding: 1rem', $mappings['p-theme-base']);
        
        // Check typography mappings
        $this->assertArrayHasKey('font-theme', $mappings);
        $this->assertEquals('font-family: Arial, sans-serif', $mappings['font-theme']);
    }

    public function test_exports_theme_for_grapejs(): void
    {
        $export = $this->service->exportForGrapeJS($this->theme);

        $this->assertIsArray($export);
        
        // Check required keys
        $this->assertArrayHasKey('id', $export);
        $this->assertArrayHasKey('name', $export);
        $this->assertArrayHasKey('slug', $export);
        $this->assertArrayHasKey('isDefault', $export);
        $this->assertArrayHasKey('styleManager', $export);
        $this->assertArrayHasKey('cssVariables', $export);
        $this->assertArrayHasKey('tailwindMappings', $export);
        $this->assertArrayHasKey('css', $export);
        $this->assertArrayHasKey('accessibility', $export);
        $this->assertArrayHasKey('preview', $export);
        
        // Check data types
        $this->assertEquals($this->theme->id, $export['id']);
        $this->assertEquals($this->theme->name, $export['name']);
        $this->assertEquals($this->theme->is_default, $export['isDefault']);
        $this->assertIsArray($export['styleManager']);
        $this->assertIsArray($export['cssVariables']);
        $this->assertIsArray($export['tailwindMappings']);
        $this->assertIsString($export['css']);
        $this->assertIsArray($export['accessibility']);
        $this->assertIsString($export['preview']);
    }

    public function test_imports_theme_from_grapejs(): void
    {
        $grapeJSConfig = [
            'name' => 'Imported Theme',
            'styles' => [
                '--theme-color-primary' => '#ff6b6b',
                '--theme-color-background' => '#ffffff',
                '--theme-font-family' => 'Inter, sans-serif',
                '--theme-spacing-base' => '1rem'
            ]
        ];

        $theme = $this->service->importFromGrapeJS($grapeJSConfig, 'tenant-123');

        $this->assertInstanceOf(ComponentTheme::class, $theme);
        $this->assertEquals('Imported Theme', $theme->name);
        $this->assertEquals('imported-theme', $theme->slug);
        $this->assertEquals('tenant-123', $theme->tenant_id);
        $this->assertFalse($theme->is_default);
        
        // Check converted config
        $config = $theme->config;
        $this->assertEquals('#ff6b6b', $config['colors']['primary']);
        $this->assertEquals('#ffffff', $config['colors']['background']);
        $this->assertEquals('Inter, sans-serif', $config['typography']['font_family']);
        $this->assertEquals('1rem', $config['spacing']['base']);
    }

    public function test_validates_grapejs_compatibility(): void
    {
        // Test compatible theme
        $issues = $this->service->validateGrapeJSCompatibility($this->theme);
        $this->assertIsArray($issues);
        $this->assertEmpty($issues); // Should have no issues
        
        // Test incompatible theme (missing required colors)
        $incompatibleTheme = ComponentTheme::factory()->create([
            'config' => [
                'colors' => [
                    'primary' => '#007bff'
                    // Missing background and text colors
                ],
                'typography' => [
                    // Missing font_family
                ],
                'spacing' => [
                    // Missing base spacing
                ]
            ]
        ]);
        
        $issues = $this->service->validateGrapeJSCompatibility($incompatibleTheme);
        $this->assertNotEmpty($issues);
        $this->assertContains('Missing required color: background', $issues);
        $this->assertContains('Missing required color: text', $issues);
        $this->assertContains('Missing font family configuration', $issues);
        $this->assertContains('Missing base spacing configuration', $issues);
    }

    public function test_caches_theme_data(): void
    {
        // First call should generate and cache data
        $data1 = $this->service->getCachedThemeData($this->theme);
        
        // Second call should return cached data
        $data2 = $this->service->getCachedThemeData($this->theme);
        
        $this->assertEquals($data1, $data2);
        $this->assertArrayHasKey('id', $data1);
        $this->assertEquals($this->theme->id, $data1['id']);
    }

    public function test_clears_theme_cache(): void
    {
        // Cache some data
        $this->service->getCachedThemeData($this->theme);
        
        // Clear cache
        $this->service->clearThemeCache($this->theme);
        
        // This should work without errors
        $this->assertTrue(true);
    }

    public function test_gets_themes_for_grapejs(): void
    {
        // Create multiple themes
        ComponentTheme::factory()->count(3)->create([
            'tenant_id' => $this->theme->tenant_id
        ]);
        
        $themes = $this->service->getThemesForGrapeJS($this->theme->tenant_id);
        
        $this->assertCount(4, $themes); // 3 + 1 from setUp
        
        // Check that each theme has the required GrapeJS format
        foreach ($themes as $theme) {
            $this->assertArrayHasKey('id', $theme);
            $this->assertArrayHasKey('name', $theme);
            $this->assertArrayHasKey('styleManager', $theme);
            $this->assertArrayHasKey('cssVariables', $theme);
        }
    }

    public function test_color_properties_generation(): void
    {
        $colors = [
            'primary' => '#007bff',
            'secondary' => '#6c757d',
            'accent' => '#28a745'
        ];
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildColorProperties');
        $method->setAccessible(true);
        
        $properties = $method->invoke($this->service, $colors);
        
        $this->assertIsArray($properties);
        $this->assertCount(3, $properties);
        
        foreach ($properties as $property) {
            $this->assertArrayHasKey('name', $property);
            $this->assertArrayHasKey('property', $property);
            $this->assertArrayHasKey('type', $property);
            $this->assertEquals('color', $property['type']);
            $this->assertEquals('color', $property['property']);
        }
    }

    public function test_typography_properties_generation(): void
    {
        $typography = [
            'font_family' => 'Arial, sans-serif',
            'font_sizes' => [
                'base' => '16px',
                'heading' => '2rem'
            ],
            'line_height' => 1.6
        ];
        
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('buildTypographyProperties');
        $method->setAccessible(true);
        
        $properties = $method->invoke($this->service, $typography);
        
        $this->assertIsArray($properties);
        $this->assertNotEmpty($properties);
        
        // Check font family property
        $fontFamilyProperty = collect($properties)->firstWhere('name', 'Font Family');
        $this->assertNotNull($fontFamilyProperty);
        $this->assertEquals('font-family', $fontFamilyProperty['property']);
        $this->assertEquals('select', $fontFamilyProperty['type']);
        
        // Check line height property
        $lineHeightProperty = collect($properties)->firstWhere('name', 'Line Height');
        $this->assertNotNull($lineHeightProperty);
        $this->assertEquals('line-height', $lineHeightProperty['property']);
        $this->assertEquals('slider', $lineHeightProperty['type']);
    }
}