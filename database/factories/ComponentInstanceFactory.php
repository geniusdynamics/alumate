<?php

namespace Database\Factories;

use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComponentInstance>
 */
class ComponentInstanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'component_id' => Component::factory(),
            'page_type' => 'landing_page',
            'page_id' => fake()->numberBetween(1, 100),
            'position' => fake()->numberBetween(0, 10),
            'custom_config' => [],
        ];
    }

    /**
     * Create instance for a specific page
     */
    public function forPage(string $pageType, int $pageId): static
    {
        return $this->state(fn (array $attributes) => [
            'page_type' => $pageType,
            'page_id' => $pageId,
        ]);
    }

    /**
     * Create instance at specific position
     */
    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }

    /**
     * Create instance with custom configuration
     */
    public function withCustomConfig(array $config): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_config' => $config,
        ]);
    }

    /**
     * Create instance for hero component
     */
    public function forHeroComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->hero(),
            'custom_config' => [
                'headline' => 'Sample Headline',
                'subheading' => 'Sample subheading text',
                'cta_text' => 'Get Started',
                'background_type' => 'image',
            ],
        ]);
    }

    /**
     * Create instance for form component
     */
    public function forFormComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->form(),
            'custom_config' => [
                'submit_text' => 'Submit Form',
                'success_message' => 'Thank you for your submission!',
                'fields' => [
                    [
                        'type' => 'text',
                        'label' => 'Full Name',
                        'required' => true,
                    ],
                    [
                        'type' => 'email',
                        'label' => 'Email Address',
                        'required' => true,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create instance for testimonial component
     */
    public function forTestimonialComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->testimonial(),
            'custom_config' => [
                'layout' => 'single',
                'show_author_photo' => true,
                'show_company' => true,
            ],
        ]);
    }

    /**
     * Create instance for statistics component
     */
    public function forStatisticsComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->statistics(),
            'custom_config' => [
                'animation_type' => 'counter',
                'trigger_on_scroll' => true,
                'data_source' => 'manual',
            ],
        ]);
    }

    /**
     * Create instance for CTA component
     */
    public function forCtaComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->cta(),
            'custom_config' => [
                'style' => 'primary',
                'size' => 'medium',
                'track_conversions' => true,
            ],
        ]);
    }

    /**
     * Create instance for media component
     */
    public function forMediaComponent(): static
    {
        return $this->state(fn (array $attributes) => [
            'component_id' => Component::factory()->media(),
            'custom_config' => [
                'type' => 'image',
                'lazy_load' => true,
                'responsive' => true,
            ],
        ]);
    }

    /**
     * Create instance with no custom configuration
     */
    public function withoutCustomConfig(): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_config' => [],
        ]);
    }

    /**
     * Generate custom configuration based on component category
     */
    protected function generateCustomConfigForCategory(): array
    {
        return [
            'test_key' => 'test_value',
        ];
    }
}
