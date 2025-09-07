<?php

namespace Tests\Unit\Services;

use App\Services\EmailSendingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class EmailSendingServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailSendingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailSendingService();
        Cache::flush();
    }

    /** @test */
    public function it_sends_email_successfully()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'internal',
        ];

        $result = $this->service->sendEmail($emailData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message_id', $result);
    }

    /** @test */
    public function it_validates_email_data()
    {
        // Valid data
        $validData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
        ];

        $result = $this->service->validateEmailData($validData);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);

        // Invalid data
        $invalidData = [
            'subject' => 'Test Subject',
            'content' => 'Test content',
        ];

        $result = $this->service->validateEmailData($invalidData);
        $this->assertFalse($result['valid']);
        $this->assertContains('Recipient email is required', $result['errors']);
    }

    /** @test */
    public function it_enforces_rate_limits()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'mailgun',
        ];

        // Send emails up to the limit
        for ($i = 0; $i < 300; $i++) {
            $result = $this->service->sendEmail($emailData);
            $this->assertTrue($result['success']);
        }

        // Next email should be rate limited
        $result = $this->service->sendEmail($emailData);
        $this->assertFalse($result['success']);
        $this->assertEquals('Rate limit exceeded', $result['error']);
        $this->assertArrayHasKey('retry_after', $result);
    }

    /** @test */
    public function it_sends_bulk_emails_with_batching()
    {
        $emailsData = [
            [
                'to' => 'test1@example.com',
                'subject' => 'Test Subject 1',
                'content' => 'Test content 1',
            ],
            [
                'to' => 'test2@example.com',
                'subject' => 'Test Subject 2',
                'content' => 'Test content 2',
            ],
        ];

        $result = $this->service->sendBulkEmails($emailsData, 'internal');

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['successful']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(2, $result['results']);
    }

    /** @test */
    public function it_handles_provider_errors_gracefully()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'invalid_provider',
        ];

        $result = $this->service->validateEmailData($emailData);
        $this->assertFalse($result['valid']);
        $this->assertContains('Unsupported email provider: invalid_provider', $result['errors']);
    }

    /** @test */
    public function it_tests_provider_connectivity()
    {
        $result = $this->service->testProviderConnection('internal');
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_gets_provider_statistics()
    {
        $stats = $this->service->getProviderStats('internal');

        $this->assertArrayHasKey('healthy', $stats);
        $this->assertArrayHasKey('rate_limit_remaining', $stats);
        $this->assertArrayHasKey('rate_limit_reset', $stats);
        $this->assertTrue($stats['healthy']);
    }

    /** @test */
    public function it_handles_email_send_exceptions()
    {
        // Test with invalid email data that would cause internal exceptions
        $emailData = [
            'to' => 'invalid-email',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'internal',
        ];

        $result = $this->service->sendEmail($emailData);
        // The internal provider should handle this gracefully
        $this->assertIsArray($result);
    }

    /** @test */
    public function it_updates_rate_limit_counters()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'mailgun',
        ];

        // Send one email
        $this->service->sendEmail($emailData);

        // Check that rate limit counter was incremented
        $minuteKey = "email:mailgun:minute:" . now()->format('Y-m-d-H-i');
        $this->assertEquals(1, Cache::get($minuteKey));
    }

    /** @test */
    public function it_handles_bulk_send_partial_failures()
    {
        $emailsData = [
            [
                'to' => 'valid@example.com',
                'subject' => 'Test Subject 1',
                'content' => 'Test content 1',
            ],
            [
                'to' => '', // Invalid email
                'subject' => 'Test Subject 2',
                'content' => 'Test content 2',
            ],
        ];

        $result = $this->service->sendBulkEmails($emailsData, 'internal');

        $this->assertEquals(2, $result['total']);
        // Both should succeed with internal provider as it handles errors gracefully
        $this->assertGreaterThanOrEqual(1, $result['successful']);
    }

    /** @test */
    public function it_respects_different_provider_limits()
    {
        // Test SES limits (14 per minute)
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'ses',
        ];

        for ($i = 0; $i < 14; $i++) {
            $result = $this->service->sendEmail($emailData);
            $this->assertTrue($result['success']);
        }

        // 15th email should be rate limited
        $result = $this->service->sendEmail($emailData);
        $this->assertFalse($result['success']);
        $this->assertEquals('Rate limit exceeded', $result['error']);
    }

    /** @test */
    public function it_logs_email_sending_activities()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'provider' => 'internal',
        ];

        Log::shouldReceive('info')
            ->once()
            ->with('Sending email via provider', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Email sent successfully', Mockery::type('array'));

        $this->service->sendEmail($emailData);
    }

    /** @test */
    public function it_handles_template_data_in_emails()
    {
        $emailData = [
            'to' => 'test@example.com',
            'subject' => 'Test Subject',
            'content' => 'Test content',
            'template_data' => [
                'name' => 'John Doe',
                'company' => 'Test Company',
            ],
            'provider' => 'internal',
        ];

        $result = $this->service->sendEmail($emailData);
        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_provides_correct_batch_sizes_for_different_providers()
    {
        // Test that different providers have different batch sizes
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getBatchSize');
        $method->setAccessible(true);

        $this->assertEquals(1000, $method->invoke($this->service, 'mailgun'));
        $this->assertEquals(50, $method->invoke($this->service, 'ses'));
        $this->assertEquals(100, $method->invoke($this->service, 'internal'));
    }

    /** @test */
    public function it_provides_correct_batch_delays_for_different_providers()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getBatchDelay');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke($this->service, 'mailgun'));
        $this->assertEquals(1, $method->invoke($this->service, 'ses'));
        $this->assertEquals(0, $method->invoke($this->service, 'internal'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}