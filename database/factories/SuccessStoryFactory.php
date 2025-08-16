<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SuccessStory>
 */
class SuccessStoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Education', 'Marketing',
            'Engineering', 'Consulting', 'Non-profit', 'Government', 'Media',
        ];

        $achievementTypes = [
            'promotion', 'award', 'startup', 'publication', 'patent',
            'leadership', 'community_service', 'innovation', 'research', 'entrepreneurship',
        ];

        $tags = [
            'leadership', 'innovation', 'technology', 'social_impact', 'entrepreneurship',
            'research', 'community', 'diversity', 'mentorship', 'global',
        ];

        $demographics = [
            'gender' => fake()->randomElement(['male', 'female', 'non-binary']),
            'ethnicity' => fake()->randomElement(['asian', 'black', 'hispanic', 'white', 'other']),
            'first_generation' => fake()->boolean(30),
            'international' => fake()->boolean(20),
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'title' => fake()->sentence(6),
            'summary' => fake()->paragraph(2),
            'content' => fake()->paragraphs(5, true),
            'featured_image' => 'success-stories/featured/'.fake()->uuid().'.jpg',
            'media_urls' => fake()->randomElements([
                'success-stories/media/'.fake()->uuid().'.jpg',
                'success-stories/media/'.fake()->uuid().'.mp4',
                'success-stories/media/'.fake()->uuid().'.pdf',
            ], fake()->numberBetween(0, 3)),
            'industry' => fake()->randomElement($industries),
            'achievement_type' => fake()->randomElement($achievementTypes),
            'current_role' => fake()->jobTitle(),
            'current_company' => fake()->company(),
            'graduation_year' => fake()->year($max = 'now'),
            'degree_program' => fake()->randomElement([
                'Computer Science', 'Business Administration', 'Engineering',
                'Medicine', 'Law', 'Education', 'Psychology', 'Marketing',
            ]),
            'tags' => fake()->randomElements($tags, fake()->numberBetween(2, 5)),
            'demographics' => $demographics,
            'status' => fake()->randomElement(['draft', 'published', 'featured']),
            'is_featured' => fake()->boolean(20),
            'allow_social_sharing' => fake()->boolean(90),
            'view_count' => fake()->numberBetween(0, 1000),
            'share_count' => fake()->numberBetween(0, 100),
            'like_count' => fake()->numberBetween(0, 200),
            'published_at' => fake()->optional(0.8)->dateTimeBetween('-2 years', 'now'),
            'featured_at' => fake()->optional(0.2)->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the success story is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the success story is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'featured',
            'is_featured' => true,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'featured_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the success story is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
            'featured_at' => null,
        ]);
    }
}
