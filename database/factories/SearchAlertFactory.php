<?php

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\SearchAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchAlertFactory extends Factory
{
    protected $model = SearchAlert::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'saved_search_id' => SavedSearch::factory(),
            'frequency' => $this->faker->randomElement(['daily', 'weekly', 'monthly']),
            'is_active' => $this->faker->boolean(90),
            'last_sent_at' => $this->faker->optional()->dateTimeBetween('-1 week', 'now'),
            'next_send_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ];
    }
}
