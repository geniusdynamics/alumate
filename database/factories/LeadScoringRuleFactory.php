<?php

namespace Database\Factories;

use App\Models\LeadScoringRule;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadScoringRuleFactory extends Factory
{
    protected $model = LeadScoringRule::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::inRandomOrder()->first()?->id,
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'field' => $this->faker->randomElement(['email', 'phone', 'company_size', 'industry', 'source']),
            'operator' => $this->faker->randomElement(['equals', 'contains', 'greater_than', 'in']),
            'value' => $this->faker->word(),
            'points' => $this->faker->numberBetween(5, 50),
            'is_active' => true,
            'category' => $this->faker->randomElement(['demographic', 'behavioral', 'engagement']),
        ];
    }
}
