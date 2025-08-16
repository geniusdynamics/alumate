<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        $types = ['school', 'custom', 'interest', 'professional'];
        $privacies = ['public', 'private', 'secret'];

        return [
            'name' => $this->faker->words(3, true).' Group',
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement($types),
            'privacy' => $this->faker->randomElement($privacies),
            'institution_id' => null, // Will be set by states if needed
            'creator_id' => User::factory(),
            'settings' => [
                'posting_restriction' => $this->faker->randomElement(['all_members', 'admins_and_moderators', 'admins_only']),
                'invite_permission' => $this->faker->randomElement(['all_members', 'admins_and_moderators', 'admins_only']),
            ],
            'member_count' => $this->faker->numberBetween(1, 500),
        ];
    }

    public function school(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'school',
                'name' => $this->faker->company.' University Alumni',
                'description' => 'Official alumni group for '.$this->faker->company.' University',
                'institution_id' => Tenant::factory(),
                'privacy' => 'public',
            ];
        });
    }

    public function custom(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'custom',
                'name' => $this->faker->words(2, true).' Club',
                'description' => 'A custom group for '.$this->faker->words(3, true),
                'institution_id' => null,
            ];
        });
    }

    public function interest(): static
    {
        return $this->state(function (array $attributes) {
            $interests = ['Photography', 'Hiking', 'Technology', 'Cooking', 'Reading', 'Travel'];
            $interest = $this->faker->randomElement($interests);

            return [
                'type' => 'interest',
                'name' => $interest.' Enthusiasts',
                'description' => 'A group for alumni interested in '.strtolower($interest),
                'institution_id' => null,
            ];
        });
    }

    public function professional(): static
    {
        return $this->state(function (array $attributes) {
            $professions = ['Software Engineers', 'Marketing Professionals', 'Healthcare Workers', 'Teachers', 'Entrepreneurs'];
            $profession = $this->faker->randomElement($professions);

            return [
                'type' => 'professional',
                'name' => 'Alumni '.$profession,
                'description' => 'Professional networking group for '.strtolower($profession),
                'institution_id' => null,
            ];
        });
    }

    public function public(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy' => 'public',
            ];
        });
    }

    public function private(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy' => 'private',
            ];
        });
    }

    public function secret(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'privacy' => 'secret',
            ];
        });
    }

    public function withCreator(User $creator): static
    {
        return $this->state(function (array $attributes) use ($creator) {
            return [
                'creator_id' => $creator->id,
            ];
        });
    }

    public function withInstitution(Tenant $institution): static
    {
        return $this->state(function (array $attributes) use ($institution) {
            return [
                'institution_id' => $institution->id,
            ];
        });
    }
}
