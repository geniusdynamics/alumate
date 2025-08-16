<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailCampaign>
 */
class EmailCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'type' => $this->faker->randomElement(['newsletter', 'announcement', 'event', 'fundraising', 'engagement']),
            'status' => $this->faker->randomElement(['draft', 'scheduled', 'sent']),
            'provider' => $this->faker->randomElement(['internal', 'mailchimp', 'constant_contact', 'mautic']),
            'total_recipients' => $this->faker->numberBetween(10, 1000),
            'delivered_count' => $this->faker->numberBetween(0, 900),
            'opened_count' => $this->faker->numberBetween(0, 500),
            'clicked_count' => $this->faker->numberBetween(0, 200),
            'open_rate' => $this->faker->randomFloat(2, 0, 100),
            'click_rate' => $this->faker->randomFloat(2, 0, 50),
            'is_ab_test' => false,
            'created_by' => $user->id,
            'tenant_id' => $user->tenant_id,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    public function newsletter(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'newsletter',
            'name' => 'Alumni Newsletter - '.$this->faker->monthName(),
        ]);
    }

    public function abTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_ab_test' => true,
            'ab_test_variant' => $this->faker->randomElement(['A', 'B']),
        ]);
    }
}
