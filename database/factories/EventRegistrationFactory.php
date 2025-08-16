<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    public function definition(): array
    {
        $statuses = ['registered', 'waitlisted', 'cancelled', 'attended', 'no_show'];
        $status = $this->faker->randomElement($statuses);

        $registeredAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $checkedInAt = null;
        $cancelledAt = null;

        if ($status === 'attended') {
            $checkedInAt = $this->faker->dateTimeBetween($registeredAt, 'now');
        } elseif ($status === 'cancelled') {
            $cancelledAt = $this->faker->dateTimeBetween($registeredAt, 'now');
        }

        $guestsCount = $this->faker->numberBetween(0, 3);
        $guestDetails = [];

        for ($i = 0; $i < $guestsCount; $i++) {
            $guestDetails[] = [
                'name' => $this->faker->name,
                'email' => $this->faker->email,
            ];
        }

        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => $status,
            'registered_at' => $registeredAt,
            'checked_in_at' => $checkedInAt,
            'guests_count' => $guestsCount,
            'guest_details' => $guestsCount > 0 ? $guestDetails : null,
            'special_requirements' => $this->faker->boolean(30) ? $this->faker->sentence : null,
            'registration_data' => [
                'how_heard' => $this->faker->randomElement(['email', 'social_media', 'friend', 'website', 'other']),
                'expectations' => $this->faker->boolean(50) ? $this->faker->paragraph : null,
            ],
            'amount_paid' => $this->faker->boolean(30) ? $this->faker->randomFloat(2, 10, 100) : null,
            'payment_status' => $this->faker->boolean(30) ? $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']) : null,
            'payment_reference' => $this->faker->boolean(30) ? $this->faker->uuid : null,
            'cancellation_reason' => $status === 'cancelled' ? $this->faker->sentence : null,
            'cancelled_at' => $cancelledAt,
        ];
    }

    public function registered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'registered',
            'checked_in_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function waitlisted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'waitlisted',
            'checked_in_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function attended(): static
    {
        return $this->state(function (array $attributes) {
            $registeredAt = $attributes['registered_at'] ?? $this->faker->dateTimeBetween('-1 month', '-1 day');

            return [
                'status' => 'attended',
                'checked_in_at' => $this->faker->dateTimeBetween($registeredAt, 'now'),
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            $registeredAt = $attributes['registered_at'] ?? $this->faker->dateTimeBetween('-1 month', '-1 day');

            return [
                'status' => 'cancelled',
                'checked_in_at' => null,
                'cancelled_at' => $this->faker->dateTimeBetween($registeredAt, 'now'),
                'cancellation_reason' => $this->faker->sentence,
            ];
        });
    }

    public function noShow(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'no_show',
            'checked_in_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function withGuests(int $count = 2): static
    {
        return $this->state(function (array $attributes) use ($count) {
            $guestDetails = [];

            for ($i = 0; $i < $count; $i++) {
                $guestDetails[] = [
                    'name' => $this->faker->name,
                    'email' => $this->faker->email,
                ];
            }

            return [
                'guests_count' => $count,
                'guest_details' => $guestDetails,
            ];
        });
    }

    public function withPayment(?float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'amount_paid' => $amount ?? $this->faker->randomFloat(2, 10, 100),
            'payment_status' => 'completed',
            'payment_reference' => $this->faker->uuid,
        ]);
    }

    public function withSpecialRequirements(?string $requirements = null): static
    {
        return $this->state(fn (array $attributes) => [
            'special_requirements' => $requirements ?? $this->faker->paragraph,
        ]);
    }
}
