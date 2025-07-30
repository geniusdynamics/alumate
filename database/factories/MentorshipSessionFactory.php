<?php

namespace Database\Factories;

use App\Models\MentorshipRequest;
use App\Models\MentorshipSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorshipSessionFactory extends Factory
{
    protected $model = MentorshipSession::class;

    public function definition(): array
    {
        return [
            'mentorship_id' => MentorshipRequest::factory(),
            'scheduled_at' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'duration' => $this->faker->randomElement([30, 45, 60, 90]),
            'notes' => $this->faker->optional()->sentence(),
            'status' => 'scheduled',
            'feedback' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            'feedback' => [
                'mentor' => [
                    'rating' => $this->faker->numberBetween(1, 5),
                    'feedback' => $this->faker->sentence(),
                    'submitted_at' => now()->toISOString(),
                ],
                'mentee' => [
                    'rating' => $this->faker->numberBetween(1, 5),
                    'feedback' => $this->faker->sentence(),
                    'submitted_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'scheduled_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('+1 hour', '+1 month'),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('today', 'today +23 hours'),
        ]);
    }

    public function thisWeek(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('monday this week', 'sunday this week'),
        ]);
    }
}