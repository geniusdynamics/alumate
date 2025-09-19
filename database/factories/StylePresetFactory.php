<?php

namespace Database\Factories;

use App\Models\StylePreset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StylePreset>
 */
class StylePresetFactory extends Factory
{
    protected $model = StylePreset::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['buttons', 'cards', 'forms', 'headings', 'custom'];
        $category = $this->faker->randomElement($categories);

        return [
            'name' => $this->faker->words(2, true) . ' Style',
            'description' => $this->faker->sentence(),
            'category' => $category,
            'styles' => $this->generateStylesForCategory($category),
            'tailwind_classes' => $this->generateTailwindClasses($category),
            'tenant_id' => 'default-tenant',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Generate styles based on category
     */
    private function generateStylesForCategory(string $category): array
    {
        $brandColors = ['#3B82F6', '#1E40AF', '#10B981', '#F59E0B', '#6B7280', '#059669', '#D97706', '#DC2626'];
        
        switch ($category) {
            case 'buttons':
                return [
                    'background-color' => $this->faker->randomElement($brandColors),
                    'color' => '#FFFFFF',
                    'padding' => $this->faker->randomElement(['0.5rem 1rem', '0.75rem 1.5rem', '1rem 2rem']),
                    'border-radius' => $this->faker->randomElement(['0.25rem', '0.375rem', '0.5rem']),
                    'font-weight' => $this->faker->randomElement(['500', '600', '700']),
                    'border' => 'none',
                    'cursor' => 'pointer'
                ];

            case 'cards':
                return [
                    'background-color' => '#FFFFFF',
                    'border' => '1px solid #E5E7EB',
                    'border-radius' => $this->faker->randomElement(['0.5rem', '0.75rem', '1rem']),
                    'padding' => $this->faker->randomElement(['1rem', '1.5rem', '2rem']),
                    'box-shadow' => $this->faker->randomElement([
                        '0 1px 3px 0 rgb(0 0 0 / 0.1)',
                        '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                        '0 10px 15px -3px rgb(0 0 0 / 0.1)'
                    ])
                ];

            case 'forms':
                return [
                    'border' => '1px solid #D1D5DB',
                    'border-radius' => '0.375rem',
                    'padding' => '0.75rem',
                    'font-size' => '1rem',
                    'background-color' => '#FFFFFF',
                    'color' => '#374151'
                ];

            case 'headings':
                return [
                    'color' => $this->faker->randomElement($brandColors),
                    'font-size' => $this->faker->randomElement(['1.5rem', '2rem', '2.5rem', '3rem']),
                    'font-weight' => $this->faker->randomElement(['600', '700', '800']),
                    'line-height' => $this->faker->randomElement(['1.2', '1.3', '1.4']),
                    'margin-bottom' => $this->faker->randomElement(['0.5rem', '1rem', '1.5rem'])
                ];

            default:
                return [
                    'color' => $this->faker->randomElement($brandColors),
                    'background-color' => $this->faker->randomElement(['#FFFFFF', '#F9FAFB', '#F3F4F6']),
                    'padding' => $this->faker->randomElement(['0.5rem', '1rem', '1.5rem']),
                    'border-radius' => $this->faker->randomElement(['0.25rem', '0.5rem', '0.75rem'])
                ];
        }
    }

    /**
     * Generate Tailwind classes based on category
     */
    private function generateTailwindClasses(string $category): array
    {
        switch ($category) {
            case 'buttons':
                return [
                    $this->faker->randomElement(['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500']),
                    'text-white',
                    $this->faker->randomElement(['px-4', 'px-6', 'px-8']),
                    $this->faker->randomElement(['py-2', 'py-3', 'py-4']),
                    $this->faker->randomElement(['rounded', 'rounded-md', 'rounded-lg']),
                    $this->faker->randomElement(['font-medium', 'font-semibold', 'font-bold']),
                    'hover:opacity-90',
                    'transition-opacity'
                ];

            case 'cards':
                return [
                    'bg-white',
                    'border',
                    'border-gray-200',
                    $this->faker->randomElement(['rounded-lg', 'rounded-xl', 'rounded-2xl']),
                    $this->faker->randomElement(['p-4', 'p-6', 'p-8']),
                    $this->faker->randomElement(['shadow-sm', 'shadow', 'shadow-md', 'shadow-lg'])
                ];

            case 'forms':
                return [
                    'border',
                    'border-gray-300',
                    'rounded-md',
                    'px-3',
                    'py-2',
                    'bg-white',
                    'text-gray-900',
                    'focus:ring-2',
                    'focus:ring-blue-500',
                    'focus:border-blue-500'
                ];

            case 'headings':
                return [
                    $this->faker->randomElement(['text-blue-600', 'text-green-600', 'text-gray-900']),
                    $this->faker->randomElement(['text-2xl', 'text-3xl', 'text-4xl', 'text-5xl']),
                    $this->faker->randomElement(['font-semibold', 'font-bold', 'font-extrabold']),
                    $this->faker->randomElement(['leading-tight', 'leading-snug', 'leading-normal']),
                    $this->faker->randomElement(['mb-2', 'mb-4', 'mb-6'])
                ];

            default:
                return [
                    $this->faker->randomElement(['text-gray-900', 'text-blue-600', 'text-green-600']),
                    $this->faker->randomElement(['bg-white', 'bg-gray-50', 'bg-gray-100']),
                    $this->faker->randomElement(['p-2', 'p-4', 'p-6']),
                    $this->faker->randomElement(['rounded', 'rounded-md', 'rounded-lg'])
                ];
        }
    }

    /**
     * Create a button style preset
     */
    public function button(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Button Style',
            'category' => 'buttons',
            'styles' => [
                'background-color' => '#3B82F6',
                'color' => '#FFFFFF',
                'padding' => '0.75rem 1.5rem',
                'border-radius' => '0.375rem',
                'font-weight' => '600',
                'border' => 'none',
                'cursor' => 'pointer'
            ],
            'tailwind_classes' => ['bg-blue-500', 'text-white', 'px-6', 'py-3', 'rounded-md', 'font-semibold']
        ]);
    }

    /**
     * Create a card style preset
     */
    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Card Style',
            'category' => 'cards',
            'styles' => [
                'background-color' => '#FFFFFF',
                'border' => '1px solid #E5E7EB',
                'border-radius' => '0.5rem',
                'padding' => '1.5rem',
                'box-shadow' => '0 4px 6px -1px rgb(0 0 0 / 0.1)'
            ],
            'tailwind_classes' => ['bg-white', 'border', 'border-gray-200', 'rounded-lg', 'p-6', 'shadow-md']
        ]);
    }

    /**
     * Create a brand compliant preset
     */
    public function brandCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'styles' => [
                'color' => '#3B82F6',
                'background-color' => '#FFFFFF',
                'border-color' => '#6B7280'
            ]
        ]);
    }

    /**
     * Create a non-brand compliant preset
     */
    public function nonBrandCompliant(): static
    {
        return $this->state(fn (array $attributes) => [
            'styles' => [
                'color' => '#FF0000',
                'background-color' => '#00FF00',
                'border-color' => '#0000FF'
            ]
        ]);
    }
}
