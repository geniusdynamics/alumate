<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $institutionTypes = ['University', 'College', 'Institute', 'Academy'];
        $institutionType = fake()->randomElement($institutionTypes);
        $institutionName = fake()->company().' '.$institutionType;

        return [
            'id' => fake()->unique()->slug(),
            'name' => $institutionName,
            'address' => fake()->address(),
            'contact_information' => json_encode([
                'email' => fake()->companyEmail(),
                'phone' => fake()->phoneNumber(),
                'website' => fake()->url(),
            ]),
            'plan' => fake()->randomElement(['basic', 'premium', 'enterprise']),
            'data' => json_encode([
                'name' => $institutionName, // Also store in data for compatibility
                'established' => fake()->numberBetween(1800, 2020),
                'type' => strtolower($institutionType),
                'accreditation' => fake()->words(3, true),
            ]),
        ];
    }

    public function university(): static
    {
        return $this->state(function (array $attributes) {
            $data = json_decode($attributes['data'] ?? '{}', true);
            $data['type'] = 'university';

            return [
                'name' => fake()->company().' University',
                'data' => json_encode($data),
            ];
        });
    }

    public function college(): static
    {
        return $this->state(function (array $attributes) {
            $data = json_decode($attributes['data'] ?? '{}', true);
            $data['type'] = 'college';

            return [
                'name' => fake()->company().' College',
                'data' => json_encode($data),
            ];
        });
    }

    public function withPlan(string $plan): static
    {
        return $this->state(function (array $attributes) use ($plan) {
            return [
                'plan' => $plan,
            ];
        });
    }
}
