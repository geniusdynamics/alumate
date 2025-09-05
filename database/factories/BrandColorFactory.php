<?php

namespace Database\Factories;

use App\Models\BrandColor;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandColor>
 */
class BrandColorFactory extends Factory
{
    protected $model = BrandColor::class;

    public function definition(): array
    {
        $colorTypes = [
            'primary' => 'Primary Brand Color',
            'secondary' => 'Secondary Brand Color',
            'accent' => 'Accent Color',
            'neutral' => 'Neutral Color',
            'success' => 'Success Color',
            'warning' => 'Warning Color',
            'error' => 'Error Color',
            'info' => 'Info Color',
            'text' => 'Text Color',
            'background' => 'Background Color',
        ];

        $selectedType = fake()->randomElement(array_keys($colorTypes));
        $baseColor = $this->generateColorForType($selectedType);
        $usageGuidelines = $this->generateUsageGuidelines($selectedType);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $colorTypes[$selectedType],
            'value' => $baseColor,
            'type' => $selectedType,
            'usage_guidelines' => $usageGuidelines,
            'usage_count' => fake()->numberBetween(0, 1000),
            'contrast_ratios' => $this->generateContrastRatios($baseColor),
            'accessibility' => $this->generateAccessibilityInfo($baseColor, $selectedType),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Generate appropriate base color for each type
     */
    private function generateColorForType(string $type): string
    {
        return match ($type) {
            'primary' => fake()->randomElement([
                '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#10B981',
                '#F59E0B', '#EF4444', '#06B6D4', '#84CC16', '#F97316'
            ]),
            'secondary' => fake()->randomElement([
                '#64748B', '#6B7280', '#78716C', '#A1A1AA', '#64748B',
                '#475569', '#94A3B8', '#CBD5E1', '#E2E8F0', '#F8FAFC'
            ]),
            'accent' => fake()->randomElement([
                '#F59E0B', '#EF4444', '#EC4899', '#8B5CF6', '#06B6D4',
                '#84CC16', '#F97316', '#6366F1', '#14B8A6', '#A855F7'
            ]),
            'neutral' => fake()->randomElement([
                '#FFFFFF', '#F8FAFC', '#F1F5F9', '#E2E8F0', '#CBD5E1',
                '#94A3B8', '#64748B', '#475569', '#334155', '#1E293B'
            ]),
            'success' => fake()->randomElement(['#10B981', '#059669', '#0D9488', '#047857']),
            'warning' => fake()->randomElement(['#F59E0B', '#D97706', '#C2410C', '#B45309']),
            'error' => fake()->randomElement(['#EF4444', '#DC2626', '#B91C1C', '#991B1B']),
            'info' => fake()->randomElement(['#3B82F6', '#2563EB', '#1D4ED8', '#1E40AF']),
            'text' => fake()->randomElement([
                '#1E293B', '#334155', '#475569', '#64748B', '#1F2937',
                '#374151', '#4B5563', '#6B7280', '#111827'
            ]),
            'background' => fake()->randomElement([
                '#FFFFFF', '#F8FAFC', '#F1F5F9', '#F9FAFB', '#FAFAFA',
                '#FEFEFE', '#FCFCFC', '#FBFFFD', '#FFFEFB', '#FFFDFD'
            ]),
            default => '#333333',
        };
    }

    /**
     * Generate contrast ratio data
     */
    private function generateContrastRatios(string $color): array
    {
        $commonBackgrounds = ['#FFFFFF', '#F8FAFC', '#000000', '#1E293B'];

        return collect($commonBackgrounds)->map(function ($bgColor) use ($color) {
            $ratio = $this->calculateContrastRatio($color, $bgColor);
            $level = $this->getComplianceLevel($ratio);

            return [
                'background' => $bgColor,
                'ratio' => round($ratio, 2),
                'level' => $level['level'] ?? 'Fail',
                'passes_aa' => $level['passes_aa'] ?? false,
                'passes_aaa' => $level['passes_aaa'] ?? false,
            ];
        })->toArray();
    }

    /**
     * Calculate contrast ratio between two colors
     */
    private function calculateContrastRatio(string $foreground, string $background): float
    {
        $fgLuminance = $this->getRelativeLuminance($foreground);
        $bgLuminance = $this->getRelativeLuminance($background);

        $lighter = max($fgLuminance, $bgLuminance);
        $darker = min($fgLuminance, $bgLuminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Calculate relative luminance for accessibility
     */
    private function getRelativeLuminance(string $hexColor): float
    {
        $hexColor = ltrim($hexColor, '#');

        $r = hexdec(substr($hexColor, 0, 2)) / 255;
        $g = hexdec(substr($hexColor, 2, 2)) / 255;
        $b = hexdec(substr($hexColor, 4, 2)) / 255;

        // Convert to linear RGB
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate relative luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get WCAG compliance level
     */
    private function getComplianceLevel(float $ratio): array
    {
        $passesAA = $ratio >= 4.5;
        $passesAAA = $ratio >= 7.0;
        $passesAA18 = $ratio >= 3.0; // For large text
        $passesAAA18 = $ratio >= 4.5; // For large text

        if ($passesAAA) {
            $level = 'AAA';
        } elseif ($passesAA) {
            $level = 'AA';
        } elseif ($passesAA18) {
            $level = 'AA (18pt+)';
        } elseif ($passesAAA18) {
            $level = 'AAA (18pt+)';
        } else {
            $level = 'Fail';
        }

        return [
            'level' => $level,
            'passes_aa' => $passesAA,
            'passes_aaa' => $passesAAA,
            'passes_aa_large' => $passesAA18,
            'passes_aaa_large' => $passesAAA18,
        ];
    }

    /**
     * Generate accessibility information
     */
    private function generateAccessibilityInfo(string $color, string $type): array
    {
        $textColors = ['text', 'primary', 'secondary'];

        // Calculate main contrast values
        $contrastNormal = $this->calculateContrastRatio($color, '#FFFFFF');
        $contrastDark = $this->calculateContrastRatio($color, '#000000');
        $contrastGray = $this->calculateContrastRatio($color, '#64748B');

        $isTextColor = in_array($type, $textColors);
        $suitability = [];

        if ($contrastNormal >= 7.0) {
            $suitability[] = 'Excellent for dark backgrounds';
        } elseif ($contrastNormal >= 4.5) {
            $suitability[] = 'Good for dark backgrounds';
        } elseif ($contrastNormal >= 3.0) {
            $suitability[] = 'Suitable for large text on dark backgrounds';
        }

        if ($contrastDark >= 7.0) {
            $suitability[] = 'Excellent for light backgrounds';
        } elseif ($contrastDark >= 4.5) {
            $suitability[] = 'Good for light backgrounds';
        } elseif ($contrastDark >= 3.0) {
            $suitability[] = 'Suitable for large text on light backgrounds';
        }

        return [
            'contrast_ratio_normal' => round($contrastNormal, 2),
            'contrast_ratio_dark' => round($contrastDark, 2),
            'contrast_ratio_gray' => round($contrastGray, 2),
            'is_text_color' => $isTextColor,
            'suitability' => $suitability,
            'wcag_aa_pass' => max($contrastNormal, $contrastDark) >= 4.5,
            'wcag_aaa_pass' => max($contrastNormal, $contrastDark) >= 7.0,
            'hsl_values' => $this->hexToHsl($color),
            'rgb_values' => $this->hexToRgb($color),
        ];
    }

    /**
     * Convert hex to HSL
     */
    private function hexToHsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0) {
            $h = 0;
            $s = 0;
        } else {
            $s = $l > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

            switch ($max) {
                case $r: $h = ($g - $b) / $delta + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $delta + 2; break;
                case $b: $h = ($r - $g) / $delta + 4; break;
            }
            $h /= 6;
        }

        return [
            'hue' => round($h * 360),
            'saturation' => round($s * 100),
            'lightness' => round($l * 100),
        ];
    }

    /**
     * Convert hex to RGB array
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        return [
            'red' => hexdec(substr($hex, 0, 2)),
            'green' => hexdec(substr($hex, 2, 2)),
            'blue' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Generate usage guidelines for different color types
     */
    private function generateUsageGuidelines(string $type): string
    {
        return match ($type) {
            'primary' => implode(' ', [
                'Use as the main brand color for primary buttons, links, and key elements.',
                'Ensure sufficient contrast (4.5:1 minimum) for accessibility.',
                'Limit use to avoid visual fatigue and maintain brand impact.',
                'Consider different shades for hover states and secondary actions.',
            ]),
            'secondary' => implode(' ', [
                'Use to support the primary color and provide visual hierarchy.',
                'Ideal for secondary buttons, borders, and decorative elements.',
                'Should complement rather than compete with the primary color.',
                'Can be adjusted in opacity for subtle backgrounds and overlays.',
            ]),
            'accent' => implode(' ', [
                'Use sparingly to draw attention to important elements and calls-to-action.',
                'Excellent for highlighting key information or new features.',
                'Avoid overuse which can diminish its impact.',
                'Consider using lighter shades for alert states and notifications.',
            ]),
            'success' => implode(' ', [
                'Use exclusively for positive actions, confirmations, and success states.',
                'Helps users identify when actions have been completed successfully.',
                'Maintain consistency across success messages and notifications.',
                'Test contrast with surrounding elements for optimal visibility.',
            ]),
            'error' => implode(' ', [
                'Use only for error states, warnings, and important alerts.',
                'Should stand out clearly to ensure users notice critical information.',
                'Avoid using variations that might be confused with other states.',
                'Always provide clear text alongside the color for clarity.',
            ]),
            'neutral' => implode(' ', [
                'Perfect for backgrounds, borders, and creating visual separation.',
                'Use as the foundation for building color hierarchies.',
                'Consider different shades for various levels of visual weight.',
                'Test combinations to ensure sufficient contrast with content.',
            ]),
            default => 'General usage guidelines should be determined based on visual context and user experience requirements.',
        };
    }

    /**
     * Create a primary color
     */
    public function primary(): static
    {
        return $this->state(['type' => 'primary']);
    }

    /**
     * Create a secondary color
     */
    public function secondary(): static
    {
        return $this->state(['type' => 'secondary']);
    }

    /**
     * Create an accent color
     */
    public function accent(): static
    {
        return $this->state(['type' => 'accent']);
    }

    /**
     * Create a success color
     */
    public function success(): static
    {
        return $this->state(['type' => 'success']);
    }

    /**
     * Create an error color
     */
    public function error(): static
    {
        return $this->state(['type' => 'error']);
    }

    /**
     * Create a warning color
     */
    public function warning(): static
    {
        return $this->state(['type' => 'warning']);
    }

    /**
     * Create a text color
     */
    public function text(): static
    {
        return $this->state(['type' => 'text']);
    }

    /**
     * Create a background color
     */
    public function background(): static
    {
        return $this->state(['type' => 'background']);
    }

    /**
     * Create a high contrast color
     */
    public function highContrast(): static
    {
        return $this->state(function (array $attributes) {
            $contrastRatios = $this->generateContrastRatios($attributes['value'] ?? '#000000');
            $hasHighContrast = collect($contrastRatios)->some(fn($ratio) => $ratio['ratio'] >= 7.0);

            if (!$hasHighContrast) {
                // Force a high contrast color if calculation shows it's needed
                $highContrastColors = ['#FFFFFF', '#000000', '#FF0000', '#FFFF00'];
                $attributes['value'] = fake()->randomElement($highContrastColors);
                $attributes['contrast_ratios'] = $this->generateContrastRatios($attributes['value']);
            }

            return $attributes;
        });
    }

    /**
     * Create a color with specific RGB values
     */
    public function withRgb(int $red, int $green, int $blue): static
    {
        return $this->state([
            'value' => sprintf('#%02X%02X%02X', $red, $green, $blue),
        ]);
    }

    /**
     * Create a color for a specific tenant
     */
    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a color with custom usage guidelines
     */
    public function withGuidelines(string $guidelines): static
    {
        return $this->state([
            'usage_guidelines' => $guidelines,
        ]);
    }

    /**
     * Create a color with high usage count
     */
    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(500, 10000),
        ]);
    }

    /**
     * Create a monochrome palette color
     */
    public function monochrome(): static
    {
        return $this->state(function (array $attributes) {
            $baseValue = fake()->numberBetween(0, 255);
            return [
                'value' => sprintf('#%02X%02X%02X', $baseValue, $baseValue, $baseValue),
            ];
        });
    }

    /**
     * Create a warm color (reds, oranges, yellows)
     */
    public function warm(): static
    {
        return $this->state(function (array $attributes) {
            $warmColors = [
                '#EF4444', '#F97316', '#F59E0B', '#EAB308', '#F97316',
                '#DC2626', '#EA580C', '#D97706', '#CA8A04', '#C2410C'
            ];
            return [
                'value' => fake()->randomElement($warmColors),
            ];
        });
    }

    /**
     * Create a cool color (blues, greens, purples)
     */
    public function cool(): static
    {
        return $this->state(function (array $attributes) {
            $coolColors = [
                '#3B82F6', '#06B6D4', '#10B981', '#8B5CF6', '#EC4899',
                '#2563EB', '#0891B2', '#059669', '#7C3AED', '#DB2777'
            ];
            return [
                'value' => fake()->randomElement($coolColors),
            ];
        });
    }
}