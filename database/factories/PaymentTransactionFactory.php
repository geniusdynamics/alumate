<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\CampaignDonation;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        $gateways = ['stripe', 'paypal', 'bank_transfer'];
        $gateway = $this->faker->randomElement($gateways);
        $amount = $this->faker->randomFloat(2, 10, 1000);

        return [
            'donation_id' => CampaignDonation::factory(),
            'transaction_type' => 'payment',
            'gateway' => $gateway,
            'gateway_transaction_id' => $this->generateGatewayId($gateway),
            'amount' => $amount,
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed']),
            'gateway_response' => $this->generateGatewayResponse($gateway),
            'fee_amount' => $amount * 0.029 + 0.30, // Typical payment processing fee
            'fee_currency' => 'USD',
            'processed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'processed_at' => null,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => 'refund',
            'gateway_transaction_id' => 'refund_' . $this->faker->uuid,
        ]);
    }

    private function generateGatewayId(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'pi_' . $this->faker->regexify('[a-zA-Z0-9]{24}'),
            'paypal' => 'PAY-' . $this->faker->regexify('[A-Z0-9]{17}'),
            'bank_transfer' => 'bt_' . $this->faker->uuid,
            default => $this->faker->uuid,
        };
    }

    private function generateGatewayResponse(string $gateway): array
    {
        return match ($gateway) {
            'stripe' => [
                'id' => $this->generateGatewayId($gateway),
                'object' => 'payment_intent',
                'status' => 'succeeded',
                'payment_method' => 'pm_' . $this->faker->regexify('[a-zA-Z0-9]{24}'),
            ],
            'paypal' => [
                'id' => $this->generateGatewayId($gateway),
                'state' => 'approved',
                'payer' => [
                    'payment_method' => 'paypal',
                    'payer_info' => [
                        'email' => $this->faker->email,
                    ],
                ],
            ],
            'bank_transfer' => [
                'reference' => $this->faker->regexify('[A-Z0-9]{10}'),
                'status' => 'pending_verification',
            ],
            default => [],
        };
    }
}