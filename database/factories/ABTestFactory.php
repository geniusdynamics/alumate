<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ABTest>
 */
class ABTestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variants = ['control', 'variant_a', 'variant_b'];
        $distribution = [
            'control' => 50,
            'variant_a' => 25,
            'variant_b' => 25,
        ];

        return [
            'name' => $this->faker->slug(3).'_test',
            'description' => $this->faker->paragraph(),
            'variants' => $variants,
            'distribution' => $distribution,
            'status' => $this->faker->randomElement(['draft', 'active', 'paused', 'completed']),
            'started_at' => $this->faker->optional(0.7)->dateTimeThisMonth(),
            'ended_at' => $this->faker->optional(0.3)->dateTimeThisMonth(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'started_at' => $this->faker->dateTimeThisMonth(),
            'ended_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => $this->faker->dateTimeThisMonth(),
            'ended_at' => $this->faker->dateTimeThisMonth(),
        ]);
    }

    public function twoVariants(): static
    {
        return $this->state(fn (array $attributes) => [
            'variants' => ['control', 'variant_a'],
            'distribution' => [
                'control' => 50,
                'variant_a' => 50,
            ],
        ]);
    }
}
