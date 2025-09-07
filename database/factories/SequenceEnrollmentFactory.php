<?php

namespace Database\Factories;

use App\Models\EmailSequence;
use App\Models\Lead;
use App\Models\SequenceEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SequenceEnrollment>
 */
class SequenceEnrollmentFactory extends Factory
{
    protected $model = SequenceEnrollment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = SequenceEnrollment::STATUSES;
        $enrolledAt = $this->faker->dateTimeBetween('-30 days', 'now');

        return [
            'sequence_id' => EmailSequence::factory(),
            'lead_id' => Lead::factory(),
            'current_step' => $this->faker->numberBetween(0, 5),
            'status' => $this->faker->randomElement($statuses),
            'enrolled_at' => $enrolledAt,
            'completed_at' => $this->faker->optional(0.3)->dateTimeBetween($enrolledAt, 'now'), // 30% completion rate
        ];
    }

    /**
     * Indicate that the enrollment is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the enrollment is completed
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $enrolledAt = $attributes['enrolled_at'] ?? $this->faker->dateTimeBetween('-30 days', '-1 day');

            return [
                'status' => 'completed',
                'current_step' => $this->faker->numberBetween(3, 10),
                'completed_at' => $this->faker->dateTimeBetween($enrolledAt, 'now'),
            ];
        });
    }

    /**
     * Indicate that the enrollment is paused
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the enrollment is unsubscribed
     */
    public function unsubscribed(): static
    {
        return $this->state(function (array $attributes) {
            $enrolledAt = $attributes['enrolled_at'] ?? $this->faker->dateTimeBetween('-30 days', '-1 day');

            return [
                'status' => 'unsubscribed',
                'completed_at' => $this->faker->dateTimeBetween($enrolledAt, 'now'),
            ];
        });
    }

    /**
     * Create enrollment for a specific sequence
     */
    public function forSequence(EmailSequence $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence_id' => $sequence->id,
        ]);
    }

    /**
     * Create enrollment for a specific lead
     */
    public function forLead(Lead $lead): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_id' => $lead->id,
        ]);
    }

    /**
     * Indicate that the enrollment is at the beginning
     */
    public function atBeginning(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => 0,
        ]);
    }

    /**
     * Indicate that the enrollment is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => $this->faker->numberBetween(1, 3),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the enrollment is near completion
     */
    public function nearCompletion(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => $this->faker->numberBetween(4, 6),
            'status' => 'active',
        ]);
    }

    /**
     * Create enrollment with recent enrollment date
     */
    public function recentlyEnrolled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrolled_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    /**
     * Create enrollment with old enrollment date
     */
    public function longTimeAgo(): static
    {
        return $this->state(fn (array $attributes) => [
            'enrolled_at' => $this->faker->dateTimeBetween('-90 days', '-30 days'),
        ]);
    }

    /**
     * Create enrollment with specific current step
     */
    public function atStep(int $step): static
    {
        return $this->state(fn (array $attributes) => [
            'current_step' => $step,
        ]);
    }
}