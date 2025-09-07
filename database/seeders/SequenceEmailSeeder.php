<?php

namespace Database\Seeders;

use App\Models\EmailSequence;
use App\Models\SequenceEmail;
use App\Models\Template;
use Illuminate\Database\Seeder;

/**
 * Seeder for creating sample sequence emails for testing
 */
class SequenceEmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample sequence emails...');

        $sequences = EmailSequence::all();

        if ($sequences->isEmpty()) {
            $this->command->info('No email sequences found. Running EmailSequenceSeeder first...');
            $this->call(EmailSequenceSeeder::class);
            $sequences = EmailSequence::all();
        }

        $emailsCreated = 0;

        foreach ($sequences as $sequence) {
            $emailsCreated += $this->createEmailsForSequence($sequence);
        }

        $this->command->info("SequenceEmailSeeder completed. Total sequence emails created: {$emailsCreated}");
    }

    /**
     * Create emails for a specific sequence
     */
    private function createEmailsForSequence(EmailSequence $sequence): int
    {
        $emailConfigs = $this->getEmailConfigsForSequence($sequence);
        $count = 0;

        foreach ($emailConfigs as $index => $config) {
            SequenceEmail::factory()->create([
                'sequence_id' => $sequence->id,
                'send_order' => $index,
                'subject_line' => $config['subject'],
                'delay_hours' => $config['delay_hours'],
            ]);

            $this->command->info("Created email: {$config['subject']} for sequence: {$sequence->name}");
            $count++;
        }

        return $count;
    }

    /**
     * Get email configurations based on sequence type
     */
    private function getEmailConfigsForSequence(EmailSequence $sequence): array
    {
        return match ($sequence->name) {
            'Welcome Series' => [
                [
                    'subject' => 'Welcome to Our Platform!',
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'Getting Started Guide',
                    'delay_hours' => 24,
                ],
                [
                    'subject' => 'Your First Week Tips',
                    'delay_hours' => 168,
                ],
                [
                    'subject' => 'Advanced Features Overview',
                    'delay_hours' => 336,
                ],
            ],
            'Institution Onboarding' => [
                [
                    'subject' => 'Welcome Institution Partner',
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'Setting Up Your Institution Profile',
                    'delay_hours' => 24,
                ],
                [
                    'subject' => 'Integration Guide',
                    'delay_hours' => 72,
                ],
            ],
            'Product Updates' => [
                [
                    'subject' => 'New Features Available',
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'How to Use the Latest Updates',
                    'delay_hours' => 48,
                ],
            ],
            'Employer Engagement' => [
                [
                    'subject' => 'Partner With Us',
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'Employer Benefits Overview',
                    'delay_hours' => 24,
                ],
                [
                    'subject' => 'Success Stories from Our Partners',
                    'delay_hours' => 168,
                ],
            ],
            'Event Promotion' => [
                [
                    'subject' => 'Upcoming Event: Don\'t Miss Out!',
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'Event Details & Registration',
                    'delay_hours' => 24,
                ],
                [
                    'subject' => 'Last Chance to Register',
                    'delay_hours' => 72,
                ],
            ],
            default => [
                [
                    'subject' => 'Hello from ' . $sequence->name,
                    'delay_hours' => 0,
                ],
                [
                    'subject' => 'Follow-up from ' . $sequence->name,
                    'delay_hours' => 24,
                ],
            ],
        };
    }

    /**
     * Create a large number of sequence emails for testing
     */
    public function runLargeDataset(): void
    {
        $this->command->info('Creating large dataset of sequence emails...');

        $sequences = EmailSequence::all();
        $totalCreated = 0;

        foreach ($sequences as $sequence) {
            // Create 3-5 emails per sequence
            $emailCount = rand(3, 5);

            for ($i = 0; $i < $emailCount; $i++) {
                SequenceEmail::factory()->create([
                    'sequence_id' => $sequence->id,
                    'send_order' => $i,
                    'delay_hours' => $i * 24, // 24 hours apart
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Large dataset created. Total sequence emails: {$totalCreated}");
    }

    /**
     * Create sequence emails for a specific sequence
     */
    public function runForSequence(int $sequenceId): void
    {
        $sequence = EmailSequence::findOrFail($sequenceId);
        $this->command->info("Creating sequence emails for: {$sequence->name}");

        $count = $this->createEmailsForSequence($sequence);

        $this->command->info("Created {$count} emails for sequence: {$sequence->name}");
    }

    /**
     * Create sequence emails with specific templates
     */
    public function runWithTemplates(): void
    {
        $this->command->info('Creating sequence emails with specific templates...');

        $sequences = EmailSequence::all();
        $templates = Template::email()->get();

        if ($templates->isEmpty()) {
            $this->command->info('No email templates found. Creating sample templates...');
            // Create some basic email templates
            $templates = collect([
                Template::factory()->email()->create(['name' => 'Welcome Email Template']),
                Template::factory()->email()->create(['name' => 'Newsletter Template']),
                Template::factory()->email()->create(['name' => 'Promotional Template']),
            ]);
        }

        $totalCreated = 0;

        foreach ($sequences as $sequence) {
            $emailCount = rand(2, 4);

            for ($i = 0; $i < $emailCount; $i++) {
                SequenceEmail::factory()->create([
                    'sequence_id' => $sequence->id,
                    'template_id' => $templates->random()->id,
                    'send_order' => $i,
                    'delay_hours' => $i * 24,
                ]);
                $totalCreated++;
            }
        }

        $this->command->info("Created {$totalCreated} sequence emails with templates");
    }
}