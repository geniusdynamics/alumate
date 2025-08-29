<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasVideo = fake()->boolean(30); // 30% chance of having video
        $industries = [
            'Technology', 'Healthcare', 'Finance', 'Education', 'Marketing',
            'Engineering', 'Sales', 'Consulting', 'Non-profit', 'Government',
            'Manufacturing', 'Retail', 'Media', 'Real Estate', 'Legal'
        ];

        return [
            'tenant_id' => 'default', // Will be overridden in tests
            'author_name' => fake()->name(),
            'author_title' => fake()->jobTitle(),
            'author_company' => fake()->company(),
            'author_photo' => fake()->imageUrl(200, 200, 'people'),
            'graduation_year' => fake()->numberBetween(2000, 2024),
            'industry' => fake()->randomElement($industries),
            'audience_type' => fake()->randomElement(Testimonial::AUDIENCE_TYPES),
            'content' => fake()->paragraph(3),
            'video_url' => $hasVideo ? fake()->url() : null,
            'video_thumbnail' => $hasVideo ? fake()->imageUrl(640, 360, 'business') : null,
            'rating' => fake()->numberBetween(4, 5), // Most testimonials are positive
            'status' => fake()->randomElement(['approved', 'pending']),
            'featured' => fake()->boolean(20), // 20% chance of being featured
            'view_count' => fake()->numberBetween(0, 1000),
            'click_count' => fake()->numberBetween(0, 100),
            'conversion_rate' => fake()->randomFloat(4, 0, 0.5),
            'metadata' => [
                'source' => fake()->randomElement(['website', 'email', 'survey', 'interview']),
                'tags' => fake()->randomElements(['success', 'career-growth', 'networking', 'education'], 2),
                'location' => fake()->city(),
            ],
        ];
    }

    /**
     * Indicate that the testimonial is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Indicate that the testimonial is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the testimonial is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * Indicate that the testimonial is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * Indicate that the testimonial has video content.
     */
    public function withVideo(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_url' => fake()->url(),
            'video_thumbnail' => fake()->imageUrl(640, 360, 'business'),
        ]);
    }

    /**
     * Indicate that the testimonial is text-only.
     */
    public function textOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_url' => null,
            'video_thumbnail' => null,
        ]);
    }

    /**
     * Create testimonial for specific audience type.
     */
    public function forAudience(string $audienceType): static
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => $audienceType,
        ]);
    }

    /**
     * Create testimonial for specific industry.
     */
    public function forIndustry(string $industry): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => $industry,
        ]);
    }

    /**
     * Create testimonial for specific graduation year.
     */
    public function forGraduationYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'graduation_year' => $year,
        ]);
    }

    /**
     * Create high-performing testimonial.
     */
    public function highPerforming(): static
    {
        return $this->state(fn (array $attributes) => [
            'view_count' => fake()->numberBetween(500, 2000),
            'click_count' => fake()->numberBetween(100, 500),
            'conversion_rate' => fake()->randomFloat(4, 0.2, 0.8),
            'featured' => true,
        ]);
    }
}