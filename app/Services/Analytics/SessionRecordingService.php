<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\SessionRecording;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SessionRecordingService
{
    private const COMPRESSION_PREFIX = 'compressed:';

    private const RAGE_CLICK_THRESHOLD = 3;

    private const CONFUSION_THRESHOLD = 5;

    /**
     * Capture a session event and store it in the recording
     */
    public function captureSessionEvent(string $sessionId, array $eventData): bool
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            // Get or create session recording
            $recording = SessionRecording::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'session_id' => $sessionId,
                ],
                [
                    'user_id' => $eventData['user_id'] ?? null,
                    'recording_data' => [],
                    'duration_seconds' => 0,
                    'page_views' => 0,
                    'interactions_count' => 0,
                    'privacy_masked' => false,
                ]
            );

            // Append event to recording data
            $currentData = $recording->recording_data ?? [];
            $currentData[] = array_merge($eventData, [
                'timestamp' => now()->toISOString(),
                'sequence_id' => count($currentData),
            ]);

            // Update metrics
            $metrics = $this->calculateMetrics($currentData);
            $recording->update([
                'recording_data' => $currentData,
                'duration_seconds' => $metrics['duration_seconds'],
                'page_views' => $metrics['page_views'],
                'interactions_count' => $metrics['interactions_count'],
            ]);

            // Log to unified analytics event system
            $this->logAnalyticsEvent($sessionId, $eventData, $tenantId);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to capture session event', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve full session recording with playback data
     */
    public function getSessionRecording(string $sessionId): ?array
    {
        try {
            $tenantId = $this->getCurrentTenantId();

            $recording = SessionRecording::byTenant($tenantId)
                ->bySessionId($sessionId)
                ->first();

            if (! $recording) {
                return null;
            }

            $data = $recording->getDecompressedData();
            $insights = $recording->getSessionInsights();

            return [
                'session_id' => $recording->session_id,
                'user_id' => $recording->user_id,
                'duration_seconds' => $recording->duration_seconds,
                'page_views' => $recording->page_views,
                'interactions_count' => $recording->interactions_count,
                'privacy_masked' => $recording->privacy_masked,
                'recording_data' => $data,
                'insights' => $insights,
                'created_at' => $recording->created_at,
                'updated_at' => $recording->updated_at,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve session recording', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Apply privacy masking to recording data
     */
    public function maskSensitiveData(array $recordingData): array
    {
        return array_map(function ($event) {
            $maskedEvent = $event;

            // Mask sensitive form inputs
            if (isset($event['target']) && isset($event['type'])) {
                if ($this->isSensitiveInput($event['target'])) {
                    $maskedEvent['value'] = $this->maskValue($event['value'] ?? '');
                }
            }

            // Hash IP addresses
            if (isset($event['ip_address'])) {
                $maskedEvent['ip_address'] = hash('sha256', $event['ip_address']);
            }

            // Pseudonymize user identifiers
            if (isset($event['user_id'])) {
                $maskedEvent['user_id'] = $this->pseudonymizeUserId($event['user_id']);
            }

            return $maskedEvent;
        }, $recordingData);
    }

    /**
     * Analyze session behavior for patterns
     */
    public function analyzeSessionBehavior(string $sessionId): array
    {
        $recording = $this->getSessionRecording($sessionId);

        if (! $recording) {
            return [];
        }

        $data = $recording['recording_data'];
        $insights = $recording['insights'];

        // Additional analysis
        $analysis = [
            'session_quality_score' => $this->calculateSessionQualityScore($data),
            'user_friction_points' => $this->detectFrictionPoints($data),
            'conversion_funnel_progress' => $this->analyzeConversionProgress($data),
            'technical_issues' => $this->detectTechnicalIssues($data),
            'behavioral_patterns' => $insights,
        ];

        return array_merge($recording, ['analysis' => $analysis]);
    }

    /**
     * Compress recording data for storage optimization
     */
    public function compressRecordingData(array $data): array|string
    {
        // Implement delta encoding for repetitive events
        $compressed = $this->applyDeltaEncoding($data);

        // Compress using gzcompress if beneficial
        $jsonData = json_encode($compressed);
        $compressedData = gzcompress($jsonData);

        if (strlen($compressedData) < strlen($jsonData)) {
            return self::COMPRESSION_PREFIX.base64_encode($compressedData);
        }

        return $compressed;
    }

    /**
     * Check if user has consented to recording
     */
    public function hasRecordingConsent(?int $userId): bool
    {
        if (! $userId) {
            return false; // Anonymous users require explicit consent
        }

        // Check user preferences (implement based on your user model)
        // For now, return true as placeholder
        return Cache::remember(
            "user_recording_consent_{$userId}",
            3600,
            fn () => true // Replace with actual consent check
        );
    }

    /**
     * Get sessions within date range
     */
    public function getSessionsInRange(Carbon $startDate, Carbon $endDate, array $filters = []): Collection
    {
        $query = SessionRecording::byTenant($this->getCurrentTenantId())
            ->byDateRange($startDate, $endDate);

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['privacy_masked'])) {
            if ($filters['privacy_masked']) {
                $query->privacyMasked();
            } else {
                $query->where('privacy_masked', false);
            }
        }

        return $query->get();
    }

    /**
     * Anonymize old recordings based on retention policy
     *
     * @return int Number of recordings anonymized
     */
    public function anonymizeOldRecordings(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $recordings = SessionRecording::where('created_at', '<', $cutoffDate)
            ->where('privacy_masked', false)
            ->get();

        $count = 0;
        foreach ($recordings as $recording) {
            $maskedData = $this->maskSensitiveData($recording->getDecompressedData());
            $recording->update([
                'recording_data' => $this->compressRecordingData($maskedData),
                'privacy_masked' => true,
            ]);
            $count++;
        }

        return $count;
    }

    // Private helper methods

    private function getCurrentTenantId(): string
    {
        // Implement tenant context retrieval
        // For now, return a placeholder
        return session('tenant_id', 'default');
    }

    private function calculateMetrics(array $data): array
    {
        $startTime = null;
        $endTime = null;
        $pageViews = 0;
        $interactions = 0;

        foreach ($data as $event) {
            if (isset($event['timestamp'])) {
                $timestamp = Carbon::parse($event['timestamp']);
                if ($startTime === null || $timestamp->lessThan($startTime)) {
                    $startTime = $timestamp;
                }
                if ($endTime === null || $timestamp->greaterThan($endTime)) {
                    $endTime = $timestamp;
                }
            }

            if (($event['type'] ?? '') === 'page_view') {
                $pageViews++;
            }

            $interactions++;
        }

        $duration = $startTime && $endTime ? $endTime->diffInSeconds($startTime) : 0;

        return [
            'duration_seconds' => $duration,
            'page_views' => $pageViews,
            'interactions_count' => $interactions,
        ];
    }

    private function logAnalyticsEvent(string $sessionId, array $eventData, string $tenantId): void
    {
        try {
            AnalyticsEvent::create([
                'tenant_id' => $tenantId,
                'event_type' => 'session_recording',
                'event_name' => $eventData['type'] ?? 'unknown',
                'user_id' => $eventData['user_id'] ?? null,
                'properties' => array_merge($eventData, ['session_id' => $sessionId]),
                'session_id' => $sessionId,
                'occurred_at' => now(),
                'is_compliant' => true,
                'consent_given' => $this->hasRecordingConsent($eventData['user_id'] ?? null),
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log analytics event for session recording', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isSensitiveInput(string $target): bool
    {
        $sensitiveSelectors = [
            'input[type="email"]',
            'input[type="password"]',
            'input[name*="password"]',
            'input[name*="email"]',
            'input[name*="ssn"]',
            'input[name*="credit"]',
            'textarea[name*="address"]',
        ];

        foreach ($sensitiveSelectors as $selector) {
            if (str_contains($target, $selector)) {
                return true;
            }
        }

        return false;
    }

    private function maskValue(string $value): string
    {
        if (empty($value)) {
            return $value;
        }

        // Mask email addresses
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $parts = explode('@', $value);

            return substr($parts[0], 0, 2).'***@'.$parts[1];
        }

        // Mask other sensitive data
        return str_repeat('*', min(strlen($value), 10));
    }

    private function pseudonymizeUserId(int|string $userId): string
    {
        return hash('sha256', (string) $userId.env('APP_KEY'));
    }

    private function calculateSessionQualityScore(array $data): float
    {
        $score = 100;

        // Deduct points for rage clicks
        $rageClicks = $this->countRageClicks($data);
        $score -= min($rageClicks * 5, 30);

        // Deduct points for long loading times (placeholder)
        // $score -= min($loadingTimeIssues * 2, 20);

        // Deduct points for confusion patterns
        $confusionPatterns = $this->countConfusionPatterns($data);
        $score -= min($confusionPatterns * 3, 25);

        return max(0, $score);
    }

    private function detectFrictionPoints(array $data): array
    {
        $frictionPoints = [];

        foreach ($data as $event) {
            if (($event['type'] ?? '') === 'error' || ($event['type'] ?? '') === 'rage_click') {
                $frictionPoints[] = [
                    'timestamp' => $event['timestamp'] ?? null,
                    'type' => $event['type'],
                    'description' => $event['description'] ?? 'Friction point detected',
                ];
            }
        }

        return $frictionPoints;
    }

    private function analyzeConversionProgress(array $data): array
    {
        // Placeholder for conversion funnel analysis
        return [
            'funnel_stage' => 'unknown',
            'progress_percentage' => 0,
            'drop_off_points' => [],
        ];
    }

    private function detectTechnicalIssues(array $data): array
    {
        $issues = [];

        foreach ($data as $event) {
            if (isset($event['type']) && $event['type'] === 'error') {
                $issues[] = [
                    'timestamp' => $event['timestamp'] ?? null,
                    'error_type' => $event['error_type'] ?? 'unknown',
                    'message' => $event['message'] ?? 'Technical issue detected',
                ];
            }
        }

        return $issues;
    }

    private function countRageClicks(array $data): int
    {
        $clickEvents = array_filter($data, fn ($event) => ($event['type'] ?? '') === 'click');
        $rageClicks = 0;

        foreach ($clickEvents as $index => $event) {
            $timestamp = strtotime($event['timestamp'] ?? '0');
            $nearbyClicks = array_filter($clickEvents, function ($otherEvent) use ($timestamp) {
                return abs(strtotime($otherEvent['timestamp'] ?? '0') - $timestamp) < 1000;
            });

            if (count($nearbyClicks) >= self::RAGE_CLICK_THRESHOLD) {
                $rageClicks++;
            }
        }

        return $rageClicks;
    }

    private function countConfusionPatterns(array $data): int
    {
        $interactionTimestamps = array_column($data, 'timestamp');
        $interactionTimestamps = array_map('strtotime', array_filter($interactionTimestamps));
        sort($interactionTimestamps);

        $confusionPatterns = 0;
        for ($i = 0; $i < count($interactionTimestamps) - self::CONFUSION_THRESHOLD; $i++) {
            $timeSpan = $interactionTimestamps[$i + self::CONFUSION_THRESHOLD - 1] - $interactionTimestamps[$i];
            if ($timeSpan < 5000) { // within 5 seconds
                $confusionPatterns++;
            }
        }

        return $confusionPatterns;
    }

    private function applyDeltaEncoding(array $data): array
    {
        if (empty($data)) {
            return $data;
        }

        $encoded = [$data[0]]; // First event as baseline
        $previous = $data[0];

        for ($i = 1; $i < count($data); $i++) {
            $current = $data[$i];
            $delta = [];

            foreach ($current as $key => $value) {
                if (! isset($previous[$key]) || $previous[$key] !== $value) {
                    $delta[$key] = $value;
                }
            }

            $delta['_delta'] = true;
            $encoded[] = $delta;
            $previous = $current;
        }

        return $encoded;
    }
}
