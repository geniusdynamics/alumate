<?php

namespace Database\Factories;

use App\Models\InsightsRecommendation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InsightsRecommendation>
 */
class InsightsRecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'recommendation_type' => $this->faker->randomElement([
                'course_suggestion',
                'skill_development',
                'career_path',
                'engagement_boost',
                'certification_path'
            ]),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'priority' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'confidence_score' => $this->faker->numberBetween(0, 100),
            'data_source' => $this->faker->randomElement([
                'learning_analytics',
                'career_prediction',
                'engagement_metrics',
                'external_analytics'
            ]),
            'metadata' => [
                'course_id' => $this->faker->numberBetween(1, 100),
                'predicted_outcome' => $this->faker->randomFloat(2, 0, 1),
                'time_to_complete' => $this->faker->numberBetween(1, 52), // weeks
            ],
            'expires_at' => $this->faker->optional(0.7)->dateTimeBetween('now', '+6 months'),
            'is_dismissed' => false,
            'dismissed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a high priority recommendation
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'confidence_score' => $this->faker->numberBetween(80, 100),
        ]);
    }

    /**
     * Create a dismissed recommendation
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Create a course suggestion recommendation
     */
    public function courseSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'recommendation_type' => 'course_suggestion',
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'suggested_course_id' => $this->faker->numberBetween(1, 100),
                'reason' => 'Based on career goals and current skill gaps',
            ]),
        ]);
    }

    /**
     * Create an expired recommendation
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }
}