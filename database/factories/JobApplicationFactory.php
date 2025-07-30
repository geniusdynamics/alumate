<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'pending', 'reviewing', 'interviewing', 'offered', 
            'accepted', 'rejected', 'withdrawn'
        ]);

        return [
            'job_id' => JobPosting::factory(),
            'user_id' => User::factory(),
            'status' => $status,
            'applied_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'cover_letter' => $this->faker->paragraphs(3, true),
            'resume_url' => $this->faker->optional(0.8)->url(),
            'introduction_requested' => $this->faker->boolean(30),
            'introduction_contact_id' => $this->faker->optional(0.3)->numberBetween(1, 100),
            'notes' => $this->faker->optional(0.4)->paragraph(),
        ];
    }

    /**
     * Indicate that the application is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'applied_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the application was accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
            'applied_at' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
        ]);
    }

    /**
     * Indicate that the application was rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'applied_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Indicate that an introduction was requested.
     */
    public function withIntroduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'introduction_requested' => true,
            'introduction_contact_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the application is currently being reviewed.
     */
    public function reviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewing',
            'applied_at' => $this->faker->dateTimeBetween('-2 weeks', '-1 week'),
        ]);
    }

    /**
     * Indicate that the application is in interview stage.
     */
    public function interviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'interviewing',
            'applied_at' => $this->faker->dateTimeBetween('-1 month', '-2 weeks'),
        ]);
    }
}