<?php

namespace Database\Seeders;

use App\Models\EmailSequence;
use App\Models\Lead;
use App\Models\SequenceEnrollment;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating sample sequence enrollments for testing
 */
class SequenceEnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample sequence enrollments...');

        $sequences = EmailSequence::all();
        $leads = Lead::all();

        if ($sequences->isEmpty()) {
            $this->command->info('No email sequences found. Running EmailSequenceSeeder first...');
            $this->call(EmailSequenceSeeder::class);
            $sequences = EmailSequence::all();
        }

        if ($leads->isEmpty()) {
            $this->command->info('No leads found. Creating sample leads...');
            $leads = collect();
            for ($i = 0; $i < 20; $i++) {
                $leads->push(Lead::factory()->create());
            }
        }

        $enrollmentsCreated = 0;

        foreach ($sequences as $sequence) {
            $enrollmentsCreated += $this->createEnrollmentsForSequence($sequence, $leads);
        }

        $this->command->info("SequenceEnrollmentSeeder completed. Total enrollments created: {$enrollmentsCreated}");
    }

    /**
     * Create enrollments for a specific sequence
     */
    private function createEnrollmentsForSequence(EmailSequence $sequence, $leads): int
    {
        // Create 3-8 enrollments per sequence
        $enrollmentCount = rand(3, 8);
        $count = 0;

        // Shuffle leads to get random selection
        $availableLeads = $leads->shuffle();

        for ($i = 0; $i < min($enrollmentCount, $availableLeads->count()); $i++) {
            $lead = $availableLeads[$i];

            // Skip if enrollment already exists
            if (SequenceEnrollment::where('sequence_id', $sequence->id)->where('lead_id', $lead->id)->exists()) {
                continue;
            }

            $enrollment = $this->createEnrollmentForSequenceAndLead($sequence, $lead);
            $this->command->info("Created enrollment: {$lead->email} in {$sequence->name}");
            $count++;
        }

        return $count;
    }

    /**
     * Create a single enrollment with realistic data
     */
    private function createEnrollmentForSequenceAndLead(EmailSequence $sequence, Lead $lead): SequenceEnrollment
    {
        $status = $this->getRandomStatus();
        $currentStep = $this->getCurrentStepForStatus($status, $sequence);
        $enrolledAt = fake()->dateTimeBetween('-30 days', 'now');

        return SequenceEnrollment::factory()->create([
            'sequence_id' => $sequence->id,
            'lead_id' => $lead->id,
            'current_step' => $currentStep,
            'status' => $status,
            'enrolled_at' => $enrolledAt,
            'completed_at' => $status === 'completed' ? $this->faker->dateTimeBetween($enrolledAt, 'now') : null,
        ]);
    }

    /**
     * Get a random status with realistic distribution
     */
    private function getRandomStatus(): string
    {
        $statuses = [
            'active' => 60,      // 60% active
            'completed' => 25,   // 25% completed
            'paused' => 10,      // 10% paused
            'unsubscribed' => 5, // 5% unsubscribed
        ];

        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'active';
    }

    /**
     * Get appropriate current step based on status
     */
    private function getCurrentStepForStatus(string $status, EmailSequence $sequence): int
    {
        return match ($status) {
            'active' => rand(0, 2), // Early in the sequence
            'completed' => rand(3, 5), // Further along
            'paused' => rand(1, 3), // Somewhere in the middle
            'unsubscribed' => rand(0, 4), // Could be at any point
            default => 0,
        };
    }

    /**
     * Create a large number of enrollments for testing
     */
    public function runLargeDataset(): void
    {
        $this->command->info('Creating large dataset of sequence enrollments...');

        $sequences = EmailSequence::all();
        $leads = Lead::all();

        if ($leads->isEmpty()) {
            $this->command->info('Creating leads for large dataset...');
            for ($i = 0; $i < 100; $i++) {
                $leads->push(Lead::factory()->create());
            }
        }

        $totalCreated = 0;

        foreach ($sequences as $sequence) {
            // Create 10-20 enrollments per sequence
            $enrollmentCount = rand(10, 20);

            for ($i = 0; $i < $enrollmentCount; $i++) {
                $lead = $leads->random();

                // Skip if enrollment already exists
                if (SequenceEnrollment::where('sequence_id', $sequence->id)->where('lead_id', $lead->id)->exists()) {
                    continue;
                }

                SequenceEnrollment::factory()->create([
                    'sequence_id' => $sequence->id,
                    'lead_id' => $lead->id,
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Large dataset created. Total enrollments: {$totalCreated}");
    }

    /**
     * Create enrollments for a specific sequence
     */
    public function runForSequence(int $sequenceId): void
    {
        $sequence = EmailSequence::findOrFail($sequenceId);
        $leads = Lead::all();

        if ($leads->isEmpty()) {
            $this->command->info('No leads found. Creating sample leads...');
            for ($i = 0; $i < 10; $i++) {
                $leads->push(Lead::factory()->create());
            }
        }

        $this->command->info("Creating enrollments for sequence: {$sequence->name}");

        $count = $this->createEnrollmentsForSequence($sequence, $leads);

        $this->command->info("Created {$count} enrollments for sequence: {$sequence->name}");
    }

    /**
     * Create enrollments with specific status distribution
     */
    public function runWithStatusDistribution(array $distribution = null): void
    {
        $distribution = $distribution ?? [
            'active' => 50,
            'completed' => 30,
            'paused' => 15,
            'unsubscribed' => 5,
        ];

        $this->command->info('Creating enrollments with custom status distribution...');

        $sequences = EmailSequence::all();
        $leads = Lead::all();

        if ($leads->isEmpty()) {
            $this->command->info('Creating leads...');
            for ($i = 0; $i < 50; $i++) {
                $leads->push(Lead::factory()->create());
            }
        }

        $totalCreated = 0;

        foreach ($sequences as $sequence) {
            foreach ($distribution as $status => $count) {
                for ($i = 0; $i < $count; $i++) {
                    if ($leads->isEmpty()) break;

                    $lead = $leads->pop();

                    SequenceEnrollment::factory()->create([
                        'sequence_id' => $sequence->id,
                        'lead_id' => $lead->id,
                        'status' => $status,
                        'current_step' => $this->getCurrentStepForStatus($status, $sequence),
                    ]);
                    $totalCreated++;
                }
            }
        }

        $this->command->info("Created {$totalCreated} enrollments with custom distribution");
    }
}