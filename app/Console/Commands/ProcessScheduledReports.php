<?php

namespace App\Console\Commands;

use App\Models\CustomReport;
use App\Services\ReportBuilderService;
use Illuminate\Console\Command;

class ProcessScheduledReports extends Command
{
    protected $signature = 'analytics:process-scheduled-reports 
                            {--report= : Specific report ID to process}
                            {--dry-run : Show which reports would be processed without executing them}';

    protected $description = 'Process scheduled analytics reports and generate deliveries';

    protected $reportBuilderService;

    public function __construct(ReportBuilderService $reportBuilderService)
    {
        parent::__construct();
        $this->reportBuilderService = $reportBuilderService;
    }

    public function handle()
    {
        $reportId = $this->option('report');
        $dryRun = $this->option('dry-run');

        $this->info('Processing scheduled reports...');

        try {
            if ($reportId) {
                $this->processSpecificReport($reportId, $dryRun);
            } else {
                $this->processAllScheduledReports($dryRun);
            }

            $this->info('Scheduled reports processing completed!');

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to process scheduled reports: '.$e->getMessage());

            return 1;
        }
    }

    private function processSpecificReport($reportId, $dryRun)
    {
        $report = CustomReport::find($reportId);

        if (! $report) {
            $this->error("Report not found: {$reportId}");

            return;
        }

        if (! $report->is_scheduled) {
            $this->warn("Report is not scheduled: {$report->name}");

            return;
        }

        if ($dryRun) {
            $this->info("Would process report: {$report->name}");

            return;
        }

        $this->processReport($report);
    }

    private function processAllScheduledReports($dryRun)
    {
        $reports = CustomReport::scheduled()
            ->with(['user', 'latestExecution'])
            ->get()
            ->filter(function ($report) {
                return $report->shouldRunScheduled();
            });

        if ($reports->isEmpty()) {
            $this->info('No scheduled reports need processing at this time.');

            return;
        }

        if ($dryRun) {
            $this->info('Reports that would be processed:');
            foreach ($reports as $report) {
                $this->line("  - {$report->name} (ID: {$report->id}) - {$report->schedule_frequency}");
            }

            return;
        }

        $bar = $this->output->createProgressBar($reports->count());
        $bar->start();

        $processed = 0;
        $failed = 0;

        foreach ($reports as $report) {
            try {
                $this->processReport($report);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to process report {$report->name}: ".$e->getMessage());
                \Log::error('Scheduled report processing failed', [
                    'report_id' => $report->id,
                    'report_name' => $report->name,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Processing summary:');
        $this->line("  ✓ Processed: {$processed}");
        if ($failed > 0) {
            $this->line("  ✗ Failed: {$failed}");
        }
    }

    private function processReport(CustomReport $report)
    {
        $this->info("Processing report: {$report->name}");

        // Determine output format based on schedule config
        $format = $report->schedule_config['format'] ?? 'csv';
        $parameters = array_merge($report->filters, ['format' => $format]);

        // Execute the report
        $execution = $this->reportBuilderService->executeReport($report, $parameters);

        if ($execution->isCompleted()) {
            $this->info("✓ Report completed: {$report->name}");

            // Send delivery if configured
            $this->deliverReport($report, $execution);
        } else {
            throw new \Exception('Report execution failed: '.$execution->error_message);
        }
    }

    private function deliverReport(CustomReport $report, $execution)
    {
        $deliveryConfig = $report->schedule_config['delivery'] ?? [];

        if (empty($deliveryConfig)) {
            return;
        }

        $deliveryMethod = $deliveryConfig['method'] ?? 'email';

        switch ($deliveryMethod) {
            case 'email':
                $this->deliverByEmail($report, $execution, $deliveryConfig);
                break;
            case 'slack':
                $this->deliverBySlack($report, $execution, $deliveryConfig);
                break;
            case 'webhook':
                $this->deliverByWebhook($report, $execution, $deliveryConfig);
                break;
            default:
                $this->warn("Unknown delivery method: {$deliveryMethod}");
        }
    }

    private function deliverByEmail(CustomReport $report, $execution, $config)
    {
        $recipients = $config['recipients'] ?? [$report->user->email];

        foreach ($recipients as $recipient) {
            try {
                \Mail::to($recipient)->send(new \App\Mail\ScheduledReportDelivery($report, $execution));
                $this->info("  ✓ Email sent to: {$recipient}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to send email to {$recipient}: ".$e->getMessage());
            }
        }
    }

    private function deliverBySlack(CustomReport $report, $execution, $config)
    {
        $webhookUrl = $config['webhook_url'] ?? null;

        if (! $webhookUrl) {
            $this->error('  ✗ Slack webhook URL not configured');

            return;
        }

        try {
            $message = [
                'text' => "Scheduled Report: {$report->name}",
                'attachments' => [
                    [
                        'color' => 'good',
                        'fields' => [
                            [
                                'title' => 'Report',
                                'value' => $report->name,
                                'short' => true,
                            ],
                            [
                                'title' => 'Records',
                                'value' => $execution->result_data['total_records'] ?? 'N/A',
                                'short' => true,
                            ],
                            [
                                'title' => 'Generated',
                                'value' => $execution->completed_at->format('Y-m-d H:i:s'),
                                'short' => true,
                            ],
                        ],
                    ],
                ],
            ];

            $response = \Http::post($webhookUrl, $message);

            if ($response->successful()) {
                $this->info('  ✓ Slack notification sent');
            } else {
                $this->error('  ✗ Slack notification failed: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to send Slack notification: '.$e->getMessage());
        }
    }

    private function deliverByWebhook(CustomReport $report, $execution, $config)
    {
        $webhookUrl = $config['url'] ?? null;

        if (! $webhookUrl) {
            $this->error('  ✗ Webhook URL not configured');

            return;
        }

        try {
            $payload = [
                'report' => [
                    'id' => $report->id,
                    'name' => $report->name,
                    'type' => $report->type,
                ],
                'execution' => [
                    'id' => $execution->id,
                    'status' => $execution->status,
                    'completed_at' => $execution->completed_at,
                    'file_path' => $execution->file_path,
                    'total_records' => $execution->result_data['total_records'] ?? 0,
                ],
                'download_url' => $execution->getDownloadUrl(),
            ];

            $headers = $config['headers'] ?? [];

            $response = \Http::withHeaders($headers)->post($webhookUrl, $payload);

            if ($response->successful()) {
                $this->info('  ✓ Webhook delivered');
            } else {
                $this->error('  ✗ Webhook delivery failed: '.$response->body());
            }
        } catch (\Exception $e) {
            $this->error('  ✗ Failed to deliver webhook: '.$e->getMessage());
        }
    }
}
