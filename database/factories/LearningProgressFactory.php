<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LearningProgress;
use App\Models\User;
use App\Models\Course;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * LearningProgress Factory
 *
 * Factory for creating LearningProgress model instances for testing.
 */
class LearningProgressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = LearningProgress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'module_id' => $this->faker->numberBetween(1, 10),
            'progress_percentage' => $this->faker->numberBetween(0, 100),
            'engagement_duration' => $this->faker->numberBetween(0, 500),
            'completion_timestamp' => $this->faker->optional(0.3)->dateTime(),
            'certifications' => [],
            'interactions_count' => $this->faker->numberBetween(0, 100),
            'modules_completed' => $this->faker->numberBetween(0, 10),
            'total_score' => $this->faker->numberBetween(0, 100),
            'engagement_score' => $this->faker->numberBetween(0, 100),
            'certified' => $this->faker->boolean(20),
        ];
    }

    /**
     * Create progress for a specific user.
     *
     * @param int $userId
     * @return self
     */
    public function forUser(int $userId): self
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Create progress for a specific course.
     *
     * @param int $courseId
     * @return self
     */
    public function forCourse(int $courseId): self
    {
        return $this->state(function (array $attributes) use ($courseId) {
            return [
                'course_id' => $courseId,
            ];
        });
    }

    /**
     * Create completed progress (100%).
     *
     * @return self
     */
    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'progress_percentage' => 100,
                'modules_completed' => 10,
                'completion_timestamp' => now(),
            ];
        });
    }

    /**
     * Create progress with high engagement.
     *
     * @return self
     */
    public function highEngagement(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'engagement_duration' => $this->faker->numberBetween(300, 500),
                'interactions_count' => $this->faker->numberBetween(50, 100),
                'engagement_score' => $this->faker->numberBetween(80, 100),
            ];
        });
    }

    /**
     * Create progress with low engagement.
     *
     * @return self
     */
    public function lowEngagement(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'engagement_duration' => $this->faker->numberBetween(0, 60),
                'interactions_count' => $this->faker->numberBetween(0, 10),
                'engagement_score' => $this->faker->numberBetween(0, 30),
            ];
        });
    }

    /**
     * Create certified progress.
     *
     * @return self
     */
    public function certified(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'certified' => true,
                'total_score' => $this->faker->numberBetween(80, 100),
                'progress_percentage' => 100,
                'modules_completed' => 10,
            ];
        });
    }

    /**
     * Create progress with specific module.
     *
     * @param int $moduleId
     * @return self
     */
    public function forModule(int $moduleId): self
    {
        return $this->state(function (array $attributes) use ($moduleId) {
            return [
                'module_id' => $moduleId,
            ];
        });
    }

    /**
     * Create progress for a specific tenant.
     *
     * @param int $tenantId
     * @return self
     */
    public function forTenant(int $tenantId): self
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }
}
