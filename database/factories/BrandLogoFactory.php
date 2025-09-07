<?php

namespace Database\Factories;

use App\Models\BrandLogo;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandLogo>
 */
class BrandLogoFactory extends Factory
{
    protected $model = BrandLogo::class;

    public function definition(): array
    {
        $logoTypes = [
            'primary' => 'Primary Logo',
            'secondary' => 'Secondary Logo',
            'favicon' => 'Favicon',
            'footer' => 'Footer Logo',
            'email' => 'Email Logo',
            'social' => 'Social Media Logo',
        ];

        $mimeTypes = [
            'image/png',
            'image/jpeg',
            'image/webp',
            'image/svg+xml',
        ];

        $selectedType = fake()->randomElement(array_keys($logoTypes));
        $name = $logoTypes[$selectedType];
        $brandName = fake()->company();
        $mimeType = fake()->randomElement($mimeTypes);
        $cdnUrl = fake()->randomElement([null, fake()->url() . '/logos/' . fake()->unique()->uuid()]);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'type' => $selectedType,
            'url' => $this->generateLogoUrl($brandName, $selectedType),
            'alt' => $brandName . ' - ' . $name,
            'size' => fake()->numberBetween(1000, 50000),
            'mime_type' => $mimeType,
            'optimized' => fake()->boolean(80), // 80% chance of being optimized
            'is_primary' => $selectedType === 'primary' ? fake()->boolean(60) : false,
            'variants' => $this->generateLogoVariants($brandName, $selectedType, $mimeType),
            'usage_guidelines' => $this->generateUsageGuidelines($selectedType),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * Generate a realistic logo URL
     */
    private function generateLogoUrl(string $brandName, string $type): string
    {
        $baseUrl = 'https://via.placeholder.com';
        $slug = Str::slug($brandName);

        return match ($type) {
            'favicon' => "{$baseUrl}/32x32/3B82F6/FFFFFF?text=" . urlencode(strtoupper(substr($brandName, 0, 3))),
            'primary' => "{$baseUrl}/200x80/ffffff/000000?text=" . urlencode($slug),
            'secondary' => "{$baseUrl}/180x60/ffffff/000000?text=" . urlencode($slug . '+sec'),
            'footer' => "{$baseUrl}/150x50/ffffff/000000?text=" . urlencode($slug . '+ft'),
            'email' => "{$baseUrl}/300x80/ffffff/000000?text=" . urlencode($slug . '+email'),
            'social' => "{$baseUrl}/1200x630/ffffff/000000?text=" . urlencode($slug + '+social'),
            default => "{$baseUrl}/200x80/ffffff/000000?text=" . urlencode($slug),
        };
    }

    /**
     * Generate logo variants for different sizes and formats
     */
    private function generateLogoVariants(string $brandName, string $type, string $mimeType): array
    {
        $variants = [];
        $baseSlug = Str::slug($brandName);
        $baseUrl = 'https://cdn.example.com/logos/' . $baseSlug;

        // Generate multiple size variants
        $sizes = match ($type) {
            'favicon' => [16, 32, 48, 64],
            'primary' => [150, 200, 300, 400, 800],
            'secondary' => [100, 150, 200, 300],
            'footer' => [80, 100, 150, 200],
            'email' => [200, 300, 400, 600],
            'social' => [1200, 600, 300],
            default => [150, 200, 300],
        };

        foreach ($sizes as $size) {
            $width = $type === 'social' ? ($size === 1200 ? 1200 : ($size === 600 ? 1200 : 600)) : $size;
            $height = $type === 'social' ? ($size === 1200 ? 630 : ($size === 600 ? 315 : 315)) : (
                $type === 'favicon' ? $size : (int)($size * 0.4)
            );

            $variants[] = [
                'type' => 'optimized',
                'url' => "{$baseUrl}-{$size}.webp",
                'size' => $width * $height * 0.1, // Approximate file size in bytes
                'format' => 'webp',
                'width' => $width,
                'height' => $height,
            ];

            // Add fallback PNG variant
            if ($width <= 400) { // Only for smaller sizes
                $variants[] = [
                    'type' => 'fallback',
                    'url' => "{$baseUrl}-{$size}.png",
                    'size' => $width * $height * 0.2, // Larger file size for PNG
                    'format' => 'png',
                    'width' => $width,
                    'height' => $height,
                ];
            }

            // Add CDN variant for primary logos
            if ($type === 'primary' && $size <= 300) {
                $variants[] = [
                    'type' => 'cdn',
                    'url' => "https://cdn.jsdelivr.net/gh/logos/{$baseSlug}@1.0.0/logo-{$size}.svg",
                    'size' => $size * 10, // Small SVG size
                    'format' => 'svg',
                    'width' => $width,
                    'height' => $height,
                ];
            }
        }

        return $variants;
    }

    /**
     * Generate usage guidelines for different logo types
     */
    private function generateUsageGuidelines(string $type): array
    {
        $baseGuidelines = [
            'Never distort or stretch the logo',
            'Always use on clean, contrasting backgrounds',
            'Minimum clear space of 1x logo height around logo',
            'Never place logo over images with high contrast',
        ];

        return match ($type) {
            'favicon' => array_merge($baseGuidelines, [
                'Must be exactly square dimensions',
                'Use on transparent or clean background colors',
                'Scales well for small display in browser tabs',
                'Consider using monochrome version for UX clarity',
            ]),
            'primary' => array_merge($baseGuidelines, [
                'Use horizontally centered on white/light backgrounds',
                'Minimum size 150px width in digital applications',
                'Not for use on colored backgrounds without clear space',
                'Primary brand identifier - use consistently',
            ]),
            'secondary' => array_merge($baseGuidelines, [
                'Use as alternative when primary is too distracting',
                'Suitable for internal communications and emails',
                'Can be used on color backgrounds with clear space',
                'Maintains brand recognition without dominating',
            ]),
            'footer' => array_merge($baseGuidelines, [
                'Designed specifically for footer placement',
                'Use black/white version on dark backgrounds',
                'Can be vertically aligned with copyright text',
                'Optimal size 100-150px width',
            ]),
            'email' => array_merge($baseGuidelines, [
                'Designed for email client compatibility',
                'Use colored version for better email engagement',
                'Optimal size 200-300px width',
                'Please test rendering in major email clients',
            ]),
            'social' => array_merge($baseGuidelines, [
                'Designed for social media graphics and covers',
                'Recommended for Facebook, LinkedIn, Twitter cover images',
                'Use with caution on Instagram and TikTok',
                'Maintain aspect ratio for proper platform display',
            ]),
            default => $baseGuidelines,
        };
    }

    /**
     * Create a primary logo
     */
    public function primary(): static
    {
        return $this->state([
            'type' => 'primary',
            'is_primary' => true,
            'name' => 'Primary Logo',
            'optimized' => true,
        ]);
    }

    /**
     * Create a favicon logo
     */
    public function favicon(): static
    {
        return $this->state([
            'type' => 'favicon',
            'is_primary' => false,
            'name' => 'Favicon',
            'size' => fake()->numberBetween(512, 2048), // Smaller favicon file
        ]);
    }

    /**
     * Create an optimized logo
     */
    public function optimized(): static
    {
        return $this->state([
            'optimized' => true,
            'size' => fake()->numberBetween(1000, 10000), // Smaller optimized file
        ]);
    }

    /**
     * Create a logo with multiple variants
     */
    public function withVariants(int $count = 5): static
    {
        return $this->state(function (array $attributes) use ($count) {
            return [
                'variants' => array_slice($attributes['variants'] ?? [], 0, $count),
            ];
        });
    }

    /**
     * Create a logo for a specific tenant
     */
    public function forTenant($tenantId): static
    {
        return $this->state([
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a logo with specific type and size
     */
    public function ofType(string $type): static
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
                'name' => match ($type) {
                    'primary' => 'Primary Logo',
                    'secondary' => 'Secondary Logo',
                    'favicon' => 'Favicon',
                    'footer' => 'Footer Logo',
                    'email' => 'Email Logo',
                    'social' => 'Social Media Logo',
                    default => $attributes['name'],
                },
            ];
        });
    }

    /**
     * Create a logo with custom usage guidelines
     */
    public function withGuidelines(array $guidelines): static
    {
        return $this->state([
            'usage_guidelines' => $guidelines,
        ]);
    }

    /**
     * Create a logo with CDN support
     */
    public function withCdn(): static
    {
        return $this->state(function (array $attributes) {
            $cdnVariant = [
                'type' => 'cdn',
                'url' => fake()->url() . '/cdn/logos/' . fake()->unique()->uuid(),
                'size' => $attributes['size'] ?? 10000,
                'format' => 'webp',
            ];

            $variants = $attributes['variants'] ?? [];
            $variants[] = $cdnVariant;

            return [
                'variants' => $variants,
                'optimized' => true,
            ];
        });
    }
}