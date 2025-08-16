<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $leadTypes = ['individual', 'institutional', 'enterprise'];
        $sources = ['homepage', 'demo_request', 'trial_signup', 'contact_form', 'referral', 'organic', 'paid_ads'];
        $statuses = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'lead_type' => $this->faker->randomElement($leadTypes),
            'source' => $this->faker->randomElement($sources),
            'status' => $this->faker->randomElement($statuses),
            'score' => $this->faker->numberBetween(0, 100),
            'priority' => $this->faker->randomElement($priorities),
            'utm_data' => [
                'utm_source' => $this->faker->randomElement(['google', 'facebook', 'linkedin', 'twitter']),
                'utm_medium' => $this->faker->randomElement(['cpc', 'organic', 'email', 'social']),
                'utm_campaign' => $this->faker->words(3, true),
            ],
            'form_data' => [
                'interest' => $this->faker->randomElement(['demo', 'trial', 'pricing', 'features']),
                'company_size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-1000', '1000+']),
            ],
            'behavioral_data' => [
                'page_views' => $this->faker->numberBetween(1, 20),
                'time_on_site' => $this->faker->numberBetween(60, 1800),
                'downloads' => $this->faker->numberBetween(0, 5),
            ],
            'notes' => $this->faker->optional()->paragraph(),
            'assigned_to' => $this->faker->boolean(70) ? User::factory() : null,
            'last_contacted_at' => $this->faker->optional(0.6)->dateTimeBetween('-30 days', 'now'),
            'qualified_at' => $this->faker->optional(0.3)->dateTimeBetween('-15 days', 'now'),
            'crm_id' => $this->faker->optional(0.4)->uuid(),
            'synced_at' => $this->faker->optional(0.4)->dateTimeBetween('-7 days', 'now'),
        ];
    }

    /**
     * Indicate that the lead is qualified.
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'qualified',
            'qualified_at' => $this->faker->dateTimeBetween('-15 days', 'now'),
            'score' => $this->faker->numberBetween(60, 100),
        ]);
    }

    /**
     * Indicate that the lead is hot.
     */
    public function hot(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
            'score' => $this->faker->numberBetween(80, 100),
        ]);
    }

    /**
     * Indicate that the lead is enterprise.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_type' => 'enterprise',
            'company' => $this->faker->company().' Corp',
            'form_data' => array_merge($attributes['form_data'] ?? [], [
                'company_size' => $this->faker->randomElement(['201-1000', '1000+']),
                'budget' => $this->faker->randomElement(['50k_100k', '100k_500k', 'over_500k']),
            ]),
        ]);
    }

    /**
     * Indicate that the lead needs follow-up.
     */
    public function needsFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_contacted_at' => $this->faker->dateTimeBetween('-14 days', '-8 days'),
            'status' => $this->faker->randomElement(['contacted', 'qualified']),
        ]);
    }

    /**
     * Indicate that the lead is unassigned.
     */
    public function unassigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => null,
        ]);
    }
}
