<?php

namespace Database\Seeders;

use App\Models\EmailSequence;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating sample email sequences for testing
 */
class EmailSequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample email sequences...');

        // Get existing tenants or create sample ones
        $tenants = $this->getOrCreateTenants();

        $sequencesCreated = 0;

        // Create sequences for each tenant
        foreach ($tenants as $tenant) {
            $sequencesCreated += $this->createSequencesForTenant($tenant);
        }

        $this->command->info("EmailSequenceSeeder completed. Total sequences created: {$sequencesCreated}");
    }

    /**
     * Create sequences for a specific tenant
     */
    private function createSequencesForTenant(Tenant $tenant): int
    {
        $sequences = [
            // Onboarding sequences
            [
                'name' => 'Welcome Series',
                'description' => 'Welcome new users to the platform',
                'audience_type' => 'individual',
                'trigger_type' => 'form_submission',
                'is_active' => true,
            ],
            [
                'name' => 'Institution Onboarding',
                'description' => 'Guide institutions through setup process',
                'audience_type' => 'institutional',
                'trigger_type' => 'manual',
                'is_active' => true,
            ],

            // Marketing sequences
            [
                'name' => 'Product Updates',
                'description' => 'Keep users informed about new features',
                'audience_type' => 'individual',
                'trigger_type' => 'manual',
                'is_active' => true,
            ],
            [
                'name' => 'Employer Engagement',
                'description' => 'Engage potential employer partners',
                'audience_type' => 'employer',
                'trigger_type' => 'form_submission',
                'is_active' => true,
            ],

            // Event sequences
            [
                'name' => 'Event Promotion',
                'description' => 'Promote upcoming events to alumni',
                'audience_type' => 'individual',
                'trigger_type' => 'manual',
                'is_active' => true,
            ],

            // Inactive sequences for testing
            [
                'name' => 'Holiday Greetings',
                'description' => 'Seasonal holiday communications',
                'audience_type' => 'general',
                'trigger_type' => 'manual',
                'is_active' => false,
            ],
        ];

        $count = 0;
        foreach ($sequences as $sequenceData) {
            EmailSequence::factory()->create([
                'tenant_id' => $tenant->id,
                ...$sequenceData,
            ]);

            $this->command->info("Created: {$sequenceData['name']} for {$tenant->name}");
            $count++;
        }

        return $count;
    }

    /**
     * Get existing tenants or create sample ones
     */
    private function getOrCreateTenants()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command->info('No tenants found. Creating sample tenants...');

            $tenantConfigs = [
                'Alumni Network Pro',
                'TalentHub Solutions',
                'CareerBridge Platform',
            ];

            foreach ($tenantConfigs as $config) {
                $tenants->push(Tenant::factory()->create([
                    'name' => $config,
                ]));
            }
        }

        return $tenants;
    }

    /**
     * Create a large number of sequences for testing
     */
    public function runLargeDataset(): void
    {
        $this->command->info('Creating large dataset of email sequences...');

        $tenants = $this->getOrCreateTenants();
        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            // Create 10 sequences per tenant
            for ($i = 1; $i <= 10; $i++) {
                EmailSequence::factory()->create([
                    'tenant_id' => $tenant->id,
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Large dataset created. Total sequences: {$totalCreated}");
    }

    /**
     * Create sequences with specific trigger types
     */
    public function runByTriggerType(string $triggerType): void
    {
        $this->command->info("Creating sequences with trigger type: {$triggerType}");

        $tenants = $this->getOrCreateTenants();
        $totalCreated = 0;

        foreach ($tenants as $tenant) {
            for ($i = 1; $i <= 3; $i++) {
                EmailSequence::factory()
                    ->create([
                        'tenant_id' => $tenant->id,
                        'trigger_type' => $triggerType,
                    ]);
                $totalCreated++;
            }
        }

        $this->command->info("Created {$totalCreated} sequences with trigger type: {$triggerType}");
    }

    /**
     * Create sequences for a specific tenant
     */
    public function runForTenant(int $tenantId): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $this->command->info("Creating email sequences for tenant: {$tenant->name}");

        $count = $this->createSequencesForTenant($tenant);

        $this->command->info("Created {$count} sequences for tenant: {$tenant->name}");
    }
}