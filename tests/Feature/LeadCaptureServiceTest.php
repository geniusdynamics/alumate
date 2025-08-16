<?php

namespace Tests\Feature;

use App\Mail\AdminDemoNotification;
use App\Mail\AdminTrialNotification;
use App\Mail\DemoRequestConfirmation;
use App\Mail\TrialSignupConfirmation;
use App\Services\LeadCaptureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LeadCaptureServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeadCaptureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeadCaptureService;
        Cache::flush();
        Mail::fake();
        Log::fake();
    }

    /** @test */
    public function it_can_process_trial_signup_successfully()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'graduationYear' => 2020,
            'institution' => 'Test University',
            'currentRole' => 'Software Engineer',
            'industry' => 'technology',
            'referralSource' => 'search_engine',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $result = $this->service->processTrialSignup($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Trial signup processed successfully', $result['message']);
        $this->assertArrayHasKey('trial_id', $result);
        $this->assertArrayHasKey('next_steps', $result);
    }

    /** @test */
    public function it_sanitizes_trial_data_correctly()
    {
        $data = [
            'name' => '  John Doe  ',
            'email' => '  JOHN@EXAMPLE.COM  ',
            'graduationYear' => 2020,
            'institution' => '  Test University  ',
            'currentRole' => '  Software Engineer  ',
            'industry' => 'technology',
            'referralSource' => 'search_engine',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        // Check that data was stored in cache with proper sanitization
        $cacheKeys = Cache::getRedis()->keys('*trial_lead_*');
        $this->assertCount(1, $cacheKeys);

        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));

        $this->assertEquals('John Doe', $cachedData['name']);
        $this->assertEquals('john@example.com', $cachedData['email']);
        $this->assertEquals('Test University', $cachedData['institution']);
        $this->assertEquals('Software Engineer', $cachedData['current_role']);
    }

    /** @test */
    public function it_sends_trial_confirmation_email()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        Mail::assertSent(TrialSignupConfirmation::class, function ($mail) {
            return $mail->hasTo('john@example.com');
        });
    }

    /** @test */
    public function it_sends_admin_trial_notification()
    {
        config(['app.admin_emails' => ['admin@example.com', 'manager@example.com']]);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        Mail::assertSent(AdminTrialNotification::class, 2); // Sent to 2 admin emails
    }

    /** @test */
    public function it_can_process_demo_request_successfully()
    {
        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'title' => 'Alumni Relations Director',
            'phone' => '(555) 123-4567',
            'alumniCount' => '5000_10000',
            'currentSolution' => 'spreadsheets',
            'interests' => ['mobile_app', 'analytics'],
            'preferredTime' => 'morning',
            'message' => 'Looking to improve alumni engagement',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $result = $this->service->processDemoRequest($data);

        $this->assertTrue($result['success']);
        $this->assertEquals('Demo request submitted successfully', $result['message']);
        $this->assertArrayHasKey('request_id', $result);
        $this->assertArrayHasKey('next_steps', $result);
    }

    /** @test */
    public function it_sanitizes_demo_data_correctly()
    {
        $data = [
            'institutionName' => '  Test University  ',
            'contactName' => '  Jane Smith  ',
            'email' => '  JANE@TESTUNIVERSITY.EDU  ',
            'title' => '  Alumni Relations Director  ',
            'phone' => '  (555) 123-4567  ',
            'alumniCount' => '5000_10000',
            'currentSolution' => 'spreadsheets',
            'interests' => ['mobile_app', 'analytics'],
            'preferredTime' => 'morning',
            'message' => '  Looking to improve alumni engagement  ',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $this->service->processDemoRequest($data);

        // Check that data was stored in cache with proper sanitization
        $cacheKeys = Cache::getRedis()->keys('*demo_lead_*');
        $this->assertCount(1, $cacheKeys);

        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));

        $this->assertEquals('Test University', $cachedData['institution_name']);
        $this->assertEquals('Jane Smith', $cachedData['contact_name']);
        $this->assertEquals('jane@testuniversity.edu', $cachedData['email']);
        $this->assertEquals('Alumni Relations Director', $cachedData['title']);
        $this->assertEquals('(555) 123-4567', $cachedData['phone']);
        $this->assertEquals('Looking to improve alumni engagement', $cachedData['message']);
    }

    /** @test */
    public function it_sends_demo_confirmation_email()
    {
        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $this->service->processDemoRequest($data);

        Mail::assertSent(DemoRequestConfirmation::class, function ($mail) {
            return $mail->hasTo('jane@testuniversity.edu');
        });
    }

    /** @test */
    public function it_sends_sales_team_demo_notification()
    {
        config(['app.sales_emails' => ['sales@example.com', 'manager@example.com']]);

        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $this->service->processDemoRequest($data);

        Mail::assertSent(AdminDemoNotification::class, 2); // Sent to 2 sales emails
    }

    /** @test */
    public function it_calculates_demo_priority_correctly()
    {
        // High priority demo (large alumni count + high-value interests)
        $highPriorityData = [
            'institutionName' => 'Large University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@largeuniversity.edu',
            'alumniCount' => 'over_50000',
            'currentSolution' => 'none',
            'interests' => ['analytics', 'integrations', 'fundraising'],
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $result = $this->service->processDemoRequest($highPriorityData);

        $cacheKeys = Cache::getRedis()->keys('*demo_lead_*');
        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));

        $this->assertEquals('high', $cachedData['priority']);

        // Low priority demo (small alumni count + few interests)
        Cache::flush();

        $lowPriorityData = [
            'institutionName' => 'Small College',
            'contactName' => 'John Doe',
            'email' => 'john@smallcollege.edu',
            'alumniCount' => 'under_1000',
            'currentSolution' => 'crm',
            'interests' => ['mobile_app'],
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processDemoRequest($lowPriorityData);

        $cacheKeys = Cache::getRedis()->keys('*demo_lead_*');
        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));

        $this->assertEquals('low', $cachedData['priority']);
    }

    /** @test */
    public function it_stores_trial_lead_in_cache()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        // Check that data was stored in cache
        $cacheKeys = Cache::getRedis()->keys('*trial_lead_*');
        $this->assertCount(1, $cacheKeys);

        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));
        $this->assertEquals('John Doe', $cachedData['name']);
        $this->assertEquals('john@example.com', $cachedData['email']);
        $this->assertEquals('professional', $cachedData['plan_id']);
    }

    /** @test */
    public function it_stores_demo_lead_in_cache()
    {
        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $this->service->processDemoRequest($data);

        // Check that data was stored in cache
        $cacheKeys = Cache::getRedis()->keys('*demo_lead_*');
        $this->assertCount(1, $cacheKeys);

        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));
        $this->assertEquals('Test University', $cachedData['institution_name']);
        $this->assertEquals('Jane Smith', $cachedData['contact_name']);
        $this->assertEquals('jane@testuniversity.edu', $cachedData['email']);
    }

    /** @test */
    public function it_tracks_conversion_metrics()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        // Check that conversion metrics were tracked
        $today = now()->format('Y-m-d');
        $metrics = Cache::get("conversion_metrics_trial_{$today}");

        $this->assertNotNull($metrics);
        $this->assertEquals(1, $metrics['count']);
        $this->assertEquals(1, $metrics['sources']['pricing_modal']);
    }

    /** @test */
    public function it_provides_trial_next_steps()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $result = $this->service->processTrialSignup($data);

        $nextSteps = $result['next_steps'];
        $this->assertArrayHasKey('login_url', $nextSteps);
        $this->assertArrayHasKey('setup_guide', $nextSteps);
        $this->assertArrayHasKey('support_email', $nextSteps);
        $this->assertArrayHasKey('trial_duration', $nextSteps);
        $this->assertArrayHasKey('trial_end_date', $nextSteps);
    }

    /** @test */
    public function it_provides_demo_next_steps()
    {
        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $result = $this->service->processDemoRequest($data);

        $nextSteps = $result['next_steps'];
        $this->assertArrayHasKey('contact_timeline', $nextSteps);
        $this->assertArrayHasKey('demo_duration', $nextSteps);
        $this->assertArrayHasKey('preparation_guide', $nextSteps);
        $this->assertArrayHasKey('contact_email', $nextSteps);
        $this->assertArrayHasKey('contact_phone', $nextSteps);
    }

    /** @test */
    public function it_handles_trial_processing_errors_gracefully()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail service unavailable'));

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to process trial signup. Please try again.');

        $this->service->processTrialSignup($data);
    }

    /** @test */
    public function it_handles_demo_processing_errors_gracefully()
    {
        // Mock mail to throw exception
        Mail::shouldReceive('to')->andThrow(new \Exception('Mail service unavailable'));

        $data = [
            'institutionName' => 'Test University',
            'contactName' => 'Jane Smith',
            'email' => 'jane@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to process demo request. Please try again.');

        $this->service->processDemoRequest($data);
    }

    /** @test */
    public function it_can_get_lead_statistics()
    {
        // Process some leads
        $this->service->processTrialSignup([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ]);

        $this->service->processTrialSignup([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'planId' => 'professional',
            'source' => 'website',
        ]);

        $this->service->processDemoRequest([
            'institutionName' => 'Test University',
            'contactName' => 'Bob Johnson',
            'email' => 'bob@testuniversity.edu',
            'planId' => 'enterprise',
            'source' => 'pricing_modal',
        ]);

        $statistics = $this->service->getLeadStatistics();

        $this->assertEquals(2, $statistics['today']['trials']);
        $this->assertEquals(1, $statistics['today']['demos']);
        $this->assertEquals(3, $statistics['today']['total']);

        $this->assertEquals(1, $statistics['sources']['trial']['pricing_modal']);
        $this->assertEquals(1, $statistics['sources']['trial']['website']);
        $this->assertEquals(1, $statistics['sources']['demo']['pricing_modal']);
    }

    /** @test */
    public function it_sets_correct_trial_dates()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
        ];

        $this->service->processTrialSignup($data);

        $cacheKeys = Cache::getRedis()->keys('*trial_lead_*');
        $cachedData = Cache::get(str_replace(config('cache.prefix').':', '', $cacheKeys[0]));

        $trialStart = $cachedData['trial_start_date'];
        $trialEnd = $cachedData['trial_end_date'];

        $this->assertInstanceOf(\Carbon\Carbon::class, $trialStart);
        $this->assertInstanceOf(\Carbon\Carbon::class, $trialEnd);
        $this->assertEquals(14, $trialStart->diffInDays($trialEnd));
    }

    /** @test */
    public function it_logs_analytics_events()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'planId' => 'professional',
            'source' => 'pricing_modal',
            'industry' => 'technology',
            'referralSource' => 'search_engine',
        ];

        $this->service->processTrialSignup($data);

        Log::assertLogged('info', function ($message, $context) {
            return $message === 'Analytics event tracked' &&
                   $context['event'] === 'trial_signup' &&
                   $context['properties']['plan_id'] === 'professional';
        });
    }
}
