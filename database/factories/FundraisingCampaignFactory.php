<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FundraisingCampaign>
 */
class FundraisingCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = fake()->dateTimeBetween($startDate, '+6 months');
        
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(3),
            'story' => fake()->paragraphs(5, true),
            'goal_amount' => fake()->numberBetween(1000, 100000),
            'raised_amount' => fake()->numberBetween(0, 50000),
            'currency' => 'USD',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => fake()->randomElement(['draft', 'active', 'paused', 'completed']),
            'type' => fake()->randomElement(['general', 'scholarship', 'emergency', 'project']),
            'media_urls' => fake()->optional(0.7)->randomElements([
                'https://picsum.photos/800/600?random=1',
                'https://picsum.photos/800/600?random=2',
                'https://picsum.photos/800/600?random=3',
            ], fake()->numberBetween(0, 2)),
            'settings' => [
                'visibility' => 'public',
                'allow_comments' => true,
            ],
            'allow_peer_fundraising' => fake()->boolean(70),
            'show_donor_names' => fake()->boolean(80),
            'allow_anonymous_donations' => fake()->boolean(90),
            'thank_you_message' => fake()->optional(0.6)->paragraph(),
            'created_by' => \App\Models\User::factory(),
            'institution_id' => fake()->optional(0.8)->numberBetween(1, 10),
            'donor_count' => fake()->numberBetween(0, 500),
            'analytics_data' => [
                'views' => fake()->numberBetween(100, 10000),
                'shares' => fake()->numberBetween(10, 1000),
            ],
        ];
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => now()->subDays(fake()->numberBetween(1, 30)),
            'end_date' => now()->addDays(fake()->numberBetween(30, 180)),
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'raised_amount' => fake()->numberBetween($attributes['goal_amount'], $attributes['goal_amount'] * 1.5),
        ]);
    }
}
