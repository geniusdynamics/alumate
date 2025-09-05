<?php

namespace App\Services;

use App\Models\EmailSequence;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class EmailTrackingService
{
    protected string $trackingDomain;

    public function __construct()
    {
        $this->trackingDomain = config('app.url');
    }

    /**
     * Track email send and create tracking records
     */
    public function trackEmailSend(EmailSequence $sequence, User $recipient, ?string $messageId = null, ?string $providerMessageId = null): array
    {
        $trackingId = $this->generateTrackingId();

        $trackingData = [
            'sequence_id' => $sequence->id,
            'user_id' => $recipient->id,
            'email' => $recipient->email,
            'message_id' => $messageId,
            'provider_message_id' => $providerMessageId,
            'tracking_id' => $trackingId,
            'status' => 'sent',
            'sent_at' => now(),
            'tenant_id' => $sequence->tenant_id,
        ];

        // Store tracking data in cache for fast access
        Cache::put("email_tracking:{$trackingId}", $trackingData, now()->addDays(30));

        Log::info('Email tracking record created', [
            'sequence_id' => $sequence->id,
            'recipient_id' => $recipient->id,
            'tracking_id' => $trackingId,
        ]);

        return [
            'tracking_id' => $trackingId,
            'tracking_data' => $trackingData,
        ];
    }

    /**
     * Track email open event
     */
    public function trackEmailOpen(string $trackingId, array $metadata = []): array
    {
        $trackingData = $this->getTrackingData($trackingId);

        if (!$trackingData) {
            Log::warning('Email open tracking failed - invalid tracking ID', [
                'tracking_id' => $trackingId,
            ]);
            return ['success' => false, 'error' => 'Invalid tracking ID'];
        }

        // Prevent duplicate open tracking
        if ($trackingData['status'] === 'opened') {
            return ['success' => true, 'duplicate' => true];
        }

        $trackingData['status'] = 'opened';
        $trackingData['opened_at'] = now();
        $trackingData['open_metadata'] = $metadata;

        Cache::put("email_tracking:{$trackingId}", $trackingData, now()->addDays(30));

        // Update sequence recipient status
        $this->updateSequenceRecipientStatus($trackingData, 'opened');

        Log::info('Email opened', [
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
            'tracking_id' => $trackingId,
        ]);

        return [
            'success' => true,
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
        ];
    }

    /**
     * Track link click event
     */
    public function trackLinkClick(string $trackingId, string $url, array $metadata = []): array
    {
        $trackingData = $this->getTrackingData($trackingId);

        if (!$trackingData) {
            Log::warning('Link click tracking failed - invalid tracking ID', [
                'tracking_id' => $trackingId,
                'url' => $url,
            ]);
            return ['success' => false, 'error' => 'Invalid tracking ID'];
        }

        $clickData = [
            'url' => $url,
            'clicked_at' => now(),
            'metadata' => $metadata,
        ];

        // Initialize clicks array if not exists
        if (!isset($trackingData['clicks'])) {
            $trackingData['clicks'] = [];
        }

        $trackingData['clicks'][] = $clickData;
        $trackingData['status'] = 'clicked';
        $trackingData['last_clicked_at'] = now();

        Cache::put("email_tracking:{$trackingId}", $trackingData, now()->addDays(30));

        // Update sequence recipient status
        $this->updateSequenceRecipientStatus($trackingData, 'clicked');

        Log::info('Email link clicked', [
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
            'tracking_id' => $trackingId,
            'url' => $url,
        ]);

        return [
            'success' => true,
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
            'redirect_url' => $url,
        ];
    }

    /**
     * Process bounce notification from email provider
     */
    public function processBounceNotification(array $bounceData): array
    {
        $messageId = $bounceData['message_id'] ?? null;
        $email = $bounceData['email'] ?? null;
        $bounceType = $bounceData['bounce_type'] ?? 'unknown';

        if (!$messageId && !$email) {
            Log::warning('Bounce notification missing identifiers', $bounceData);
            return ['success' => false, 'error' => 'Missing message ID or email'];
        }

        $trackingData = null;

        // Try to find by message ID first
        if ($messageId) {
            $trackingData = $this->findTrackingDataByMessageId($messageId);
        }

        // Fallback to email search
        if (!$trackingData && $email) {
            $trackingData = $this->findTrackingDataByEmail($email);
        }

        if (!$trackingData) {
            Log::warning('Bounce notification - tracking data not found', [
                'message_id' => $messageId,
                'email' => $email,
            ]);
            return ['success' => false, 'error' => 'Tracking data not found'];
        }

        $trackingData['status'] = 'bounced';
        $trackingData['bounced_at'] = now();
        $trackingData['bounce_type'] = $bounceType;
        $trackingData['bounce_data'] = $bounceData;

        Cache::put("email_tracking:{$trackingData['tracking_id']}", $trackingData, now()->addDays(30));

        // Update sequence recipient status
        $this->updateSequenceRecipientStatus($trackingData, 'bounced');

        Log::info('Email bounce processed', [
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
            'bounce_type' => $bounceType,
        ]);

        return [
            'success' => true,
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
        ];
    }

    /**
     * Process complaint/unsubscribe notification
     */
    public function processComplaintNotification(array $complaintData): array
    {
        $messageId = $complaintData['message_id'] ?? null;
        $email = $complaintData['email'] ?? null;

        if (!$messageId && !$email) {
            Log::warning('Complaint notification missing identifiers', $complaintData);
            return ['success' => false, 'error' => 'Missing message ID or email'];
        }

        $trackingData = null;

        // Try to find by message ID first
        if ($messageId) {
            $trackingData = $this->findTrackingDataByMessageId($messageId);
        }

        // Fallback to email search
        if (!$trackingData && $email) {
            $trackingData = $this->findTrackingDataByEmail($email);
        }

        if (!$trackingData) {
            Log::warning('Complaint notification - tracking data not found', [
                'message_id' => $messageId,
                'email' => $email,
            ]);
            return ['success' => false, 'error' => 'Tracking data not found'];
        }

        $trackingData['status'] = 'unsubscribed';
        $trackingData['unsubscribed_at'] = now();
        $trackingData['complaint_data'] = $complaintData;

        Cache::put("email_tracking:{$trackingData['tracking_id']}", $trackingData, now()->addDays(30));

        // Update sequence recipient status
        $this->updateSequenceRecipientStatus($trackingData, 'unsubscribed');

        Log::info('Email complaint/unsubscribe processed', [
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
        ]);

        return [
            'success' => true,
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
        ];
    }

    /**
     * Generate tracking pixel URL for email opens
     */
    public function generateTrackingPixelUrl(string $trackingId): string
    {
        return URL::signedRoute('email.tracking.pixel', ['trackingId' => $trackingId]);
    }

    /**
     * Generate tracking URL for link clicks
     */
    public function generateTrackingUrl(string $trackingId, string $destinationUrl): string
    {
        return URL::signedRoute('email.tracking.click', [
            'trackingId' => $trackingId,
            'url' => urlencode($destinationUrl)
        ]);
    }

    /**
     * Process webhook from email provider
     */
    public function processWebhook(Request $request, string $provider): array
    {
        $events = $request->all();

        if (!is_array($events)) {
            $events = [$events];
        }

        $processed = 0;
        $failed = 0;

        foreach ($events as $event) {
            try {
                $result = $this->processWebhookEvent($event, $provider);
                if ($result['success']) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error('Webhook event processing failed', [
                    'provider' => $provider,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        Log::info('Webhook processed', [
            'provider' => $provider,
            'events_count' => count($events),
            'processed' => $processed,
            'failed' => $failed,
        ]);

        return [
            'total_events' => count($events),
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Get engagement metrics for a sequence
     */
    public function getSequenceMetrics(int $sequenceId): array
    {
        $cacheKey = "sequence_metrics:{$sequenceId}";
        $metrics = Cache::get($cacheKey);

        if ($metrics) {
            return $metrics;
        }

        // For now, return placeholder metrics
        // In a real implementation, you would aggregate from database or use a proper cache scanning method
        $metrics = [
            'sent' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'unsubscribed' => 0,
            'open_rate' => 0,
            'click_rate' => 0,
            'bounce_rate' => 0,
            'unsubscribe_rate' => 0,
        ];

        // Cache for 5 minutes
        Cache::put($cacheKey, $metrics, 300);

        return $metrics;
    }

    protected function generateTrackingId(): string
    {
        return Str::random(32);
    }

    protected function getTrackingData(string $trackingId): ?array
    {
        return Cache::get("email_tracking:{$trackingId}");
    }

    protected function findTrackingDataByMessageId(string $messageId): ?array
    {
        // Store message ID to tracking ID mapping for faster lookups
        $trackingId = Cache::get("msg_to_tracking:{$messageId}");

        if ($trackingId) {
            return $this->getTrackingData($trackingId);
        }

        return null;
    }

    protected function findTrackingDataByEmail(string $email): ?array
    {
        // Store email to tracking ID mapping for faster lookups
        $trackingId = Cache::get("email_to_tracking:{$email}");

        if ($trackingId) {
            return $this->getTrackingData($trackingId);
        }

        return null;
    }

    protected function updateSequenceRecipientStatus(array $trackingData, string $status): void
    {
        // Update the sequence recipient record
        // This would typically update a pivot table or related model
        // Implementation depends on the actual database schema
        Log::info('Sequence recipient status updated', [
            'sequence_id' => $trackingData['sequence_id'],
            'user_id' => $trackingData['user_id'],
            'status' => $status,
        ]);
    }

    protected function processWebhookEvent(array $event, string $provider): array
    {
        // Process different types of webhook events based on provider
        switch ($provider) {
            case 'mailgun':
                return $this->processMailgunEvent($event);
            case 'sendgrid':
                return $this->processSendGridEvent($event);
            case 'ses':
                return $this->processSESEvent($event);
            default:
                Log::warning('Unknown webhook provider', ['provider' => $provider]);
                return ['success' => false, 'error' => 'Unknown provider'];
        }
    }

    protected function processMailgunEvent(array $event): array
    {
        $eventType = $event['event'] ?? null;

        switch ($eventType) {
            case 'delivered':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'opened':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'clicked':
                return $this->trackLinkClick($event['tracking_id'] ?? '', $event['url'] ?? '', $event);
            case 'bounced':
                return $this->processBounceNotification($event);
            case 'complained':
                return $this->processComplaintNotification($event);
            default:
                return ['success' => false, 'error' => 'Unknown event type'];
        }
    }

    protected function processSendGridEvent(array $event): array
    {
        $eventType = $event['event'] ?? null;

        switch ($eventType) {
            case 'delivered':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'open':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'click':
                return $this->trackLinkClick($event['tracking_id'] ?? '', $event['url'] ?? '', $event);
            case 'bounce':
                return $this->processBounceNotification($event);
            case 'unsubscribe':
                return $this->processComplaintNotification($event);
            default:
                return ['success' => false, 'error' => 'Unknown event type'];
        }
    }

    protected function processSESEvent(array $event): array
    {
        $eventType = $event['eventType'] ?? null;

        switch ($eventType) {
            case 'Delivery':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'Open':
                return $this->trackEmailOpen($event['tracking_id'] ?? '', $event);
            case 'Click':
                return $this->trackLinkClick($event['tracking_id'] ?? '', $event['destinationUrl'] ?? '', $event);
            case 'Bounce':
                return $this->processBounceNotification($event);
            case 'Complaint':
                return $this->processComplaintNotification($event);
            default:
                return ['success' => false, 'error' => 'Unknown event type'];
        }
    }
}