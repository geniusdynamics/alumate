<?php

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Services\CrmIntegrationService;
use App\Services\GdprComplianceService;
use App\Jobs\SyncLeadToCrm;
use App\Jobs\ProcessCrmWebhook;
use App\Jobs\RetryFailedCrmSubmission;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->crmService = app(CrmIntegrationService::class);
    $this->gdprService = app(GdprComplianceService::class);
});

describe('CRM Integration Service', function () {
    it('can process form submission and create lead', function () {
        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'job_title' => 'Software Engineer',
            'utm_source' => 'google-ads',
            'utm_campaign' => 'test-campaign'
        ];

        $result = $this->crmService->processFormSubmission(
            $formData,
            'individual-signup',
            ['enabled' => false] // Disable CRM for this test
        );

        expect($result['success'])->toBeTrue();
        expect($result['lead_id'])->toBeInt();
        expect($result['lead_score'])->toBeGreaterThan(0);

        $lead = Lead::find($result['lead_id']);
        expect($lead->first_name)->toBe('John');
        expect($lead->last_name)->toBe('Doe');
        expect($lead->email)->toBe('john.doe@example.com');
        expect($lead->lead_type)->toBe('individual');
        expect($lead->source)->toBe('form_submission');
    });

    it('calculates lead score correctly', function () {
        $formData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@company.com',
            'phone' => '+1234567890',
            'company' => 'Enterprise Corp',
            'job_title' => 'CTO',
            'decision_role' => 'decision_maker',
            'budget_range' => '$50,000-$100,000',
            'implementation_timeline' => 'immediate',
            'utm_source' => 'linkedin-ads'
        ];

        $score = $this->crmService->calculateLeadScore($formData, 'institution-demo-request');

        expect($score)->toBeGreaterThan(80); // Should be high score
        expect($score)->toBeLessThanOrEqual(100);
    });

    it('determines lead routing correctly', function () {
        $formData = [
            'email' => 'test@example.com',
            'inquiry_category' => 'sales',
            'priority_level' => 'urgent'
        ];

        $routing = $this->crmService->determineLeadRouting($formData, 'contact-general', 85);

        expect($routing['priority'])->toBe('urgent');
        expect($routing['assigned_to'])->toBeInt();
    });

    it('queues CRM sync job when enabled', function () {
        Queue::fake();

        $lead = Lead::factory()->create();
        $crmConfig = ['enabled' => true, 'provider' => 'hubspot'];

        $result = $this->crmService->sendLeadToCrm($lead, $crmConfig);

        expect($result['success'])->toBeTrue();
        Queue::assertPushed(SyncLeadToCrm::class);
    });

    it('processes webhook correctly', function () {
        Queue::fake();

        $payload = [
            'event_type' => 'lead.created',
            'data' => [
                'id' => 'crm_123',
                'firstname' => 'Test',
                'lastname' => 'User',
                'email' => 'test@example.com'
            ]
        ];

        $result = $this->crmService->processWebhook('hubspot', $payload);

        expect($result['success'])->toBeTrue();
        Queue::assertPushed(ProcessCrmWebhook::class);
    });
});

