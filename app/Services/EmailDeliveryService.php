<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmailLog;
use App\Services\TenantContextService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Collection;
use Exception;

/**
 * Email Delivery Service
 *
 * Core service for managing email delivery across multiple providers (SendGrid, Mailgun, AWS SES).
 * Handles sending, bulk operations, bounce/complaint processing, retry logic with exponential backoff,
 * and queue management.
 */
class EmailDeliveryService extends BaseService
{
    /**
     * Supported email providers
     */
    public const PROVIDER_SENDGRID = 'sendgrid';
    public const PROVIDER_MAILGUN = 'mailgun';
    public const PROVIDER_SES = 'ses';
    public const PROVIDER_INTERNAL = 'internal';

    public const PROVIDERS = [
        self::PROVIDER_SENDGRID,
        self::PROVIDER_MAILGUN,
        self::PROVIDER_SES,
        self::PROVIDER_INTERNAL,
    ];

    /**
     * Rate limits per provider (emails per minute)
     */
    protected array $rateLimits = [
        self::PROVIDER_SENDGRID => 100,
        self::PROVIDER_MAILGUN => 300,
        self::PROVIDER_SES => 14,
        self::PROVIDER_INTERNAL => 60,
    ];

    /**
     * Batch sizes per provider
     */
    protected array $batchSizes = [
        self::PROVIDER_SENDGRID => 1000,
        self::PROVIDER_MAILGUN => 1000,
        self::PROVIDER_SES => 50,
        self::PROVIDER_INTERNAL => 100,
    ];

    protected array $providerConfigs = [];

    public function __construct(TenantContextService $tenantContext)
    {
        parent::__construct($tenantContext);
        $this->loadProviderConfigurations();
    }

