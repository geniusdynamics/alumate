<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Services\Analytics\AnalyticsDisasterRecoveryService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecoveryPlan>
 */
class RecoveryPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory()->create()->id,
            'user_id' => \App\Models\User::factory()->create()->id,
            'name' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement([
                AnalyticsDisasterRecoveryService::TYPE_FULL_RECOVERY,
                AnalyticsDisasterRecoveryService::TYPE_PARTIAL_RECOVERY,
                AnalyticsDisasterRecoveryService::TYPE_POINT_IN_TIME,
                AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
                AnalyticsDisasterRecoveryService::TYPE_FAILBACK,
            ]),
            'status' => fake()->randomElement([
                AnalyticsDisasterRecoveryService::STATUS_DRAFT,
                AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
                AnalyticsDisasterRecoveryService::STATUS_TESTING,
                AnalyticsDisasterRecoveryService::STATUS_EXECUTING,
                AnalyticsDisasterRecoveryService::STATUS_COMPLETED,
                AnalyticsDisasterRecoveryService::STATUS_FAILED,
                AnalyticsDisasterRecoveryService::STATUS_ARCHIVED,
            ]),
            'priority' => fake()->randomElement([
                AnalyticsDisasterRecoveryService::PRIORITY_CRITICAL,
                AnalyticsDisasterRecoveryService::PRIORITY_HIGH,
                AnalyticsDisasterRecoveryService::PRIORITY_MEDIUM,
                AnalyticsDisasterRecoveryService::PRIORITY_LOW,
            ]),
            'backup_id' => null,
            'target_backup_id' => null,
            'steps' => [
                [
                    'order' => 1,
                    'name' => 'Pre-flight Checks',
                    'action' => 'preflight_checks',
                    'timeout_minutes' => 5,
                ],
                [
                    'order' => 2,
                    'name' => 'Restore Data',
                    'action' => 'restore_data',
                    'timeout_minutes' => 30,
                ],
                [
                    'order' => 3,
                    'name' => 'Verification',
                    'action' => 'post_recovery_verification',
                    'timeout_minutes' => 15,
                ],
            ],
            'configuration' => [
                'data_restore_options' => [
                    'restore_events' => true,
                    'restore_snapshots' => true,
                    'restore_attributions' => true,
                    'restore_cohorts' => true,
                    'restore_predictions' => true,
                    'restore_custom_events' => true,
                    'clear_existing' => false,
                ],
                'verification_options' => [
                    'verify_checksums' => true,
                    'validate_references' => true,
                ],
                'rollback_options' => [
                    'enable_rollback' => true,
                    'rollback_on_failure' => true,
                ],
            ],
            'test_results' => null,
            'execution_log' => null,
            'scheduled_at' => null,
            'started_at' => null,
            'completed_at' => null,
            'estimated_duration_minutes' => fake()->numberBetween(15, 120),
            'actual_duration_minutes' => null,
            'data_loss_estimate' => null,
            'last_validated_at' => null,
            'last_tested_at' => null,
            'failure_count' => 0,
            'is_automatic' => false,
            'notify_on_completion' => true,
            'notification_channels' => ['email'],
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::STATUS_DRAFT,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::STATUS_ACTIVE,
            'test_results' => ['overall_success' => true],
        ]);
    }

    public function executing(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::STATUS_EXECUTING,
            'started_at' => now(),
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::STATUS_COMPLETED,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'actual_duration_minutes' => 45,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalyticsDisasterRecoveryService::STATUS_FAILED,
            'failure_count' => 1,
        ]);
    }

    public function failover(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILOVER,
            'priority' => AnalyticsDisasterRecoveryService::PRIORITY_CRITICAL,
        ]);
    }

    public function failback(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => AnalyticsDisasterRecoveryService::TYPE_FAILBACK,
            'priority' => AnalyticsDisasterRecoveryService::PRIORITY_HIGH,
        ]);
    }

    public function automatic(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_automatic' => true,
        ]);
    }
}
