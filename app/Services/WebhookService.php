<?php

namespace App\Services;

use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Available webhook events.
     */
    private const AVAILABLE_EVENTS = [
        'user.created' => 'User Created',
        'user.updated' => 'User Updated',
        'user.deleted' => 'User Deleted',
        'post.created' => 'Post Created',
        'post.updated' => 'Post Updated',
        'post.deleted' => 'Post Deleted',
        'post.liked' => 'Post Liked',
        'post.commented' => 'Post Commented',
        'post.shared' => 'Post Shared',
        'connection.created' => 'Connection Created',
        'connection.accepted' => 'Connection Accepted',
        'event.created' => 'Event Created',
        'event.updated' => 'Event Updated',
        'event.registered' => 'Event Registration',
        'event.cancelled' => 'Event Cancelled',
        'donation.completed' => 'Donation Completed',
        'donation.failed' => 'Donation Failed',
        'donation.refunded' => 'Donation Refunded',
        'mentorship.requested' => 'Mentorship Requested',
        'mentorship.accepted' => 'Mentorship Accepted',
        'mentorship.declined' => 'Mentorship Declined',
        'job.applied' => 'Job Application',
        'achievement.earned' => 'Achievement Earned',
        'notification.sent' => 'Notification Sent',
    ];

    /**
     * Create a new webhook.
     */
    public function createWebhook(User $user, array $data): Webhook
    {
        // Validate webhook URL
        $validation = $this->validateWebhookUrl($data['url']);

        if (! $validation['valid']) {
            throw new \InvalidArgumentException('Invalid webhook URL: '.$validation['error']);
        }

        $webhook = Webhook::create([
            'user_id' => $user->id,
            'url' => $data['url'],
            'events' => $data['events'],
            'secret' => $data['secret'] ?? Str::random(32),
            'status' => 'active',
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'headers' => $data['headers'] ?? [],
            'timeout' => $data['timeout'] ?? 30,
            'retry_attempts' => $data['retry_attempts'] ?? 3,
        ]);

        // Send test webhook to verify connectivity
        $this->testWebhook($webhook);

        Log::info('Webhook created', [
            'webhook_id' => $webhook->id,
            'user_id' => $user->id,
            'url' => $webhook->url,
            'events' => $webhook->events,
        ]);

        return $webhook;
    }

    /**
     * Update an existing webhook.
     */
    public function updateWebhook(Webhook $webhook, array $data): Webhook
    {
        // If URL is being changed, validate it
        if (isset($data['url']) && $data['url'] !== $webhook->url) {
            $validation = $this->validateWebhookUrl($data['url']);

            if (! $validation['valid']) {
                throw new \InvalidArgumentException('Invalid webhook URL: '.$validation['error']);
            }
        }

        $webhook->update(array_filter([
            'url' => $data['url'] ?? null,
            'events' => $data['events'] ?? null,
            'secret' => $data['secret'] ?? null,
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'headers' => $data['headers'] ?? null,
            'timeout' => $data['timeout'] ?? null,
            'retry_attempts' => $data['retry_attempts'] ?? null,
        ], fn ($value) => $value !== null));

        Log::info('Webhook updated', [
            'webhook_id' => $webhook->id,
            'changes' => $webhook->getChanges(),
        ]);

        return $webhook->fresh();
    }

    /**
     * Delete a webhook.
     */
    public function deleteWebhook(Webhook $webhook): void
    {
        $webhookId = $webhook->id;

        // Delete associated deliveries
        $webhook->deliveries()->delete();

        // Delete the webhook
        $webhook->delete();

        Log::info('Webhook deleted', [
            'webhook_id' => $webhookId,
        ]);
    }

    /**
     * Send a test webhook.
     */
    public function testWebhook(Webhook $webhook): WebhookDelivery
    {
        $payload = [
            'id' => 'test_'.Str::random(10),
            'event' => 'webhook.test',
            'timestamp' => now()->toISOString(),
            'data' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'test' => true,
            ],
        ];

        return $this->deliverWebhook($webhook, 'webhook.test', $payload);
    }

    /**
     * Deliver a webhook payload.
     */
    public function deliverWebhook(Webhook $webhook, string $eventType, array $payload): WebhookDelivery
    {
        // Check if webhook is active and handles this event
        if ($webhook->status !== 'active' || ! in_array($eventType, $webhook->events)) {
            return $this->createDeliveryRecord($webhook, $eventType, $payload, 'skipped', null, 'Webhook not active or event not subscribed');
        }

        // Create delivery record
        $delivery = $this->createDeliveryRecord($webhook, $eventType, $payload, 'pending');

        try {
            // Prepare headers
            $headers = array_merge([
                'Content-Type' => 'application/json',
                'User-Agent' => 'AlumniPlatform-Webhook/1.0',
                'X-Webhook-ID' => $webhook->id,
                'X-Event-Type' => $eventType,
                'X-Delivery-ID' => $delivery->id,
                'X-Timestamp' => now()->timestamp,
            ], $webhook->headers ?? []);

            // Add signature if secret is provided
            if ($webhook->secret) {
                $signature = $this->generateSignature($payload, $webhook->secret);
                $headers['X-Signature'] = $signature;
            }

            // Send HTTP request
            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, $payload);

            // Update delivery record with response
            $this->updateDeliveryRecord($delivery, $response);

            // Log successful delivery
            if ($response->successful()) {
                Log::info('Webhook delivered successfully', [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'event_type' => $eventType,
                    'response_code' => $response->status(),
                ]);
            } else {
                Log::warning('Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'event_type' => $eventType,
                    'response_code' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            // Update delivery record with error
            $delivery->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'delivered_at' => now(),
            ]);

            Log::error('Webhook delivery exception', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }

        // Schedule retry if delivery failed and retries are available
        if ($delivery->status === 'failed' && $delivery->retry_count < $webhook->retry_attempts) {
            $this->scheduleRetry($delivery);
        }

        return $delivery;
    }

    /**
     * Retry a failed webhook delivery.
     */
    public function retryDelivery(WebhookDelivery $delivery): WebhookDelivery
    {
        $webhook = $delivery->webhook;

        // Create new delivery record for retry
        $newDelivery = $this->createDeliveryRecord(
            $webhook,
            $delivery->event_type,
            $delivery->payload,
            'pending',
            $delivery->retry_count + 1
        );

        try {
            // Prepare headers
            $headers = array_merge([
                'Content-Type' => 'application/json',
                'User-Agent' => 'AlumniPlatform-Webhook/1.0',
                'X-Webhook-ID' => $webhook->id,
                'X-Event-Type' => $delivery->event_type,
                'X-Delivery-ID' => $newDelivery->id,
                'X-Retry-Count' => $newDelivery->retry_count,
                'X-Timestamp' => now()->timestamp,
            ], $webhook->headers ?? []);

            // Add signature if secret is provided
            if ($webhook->secret) {
                $signature = $this->generateSignature($delivery->payload, $webhook->secret);
                $headers['X-Signature'] = $signature;
            }

            // Send HTTP request
            $response = Http::timeout($webhook->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, $delivery->payload);

            // Update delivery record with response
            $this->updateDeliveryRecord($newDelivery, $response);

        } catch (\Exception $e) {
            $newDelivery->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'delivered_at' => now(),
            ]);
        }

        return $newDelivery;
    }

    /**
     * Validate a webhook URL.
     */
    public function validateWebhookUrl(string $url): array
    {
        // Basic URL validation
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'Invalid URL format'];
        }

        // Check if URL is reachable
        try {
            $response = Http::timeout(10)->head($url);

            return [
                'valid' => true,
                'reachable' => $response->status() < 500,
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'valid' => true,
                'reachable' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get webhook statistics.
     */
    public function getWebhookStatistics(Webhook $webhook, array $options = []): array
    {
        $period = $options['period'] ?? '30d';
        $startDate = $this->getPeriodStartDate($period);

        $deliveries = $webhook->deliveries()
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalDeliveries = $deliveries->count();
        $successfulDeliveries = $deliveries->where('status', 'delivered')->count();
        $failedDeliveries = $deliveries->where('status', 'failed')->count();
        $pendingDeliveries = $deliveries->where('status', 'pending')->count();

        $successRate = $totalDeliveries > 0 ? ($successfulDeliveries / $totalDeliveries) * 100 : 0;

        // Average response time for successful deliveries
        $avgResponseTime = $deliveries
            ->where('status', 'delivered')
            ->whereNotNull('response_time')
            ->avg('response_time');

        // Most common response codes
        $responseCodes = $deliveries
            ->whereNotNull('response_code')
            ->groupBy('response_code')
            ->map->count()
            ->sortDesc()
            ->take(5);

        // Event type breakdown
        $eventTypes = $deliveries
            ->groupBy('event_type')
            ->map->count()
            ->sortDesc();

        return [
            'period' => $period,
            'total_deliveries' => $totalDeliveries,
            'successful_deliveries' => $successfulDeliveries,
            'failed_deliveries' => $failedDeliveries,
            'pending_deliveries' => $pendingDeliveries,
            'success_rate' => round($successRate, 2),
            'average_response_time' => $avgResponseTime ? round($avgResponseTime, 2) : null,
            'response_codes' => $responseCodes,
            'event_types' => $eventTypes,
            'last_delivery' => $deliveries->sortByDesc('created_at')->first()?->created_at,
        ];
    }

    /**
     * Get available webhook events.
     */
    public function getAvailableEvents(): array
    {
        return collect(self::AVAILABLE_EVENTS)
            ->map(fn ($name, $key) => [
                'event' => $key,
                'name' => $name,
                'description' => $this->getEventDescription($key),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Pause webhook deliveries.
     */
    public function pauseWebhook(Webhook $webhook): void
    {
        $webhook->update(['status' => 'paused']);

        Log::info('Webhook paused', ['webhook_id' => $webhook->id]);
    }

    /**
     * Resume webhook deliveries.
     */
    public function resumeWebhook(Webhook $webhook): void
    {
        $webhook->update(['status' => 'active']);

        Log::info('Webhook resumed', ['webhook_id' => $webhook->id]);
    }

    /**
     * Generate HMAC signature for webhook payload.
     */
    private function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return 'sha256='.hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Create a webhook delivery record.
     */
    private function createDeliveryRecord(
        Webhook $webhook,
        string $eventType,
        array $payload,
        string $status = 'pending',
        int $retryCount = 0,
        ?string $errorMessage = null
    ): WebhookDelivery {
        return WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => $status,
            'retry_count' => $retryCount,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }

    /**
     * Update delivery record with HTTP response.
     */
    private function updateDeliveryRecord(WebhookDelivery $delivery, Response $response): void
    {
        $delivery->update([
            'status' => $response->successful() ? 'delivered' : 'failed',
            'response_code' => $response->status(),
            'response_body' => $response->body(),
            'response_time' => $response->transferStats?->getTransferTime() * 1000, // Convert to milliseconds
            'delivered_at' => now(),
        ]);
    }

    /**
     * Schedule a retry for a failed delivery.
     */
    private function scheduleRetry(WebhookDelivery $delivery): void
    {
        // Exponential backoff: 1min, 5min, 30min
        $delays = [60, 300, 1800];
        $delay = $delays[$delivery->retry_count] ?? 1800;

        // In a real implementation, you would dispatch a job with delay
        // dispatch(new RetryWebhookDelivery($delivery))->delay(now()->addSeconds($delay));

        Log::info('Webhook retry scheduled', [
            'delivery_id' => $delivery->id,
            'retry_count' => $delivery->retry_count,
            'delay_seconds' => $delay,
        ]);
    }

    /**
     * Get period start date.
     */
    private function getPeriodStartDate(string $period): Carbon
    {
        return match ($period) {
            '1d' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            '1y' => now()->subYear(),
            default => now()->subMonth(),
        };
    }

    /**
     * Get event description.
     */
    private function getEventDescription(string $event): string
    {
        return match ($event) {
            'user.created' => 'Triggered when a new user registers',
            'user.updated' => 'Triggered when user profile is updated',
            'user.deleted' => 'Triggered when user account is deleted',
            'post.created' => 'Triggered when a new post is created',
            'post.updated' => 'Triggered when a post is edited',
            'post.deleted' => 'Triggered when a post is deleted',
            'post.liked' => 'Triggered when a post receives a like',
            'post.commented' => 'Triggered when a post receives a comment',
            'post.shared' => 'Triggered when a post is shared',
            'connection.created' => 'Triggered when a connection request is sent',
            'connection.accepted' => 'Triggered when a connection request is accepted',
            'event.created' => 'Triggered when a new event is created',
            'event.updated' => 'Triggered when an event is updated',
            'event.registered' => 'Triggered when someone registers for an event',
            'event.cancelled' => 'Triggered when an event is cancelled',
            'donation.completed' => 'Triggered when a donation is successfully processed',
            'donation.failed' => 'Triggered when a donation fails',
            'donation.refunded' => 'Triggered when a donation is refunded',
            'mentorship.requested' => 'Triggered when mentorship is requested',
            'mentorship.accepted' => 'Triggered when mentorship request is accepted',
            'mentorship.declined' => 'Triggered when mentorship request is declined',
            'job.applied' => 'Triggered when someone applies for a job',
            'achievement.earned' => 'Triggered when a user earns an achievement',
            'notification.sent' => 'Triggered when a notification is sent',
            default => 'Event description not available',
        };
    }
}
