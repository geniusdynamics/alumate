<?php

namespace Database\Factories;

use App\Models\BrandConfig;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandConfig>
 */
class BrandConfigFactory extends Factory
{
    protected $model = BrandConfig::class;

    public function definition(): array
    {
        $brandName = fake()->randomElement([
            'AlumniConnect Pro',
            'CareerHorizons',
            'TalentBridge Solutions',
            'NetworkMasters',
            'SuccessUnleashed',
            'NextGen Leaders',
            'ProConnect Elite',
            'AlumniSuccess Hub',
        ]);

        $colorSchemes = [
            'blue' => ['#2563EB', '#64748B', '#EF4444', '#10B981'],
            'green' => ['#059669', '#10B981', '#F59E0B', '#EF4444'],
            'purple' => ['#7C3AED', '#A78BFA', '#EC4899', '#F59E0B'],
            'red' => ['#DC2626', '#EF4444', '#F59E0B', '#8B5CF6'],
            'dark' => ['#1E293B', '#475569', '#64748B', '#EF4444'],
        ];

        $colorScheme = fake()->randomElement(array_keys($colorSchemes));
        $colors = $colorSchemes[$colorScheme];

        return [
            'tenant_id' => Tenant::factory(),
            'name' => "{$brandName} - Primary Brand",
            'primary_color' => $colors[0],
            'secondary_color' => $colors[1],
            'accent_color' => $colors[2],
            'font_family' => fake()->randomElement([
                'Inter, sans-serif',
                'Poppins, sans-serif',
                'Roboto, sans-serif',
                'Open Sans, sans-serif',
                'Lato, sans-serif',
                'Playfair Display, serif',
                'Crimson Text, serif',
                'Merriweather, serif',
            ]),
            'heading_font_family' => fake()->randomElement([
                'Inter, sans-serif',
                'Poppins, sans-serif',
                'Caladea, serif',
                'Lora, serif',
            ]),
            'body_font_family' => fake()->randomElement([
                'Inter, sans-serif',
                'Roboto, sans-serif',
                'Source Sans Pro, sans-serif',
            ]),
            'logo_url' => 'https://via.placeholder.com/200x80/' .
                         str_replace('#', '', $colors[0]) . '/FFFFFF?text=' .
                         urlencode(strtoupper(substr($brandName, 0, 3))),
            'favicon_url' => 'https://via.placeholder.com/32x32/' .
                           str_replace('#', '', $colors[0]) . '/FFFFFF?text=' .
                           strtoupper(substr($brandName, 0, 1)),
            'custom_css' => $this->generateDefaultCss($colors),
            'font_weights' => fake()->randomElement([
                [400, 500, 600],
                [300, 400, 500, 600],
                [400, 600, 700],
                [200, 400, 600, 800],
            ]),
            'brand_colors' => $this->generateBrandColors($colors, $colorScheme),
            'typography_settings' => [
                'heading_sizes' => [
                    'h1' => '2.5rem',
                    'h2' => '2rem',
                    'h3' => '1.5rem',
                    'h4' => '1.25rem',
                    'h5' => '1rem',
                    'h6' => '0.875rem',
                ],
                'body_sizes' => [
                    'large' => '1.125rem',
                    'base' => '1rem',
                    'small' => '0.875rem',
                    'xs' => '0.75rem',
                ],
                'line_heights' => [
                    'heading' => 1.2,
                    'body' => 1.6,
                    'tight' => 1.25,
                ],
            ],
            'spacing_settings' => [
                'base_unit' => '0.25rem',
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
                'container_max_width' => '1200px',
                'section_spacing' => '3rem',
            ],
            'is_default' => fake()->boolean(30), // 30% chance of being default
            'is_active' => true,
            'usage_guidelines' => fake()->randomElement([
                'Use primary color for main CTAs and brand elements. Secondary for supporting elements. Accent for highlights only.',
                'Maintain consistent color contrast ratios. Primary color should have WCAG AAA compliance.',
                'Limit accent color usage to 10% of visual elements. Reserve for important calls-to-action.',
                'Use brand font weights consistently: headings should use 600+ weight, body text 400 weight.',
                'Logo should maintain minimum 8% white space around its perimeter for proper brand recognition.',
            ]),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    private function generateDefaultCss(array $colors): string
    {
        return "
/* Custom Brand CSS */
:root {
  --brand-primary: {$colors[0]};
  --brand-secondary: {$colors[1]};
  --brand-accent: {$colors[2]};
  --brand-success: #10B981;
  --brand-warning: #F59E0B;
  --brand-error: #EF4444;
  --brand-info: #3B82F6;
}

/* Button Styles */
.btn-primary {
  background-color: var(--brand-primary);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background-color: var(--brand-secondary);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
  background-color: var(--brand-secondary);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background-color: var(--brand-primary);
}

.btn-accent {
  background-color: var(--brand-accent);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 6px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-accent:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

/* Form Styles */
.form-input {
  border: 2px solid var(--brand-secondary);
  border-radius: 6px;
  padding: 12px 16px;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input:focus + .form-label,
.form-input:not(:placeholder-shown) + .form-label {
  transform: translateY(-1.5rem);
  font-size: 0.75rem;
  color: var(--brand-primary);
}

/* Accent Elements */
.accent-text {
  color: var(--brand-accent);
  font-weight: 600;
}

.accent-bg {
  background-color: var(--brand-accent);
  color: white;
}

/* Sections */
.section-hero {
  background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
}

.section-cta {
  background-color: var(--brand-accent);
  color: white;
}";
    }

    private function generateBrandColors(array $colors, string $colorScheme): array
    {
        $brandColors = [
            'primary' => [
                'name' => 'Primary Color',
                'value' => $colors[0],
                'usage' => 'Main brand elements, CTAs, links',
                'contrast' => 'high',
            ],
            'secondary' => [
                'name' => 'Secondary Color',
                'value' => $colors[1],
                'usage' => 'Supporting elements, navigation',
                'contrast' => 'medium',
            ],
            'accent' => [
                'name' => 'Accent Color',
                'value' => $colors[2],
                'usage' => 'Highlights, important CTAs',
                'contrast' => 'high',
            ],
            'success' => [
                'name' => 'Success Color',
                'value' => '#10B981',
                'usage' => 'Success messages, positive actions',
                'contrast' => 'high',
            ],
            'warning' => [
                'name' => 'Warning Color',
                'value' => '#F59E0B',
                'usage' => 'Warning messages, caution states',
                'contrast' => 'high',
            ],
            'error' => [
                'name' => 'Error Color',
                'value' => '#EF4444',
                'usage' => 'Error messages, destructive actions',
                'contrast' => 'high',
            ],
            'info' => [
                'name' => 'Info Color',
                'value' => '#3B82F6',
                'usage' => 'Information, help messages',
                'contrast' => 'high',
            ],
            'neutral' => [
                'name' => 'Neutral Color',
                'value' => '#64748B',
                'usage' => 'Text, borders, backgrounds',
                'contrast' => 'low',
            ],
        ];

        // For dark themes, adjust brand colors
        if ($colorScheme === 'dark') {
            $brandColors['primary']['contrast'] = 'low';
            $brandColors['secondary']['contrast'] = 'low';
            $brandColors['text'] = [
                'name' => 'Text Color',
                'value' => '#FFFFFF',
                'usage' => 'Primary text on dark backgrounds',
                'contrast' => 'high',
            ];
            $brandColors['background'] = [
                'name' => 'Background Color',
                'value' => '#0F172A',
                'usage' => 'Main background on dark themes',
                'contrast' => 'low',
            ];
        }

        return $brandColors;
    }

    // State methods
    public function forTenant($tenantId): static
    {
        return $this->state(['tenant_id' => $tenantId]);
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function notDefault(): static
    {
        return $this->state(['is_default' => false]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function blueTheme(): static
    {
        return $this->state([
            'primary_color' => '#2563EB',
            'secondary_color' => '#64748B',
            'accent_color' => '#EF4444',
        ]);
    }

    public function greenTheme(): static
    {
        return $this->state([
            'primary_color' => '#059669',
            'secondary_color' => '#10B981',
            'accent_color' => '#F59E0B',
        ]);
    }

    public function purpleTheme(): static
    {
        return $this->state([
            'primary_color' => '#7C3AED',
            'secondary_color' => '#A78BFA',
            'accent_color' => '#EC4899',
        ]);
    }

    public function darkTheme(): static
    {
        return $this->state([
            'primary_color' => '#1E293B',
            'secondary_color' => '#475569',
            'accent_color' => '#64748B',
        ]);
    }

    public function withCustomColors(array $colors): static
    {
        $state = [];

        if (isset($colors['primary'])) {
            $state['primary_color'] = $colors['primary'];
        }
        if (isset($colors['secondary'])) {
            $state['secondary_color'] = $colors['secondary'];
        }
        if (isset($colors['accent'])) {
            $state['accent_color'] = $colors['accent'];
        }

        return $this->state($state);
    }

    public function withCustomFont(string $fontFamily): static
    {
        return $this->state([
            'font_family' => $fontFamily,
            'heading_font_family' => $fontFamily,
        ]);
    }

    public function serif(): static
    {
        return $this->state([
            'font_family' => 'Playfair Display, serif',
            'heading_font_family' => 'Playfair Display, serif',
            'body_font_family' => 'Source Sans Pro, sans-serif',
        ]);
    }

    public function sansSerif(): static
    {
        return $this->state([
            'font_family' => 'Inter, sans-serif',
            'heading_font_family' => 'Inter, sans-serif',
            'body_font_family' => 'Inter, sans-serif',
        ]);
    }

    public function complete(): static
    {
        return $this->state([
            'logo_url' => 'https://via.placeholder.com/200x80/2563EB/FFFFFF?text=LOGO',
            'favicon_url' => 'https://via.placeholder.com/32x32/2563EB/FFFFFF?text=F',
        ]);
    }
}