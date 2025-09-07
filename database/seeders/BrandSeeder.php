<?php

namespace Database\Seeders;

use App\Models\BrandColor;
use App\Models\BrandConfig;
use App\Models\BrandFont;
use App\Models\BrandLogo;
use App\Models\BrandTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating comprehensive brand data for multi-tenant scenarios
 */
class BrandSeeder extends Seeder
{
    /**
     * Sample brand configurations for different organization types
     */
    private array $brandConfigurations = [
        [
            'tenant_name' => 'Alumni Network Pro',
            'type' => 'alumni_network',
            'primary_color' => '#2563EB',
            'secondary_color' => '#64748B',
            'accent_color' => '#EF4444',
            'font_family' => 'Inter',
            'logo_types' => ['primary', 'favicon', 'email'],
            'colors_needed' => ['primary', 'secondary', 'accent', 'success', 'error', 'text'],
            'fonts_needed' => ['sans-serif', 'serif'],
        ],
        [
            'tenant_name' => 'TalentHub Solutions',
            'type' => 'recruiting_platform',
            'primary_color' => '#7C3AED',
            'secondary_color' => '#A78BFA',
            'accent_color' => '#EC4899',
            'font_family' => 'Poppins',
            'logo_types' => ['primary', 'secondary', 'social'],
            'colors_needed' => ['primary', 'secondary', 'accent', 'success', 'warning', 'error', 'info'],
            'fonts_needed' => ['sans-serif', 'monospace'],
        ],
        [
            'tenant_name' => 'CareerBridge Platform',
            'type' => 'career_services',
            'primary_color' => '#059669',
            'secondary_color' => '#10B981',
            'accent_color' => '#F59E0B',
            'font_family' => 'Roboto',
            'logo_types' => ['primary', 'footer', 'email'],
            'colors_needed' => ['primary', 'secondary', 'accent', 'neutral', 'text', 'background'],
            'fonts_needed' => ['sans-serif', 'sans-serif', 'serif'],
        ],
        [
            'tenant_name' => 'SuccessPath Alumni',
            'type' => 'university_alumni',
            'primary_color' => '#DC2626',
            'secondary_color' => '#EF4444',
            'accent_color' => '#F97316',
            'font_family' => 'Lato',
            'logo_types' => ['primary', 'secondary', 'favicon'],
            'colors_needed' => ['primary', 'secondary', 'accent', 'neutral', 'success', 'error'],
            'fonts_needed' => ['sans-serif', 'serif'],
        ],
        [
            'tenant_name' => 'Elite Alumni Alliance',
            'type' => 'premium_network',
            'primary_color' => '#1E293B',
            'secondary_color' => '#475569',
            'accent_color' => '#0F172A',
            'font_family' => 'Playfair Display',
            'logo_types' => ['primary', 'footer', 'social'],
            'colors_needed' => ['primary', 'secondary', 'accent', 'neutral', 'text', 'background'],
            'fonts_needed' => ['serif', 'sans-serif', 'monospace'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive brand data for multi-tenant scenarios...');

        $totalCreated = [
            'configs' => 0,
            'logos' => 0,
            'colors' => 0,
            'fonts' => 0,
            'templates' => 0,
        ];

        foreach ($this->brandConfigurations as $brandConfig) {
            $tenant = $this->getOrCreateTenant($brandConfig['tenant_name']);

            // Create brand config
            $brandConfiguration = $this->createBrandConfig($tenant, $brandConfig);
            $totalCreated['configs']++;

            // Create brand colors
            $colors = $this->createBrandColors($tenant, $brandConfig);
            $totalCreated['colors'] += count($colors);

            // Create brand fonts
            $fonts = $this->createBrandFonts($tenant, $brandConfig);
            $totalCreated['fonts'] += count($fonts);

            // Create brand logos
            $logos = $this->createBrandLogos($tenant, $brandConfig);
            $totalCreated['logos'] += count($logos);

            // Create brand templates
            $templates = $this->createBrandTemplates($tenant, $brandConfig, $colors, $fonts, $logos);
            $totalCreated['templates'] += count($templates);

            $this->command->info("✓ Created brand setup for: {$brandConfig['tenant_name']}");
        }

        // Create brand guidelines for each tenant
        $this->createBrandGuidelines();

        $this->command->info('BrandSeeder completed:');
        $this->command->info("  - Brand configs: {$totalCreated['configs']}");
        $this->command->info("  - Brand colors: {$totalCreated['colors']}");
        $this->command->info("  - Brand fonts: {$totalCreated['fonts']}");
        $this->command->info("  - Brand logos: {$totalCreated['logos']}");
        $this->command->info("  - Brand templates: {$totalCreated['templates']}");
    }

    /**
     * Get or create tenant by name
     */
    private function getOrCreateTenant(string $name): Tenant
    {
        return Tenant::firstOrCreate(
            ['name' => $name],
            [
                'id' => \Str::slug($name),
                'address' => fake()->address(),
                'contact_information' => json_encode([
                    'email' => fake()->companyEmail(),
                    'phone' => fake()->phoneNumber(),
                    'website' => fake()->url(),
                ]),
                'plan' => fake()->randomElement(['basic', 'premium', 'enterprise']),
                'data' => json_encode([
                    'name' => $name,
                    'type' => 'alumni_network',
                    'accreditation' => fake()->words(3, true),
                ]),
            ]
        );
    }

    /**
     * Create brand configuration
     */
    private function createBrandConfig(Tenant $tenant, array $config): BrandConfig
    {
        return BrandConfig::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => "{$tenant->name} - Primary Brand",
            'primary_color' => $config['primary_color'],
            'secondary_color' => $config['secondary_color'],
            'accent_color' => $config['accent_color'],
            'font_family' => $config['font_family'] . ', sans-serif',
            'heading_font_family' => $config['font_family'] . ', sans-serif',
            'body_font_family' => $config['font_family'] . ', sans-serif',
            'logo_url' => $this->generateLogoUrl($tenant->name, 'primary'),
            'favicon_url' => $this->generateLogoUrl($tenant->name, 'favicon'),
            'custom_css' => $this->generateCustomCss($config),
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Create brand colors for tenant
     */
    private function createBrandColors(Tenant $tenant, array $config): array
    {
        $colors = [];
        $colorInstances = [
            'primary' => $config['primary_color'],
            'secondary' => $config['secondary_color'],
            'accent' => $config['accent_color'],
        ];

        // Create core colors
        foreach ($config['colors_needed'] as $colorType) {
            if (isset($colorInstances[$colorType])) {
                $color = $colorInstances[$colorType];
            } else {
                $color = $this->getColorForType($colorType);
            }

            $colors[] = BrandColor::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => ucfirst($colorType) . ' Color',
                'value' => $color,
                'type' => $colorType,
                'usage_guidelines' => $this->getColorGuidelines($colorType),
            ]);
        }

        return $colors;
    }

    /**
     * Create brand fonts for tenant
     */
    private function createBrandFonts(Tenant $tenant, array $config): array
    {
        $fonts = [];
        $fontTypes = $config['fonts_needed'] ?? ['sans-serif'];

        foreach ($fontTypes as $index => $fontType) {
            $isPrimary = $index === 0; // First font is primary

            $fontConfig = $this->getFontConfig($fontType, $isPrimary);

            $fonts[] = BrandFont::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => $fontConfig['name'],
                'family' => $fontConfig['family'],
                'type' => $fontConfig['type'],
                'is_primary' => $isPrimary,
                'weights' => $fontConfig['weights'],
            ]);
        }

