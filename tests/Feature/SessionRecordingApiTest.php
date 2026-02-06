<?php

use App\Models\SessionRecording;
use App\Models\User;
use App\Services\Analytics\SessionRecordingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->sessionId = (string) Str::uuid();
    $this->consentToken = hash('sha256', 'test_consent_' . now()->timestamp);

    // Mock consent token in cache
    Cache::put("consent_token_{$this->consentToken}", true, 3600);
});

describe('Session Recording API', function () {
    it('can track session events with valid consent', function () {
        $events = [
            [
                'type' => 'page_view',
                'timestamp' => now()->toISOString(),
                'url' => 'https://example.com/page1',
                'user_id' => $this->user->id,
            ],
            [
                'type' => 'click',
                'timestamp' => now()->addSeconds(5)->toISOString(),
                'element' => 'button.cta',
                'position' => ['x' => 100, 'y' => 200],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => $events,
                'consent_token' => $this->consentToken,
            ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'processed' => 2,
                'total' => 2,
            ]);

        // Verify session was created
        $this->assertDatabaseHas('session_recordings', [
            'session_id' => $this->sessionId,
            'user_id' => $this->user->id,
        ]);
    });

    it('validates required consent token', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                // Missing consent_token
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'CONSENT_REQUIRED',
            ]);
    });

    it('validates event data structure', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'invalid_type',
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_data.0.type']);
    });

    it('rejects oversized payloads', function () {
        $largeEventData = array_fill(0, 200, [
            'type' => 'page_view',
            'timestamp' => now()->toISOString(),
            'url' => str_repeat('a', 1000), // Make payload large
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => $largeEventData,
                'consent_token' => $this->consentToken,
            ]);

        $response->assertStatus(413)
            ->assertJson([
                'success' => false,
                'error' => 'Payload size exceeds maximum limit of 1MB',
            ]);
    });

    it('can retrieve session recording', function () {
        // First create a session
        $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                        'url' => 'https://example.com/test',
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/sessions/{$this->sessionId}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'session_id',
                    'user_id',
                    'duration_seconds',
                    'page_views',
                    'interactions_count',
                    'recording_data',
                    'insights',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'session_id' => $this->sessionId,
                    'user_id' => $this->user->id,
                ],
            ]);
    });

    it('returns 404 for non-existent session', function () {
        $nonExistentId = (string) Str::uuid();

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/sessions/{$nonExistentId}");

        $response->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => 'Session not found',
            ]);
    });

    it('can list sessions with filtering and pagination', function () {
        // Create multiple sessions
        $sessions = collect(range(1, 5))->map(function ($i) {
            $sessionId = (string) Str::uuid();
            $this->actingAs($this->user)
                ->postJson('/api/analytics/sessions/track', [
                    'session_id' => $sessionId,
                    'event_data' => [
                        [
                            'type' => 'page_view',
                            'timestamp' => now()->subDays($i)->toISOString(),
                        ],
                    ],
                    'consent_token' => $this->consentToken,
                ]);
            return $sessionId;
        });

        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sessions?per_page=3&page=1');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ])
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 3,
                ],
            ]);

        expect($response->json('data'))->toHaveCount(3);
        expect($response->json('pagination.total'))->toBe(5);
    });

    it('can filter sessions by date range', function () {
        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->user)
            ->getJson("/api/analytics/sessions?date_range[from]={$startDate}&date_range[to]={$endDate}");

        $response->assertSuccessful();
    });

    it('can delete session recording for opt-out', function () {
        // Create a session
        $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        // Verify it exists
        $this->assertDatabaseHas('session_recordings', [
            'session_id' => $this->sessionId,
        ]);

        // Delete it
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/analytics/sessions/{$this->sessionId}");

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Session recording has been deleted',
            ]);

        // Verify it's soft deleted
        $recording = SessionRecording::withTrashed()
            ->where('session_id', $this->sessionId)
            ->first();

        expect($recording)->not->toBeNull();
        expect($recording->trashed())->toBeTrue();
    });

    it('blocks requests without user consent', function () {
        // Mock user without consent
        $mockService = mock(SessionRecordingService::class);
        $mockService->shouldReceive('hasRecordingConsent')
            ->andReturn(false);

        $this->app->instance(SessionRecordingService::class, $mockService);

        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response->assertForbidden()
            ->assertJson([
                'success' => false,
                'code' => 'CONSENT_REQUIRED',
            ]);
    });

    it('handles malformed event data gracefully', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => 'not_an_array', // Invalid
                'consent_token' => $this->consentToken,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_data']);
    });

    it('validates session ID format', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => 'invalid-session-id',
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['session_id']);
    });

    it('rate limits session recording requests', function () {
        // Make multiple requests to trigger rate limit
        for ($i = 0; $i < 105; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/api/analytics/sessions/track', [
                    'session_id' => $this->sessionId . '_' . $i,
                    'event_data' => [
                        [
                            'type' => 'page_view',
                            'timestamp' => now()->toISOString(),
                        ],
                    ],
                    'consent_token' => $this->consentToken,
                ]);

            if ($i < 100) {
                $response->assertSuccessful();
            } else {
                $response->assertStatus(429)
                    ->assertJson([
                        'success' => false,
                        'code' => 'RATE_LIMIT_EXCEEDED',
                    ]);
            }
        }
    });

    it('masks sensitive data in events', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'input_change',
                        'timestamp' => now()->toISOString(),
                        'element' => 'input[name="email"]',
                        'value' => 'user@example.com',
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response->assertSuccessful();

        // Retrieve and check masking
        $sessionResponse = $this->actingAs($this->user)
            ->getJson("/api/analytics/sessions/{$this->sessionId}");

        $sessionResponse->assertSuccessful();
        $recordingData = $sessionResponse->json('data.recording_data.0');

        // Email should be masked
        expect($recordingData['value'])->not->toBe('user@example.com');
        expect($recordingData['value'])->toContain('***');
    });

    it('provides session analytics summary', function () {
        // Create some test sessions
        for ($i = 0; $i < 3; $i++) {
            $sessionId = (string) Str::uuid();
            $this->actingAs($this->user)
                ->postJson('/api/analytics/sessions/track', [
                    'session_id' => $sessionId,
                    'event_data' => array_fill(0, 5, [
                        'type' => 'page_view',
                        'timestamp' => now()->toISOString(),
                        'url' => 'https://example.com/page' . ($i + 1),
                    ]),
                    'consent_token' => $this->consentToken,
                ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sessions/analytics');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_sessions',
                    'total_duration',
                    'total_page_views',
                    'total_interactions',
                    'avg_session_duration',
                    'avg_page_views',
                    'avg_interactions',
                ],
                'date_range',
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_sessions' => 3,
                    'total_page_views' => 15, // 3 sessions * 5 events each
                ],
            ]);
    });

    it('handles concurrent session tracking', function () {
        // Simulate concurrent requests
        $promises = [];

        for ($i = 0; $i < 10; $i++) {
            $sessionId = (string) Str::uuid();
            $promises[] = $this->actingAs($this->user)
                ->postJson('/api/analytics/sessions/track', [
                    'session_id' => $sessionId,
                    'event_data' => [
                        [
                            'type' => 'page_view',
                            'timestamp' => now()->toISOString(),
                        ],
                    ],
                    'consent_token' => $this->consentToken,
                ]);
        }

        // All requests should succeed
        foreach ($promises as $promise) {
            $promise->assertSuccessful();
        }

        // Verify all sessions were created
        expect(SessionRecording::count())->toBe(10);
    });

    it('validates pagination parameters', function () {
        $response = $this->actingAs($this->user)
            ->getJson('/api/analytics/sessions?page=0&per_page=1001');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['page', 'per_page']);
    });

    it('handles empty event arrays', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [], // Empty array
                'consent_token' => $this->consentToken,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_data']);
    });

    it('validates timestamp freshness', function () {
        $oldTimestamp = now()->subHours(2)->toISOString();

        $response = $this->actingAs($this->user)
            ->postJson('/api/analytics/sessions/track', [
                'session_id' => $this->sessionId,
                'event_data' => [
                    [
                        'type' => 'page_view',
                        'timestamp' => $oldTimestamp, // Too old
                    ],
                ],
                'consent_token' => $this->consentToken,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['event_data.0.timestamp']);
    });
});