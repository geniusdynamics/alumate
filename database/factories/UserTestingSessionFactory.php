<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserTestingSession>
 */
class UserTestingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scenarios = [
            'alumni_onboarding',
            'job_search_workflow',
            'mentorship_connection',
            'event_participation',
        ];

        $startedAt = $this->faker->dateTimeThisMonth();
        $status = $this->faker->randomElement(['active', 'completed', 'abandoned']);

        $completedAt = null;
        $duration = null;

        if ($status === 'completed') {
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');
            $duration = $this->faker->numberBetween(60, 1800); // 1 minute to 30 minutes
        } elseif ($status === 'abandoned') {
            $duration = $this->faker->numberBetween(10, 300); // 10 seconds to 5 minutes
        }

        return [
            'user_id' => User::factory(),
            'scenario' => $this->faker->randomElement($scenarios),
            'metadata' => json_encode([
                'browser' => $this->faker->userAgent(),
                'screen_resolution' => $this->faker->randomElement(['1920x1080', '1366x768', '1440x900']),
                'device_type' => $this->faker->randomElement(['desktop', 'tablet', 'mobile']),
            ]),
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'duration_seconds' => $duration,
        ];
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startedAt = $this->faker->dateTimeThisMonth();
            $completedAt = $this->faker->dateTimeBetween($startedAt, 'now');

            return [
                'status' => 'completed',
                'started_at' => $startedAt,
                'completed_at' => $completedAt,
                'duration_seconds' => $this->faker->numberBetween(120, 1200),
            ];
        });
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'abandoned',
            'completed_at' => null,
            'duration_seconds' => $this->faker->numberBetween(10, 180),
        ]);
    }

    public function onboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'scenario' => 'alumni_onboarding',
        ]);
    }
}
