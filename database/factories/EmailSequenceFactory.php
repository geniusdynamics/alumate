<?php

namespace Database\Factories;

use App\Models\EmailSequence;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailSequence>
 */
class EmailSequenceFactory extends Factory
{
    protected $model = EmailSequence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $audienceTypes = EmailSequence::AUDIENCE_TYPES;
        $triggerTypes = EmailSequence::TRIGGER_TYPES;

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->optional(0.8)->sentence(),
            'audience_type' => $this->faker->randomElement($audienceTypes),
            'trigger_type' => $this->faker->randomElement($triggerTypes),
            'trigger_conditions' => $this->generateTriggerConditions(),
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
        ];
    }

    /**
     * Generate realistic trigger conditions based on trigger type
     */
    private function generateTriggerConditions(): array
    {
        $triggerType = $this->faker->randomElement(EmailSequence::TRIGGER_TYPES);

        return match ($triggerType) {
            'form_submission' => [
                'form_id' => $this->faker->uuid(),
                'form_type' => $this->faker->randomElement(['contact', 'newsletter', 'demo_request']),
                'required_fields' => $this->faker->randomElements(['email', 'first_name', 'company'], $this->faker->numberBetween(1, 3)),
            ],
            'page_visit' => [
                'page_url' => $this->faker->url(),
                'visit_count' => $this->faker->numberBetween(1, 5),
                'time_on_page' => $this->faker->numberBetween(30, 300), // seconds
            ],
            'behavior' => [
                'actions' => $this->faker->randomElements(
                    ['download', 'click_link', 'watch_video', 'scroll_depth'],
                    $this->faker->numberBetween(1, 3)
                ),
                'frequency' => $this->faker->numberBetween(1, 10),
                'time_window' => $this->faker->numberBetween(1, 30), // days
            ],
            'manual' => [
                'assigned_by' => $this->faker->name(),
                'reason' => $this->faker->sentence(),
            ],
            default => [],
        };
    }

    /**
     * Indicate that the sequence is for individual audience
     */
    public function forIndividuals(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => 'individual',
        ]);
    }

    /**
     * Indicate that the sequence is for institutional audience
     */
    public function forInstitutions(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => 'institutional',
        ]);
    }

    /**
     * Indicate that the sequence is for employers
     */
    public function forEmployers(): static
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => 'employer',
        ]);
    }

    /**
     * Indicate that the sequence is triggered by form submission
     */
    public function formTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'form_submission',
            'trigger_conditions' => [
                'form_id' => $this->faker->uuid(),
                'form_type' => $this->faker->randomElement(['contact', 'newsletter', 'demo_request']),
                'required_fields' => ['email'],
            ],
        ]);
    }

    /**
     * Indicate that the sequence is triggered by page visits
     */
    public function pageVisitTriggered(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_type' => 'page_visit',
            'trigger_conditions' => [
                'page_url' => $this->faker->url(),
                'visit_count' => $this->faker->numberBetween(1, 3),
                'time_on_page' => $this->faker->numberBetween(60, 180),
            ],
        ]);
    }

    /**
     * Indicate that the sequence is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the sequence is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create sequence for a specific tenant
     */
    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenant->id,
        ]);
    }
}