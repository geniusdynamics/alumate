<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollaborationSession>
 */
class CollaborationSessionFactory extends Factory
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
            'user_id' => \App\Models\User::factory(),
            'session_id' => $this->faker->uuid(),
            'cursor_position' => [
                'x' => $this->faker->numberBetween(0, 1920),
                'y' => $this->faker->numberBetween(0, 1080),
            ],
            'selected_component' => [
                'id' => 'component-' . $this->faker->uuid(),
                'type' => $this->faker->randomElement(['text', 'image', 'button', 'form']),
            ],
            'status' => $this->faker->randomElement(['active', 'idle', 'disconnected']),
            'last_activity' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'last_activity' => now(),
        ]);
    }

    public function idle(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'idle',
            'last_activity' => now()->subMinutes(10),
        ]);
    }

    public function disconnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'disconnected',
            'last_activity' => now()->subHour(),
        ]);
    }
}
