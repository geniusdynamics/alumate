<?php

namespace Database\Factories;

use App\Models\RecurringDonation;
use App\Models\CampaignDonation;
use App\Models\FundraisingCampaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringDonationFactory extends Factory
{
    protected $model = RecurringDonation::class;

    public function definition(): array
    {
        $frequency = $this->faker->randomElement(['monthly', 'quarterly', 'yearly']);
        $startDate = $this->faker->dateTimeBetween('-1 year', '-1 month');
        
        return [
            'original_donation_id' => CampaignDonation::factory(),
            'campaign_id' => FundraisingCampaign::factory(),
            'donor_id' => User::factory(),
            'donor_name' => $this->faker->name,
            'donor_email' => $this->faker->email,
            'amount' => $this->faker->randomFloat(2, 25, 500),
            'currency' => 'USD',
            'frequency' => $frequency,
            'payment_method' => $this->faker->randomElement(['stripe', 'paypal']),
            'payment_data' => $this->generatePaymentData(),
            'status' => 'active',
            'next_payment_date' => $this->calculateNextPaymentDate($startDate, $frequency),
            'last_payment_date' => $startDate->format('Y-m-d'),
            'total_payments' => $this->faker->numberBetween(1, 12),
            'total_amount_collected' => $this->faker->randomFloat(2, 100, 5000),
            'failed_attempts' => 0,
            'started_at' => $startDate->format('Y-m-d'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'cancellation_reason' => $this->faker->randomElement([
                'Donor request',
                'Payment failure',
                'Card expired',
                'Insufficient funds',
            ]),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paused',
        ]);
    }

    public function dueForPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'next_payment_date' => $this->faker->dateTimeBetween('-1 week', 'today')->format('Y-m-d'),
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
        ]);
    }

    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'quarterly',
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'yearly',
        ]);
    }

    private function calculateNextPaymentDate(\DateTime $startDate, string $frequency): string
    {
        $nextDate = clone $startDate;
        
        return match ($frequency) {
            'monthly' => $nextDate->modify('+1 month')->format('Y-m-d'),
            'quarterly' => $nextDate->modify('+3 months')->format('Y-m-d'),
            'yearly' => $nextDate->modify('+1 year')->format('Y-m-d'),
            default => $nextDate->modify('+1 month')->format('Y-m-d'),
        };
    }

    private function generatePaymentData(): array
    {
        return [
            'stripe_customer_id' => 'cus_' . $this->faker->regexify('[a-zA-Z0-9]{14}'),
            'stripe_subscription_id' => 'sub_' . $this->faker->regexify('[a-zA-Z0-9]{14}'),
            'payment_method_id' => 'pm_' . $this->faker->regexify('[a-zA-Z0-9]{24}'),
        ];
    }
}