<?php

namespace Database\Factories;

use App\Models\LeadScoringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeadScoringRule>
 */
class LeadScoringRuleFactory extends Factory
{
    protected $model = LeadScoringRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $triggerTypes = ['form_submission', 'page_visit', 'email_open', 'email_click', 'demo_request', 'trial_signup', 'company_size', 'job_title', 'industry'];

        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'trigger_type' => $this->faker->randomElement($triggerTypes),
            'conditions' => $this->generateConditions(),
            'points' => $this->faker->numberBetween(-20, 50),
            'is_active' => $this->faker->boolean(80),
            'priority' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Generate sample conditions based on trigger type
     */
    private function generateConditions(): array
    {
        $conditionTypes = [
            'form_submission' => [
                'lead_type' => $this->faker->randomElement(['individual', 'institutional', 'enterprise']),
                'source' => $this->faker->randomElement(['homepage', 'demo_request', 'trial_signup']),
            ],
            'page_visit' => [
                'page' => $this->faker->randomElement(['pricing', 'features', 'demo', 'contact']),
                'time_on_page' => $this->faker->numberBetween(30, 300),
            ],
            'company_size' => [
                'size' => $this->faker->randomElement(['1-10', '11-50', '51-200', '201-1000', '1000+']),
            ],
            'job_title' => [
                'title_contains' => $this->faker->randomElement(['CEO', 'CTO', 'Manager', 'Director', 'VP']),
            ],
        ];

        $triggerType = $this->faker->randomElement(array_keys($conditionTypes));

        return $conditionTypes[$triggerType];
    }

    /**
     * Indicate that the rule is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the rule has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $this->faker->numberBetween(8, 10),
        ]);
    }
}
