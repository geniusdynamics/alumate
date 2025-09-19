<?php

namespace App\Jobs;

use App\Models\FormSubmission;
use App\Services\CrmIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFormSubmissionToCrm implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public FormSubmission $submission
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CrmIntegrationService $crmService): void
    {
        Log::info('Processing form submission to CRM', [
            'submission_id' => $this->submission->id,
            'form_id' => $this->submission->form_id
        ]);

        $success = $crmService->syncFormSubmissionToCrm($this->submission);

        if ($success) {
            Log::info('Form submission successfully synced to CRM', [
                'submission_id' => $this->submission->id
            ]);
        } else {
            Log::warning('Form submission CRM sync failed', [
                'submission_id' => $this->submission->id
            ]);
            
            // Job will be retried automatically due to $tries setting
            throw new \Exception('CRM sync failed for submission ' . $this->submission->id);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Form submission CRM sync job failed permanently', [
            'submission_id' => $this->submission->id,
            'error' => $exception->getMessage()
        ]);

        // Update submission status to indicate permanent failure
        $this->submission->update([
            'crm_sync_status' => 'failed',
            'crm_sync_error' => [
                'message' => $exception->getMessage(),
                'failed_at' => now()->toISOString(),
                'attempts' => $this->attempts()
            ]
        ]);
    }
}
