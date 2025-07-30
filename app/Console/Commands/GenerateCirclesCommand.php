<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CircleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateCirclesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'circles:generate-all 
                            {--batch-size=100 : Number of users to process in each batch}
                            {--force : Force regeneration even if circles already exist}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Generate circles for all users based on their education history';

    protected CircleManager $circleManager;

    /**
     * Create a new command instance.
     */
    public function __construct(CircleManager $circleManager)
    {
        parent::__construct();
        $this->circleManager = $circleManager;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('Starting circle generation for all users...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get total user count
        $totalUsers = User::count();
        $this->info("Total users to process: {$totalUsers}");

        if ($totalUsers === 0) {
            $this->warn('No users found. Nothing to process.');
            return self::SUCCESS;
        }

        $processedCount = 0;
        $circlesCreated = 0;
        $errors = 0;

        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        // Process users in batches
        User::with('educations.institution')
            ->chunk($batchSize, function ($users) use (&$processedCount, &$circlesCreated, &$errors, $force, $dryRun, $progressBar) {
                foreach ($users as $user) {
                    try {
                        if (!$dryRun) {
                            if ($force) {
                                // Remove existing circles and regenerate
                                $this->circleManager->updateCirclesForUser($user);
                            } else {
                                // Only generate if user has no circles
                                $existingCircles = $user->circles()->count();
                                if ($existingCircles === 0) {
                                    $newCircles = $this->circleManager->generateCirclesForUser($user);
                                    $circlesCreated += $newCircles->count();
                                }
                            }
                        } else {
                            // Dry run - just count what would be done
                            $eligibleCircles = $this->circleManager->getEligibleCirclesForUser($user);
                            $circlesCreated += $eligibleCircles->count();
                        }

                        $processedCount++;
                    } catch (\Exception $e) {
                        $errors++;
                        $this->error("Error processing user {$user->id}: " . $e->getMessage());
                    }

                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->info('Circle generation completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users processed', $processedCount],
                ['Circles created/would be created', $circlesCreated],
                ['Errors', $errors],
            ]
        );

        // Show circle statistics
        if (!$dryRun) {
            $this->newLine();
            $this->info('Current circle statistics:');
            $stats = $this->circleManager->getCircleStatistics();
            
            $this->table(
                ['Statistic', 'Value'],
                [
                    ['Total circles', $stats['total_circles']],
                    ['Auto-generated circles', $stats['auto_generated_circles']],
                    ['Custom circles', $stats['custom_circles']],
                    ['School-year circles', $stats['school_year_circles']],
                    ['Multi-school circles', $stats['multi_school_circles']],
                    ['Average members per circle', round($stats['average_members_per_circle'], 2)],
                    ['Largest circle size', $stats['largest_circle_size']],
                ]
            );
        }

        // Cleanup empty circles if not in dry run mode
        if (!$dryRun && !$force) {
            $this->info('Cleaning up empty circles...');
            $deletedCount = $this->circleManager->cleanupEmptyCircles();
            $this->info("Deleted {$deletedCount} empty circles.");
        }

        if ($errors > 0) {
            $this->warn("Completed with {$errors} errors. Check the logs for details.");
            return self::FAILURE;
        }

        $this->info('All done!');
        return self::SUCCESS;
    }
}