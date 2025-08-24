<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'query' => $this->faker->words(2, true),
            'filters' => [
                'location' => $this->faker->city(),
                'graduation_year' => $this->faker->numberBetween(2010, 2023),
                'industry' => $this->faker->randomElement(['Technology', 'Finance', 'Healthcare', 'Education'])
            ],
            'result_count' => $this->faker->numberBetween(5, 100),
            'is_active' => $this->faker->boolean(80),
            'last_executed_at' => $this->faker->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
