<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'institution_id' => null,
            'profile_data' => [
                'bio' => fake()->optional()->paragraph(),
                'location' => fake()->optional()->city(),
                'website' => fake()->optional()->url(),
                'linkedin' => fake()->optional()->url(),
                'twitter' => fake()->optional()->userName(),
            ],
            'preferences' => [
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true,
                ],
                'privacy' => [
                    'profile_visible' => true,
                    'show_email' => false,
                    'show_phone' => false,
                ],
                'dashboard' => [
                    'theme' => 'light',
                    'compact_mode' => false,
                ],
            ],
            'status' => 'active',
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'last_activity_at' => fake()->optional()->dateTimeBetween('-7 days', 'now'),
            'two_factor_enabled' => false,
            'timezone' => fake()->randomElement(['UTC', 'America/New_York', 'Europe/London', 'Asia/Tokyo']),
            'language' => fake()->randomElement(['en', 'es', 'fr', 'de']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with a specific role.
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function ($user) use ($role) {
            $user->assignRole($role);
        });
    }

    /**
     * Create a super admin user.
     */
    public function superAdmin(): static
    {
        return $this->state([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ])->withRole('super-admin');
    }

    /**
     * Create an institution admin user.
     */
    public function institutionAdmin(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'institution_id' => Tenant::factory(),
            ];
        })->withRole('institution-admin');
    }

    /**
     * Create an employer user.
     */
    public function employer(): static
    {
        return $this->withRole('employer');
    }

    /**
     * Create a graduate user.
     */
    public function graduate(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'institution_id' => Tenant::factory(),
            ];
        })->withRole('graduate');
    }

    /**
     * Create a suspended user.
     */
    public function suspended(): static
    {
        return $this->state([
            'is_suspended' => true,
            'suspended_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'suspension_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state([
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a user with two-factor authentication enabled.
     */
    public function withTwoFactor(): static
    {
        return $this->state([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt('base32secret'),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
                'recovery-code-3',
            ])),
        ]);
    }

    /**
     * Create a user with complete profile.
     */
    public function withCompleteProfile(): static
    {
        return $this->state([
            'phone' => fake()->phoneNumber(),
            'profile_data' => [
                'bio' => fake()->paragraph(),
                'location' => fake()->city().', '.fake()->country(),
                'website' => fake()->url(),
                'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
                'twitter' => '@'.fake()->userName(),
                'skills' => fake()->randomElements([
                    'PHP', 'JavaScript', 'Python', 'Java', 'C#', 'Ruby',
                    'Laravel', 'React', 'Vue.js', 'Angular', 'Node.js',
                    'MySQL', 'PostgreSQL', 'MongoDB', 'Redis',
                ], rand(3, 8)),
                'interests' => fake()->randomElements([
                    'Web Development', 'Mobile Development', 'Data Science',
                    'Machine Learning', 'DevOps', 'UI/UX Design',
                    'Project Management', 'Cybersecurity',
                ], rand(2, 5)),
            ],
        ]);
    }

    /**
     * Create a user for a specific institution.
     */
    public function forInstitution($institutionId): static
    {
        return $this->state([
            'institution_id' => $institutionId,
        ]);
    }

    /**
     * Create a recently active user.
     */
    public function recentlyActive(): static
    {
        return $this->state([
            'last_login_at' => fake()->dateTimeBetween('-3 days', 'now'),
            'last_activity_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Create a user with specific preferences.
     */
    public function withPreferences(array $preferences): static
    {
        return $this->state([
            'preferences' => array_merge([
                'notifications' => [
                    'email' => true,
                    'sms' => false,
                    'push' => true,
                ],
                'privacy' => [
                    'profile_visible' => true,
                    'show_email' => false,
                    'show_phone' => false,
                ],
                'dashboard' => [
                    'theme' => 'light',
                    'compact_mode' => false,
                ],
            ], $preferences),
        ]);
    }
}
