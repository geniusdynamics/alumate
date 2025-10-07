<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Insight;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Insight>
 */
class InsightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Insight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => $this->faker->randomElement(['trend', 'recommendation']),
            'data' => [
                'title' => $this->faker->sentence(),
                'description' => $this->faker->paragraph(),
                'impact_score' => $this->faker->numberBetween(1, 100),
                'metrics' => [
                    'conversion_rate' => $this->faker->randomFloat(2, 0, 100),
                    'engagement_score' => $this->faker->numberBetween(1, 100),
                ],
            ],
            'status' => $this->faker->randomElement(['active', 'dismissed', 'implemented']),
            'effectiveness_score' => $this->faker->optional(0.7)->randomFloat(2, 0, 100),
            'tracked_at' => $this->faker->optional(0.5)->dateTime(),
        ];
    }

    /**
     * Create an active insight.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Create an implemented insight.
     */
    public function implemented(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'implemented',
            'effectiveness_score' => $this->faker->randomFloat(2, 0, 100),
            'tracked_at' => $this->faker->dateTime(),
        ]);
    }

    /**
     * Create a dismissed insight.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
        ]);
    }

    /**
     * Create a trend insight.
     */
    public function trend(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'trend',
            'data' => array_merge($attributes['data'] ?? [], [
                'direction' => $this->faker->randomElement(['increasing', 'decreasing', 'stable']),
                'strength' => $this->faker->randomFloat(2, 0, 50),
                'confidence' => $this->faker->numberBetween(50, 100),
            ]),
        ]);
    }

    /**
     * Create a recommendation insight.
     */
    public function recommendation(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recommendation',
            'data' => array_merge($attributes['data'] ?? [], [
                'action' => $this->faker->sentence(),
                'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
                'category' => $this->faker->randomElement([
                    'user_engagement',
                    'conversion_optimization',
                    'technical_stability',
                    'performance',
                    'analytics_setup'
                ]),
            ]),
        ]);
    }
}