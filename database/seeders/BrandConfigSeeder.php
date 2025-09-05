<?php

namespace Database\Seeders;

use App\Models\BrandConfig;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Seeder for creating comprehensive brand configurations for multi-tenant scenarios
 */
class BrandConfigSeeder extends Seeder
{
    /**
     * Brand configuration templates for different organization types
     */
    private array $brandConfigurationTemplates = [
        'alumni_network_pro' => [
            'name' => 'Alumni Network Pro',
            'type' => 'alumni_network',
            'theme' => 'blue',
            'colors' => [
                'primary' => '#2563EB',
                'secondary' => '#64748B',
                'accent' => '#EF4444',
            ],
            'font_family' => 'Inter, sans-serif',
            'industry_keywords' => ['networking', 'career', 'alumni', 'professional'],
        ],
        'talent_hub_solutions' => [
            'name' => 'TalentHub Solutions',
            'type' => 'recruiting_platform',
            'theme' => 'purple',
            'colors' => [
                'primary' => '#7C3AED',
                'secondary' => '#A78BFA',
                'accent' => '#EC4899',
            ],
            'font_family' => 'Poppins, sans-serif',
            'industry_keywords' => ['recruiting', 'talent', 'jobs', 'hr'],
        ],
        'career_bridge_platform' => [
            'name' => 'CareerBridge Platform',
            'type' => 'career_services',
            'theme' => 'green',
            'colors' => [
                'primary' => '#059669',
                'secondary' => '#10B981',
                'accent' => '#F59E0B',
            ],
            'font_family' => 'Roboto, sans-serif',
            'industry_keywords' => ['career', 'bridge', 'services', 'development'],
        ],
        'success_path_alumni' => [
            'name' => 'SuccessPath Alumni',
            'type' => 'university_alumni',
            'theme' => 'red',
            'colors' => [
                'primary' => '#DC2626',
                'secondary' => '#EF4444',
                'accent' => '#F97316',
            ],
            'font_family' => 'Lato, sans-serif',
            'industry_keywords' => ['success', 'path', 'university', 'achievement'],
        ],
        'elite_alumni_alliance' => [
            'name' => 'Elite Alumni Alliance',
            'type' => 'premium_network',
            'theme' => 'dark',
            'colors' => [
                'primary' => '#1E293B',
                'secondary' => '#475569',
                'accent' => '#64748B',
            ],
            'font_family' => 'Playfair Display, serif',
            'industry_keywords' => ['elite', 'alliance', 'premium', 'exclusive'],
        ],
        'tech_alumni_connect' => [
            'name' => 'TechAlumni Connect',
            'type' => 'tech_network',
            'theme' => 'blue',
            'colors' => [
                'primary' => '#0EA5E9',
                'secondary' => '#64748B',
                'accent' => '#F59E0B',
            ],
            'font_family' => 'Source Sans Pro, sans-serif',
            'industry_keywords' => ['tech', 'connect', 'innovation', 'technology'],
        ],
        'global_executive_network' => [
            'name' => 'Global Executive Network',
            'type' => 'executive_network',
            'theme' => 'dark',
            'colors' => [
                'primary' => '#0F172A',
                'secondary' => '#1E293B',
                'accent' => '#059669',
            ],
            'font_family' => 'Crimson Text, serif',
            'industry_keywords' => ['executive', 'global', 'leadership', 'network'],
        ],
        'women_in_business' => [
            'name' => 'Women in Business Network',
            'type' => 'women_in_business',
            'theme' => 'purple',
            'colors' => [
                'primary' => '#BE185D',
                'secondary' => '#EC4899',
                'accent' => '#F59E0B',
            ],
            'font_family' => 'Montserrat, sans-serif',
            'industry_keywords' => ['women', 'business', 'empowerment', 'network'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive brand configurations for multi-tenant scenarios...');

        // Get existing tenants or create sample ones
        $tenants = $this->getOrCreateTenants();

        $totalCreated = [
            'configs' => 0,
            'defaults' => 0,
            'actives' => 0,
        ];

        foreach ($tenants as $tenant) {
            $template = $this->selectBrandTemplate($tenant);

            // Create primary brand config
            $primaryConfig = $this->createBrandConfig($tenant, $template, true);
            $totalCreated['configs']++;
            $totalCreated['defaults']++;
            $totalCreated['actives']++;

            // Create secondary brand config (optional)
            if (fake()->boolean(60)) { // 60% chance of having secondary config
                $secondaryConfig = $this->createBrandConfig($tenant, $template, false);
                $totalCreated['configs']++;

                if ($secondaryConfig->is_active) {
                    $totalCreated['actives']++;
                }
            }

            // Create themed variations
            if (fake()->boolean(30)) { // 30% chance of themed variations
                $variations = $this->createThemedVariations($tenant, $template);
                $totalCreated['configs'] += count($variations);

                foreach ($variations as $variation) {
                    if ($variation->is_active) {
                        $totalCreated['actives']++;
                    }
                }
            }

            $this->command->info("✓ Created brand configs for: {$tenant->name}");
        }

        // Create brand guidelines for all tenants
        $this->createBrandGuidelines($tenants);

        $this->command->info('BrandConfigSeeder completed:');
        $this->command->info("  - Brand configs: {$totalCreated['configs']}");
        $this->command->info("  - Default configs: {$totalCreated['defaults']}");
        $this->command->info("  - Active configs: {$totalCreated['actives']}");
    }

    /**
     * Get existing tenants or create sample ones
     */
    private function getOrCreateTenants()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->info('No tenants found. Creating sample tenants...');

            foreach (array_keys($this->brandConfigurationTemplates) as $templateKey) {
                $tenants->push(\App\Models\Tenant::factory()->create([
                    'name' => $this->brandConfigurationTemplates[$templateKey]['name'],
                ]));
            }
        }

        return $tenants;
    }

