<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'description' => $this->faker->paragraph(3),
            'logo_url' => $this->faker->imageUrl(200, 200, 'business'),
            'website' => $this->faker->url(),
            'size' => $this->faker->randomElement(['startup', 'small', 'medium', 'large', 'enterprise']),
            'industry' => $this->faker->randomElement([
                'Technology',
                'Healthcare',
                'Finance',
                'Education',
                'Manufacturing',
                'Retail',
                'Consulting',
                'Media',
                'Real Estate',
                'Non-profit',
            ]),
            'location' => $this->faker->city().', '.$this->faker->stateAbbr(),
            'founded_year' => $this->faker->numberBetween(1950, 2020),
            'is_verified' => $this->faker->boolean(70), // 70% chance of being verified
        ];
    }

    /**
     * Indicate that the company is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }

    /**
     * Indicate that the company is a startup.
     */
    public function startup(): static
    {
        return $this->state(fn (array $attributes) => [
            'size' => 'startup',
            'founded_year' => $this->faker->numberBetween(2015, 2023),
        ]);
    }

    /**
     * Indicate that the company is in tech industry.
     */
    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'industry' => 'Technology',
            'name' => $this->faker->randomElement([
                'TechCorp',
                'InnovateLabs',
                'DataSoft',
                'CloudTech',
                'DevSolutions',
                'CodeCraft',
                'ByteWorks',
                'PixelForge',
            ]).' '.$this->faker->randomElement(['Inc.', 'LLC', 'Corp', 'Technologies']),
        ]);
    }
}
