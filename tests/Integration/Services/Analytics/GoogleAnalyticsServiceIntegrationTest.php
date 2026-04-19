<?php

namespace Tests\Integration\Services\Analytics;

use App\Services\Analytics\ConsentService;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\TenantContextService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Integration tests for GoogleAnalyticsService
 * 
 * These tests verify the complete functionality of the Google Analytics integration
 * including event forwarding, custom dimension mapping, goal synchronization,
 * audience segment management, and configuration validation.
 */
class GoogleAnalyticsServiceIntegrationTest extends TestCase
{
    private GoogleAnalyticsService $service;
    private ConsentService&MockObject $consentService;
    private MockHandler $mockHandler;
    private Client $httpClient;
    private ?TenantContextService $mockTenantContext = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = $this->createMock(ConsentService::class);
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);
    }

    /**
     * Create service instance for testing
     */
    private function createService(): GoogleAnalyticsService
    {
        $service = new GoogleAnalyticsService(
            $this->consentService,
            $this->mockTenantContext
        );

        // Use reflection to set the httpClient
        $reflection = new \ReflectionProperty(GoogleAnalyticsService::class, 'httpClient');
        $reflection->setAccessible(true);
        $reflection->setValue($service, $this->httpClient);

        return $service;
    }

    /**
     * Test event forwarding with complete configuration
     */
    public function test_forward_event_with_full_configuration(): void
    {
        // Configure Google Analytics settings
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');
        Config::set('services.google.analytics.property_id', '123456789');
        
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(204));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
            'label' => 'test_label',
            'value' => 10,
            'custom_params' => ['param1' => 'value1'],
            'user_properties' => ['user_type' => 'alumni'],
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertTrue($result);
    }

    /**
     * Test event forwarding is skipped without consent
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
        $result = $service->forwardEvent($eventData, 'tenant-123', 'premium');

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
        Config::set('services.google.analytics.measurement_id', null);
        Config::set('services.google.analytics.api_secret', null);

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    /**
     * Test event forwarding fails on API error
     */
    public function test_forward_event_api_error(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock API failure
        $this->mockHandler->append(new Response(400, [], 'Bad Request'));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    /**
     * Test event forwarding handles Guzzle exceptions
     */
    public function test_forward_event_guzzle_exception(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Connection Error',
            new \GuzzleHttp\Psr7\Request('POST', 'test')
        ));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $service = $this->createService();
        $result = $service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    /**
     * Test batch event forwarding with multiple events
     */
    public function test_batch_forward_events(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        // Mock consent granted
        $this->consentService->expects($this->exactly(3))->method('hasConsent')->willReturn(true);

        // Mock successful responses for all events
        $this->mockHandler->append(new Response(204));
        $this->mockHandler->append(new Response(204));
        $this->mockHandler->append(new Response(204));

        $events = [
            ['name' => 'event_1', 'category' => 'cat_1'],
            ['name' => 'event_2', 'category' => 'cat_2'],
            ['name' => 'event_3', 'category' => 'cat_3'],
        ];

        $service = $this->createService();
        $result = $service->batchForwardEvents($events, 'tenant-123', 'premium');

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
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        // Mock consent granted
        $this->consentService->expects($this->exactly(3))->method('hasConsent')->willReturn(true);

        // Mock mixed responses: success, failure, success
        $this->mockHandler->append(new Response(204));
        $this->mockHandler->append(new Response(400));
        $this->mockHandler->append(new Response(204));

        $events = [
            ['name' => 'event_1', 'category' => 'cat_1'],
            ['name' => 'event_2', 'category' => 'cat_2'],
            ['name' => 'event_3', 'category' => 'cat_3'],
        ];

        $service = $this->createService();
        $result = $service->batchForwardEvents($events, 'tenant-123', 'premium');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
        $this->assertEquals(1, $result['errors'][0]['index']);
    }

    /**
     * Test custom dimension mapping
     */
    public function test_map_custom_dimensions(): void
    {
        Config::set('services.google.analytics.custom_dimensions', [
            'tenant_id' => 'custom_dim_tenant',
            'user_segment' => 'custom_dim_segment',
            'cohort_id' => 'custom_dim_cohort',
            'graduation_year' => 'custom_dim_grad_year',
            'program_of_study' => 'custom_dim_program',
            'engagement_level' => 'custom_dim_engagement',
            'alumni_status' => 'custom_dim_status',
            'connection_count' => 'custom_dim_connections',
        ]);

        $internalData = [
            'user_segment' => 'premium',
            'cohort_id' => 123,
            'graduation_year' => 2020,
            'program_of_study' => 'Computer Science',
            'engagement_level' => 'high',
            'alumni_status' => 'active',
            'connection_count' => 50,
        ];

        $service = $this->createService();
        $result = $service->mapCustomDimensions($internalData, 'tenant-456');

        $this->assertEquals('tenant-456', $result['custom_dim_tenant']);
        $this->assertEquals('premium', $result['custom_dim_segment']);
        $this->assertEquals('123', $result['custom_dim_cohort']);
        $this->assertEquals('2020', $result['custom_dim_grad_year']);
        $this->assertEquals('Computer Science', $result['custom_dim_program']);
        $this->assertEquals('high', $result['custom_dim_engagement']);
        $this->assertEquals('active', $result['custom_dim_status']);
        $this->assertEquals(50, $result['custom_dim_connections']);
    }

    /**
     * Test custom dimension mapping with missing data
     */
    public function test_map_custom_dimensions_partial_data(): void
    {
        Config::set('services.google.analytics.custom_dimensions', [
            'tenant_id' => 'custom_dim_tenant',
            'user_segment' => 'custom_dim_segment',
        ]);

        $internalData = [
            'user_segment' => 'basic',
        ];

        $service = $this->createService();
        $result = $service->mapCustomDimensions($internalData, null);

        $this->assertArrayNotHasKey('custom_dim_tenant', $result);
        $this->assertEquals('basic', $result['custom_dim_segment']);
    }

    /**
     * Test goal synchronization
     */
    public function test_sync_goals(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        $funnelData = [
            [
                'name' => 'test_goal',
                'conversion_events' => ['event1', 'event2'],
            ],
        ];

        $service = $this->createService();
        $result = $service->syncGoals($funnelData);

        $this->assertTrue($result);
    }

    /**
     * Test goal creation
     */
    public function test_create_goal(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $funnelData = [
            'name' => 'New User Registration',
            'event_name' => 'registration_complete',
            'value' => 10,
        ];

        $service = $this->createService();
        $result = $service->createGoal($funnelData);

        $this->assertNotNull($result);
        $this->assertEquals('New User Registration', $result['displayName']);
        $this->assertEquals('registration_complete', $result['eventConditions'][0]['eventName']);
        $this->assertEquals(10, $result['value']);
    }

    /**
     * Test audience segment creation
     */
    public function test_create_audience_segment(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $criteria = [
            'name' => 'Premium Alumni',
            'description' => 'Alumni with premium subscription',
            'audience_type' => 'USER_BASED',
            'criteria' => [
                'user_segment' => 'premium',
                'min_engagement' => 100,
                'days_since_last_activity' => 30,
                'graduation_year' => 2020,
            ],
        ];

        $service = $this->createService();
        $result = $service->createAudienceSegment($criteria);

        $this->assertNotNull($result);
        $this->assertEquals('Premium Alumni', $result['displayName']);
        $this->assertEquals('Alumni with premium subscription', $result['description']);
        $this->assertEquals('USER_BASED', $result['audienceType']);
        $this->assertNotEmpty($result['segmentFilters']);
    }

    /**
     * Test audience segment sharing
     */
    public function test_share_audience_segment(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $shareOptions = [
            'shared_with' => ['user@example.com'],
            'permissions' => ['view', 'edit'],
            'notify_users' => true,
        ];

        $service = $this->createService();
        $result = $service->shareAudienceSegment('segments/123456', $shareOptions);

        $this->assertTrue($result);
    }

    /**
     * Test audience segment sharing with minimal options
     */
    public function test_share_audience_segment_minimal(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $service = $this->createService();
        $result = $service->shareAudienceSegment('segments/789012');

        $this->assertTrue($result);
    }

    /**
     * Test get audience segments
     */
    public function test_get_audience_segments(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $mockResponse = [
            'audiences' => [
                [
                    'name' => 'properties/123456789/audiences/111',
                    'displayName' => 'All Users',
                    'description' => 'All website visitors',
                    'createTime' => '2024-01-01T00:00:00Z',
                    'updateTime' => '2024-01-15T00:00:00Z',
                ],
                [
                    'name' => 'properties/123456789/audiences/222',
                    'displayName' => 'Premium Members',
                    'description' => 'Premium subscription holders',
                    'createTime' => '2024-01-05T00:00:00Z',
                    'updateTime' => '2024-01-20T00:00:00Z',
                ],
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getAudienceSegments();

        $this->assertCount(2, $result);
        $this->assertEquals('properties/123456789/audiences/111', $result[0]['id']);
        $this->assertEquals('All Users', $result[0]['display_name']);
        $this->assertEquals('Premium Members', $result[1]['display_name']);
    }

    /**
     * Test get audience segments with empty response
     */
    public function test_get_audience_segments_empty(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_key');

        $mockResponse = ['audiences' => []];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getAudienceSegments();

        $this->assertEmpty($result);
    }

    /**
     * Test configuration validation with valid config
     */
    public function test_validate_configuration_valid(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret_long_enough');
        Config::set('services.google.analytics.property_id', '123456789');

        // Mock API connectivity check success
        $this->mockHandler->append(new Response(200, [], json_encode(['rows' => []])));

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertNotEmpty($result['details']);
    }

    /**
     * Test configuration validation with missing measurement ID
     */
    public function test_validate_configuration_missing_measurement_id(): void
    {
        Config::set('services.google.analytics.measurement_id', null);
        Config::set('services.google.analytics.api_secret', 'test_api_secret');
        Config::set('services.google.analytics.property_id', '123456789');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Measurement ID is not configured', $result['errors']);
    }

    /**
     * Test configuration validation with invalid measurement ID format
     */
    public function test_validate_configuration_invalid_measurement_id(): void
    {
        Config::set('services.google.analytics.measurement_id', 'invalid-format');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');
        Config::set('services.google.analytics.property_id', '123456789');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('Measurement ID format is invalid', $result['errors']);
    }

    /**
     * Test configuration validation with missing API secret
     */
    public function test_validate_configuration_missing_api_secret(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', null);
        Config::set('services.google.analytics.property_id', '123456789');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertFalse($result['valid']);
        $this->assertContains('API Secret is not configured', $result['errors']);
    }

    /**
     * Test configuration validation with warning for missing property ID
     */
    public function test_validate_configuration_missing_property_id(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');
        Config::set('services.google.analytics.property_id', null);

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertContains('Property ID is not configured', $result['warnings']);
    }

    /**
     * Test configuration validation with short API secret warning
     */
    public function test_validate_configuration_short_api_secret(): void
    {
        Config::set('services.google.analytics.measurement_id', 'G-ABC123XYZ');
        Config::set('services.google.analytics.api_secret', 'short');
        Config::set('services.google.analytics.property_id', '123456789');

        $service = $this->createService();
        $result = $service->validateConfiguration();

        $this->assertTrue($result['valid']);
        $this->assertContains('API Secret seems too short', $result['warnings']);
    }

    /**
     * Test report retrieval
     */
    public function test_get_report(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');

        $mockResponse = [
            'rows' => [
                ['dimensionValues' => [], 'metricValues' => []],
            ],
            'rowCount' => 1,
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $reportRequest = [
            'date_ranges' => [
                ['startDate' => '2024-01-01', 'endDate' => '2024-01-31'],
            ],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'users'],
            ],
            'dimensions' => [
                ['name' => 'country'],
            ],
        ];

        $service = $this->createService();
        $result = $service->getReport($reportRequest);

        $this->assertNotNull($result);
        $this->assertEquals(1, $result['rowCount']);
    }

    /**
     * Test real-time data retrieval
     */
    public function test_get_realtime_data(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');

        $mockResponse = [
            'rows' => [
                [
                    'dimensionValues' => [
                        ['value' => 'United States'],
                        ['value' => 'New York'],
                    ],
                    'metricValues' => [
                        ['value' => '150'],
                    ],
                ],
            ],
        ];

        $this->mockHandler->append(new Response(200, [], json_encode($mockResponse)));

        $service = $this->createService();
        $result = $service->getRealtimeData();

        $this->assertNotNull($result);
        $this->assertCount(1, $result['rows']);
    }

    /**
     * Test audience export
     */
    public function test_export_segments(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');

        $segmentData = [
            [
                'name' => 'test_segment',
                'definition' => ['field' => 'value'],
            ],
        ];

        $service = $this->createService();
        $result = $service->exportSegments($segmentData);

        $this->assertTrue($result);
    }

    /**
     * Test audience creation
     */
    public function test_create_audience(): void
    {
        Config::set('services.google.analytics.property_id', '123456789');
        Config::set('services.google.analytics.api_secret', 'test_api_secret');

        $segmentData = [
            'name' => 'High Value Alumni',
            'description' => 'Alumni with high engagement',
            'criteria' => [
                'min_engagement' => 500,
                'days_since_last_activity' => 7,
            ],
        ];

        $service = $this->createService();
        $result = $service->createAudience($segmentData);

        $this->assertNotNull($result);
        $this->assertEquals('High Value Alumni', $result['displayName']);
        $this->assertNotEmpty($result['filterClauses']);
    }
}
