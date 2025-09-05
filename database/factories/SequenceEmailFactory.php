<?php

namespace Database\Factories;

use App\Models\EmailSequence;
use App\Models\SequenceEmail;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SequenceEmail>
 */
class SequenceEmailFactory extends Factory
{
    protected $model = SequenceEmail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sequence_id' => EmailSequence::factory(),
            'template_id' => Template::factory()->email(),
            'subject_line' => $this->faker->sentence(8),
            'delay_hours' => $this->faker->numberBetween(0, 168), // 0 to 7 days
            'send_order' => $this->faker->numberBetween(0, 10),
            'trigger_conditions' => $this->generateTriggerConditions(),
        ];
    }

    /**
     * Generate realistic trigger conditions for sequence emails
     */
    private function generateTriggerConditions(): array
    {
        $conditions = [];

        // Sometimes add previous email conditions
        if ($this->faker->boolean(30)) {
            $conditions['previous_email_opened'] = $this->faker->boolean(70);
            $conditions['previous_email_clicked'] = $this->faker->boolean(40);
        }

        // Sometimes add time-based conditions
        if ($this->faker->boolean(20)) {
            $conditions['min_time_since_previous'] = $this->faker->numberBetween(1, 24); // hours
        }

        // Sometimes add user behavior conditions
        if ($this->faker->boolean(15)) {
            $conditions['user_actions'] = $this->faker->randomElements(
                ['page_visit', 'form_submission', 'download'],
                $this->faker->numberBetween(1, 3)
            );
        }

        return $conditions;
    }

    /**
     * Indicate that this is the first email in the sequence
     */
    public function firstInSequence(): static
    {
        return $this->state(fn (array $attributes) => [
            'delay_hours' => 0,
            'send_order' => 0,
            'trigger_conditions' => [],
        ]);
    }

    /**
     * Indicate that this is a follow-up email
     */
    public function followUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'delay_hours' => $this->faker->numberBetween(24, 72), // 1-3 days
            'send_order' => $this->faker->numberBetween(1, 5),
            'trigger_conditions' => [
                'previous_email_opened' => true,
                'min_time_since_previous' => 24,
            ],
        ]);
    }

    /**
     * Indicate that this email has a long delay
     */
    public function delayed(): static
    {
        return $this->state(fn (array $attributes) => [
            'delay_hours' => $this->faker->numberBetween(72, 168), // 3-7 days
        ]);
    }

    /**
     * Create sequence email for a specific sequence
     */
    public function forSequence(EmailSequence $sequence): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence_id' => $sequence->id,
        ]);
    }

    /**
     * Create sequence email with a specific template
     */
    public function withTemplate(Template $template): static
    {
        return $this->state(fn (array $attributes) => [
            'template_id' => $template->id,
        ]);
    }

    /**
     * Create sequence email with specific subject line
     */
    public function withSubject(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_line' => $subject,
        ]);
    }

    /**
     * Create sequence email with specific send order
     */
    public function withSendOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'send_order' => $order,
        ]);
    }

    /**
     * Create sequence email with specific delay
     */
    public function withDelay(int $hours): static
    {
        return $this->state(fn (array $attributes) => [
            'delay_hours' => $hours,
        ]);
    }

    /**
     * Create sequence email with trigger conditions requiring previous email interaction
     */
    public function requiresPreviousInteraction(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_conditions' => [
                'previous_email_opened' => true,
                'min_time_since_previous' => $this->faker->numberBetween(1, 48),
            ],
        ]);
    }

    /**
     * Create sequence email with time-based trigger conditions
     */
    public function timeBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_conditions' => [
                'min_time_since_previous' => $this->faker->numberBetween(24, 72),
                'max_time_since_previous' => $this->faker->numberBetween(168, 336), // 1-2 weeks
            ],
        ]);
    }
}