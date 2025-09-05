<?php

namespace Database\Factories;

use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\SequenceEmail;
use App\Models\SequenceEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmailSend>
 */
class EmailSendFactory extends Factory
{
    protected $model = EmailSend::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = EmailSend::STATUSES;
        $sentAt = $this->faker->optional(0.8)->dateTimeBetween('-7 days', 'now'); // 80% sent

        return [
            'enrollment_id' => SequenceEnrollment::factory(),
            'sequence_email_id' => SequenceEmail::factory(),
            'lead_id' => Lead::factory(),
            'subject' => $this->faker->sentence(6),
            'status' => $this->faker->randomElement($statuses),
            'sent_at' => $sentAt,
            'delivered_at' => $sentAt ? $this->faker->optional(0.9)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +5 minutes') : null,
            'opened_at' => $this->faker->optional(0.4)->dateTimeBetween($sentAt ?? '-7 days', 'now'), // 40% opened
            'clicked_at' => $this->faker->optional(0.15)->dateTimeBetween($sentAt ?? '-7 days', 'now'), // 15% clicked
            'unsubscribed_at' => $this->faker->optional(0.02)->dateTimeBetween($sentAt ?? '-7 days', 'now'), // 2% unsubscribed
        ];
    }

    /**
     * Indicate that the email was queued
     */
    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'queued',
            'sent_at' => null,
            'delivered_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
            'unsubscribed_at' => null,
        ]);
    }

    /**
     * Indicate that the email was sent
     */
    public function sent(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', 'now');

            return [
                'status' => 'sent',
                'sent_at' => $sentAt,
                'delivered_at' => null,
                'opened_at' => null,
                'clicked_at' => null,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the email was delivered
     */
    public function delivered(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', '-1 hour');
            $deliveredAt = $this->faker->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +10 minutes');

            return [
                'status' => 'delivered',
                'sent_at' => $sentAt,
                'delivered_at' => $deliveredAt,
                'opened_at' => null,
                'clicked_at' => null,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the email was opened
     */
    public function opened(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', '-2 hours');
            $deliveredAt = $this->faker->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +10 minutes');
            $openedAt = $this->faker->dateTimeBetween($deliveredAt, $deliveredAt->format('Y-m-d H:i:s') . ' +2 hours');

            return [
                'status' => 'delivered',
                'sent_at' => $sentAt,
                'delivered_at' => $deliveredAt,
                'opened_at' => $openedAt,
                'clicked_at' => null,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the email was clicked
     */
    public function clicked(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', '-2 hours');
            $deliveredAt = $this->faker->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +10 minutes');
            $openedAt = $this->faker->dateTimeBetween($deliveredAt, $deliveredAt->format('Y-m-d H:i:s') . ' +1 hour');
            $clickedAt = $this->faker->dateTimeBetween($openedAt, $openedAt->format('Y-m-d H:i:s') . ' +30 minutes');

            return [
                'status' => 'delivered',
                'sent_at' => $sentAt,
                'delivered_at' => $deliveredAt,
                'opened_at' => $openedAt,
                'clicked_at' => $clickedAt,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the email was unsubscribed
     */
    public function unsubscribed(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', '-1 day');
            $deliveredAt = $this->faker->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +10 minutes');
            $unsubscribedAt = $this->faker->dateTimeBetween($deliveredAt, $deliveredAt->format('Y-m-d H:i:s') . ' +1 day');

            return [
                'status' => 'delivered',
                'sent_at' => $sentAt,
                'delivered_at' => $deliveredAt,
                'opened_at' => null,
                'clicked_at' => null,
                'unsubscribed_at' => $unsubscribedAt,
            ];
        });
    }

    /**
     * Indicate that the email bounced
     */
    public function bounced(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-7 days', '-1 hour');

            return [
                'status' => 'bounced',
                'sent_at' => $sentAt,
                'delivered_at' => null,
                'opened_at' => null,
                'clicked_at' => null,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the email failed
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->optional(0.5)->dateTimeBetween('-7 days', '-1 hour');

            return [
                'status' => 'failed',
                'sent_at' => $sentAt,
                'delivered_at' => null,
                'opened_at' => null,
                'clicked_at' => null,
                'unsubscribed_at' => null,
            ];
        });
    }

    /**
     * Create email send for a specific enrollment
     */
    public function forEnrollment(SequenceEnrollment $enrollment): static
    {
        return $this->state(fn (array $attributes) => [
            'enrollment_id' => $enrollment->id,
            'lead_id' => $enrollment->lead_id,
        ]);
    }

    /**
     * Create email send for a specific sequence email
     */
    public function forSequenceEmail(SequenceEmail $sequenceEmail): static
    {
        return $this->state(fn (array $attributes) => [
            'sequence_email_id' => $sequenceEmail->id,
        ]);
    }

    /**
     * Create email send for a specific lead
     */
    public function forLead(Lead $lead): static
    {
        return $this->state(fn (array $attributes) => [
            'lead_id' => $lead->id,
        ]);
    }

    /**
     * Create email send with specific subject
     */
    public function withSubject(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject' => $subject,
        ]);
    }

    /**
     * Create email send with recent activity
     */
    public function recent(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-1 day', 'now');

            return [
                'sent_at' => $sentAt,
                'delivered_at' => $this->faker->optional(0.9)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +5 minutes'),
                'opened_at' => $this->faker->optional(0.4)->dateTimeBetween($sentAt, 'now'),
                'clicked_at' => $this->faker->optional(0.15)->dateTimeBetween($sentAt, 'now'),
            ];
        });
    }

    /**
     * Create email send with old activity
     */
    public function old(): static
    {
        return $this->state(function (array $attributes) {
            $sentAt = $this->faker->dateTimeBetween('-30 days', '-7 days');

            return [
                'sent_at' => $sentAt,
                'delivered_at' => $this->faker->optional(0.9)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +5 minutes'),
                'opened_at' => $this->faker->optional(0.4)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +7 days'),
                'clicked_at' => $this->faker->optional(0.15)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +7 days'),
            ];
        });
    }
}