<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageVersion>
 */
class PageVersionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'page_id' => \App\Models\LandingPage::factory(),
            'version_number' => $this->faker->numberBetween(1, 10),
            'grapejs_data' => [
                'html' => '<div class="container"><h1>' . $this->faker->sentence() . '</h1><p>' . $this->faker->paragraph() . '</p></div>',
                'css' => '.container { padding: 20px; } h1 { color: #333; }',
                'components' => [],
            ],
            'metadata' => [
                'auto_save' => $this->faker->boolean(20),
                'device_mode' => $this->faker->randomElement(['desktop', 'tablet', 'mobile']),
            ],
            'change_summary' => $this->faker->optional()->sentence(),
            'created_by' => \App\Models\User::factory(),
            'is_published' => $this->faker->boolean(10),
            'published_at' => $this->faker->optional(10)->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function autoSave(): static
    {
        return $this->state(fn (array $attributes) => [
            'change_summary' => 'Auto-save at ' . now()->format('H:i:s'),
            'metadata' => array_merge($attributes['metadata'] ?? [], ['auto_save' => true]),
        ]);
    }
}
