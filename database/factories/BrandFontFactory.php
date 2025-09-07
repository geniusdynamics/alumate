<?php

namespace Database\Factories;

use App\Models\BrandFont;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandFont>
 */
class BrandFontFactory extends Factory
{
    protected $model = BrandFont::class;

    public function definition(): array
    {
        $fontTypes = ['system', 'google', 'custom'];
        $selectedType = fake()->randomElement($fontTypes);

        $fontData = $this->generateFontData($selectedType);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $fontData['name'],
            'family' => $fontData['family'],
            'weights' => $fontData['weights'],
            'styles' => $fontData['styles'],
            'is_primary' => false,
            'type' => $selectedType,
            'source' => $fontData['source'],
            'url' => $fontData['url'],
            'fallbacks' => $fontData['fallbacks'],
            'usage_count' => fake()->numberBetween(0, 1000),
            'loading_strategy' => $this->getRandomLoadingStrategy($selectedType),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Generate font data based on type
     */
    private function generateFontData(string $type): array
    {
        return match ($type) {
            'system' => $this->generateSystemFontData(),
            'google' => $this->generateGoogleFontData(),
            'custom' => $this->generateCustomFontData(),
        };
    }

    /**
     * Generate system font data
     */
    private function generateSystemFontData(): array
    {
        $systemFonts = [
            [
                'name' => 'System Sans Serif',
                'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                'weights' => [400, 500, 600, 700],
                'styles' => ['normal'],
                'fallbacks' => ['Arial', 'Helvetica', 'sans-serif'],
                'source' => null,
                'url' => null,
            ],
            [
                'name' => 'System Serif',
                'family' => '"Times New Roman", Times, serif',
                'weights' => [400, 700],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Georgia', 'serif'],
                'source' => null,
                'url' => null,
            ],
            [
                'name' => 'System Mono',
                'family' => '"Courier New", Courier, Consolas, Monaco, monospace',
                'weights' => [400, 700],
                'styles' => ['normal'],
                'fallbacks' => ['Monaco', 'monospace'],
                'source' => null,
                'url' => null,
            ],
        ];

        return fake()->randomElement($systemFonts);
    }

    /**
     * Generate Google Fonts data
     */
    private function generateGoogleFontData(): array
    {
        $googleFonts = [
            [
                'name' => 'Inter',
                'family' => 'Inter',
                'weights' => [100, 200, 300, 400, 500, 600, 700, 800, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['system-ui', '-apple-system', 'sans-serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Inter',
            ],
            [
                'name' => 'Roboto',
                'family' => 'Roboto',
                'weights' => [100, 300, 400, 500, 700, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Helvetica', 'Arial', 'sans-serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Roboto',
            ],
            [
                'name' => 'Open Sans',
                'family' => 'Open Sans',
                'weights' => [300, 400, 500, 600, 700, 800],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Helvetica', 'Arial', 'sans-serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap',
                'url' => 'https://fonts.google.com/specimen/Open+Sans',
            ],
            [
                'name' => 'Lato',
                'family' => 'Lato',
                'weights' => [100, 300, 400, 700, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Helvetica', 'Arial', 'sans-serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Lato',
            ],
            [
                'name' => 'Poppins',
                'family' => 'Poppins',
                'weights' => [100, 200, 300, 400, 500, 600, 700, 800, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Helvetica', 'Arial', 'sans-serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Poppins',
            ],
            [
                'name' => 'Playfair Display',
                'family' => 'Playfair Display',
                'weights' => [400, 500, 600, 700, 800, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Georgia', 'serif'],
                'source' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Playfair+Display',
            ],
            [
                'name' => 'Source Code Pro',
                'family' => 'Source Code Pro',
                'weights' => [200, 300, 400, 500, 600, 700, 800, 900],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Consolas', 'monospace'],
                'source' => 'https://fonts.googleapis.com/css2?family=Source+Code+Pro:wght@200;300;400;500;600;700;800;900&display=swap',
                'url' => 'https://fonts.google.com/specimen/Source+Code+Pro',
            ],
        ];

        return fake()->randomElement($googleFonts);
    }

    /**
     * Generate custom font data
     */
    private function generateCustomFontData(): array
    {
        $customFonts = [
            [
                'name' => 'Brand Custom Sans',
                'family' => 'BrandCustomSans',
                'weights' => [300, 400, 500, 600, 700],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Arial', 'sans-serif'],
                'source' => 'https://cdn.example.com/fonts/brand-custom-sans.woff2',
                'url' => 'https://example.com/fonts',
            ],
            [
                'name' => 'Company Serif',
                'family' => 'CompanySerif',
                'weights' => [400, 600, 700],
                'styles' => ['normal', 'italic'],
                'fallbacks' => ['Georgia', 'serif'],
                'source' => 'https://fonts.company.com/serif.woff2',
                'url' => 'https://company.com/brand/fonts',
            ],
            [
                'name' => 'University Script',
                'family' => 'UniversityScript',
                'weights' => [400],
                'styles' => ['normal'],
                'fallbacks' => ['cursive'],
                'source' => 'https://university.edu/fonts/script.ttf',
                'url' => 'https://university.edu/brand-guidelines',
            ],
            [
                'name' => 'Corporate Display',
                'family' => 'CorporateDisplay',
                'weights' => [200, 300, 400, 500, 600],
                'styles' => ['normal'],
                'fallbacks' => ['Arial', 'Helvetica', 'sans-serif'],
                'source' => 'https://corporate.com/assets/fonts/display.woff',
                'url' => 'https://corporate.com/brand-assets',
            ],
        ];

        return fake()->randomElement($customFonts);
    }

    /**
     * Get appropriate loading strategy based on font type
     */
    private function getRandomLoadingStrategy(string $type): string
    {
        $strategies = match ($type) {
            'system' => ['swap', 'block', 'optional'],
            'google' => ['swap', 'block', 'optional'],
            'custom' => ['swap', 'block'],
            default => ['swap'],
        };

        return fake()->randomElement($strategies);
    }

    /**
     * Create a primary font
     */
    public function primary(): static
    {
        return $this->state([
            'is_primary' => true,
        ]);
    }

    /**
     * Create a system font
     */
    public function system(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, $this->generateSystemFontData(), [
                'type' => 'system',
            ]);
        });
    }

    /**
     * Create a Google font
     */
    public function google(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, $this->generateGoogleFontData(), [
                'type' => 'google',
            ]);
        });
    }

    /**
     * Create a custom font
     */
    public function custom(): static
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, $this->generateCustomFontData(), [
                'type' => 'custom',
            ]);
        });
    }

    /**
     * Create a sans-serif font
     */
    public function sansSerif(): static
    {
        return $this->state(function (array $attributes) {
            $sansFonts = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Poppins'];
            return [
                'name' => fake()->randomElement($sansFonts),
                'family' => fake()->randomElement($sansFonts),
                'fallbacks' => ['Helvetica', 'Arial', 'sans-serif'],
            ];
        });
    }

    /**
     * Create a serif font
     */
    public function serif(): static
    {
        return $this->state(function (array $attributes) {
            $serifFonts = ['Playfair Display', 'Crimson Text', 'Merriweather', 'Source Serif Pro'];
            return [
                'name' => fake()->randomElement($serifFonts),
                'family' => fake()->randomElement($serifFonts),
                'fallbacks' => ['Georgia', 'Times New Roman', 'serif'],
            ];
        });
    }

    /**
     * Create a monospace font
     */
    public function monospace(): static
    {
        return $this->state(function (array $attributes) {
            $monoFonts = ['Source Code Pro', 'JetBrains Mono', 'Fira Code', 'Roboto Mono'];
            return [
                'name' => fake()->randomElement($monoFonts),
                'family' => fake()->randomElement($monoFonts),
                'fallbacks' => ['Consolas', 'monospace'],
            ];
        });
    }

    /**
     * Create a heavy font (multiple weights)
     */
    public function heavy(): static
    {
        return $this->state([
            'weights' => [200, 300, 400, 500, 600, 700, 800, 900],
        ]);
    }

    /**
     * Create a light font (fewer weights)
     */
    public function light(): static
    {
        return $this->state([
            'weights' => [300, 400, 500, 600, 700],
        ]);
    }

    /**
     * Create a font with italics
     */
    public function withItalics(): static
    {
        return $this->state([
            'styles' => ['normal', 'italic'],
        ]);
    }

    /**
     * Create a popular font (high usage count)
     */
    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(500, 5000),
        ]);
    }

    /**
     * Create a font with optimized loading
     */
    public function optimized(): static
    {
        return $this->state([
            'loading_strategy' => 'swap',
        ]);
    }

    /**
     * Create a font with block loading (better for branding)
     */
    public function blocking(): static
    {
        return $this->state([
            'loading_strategy' => 'block',
        ]);
    }

    /**
     * Create a font with optional loading
     */
    public function optional(): static
    {
        return $this->state([
            'loading_strategy' => 'optional',
        ]);
    }

    /**
     * Create a font for a specific tenant
     */
    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a font with specific weights
     */
    public function withWeights(array $weights): static
    {
        return $this->state([
            'weights' => $weights,
        ]);
    }

    /**
     * Create a font with specific name and family
     */
    public function named(string $name, string $family): static
    {
        return $this->state([
            'name' => $name,
            'family' => $family,
        ]);
    }

    /**
     * Create an academic institution font
     */
    public function academic(): static
    {
        return $this->state([
            'name' => 'Academic Sans',
            'family' => 'Academic Sans',
            'weights' => [300, 400, 500, 600, 700],
            'styles' => ['normal', 'italic'],
            'fallbacks' => ['Georgia', 'serif'],
            'type' => 'custom',
        ]);
    }

    /**
     * Create a corporate font
     */
    public function corporate(): static
    {
        return $this->state([
            'name' => 'Corporate Sans',
            'family' => 'Corporate Sans',
            'weights' => [400, 500, 600, 700],
            'styles' => ['normal'],
            'fallbacks' => ['Arial', 'sans-serif'],
            'type' => 'custom',
        ]);
    }

    /**
     * Create a creative/brand font
     */
    public function creative(): static
    {
        return $this->state([
            'name' => 'Creative Script',
            'family' => 'Creative Script',
            'weights' => [400],
            'styles' => ['normal'],
            'fallbacks' => ['cursive'],
            'type' => 'custom',
        ]);
    }
}