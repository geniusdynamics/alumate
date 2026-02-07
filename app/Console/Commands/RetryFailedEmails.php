<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EmailDeliveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Retry Failed Emails Command
 *
 * Artisan command to retry failed emails with exponential backoff.
 * Can be scheduled to run periodically for automatic retry processing.
 */
class RetryFailedEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:retry-failed
                            {--limit=100 : Maximum number of emails to retry}
                            {--provider= : Retry only emails from specific provider}
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be retried without actually retrying}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed emails with exponential backoff';

    /**
     * Email delivery service instance
     */
    protected EmailDeliveryService $deliveryService;

    /**
     * Create a new command instance
     */
    public function __construct(EmailDeliveryService $deliveryService)
    {
        parent::__construct();
        $this->deliveryService = $deliveryService;
    }

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $provider = $this->option('provider');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Validate limit
        if ($limit < 1 || $limit > 1000) {
            $this->error('Limit must be between 1 and 1000');

            return self::FAILURE;
        }

        // Validate provider if specified
        if ($provider && ! in_array($provider, EmailDeliveryService::PROVIDERS)) {
            $this->error("Invalid provider: {$provider}");
            $this->info('Valid providers: ' . implode(', ', EmailDeliveryService::PROVIDERS));

            return self::FAILURE;
        }

        // Get failed emails ready for retry
        $failedEmails = $this->getFailedEmails($limit, $provider);

        if ($failedEmails->isEmpty()) {
            $this->info('No failed emails ready for retry.');

            return self::SUCCESS;
        }

        $this->info("Found {$failedEmails->count()} failed email(s) ready for retry.");

        // Show summary
        $this->displaySummary($failedEmails);

        // Dry run mode
        if ($dryRun) {
            $this->warn('Dry run mode - no emails will be retried.');

            return self::SUCCESS;
        }

        // Confirm if not forced
        if (! $force) {
            if (! $this->confirm('Do you want to retry these emails?')) {
                $this->info('Retry cancelled.');

                return self::SUCCESS;
            }
        }

        // Process retries
        $this->info('Processing retries...');
        $this->newLine();

        $progressBar = $this->output->createProgressBar($failedEmails->count());
        $progressBar->start();

        $results = [
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($failedEmails as $emailLog) {
            try {
                // Get original email data from metadata
                $emailData = $emailLog->metadata['original_data'] ?? [
                    'to' => $emailLog->recipient_email,
                    'from' => $emailLog->sender_email,
                    'subject' => $emailLog->subject,
                    'template' => $emailLog->template,
                ];

                // Attempt to resend
                $result = $this->deliveryService->send($emailData, $emailLog->provider);

                if ($result->isSent() || $result->isDelivered()) {
                    $results['successful']++;

                    Log::info('Email retry successful', [
                        'email_log_id' => $emailLog->id,
                        'recipient' => $emailLog->recipient_email,
                    ]);
                } else {
                    $results['failed']++;

                    Log::warning('Email retry failed', [
                        'email_log_id' => $emailLog->id,
                        'recipient' => $emailLog->recipient_email,
                        'error' => $result->error_message,
                    ]);
                }
            } catch (\Exception $e) {
                $results['failed']++;

                Log::error('Email retry exception', [
                    'email_log_id' => $emailLog->id,
                    'recipient' => $emailLog->recipient_email,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->displayResults($results);

        return self::SUCCESS;
    }

    /**
     * Get failed emails ready for retry
     */
    protected function getFailedEmails(int $limit, ?string $provider = null): \Illuminate\Support\Collection
    {
        $query = \App\Models\EmailLog::readyForRetry();

        if ($provider) {
            $query->byProvider($provider);
        }

        return $query->limit($limit)
            ->orderBy('next_retry_at')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Display summary of failed emails
     */
    protected function displaySummary(\Illuminate\Support\Collection $emails): void
    {
        $this->newLine();
        $this->info('Summary of emails to retry:');
        $this->newLine();

        $headers = ['ID', 'Recipient', 'Subject', 'Provider', 'Retry Count', 'Next Retry'];
        $rows = $emails->map(function ($email) {
            return [
                $email->id,
                str_limit($email->recipient_email, 30),
                str_limit($email->subject, 40),
                $email->provider,
                $email->retry_count,
                $email->next_retry_at?->diffForHumans() ?? 'Now',
            ];
        })->toArray();

        $this->table($headers, $rows);

        // Show breakdown by provider
        $byProvider = $emails->groupBy('provider')->map->count();
        $this->newLine();
        $this->info('Breakdown by provider:');
        foreach ($byProvider as $provider => $count) {
            $this->line("  - {$provider}: {$count}");
        }

        $this->newLine();
    }

    /**
     * Display retry results
     */
    protected function displayResults(array $results): void
    {
        $this->info('Retry completed!');
        $this->newLine();

        $total = array_sum($results);

        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Successful', $results['successful'], $total > 0 ? round(($results['successful'] / $total) * 100, 1) . '%' : '0%'],
                ['Failed', $results['failed'], $total > 0 ? round(($results['failed'] / $total) * 100, 1) . '%' : '0%'],
                ['Skipped', $results['skipped'], $total > 0 ? round(($results['skipped'] / $total) * 100, 1) . '%' : '0%'],
            ]
        );

        $this->newLine();

        if ($results['successful'] > 0) {
            $this->info("✓ {$results['successful']} email(s) retried successfully");
        }

        if ($results['failed'] > 0) {
            $this->error("✗ {$results['failed']} email(s) failed again");
        }

        if ($results['skipped'] > 0) {
            $this->warn("⚠ {$results['skipped']} email(s) skipped");
        }

        $this->newLine();
        $this->info('Run `emails:retry-failed` again to retry any remaining failed emails.');
    }
}