    /**
     * Send a single email
     */
    public function send(array $emailData, string $provider = self::PROVIDER_INTERNAL): EmailLog
    {
        $this->ensureTenantContext();

        try {
            // Validate provider
            if (! in_array($provider, self::PROVIDERS)) {
                throw new Exception("Unsupported email provider: {$provider}");
            }

            // Check rate limits
            if (! $this->checkRateLimit($provider)) {
                throw new Exception("Rate limit exceeded for provider: {$provider}");
            }

            // Create email log entry
            $emailLog = $this->createEmailLog($emailData, $provider);

            // Send via appropriate provider
            $result = $this->sendViaProvider($emailData, $provider);

            if ($result['success']) {
                $emailLog->markAsSent($result['provider_id'] ?? null);
                $this->incrementRateLimit($provider);

                Log::info('Email sent successfully', [
                    'email_log_id' => $emailLog->id,
                    'provider' => $provider,
                    'recipient' => $emailData['to'] ?? 'unknown',
                ]);
            } else {
                $emailLog->markAsFailed($result['error'] ?? 'Unknown error');

                Log::error('Email send failed', [
                    'email_log_id' => $emailLog->id,
                    'provider' => $provider,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $emailLog;
        } catch (Exception $e) {
            Log::error('Email delivery service error', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'recipient' => $emailData['to'] ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);

            // Create failed log entry if not already created
            if (! isset($emailLog)) {
                $emailLog = $this->createEmailLog($emailData, $provider);
                $emailLog->markAsFailed($e->getMessage());
            }

            return $emailLog;
        }
    }

    /**
     * Send bulk emails with batching and rate limiting
     */
    public function sendBulk(array $emailsData, string $provider = self::PROVIDER_INTERNAL): array
    {
        $this->ensureTenantContext();

        $results = [
            'total' => count($emailsData),
            'successful' => 0,
            'failed' => 0,
            'logs' => [],
        ];

        $batchSize = $this->batchSizes[$provider] ?? 100;
        $batches = array_chunk($emailsData, $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            Log::info('Processing email batch', [
                'batch' => $batchIndex + 1,
                'batch_size' => count($batch),
                'provider' => $provider,
            ]);

            foreach ($batch as $emailData) {
                $emailLog = $this->send($emailData, $provider);
                $results['logs'][] = $emailLog;

                if ($emailLog->isSent() || $emailLog->isDelivered()) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                }

                // Small delay between emails for rate limiting
                usleep(100000); // 100ms delay
            }

            // Delay between batches
            if ($batchIndex < count($batches) - 1) {
                sleep(1);
            }
        }

        Log::info('Bulk email send completed', [
            'total' => $results['total'],
            'successful' => $results['successful'],
            'failed' => $results['failed'],
            'provider' => $provider,
        ]);

        return $results;
    }

    /**
     * Handle bounce notification from email provider
     */
    public function handleBounce(array $bounceData): ?EmailLog
    {
        $emailLog = $this->findEmailLogByProviderData($bounceData);

        if (! $emailLog) {
            Log::warning('Bounce notification received but email log not found', $bounceData);
            return null;
        }

        $bounceType = $bounceData['bounce_type'] ?? EmailLog::BOUNCE_TYPE_SOFT;
        $bounceReason = $bounceData['reason'] ?? $bounceData['description'] ?? null;

        $emailLog->markAsBounced($bounceType, $bounceReason);

        // Handle hard bounces - unsubscribe user if configured
        if ($bounceType === EmailLog::BOUNCE_TYPE_HARD) {
            $this->handleHardBounce($emailLog);
        }

        Log::info('Email bounce processed', [
            'email_log_id' => $emailLog->id,
            'bounce_type' => $bounceType,
            'recipient' => $emailLog->recipient_email,
        ]);

        return $emailLog;
    }

    /**
     * Handle spam complaint notification
     */
    public function handleSpamComplaint(array $complaintData): ?EmailLog
    {
        $emailLog = $this->findEmailLogByProviderData($complaintData);

        if (! $emailLog) {
            Log::warning('Spam complaint received but email log not found', $complaintData);
            return null;
        }

        // Automatically unsubscribe user on spam complaint
        if ($emailLog->user_id) {
            $this->unsubscribe($emailLog->recipient_email, 'spam_complaint');
        }

        $emailLog->update([
            'metadata' => array_merge($emailLog->metadata ?? [], [
                'spam_complaint' => true,
                'complaint_data' => $complaintData,
            ]),
        ]);

        Log::info('Spam complaint processed', [
            'email_log_id' => $emailLog->id,
            'recipient' => $emailLog->recipient_email,
        ]);

        return $emailLog;
    }

    /**
     * Unsubscribe an email address
     */
    public function unsubscribe(string $email, string $reason = 'user_request'): bool
    {
        try {
            // Update email preferences if user exists
            $user = \App\Models\User::where('email', $email)->first();
            if ($user) {
                \App\Models\EmailPreference::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'subscribed' => false,
                        'unsubscribed_at' => now(),
                        'unsubscribe_reason' => $reason,
                    ]
                );
            }

            // Update all pending emails for this address
            EmailLog::where('recipient_email', $email)
                ->whereIn('status', [EmailLog::STATUS_QUEUED, EmailLog::STATUS_FAILED])
                ->update([
                    'status' => EmailLog::STATUS_FAILED,
                    'error_message' => 'Unsubscribed: ' . $reason,
                ]);

            Log::info('Email unsubscribed', [
                'email' => $email,
                'reason' => $reason,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Unsubscribe failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Track deliverability metrics
     */
    public function trackDeliverability(?string $provider = null, ?int $days = 30): array
    {
        $query = EmailLog::query();

        if ($provider) {
            $query->byProvider($provider);
        }

        if ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $total = $query->count();
        $sent = (clone $query)->sent()->count();
        $delivered = (clone $query)->delivered()->count();
        $bounced = (clone $query)->bounced()->count();
        $failed = (clone $query)->failed()->count();
        $opened = (clone $query)->opened()->count();
        $clicked = (clone $query)->clicked()->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'delivered' => $delivered,
            'bounced' => $bounced,
            'failed' => $failed,
            'opened' => $opened,
            'clicked' => $clicked,
            'delivery_rate' => $sent > 0 ? round(($delivered / $sent) * 100, 2) : 0,
            'bounce_rate' => $sent > 0 ? round(($bounced / $sent) * 100, 2) : 0,
            'open_rate' => $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0,
            'click_rate' => $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0,
            'provider' => $provider,
            'period_days' => $days,
        ];
    }

    /**
     * Retry failed emails with exponential backoff
     */
    public function retryFailed(int $limit = 100): array
    {
        $results = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $failedEmails = EmailLog::readyForRetry()
            ->limit($limit)
            ->get();

        foreach ($failedEmails as $emailLog) {
            $results['processed']++;

            try {
                // Check if max retries reached
                if ($emailLog->retry_count >= 5) {
                    $results['skipped']++;
                    continue;
                }

                // Get original email data from metadata
                $emailData = $emailLog->metadata['original_data'] ?? [
                    'to' => $emailLog->recipient_email,
                    'from' => $emailLog->sender_email,
                    'subject' => $emailLog->subject,
                    'template' => $emailLog->template,
                ];

                // Attempt to resend
                $result = $this->sendViaProvider($emailData, $emailLog->provider);

                if ($result['success']) {
                    $emailLog->markAsSent($result['provider_id'] ?? null);
                    $emailLog->update([
                        'error_message' => null,
                        'failed_at' => null,
                    ]);
                    $results['successful']++;
                } else {
                    $emailLog->incrementRetry();
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $emailLog->incrementRetry();
                $results['failed']++;

                Log::error('Retry failed for email', [
                    'email_log_id' => $emailLog->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Retry failed emails completed', $results);

        return $results;
    }

    /**
     * Queue an email for asynchronous sending
     */
    public function queue(array $emailData, string $provider = self::PROVIDER_INTERNAL, ?string $queue = null): EmailLog
    {
        $emailLog = $this->createEmailLog($emailData, $provider);

        // Dispatch job
        \App\Jobs\SendEmailJob::dispatch($emailLog->id, $emailData, $provider)
            ->onQueue($queue ?? 'emails');

        Log::info('Email queued', [
            'email_log_id' => $emailLog->id,
            'provider' => $provider,
            'queue' => $queue ?? 'emails',
        ]);

        return $emailLog;
    }

    /**
     * Get provider health status
     */
    public function getProviderHealth(string $provider): array
    {
        try {
            $stats = $this->trackDeliverability($provider, 1); // Last 24 hours

            $healthy = $stats['bounce_rate'] < 5 && $stats['delivery_rate'] > 95;

            return [
                'provider' => $provider,
                'healthy' => $healthy,
                'delivery_rate' => $stats['delivery_rate'],
                'bounce_rate' => $stats['bounce_rate'],
                'rate_limit_remaining' => $this->getRemainingRateLimit($provider),
                'last_hour_volume' => EmailLog::byProvider($provider)
                    ->where('created_at', '>=', now()->subHour())
                    ->count(),
            ];
        } catch (Exception $e) {
            return [
                'provider' => $provider,
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create email log entry
     */
    protected function createEmailLog(array $emailData, string $provider): EmailLog
    {
        return EmailLog::create([
            'recipient_email' => $emailData['to'],
            'sender_email' => $emailData['from'] ?? config('mail.from.address'),
            'subject' => $emailData['subject'],
            'template' => $emailData['template'] ?? null,
            'status' => EmailLog::STATUS_QUEUED,
            'provider' => $provider,
            'tracking_id' => $this->generateTrackingId(),
            'metadata' => [
                'original_data' => $emailData,
            ],
            'tenant_id' => $this->getCurrentTenantId(),
            'user_id' => $emailData['user_id'] ?? null,
        ]);
    }

    /**
     * Send email via specified provider
     */
    protected function sendViaProvider(array $emailData, string $provider): array
    {
        return match ($provider) {
            self::PROVIDER_SENDGRID => $this->sendViaSendGrid($emailData),
            self::PROVIDER_MAILGUN => $this->sendViaMailgun($emailData),
            self::PROVIDER_SES => $this->sendViaSes($emailData),
            default => $this->sendViaInternal($emailData),
        };
    }

    /**
     * Send via SendGrid
     */
    protected function sendViaSendGrid(array $emailData): array
    {
        try {
            $config = $this->providerConfigs[self::PROVIDER_SENDGRID];

            // Implementation would use SendGrid API
            // $sendgrid = new \SendGrid($config['api_key']);
            // $response = $sendgrid->send($email);

            // Simulated success for now
            return [
                'success' => true,
                'provider_id' => 'sg_' . uniqid(),
                'message_id' => uniqid('sendgrid_'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via Mailgun
     */
    protected function sendViaMailgun(array $emailData): array
    {
        try {
            $config = $this->providerConfigs[self::PROVIDER_MAILGUN];

            // Implementation would use Mailgun API
            // $mg = \Mailgun\Mailgun::create($config['api_key']);
            // $response = $mg->messages()->send($config['domain'], $emailData);

            // Simulated success for now
            return [
                'success' => true,
                'provider_id' => 'mg_' . uniqid(),
                'message_id' => uniqid('mailgun_'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via AWS SES
     */
    protected function sendViaSes(array $emailData): array
    {
        try {
            $config = $this->providerConfigs[self::PROVIDER_SES];

            // Implementation would use AWS SES SDK
            // $client = new \Aws\Ses\SesClient([
            //     'version' => 'latest',
            //     'region' => $config['region'],
            //     'credentials' => [
            //         'key' => $config['key'],
            //         'secret' => $config['secret'],
            //     ],
            // ]);

            // Simulated success for now
            return [
                'success' => true,
                'provider_id' => 'ses_' . uniqid(),
                'message_id' => uniqid('ses_'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send via internal Laravel mail
     */
    protected function sendViaInternal(array $emailData): array
    {
        try {
            \Mail::send(
                $emailData['template'] ?? 'emails.default',
                $emailData['data'] ?? [],
                function ($message) use ($emailData) {
                    $message->to($emailData['to'])
                        ->subject($emailData['subject']);

                    if (isset($emailData['from'])) {
                        $message->from($emailData['from']);
                    }

                    if (isset($emailData['cc'])) {
                        $message->cc($emailData['cc']);
                    }

                    if (isset($emailData['bcc'])) {
                        $message->bcc($emailData['bcc']);
                    }

                    if (isset($emailData['attachments'])) {
                        foreach ($emailData['attachments'] as $attachment) {
                            $message->attach($attachment);
                        }
                    }
                }
            );

            return [
                'success' => true,
                'provider_id' => 'internal_' . uniqid(),
                'message_id' => uniqid('internal_'),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find email log by provider data
     */
    protected function findEmailLogByProviderData(array $data): ?EmailLog
    {
        // Try by provider message ID
        if (! empty($data['message_id'])) {
            $log = EmailLog::where('provider_id', $data['message_id'])->first();
            if ($log) {
                return $log;
            }
        }

        // Try by tracking ID
        if (! empty($data['tracking_id'])) {
            $log = EmailLog::where('tracking_id', $data['tracking_id'])->first();
            if ($log) {
                return $log;
            }
        }

        // Try by email address and recent timestamp
        if (! empty($data['email'])) {
            $log = EmailLog::where('recipient_email', $data['email'])
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('created_at', 'desc')
                ->first();
            if ($log) {
                return $log;
            }
        }

        return null;
    }

    /**
     * Handle hard bounce - unsubscribe if configured
     */
    protected function handleHardBounce(EmailLog $emailLog): void
    {
        if (config('services.email.unsubscribe_on_hard_bounce', false)) {
            $this->unsubscribe($emailLog->recipient_email, 'hard_bounce');
        }
    }

    /**
     * Generate unique tracking ID
     */
    protected function generateTrackingId(): string
    {
        return uniqid('eml_', true) . bin2hex(random_bytes(8));
    }

    /**
     * Check rate limit for provider
     */
    protected function checkRateLimit(string $provider): bool
    {
        $limit = $this->rateLimits[$provider] ?? 60;
        $key = "email_rate_limit:{$provider}:" . now()->format('Y-m-d-H-i');
        $current = Cache::get($key, 0);

        return $current < $limit;
    }

    /**
     * Increment rate limit counter
     */
    protected function incrementRateLimit(string $provider): void
    {
        $key = "email_rate_limit:{$provider}:" . now()->format('Y-m-d-H-i');
        Cache::increment($key, 1, 60); // 1 minute TTL
    }

    /**
     * Get remaining rate limit
     */
    protected function getRemainingRateLimit(string $provider): int
    {
        $limit = $this->rateLimits[$provider] ?? 60;
        $key = "email_rate_limit:{$provider}:" . now()->format('Y-m-d-H-i');
        $current = Cache::get($key, 0);

        return max(0, $limit - $current);
    }

    /**
     * Load provider configurations
     */
    protected function loadProviderConfigurations(): void
    {
        $this->providerConfigs = [
            self::PROVIDER_SENDGRID => [
                'api_key' => config('services.sendgrid.api_key'),
                'endpoint' => config('services.sendgrid.endpoint', 'https://api.sendgrid.com/v3'),
            ],
            self::PROVIDER_MAILGUN => [
                'api_key' => config('services.mailgun.secret'),
                'domain' => config('services.mailgun.domain'),
                'endpoint' => config('services.mailgun.endpoint', 'https://api.mailgun.net/v3'),
            ],
            self::PROVIDER_SES => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
                'region' => config('services.ses.region', 'us-east-1'),
            ],
            self::PROVIDER_INTERNAL => [
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ];
    }
}
