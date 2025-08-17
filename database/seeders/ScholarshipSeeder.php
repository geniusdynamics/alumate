<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ScholarshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample scholarships
        $scholarships = \App\Models\Scholarship::factory(10)->create();

        // Create applications for each scholarship
        foreach ($scholarships as $scholarship) {
            $applications = \App\Models\ScholarshipApplication::factory(rand(3, 8))
                ->create(['scholarship_id' => $scholarship->id]);

            // Create reviews for submitted applications
            foreach ($applications as $application) {
                if ($application->status === 'submitted' || $application->status === 'under_review') {
                    \App\Models\ScholarshipReview::factory(rand(1, 3))
                        ->create(['application_id' => $application->id]);
                }

                // Create recipients for awarded applications
                if ($application->status === 'awarded') {
                    \App\Models\ScholarshipRecipient::factory()
                        ->create([
                            'scholarship_id' => $scholarship->id,
                            'application_id' => $application->id,
                            'recipient_id' => $application->applicant_id,
                        ]);
                }
            }
        }
    }
}
