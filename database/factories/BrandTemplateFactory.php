<?php

namespace Database\Factories;

use App\Models\BrandTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BrandTemplate>
 */
class BrandTemplateFactory extends Factory
{
    protected $model = BrandTemplate::class;

    public function definition(): array
    {
        $templateTypes = [
            'Corporate Blue', 'Academic Green', 'Tech Dark', 'Creative Purple',
            'Medical White', 'Startup Gradient', 'Professional Gray', 'Bold Red',
        ];

        $selectedName = fake()->randomElement($templateTypes);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $selectedName . ' Template',
            'description' => 'Pre-configured brand template with ' . strtolower($selectedName) . ' color scheme and typography.',
            'primary_font' => fake()->randomElement(['Inter', 'Roboto', 'Open Sans', 'Lato']),
            'secondary_font' => fake()->randomElement(['Poppins', 'Source Serif Pro', 'Playfair Display']),
            'logo_variant' => fake()->randomElement(['primary', 'secondary', 'minimal']),
            'tags' => fake()->randomElements(['corporate', 'academic', 'creative', 'professional', 'modern', 'classic'], rand(2, 4)),
            'is_default' => false,
            'usage_count' => fake()->numberBetween(0, 1000),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }

    public function popular(): static
    {
        return $this->state(['usage_count' => fake()->numberBetween(500, 2000)]);
    }

    public function forTenant($tenantId): static
    {
        return $this->state(['tenant_id' => $tenantId]);
    }
}