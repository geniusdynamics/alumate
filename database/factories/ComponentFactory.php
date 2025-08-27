<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Component>
 */
class ComponentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = Component::CATEGORIES;
        $category = $categories[array_rand($categories)];
        $name = 'Test Component '.rand(1, 1000);

        return [
            'tenant_id' => Tenant::factory(),
            'theme_id' => null, // Will be set when needed
            'name' => $name,
            'slug' => Str::slug($name),
            'category' => $category,
            'type' => 'test-type',
            'description' => 'Test component description',
            'config' => $this->getConfigForCategory($category),
            'metadata' => [
                'created_by' => 'Test User',
                'tags' => ['tag1', 'tag2', 'tag3'],
                'difficulty' => ['easy', 'medium', 'hard'][array_rand(['easy', 'medium', 'hard'])],
            ],
            'version' => ['1.0.0', '1.1.0', '2.0.0'][array_rand(['1.0.0', '1.1.0', '2.0.0'])],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the component is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the component is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a hero component
     */
    public function hero(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'hero',
            'type' => ['individual', 'institution', 'employer'][array_rand(['individual', 'institution', 'employer'])],
            'config' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'cta_text' => ['Get Started', 'Learn More', 'Join Now'][array_rand(['Get Started', 'Learn More', 'Join Now'])],
                'cta_url' => 'https://example.com',
                'background_type' => ['image', 'video', 'gradient'][array_rand(['image', 'video', 'gradient'])],
                'background_media' => 'https://example.com/image.jpg',
                'show_statistics' => false,
            ],
        ]);
    }

    /**
     * Create a form component
     */
    public function form(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'forms',
            'type' => ['signup', 'contact', 'demo_request'][array_rand(['signup', 'contact', 'demo_request'])],
            'config' => [
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
                'submit_text' => 'Submit',
                'success_message' => 'Thank you for your submission!',
                'validation_rules' => [],
                'crm_integration' => false,
            ],
        ]);
    }

    /**
     * Create a testimonial component
     */
    public function testimonial(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'testimonials',
            'type' => ['single', 'carousel', 'grid'][array_rand(['single', 'carousel', 'grid'])],
            'config' => [
                'layout' => ['single', 'carousel', 'grid'][array_rand(['single', 'carousel', 'grid'])],
                'show_author_photo' => true,
                'show_company' => true,
                'show_graduation_year' => false,
                'filter_by_audience' => false,
            ],
        ]);
    }

    /**
     * Create a statistics component
     */
    public function statistics(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'statistics',
            'type' => ['counter', 'progress', 'chart'][array_rand(['counter', 'progress', 'chart'])],
            'config' => [
                'animation_type' => ['counter', 'progress', 'chart'][array_rand(['counter', 'progress', 'chart'])],
                'trigger_on_scroll' => true,
                'data_source' => ['manual', 'api'][array_rand(['manual', 'api'])],
                'format_numbers' => true,
            ],
        ]);
    }

    /**
     * Create a CTA component
     */
    public function cta(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'ctas',
            'type' => ['button', 'banner', 'inline'][array_rand(['button', 'banner', 'inline'])],
            'config' => [
                'style' => ['primary', 'secondary', 'outline', 'text'][array_rand(['primary', 'secondary', 'outline', 'text'])],
                'size' => ['small', 'medium', 'large'][array_rand(['small', 'medium', 'large'])],
                'track_conversions' => true,
                'utm_parameters' => [
                    'utm_source' => 'website',
                    'utm_medium' => 'cta',
                    'utm_campaign' => 'test-campaign',
                ],
            ],
        ]);
    }

    /**
     * Create a media component
     */
    public function media(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'media',
            'type' => ['image', 'video', 'gallery'][array_rand(['image', 'video', 'gallery'])],
            'config' => [
                'type' => ['image', 'video', 'gallery'][array_rand(['image', 'video', 'gallery'])],
                'lazy_load' => true,
                'responsive' => true,
                'accessibility_alt' => 'Test alt text',
            ],
        ]);
    }

    /**
     * Get configuration for specific category
     */
    private function getConfigForCategory(string $category): array
    {
        return match ($category) {
            'hero' => [
                'headline' => 'Test Headline',
                'subheading' => 'Test Subheading',
                'cta_text' => 'Get Started',
                'cta_url' => 'https://example.com',
                'background_type' => 'image',
                'show_statistics' => false,
            ],
            'forms' => [
                'fields' => [],
                'submit_text' => 'Submit',
                'success_message' => 'Thank you!',
                'crm_integration' => false,
            ],
            'testimonials' => [
                'layout' => 'single',
                'show_author_photo' => true,
                'show_company' => true,
            ],
            'statistics' => [
                'animation_type' => 'counter',
                'trigger_on_scroll' => true,
                'data_source' => 'manual',
            ],
            'ctas' => [
                'style' => 'primary',
                'size' => 'medium',
                'track_conversions' => true,
            ],
            'media' => [
                'type' => 'image',
                'lazy_load' => true,
                'responsive' => true,
            ],
            default => [],
        };
    }
}
