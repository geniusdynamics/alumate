<?php

namespace Database\Factories;

use App\Models\TaxReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxReceiptFactory extends Factory
{
    protected $model = TaxReceipt::class;

    public function definition(): array
    {
        $taxYear = $this->faker->numberBetween(2020, 2024);
        $donationCount = $this->faker->numberBetween(1, 8);
        $donations = [];
        $totalAmount = 0;

        // Generate fake donation data
        for ($i = 0; $i < $donationCount; $i++) {
            $amount = $this->faker->randomFloat(2, 25, 500);
            $totalAmount += $amount;
            
            $donations[] = [
                'donation_id' => $this->faker->numberBetween(1, 1000),
                'amount' => $amount,
                'date' => $this->faker->dateTimeBetween("{$taxYear}-01-01", "{$taxYear}-12-31")->format('Y-m-d'),
                'campaign' => $this->faker->sentence(3),
            ];
        }

        return [
            'receipt_number' => str_pad($this->faker->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'donor_id' => User::factory(),
            'donor_name' => $this->faker->name,
            'donor_email' => $this->faker->email,
            'donor_address' => [
                $this->faker->streetAddress,
                $this->faker->city . ', ' . $this->faker->stateAbbr . ' ' . $this->faker->postcode,
            ],
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'tax_year' => $taxYear,
            'receipt_date' => $this->faker->dateTimeBetween("{$taxYear}-12-31", 'now'),
            'donations' => $donations,
            'status' => 'generated',
            'pdf_path' => "tax-receipts/{$taxYear}/" . str_pad($this->faker->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT) . '.pdf',
            'generated_at' => $this->faker->dateTimeBetween("{$taxYear}-12-31", 'now'),
        ];
    }

    public function generated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'generated',
            'sent_at' => null,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    public function downloaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'downloaded',
        ]);
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'tax_year' => $year,
            'receipt_date' => $this->faker->dateTimeBetween("{$year}-12-31", 'now'),
            'generated_at' => $this->faker->dateTimeBetween("{$year}-12-31", 'now'),
        ]);
    }
}