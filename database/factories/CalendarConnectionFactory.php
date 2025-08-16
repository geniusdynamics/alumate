<?php

namespace Database\Factories;

use App\Models\CalendarConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalendarConnection>
 */
class CalendarConnectionFactory extends Factory
{
    protected $model = CalendarConnection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => $this->faker->randomElement(['google', 'outlook', 'apple', 'caldav']),
            'credentials' => [
                'access_token' => $this->faker->sha256(),
                'refresh_token' => $this->faker->sha256(),
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ],
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'last_sync_at' => $this->faker->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'sync_status' => $this->faker->randomElement(['success', 'failed', 'pending', null]),
            'sync_error' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the connection is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'sync_status' => 'success',
            'last_sync_at' => now()->subHours(rand(1, 24)),
            'sync_error' => null,
        ]);
    }

    /**
     * Indicate that the connection is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'sync_status' => null,
            'last_sync_at' => null,
            'sync_error' => null,
        ]);
    }

    /**
     * Indicate that the connection has failed sync.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'sync_status' => 'failed',
            'sync_error' => 'Authentication failed: Invalid credentials',
            'last_sync_at' => now()->subHours(rand(1, 48)),
        ]);
    }

    /**
     * Create a Google Calendar connection.
     */
    public function google(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'google',
            'credentials' => [
                'access_token' => 'ya29.'.$this->faker->sha256(),
                'refresh_token' => '1//'.$this->faker->sha256(),
                'expires_in' => 3600,
                'token_type' => 'Bearer',
                'scope' => 'https://www.googleapis.com/auth/calendar',
            ],
        ]);
    }

    /**
     * Create an Outlook Calendar connection.
     */
    public function outlook(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'outlook',
            'credentials' => [
                'access_token' => 'EwB4A'.$this->faker->sha256(),
                'refresh_token' => 'M.R3_'.$this->faker->sha256(),
                'expires_in' => 3600,
                'token_type' => 'Bearer',
                'scope' => 'https://graph.microsoft.com/calendars.readwrite',
            ],
        ]);
    }

    /**
     * Create an Apple Calendar connection.
     */
    public function apple(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'apple',
            'credentials' => [
                'username' => $this->faker->email(),
                'password' => $this->faker->password(),
                'server_url' => 'https://caldav.icloud.com',
                'principal_url' => '/principals/'.$this->faker->uuid(),
            ],
        ]);
    }

    /**
     * Create a CalDAV connection.
     */
    public function caldav(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'caldav',
            'credentials' => [
                'username' => $this->faker->email(),
                'password' => $this->faker->password(),
                'server_url' => $this->faker->url(),
                'calendar_url' => $this->faker->url().'/calendar/',
            ],
        ]);
    }
}
