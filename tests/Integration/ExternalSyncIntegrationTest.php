<?php

namespace Tests\Integration;

use App\Services\Analytics\ConsentService;
use App\Services\Analytics\GoogleAnalyticsService;
use App\Services\Analytics\MatomoService;
use App\Services\SyncService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ExternalSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private SyncService $syncService;
    private GoogleAnalyticsService $gaService;
    private MatomoService $matomoService;
    private ConsentService $consentService;
    private MockHandler $mockHandler;
    private Client $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = new ConsentService();
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);

        $this->gaService = new GoogleAnalyticsService($this->consentService);
        $this->matomoService = new MatomoService($this->consentService);

        // Use reflection to replace the HTTP clients with our mocks
        $gaReflection = new \ReflectionClass($this->gaService);
        $gaHttpClientProperty = $gaReflection->getProperty('httpClient');
        $gaHttpClientProperty->setAccessible(true);
        $gaHttpClientProperty->setValue($this->gaService, $this->httpClient);

        $matomoReflection = new \ReflectionClass($this->matomoService);
        $matomoHttpClientProperty = $matomoReflection->getProperty('httpClient');
        $matomoHttpClientProperty->setAccessible(true);
        $matomoHttpClientProperty->setValue($this->matomoService, $this->httpClient);

        $this->syncService = new SyncService($this->gaService, $this->matomoService, $this->consentService);
    }

    public function test_sync_to_external_success(): void
    {
        // Mock successful responses from both services
        $this->mockHandler->append(new Response(204)); // GA success
        $this->mockHandler->append(new Response(200, [], '1')); // Matomo success

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
            'label' => 'test_label',
            'value' => 10,
            'custom_params' => ['param1' => 'value1'],
        ];

        $result = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');

        $this->assertTrue($result['google_analytics']);
        $this->assertTrue($result['matomo']);
        $this->assertEmpty($result['discrepancies']);
    }

    public function test_sync_to_external_with_discrepancy(): void
    {
        // Mock one success and one failure to create discrepancy
        $this->mockHandler->append(new Response(204)); // GA success
        $this->mockHandler->append(new Response(400)); // Matomo failure

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');

        $this->assertTrue($result['google_analytics']);
        $this->assertFalse($result['matomo']);
        $this->assertNotEmpty($result['discrepancies']);
    }

    public function test_sync_to_external_no_consent(): void
    {
        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result['google_analytics']);
        $this->assertFalse($result['matomo']);
        $this->assertEmpty($result['discrepancies']);
    }

    public function test_sync_goals_to_external(): void
    {
        // Mock successful response for GA goal sync
        $this->mockHandler->append(new Response(200, [], json_encode(['success' => true])));

        $funnelData = [
            [
                'name' => 'test_goal',
                'conversion_events' => ['event1', 'event2'],
            ]
        ];

        $result = $this->syncService->syncGoalsToExternal($funnelData);

        $this->assertTrue($result['google_analytics']);
    }

    public function test_sync_segments_to_external(): void
    {
        // Mock successful response for GA segment sync
        $this->mockHandler->append(new Response(200, [], json_encode(['success' => true])));

        $segmentData = [
            [
                'name' => 'test_segment',
                'definition' => ['field' => 'value'],
            ]
        ];

        $result = $this->syncService->syncSegmentsToExternal($segmentData);

        $this->assertTrue($result['google_analytics']);
    }

    public function test_sync_to_external_with_tenant_isolation(): void
    {
        // Mock successful responses
        $this->mockHandler->append(new Response(204)); // GA success
        $this->mockHandler->append(new Response(200, [], '1')); // Matomo success

        $eventData = [
            'name' => 'tenant_event',
            'category' => 'tenant_category',
        ];

        // Test with different tenant IDs
        $result1 = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');
        $result2 = $this->syncService->syncToExternal($eventData, 'tenant-456', 'standard');

        $this->assertTrue($result1['google_analytics']);
        $this->assertTrue($result1['matomo']);
        $this->assertTrue($result2['google_analytics']);
        $this->assertTrue($result2['matomo']);
    }

    public function test_sync_to_external_guzzle_exception_handling(): void
    {
        // Mock Guzzle exceptions
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'GA Error',
            new \GuzzleHttp\Psr7\Request('POST', 'ga-test')
        ));
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Matomo Error',
            new \GuzzleHttp\Psr7\Request('GET', 'matomo-test')
        ));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');

        // Should handle exceptions gracefully and return false for both
        $this->assertFalse($result['google_analytics']);
        $this->assertFalse($result['matomo']);
    }

    public function test_sync_to_external_with_empty_event_data(): void
    {
        // Mock successful responses
        $this->mockHandler->append(new Response(204)); // GA success
        $this->mockHandler->append(new Response(200, [], '1')); // Matomo success

        $eventData = []; // Empty event data

        $result = $this->syncService->syncToExternal($eventData, 'tenant-123', 'premium');

        // Should still attempt to sync even with minimal data
        $this->assertTrue($result['google_analytics']);
        $this->assertTrue($result['matomo']);
    }
}