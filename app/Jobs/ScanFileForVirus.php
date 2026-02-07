<?php
// ABOUTME: Background job for scanning uploaded files for viruses using ClamAV
// ABOUTME: or other configured antivirus scanners

declare(strict_types=1);

namespace App\Jobs;

use App\Models\StoredFile;
use App\Services\FileStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\VirusDetectedNotification;
use Exception;

class ScanFileForVirus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        protected StoredFile $storedFile
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(FileStorageService $fileStorage): void
    {
        try {
            Log::info('Starting virus scan', [
                'file_id' => $this->storedFile->id,
                'filename' => $this->storedFile->filename,
                'user_id' => $this->storedFile->user_id,
            ]);

            // Check if virus scanning is enabled
            if (!config('filesystems.virus_scanning.enabled', true)) {
                Log::info('Virus scanning disabled, marking as clean', [
                    'file_id' => $this->storedFile->id,
                ]);

                $this->storedFile->markAsScanned(StoredFile::SCAN_CLEAN);
                return;
            }

            // Perform the scan
            $result = $fileStorage->scanForVirus($this->storedFile);

            // Handle scan result
            match ($result['status']) {
                StoredFile::SCAN_CLEAN => $this->handleCleanResult(),
                StoredFile::SCAN_INFECTED => $this->handleInfectedResult($result),
                default => $this->handleUnknownResult($result),
            };

            Log::info('Virus scan completed', [
                'file_id' => $this->storedFile->id,
                'status' => $result['status'],
            ]);
        } catch (Exception $e) {
            Log::error('Virus scan failed', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Mark as pending to allow retry
            $this->storedFile->update([
                'virus_scan_status' => StoredFile::SCAN_PENDING,
                'metadata' => array_merge($this->storedFile->metadata ?? [], [
                    'scan_error' => $e->getMessage(),
                    'scan_attempted_at' => now()->toIso8601String(),
                ]),
            ]);

            throw $e;
        }
    }

    /**
     * Handle clean scan result
     */
    protected function handleCleanResult(): void
    {
        Log::info('File is clean', [
            'file_id' => $this->storedFile->id,
        ]);

        // Update metadata to record successful scan
        $this->storedFile->updateMetadata([
            'scan_completed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle infected scan result
     */
    protected function handleInfectedResult(array $result): void
    {
        Log::warning('Virus detected in file', [
            'file_id' => $this->storedFile->id,
            'filename' => $this->storedFile->filename,
            'user_id' => $this->storedFile->user_id,
            'details' => $result['details'] ?? 'No details provided',
        ]);

        // Update metadata with infection details
        $this->storedFile->updateMetadata([
            'infection_details' => $result['details'] ?? null,
            'infection_detected_at' => now()->toIso8601String(),
        ]);

        // Notify user
        $this->notifyUserOfInfection();

        // Notify admins
        $this->notifyAdminsOfInfection($result);

        // Auto-delete if configured
        if (config('filesystems.virus_scanning.auto_delete_infected', false)) {
            $this->deleteInfectedFile();
        } else {
            // Quarantine the file - move to quarantine collection
            $this->quarantineFile();
        }
    }

    /**
     * Handle unknown/error scan result
     */
    protected function handleUnknownResult(array $result): void
    {
        Log::warning('Virus scan returned unknown status', [
            'file_id' => $this->storedFile->id,
            'status' => $result['status'] ?? 'unknown',
            'message' => $result['message'] ?? 'No message',
        ]);

        // Keep as pending for retry
        $this->storedFile->update([
            'virus_scan_status' => StoredFile::SCAN_PENDING,
            'metadata' => array_merge($this->storedFile->metadata ?? [], [
                'scan_error' => $result['message'] ?? 'Unknown scan result',
            ]),
        ]);
    }

    /**
     * Notify user that their file was infected
     */
    protected function notifyUserOfInfection(): void
    {
        try {
            $user = $this->storedFile->user;

            if ($user) {
                $user->notify(new VirusDetectedNotification($this->storedFile));

                Log::info('User notified of infected file', [
                    'file_id' => $this->storedFile->id,
                    'user_id' => $user->id,
                ]);
            }
        } catch (Exception $e) {
            Log::error('Failed to notify user of infected file', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admins of infected file
     */
    protected function notifyAdminsOfInfection(array $result): void
    {
        try {
            // Get admin users to notify
            $adminUsers = \App\Models\User::superAdmins()->get();

            foreach ($adminUsers as $admin) {
                $admin->notify(new VirusDetectedNotification(
                    $this->storedFile,
                    true, // is admin notification
                    $result['details'] ?? null
                ));
            }

            Log::info('Admins notified of infected file', [
                'file_id' => $this->storedFile->id,
                'admin_count' => $adminUsers->count(),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to notify admins of infected file', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete infected file from storage
     */
    protected function deleteInfectedFile(): void
    {
        try {
            $this->storedFile->deleteFromStorage();
            $this->storedFile->delete();

            Log::info('Infected file auto-deleted', [
                'file_id' => $this->storedFile->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete infected file', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Move infected file to quarantine
     */
    protected function quarantineFile(): void
    {
        try {
            // Update collection to quarantine
            $this->storedFile->update([
                'collection' => 'quarantine',
                'visibility' => StoredFile::VISIBILITY_PRIVATE,
            ]);

            Log::info('Infected file moved to quarantine', [
                'file_id' => $this->storedFile->id,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to quarantine infected file', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ScanFileForVirus job failed permanently', [
            'file_id' => $this->storedFile->id,
            'filename' => $this->storedFile->filename,
            'error' => $exception->getMessage(),
        ]);

        // Mark scan as pending so it can be retried manually
        try {
            $this->storedFile->update([
                'virus_scan_status' => StoredFile::SCAN_PENDING,
                'metadata' => array_merge($this->storedFile->metadata ?? [], [
                    'scan_failed_at' => now()->toIso8601String(),
                    'scan_error' => $exception->getMessage(),
                ]),
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update file metadata after scan failure', [
                'file_id' => $this->storedFile->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}
