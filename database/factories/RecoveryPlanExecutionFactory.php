<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Services\Analytics\AnalyticsDisasterRecoveryService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecoveryPlanExecution>
 */
class RecoveryPlanExecutionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recovery_plan_id' => \App\Models\RecoveryPlan::factory()->create()->id,
            'tenant_id' => \App\Models\Tenant::factory()->create()->id,
            'status' => fake()->randomElement([
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_PENDING,
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_RUNNING,
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED,
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_FAILED,
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_ROLLED_BACK,
                AnalyticsDisasterRecoveryService::EXECUTION_STATUS_CANCELLED,
            ]),
            'started_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'completed_at' => null,
            'duration_seconds' => null,
            'steps_completed' => 0,
            'steps_total' => 5,
            'data_restored_count' => 0,
            'data_loss_count' => 0,
            'errors' => null,
            'warnings' => null,
            'rollback_performed' => false,
            'rollback_reason' => null,
            'initiated_by' => \App\Models\User::factory()->create()->id,
            'execution_type' => fake()->randomElement(['manual', 'automatic', 'scheduled']),
            'source_system' => fake()->randomElement(['primary', 'backup']),
            'target_system' => fake()->randomElement(['backup', 'primary']),
            'verification_results' => null,
        ];
    }

    public function pending(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_PENDING,
        ]);
    }

    public function running(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_RUNNING,
            'started_at' => now(),
            'steps_completed' => fake()->numberBetween(1, 4),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => fake()->numberBetween(600, 3600),
            'steps_completed' => 5,
            'data_restored_count' => fake()->numberBetween(1000, 10000),
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_FAILED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => fake()->numberBetween(100, 1800),
            'steps_completed' => fake()->numberBetween(1, 4),
            'errors' => [
                [
                    'message' => fake()->sentence(),
                    'timestamp' => now()->toIso8601String(),
                ],
            ],
        ]);
    }

    public function rolledBack(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_ROLLED_BACK,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => fake()->numberBetween(600, 2400),
            'rollback_performed' => true,
            'rollback_reason' => fake()->sentence(),
        ]);
    }

    public function cancelled(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::EXECUTION_STATUS_CANCELLED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'duration_seconds' => fake()->numberBetween(60, 300),
        ]);
    }

    public function manual(): self
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'manual',
        ]);
    }

    public function automatic(): self
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'automatic',
        ]);
    }

    public function failoverExecution(): self
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'automatic',
            'source_system' => 'primary',
            'target_system' => 'backup',
        ]);
    }

    public function failbackExecution(): self
    {
        return $this->state(fn (array $attributes) => [
            'execution_type' => 'automatic',
            'source_system' => 'backup',
            'target_system' => 'primary',
        ]);
    }

    public function withVerificationResults(): self
    {
        return $this->state(fn (array $attributes) => [
            'verification_results' => [
                'database_connectivity' => true,
                'data_integrity' => true,
                'service_availability' => true,
                'api_accessibility' => true,
            ],
        ]);
    }
}
