<?php

namespace Tests\Unit\Services;

use App\Services\Analytics\ConsentService;
use App\Services\Analytics\GoogleAnalyticsService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class GoogleAnalyticsServiceTest extends TestCase
{
    private GoogleAnalyticsService $service;
    private ConsentService&MockObject $consentService;
    private MockHandler $mockHandler;
    private Client $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = $this->createMock(ConsentService::class);
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->httpClient = new Client(['handler' => $handlerStack]);

        // Create a spy for the service to allow testing without complex mocking
        $this->service = $this->getMockBuilder(GoogleAnalyticsService::class)
            ->setConstructorArgs([$this->consentService])
            ->onlyMethods(['getHttpClient'])
            ->getMock();

        $this->service->method('getHttpClient')
            ->willReturn($this->httpClient);
    }

    public function test_forward_event_success(): void
    {
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
        ];

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertTrue($result);
    }

    public function test_forward_event_no_consent(): void
    {
        // Mock no consent
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(false);

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

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

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    public function test_forward_event_api_failure(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock API failure
        $this->mockHandler->append(new Response(400));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    public function test_sync_goals_success(): void
    {
        // Mock consent granted for any internal operations that might check it
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        $funnelData = [
            [
                'name' => 'test_goal',
                'conversion_events' => ['event1', 'event2'],
            ]
        ];

        $result = $this->service->syncGoals($funnelData);

        $this->assertTrue($result);
    }

    public function test_export_segments_success(): void
    {
        // Mock consent granted for any internal operations that might check it
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        $segmentData = [
            [
                'name' => 'test_segment',
                'definition' => ['field' => 'value'],
            ]
        ];

        $result = $this->service->exportSegments($segmentData);

        $this->assertTrue($result);
    }

    public function test_forward_event_guzzle_exception(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock Guzzle exception
        $this->mockHandler->append(new \GuzzleHttp\Exception\RequestException(
            'Error',
            new \GuzzleHttp\Psr7\Request('POST', 'test')
        ));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }

    public function test_forward_event_general_exception(): void
    {
        // Mock consent granted
        $this->consentService->expects($this->once())->method('hasConsent')->willReturn(true);

        // Mock successful response but with unexpected content
        $this->mockHandler->append(new Response(200, [], 'invalid'));

        $eventData = [
            'name' => 'test_event',
            'category' => 'test_category',
        ];

        $result = $this->service->forwardEvent($eventData, 'tenant-123', 'premium');

        $this->assertFalse($result);
    }
}