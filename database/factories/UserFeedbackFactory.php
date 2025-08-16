<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserFeedback>
 */
class UserFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['bug_report', 'feature_request', 'general_feedback', 'usability_issue'];
        $type = $this->faker->randomElement($types);

        $content = match ($type) {
            'bug_report' => $this->faker->paragraph().' Steps to reproduce: '.$this->faker->sentence(),
            'feature_request' => 'It would be great if the platform could '.$this->faker->sentence(),
            'general_feedback' => $this->faker->paragraph(),
            'usability_issue' => 'I found it confusing when '.$this->faker->sentence(),
        };

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'content' => $content,
            'rating' => $type === 'general_feedback' ? $this->faker->numberBetween(1, 5) : null,
            'metadata' => [
                'page' => $this->faker->url(),
                'userAgent' => $this->faker->userAgent(),
                'timestamp' => $this->faker->dateTimeThisMonth()->toISOString(),
            ],
            'status' => $this->faker->randomElement(['pending', 'reviewed', 'resolved', 'dismissed']),
        ];
    }

    public function bugReport(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bug_report',
            'content' => 'Found a bug: '.$this->faker->sentence().' Steps: '.$this->faker->paragraph(),
            'rating' => null,
        ]);
    }

    public function featureRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'feature_request',
            'content' => 'Feature request: '.$this->faker->paragraph(),
            'rating' => null,
        ]);
    }

    public function generalFeedback(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'general_feedback',
            'content' => $this->faker->paragraph(),
            'rating' => $this->faker->numberBetween(1, 5),
        ]);
    }
}
