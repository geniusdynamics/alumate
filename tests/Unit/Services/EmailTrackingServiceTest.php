<?php

namespace Tests\Unit\Services;

use App\Models\EmailSequence;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class EmailTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailTrackingService $service;
    private Tenant $tenant;
    private EmailSequence $sequence;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EmailTrackingService();
        $this->tenant = Tenant::factory()->create();
        $this->sequence = EmailSequence::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email' => 'test@example.com',
        ]);

        Cache::flush();
    }

    /** @test */
    public function it_tracks_email_send_successfully()
    {
        $result = $this->service->trackEmailSend(
            $this->sequence,
            $this->user,
            'test_msg_123',
            'prov_msg_456'
        );

        $this->assertArrayHasKey('tracking_id', $result);
        $this->assertArrayHasKey('tracking_data', $result);

        $trackingData = $result['tracking_data'];
        $this->assertEquals($this->sequence->id, $trackingData['sequence_id']);
        $this->assertEquals($this->user->id, $trackingData['user_id']);
        $this->assertEquals('test_msg_123', $trackingData['message_id']);
        $this->assertEquals('prov_msg_456', $trackingData['provider_message_id']);
        $this->assertEquals('sent', $trackingData['status']);
    }

    /** @test */
    public function it_tracks_email_open_successfully()
    {
        // First track the email send
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user);
        $trackingId = $sendResult['tracking_id'];

        // Then track the open
        $result = $this->service->trackEmailOpen($trackingId, ['user_agent' => 'Test Browser']);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->sequence->id, $result['sequence_id']);
        $this->assertEquals($this->user->id, $result['user_id']);

        // Verify tracking data was updated
        $updatedData = Cache::get("email_tracking:{$trackingId}");
        $this->assertEquals('opened', $updatedData['status']);
        $this->assertArrayHasKey('open_metadata', $updatedData);
    }

    /** @test */
    public function it_handles_invalid_tracking_id_for_open()
    {
        $result = $this->service->trackEmailOpen('invalid_tracking_id');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid tracking ID', $result['error']);
    }

    /** @test */
    public function it_tracks_link_click_successfully()
    {
        // First track the email send
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user);
        $trackingId = $sendResult['tracking_id'];

        // Then track the click
        $result = $this->service->trackLinkClick(
            $trackingId,
            'https://example.com/link',
            ['referrer' => 'email']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('https://example.com/link', $result['redirect_url']);

        // Verify tracking data was updated
        $updatedData = Cache::get("email_tracking:{$trackingId}");
        $this->assertEquals('clicked', $updatedData['status']);
        $this->assertArrayHasKey('clicks', $updatedData);
        $this->assertCount(1, $updatedData['clicks']);
    }

    /** @test */
    public function it_handles_invalid_tracking_id_for_click()
    {
        $result = $this->service->trackLinkClick('invalid_tracking_id', 'https://example.com');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid tracking ID', $result['error']);
    }

    /** @test */
    public function it_processes_bounce_notifications()
    {
        // First track the email send
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user, 'test_msg_123');
        $trackingId = $sendResult['tracking_id'];

        // Process bounce notification
        $bounceData = [
            'message_id' => 'test_msg_123',
            'email' => 'test@example.com',
            'bounce_type' => 'Permanent',
        ];

        $result = $this->service->processBounceNotification($bounceData);

        $this->assertTrue($result['success']);

        // Verify tracking data was updated
        $updatedData = Cache::get("email_tracking:{$trackingId}");
        $this->assertEquals('bounced', $updatedData['status']);
        $this->assertEquals('Permanent', $updatedData['bounce_type']);
    }

    /** @test */
    public function it_processes_complaint_notifications()
    {
        // First track the email send
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user, 'test_msg_123');
        $trackingId = $sendResult['tracking_id'];

        // Process complaint notification
        $complaintData = [
            'message_id' => 'test_msg_123',
            'email' => 'test@example.com',
        ];

        $result = $this->service->processComplaintNotification($complaintData);

        $this->assertTrue($result['success']);

        // Verify tracking data was updated
        $updatedData = Cache::get("email_tracking:{$trackingId}");
        $this->assertEquals('unsubscribed', $updatedData['status']);
        $this->assertArrayHasKey('complaint_data', $updatedData);
    }

    /** @test */
    public function it_generates_tracking_pixel_url()
    {
        $trackingId = 'test_tracking_123';
        $url = $this->service->generateTrackingPixelUrl($trackingId);

        $this->assertStringContainsString('email.tracking.pixel', $url);
        $this->assertStringContainsString('trackingId=test_tracking_123', $url);
    }

    /** @test */
    public function it_generates_tracking_url()
    {
        $trackingId = 'test_tracking_123';
        $destinationUrl = 'https://example.com/page';
        $url = $this->service->generateTrackingUrl($trackingId, $destinationUrl);

        $this->assertStringContainsString('email.tracking.click', $url);
        $this->assertStringContainsString('trackingId=test_tracking_123', $url);
        $this->assertStringContainsString('url=https%3A%2F%2Fexample.com%2Fpage', $url);
    }

    /** @test */
    public function it_returns_sequence_metrics()
    {
        $metrics = $this->service->getSequenceMetrics($this->sequence->id);

        $this->assertArrayHasKey('sent', $metrics);
        $this->assertArrayHasKey('opened', $metrics);
        $this->assertArrayHasKey('clicked', $metrics);
        $this->assertArrayHasKey('bounced', $metrics);
        $this->assertArrayHasKey('unsubscribed', $metrics);
        $this->assertArrayHasKey('open_rate', $metrics);
        $this->assertArrayHasKey('click_rate', $metrics);
        $this->assertArrayHasKey('bounce_rate', $metrics);
        $this->assertArrayHasKey('unsubscribe_rate', $metrics);
    }

    /** @test */
    public function it_handles_bounce_without_message_id_or_email()
    {
        $result = $this->service->processBounceNotification([]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Missing message ID or email', $result['error']);
    }

    /** @test */
    public function it_handles_complaint_without_message_id_or_email()
    {
        $result = $this->service->processComplaintNotification([]);

        $this->assertFalse($result['success']);
        $this->assertEquals('Missing message ID or email', $result['error']);
    }

    /** @test */
    public function it_processes_webhook_events()
    {
        $request = Mockery::mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('all')->andReturn([
            [
                'event' => 'delivered',
                'tracking_id' => 'test_tracking_123',
            ],
        ]);

        // Mock the tracking data
        Cache::put('email_tracking:test_tracking_123', [
            'sequence_id' => $this->sequence->id,
            'user_id' => $this->user->id,
            'status' => 'sent',
        ]);

        $result = $this->service->processWebhook($request, 'mailgun');

        $this->assertEquals(1, $result['total_events']);
        $this->assertGreaterThanOrEqual(0, $result['processed']);
        $this->assertGreaterThanOrEqual(0, $result['failed']);
    }

    /** @test */
    public function it_handles_duplicate_open_tracking()
    {
        // First track the email send
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user);
        $trackingId = $sendResult['tracking_id'];

        // First open
        $result1 = $this->service->trackEmailOpen($trackingId);
        $this->assertTrue($result1['success']);

        // Duplicate open
        $result2 = $this->service->trackEmailOpen($trackingId);
        $this->assertTrue($result2['success']);
        $this->assertTrue($result2['duplicate']);
    }

    /** @test */
    public function it_logs_tracking_activities()
    {
        // Track email send
        Log::shouldReceive('info')
            ->once()
            ->with('Email tracking record created', Mockery::type('array'));

        $this->service->trackEmailSend($this->sequence, $this->user);

        // Track email open
        $sendResult = $this->service->trackEmailSend($this->sequence, $this->user);
        $trackingId = $sendResult['tracking_id'];

        Log::shouldReceive('info')
            ->once()
            ->with('Email opened', Mockery::type('array'));

        $this->service->trackEmailOpen($trackingId);

        // Track link click
        Log::shouldReceive('info')
            ->once()
            ->with('Email link clicked', Mockery::type('array'));

        $this->service->trackLinkClick($trackingId, 'https://example.com');
    }

    /** @test */
    public function it_handles_bounce_notification_errors()
    {
        $bounceData = [
            'message_id' => 'nonexistent_msg',
            'email' => 'nonexistent@example.com',
        ];

        Log::shouldReceive('warning')
            ->once()
            ->with('Bounce notification - tracking data not found', Mockery::type('array'));

        $result = $this->service->processBounceNotification($bounceData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Tracking data not found', $result['error']);
    }

    /** @test */
    public function it_handles_complaint_notification_errors()
    {
        $complaintData = [
            'message_id' => 'nonexistent_msg',
            'email' => 'nonexistent@example.com',
        ];

        Log::shouldReceive('warning')
            ->once()
            ->with('Complaint notification - tracking data not found', Mockery::type('array'));

        $result = $this->service->processComplaintNotification($complaintData);

        $this->assertFalse($result['success']);
        $this->assertEquals('Tracking data not found', $result['error']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}