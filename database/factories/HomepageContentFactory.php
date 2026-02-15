<?php

namespace Database\Factories;

use App\Models\HomepageContent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HomepageContentFactory extends Factory
{
    protected $model = HomepageContent::class;

    public function definition(): array
    {
        $tenantId = Tenant::inRandomOrder()->first()?->id;
        
        return [
            'tenant_id' => $tenantId,
            'section' => $this->faker->randomElement(['hero', 'features', 'cta', 'testimonials', 'footer']),
            'key' => $this->faker->randomElement(['headline', 'subheadline', 'button_text', 'background_image']),
            'content' => [
                'text' => $this->faker->sentence(),
                'enabled' => true,
            ],
            'audience' => $this->faker->randomElement(['individual', 'institution', 'employer', 'all']),
            'status' => $this->faker->randomElement(['draft', 'published']),
            'published_at' => $this->faker->dateTime(),
            'version' => 1,
            'metadata' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience' => 'individual',
        ]);
    }

    public function institutional(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience' => 'institution',
        ]);
    }
}
