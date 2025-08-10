<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingPageSubmission;
use App\Models\LandingPageAnalytics;
use App\Models\Lead;
use App\Services\LeadManagementService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class LandingPageService
{
    public function __construct(
        private LeadManagementService $leadService
    ) {}

    /**
     * Create a new landing page
     */
    public function createLandingPage(array $data): LandingPage
    {
        return DB::transaction(function () use ($data) {
            $landingPage = LandingPage::create([
                'name' => $data['name'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'target_audience' => $data['target_audience'],
                'campaign_type' => $data['campaign_type'],
                'campaign_name' => $data['campaign_name'] ?? null,
                'content' => $data['content'] ?? [],
                'settings' => $data['settings'] ?? $this->getDefaultSettings(),
                'form_config' => $data['form_config'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'created_by' => auth()->id(),
                'status' => 'draft',
            ]);

            return $landingPage;
        });
    }

    /**
     * Handle form submission and create lead
     */
    public function handleFormSubmission(LandingPage $landingPage, array $formData, Request $request): LandingPageSubmission
    {
        return DB::transaction(function () use ($landingPage, $formData, $request) {
            // Create submission record
            $submission = LandingPageSubmission::create([
                'landing_page_id' => $landingPage->id,
                'form_name' => $formData['form_name'] ?? 'default',
                'form_data' => $formData,
                'utm_data' => $this->extractUtmData($request),
                'session_data' => $this->extractSessionData($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'status' => 'new',
            ]);

            // Create lead in CRM
            $lead = $this->createLeadFromSubmission($submission, $landingPage);
            
            if ($lead) {
                $submission->update(['lead_id' => $lead->id]);
                $submission->markAsProcessed();
            }

            return $submission;
        });
    }

    /**
     * Create lead from form submission
     */
    private function createLeadFromSubmission(LandingPageSubmission $submission, LandingPage $landingPage): ?Lead
    {
        $formData = $submission->form_data;
        
        $leadData = [
            'first_name' => $formData['first_name'] ?? $formData['name'] ?? '',
            'last_name' => $formData['last_name'] ?? '',
            'email' => $formData['email'] ?? '',
            'phone' => $formData['phone'] ?? null,
            'company' => $formData['company'] ?? null,
            'job_title' => $formData['job_title'] ?? null,
            'lead_type' => $this->mapAudienceToLeadType($landingPage->target_audience),
            'source' => 'landing_page',
            'utm_data' => $submission->utm_data,
            'form_data' => array_merge($formData, [
                'landing_page_id' => $landingPage->id,
                'landing_page_name' => $landingPage->name,
                'campaign_type' => $landingPage->campaign_type,
                'campaign_name' => $landingPage->campaign_name,
            ]),
        ];

        if (empty($leadData['email'])) {
            return null;
        }

        try {
            return $this->leadService->createLead($leadData);
        } catch (\Exception $e) {
            \Log::error('Failed to create lead from landing page submission', [
                'submission_id' => $submission->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get default settings
     */
    private function getDefaultSettings(): array
    {
        return [
            'seo' => [
                'meta_description' => '',
                'meta_keywords' => '',
            ],
            'tracking' => [
                'google_analytics' => '',
                'facebook_pixel' => '',
            ],
            'design' => [
                'theme' => 'default',
                'custom_css' => '',
            ],
        ];
    }

    /**
     * Extract UTM data from request
     */
    private function extractUtmData(Request $request): array
    {
        return [
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
        ];
    }

    /**
     * Extract session data
     */
    private function extractSessionData(Request $request): array
    {
        return [
            'session_id' => $request->session()->getId(),
            'previous_url' => $request->session()->previousUrl(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];
    }

    /**
     * Map audience to lead type
     */
    private function mapAudienceToLeadType(string $audience): string
    {
        return match ($audience) {
            'institution' => 'institutional',
            'employer', 'partner' => 'enterprise',
            'alumni' => 'individual',
            default => 'individual',
        };
    }
}