        return $fonts;
    }

    /**
     * Create brand logos for tenant
     */
    private function createBrandLogos(Tenant $tenant, array $config): array
    {
        $logos = [];
        $logoTypes = $config['logo_types'] ?? ['primary'];

        foreach ($logoTypes as $logoType) {
            // Determine if this should be primary
            $isPrimary = $logoType === 'primary';

            $logos[] = BrandLogo::factory()
                ->ofType($logoType)
                ->create([
                    'tenant_id' => $tenant->id,
                    'is_primary' => $isPrimary,
                    'optimized' => true,
                ]);
        }

        return $logos;
    }

    /**
     * Create brand templates for tenant
     */
    private function createBrandTemplates(Tenant $tenant, array $config, array $colors, array $fonts, array $logos): array
    {
        $templates = [];

        // Create a few brand templates for each tenant
        $templateConfigs = [
            [
                'name' => 'Primary Brand Template',
                'description' => 'Main brand template for consistent styling',
                'is_default' => true,
            ],
            [
                'name' => 'Secondary Brand Template',
                'description' => 'Alternative brand template for specific campaigns',
                'is_default' => false,
            ],
        ];

        foreach ($templateConfigs as $templateConfig) {
            $primaryFont = collect($fonts)->first(fn($font) => $font->is_primary);
            $secondaryFont = collect($fonts)->first(fn($font) => !$font->is_primary);
            $primaryLogo = collect($logos)->first(fn($logo) => $logo->is_primary);

            $templates[] = BrandTemplate::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => $templateConfig['name'],
                'description' => $templateConfig['description'],
                'primary_font' => $primaryFont?->family,
                'secondary_font' => $secondaryFont?->family,
                'logo_variant' => $primaryLogo ? 'primary' : null,
                'is_default' => $templateConfig['is_default'],
                'usage_count' => $templateConfig['is_default'] ? fake()->numberBetween(50, 200) : fake()->numberBetween(10, 50),
            ]);
        }

        return $templates;
    }

    /**
     * Create brand guidelines for all tenants
     */
    private function createBrandGuidelines(): void
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Skip if guidelines already exist
            if (\App\Models\BrandGuidelines::where('tenant_id', $tenant->id)->exists()) {
                continue;
            }

            \App\Models\BrandGuidelines::factory()->create([
                'tenant_id' => $tenant->id,
                'enforce_color_palette' => true,
                'require_contrast_check' => true,
                'min_contrast_ratio' => 4.5,
                'enforce_font_families' => true,
                'max_heading_size' => 48,
                'max_body_size' => 18,
                'enforce_logo_placement' => true,
                'min_logo_size' => 32,
                'logo_clear_space' => 1.5,
            ]);
        }
    }

    // Helper methods

    private function generateLogoUrl(string $brandName, string $type): string
    {
        $slug = \Str::slug($brandName);

        switch ($type) {
            case 'favicon':
                return "https://via.placeholder.com/32x32/3B82F6/FFFFFF?text=" . urlencode(strtoupper(substr($brandName, 0, 3)));
            default:
                return "https://via.placeholder.com/200x80/ffffff/000000?text=" . urlencode($slug);
        }
    }

    private function generateCustomCss(array $config): string
    {
        return "/* Custom brand CSS for {$config['tenant_name']} */
:root {
  --brand-primary: {$config['primary_color']};
  --brand-secondary: {$config['secondary_color']};
  --brand-accent: {$config['accent_color']};
  --brand-font-family: {$config['font_family']}, sans-serif;
}

.accent-button {
  background-color: var(--brand-primary);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  font-family: var(--brand-font-family);
  font-weight: 500;
  transition: background-color 0.3s ease;
}

.accent-button:hover {
  background-color: var(--brand-accent);
}";
    }

    private function getColorForType(string $type): string
    {
        $colors = [
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'error' => '#EF4444',
            'info' => '#3B82F6',
            'text' => '#1E293B',
            'background' => '#FFFFFF',
            'neutral' => '#64748B',
        ];

        return $colors[$type] ?? '#333333';
    }

    private function getColorGuidelines(string $type): string
    {
        $guidelines = [
            'primary' => 'Use for main CTAs, links, and brand elements',
            'secondary' => 'Use for supporting elements and secondary actions',
            'accent' => 'Use sparingly for highlighting important elements',
            'success' => 'Use only for positive states and confirmations',
            'error' => 'Use only for error states and warnings',
            'neutral' => 'Use for backgrounds, borders, and subtle elements',
        ];

        return $guidelines[$type] ?? 'Use according to design system guidelines';
    }

    private function getFontConfig(string $fontType, bool $isPrimary): array
    {
        $configs = [
            'sans-serif' => [
                'Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins'
            ],
            'serif' => [
                'Playfair Display', 'Crimson Text', 'Merriweather'
            ],
            'monospace' => [
                'Source Code Pro', 'JetBrains Mono', 'Roboto Mono'
            ],
        ];

        $fontFamily = fake()->randomElement($configs[$fontType] ?? $configs['sans-serif']);
        $baseWeights = $fontType === 'serif' ? [400, 700] : [300, 400, 500, 600, 700];

        return [
            'name' => ($isPrimary ? 'Primary ' : '') . ucfirst($fontType) . ' Font',
            'family' => $fontFamily,
            'type' => $fontType === 'sans-serif' ? 'google' : 'system',
            'weights' => $baseWeights,
        ];
    }
}