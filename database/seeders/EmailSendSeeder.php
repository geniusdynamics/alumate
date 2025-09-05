<?php

namespace Database\Seeders;

use App\Models\EmailSend;
use App\Models\SequenceEmail;
use App\Models\SequenceEnrollment;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating sample email sends for testing
 */
class EmailSendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample email sends...');

        $enrollments = SequenceEnrollment::all();

        if ($enrollments->isEmpty()) {
            $this->command->info('No sequence enrollments found. Running SequenceEnrollmentSeeder first...');
            $this->call(SequenceEnrollmentSeeder::class);
            $enrollments = SequenceEnrollment::all();
        }

        $sendsCreated = 0;

        foreach ($enrollments as $enrollment) {
            $sendsCreated += $this->createSendsForEnrollment($enrollment);
        }

        $this->command->info("EmailSendSeeder completed. Total email sends created: {$sendsCreated}");
    }

    /**
     * Create email sends for a specific enrollment
     */
    private function createSendsForEnrollment(SequenceEnrollment $enrollment): int
    {
        $sequenceEmails = $enrollment->sequence->sequenceEmails;
        $count = 0;

        if ($sequenceEmails->isEmpty()) {
            $this->command->info("No sequence emails found for sequence: {$enrollment->sequence->name}");
            return 0;
        }

        foreach ($sequenceEmails as $sequenceEmail) {
            // Skip if send already exists
            if (EmailSend::where('enrollment_id', $enrollment->id)
                        ->where('sequence_email_id', $sequenceEmail->id)
                        ->exists()) {
                continue;
            }

            $send = $this->createSendForEnrollmentAndSequenceEmail($enrollment, $sequenceEmail);
            $this->command->info("Created send: {$send->subject} for {$enrollment->lead->email}");
            $count++;
        }

        return $count;
    }

    /**
     * Create a single email send with realistic data
     */
    private function createSendForEnrollmentAndSequenceEmail(
        SequenceEnrollment $enrollment,
        SequenceEmail $sequenceEmail
    ): EmailSend {
        $status = $this->getSendStatusForEnrollment($enrollment, $sequenceEmail);
        $sentAt = $this->getSentAtForStatus($status, $enrollment);

        return EmailSend::factory()->create([
            'enrollment_id' => $enrollment->id,
            'sequence_email_id' => $sequenceEmail->id,
            'lead_id' => $enrollment->lead_id,
            'subject' => $sequenceEmail->subject_line,
            'status' => $status,
            'sent_at' => $sentAt,
            'delivered_at' => $this->getDeliveredAtForStatus($status, $sentAt),
            'opened_at' => $this->getOpenedAtForStatus($status, $sentAt),
            'clicked_at' => $this->getClickedAtForStatus($status, $sentAt),
            'unsubscribed_at' => $this->getUnsubscribedAtForStatus($status, $sentAt),
        ]);
    }

    /**
     * Get appropriate send status based on enrollment progress
     */
    private function getSendStatusForEnrollment(
        SequenceEnrollment $enrollment,
        SequenceEmail $sequenceEmail
    ): string {
        // If enrollment is unsubscribed, some sends might be affected
        if ($enrollment->status === 'unsubscribed' && rand(1, 100) <= 30) {
            return 'queued'; // Not sent yet
        }

        // If enrollment is paused, some sends might be queued
        if ($enrollment->status === 'paused' && rand(1, 100) <= 50) {
            return 'queued';
        }

        // If enrollment is completed, all sends should be sent
        if ($enrollment->status === 'completed') {
            return $this->getRandomCompletedStatus();
        }

        // For active enrollments, mix of statuses
        return $this->getRandomActiveStatus();
    }

    /**
     * Get random status for completed enrollments
     */
    private function getRandomCompletedStatus(): string
    {
        $statuses = [
            'delivered' => 70,
            'bounced' => 5,
            'failed' => 2,
        ];

        return $this->getWeightedRandomStatus($statuses);
    }

    /**
     * Get random status for active enrollments
     */
    private function getRandomActiveStatus(): string
    {
        $statuses = [
            'queued' => 20,
            'sent' => 10,
            'delivered' => 60,
            'bounced' => 8,
            'failed' => 2,
        ];

        return $this->getWeightedRandomStatus($statuses);
    }

    /**
     * Get weighted random status
     */
    private function getWeightedRandomStatus(array $statuses): string
    {
        $rand = rand(1, 100);
        $cumulative = 0;

        foreach ($statuses as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'delivered';
    }

    /**
     * Get sent timestamp based on status
     */
    private function getSentAtForStatus(string $status, SequenceEnrollment $enrollment)
    {
        if (in_array($status, ['queued'])) {
            return null;
        }

        // Base sent time on enrollment date
        $enrolledAt = $enrollment->enrolled_at ?? now()->subDays(30);
        $daysSinceEnrollment = $enrolledAt->diffInDays(now());

        return fake()->dateTimeBetween(
            $enrolledAt->format('Y-m-d H:i:s'),
            $enrolledAt->addDays(min($daysSinceEnrollment, 30))->format('Y-m-d H:i:s')
        );
    }

    /**
     * Get delivered timestamp based on status
     */
    private function getDeliveredAtForStatus(string $status, $sentAt)
    {
        if (!$sentAt || !in_array($status, ['delivered', 'bounced', 'failed'])) {
            return null;
        }

        if ($status === 'bounced' || $status === 'failed') {
            return fake()->optional(0.9)->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +1 hour');
        }

        return fake()->dateTimeBetween($sentAt, $sentAt->format('Y-m-d H:i:s') . ' +30 minutes');
    }

    /**
     * Get opened timestamp based on status
     */
    private function getOpenedAtForStatus(string $status, $sentAt)
    {
        if (!$sentAt || $status !== 'delivered') {
            return null;
        }

        return fake()->optional(0.4)->dateTimeBetween(
            $sentAt->format('Y-m-d H:i:s') . ' +1 hour',
            $sentAt->format('Y-m-d H:i:s') . ' +7 days'
        );
    }

    /**
     * Get clicked timestamp based on status
     */
    private function getClickedAtForStatus(string $status, $sentAt)
    {
        if (!$sentAt || $status !== 'delivered') {
            return null;
        }

        return fake()->optional(0.15)->dateTimeBetween(
            $sentAt->format('Y-m-d H:i:s') . ' +2 hours',
            $sentAt->format('Y-m-d H:i:s') . ' +7 days'
        );
    }

    /**
     * Get unsubscribed timestamp based on status
     */
    private function getUnsubscribedAtForStatus(string $status, $sentAt)
    {
        if (!$sentAt || $status !== 'delivered') {
            return null;
        }

        return fake()->optional(0.02)->dateTimeBetween(
            $sentAt->format('Y-m-d H:i:s') . ' +1 hour',
            $sentAt->format('Y-m-d H:i:s') . ' +7 days'
        );
    }

    /**
     * Create a large number of email sends for testing
     */
    public function runLargeDataset(): void
    {
        $this->command->info('Creating large dataset of email sends...');

        $enrollments = SequenceEnrollment::all();
        $totalCreated = 0;

        foreach ($enrollments as $enrollment) {
            $sequenceEmails = $enrollment->sequence->sequenceEmails;

            foreach ($sequenceEmails as $sequenceEmail) {
                // Skip if send already exists
                if (EmailSend::where('enrollment_id', $enrollment->id)
                            ->where('sequence_email_id', $sequenceEmail->id)
                            ->exists()) {
                    continue;
                }

                EmailSend::factory()->create([
                    'enrollment_id' => $enrollment->id,
                    'sequence_email_id' => $sequenceEmail->id,
                    'lead_id' => $enrollment->lead_id,
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Large dataset created. Total email sends: {$totalCreated}");
    }

    /**
     * Create email sends for a specific enrollment
     */
    public function runForEnrollment(int $enrollmentId): void
    {
        $enrollment = SequenceEnrollment::findOrFail($enrollmentId);
        $this->command->info("Creating email sends for enrollment: {$enrollment->lead->email} in {$enrollment->sequence->name}");

        $count = $this->createSendsForEnrollment($enrollment);

        $this->command->info("Created {$count} email sends for enrollment");
    }

    /**
     * Create email sends with specific engagement rates
     */
    public function runWithEngagementRates(array $rates = null): void
    {
        $rates = $rates ?? [
            'open_rate' => 40,
            'click_rate' => 15,
            'unsubscribe_rate' => 2,
        ];

        $this->command->info('Creating email sends with custom engagement rates...');

        $enrollments = SequenceEnrollment::all();
        $totalCreated = 0;

        foreach ($enrollments as $enrollment) {
            $sequenceEmails = $enrollment->sequence->sequenceEmails;

            foreach ($sequenceEmails as $sequenceEmail) {
                // Skip if send already exists
                if (EmailSend::where('enrollment_id', $enrollment->id)
                            ->where('sequence_email_id', $sequenceEmail->id)
                            ->exists()) {
                    continue;
                }

                // Create send with custom engagement
                $send = EmailSend::factory()->create([
                    'enrollment_id' => $enrollment->id,
                    'sequence_email_id' => $sequenceEmail->id,
                    'lead_id' => $enrollment->lead_id,
                    'status' => 'delivered',
                ]);

                // Apply custom engagement rates
                $this->applyCustomEngagementRates($send, $rates);
                $totalCreated++;
            }
        }

        $this->command->info("Created {$totalCreated} email sends with custom engagement rates");
    }

    /**
     * Apply custom engagement rates to a send
     */
    private function applyCustomEngagementRates(EmailSend $send, array $rates): void
    {
        if (!$send->sent_at) return;

        $sentAt = $send->sent_at;

        // Apply open rate
        if (rand(1, 100) <= $rates['open_rate']) {
            $send->update([
                'opened_at' => fake()->dateTimeBetween(
                    $sentAt->format('Y-m-d H:i:s') . ' +1 hour',
                    $sentAt->format('Y-m-d H:i:s') . ' +7 days'
                )
            ]);
        }

        // Apply click rate (only if opened)
        if ($send->opened_at && rand(1, 100) <= $rates['click_rate']) {
            $send->update([
                'clicked_at' => fake()->dateTimeBetween(
                    $send->opened_at->format('Y-m-d H:i:s') . ' +5 minutes',
                    $send->opened_at->format('Y-m-d H:i:s') . ' +1 day'
                )
            ]);
        }

        // Apply unsubscribe rate
        if (rand(1, 100) <= $rates['unsubscribe_rate']) {
            $send->update([
                'unsubscribed_at' => fake()->dateTimeBetween(
                    $sentAt->format('Y-m-d H:i:s') . ' +1 hour',
                    $sentAt->format('Y-m-d H:i:s') . ' +7 days'
                )
            ]);
        }
    }
}