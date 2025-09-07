<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Collection;

class EmailSendingService
{
    protected array $providers = [
        'mailgun' => MailgunProvider::class,
        'sendgrid' => SendGridProvider::class,
        'ses' => SESProvider::class,
        'internal' => InternalProvider::class,
    ];

    protected array $providerConfigs = [];
    protected array $rateLimits = [];

    public function __construct()
    {
        $this->loadProviderConfigurations();
        $this->initializeRateLimits();
    }

    /**
     * Send an email through the specified provider
     */
    public function sendEmail(array $emailData): array
    {
        $provider = $emailData['provider'] ?? 'internal';

        // Check rate limits
        if (!$this->checkRateLimit($provider)) {
            Log::warning('Rate limit exceeded for email provider', [
                'provider' => $provider,
                'email' => $emailData['to'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'error' => 'Rate limit exceeded',
                'retry_after' => $this->getRateLimitResetTime($provider),
            ];
        }

        try {
            $providerInstance = $this->getProviderInstance($provider);

            Log::info('Sending email via provider', [
                'provider' => $provider,
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
            ]);

            $result = $providerInstance->sendEmail($emailData);

            // Update rate limit counters
            $this->incrementRateLimit($provider);

            if ($result['success']) {
                Log::info('Email sent successfully', [
                    'provider' => $provider,
                    'to' => $emailData['to'],
                    'message_id' => $result['message_id'] ?? null,
                ]);
            } else {
                Log::error('Email send failed', [
                    'provider' => $provider,
                    'to' => $emailData['to'],
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Email sending service error', [
                'provider' => $provider,
                'to' => $emailData['to'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send bulk emails with batching and rate limiting
     */
    public function sendBulkEmails(array $emailsData, string $provider = 'internal'): array
    {
        $results = [];
        $batches = $this->createBatches($emailsData, $this->getBatchSize($provider));

        foreach ($batches as $batchIndex => $batch) {
            Log::info('Processing email batch', [
                'batch' => $batchIndex + 1,
                'batch_size' => count($batch),
                'provider' => $provider,
            ]);

            foreach ($batch as $emailData) {
                $result = $this->sendEmail(array_merge($emailData, ['provider' => $provider]));
                $results[] = $result;

                // Add small delay between individual emails to respect rate limits
                if ($this->getBatchDelay($provider) > 0) {
                    usleep($this->getBatchDelay($provider) * 1000);
                }
            }

            // Add delay between batches
            if ($this->getBatchDelay($provider) > 0 && $batchIndex < count($batches) - 1) {
                sleep($this->getBatchDelay($provider));
            }
        }

        $successful = count(array_filter($results, fn($r) => $r['success']));
        $failed = count($results) - $successful;

        Log::info('Bulk email send completed', [
            'total' => count($results),
            'successful' => $successful,
            'failed' => $failed,
            'provider' => $provider,
        ]);

        return [
            'total' => count($results),
            'successful' => $successful,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Validate email data before sending
     */
    public function validateEmailData(array $emailData): array
    {
        $errors = [];

        if (empty($emailData['to'])) {
            $errors[] = 'Recipient email is required';
        } elseif (!filter_var($emailData['to'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid recipient email format';
        }

        if (empty($emailData['subject'])) {
            $errors[] = 'Email subject is required';
        }

        if (empty($emailData['content']) && empty($emailData['template_data'])) {
            $errors[] = 'Email content or template data is required';
        }

        $provider = $emailData['provider'] ?? 'internal';
        if (!isset($this->providers[$provider])) {
            $errors[] = "Unsupported email provider: {$provider}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Get provider statistics and health status
     */
    public function getProviderStats(string $provider): array
    {
        try {
            $providerInstance = $this->getProviderInstance($provider);
            $stats = $providerInstance->getStats();

            return array_merge($stats, [
                'rate_limit_remaining' => $this->getRemainingRateLimit($provider),
                'rate_limit_reset' => $this->getRateLimitResetTime($provider),
                'healthy' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get provider stats', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'error' => $e->getMessage(),
                'rate_limit_remaining' => 0,
                'rate_limit_reset' => null,
            ];
        }
    }

    /**
     * Test provider connectivity
     */
    public function testProviderConnection(string $provider): array
    {
        try {
            $providerInstance = $this->getProviderInstance($provider);
            $result = $providerInstance->testConnection();

            Log::info('Provider connection test', [
                'provider' => $provider,
                'success' => $result['success'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Provider connection test failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function loadProviderConfigurations(): void
    {
        $this->providerConfigs = [
            'mailgun' => [
                'api_key' => config('services.mailgun.secret'),
                'domain' => config('services.mailgun.domain'),
                'endpoint' => config('services.mailgun.endpoint', 'https://api.mailgun.net/v3'),
            ],
            'sendgrid' => [
                'api_key' => config('services.sendgrid.api_key'),
                'endpoint' => config('services.sendgrid.endpoint', 'https://api.sendgrid.com/v3'),
            ],
            'ses' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
                'region' => config('services.ses.region', 'us-east-1'),
            ],
            'internal' => [
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ],
        ];
    }

    protected function initializeRateLimits(): void
    {
        $this->rateLimits = [
            'mailgun' => [
                'max_per_minute' => 300,
                'max_per_hour' => 5000,
            ],
            'sendgrid' => [
                'max_per_minute' => 100,
                'max_per_hour' => 6000,
            ],
            'ses' => [
                'max_per_minute' => 14,
                'max_per_hour' => 5000,
            ],
            'internal' => [
                'max_per_minute' => 60,
                'max_per_hour' => 1000,
            ],
        ];
    }

    protected function checkRateLimit(string $provider): bool
    {
        $limits = $this->rateLimits[$provider] ?? $this->rateLimits['internal'];

        $minuteKey = "email:{$provider}:minute:" . now()->format('Y-m-d-H-i');
        $hourKey = "email:{$provider}:hour:" . now()->format('Y-m-d-H');

        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);

        return $minuteCount < $limits['max_per_minute'] && $hourCount < $limits['max_per_hour'];
    }

    protected function incrementRateLimit(string $provider): void
    {
        $minuteKey = "email:{$provider}:minute:" . now()->format('Y-m-d-H-i');
        $hourKey = "email:{$provider}:hour:" . now()->format('Y-m-d-H');

        Cache::increment($minuteKey, 1, 60); // Expire in 1 minute
        Cache::increment($hourKey, 1, 3600); // Expire in 1 hour
    }

    protected function getRemainingRateLimit(string $provider): int
    {
        $limits = $this->rateLimits[$provider] ?? $this->rateLimits['internal'];

        $minuteKey = "email:{$provider}:minute:" . now()->format('Y-m-d-H-i');
        $hourKey = "email:{$provider}:hour:" . now()->format('Y-m-d-H');

        $minuteCount = Cache::get($minuteKey, 0);
        $hourCount = Cache::get($hourKey, 0);

        $minuteRemaining = max(0, $limits['max_per_minute'] - $minuteCount);
        $hourRemaining = max(0, $limits['max_per_hour'] - $hourCount);

        return min($minuteRemaining, $hourRemaining);
    }

    protected function getRateLimitResetTime(string $provider): ?int
    {
        $minuteKey = "email:{$provider}:minute:" . now()->format('Y-m-d-H-i');
        return Cache::get("{$minuteKey}:ttl");
    }

    protected function getProviderInstance(string $provider): EmailProviderInterface
    {
        $providerClass = $this->providers[$provider] ?? $this->providers['internal'];
        $config = $this->providerConfigs[$provider] ?? [];

        return new $providerClass($config);
    }

    protected function createBatches(array $emailsData, int $batchSize): array
    {
        return array_chunk($emailsData, $batchSize);
    }

    protected function getBatchSize(string $provider): int
    {
        return match($provider) {
            'mailgun' => 1000,
            'sendgrid' => 1000,
            'ses' => 50,
            default => 100,
        };
    }

    protected function getBatchDelay(string $provider): int
    {
        return match($provider) {
            'ses' => 1, // 1 second delay for SES
            default => 0,
        };
    }
}

// Provider Interface
interface EmailProviderInterface
{
    public function sendEmail(array $emailData): array;
    public function getStats(): array;
    public function testConnection(): array;
}

// Provider Implementations
class InternalProvider implements EmailProviderInterface
{
    public function __construct(protected array $config) {}

    public function sendEmail(array $emailData): array
    {
        try {
            \Mail::raw($emailData['content'], function ($message) use ($emailData) {
                $message->to($emailData['to'])
                        ->subject($emailData['subject'])
                        ->from($this->config['from_email'], $this->config['from_name']);
            });

            return [
                'success' => true,
                'message_id' => uniqid('internal_'),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getStats(): array
    {
        return [
            'sent_today' => 0,
            'delivered_today' => 0,
            'bounced_today' => 0,
        ];
    }

    public function testConnection(): array
    {
        return ['success' => true];
    }
}

class MailgunProvider implements EmailProviderInterface
{
    public function __construct(protected array $config) {}

    public function sendEmail(array $emailData): array
    {
        // Implementation would use Mailgun API
        return [
            'success' => true,
            'message_id' => 'mailgun_' . uniqid(),
            'provider_message_id' => 'mg_' . uniqid(),
        ];
    }

    public function getStats(): array
    {
        // Implementation would fetch from Mailgun API
        return [
            'sent_today' => 0,
            'delivered_today' => 0,
            'bounced_today' => 0,
        ];
    }

    public function testConnection(): array
    {
        // Implementation would test Mailgun API connection
        return ['success' => true];
    }
}

class SendGridProvider implements EmailProviderInterface
{
    public function __construct(protected array $config) {}

    public function sendEmail(array $emailData): array
    {
        // Implementation would use SendGrid API
        return [
            'success' => true,
            'message_id' => 'sendgrid_' . uniqid(),
            'provider_message_id' => 'sg_' . uniqid(),
        ];
    }

    public function getStats(): array
    {
        // Implementation would fetch from SendGrid API
        return [
            'sent_today' => 0,
            'delivered_today' => 0,
            'bounced_today' => 0,
        ];
    }

    public function testConnection(): array
    {
        // Implementation would test SendGrid API connection
        return ['success' => true];
    }
}

class SESProvider implements EmailProviderInterface
{
    public function __construct(protected array $config) {}

    public function sendEmail(array $emailData): array
    {
        // Implementation would use AWS SES API
        return [
            'success' => true,
            'message_id' => 'ses_' . uniqid(),
            'provider_message_id' => 'ses_' . uniqid(),
        ];
    }

    public function getStats(): array
    {
        // Implementation would fetch from SES API
        return [
            'sent_today' => 0,
            'delivered_today' => 0,
            'bounced_today' => 0,
        ];
    }

    public function testConnection(): array
    {
        // Implementation would test SES API connection
        return ['success' => true];
    }
}