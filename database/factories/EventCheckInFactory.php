<?php

namespace Database\Factories;

use App\Models\EventCheckIn;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventCheckInFactory extends Factory
{
    protected $model = EventCheckIn::class;

    public function definition(): array
    {
        $methods = ['manual', 'qr_code', 'nfc', 'geofence'];
        
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'checked_in_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'check_in_method' => $this->faker->randomElement($methods),
            'location_data' => $this->faker->boolean(60) ? [
                'latitude' => $this->faker->latitude,
                'longitude' => $this->faker->longitude,
                'accuracy' => $this->faker->numberBetween(1, 50),
                'venue_area' => $this->faker->randomElement(['main_hall', 'lobby', 'registration_desk', 'parking']),
            ] : null,
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence : null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => 'manual',
            'notes' => 'Manual check-in by staff',
        ]);
    }

    public function qrCode(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => 'qr_code',
            'notes' => 'Checked in via QR code scan',
        ]);
    }

    public function nfc(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => 'nfc',
            'notes' => 'Checked in via NFC tap',
        ]);
    }

    public function geofence(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_in_method' => 'geofence',
            'location_data' => [
                'latitude' => $this->faker->latitude,
                'longitude' => $this->faker->longitude,
                'accuracy' => $this->faker->numberBetween(1, 20),
                'venue_area' => 'main_entrance',
            ],
            'notes' => 'Automatic check-in via geofence',
        ]);
    }

    public function withLocation(float $lat = null, float $lng = null): static
    {
        return $this->state(fn (array $attributes) => [
            'location_data' => [
                'latitude' => $lat ?? $this->faker->latitude,
                'longitude' => $lng ?? $this->faker->longitude,
                'accuracy' => $this->faker->numberBetween(1, 50),
                'venue_area' => $this->faker->randomElement(['main_hall', 'lobby', 'registration_desk']),
            ],
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}