<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\SessionRecording;
use App\Models\AnalyticsEvent;
use App\Services\Analytics\SessionRecordingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Carbon\Carbon;

class SessionRecordingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SessionRecordingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SessionRecordingService::class);

        // Mock tenant context
        session(['tenant_id' => 'test-tenant']);
    }

    public function test_can_capture_session_event(): void
    {
        $sessionId = 'test-session-123';
        $eventData = [
            'type' => 'click',
            'target' => 'button#submit',
            'user_id' => 1,
            'timestamp' => now()->toISOString(),
        ];

        $result = $this->service->captureSessionEvent($sessionId, $eventData);

        $this->assertTrue($result);

        $recording = SessionRecording::bySessionId($sessionId)->first();
        $this->assertNotNull($recording);
        $this->assertEquals('test-tenant', $recording->tenant_id);
        $this->assertEquals(1, $recording->user_id);
        $this->assertCount(1, $recording->recording_data);
        $this->assertEquals(1, $recording->interactions_count);
    }

    public function test_can_capture_multiple_events_in_session(): void
    {
        $sessionId = 'test-session-multi';
        $events = [
            ['type' => 'page_view', 'url' => '/dashboard', 'user_id' => 1],
            ['type' => 'click', 'target' => 'button#submit', 'user_id' => 1],
            ['type' => 'scroll', 'position' => 50, 'user_id' => 1],
        ];

        foreach ($events as $event) {
            $this->service->captureSessionEvent($sessionId, $event);
        }

        $recording = SessionRecording::bySessionId($sessionId)->first();
        $this->assertNotNull($recording);
        $this->assertCount(3, $recording->recording_data);
        $this->assertEquals(3, $recording->interactions_count);
        $this->assertEquals(1, $recording->page_views);
    }

    public function test_can_retrieve_session_recording(): void
    {
        $sessionId = 'test-session-retrieve';
        $recording = SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => $sessionId,
            'user_id' => 1,
            'recording_data' => [
                ['type' => 'click', 'timestamp' => now()->toISOString()],
            ],
            'duration_seconds' => 120,
            'page_views' => 1,
            'interactions_count' => 1,
            'privacy_masked' => false,
        ]);

        $result = $this->service->getSessionRecording($sessionId);

        $this->assertNotNull($result);
        $this->assertEquals($sessionId, $result['session_id']);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals(120, $result['duration_seconds']);
        $this->assertArrayHasKey('insights', $result);
    }

    public function test_returns_null_for_nonexistent_session(): void
    {
        $result = $this->service->getSessionRecording('nonexistent-session');

        $this->assertNull($result);
    }

    public function test_can_mask_sensitive_data(): void
    {
        $recordingData = [
            [
                'type' => 'input',
                'target' => 'input[type="email"]',
                'value' => 'user@example.com',
                'ip_address' => '192.168.1.1',
                'user_id' => 123,
            ],
            [
                'type' => 'click',
                'target' => 'button#submit',
                'value' => 'normal value',
            ],
        ];

        $masked = $this->service->maskSensitiveData($recordingData);

        $this->assertEquals('u***@example.com', $masked[0]['value']);
        $this->assertEquals(hash('sha256', '192.168.1.1'), $masked[0]['ip_address']);
        $this->assertNotEquals(123, $masked[0]['user_id']); // User ID should be pseudonymized
        $this->assertEquals('normal value', $masked[1]['value']);
    }

    public function test_can_analyze_session_behavior(): void
    {
        $sessionId = 'test-session-analysis';
        $recording = SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => $sessionId,
            'user_id' => 1,
            'recording_data' => [
                ['type' => 'click', 'timestamp' => now()->subSeconds(5)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(4)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(3)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(2)->toISOString()],
                ['type' => 'error', 'timestamp' => now()->subSeconds(1)->toISOString()],
            ],
            'duration_seconds' => 10,
            'page_views' => 1,
            'interactions_count' => 5,
            'privacy_masked' => false,
        ]);

        $analysis = $this->service->analyzeSessionBehavior($sessionId);

        $this->assertNotNull($analysis);
        $this->assertArrayHasKey('analysis', $analysis);
        $this->assertArrayHasKey('session_quality_score', $analysis['analysis']);
        $this->assertArrayHasKey('user_friction_points', $analysis['analysis']);
        $this->assertArrayHasKey('technical_issues', $analysis['analysis']);
        $this->assertArrayHasKey('behavioral_patterns', $analysis['analysis']);
    }

    public function test_can_compress_recording_data(): void
    {
        $largeData = array_fill(0, 100, [
            'type' => 'mousemove',
            'x' => 100,
            'y' => 200,
            'timestamp' => now()->toISOString(),
        ]);

        $compressed = $this->service->compressRecordingData($largeData);

        // Should return compressed string for large data
        $this->assertIsString($compressed);
        $this->assertStringStartsWith('compressed:', $compressed);
    }

    public function test_returns_uncompressed_data_when_not_beneficial(): void
    {
        $smallData = [
            ['type' => 'click', 'timestamp' => now()->toISOString()],
        ];

        $result = $this->service->compressRecordingData($smallData);

        // Should return array for small data
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_can_check_recording_consent(): void
    {
        // Test with user ID
        $hasConsent = $this->service->hasRecordingConsent(1);
        $this->assertTrue($hasConsent);

        // Test without user ID (anonymous)
        $hasConsentAnonymous = $this->service->hasRecordingConsent(null);
        $this->assertFalse($hasConsentAnonymous);
    }

    public function test_can_get_sessions_in_date_range(): void
    {
        SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => 'session1',
            'user_id' => 1,
            'recording_data' => [],
            'created_at' => now()->subDays(5),
        ]);

        SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => 'session2',
            'user_id' => 1,
            'recording_data' => [],
            'created_at' => now()->subDays(2),
        ]);

        SessionRecording::create([
            'tenant_id' => 'other-tenant',
            'session_id' => 'session3',
            'user_id' => 1,
            'recording_data' => [],
            'created_at' => now()->subDays(2),
        ]);

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $sessions = $this->service->getSessionsInRange($startDate, $endDate);

        $this->assertCount(2, $sessions); // Only test-tenant sessions
        $this->assertEquals('session1', $sessions[0]->session_id);
        $this->assertEquals('session2', $sessions[1]->session_id);
    }

    public function test_can_anonymize_old_recordings(): void
    {
        $oldRecording = SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => 'old-session',
            'user_id' => 1,
            'recording_data' => [
                [
                    'type' => 'input',
                    'target' => 'input[type="email"]',
                    'value' => 'user@example.com',
                    'ip_address' => '192.168.1.1',
                ],
            ],
            'privacy_masked' => false,
            'created_at' => now()->subDays(400), // Older than 365 days
        ]);

        $count = $this->service->anonymizeOldRecordings(365);

        $this->assertEquals(1, $count);

        $oldRecording->refresh();
        $this->assertTrue($oldRecording->privacy_masked);
        $maskedData = $oldRecording->getDecompressedData();
        $this->assertEquals('u***@example.com', $maskedData[0]['value']);
    }

    public function test_handles_empty_recording_data(): void
    {
        $sessionId = 'empty-session';
        $recording = SessionRecording::create([
            'tenant_id' => 'test-tenant',
            'session_id' => $sessionId,
            'user_id' => 1,
            'recording_data' => [],
            'duration_seconds' => 0,
            'page_views' => 0,
            'interactions_count' => 0,
            'privacy_masked' => false,
        ]);

        $insights = $recording->getSessionInsights();

        $this->assertEquals(0, $insights['total_events']);
        $this->assertEquals(0, $insights['rage_clicks']);
        $this->assertEquals(0, $insights['confusion_patterns']);
    }

    public function test_detects_rage_clicks(): void
    {
        $recording = new SessionRecording([
            'recording_data' => [
                ['type' => 'click', 'timestamp' => now()->subSeconds(5)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(4)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(3)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(2)->toISOString()],
                ['type' => 'click', 'timestamp' => now()->subSeconds(1)->toISOString()],
            ],
        ]);

        $insights = $recording->getSessionInsights();

        $this->assertGreaterThan(0, $insights['rage_clicks']);
    }

    public function test_detects_confusion_patterns(): void
    {
        $baseTime = now();
        $recording = new SessionRecording([
            'recording_data' => [
                ['type' => 'click', 'timestamp' => $baseTime->toISOString()],
                ['type' => 'click', 'timestamp' => $baseTime->copy()->addSeconds(1)->toISOString()],
                ['type' => 'click', 'timestamp' => $baseTime->copy()->addSeconds(2)->toISOString()],
                ['type' => 'click', 'timestamp' => $baseTime->copy()->addSeconds(3)->toISOString()],
                ['type' => 'click', 'timestamp' => $baseTime->copy()->addSeconds(4)->toISOString()],
                ['type' => 'click', 'timestamp' => $baseTime->copy()->addSeconds(5)->toISOString()],
            ],
        ]);

        $insights = $recording->getSessionInsights();

        $this->assertGreaterThan(0, $insights['confusion_patterns']);
    }

    public function test_logs_analytics_event_on_capture(): void
    {
        $sessionId = 'test-session-logging';
        $eventData = [
            'type' => 'click',
            'target' => 'button#submit',
            'user_id' => 1,
        ];

        $this->service->captureSessionEvent($sessionId, $eventData);

        $analyticsEvent = AnalyticsEvent::where('session_id', $sessionId)->first();
        $this->assertNotNull($analyticsEvent);
        $this->assertEquals('session_recording', $analyticsEvent->event_type);
        $this->assertEquals('click', $analyticsEvent->event_name);
        $this->assertTrue($analyticsEvent->consent_given);
    }

    public function test_handles_malformed_event_data(): void
    {
        $sessionId = 'test-session-malformed';

        // Test with missing required fields
        $result = $this->service->captureSessionEvent($sessionId, []);
        $this->assertTrue($result); // Should not fail

        $recording = SessionRecording::bySessionId($sessionId)->first();
        $this->assertNotNull($recording);
        $this->assertCount(1, $recording->recording_data);
    }

    public function test_calculates_session_metrics_correctly(): void
    {
        $sessionId = 'test-session-metrics';
        $events = [
            ['type' => 'page_view', 'url' => '/dashboard', 'timestamp' => now()->subMinutes(5)->toISOString()],
            ['type' => 'click', 'target' => 'button', 'timestamp' => now()->subMinutes(4)->toISOString()],
            ['type' => 'page_view', 'url' => '/profile', 'timestamp' => now()->subMinutes(3)->toISOString()],
            ['type' => 'scroll', 'position' => 50, 'timestamp' => now()->subMinutes(2)->toISOString()],
            ['type' => 'click', 'target' => 'link', 'timestamp' => now()->subMinutes(1)->toISOString()],
        ];

        foreach ($events as $event) {
            $this->service->captureSessionEvent($sessionId, $event);
        }

        $recording = SessionRecording::bySessionId($sessionId)->first();
        $this->assertEquals(2, $recording->page_views); // Two page_view events
        $this->assertEquals(5, $recording->interactions_count); // All events count as interactions
        $this->assertGreaterThan(0, $recording->duration_seconds);
    }

    public function test_applies_delta_encoding(): void
    {
        $data = [
            ['type' => 'mousemove', 'x' => 100, 'y' => 200, 'timestamp' => '2023-01-01T10:00:00Z'],
            ['type' => 'mousemove', 'x' => 101, 'y' => 201, 'timestamp' => '2023-01-01T10:00:01Z'],
            ['type' => 'mousemove', 'x' => 102, 'y' => 202, 'timestamp' => '2023-01-01T10:00:02Z'],
        ];

        $service = new SessionRecordingService();
        $method = new \ReflectionMethod($service, 'applyDeltaEncoding');
        $method->setAccessible(true);

        $encoded = $method->invoke($service, $data);

        $this->assertCount(3, $encoded);
        $this->assertArrayHasKey('_delta', $encoded[1]);
        $this->assertArrayHasKey('_delta', $encoded[2]);
        $this->assertArrayHasKey('x', $encoded[1]);
        $this->assertArrayHasKey('y', $encoded[1]);
        $this->assertEquals(101, $encoded[1]['x']);
        $this->assertEquals(201, $encoded[1]['y']);
        $this->assertTrue($encoded[1]['_delta']);
    }

    public function test_handles_compressed_data_decompression(): void
    {
        $originalData = [
            ['type' => 'click', 'timestamp' => now()->toISOString()],
            ['type' => 'scroll', 'timestamp' => now()->toISOString()],
        ];

        $recording = new SessionRecording([
            'recording_data' => 'compressed:' . base64_encode(gzcompress(json_encode($originalData))),
        ]);

        $decompressed = $recording->getDecompressedData();

        $this->assertEquals($originalData, $decompressed);
    }

    public function test_session_recording_model_scopes(): void
    {
        SessionRecording::create([
            'tenant_id' => 'tenant1',
            'session_id' => 'session1',
            'user_id' => 1,
            'recording_data' => [],
        ]);

        SessionRecording::create([
            'tenant_id' => 'tenant1',
            'session_id' => 'session2',
            'user_id' => 2,
            'recording_data' => [],
        ]);

        SessionRecording::create([
            'tenant_id' => 'tenant2',
            'session_id' => 'session3',
            'user_id' => 1,
            'recording_data' => [],
        ]);

        $tenant1Recordings = SessionRecording::byTenant('tenant1')->get();
        $this->assertCount(2, $tenant1Recordings);

        $user1Recordings = SessionRecording::byUser(1)->get();
        $this->assertCount(2, $user1Recordings);

        $session1Recording = SessionRecording::bySessionId('session1')->first();
        $this->assertNotNull($session1Recording);
        $this->assertEquals('session1', $session1Recording->session_id);
    }
}