describe('GDPR Compliance Service', function () {
    it('records consent correctly', function () {
        $lead = Lead::factory()->create();
        
        $consentData = [
            'gdpr_consent' => true,
            'marketing_consent' => true,
            'consent_method' => 'form_submission',
            'legal_basis' => 'consent'
        ];

        $this->gdprService->recordConsent($lead, $consentData);

        $lead->refresh();
        $gdprData = $lead->behavioral_data['gdpr_compliance'];

        expect($gdprData['consent_given'])->toBeTrue();
        expect($gdprData['marketing_consent'])->toBeTrue();
        expect($gdprData['consent_timestamp'])->toBeString();
        expect($gdprData['data_processing_purposes'])->toContain('marketing_communications');
    });

    it('handles access request correctly', function () {
        $email = 'test@example.com';
        $lead = Lead::factory()->create(['email' => $email]);

        $result = $this->gdprService->handleAccessRequest($email);

        expect($result['success'])->toBeTrue();
        expect($result['leads_found'])->toBe(1);
        expect($result['data']['leads'])->toHaveCount(1);
        expect($result['export_file'])->toBeString();
    });

    it('handles erasure request correctly', function () {
        $email = 'test@example.com';
        $lead = Lead::factory()->create([
            'email' => $email,
            'status' => 'new',
            'created_at' => now()->subDays(60) // Old enough to delete
        ]);

        $result = $this->gdprService->handleErasureRequest($email, ['anonymize_only' => true]);

        expect($result['success'])->toBeTrue();
        expect($result['results']['leads_processed'])->toBe(1);

        $lead->refresh();
        expect($lead->email)->toContain('anonymized_');
        expect($lead->first_name)->toBe('Anonymized');
    });

    it('withdraws marketing consent correctly', function () {
        $email = 'test@example.com';
        $lead = Lead::factory()->create([
            'email' => $email,
            'behavioral_data' => [
                'gdpr_compliance' => [
                    'marketing_consent' => true
                ]
            ]
        ]);

        $result = $this->gdprService->withdrawMarketingConsent($email);

        expect($result['success'])->toBeTrue();
        expect($result['leads_processed'])->toBe(1);

        $lead->refresh();
        $gdprData = $lead->behavioral_data['gdpr_compliance'];
        expect($gdprData['marketing_consent'])->toBeFalse();
        expect($gdprData['marketing_consent_withdrawn_at'])->toBeString();
    });

    it('checks retention compliance correctly', function () {
        // Create old lead that should be flagged
        Lead::factory()->create([
            'created_at' => now()->subYears(8),
            'status' => 'closed_lost'
        ]);

        // Create recent lead that should not be flagged
        Lead::factory()->create([
            'created_at' => now()->subYear(),
            'status' => 'new'
        ]);

        $result = $this->gdprService->checkRetentionCompliance();

        expect($result['expired_leads_count'])->toBeGreaterThan(0);
        expect($result['retention_period_years'])->toBe(7);
    });
});

