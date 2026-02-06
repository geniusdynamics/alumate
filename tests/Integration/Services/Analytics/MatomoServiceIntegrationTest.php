<?php

namespace Tests\Integration\Services\Analytics;

use App\Services\Analytics\ConsentService;
use App\Services\Analytics\MatomoService;
use App\Services\TenantContextService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Integration tests for MatomoService
 * 
 * These tests verify the complete functionality of the Matomo Analytics integration
 * including event forwarding, custom dimension mapping, goal synchronization,
 * segment management, and configuration validation.
 */
class MatomoServiceIntegrationTest extends TestCase
{
    private MatomoService $service;
    private ConsentService&MockObject $consentService;
    private MockHandler $mockHandler;
    private Client $httpClient;
    private $mockTenantContext = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = $this->createMock(ConsentService::class);
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
        $this->mockTenantContext = $this->createMock(TenantContextService::class);

        // Clear caches before each test
        Cache::flush();
    }

    /**
     * Create service instance for testing
     */
    private function createService(): MatomoService
    {
        $service = new MatomoService(
            $this->consentService,
            $this->mockTenantContext
        );

        // Use reflection to set the httpClient
        $reflection = new \ReflectionProperty(MatomoService::class, 'httpClient');
        $reflection->setAccessible(true);
        $reflection->setValue($service, $this->httpClient);

        return $service;
    }

    /**
     * Configure Matomo settings
     */
    private function configureMatomo(): void
    {
        Config::set('services.matomo.url', 'https://analytics.example.com');
        Config::set('services.matomo.site_id', '123');
        Config::set('services.matomo.token_auth', 'test_token_auth_key');
        Config::set('services.matomo.timeout', 10);
        Config::set('services.matomo.connect_timeout', 5);
        Config::set('services.matomo.custom_dimensions', [
            'tenant_id' => 1,
            'user_segment' => 2,
            'cohort_id' => 3,
            'graduation_year' => 4,
            'program_of_study' => 5,
            'engagement_level' => 6,
            'alumni_status' => 7,
        ]);
    }

    // ========================================
    // Event Forwarding Tests
    // ========================================

    /**
     * Test event forwarding with complete configuration
     */
    public function test_forward_event_with_full_configuration(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(200));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
            'action' => 'test_action',
            'value' => 10,
            'custom_params' => ['param1' => 'value1'],
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123');

        $this->assertTrue($result);
    }

    /**
     * Test event forwarding without consent
     */
    public function test_forward_event_without_consent(): void
    {
        // Mock no consent
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(false);

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    /**
     * Test event forwarding fails with missing credentials
     */
    public function test_forward_event_missing_credentials(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock missing credentials
        Config::set('services.matomo.url', null);
        Config::set('services.matomo.site_id', null);
        Config::set('services.matomo.token_auth', null);

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    /**
     * Test event forwarding fails on API error
     */
    public function test_forward_event_api_error(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock API failure
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    /**
     * Test event forwarding handles Guzzle exceptions
     */
    public function test_forward_event_guzzle_exception(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Connection Error',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        ));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    /**
     * Test backward compatibility - trackEvent method
     */
    public function test_track_event_backward_compatibility(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(200));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->trackEvent($eventData, 'tenant-123');

        $this->assertTrue($result);
    }

    // ========================================
    // Batch Event Forwarding Tests
    // ========================================

    /**
     * Test batch event forwarding with multiple events
     */
    public function test_batch_forward_events(): void
    {
        $this->configureMatomo();

        // Mock consent granted for each event
        $this->consentService->expects($this->exactly(3))->method('hasConsent')->willReturn(true);

        // Mock successful responses for all events
        $this->mockHandler->append(new Response(200));
        $this->mockHandler->append(new Response(200));
        $this->mockHandler->append(new Response(200));

        $events = [
            ['name' => 'event_1', 'category' => 'cat_1'],
            ['name' => 'event_2', 'category' => 'cat_2'],
            ['name' => 'event_3', 'category' => 'cat_3'],
        ];

        $service = $this->createService();
        $result = $service->batchForwardEvents($events, 'tenant-123');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test batch event forwarding with mixed results
     */
    public function test_batch_forward_events_mixed_results(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->exactly(3))->method('hasConsent')->willReturn(true);

        // Mock mixed responses: success, failure, success
        $this->mockHandler->append(new Response(200));
        $this->mockHandler->append(new Response(500));
        $this->mockHandler->append(new Response(200));

        $events = [
            ['name' => 'event_1', 'category' => 'cat_1'],
            ['name' => 'event_2', 'category' => 'cat_2'],
            ['name' => 'event_3', 'category' => 'cat_3'],
        ];

        $service = $this->createService();
        $result = $service->batchForwardEvents($events, 'tenant-123');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(1, $result['errors'][0]['index']);
    }

    /**
     * Test backward compatibility - trackBatchEvents method
     */
    public function test_track_batch_events_backward_compatibility(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->exactly(2))->method('hasConsent')->willReturn(true);

        // Mock successful responses
        $this->mockHandler->append(new Response(200));
        $this->mockHandler->append(new Response(200));

        $events = [
            ['name' => 'event_1', 'category' => 'cat_1'],
            ['name' => 'event_2', 'category' => 'cat_2'],
        ];

        $service = $this->createService();
        $result = $service->trackBatchEvents($events, 'tenant-123');

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['success']);
    }

    // ========================================
    // Custom Dimensions Mapping Tests
    // ========================================

    /**
     * Test custom dimension mapping
     */
    public function test_map_custom_dimensions(): void
    {
        $this->configureMatomo();

        $internalData = [
            'user_segment' => 'premium',
            'cohort_id' => 123,
            'graduation_year' => 2020,
            'program_of_study' => 'Computer Science',
            'engagement_level' => 'high',
            'alumni_status' => 'active',
        ];

        $service = $this->createService();
        $result = $service->mapCustomDimensions($internalData, 'tenant-456');

        // Check dimension mappings based on config
        $this->assertEquals('tenant-456', $result[1]); // tenant_id -> dimension 1
        $this->assertEquals('premium', $result[2]); // user_segment -> dimension 2
        $this->assertEquals('123', $result[3]); // cohort_id -> dimension 3
        $this->assertEquals('2020', $result[4]); // graduation_year -> dimension 4
        $this->assertEquals('Computer Science', $result[5]); // program_of_study -> dimension 5
        $this->assertEquals('high', $result[6]); // engagement_level -> dimension 6
        $this->assertEquals('active', $result[7]); // alumni_status -> dimension 7
    }

    /**
     * Test custom dimension mapping with partial data
     */
    public function test_map_custom_dimensions_partial_data(): void
    {
        Config::set('services.matomo.custom_dimensions', [
            'tenant_id' => 1,
            'user_segment' => 2,
        ]);

        $internalData = [
            'user_segment' => 'basic',
        ];

        $service = $this->createService();
        $result = $service->mapCustomDimensions($internalData, null);

        $this->assertArrayNotHasKey(1, $result);
        $this->assertEquals('basic', $result[2]);
    }

    /**
     * Test custom dimension mapping with tenant context
     */
    public function test_map_custom_dimensions_with_tenant_context(): void
    {
        $this->configureMatomo();

        $this->mockTenantContext->expects($this->once())
            ->method('getCurrentTenantId')
            ->willReturn('context-tenant-789');

        $internalData = [];

        $service = $this->createService();
        $result = $service->mapCustomDimensions($internalData, null);

        $this->assertEquals('context-tenant-789', $result[1]);
    }

    // ========================================
    // Goal Synchronization Tests
    // ========================================

    /**
     * Test goal synchronization with default goals
     */
    public function test_sync_goals_with_defaults(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock goal creation responses
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 1])));
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 2])));
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 3])));

        $service = $this->createService();
        $result = $service->syncGoals([]);

        $this->assertTrue($result);
    }

    /**
     * Test goal synchronization with custom goals
     */
    public function test_sync_goals_with_custom_goals(): void
    {
        $this->configureMatomo();

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock goal creation response
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 5])));

        $customGoals = [
            [
                'name' => 'Custom Goal',
                'event_category' => 'custom',
                'event_action' => 'complete',
            ],
        ];

        $service = $this->createService();
        $result = $service->syncGoals($customGoals);

        $this->assertTrue($result);
    }

    /**
     * Test goal synchronization fails without consent
     */
    public function test_sync_goals_without_consent(): void
    {
        // Mock no consent
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(false);

        $service = $this->createService();
        $result = $service->syncGoals([]);

        $this->assertFalse($result);
    }

    /**
     * Test goal creation
     */
    public function test_create_goal(): void
    {
        $this->configureMatomo();

        // Mock goal creation response
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 10])));

        $goalData = [
            'name' => 'Test Goal',
            'event_category' => 'test',
            'event_action' => 'complete',
            'value' => 100,
        ];

        $service = $this->createService();
        $result = $service->createGoal($goalData);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result['id']);
        $this->assertEquals('Test Goal', $result['name']);
        $this->assertEquals('test', $result['event_category']);
    }

    // ========================================
    // Segment Management Tests
    // ========================================

    /**
     * Test segment creation
     */
    public function test_create_segment(): void
    {
        $this->configureMatomo();

        // Mock segment creation response
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => 5])));

        $criteria = [
            'name' => 'Premium Alumni Segment',
            'user_segment' => 'premium',
            'min_events' => 10,
            'days_since_last_visit' => 30,
        ];

        $service = $this->createService();
        $result = $service->createSegment($criteria);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result['id']);
        $this->assertEquals('Premium Alumni Segment', $result['name']);
        $this->assertNotEmpty($result['definition']);
    }

    /**
     * Test get segments
     */
    public function test_get_segments(): void
    {
        $this->configureMatomo();

        $mockResponse = [
            [
                'idsegment' => 1,
                'name' => 'All Users',
                'definition' => 'pageviews>0',
                'enabled' => true,
                'created' => '2024-01-01',
                'updated' => '2024-01-15',
            ],
            [
                'idsegment' => 2,
                'name' => 'Premium Members',
                'definition' => 'customDimension2==premium',
                'enabled' => true,
                'created' => '2024-01-05',
                'updated' => '2024-01-20',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getSegments();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('All Users', $result[0]['name']);
        $this->assertEquals(2, $result[1]['id']);
        $this->assertEquals('Premium Members', $result[1]['name']);
    }

    /**
     * Test get segments with empty response
     */
    public function test_get_segments_empty(): void
    {
        $this->configureMatomo();

        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $service = $this->createService();
        $result = $service->getSegments();

        $this->assertEmpty($result);
    }

    /**
     * Test get segments with caching
     */
    public function test_get_segments_cached(): void
    {
        $this->configureMatomo();

        // Pre-populate cache
        $cachedSegments = [
            ['id' => 99, 'name' => 'Cached Segment', 'definition' => '', 'enabled' => true],
        ];
        Cache::put('matomo_segments_123', $cachedSegments, 60);

        $service = $this->createService();
        $result = $service->getSegments();

        $this->assertCount(1, $result);
        $this->assertEquals(99, $result[0]['id']);
        $this->assertEquals('Cached Segment', $result[0]['name']);
    }

    /**
     * Test export segments with filters
     */
    public function test_export_segments_with_filters(): void
    {
        $this->configureMatomo();

        $mockResponse = [
            [
                'idsegment' => 1,
                'name' => 'Segment 1',
                'definition' => '',
                'enabled' => true,
                'created' => '2024-01-01',
            ],
            [
                'idsegment' => 2,
                'name' => 'Segment 2',
                'definition' => '',
                'enabled' => true,
                'created' => '2024-06-01',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $filters = [
            'date_range' => [
                'start' => '2024-03-01',
                'end' => '2024-12-31',
            ],
        ];

        $service = $this->createService();
        $result = $service->exportSegments($filters);

        // Should only return segments created after March 2024
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['id']);
    }

    // ========================================
    // Configuration Validation Tests
    // ========================================

    /**
     * Test configuration validation with valid config
     */
    public function test_validate_configuration_valid(): void
    {
        $this->configureMatomo();

        // Mock API connectivity check success
        $this->mockHandler->append(new Response(200, [], json_encode(['value' => '5.0.0'])));

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['details']);
    }

    /**
     * Test configuration validation with missing URL
     */
    public function test_validate_configuration_missing_url(): void
    {
        Config::set('services.matomo.url', null);
        Config::set('services.matomo.site_id', '123');
        Config::set('services.matomo.token_auth', 'test_token');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Matomo URL is not configured', $result['errors']);
    }

    /**
     * Test configuration validation with missing site ID
     */
    public function test_validate_configuration_missing_site_id(): void
    {
        Config::set('services.matomo.url', 'https://analytics.example.com');
        Config::set('services.matomo.site_id', null);
        Config::set('services.matomo.token_auth', 'test_token');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Matomo Site ID is not configured', $result['errors']);
    }

    /**
     * Test configuration validation with missing token
     */
    public function test_validate_configuration_missing_token(): void
    {
        Config::set('services.matomo.url', 'https://analytics.example.com');
        Config::set('services.matomo.site_id', '123');
        Config::set('services.matomo.token_auth', null);

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Matomo Token Auth is not configured', $result['errors']);
    }

    /**
     * Test configuration validation with short token warning
     */
    public function test_validate_configuration_short_token_warning(): void
    {
        Config::set('services.matomo.url', 'https://analytics.example.com');
        Config::set('services.matomo.site_id', '123');
        Config::set('services.matomo.token_auth', 'short');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertContains('Matomo Token Auth seems too short', $result['warnings']);
    }

    /**
     * Test configuration validation with invalid URL format
     */
    public function test_validate_configuration_invalid_url_format(): void
    {
        Config::set('services.matomo.url', 'not-a-valid-url');
        Config::set('services.matomo.site_id', '123');
        Config::set('services.matomo.token_auth', 'test_token_long_enough');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Matomo URL format is invalid', $result['errors']);
    }

    /**
     * Test configuration validation with failed API connectivity
     */
    public function test_validate_configuration_api_failure(): void
    {
        $this->configureMatomo();

        // Mock API failure
        $this->mockHandler->append(new Response(500, [], 'Internal Server Error'));

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }

    /**
     * Test configuration validation caching
     */
    public function test_validate_configuration_cached(): void
    {
        // Pre-populate cache with valid config
        $cachedResult = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => ['cached' => true],
        ];
        Cache::put('matomo_config_validated_', $cachedResult, 60);

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertTrue($result['details']['cached']);
    }

    // ========================================
    // Report Retrieval Tests
    // ========================================

    /**
     * Test report retrieval
     */
    public function test_get_report(): void
    {
        $this->configureMatomo();

        $mockResponse = [
            'nb_visits' => 100,
            'nb_uniq_visitors' => 80,
            'nb_actions' => 500,
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getReport('VisitsSummary.get', ['period' => 'day', 'date' => 'today']);

        $this->assertNotNull($result);
        $this->assertEquals(100, $result['nb_visits']);
        $this->assertEquals(80, $result['nb_uniq_visitors']);
        $this->assertEquals(500, $result['nb_actions']);
    }

    /**
     * Test report retrieval with missing credentials
     */
    public function test_get_report_missing_credentials(): void
    {
        Config::set('services.matomo.url', null);
        Config::set('services.matomo.site_id', null);
        Config::set('services.matomo.token_auth', null);

        $service = $this->createService();
        $result = $service->getReport('VisitsSummary.get');

        $this->assertNull($result);
    }

    /**
     * Test real-time data retrieval
     */
    public function test_get_realtime_data(): void
    {
        $this->configureMatomo();

        $mockResponse = [
            [
                'idVisit' => '123',
                'visitorId' => 'abc',
                'country' => 'US',
                'city' => 'New York',
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getRealtimeData();

        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertEquals('123', $result[0]['idVisit']);
    }

    // ========================================
    // Tenant Isolation Tests
    // ========================================

    /**
     * Test tenant isolation with tenant context service
     */
    public function test_tenant_isolation_with_context_service(): void
    {
        $this->configureMatomo();

        $this->mockTenantContext->expects($this->once())
            ->method('getCurrentTenantId')
            ->willReturn('isolated-tenant-001');

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(200));

        $eventData = [
            'name' => 'tenant_isolated_event',
            'category' => 'test',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, null);

        $this->assertTrue($result);
    }

    /**
     * Test tenant isolation with header-based resolution
     */
    public function test_tenant_isolation_with_header(): void
    {
        $this->configureMatomo();

        // Don't mock tenant context - should use header
        $this->mockTenantContext->expects($this->never())
            ->method('getCurrentTenantId');

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(200));

        $eventData = [
            'name' => 'header_tenant_event',
            'category' => 'test',
        ];

        // Simulate header-based tenant ID
        request()->headers->set('X-Tenant', 'header-tenant-123');

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, null);

        $this->assertTrue($result);
    }

    // ========================================
    // Cache Management Tests
    // ========================================

    /**
     * Test cache clearing
     */
    public function test_clear_caches(): void
    {
        // Pre-populate caches
        Cache::put('matomo_segments_123', [['id' => 1]], 60);
        Cache::put('matomo_goals_123', [['id' => 1]], 60);
        Cache::put('matomo_config_validated_', ['valid' => true], 60);

        $service = $this->createService();
        $service->clearCaches();

        $this->assertNull(Cache::get('matomo_segments_123'));
        $this->assertNull(Cache::get('matomo_goals_123'));
        $this->assertNull(Cache::get('matomo_config_validated_'));
    }

    // ========================================
    // Data Sync Tests
    // ========================================

    /**
     * Test data sync dispatch
     */
    public function test_sync_data(): void
    {
        $this->configureMatomo();

        $syncData = [
            ['type' => 'event', 'data' => ['name' => 'test']],
            ['type' => 'pageview', 'data' => ['url' => '/test']],
        ];

        // Note: This tests that the job is dispatched, not the actual sync
        // The job dispatching itself can't be easily tested without triggering it
        
        $service = $this->createService();
        $result = $service->syncData($syncData);

        $this->assertTrue($result);
    }

    /**
     * Test data sync fails without credentials
     */
    public function test_sync_data_missing_credentials(): void
    {
        Config::set('services.matomo.url', null);
        Config::set('services.matomo.site_id', null);
        Config::set('services.matomo.token_auth', null);

        $syncData = [
            ['type' => 'event', 'data' => ['name' => 'test']],
        ];

        $service = $this->createService();
        $result = $service->syncData($syncData);

        $this->assertFalse($result);
    }
}
