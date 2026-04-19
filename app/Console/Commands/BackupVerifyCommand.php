<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Command for verifying backup integrity
 */
class BackupVerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backup:verify {--path= : Specific backup file to verify}';

    /**
     * The console command description.
     */
    protected $description = 'Verify backup integrity';

    /**
     * Backup service instance
     */
    private BackupService $backupService;

    /**
     * Create a new command instance.
     */
    public function __construct(BackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificPath = $this->option('path');

        $this->info('Starting backup verification...');

        try {
            if ($specificPath) {
                // Verify specific backup
                $result = $this->backupService->verifyBackup($specificPath);
                $this->displayVerificationResult($specificPath, $result);
            } else {
                // Get all recent backups and verify them
                $backups = $this->backupService->listBackups();
                $allValid = true;

                $this->info('Found '.count($backups).' backups to verify');

                $progressBar = $this->output->createProgressBar(count($backups));
                $progressBar->start();

                foreach ($backups as $backup) {
                    $result = $this->backupService->verifyBackup($backup['path']);

                    if (! $result['is_valid']) {
                        $allValid = false;
                        $this->newLine();
                        $this->error("❌ Invalid backup: {$backup['path']}");
                        foreach ($result['issues'] as $issue) {
                            $this->error("  - {$issue}");
                        }
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine();

                if ($allValid) {
                    $this->info('✅ All backups verified successfully!');
                } else {
                    $this->warn('⚠️ Some backups failed verification');
                }

                Log::info('Backup verification completed', ['all_valid' => $allValid, 'total_backups' => count($backups)]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Verification failed: {$e->getMessage()}");
            Log::error('Backup verification failed', ['error' => $e->getMessage()]);

            return Command::FAILURE;
        }
    }

    /**
     * Display verification result for a single backup
     */
    private function displayVerificationResult(string $path, array $result): void
    {
        $this->info("Verifying: {$path}");

        if ($result['is_valid']) {
            $this->info('✅ Backup is valid');
        } else {
            $this->error('❌ Backup verification failed');
            foreach ($result['issues'] as $issue) {
                $this->error("  - {$issue}");
            }
        }

        if (! empty($result['warnings'])) {
            $this->warn('Warnings:');
            foreach ($result['warnings'] as $warning) {
                $this->warn("  - {$warning}");
            }
        }
    }
}
