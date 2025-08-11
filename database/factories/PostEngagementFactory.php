<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostEngagement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostEngagement>
 */
class PostEngagementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PostEngagement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['like', 'love', 'celebrate', 'support', 'insightful', 'comment', 'share', 'bookmark']),
            'metadata' => [],
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a like engagement
     */
    public function like(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'like',
        ]);
    }

    /**
     * Create a comment engagement
     */
    public function comment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'comment',
            'metadata' => [
                'comment' => $this->faker->sentence(),
            ],
        ]);
    }

    /**
     * Create a share engagement
     */
    public function share(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'share',
            'metadata' => [
                'commentary' => $this->faker->optional()->sentence(),
            ],
        ]);
    }

    /**
     * Create a bookmark engagement
     */
    public function bookmark(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bookmark',
        ]);
    }
}
