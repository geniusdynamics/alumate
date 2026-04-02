<?php

// ABOUTME: This service handles alerting functionality for homepage monitoring and notifications
// ABOUTME: Provides alert management with rate limiting and multi-channel delivery (Slack, email, PagerDuty, etc.)

namespace App\Services\Homepage;

use App\Mail\Homepage\AlertNotification;
use App\Services\BaseService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertingService extends BaseService
{
    private array $config;

    public function __construct()
    {
        $this->config = config('services.monitoring', []);
    }

    /**
     * Send alert to all configured channels.
     */
    public function sendAlert(array $alertData): void
    {
        // Prevent alert spam by rate limiting
        if ($this->isAlertRateLimited($alertData)) {
            return;
        }

        // Log the alert
        $this->logAlert($alertData);

        // Send to configured channels
        $this->sendToSlack($alertData);
        $this->sendToEmail($alertData);
        $this->sendToPagerDuty($alertData);
        $this->sendToSentry($alertData);
        $this->sendToDataDog($alertData);

        // Update rate limiting cache
        $this->updateAlertRateLimit($alertData);
    }

    /**
     * Send uptime alert.
     */
    public function sendUptimeAlert(string $endpoint, string $url, int $statusCode, ?string $error = null): void
    {
        $alertData = [
            'type' => 'uptime',
            'severity' => 'critical',
            'title' => "Homepage Endpoint Down: {$endpoint}",
            'message' => "Endpoint {$endpoint} is not responding",
            'details' => [
                'endpoint' => $endpoint,
                'url' => $url,
                'status_code' => $statusCode,
                'error' => $error,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['homepage', 'uptime', $endpoint],
        ];

        $this->sendAlert($alertData);
    }

    /**
     * Send performance alert.
     */
    public function sendPerformanceAlert(string $metricType, string $metricName, float $value, string $severity): void
    {
        $alertData = [
            'type' => 'performance',
            'severity' => $severity,
            'title' => "Performance Alert: {$metricName}",
            'message' => "Performance metric {$metricName} exceeded threshold: {$value}ms",
            'details' => [
                'metric_type' => $metricType,
                'metric_name' => $metricName,
                'value' => $value,
                'unit' => 'ms',
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['homepage', 'performance', $metricType],
        ];

        $this->sendAlert($alertData);
    }

    /**
     * Send error alert.
     */
    public function sendErrorAlert(string $errorType, string $severity, int $count, ?string $message = null): void
    {
        $alertData = [
            'type' => 'error',
            'severity' => $severity,
            'title' => "Error Alert: {$errorType}",
            'message' => $message ?? "Error count threshold exceeded for {$errorType}: {$count} errors",
            'details' => [
                'error_type' => $errorType,
                'count' => $count,
                'severity' => $severity,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['homepage', 'error', $errorType],
        ];

        $this->sendAlert($alertData);
    }

    /**
     * Send conversion alert.
     */
    public function sendConversionAlert(string $metric, float $currentValue, float $threshold, string $comparison): void
    {
        $alertData = [
            'type' => 'conversion',
            'severity' => 'warning',
            'title' => "Conversion Alert: {$metric}",
            'message' => "Conversion metric {$metric} is {$comparison} threshold: {$currentValue}% vs {$threshold}%",
            'details' => [
                'metric' => $metric,
                'current_value' => $currentValue,
                'threshold' => $threshold,
                'comparison' => $comparison,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['homepage', 'conversion', $metric],
        ];

        $this->sendAlert($alertData);
    }

    /**
     * Send security alert.
     */
    public function sendSecurityAlert(string $threatType, array $details): void
    {
        $alertData = [
            'type' => 'security',
            'severity' => 'critical',
            'title' => "Security Alert: {$threatType}",
            'message' => "Security threat detected on homepage: {$threatType}",
            'details' => array_merge($details, [
                'threat_type' => $threatType,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ]),
            'tags' => ['homepage', 'security', $threatType],
        ];

        $this->sendAlert($alertData);
    }

    /**
     * Check if alert is rate limited.
     */
    private function isAlertRateLimited(array $alertData): bool
    {
        $cacheKey = $this->getAlertCacheKey($alertData);
        $lastSent = Cache::get($cacheKey);

        if (! $lastSent) {
            return false;
        }

        // Rate limit based on severity
        $rateLimits = [
            'critical' => 300,  // 5 minutes
            'error' => 900,     // 15 minutes
            'warning' => 1800,  // 30 minutes
            'info' => 3600,     // 1 hour
        ];

        $rateLimit = $rateLimits[$alertData['severity']] ?? 3600;

        return (time() - $lastSent) < $rateLimit;
    }

    /**
     * Update alert rate limit cache.
     */
    private function updateAlertRateLimit(array $alertData): void
    {
        $cacheKey = $this->getAlertCacheKey($alertData);
        Cache::put($cacheKey, time(), 7200); // 2 hours
    }

    /**
     * Get cache key for alert rate limiting.
     */
    private function getAlertCacheKey(array $alertData): string
    {
        return 'alert_rate_limit_'.md5(
            $alertData['type'].'_'.
            $alertData['severity'].'_'.
            ($alertData['details']['endpoint'] ?? '').'_'.
            ($alertData['details']['metric_name'] ?? '').'_'.
            ($alertData['details']['error_type'] ?? '')
        );
    }

    /**
     * Log the alert.
     */
    private function logAlert(array $alertData): void
    {
        Log::channel('homepage-alerts')->alert($alertData['title'], $alertData);
    }

    /**
     * Send alert to Slack.
     */
    private function sendToSlack(array $alertData): void
    {
        if (! $this->config['slack_webhook']) {
            return;
        }

        try {
            $message = $this->formatSlackMessage($alertData);

            Http::timeout(10)->post($this->config['slack_webhook'], $message);

        } catch (\Exception $e) {
            Log::error('Failed to send Slack alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Send alert via email.
     */
    private function sendToEmail(array $alertData): void
    {
        if (! $this->config['alert_email']) {
            return;
        }

        try {
            Mail::to($this->config['alert_email'])
                ->send(new AlertNotification($alertData));

        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Send alert to PagerDuty.
     */
    private function sendToPagerDuty(array $alertData): void
    {
        if (! $this->config['pagerduty_key'] || $alertData['severity'] !== 'critical') {
            return;
        }

        try {
            $payload = [
                'routing_key' => $this->config['pagerduty_key'],
                'event_action' => 'trigger',
                'dedup_key' => $this->getAlertCacheKey($alertData),
                'payload' => [
                    'summary' => $alertData['title'],
                    'source' => 'homepage-monitoring',
                    'severity' => $alertData['severity'],
                    'component' => 'homepage',
                    'group' => $alertData['type'],
                    'class' => $alertData['type'],
                    'custom_details' => $alertData['details'],
                ],
            ];

            Http::timeout(10)->post('https://events.pagerduty.com/v2/enqueue', $payload);

        } catch (\Exception $e) {
            Log::error('Failed to send PagerDuty alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Send alert to Sentry.
     */
    private function sendToSentry(array $alertData): void
    {
        if (! app()->bound('sentry')) {
            return;
        }

        try {
            $sentry = app('sentry');

            $sentry->withScope(function ($scope) use ($alertData) {
                $scope->setTag('alert_type', $alertData['type']);
                $scope->setTag('severity', $alertData['severity']);
                $scope->setLevel($this->mapSeverityToSentryLevel($alertData['severity']));
                $scope->setContext('alert_details', $alertData['details']);

                foreach ($alertData['tags'] as $tag) {
                    $scope->setTag('component', $tag);
                }

                $sentry->captureMessage($alertData['title']);
            });

        } catch (\Exception $e) {
            Log::error('Failed to send Sentry alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Send alert to DataDog.
     */
    private function sendToDataDog(array $alertData): void
    {
        if (! $this->config['datadog_api_key']) {
            return;
        }

        try {
            $event = [
                'title' => $alertData['title'],
                'text' => $alertData['message'],
                'alert_type' => $this->mapSeverityToDataDogAlertType($alertData['severity']),
                'source_type_name' => 'homepage',
                'tags' => $alertData['tags'],
                'aggregation_key' => $this->getAlertCacheKey($alertData),
            ];

            Http::withHeaders([
                'DD-API-KEY' => $this->config['datadog_api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://api.datadoghq.com/api/v1/events', $event);

        } catch (\Exception $e) {
            Log::error('Failed to send DataDog alert', [
                'error' => $e->getMessage(),
                'alert_data' => $alertData,
            ]);
        }
    }

    /**
     * Format Slack message.
     */
    private function formatSlackMessage(array $alertData): array
    {
        $color = match ($alertData['severity']) {
            'critical' => 'danger',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'good',
            default => 'good',
        };

        $emoji = match ($alertData['severity']) {
            'critical' => '🚨',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📊',
        };

        $fields = [];
        foreach ($alertData['details'] as $key => $value) {
            if (! in_array($key, ['timestamp'])) {
                $fields[] = [
                    'title' => ucfirst(str_replace('_', ' ', $key)),
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'short' => true,
                ];
            }
        }

        return [
            'text' => "{$emoji} {$alertData['title']}",
            'attachments' => [
                [
                    'color' => $color,
                    'title' => $alertData['title'],
                    'text' => $alertData['message'],
                    'fields' => $fields,
                    'footer' => 'Homepage Monitoring',
                    'ts' => time(),
                ],
            ],
        ];
    }

    /**
     * Map severity to Sentry level.
     */
    private function mapSeverityToSentryLevel(string $severity): string
    {
        return match ($severity) {
            'critical' => 'fatal',
            'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'info',
        };
    }

    /**
     * Map severity to DataDog alert type.
     */
    private function mapSeverityToDataDogAlertType(string $severity): string
    {
        return match ($severity) {
            'critical', 'error' => 'error',
            'warning' => 'warning',
            'info' => 'info',
            default => 'info',
        };
    }

    /**
     * Test alert system.
     */
    public function testAlert(string $type = 'test', string $severity = 'info'): void
    {
        $alertData = [
            'type' => $type,
            'severity' => $severity,
            'title' => 'Test Alert from Homepage Monitoring',
            'message' => 'This is a test alert to verify the alerting system is working correctly.',
            'details' => [
                'test' => true,
                'triggered_by' => auth()->user()?->name ?? 'System',
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString(),
            ],
            'tags' => ['homepage', 'test', $type],
        ];

        $this->sendAlert($alertData);
    }
}
