<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class FundraisingCampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample users if they don't exist
        $users = \App\Models\User::factory(5)->create();

        // For now, we'll skip institutions since the model doesn't exist
        $institutions = [];

        // Create fundraising campaigns
        $campaigns = [];
        foreach ($users as $index => $user) {
            $campaign = \App\Models\FundraisingCampaign::factory()
                ->for($user, 'creator')
                ->create([
                    'institution_id' => null, // Skip institutions for now
                    'status' => $index < 3 ? 'active' : 'draft',
                ]);

            $campaigns[] = $campaign;

            // Create some donations for active campaigns
            if ($campaign->status === 'active') {
                $donorCount = rand(5, 25);
                $totalRaised = 0;

                for ($j = 0; $j < $donorCount; $j++) {
                    $donor = $users[array_rand($users->toArray())];
                    $amount = rand(25, 500);
                    $totalRaised += $amount;

                    \App\Models\CampaignDonation::create([
                        'campaign_id' => $campaign->id,
                        'donor_id' => $donor->id,
                        'amount' => $amount,
                        'currency' => 'USD',
                        'is_anonymous' => rand(0, 1) === 1,
                        'message' => rand(0, 1) === 1 ? 'Great cause! Happy to support.' : null,
                        'payment_method' => ['stripe', 'paypal'][array_rand(['stripe', 'paypal'])],
                        'payment_id' => 'sim_'.uniqid(),
                        'status' => 'completed',
                        'processed_at' => now()->subDays(rand(0, 30)),
                    ]);
                }

                // Update campaign totals
                $campaign->update([
                    'raised_amount' => $totalRaised,
                    'donor_count' => $donorCount,
                ]);

                // Create peer fundraisers for campaigns that allow it
                if ($campaign->allow_peer_fundraising && rand(0, 1) === 1) {
                    $peerFundraiserCount = rand(2, 5);

                    for ($k = 0; $k < $peerFundraiserCount; $k++) {
                        $fundraiser = $users[array_rand($users->toArray())];

                        $peerFundraiser = \App\Models\PeerFundraiser::create([
                            'campaign_id' => $campaign->id,
                            'user_id' => $fundraiser->id,
                            'title' => "Help me support {$campaign->title}",
                            'personal_message' => 'This cause is important to me. Please consider donating!',
                            'goal_amount' => rand(1000, 5000),
                            'status' => 'active',
                        ]);

                        // Create some donations for peer fundraisers
                        $peerDonationCount = rand(1, 8);
                        $peerTotalRaised = 0;

                        for ($l = 0; $l < $peerDonationCount; $l++) {
                            $peerDonor = $users[array_rand($users->toArray())];
                            $peerAmount = rand(25, 200);
                            $peerTotalRaised += $peerAmount;

                            \App\Models\CampaignDonation::create([
                                'campaign_id' => $campaign->id,
                                'donor_id' => $peerDonor->id,
                                'peer_fundraiser_id' => $peerFundraiser->id,
                                'amount' => $peerAmount,
                                'currency' => 'USD',
                                'is_anonymous' => rand(0, 1) === 1,
                                'message' => rand(0, 1) === 1 ? 'Supporting through my friend!' : null,
                                'payment_method' => ['stripe', 'paypal'][array_rand(['stripe', 'paypal'])],
                                'payment_id' => 'sim_'.uniqid(),
                                'status' => 'completed',
                                'processed_at' => now()->subDays(rand(0, 20)),
                            ]);
                        }

                        // Update peer fundraiser totals
                        $peerFundraiser->update([
                            'raised_amount' => $peerTotalRaised,
                            'donor_count' => $peerDonationCount,
                        ]);

                        // Update campaign totals to include peer fundraising
                        $campaign->increment('raised_amount', $peerTotalRaised);
                        $campaign->increment('donor_count', $peerDonationCount);
                    }
                }

                // Create campaign updates
                $updateCount = rand(1, 3);
                for ($m = 0; $m < $updateCount; $m++) {
                    \App\Models\CampaignUpdate::create([
                        'campaign_id' => $campaign->id,
                        'created_by' => $user->id,
                        'title' => 'Campaign Update #'.($m + 1),
                        'content' => "Thank you for your continued support! We've made great progress towards our goal.",
                        'published_at' => now()->subDays(rand(1, 15)),
                    ]);
                }
            }
        }

        $this->command->info('Created '.count($campaigns).' fundraising campaigns with donations and peer fundraisers.');
    }
}
