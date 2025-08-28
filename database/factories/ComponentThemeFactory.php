<?php

namespace Database\Factories;

use App\Models\ComponentTheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComponentTheme>
 */
class ComponentThemeFactory extends Factory
{
    protected $model = ComponentTheme::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Test Theme '.rand(1, 1000);

        return [
            'tenant_id' => 1, // Will be overridden in tests
            'name' => $name,
            'slug' => str($name)->slug(),
            'config' => [
                'colors' => [
                    'primary' => '#'.str_pad(dechex(rand(0, 16777215)), 6, '0', STR_PAD_LEFT),
                    'secondary' => '#'.str_pad(dechex(rand(0, 16777215)), 6, '0', STR_PAD_LEFT),
                    'accent' => '#'.str_pad(dechex(rand(0, 16777215)), 6, '0', STR_PAD_LEFT),
                    'background' => '#ffffff',
                    'text' => '#333333',
                ],
                'typography' => [
                    'font_family' => 'Arial, sans-serif',
                    'heading_font' => 'Georgia, serif',
                    'font_sizes' => [
                        'base' => '16px',
                        'heading' => '2rem',
                    ],
                    'line_height' => 1.6,
                ],
                'spacing' => [
                    'base' => '1rem',
                    'small' => '0.5rem',
                    'large' => '2rem',
                    'section_padding' => '1.5rem',
                ],
                'borders' => [
                    'radius' => '4px',
                    'width' => '1px',
                ],
                'animations' => [
                    'duration' => '0.3s',
                    'easing' => 'ease',
                ],
            ],
            'is_default' => false,
        ];
    }

    /**
     * Create a default theme state
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Default Theme',
            'slug' => 'default-theme',
            'is_default' => true,
            'config' => [
                'colors' => [
                    'primary' => '#007bff',
                    'secondary' => '#6c757d',
                    'accent' => '#28a745',
                    'background' => '#ffffff',
                    'text' => '#333333',
                ],
                'typography' => [
                    'font_family' => 'Arial, sans-serif',
                    'heading_font' => 'Georgia, serif',
                    'font_sizes' => [
                        'base' => '16px',
                        'heading' => '2rem',
                    ],
                    'line_height' => 1.6,
                ],
                'spacing' => [
                    'base' => '1rem',
                    'small' => '0.5rem',
                    'large' => '2rem',
                    'section_padding' => '1.5rem',
                ],
                'borders' => [
                    'radius' => '4px',
                    'width' => '1px',
                ],
                'animations' => [
                    'duration' => '0.3s',
                    'easing' => 'ease',
                ],
            ],
        ]);
    }

    /**
     * Create a theme with high contrast colors for accessibility
     */
    public function highContrast(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'High Contrast Theme',
            'slug' => 'high-contrast-theme',
            'config' => array_merge($attributes['config'] ?? [], [
                'colors' => [
                    'primary' => '#000000',
                    'secondary' => '#333333',
                    'accent' => '#ffffff',
                    'background' => '#ffffff',
                    'text' => '#000000',
                ],
            ]),
        ]);
    }

    /**
     * Create a theme with invalid configuration for testing validation
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'config' => [
                'colors' => [
                    'primary' => 'invalid-color', // Invalid hex color
                ],
                'typography' => [
                    'font_family' => '', // Empty font family
                    'font_sizes' => [
                        'base' => 'invalid-size', // Invalid size format
                    ],
                ],
                'spacing' => [
                    'base' => 'invalid-spacing', // Invalid spacing format
                ],
            ],
        ]);
    }
}
