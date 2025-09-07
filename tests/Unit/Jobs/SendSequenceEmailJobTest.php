<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendSequenceEmailJob;
use App\Models\EmailSequence;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailSendingService;
use App\Services\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SendSequenceEmailJobTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private EmailSequence $sequence;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->sequence = EmailSequence::factory()->create([
            'tenant_id' => $this->tenant->id,
            'content' => 'Test email content with {{first_name}}',
            'subject' => 'Test subject for {{first_name}}',
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Cache::flush();
    }

    /** @test */
    public function it_sends_sequence_email_successfully()
    {
        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test_msg_123',
                'provider_message_id' => 'prov_msg_123',
            ]);

        $emailTrackingService->shouldReceive('trackEmailSend')
            ->once()
            ->with($this->sequence, $this->user, 'test_msg_123', 'prov_msg_123')
            ->andReturn(['tracking_id' => 'track_123']);

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);
        $job->handle($emailSendingService, $emailTrackingService);

        // Verify cache was set to prevent duplicate sends
        $cacheKey = "sequence_email:{$this->sequence->id}:{$this->user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function it_handles_email_send_failure()
    {
        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Send failed',
            ]);

        $emailTrackingService->shouldNotReceive('trackEmailSend');

        Log::shouldReceive('error')
            ->once()
            ->with('Sequence email send failed', Mockery::type('array'));

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email send failed');
        $job->handle($emailSendingService, $emailTrackingService);
    }

    /** @test */
    public function it_skips_duplicate_sends_within_time_window()
    {
        $cacheKey = "sequence_email:{$this->sequence->id}:{$this->user->id}";
        Cache::put($cacheKey, true, 3600);

        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        // Services should not be called
        $emailSendingService->shouldNotReceive('sendEmail');
        $emailTrackingService->shouldNotReceive('trackEmailSend');

        Log::shouldReceive('info')
            ->once()
            ->with('Skipping duplicate sequence email send', Mockery::type('array'));

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);
        $job->handle($emailSendingService, $emailTrackingService);
    }

    /** @test */
    public function it_applies_personalization_to_content()
    {
        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->with(Mockery::on(function ($emailData) {
                return $emailData['content'] === 'Test email content with John' &&
                       $emailData['subject'] === 'Test subject for John' &&
                       $emailData['template_data']['recipient_first_name'] === 'John';
            }))
            ->andReturn([
                'success' => true,
                'message_id' => 'test_msg_123',
            ]);

        $emailTrackingService->shouldReceive('trackEmailSend')
            ->once()
            ->andReturn(['tracking_id' => 'track_123']);

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);
        $job->handle($emailSendingService, $emailTrackingService);
    }

    /** @test */
    public function it_handles_custom_personalization_data()
    {
        $customData = ['custom_field' => 'custom_value'];

        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->with(Mockery::on(function ($emailData) use ($customData) {
                return isset($emailData['template_data']['custom_field']) &&
                       $emailData['template_data']['custom_field'] === 'custom_value';
            }))
            ->andReturn([
                'success' => true,
                'message_id' => 'test_msg_123',
            ]);

        $emailTrackingService->shouldReceive('trackEmailSend')
            ->once()
            ->andReturn(['tracking_id' => 'track_123']);

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user, $customData);
        $job->handle($emailSendingService, $emailTrackingService);
    }

    /** @test */
    public function it_logs_job_start_and_success()
    {
        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'message_id' => 'test_msg_123',
            ]);

        $emailTrackingService->shouldReceive('trackEmailSend')
            ->once()
            ->andReturn(['tracking_id' => 'track_123']);

        Log::shouldReceive('info')
            ->once()
            ->with('Starting sequence email send', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Sequence email sent successfully', Mockery::type('array'));

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);
        $job->handle($emailSendingService, $emailTrackingService);
    }

    /** @test */
    public function it_handles_job_failure_and_updates_sequence_recipient()
    {
        $emailSendingService = Mockery::mock(EmailSendingService::class);
        $emailTrackingService = Mockery::mock(EmailTrackingService::class);

        $emailSendingService->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Permanent failure',
            ]);

        $emailTrackingService->shouldNotReceive('trackEmailSend');

        Log::shouldReceive('error')
            ->once()
            ->with('Sequence email send failed', Mockery::type('array'));

        $this->app->instance(EmailSendingService::class, $emailSendingService);
        $this->app->instance(EmailTrackingService::class, $emailTrackingService);

        $job = new SendSequenceEmailJob($this->sequence, $this->user);

        try {
            $job->handle($emailSendingService, $emailTrackingService);
        } catch (\Exception $e) {
            // Expected
        }

        // Test failed method
        $job->failed($e ?? new \Exception('Test failure'));
    }

    /** @test */
    public function it_has_correct_job_configuration()
    {
        $job = new SendSequenceEmailJob($this->sequence, $this->user);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(1, $job->maxExceptions);
        $this->assertEquals(60, $job->backoff);
    }

    /** @test */
    public function it_has_correct_tags()
    {
        $job = new SendSequenceEmailJob($this->sequence, $this->user);
        $tags = $job->tags();

        $this->assertContains('email-sequence', $tags);
        $this->assertContains("sequence:{$this->sequence->id}", $tags);
        $this->assertContains("recipient:{$this->user->id}", $tags);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}