describe('CRM Webhook Controller', function () {
    it('handles HubSpot webhook correctly', function () {
        $payload = [
            'event_type' => 'contact.created',
            'data' => [
                'id' => 'hubspot_123',
                'firstname' => 'Test',
                'lastname' => 'Contact',
                'email' => 'test@example.com'
            ]
        ];

        $response = $this->postJson('/api/webhooks/crm/hubspot', $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    });

    it('handles Salesforce webhook correctly', function () {
        $payload = [
            'event_type' => 'lead.created',
            'data' => [
                'Id' => 'sf_123',
                'FirstName' => 'Test',
                'LastName' => 'Lead',
                'Email' => 'test@example.com'
            ]
        ];

        $response = $this->postJson('/api/webhooks/crm/salesforce', $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    });

    it('handles generic CRM webhook correctly', function () {
        $payload = [
            'event' => 'lead_created',
            'data' => [
                'id' => 'generic_123',
                'name' => 'Test Lead',
                'email' => 'test@example.com'
            ]
        ];

        $response = $this->postJson('/api/webhooks/crm/custom', $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    });

    it('rejects empty webhook payload', function () {
        $response = $this->postJson('/api/webhooks/crm/custom', []);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Empty payload']);
    });
});

describe('CRM Integration Jobs', function () {
    it('processes SyncLeadToCrm job correctly', function () {
        $lead = Lead::factory()->create();
        $integration = CrmIntegration::factory()->create([
            'provider' => 'hubspot',
            'is_active' => true
        ]);

        // Mock the CRM client
        $this->mock(\App\Services\CRM\HubSpotClient::class, function ($mock) {
            $mock->shouldReceive('createLead')
                ->once()
                ->andReturn(['id' => 'hubspot_123', 'success' => true]);
        });

        $job = new SyncLeadToCrm($lead, $integration);
        $job->handle();

        $lead->refresh();
        expect($lead->crm_id)->toBe('hubspot_123');
        expect($lead->synced_at)->not->toBeNull();
    });

    it('processes ProcessCrmWebhook job correctly', function () {
        $integration = CrmIntegration::factory()->create([
            'provider' => 'hubspot',
            'is_active' => true
        ]);

        $payload = [
            'event_type' => 'lead.created',
            'data' => [
                'id' => 'hubspot_456',
                'firstname' => 'Webhook',
                'lastname' => 'Test',
                'email' => 'webhook@example.com'
            ]
        ];

        $job = new ProcessCrmWebhook('hubspot', $payload);
        $job->handle();

        $lead = Lead::where('crm_id', 'hubspot_456')->first();
        expect($lead)->not->toBeNull();
        expect($lead->first_name)->toBe('Webhook');
        expect($lead->source)->toBe('crm_webhook');
    });

    it('processes RetryFailedCrmSubmission job correctly', function () {
        $lead = Lead::factory()->create(['crm_id' => null]);
        $integration = CrmIntegration::factory()->create([
            'provider' => 'hubspot',
            'is_active' => true
        ]);

        $crmConfig = ['provider' => 'hubspot'];

        // Mock successful retry
        $this->mock(\App\Services\CRM\HubSpotClient::class, function ($mock) {
            $mock->shouldReceive('createLead')
                ->once()
                ->andReturn(['id' => 'hubspot_retry_123', 'success' => true]);
        });

        $job = new RetryFailedCrmSubmission($lead, $crmConfig);
        $job->handle(app(CrmIntegrationService::class));

        // Verify the lead was synced
        $activities = $lead->activities()->where('type', 'crm_retry_success')->get();
        expect($activities)->toHaveCount(1);
    });
});

describe('Lead Scoring and Routing', function () {
    it('scores individual signup form correctly', function () {
        $formData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@company.com',
            'phone' => '+1234567890',
            'current_company' => 'Tech Corp',
            'current_job_title' => 'Senior Developer',
            'industry' => 'Technology',
            'utm_source' => 'linkedin-ads'
        ];

        $score = $this->crmService->calculateLeadScore($formData, 'individual-signup');

        expect($score)->toBeGreaterThan(70); // Should be good score
        expect($score)->toBeLessThanOrEqual(100);
    });

    it('scores institution demo request correctly', function () {
        $formData = [
            'contact_name' => 'Jane Smith',
            'email' => 'jane@university.edu',
            'phone' => '+1234567890',
            'institution_name' => 'State University',
            'decision_role' => 'decision_maker',
            'budget_range' => '$100,000+',
            'implementation_timeline' => 'immediate',
            'alumni_count' => '>50000'
        ];

        $score = $this->crmService->calculateLeadScore($formData, 'institution-demo-request');

        expect($score)->toBeGreaterThan(90); // Should be very high score
    });

    it('routes high-priority leads correctly', function () {
        $formData = [
            'email' => 'urgent@example.com',
            'priority_level' => 'urgent',
            'inquiry_category' => 'demo_request'
        ];

        $routing = $this->crmService->determineLeadRouting($formData, 'contact-general', 95);

        expect($routing['priority'])->toBe('urgent');
        expect($routing['assigned_to'])->toBeInt();
    });
});

describe('Conversion Attribution Tracking', function () {
    it('tracks conversion attribution correctly', function () {
        $formData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'utm_source' => 'google-ads',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'alumni-signup',
            'utm_term' => 'alumni network',
            'utm_content' => 'signup-form',
            'referrer' => 'https://google.com',
            'landing_page' => '/signup',
            'session_id' => 'session_123'
        ];

        $result = $this->crmService->processFormSubmission($formData, 'individual-signup');

        $lead = Lead::find($result['lead_id']);
        $attribution = $lead->behavioral_data['attribution'] ?? null;

        expect($attribution)->not->toBeNull();
        expect($attribution['utm_source'])->toBe('google-ads');
        expect($attribution['utm_campaign'])->toBe('alumni-signup');
        expect($attribution['conversion_timestamp'])->toBeString();

        // Check activity was recorded
        $activities = $lead->activities()->where('type', 'conversion_attribution')->get();
        expect($activities)->toHaveCount(1);
    });
});