    /**
     * Select appropriate brand template based on tenant
     */
    private function selectBrandTemplate($tenant): array
    {
        // Try to match tenant name with template
        foreach ($this->brandConfigurationTemplates as $template) {
            if (stripos($tenant->name, $template['name']) !== false) {
                return $template;
            }
        }

        // Match by keywords in tenant name
        foreach ($this->brandConfigurationTemplates as $template) {
            foreach ($template['industry_keywords'] as $keyword) {
                if (stripos($tenant->name, $keyword) !== false) {
                    return $template;
                }
            }
        }

        // Default to first template
        return reset($this->brandConfigurationTemplates);
    }

    /**
     * Create brand configuration for tenant
     */
    private function createBrandConfig(Tenant $tenant, array $template, bool $isDefault = false): BrandConfig
    {
        $variations = ['light', 'standard', 'bold'];
        $variation = fake()->randomElement($variations);

        $configName = $isDefault
            ? "{$template['name']} - Primary Brand"
            : "{$template['name']} - {$variation} Variant";

        return BrandConfig::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => $configName,
            'primary_color' => $this->adjustColorForVariation($template['colors']['primary'], $variation),
            'secondary_color' => $this->adjustColorForVariation($template['colors']['secondary'], $variation),
            'accent_color' => $this->adjustColorForVariation($template['colors']['accent'], $variation),
            'font_family' => $template['font_family'],
            'heading_font_family' => $variation === 'bold'
                ? $this->getHeadingFontVariation($template['font_family'])
                : $template['font_family'],
            'body_font_family' => $template['font_family'],
            'logo_url' => $this->generateLogoUrl($tenant->name, $template['theme']),
            'favicon_url' => $this->generateFaviconUrl($tenant->name, $template['theme']),
            'custom_css' => $this->generateCustomCss($template['colors'], $template['theme']),
            'font_weights' => $this->getFontWeightsForVariation($variation),
            'brand_colors' => $this->generateBrandColorPalette($template['colors'], $template['theme']),
            'typography_settings' => [
                'heading_sizes' => [
                    'h1' => $variation === 'bold' ? '3.5rem' : '2.5rem',
                    'h2' => $variation === 'bold' ? '2.5rem' : '2rem',
                    'h3' => $variation === 'bold' ? '2rem' : '1.5rem',
                    'h4' => $variation === 'bold' ? '1.5rem' : '1.25rem',
                    'h5' => $variation === 'bold' ? '1.25rem' : '1rem',
                    'h6' => $variation === 'bold' ? '1rem' : '0.875rem',
                ],
                'body_sizes' => [
                    'large' => '1.125rem',
                    'base' => '1rem',
                    'small' => '0.875rem',
                    'xs' => '0.75rem',
                ],
                'line_heights' => [
                    'heading' => $variation === 'light' ? 1.3 : 1.2,
                    'body' => 1.6,
                    'tight' => 1.25,
                ],
            ],
            'spacing_settings' => [
                'base_unit' => $variation === 'bold' ? '0.3125rem' : '0.25rem',
                'scale' => 1.5,
                'spacing_scale' => [
                    '1' => '0.25rem',
                    '2' => '0.5rem',
                    '3' => '0.75rem',
                    '4' => '1rem',
                    '5' => '1.25rem',
                    '6' => '1.5rem',
                    '8' => '2rem',
                    '10' => '2.5rem',
                    '12' => '3rem',
                    '16' => '4rem',
                    '20' => '5rem',
                    '24' => '6rem',
                ],
                'container_max_width' => $variation === 'light' ? '1440px' : '1200px',
                'section_spacing' => $variation === 'bold' ? '4rem' : '3rem',
            ],
            'is_default' => $isDefault,
            'is_active' => $isDefault ? true : fake()->boolean(80), // 80% chance for non-default to be active
            'usage_guidelines' => $this->generateUsageGuidelines($template, $variation),
            'created_by' => null,
            'updated_by' => null,
        ]);
    }

    /**
     * Create themed variations of brand config
     */
    private function createThemedVariations(Tenant $tenant, array $template): array
    {
        $variations = [];
        $themes = ['minimal', 'vibrant', 'classic'];

        foreach ($themes as $theme) {
            $variations[] = BrandConfig::factory()->create([
                'tenant_id' => $tenant->id,
                'name' => "{$template['name']} - {$theme} Theme",
                'primary_color' => $this->adjustColorForTheme($template['colors']['primary'], $theme),
                'secondary_color' => $this->adjustColorForTheme($template['colors']['secondary'], $theme),
                'accent_color' => $this->adjustColorForTheme($template['colors']['accent'], $theme),
                'font_family' => $template['font_family'],
                'is_default' => false,
                'is_active' => fake()->boolean(70), // 70% chance to be active
            ]);
        }

        return $variations;
    }

    /**
     * Create brand guidelines for tenants
     */
    private function createBrandGuidelines($tenants): void
    {
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

    private function adjustColorForVariation(string $color, string $variation): string
    {
        // For this implementation, we'll return the original color
        // In a real implementation, you might adjust brightness/contrast
        return $color;
    }

    private function adjustColorForTheme(string $color, string $theme): string
    {
        // For this implementation, we'll return the original color
        // In a real implementation, you might adjust saturation/lightness
        return $color;
    }

    private function getHeadingFontVariation(string $fontFamily): string
    {
        $boldVariants = [
            'Inter, sans-serif' => 'Inter:wght@600;700;800',
            'Poppins, sans-serif' => 'Poppins:wght@600;700;800',
            'Roboto, sans-serif' => 'Roboto:wght@500;700;900',
            'Lato, sans-serif' => 'Lato:wght@700;900',
            'Playfair Display, serif' => 'Playfair Display:wght@600;700;900',
        ];

        return $boldVariants[$fontFamily] ?? $fontFamily;
    }

    private function getFontWeightsForVariation(string $variation): array
    {
        return match ($variation) {
            'light' => [300, 400, 500, 600],
            'bold' => [400, 500, 600, 700, 800],
            default => [300, 400, 500, 600, 700],
        };
    }

    private function generateLogoUrl(string $brandName, string $theme): string
    {
        $slug = Str::slug($brandName);
        $themeParam = $theme === 'dark' ? 'FFFFFF' : '2563EB';
        return "https://via.placeholder.com/200x80/{$themeParam}/FFFFFF?text=" . urlencode(strtoupper(substr($brandName, 0, 4)));
    }

    private function generateFaviconUrl(string $brandName, string $theme): string
    {
        $brandCode = strtoupper(substr($brandName, 0, 2));
        $themeParam = $theme === 'dark' ? 'FFFFFF' : '2563EB';
        return "https://via.placeholder.com/32x32/{$themeParam}/FFFFFF?text={$brandCode}";
    }

    private function generateCustomCss(array $colors, string $theme): string
    {
        $css = "/* Custom Brand CSS for {$theme} theme */\n";
        $css .= ":root {\n";
        $css .= "  --brand-primary: {$colors['primary']};\n";
        $css .= "  --brand-secondary: {$colors['secondary']};\n";
        $css .= "  --brand-accent: {$colors['accent']};\n";
        $css .= "  --brand-success: #10B981;\n";
        $css .= "  --brand-warning: #F59E0B;\n";
        $css .= "  --brand-error: #EF4444;\n";
        $css .= "  --brand-info: #3B82F6;\n";

        if ($theme === 'dark') {
            $css .= "  --brand-text: #FFFFFF;\n";
            $css .= "  --brand-background: #0F172A;\n";
        } else {
            $css .= "  --brand-text: #1E293B;\n";
            $css .= "  --brand-background: #FFFFFF;\n";
        }

        $css .= "}\n\n";

        $css .= "/* Button Styles */\n";
        $css .= ".btn-primary {\n";
        $css .= "  background-color: var(--brand-primary);\n";
        $css .= "  color: white;\n";
        $css .= "  border: none;\n";
        $css .= "  padding: 12px 24px;\n";
        $css .= "  border-radius: 6px;\n";
        $css .= "  font-weight: 500;\n";
        $css .= "  transition: all 0.3s ease;\n";
        $css .= "}\n";

        return $css;
    }

    private function generateBrandColorPalette(array $colors, string $theme): array
    {
        $palette = [];

        // Core brand colors
        $palette['primary'] = [
            'name' => 'Primary Color',
            'value' => $colors['primary'],
            'usage' => 'Main brand elements, CTAs, links',
            'contrast' => 'high',
        ];

        $palette['secondary'] = [
            'name' => 'Secondary Color',
            'value' => $colors['secondary'],
            'usage' => 'Supporting elements, navigation',
            'contrast' => 'medium',
        ];

        $palette['accent'] = [
            'name' => 'Accent Color',
            'value' => $colors['accent'],
            'usage' => 'Highlights, important CTAs',
            'contrast' => 'high',
        ];

        // Add semantic colors
        $semanticColors = [
            'success' => ['name' => 'Success Color', 'value' => '#10B981', 'usage' => 'Success states, confirmations'],
            'warning' => ['name' => 'Warning Color', 'value' => '#F59E0B', 'usage' => 'Warning states, cautions'],
            'error' => ['name' => 'Error Color', 'value' => '#EF4444', 'usage' => 'Error states, destructive actions'],
            'info' => ['name' => 'Info Color', 'value' => '#3B82F6', 'usage' => 'Information, help messages'],
            'neutral' => ['name' => 'Neutral Color', 'value' => '#64748B', 'usage' => 'Text, borders, backgrounds'],
        ];

        foreach ($semanticColors as $key => $colorData) {
            $palette[$key] = $colorData;
        }

        return $palette;
    }

    private function generateUsageGuidelines(array $template, string $variation): string
    {
        $guidelines = [
            "Use {$template['name']} brand colors consistently across all digital assets.",
            "Maintain minimum contrast ratios of 4.5:1 for WCAG AAA compliance.",
            "{$template['font_family']} should be used as the primary font family.",
            "Logo should maintain minimum 1.5x logo height clear space around its perimeter.",
            "Limit accent color usage to 10% of visual elements to maintain brand hierarchy.",
        ];

        if ($variation === 'light') {
            $guidelines[] = "Maintain adequate white space to ensure readability with light styling.";
        } elseif ($variation === 'bold') {
            $guidelines[] = "Avoid overusing heavy font weights - reserve for headings only.";
        }

        return implode(' ', $guidelines);
    }

    /**
     * Create brand configs for a specific tenant
     */
    public function runForTenant(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->command->info("Creating brand configs for tenant: {$tenant->name}");

        $template = $this->selectBrandTemplate($tenant);

        // Create primary config
        $this->createBrandConfig($tenant, $template, true);

        // Create secondary config (if tenant is large enough)
        if (DB::table('landing_pages')->where('tenant_id', $tenantId)->count() > 10) {
            $this->createBrandConfig($tenant, $template, false);
        }

        $this->command->info("Brand configs created for tenant: {$tenant->name}");
    }

    /**
     * Create only default brand configs for all tenants
     */
    public function runDefaults(): void
    {
        $this->command->info('Creating default brand configurations for all tenants...');

        $tenants = $this->getOrCreateTenants();
        $created = 0;

        foreach ($tenants as $tenant) {
            if (!BrandConfig::where('tenant_id', $tenant->id)->where('is_default', true)->exists()) {
                $template = $this->selectBrandTemplate($tenant);
                $this->createBrandConfig($tenant, $template, true);

                $created++;
                $this->command->info("✓ Created default brand config for: {$tenant->name}");
            }
        }

        $this->command->info("Default brand configs created: {$created}");
    }

    /**
     * Create high-performing brand configs
     */
    public function runHighPerforming(): void
    {
        $this->command->info('Creating high-performing brand configurations...');

        $tenants = $this->getOrCreateTenants();
        $created = 0;

        foreach ($tenants as $tenant) {
            // Check if tenant has many landing pages (indicating high activity)
            $landingPageCount = DB::table('landing_pages')->where('tenant_id', $tenant->id)->count();

            if ($landingPageCount > 15) {
                $template = $this->selectBrandTemplate($tenant);

                // Create multiple high-performing configs
                for ($i = 0; $i < 3; $i++) {
                    $this->createBrandConfig($tenant, $template, false);
                    $created++;
                }

                $this->command->info("✓ Created high-performing brand configs for: {$tenant->name}");
            }
        }

        $this->command->info("High-performing brand configs created: {$created}");
    }
}