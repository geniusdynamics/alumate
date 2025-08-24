<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'tenant_id' => Tenant::factory(),
            'content' => $this->faker->paragraph(3),
            'media_urls' => json_encode([]),
            'post_type' => $this->faker->randomElement(['text', 'media', 'career_update', 'achievement']),
            'visibility' => $this->faker->randomElement(['public', 'circles', 'groups', 'connections']),
            'circle_ids' => json_encode([]),
            'group_ids' => json_encode([]),
            'metadata' => json_encode([]),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Create a post with specific circle IDs
     */
    public function withCircles(array $circleIds): static
    {
        return $this->state(fn (array $attributes) => [
            'circle_ids' => json_encode($circleIds),
            'visibility' => 'circles',
        ]);
    }

    /**
     * Create a post with specific group IDs
     */
    public function withGroups(array $groupIds): static
    {
        return $this->state(fn (array $attributes) => [
            'group_ids' => json_encode($groupIds),
            'visibility' => 'groups',
        ]);
    }

    /**
     * Create a post with media
     */
    public function withMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_urls' => json_encode([
                $this->faker->imageUrl(640, 480, 'posts'),
                $this->faker->imageUrl(640, 480, 'posts'),
            ]),
            'post_type' => 'media',
        ]);
    }

    /**
     * Create a career update post
     */
    public function careerUpdate(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'career_update',
            'content' => 'Excited to announce my new role as '.$this->faker->jobTitle.' at '.$this->faker->company.'!',
            'metadata' => json_encode([
                'career_update' => [
                    'type' => 'new_job',
                    'company' => $this->faker->company,
                    'title' => $this->faker->jobTitle,
                ],
            ]),
        ]);
    }

    /**
     * Create an achievement post
     */
    public function achievement(): static
    {
        return $this->state(fn (array $attributes) => [
            'post_type' => 'achievement',
            'content' => 'Proud to share that I\'ve '.$this->faker->randomElement([
                'completed my certification in '.$this->faker->word,
                'won an award for '.$this->faker->word,
                'published an article about '.$this->faker->word,
                'spoke at a conference about '.$this->faker->word,
            ]),
            'metadata' => json_encode([
                'achievement' => [
                    'type' => $this->faker->randomElement(['certification', 'award', 'publication', 'speaking']),
                    'title' => $this->faker->sentence(3),
                ],
            ]),
        ]);
    }

    /**
     * Create a public post
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'circle_ids' => json_encode([]),
            'group_ids' => json_encode([]),
        ]);
    }

    /**
     * Create an old post
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-6 months', '-1 month'),
        ]);
    }

    /**
     * Create a recent post
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
