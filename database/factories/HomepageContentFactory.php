<?php

namespace Database\Factories;

use App\Models\HomepageContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HomepageContent>
 */
class HomepageContentFactory extends Factory
{
    protected $model = HomepageContent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sections = ['hero', 'social_proof', 'features', 'success_stories', 'pricing', 'trust_security'];
        $keys = ['headline', 'subtitle', 'cta_text', 'description', 'image_url'];
        $audiences = ['individual', 'institutional', 'both'];
        $statuses = ['draft', 'pending', 'approved', 'published'];

        return [
            'section' => $this->faker->randomElement($sections),
            'audience' => $this->faker->randomElement($audiences),
            'key' => $this->faker->randomElement($keys),
            'value' => $this->faker->paragraph(),
            'metadata' => [
                'image_url' => $this->faker->imageUrl(),
                'alt_text' => $this->faker->sentence(),
                'link_url' => $this->faker->url(),
            ],
            'status' => $this->faker->randomElement($statuses),
            'created_by' => User::factory(),
            'approved_by' => $this->faker->boolean(30) ? User::factory() : null,
            'approved_at' => $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
            'published_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    /**
     * Indicate that the content is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'published_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the content is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the content is for individual audience.
     */
    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience' => 'individual',
        ]);
    }

    /**
     * Indicate that the content is for institutional audience.
     */
    public function institutional(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience' => 'institutional',
        ]);
    }
}
