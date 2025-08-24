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
        $institutionType = $this->faker->randomElement($institutionTypes);
        $institutionName = $this->faker->company.' '.$institutionType;

        return [
            'id' => $this->faker->unique()->slug(),
            'name' => $institutionName,
            'address' => $this->faker->address,
            'contact_information' => json_encode([
                'email' => $this->faker->companyEmail,
                'phone' => $this->faker->phoneNumber,
                'website' => $this->faker->url,
            ]),
            'plan' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
            'data' => json_encode([
                'name' => $institutionName, // Also store in data for compatibility
                'established' => $this->faker->numberBetween(1800, 2020),
                'type' => strtolower($institutionType),
                'accreditation' => $this->faker->words(3, true),
            ]),
        ];
    }

    public function university(): static
    {
        return $this->state(function (array $attributes) {
            $data = json_decode($attributes['data'] ?? '{}', true);
            $data['type'] = 'university';
            
            return [
                'name' => $this->faker->company.' University',
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
                'name' => $this->faker->company.' College',
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
