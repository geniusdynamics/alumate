<?php

namespace App\Jobs;

use App\Models\EmailSequence;
use App\Models\User;
use App\Services\EmailSendingService;
use App\Services\EmailTrackingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendSequenceEmailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $maxExceptions = 1;
    public int $backoff = 60; // 1 minute delay between retries

    public function __construct(
        public EmailSequence $sequence,
        public User $recipient,
        public array $personalizationData = []
    ) {}

    public function handle(EmailSendingService $emailSendingService, EmailTrackingService $emailTrackingService): void
    {
        $cacheKey = "sequence_email:{$this->sequence->id}:{$this->recipient->id}";

        // Prevent duplicate sends within a short time window
        if (Cache::has($cacheKey)) {
            Log::info('Skipping duplicate sequence email send', [
                'sequence_id' => $this->sequence->id,
                'recipient_id' => $this->recipient->id,
            ]);
            return;
        }

        try {
            Log::info('Starting sequence email send', [
                'sequence_id' => $this->sequence->id,
                'sequence_name' => $this->sequence->name,
                'recipient_id' => $this->recipient->id,
                'recipient_email' => $this->recipient->email,
            ]);

            // Prepare email content with personalization
            $emailContent = $this->prepareEmailContent();

            // Send the email
            $result = $emailSendingService->sendEmail([
                'to' => $this->recipient->email,
                'subject' => $emailContent['subject'],
                'content' => $emailContent['content'],
                'template_data' => $emailContent['template_data'],
                'provider' => $this->sequence->provider ?? 'internal',
                'tracking_enabled' => true,
                'sequence_id' => $this->sequence->id,
                'recipient_id' => $this->recipient->id,
            ]);

            if ($result['success']) {
                // Set cache to prevent duplicate sends for 1 hour
                Cache::put($cacheKey, true, 3600);

                // Track the email send
                $emailTrackingService->trackEmailSend(
                    $this->sequence,
                    $this->recipient,
                    $result['message_id'] ?? null,
                    $result['provider_message_id'] ?? null
                );

                Log::info('Sequence email sent successfully', [
                    'sequence_id' => $this->sequence->id,
                    'recipient_id' => $this->recipient->id,
                    'message_id' => $result['message_id'] ?? null,
                ]);
            } else {
                Log::error('Sequence email send failed', [
                    'sequence_id' => $this->sequence->id,
                    'recipient_id' => $this->recipient->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                $this->fail('Email send failed: ' . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            Log::error('Sequence email send job failed', [
                'sequence_id' => $this->sequence->id,
                'recipient_id' => $this->recipient->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function prepareEmailContent(): array
    {
        $content = $this->sequence->content;
        $subject = $this->sequence->subject;

        // Apply personalization
        $personalizationData = array_merge([
            'recipient_name' => $this->recipient->name,
            'recipient_email' => $this->recipient->email,
            'recipient_first_name' => explode(' ', $this->recipient->name)[0] ?? $this->recipient->name,
        ], $this->personalizationData);

        foreach ($personalizationData as $key => $value) {
            $placeholder = "{{$key}}";
            $content = str_replace($placeholder, $value, $content);
            $subject = str_replace($placeholder, $value, $subject);
        }

        return [
            'subject' => $subject,
            'content' => $content,
            'template_data' => $personalizationData,
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Sequence email send job permanently failed', [
            'sequence_id' => $this->sequence->id,
            'recipient_id' => $this->recipient->id,
            'error' => $exception->getMessage(),
        ]);

        // Update sequence recipient status to failed
        $this->sequence->recipients()
            ->where('user_id', $this->recipient->id)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Rate limiting middleware to prevent overwhelming email providers
            new \Illuminate\Queue\Middleware\RateLimited('email-sending'),
        ];
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email-sequence',
            "sequence:{$this->sequence->id}",
            "recipient:{$this->recipient->id}",
        ];
    }
}