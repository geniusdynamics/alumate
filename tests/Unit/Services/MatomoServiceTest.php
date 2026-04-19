<?php

namespace Tests\Unit\Services;

use App\Services\Analytics\ConsentService;
use App\Services\Analytics\MatomoService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MatomoServiceTest extends TestCase
{
    private MatomoService $service;
    private $consentService;
    private MockHandler $mockHandler;
    private Client $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = $this->createMock(ConsentService::class);
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);

        $this->service = new MatomoService($this->consentService);
        
        // Use reflection to replace the HTTP client with our mock
        $reflection = new \ReflectionClass($this->service);
        $httpClientProperty = $reflection->getProperty('httpClient');
        $httpClientProperty->setAccessible(true);
        $httpClientProperty->setValue($this->service, $this->httpClient);
    }

    public function test_track_event_success(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response
        $this->mockHandler->append(new Response(200, [], '1'));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
            'action' => 'test_action',
            'url' => 'https://example.com',
            'user_agent' => 'Test Agent',
            'language' => 'en',
        ];

        $result = $this->service->trackEvent($eventData, 'tenant-123');

        $this->assertTrue($result);
    }

    public function test_track_event_no_consent(): void
    {
        // Mock no consent
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(false);

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->trackEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    public function test_track_event_missing_credentials(): void
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

        $result = $this->service->trackEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    public function test_track_event_api_failure(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock API failure
        $this->mockHandler->append(new Response(400));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->trackEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    public function test_sync_data_success(): void
    {
        // Mock consent granted for any internal operations that might check it
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response with Matomo data
        $mockData = json_encode([
            ['label' => 'event1', 'nb_events' => 10],
            ['label' => 'event2', 'nb_events' => 5]
        ]);
        $this->mockHandler->append(new Response(200, [], $mockData));

        $syncData = [
            'events' => ['event1', 'event2']
        ];

        $result = $this->service->syncData($syncData);

        $this->assertTrue($result);
    }

    public function test_get_report_success(): void
    {
        // Mock successful response with Matomo report data
        $mockData = json_encode([
            'report' => 'test_report',
            'data' => ['value' => 100]
        ]);
        $this->mockHandler->append(new Response(200, [], $mockData));

        $result = $this->service->getReport('Events.getAction');

        $this->assertIsArray($result);
        $this->assertEquals('test_report', $result['report']);
    }

    public function test_get_report_missing_credentials(): void
    {
        // Mock missing credentials
        Config::set('services.matomo.url', null);
        Config::set('services.matomo.site_id', null);
        Config::set('services.matomo.token_auth', null);

        $result = $this->service->getReport('Events.getAction');

        $this->assertNull($result);
    }

    public function test_track_event_guzzle_exception(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        ));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->trackEvent($eventData, 'tenant-123');

        $this->assertFalse($result);
    }

    public function test_sync_data_guzzle_exception(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        ));

        $syncData = [
            'events' => ['event1', 'event2']
        ];

        $result = $this->service->syncData($syncData);

        $this->assertFalse($result);
    }

    public function test_get_report_guzzle_exception(): void
    {
        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('GET', 'test')
        ));

        $result = $this->service->getReport('Events.getAction');

        $this->assertNull($result);
    }
}