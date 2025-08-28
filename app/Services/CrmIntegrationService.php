<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\CrmIntegration;
use App\Jobs\ProcessCrmWebhook;
use App\Jobs\SyncLeadToCrm;
use App\Jobs\RetryFailedCrmSubmission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CrmIntegrationService
{
    /**
     * Process form submission and create lead with CRM integration
     */
    public function processFormSubmission(array $formData, string $formType, array $crmConfig = []): array
    {
        try {
            // Create lead from form data
            $lead = $this->createLeadFromFormData($formData, $formType);
            
            // Calculate lead score
            $leadScore = $this->calculateLeadScore($formData, $formType);
            $lead->update(['score' => $leadScore]);
            
            // Determine lead routing
            $routing = $this->determineLeadRouting($formData, $formType, $leadScore);
            $lead->update([
                'assigned_to' => $routing['assigned_to'],
                'priority' => $routing['priority']
            ]);
            
            // Process CRM integration if enabled
            $crmResult = null;
            if (!empty($crmConfig) && ($crmConfig['enabled'] ?? false)) {
                $crmResult = $this->sendLeadToCrm($lead, $crmConfig);
            }
            
            // Track conversion attribution
            $this->trackConversionAttribution($lead, $formData);
            
            // Handle GDPR compliance
            $this->handleGdprCompliance($lead, $formData);
            
            return [
                'success' => true,
                'lead_id' => $lead->id,
                'lead_score' => $leadScore,
                'routing' => $routing,
                'crm_result' => $crmResult
            ];
            
        } catch (\Exception $e) {
            Log::error('CRM integration form processing failed', [
                'form_type' => $formType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create lead from form data
     */
    private function createLeadFromFormData(array $formData, string $formType): Lead
    {
        // Extract standard fields
        $leadData = [
            'first_name' => $formData['first_name'] ?? $formData['name'] ?? '',
            'last_name' => $formData['last_name'] ?? '',
            'email' => $formData['email'] ?? '',
            'phone' => $formData['phone'] ?? '',
            'company' => $formData['company'] ?? $formData['organization'] ?? $formData['institution_name'] ?? '',
            'job_title' => $formData['job_title'] ?? $formData['title'] ?? $formData['contact_title'] ?? '',
            'lead_type' => $this->mapFormTypeToLeadType($formType),
            'source' => 'form_submission',
            'status' => 'new',
            'form_data' => $formData,
            'utm_data' => $this->extractUtmData($formData)
        ];
        
        // Handle name splitting if only full name provided
        if (empty($leadData['first_name']) && !empty($formData['name'])) {
            $nameParts = explode(' ', $formData['name'], 2);
            $leadData['first_name'] = $nameParts[0];
            $leadData['last_name'] = $nameParts[1] ?? '';
        }
        
        return Lead::create($leadData);
    }
    
    /**
     * Calculate lead score based on form data and type
     */
    public function calculateLeadScore(array $formData, string $formType): int
    {
        $baseScores = [
            'individual-signup' => 60,
            'institution-demo-request' => 85,
            'contact-general' => 30,
            'newsletter-signup' => 25,
            'event-registration' => 40
        ];
        
        $score = $baseScores[$formType] ?? 50;
        
        // Completeness scoring
        $totalFields = count($formData);
        $completedFields = count(array_filter($formData, function($value) {
            return !empty($value) && $value !== null;
        }));
        
        $completenessScore = ($completedFields / max($totalFields, 1)) * 20;
        $score += $completenessScore;
        
        // Form-specific scoring
        switch ($formType) {
            case 'institution-demo-request':
                if (($formData['decision_role'] ?? '') === 'decision_maker') {
                    $score += 25;
                }
                if (!empty($formData['budget_range']) && !str_contains($formData['budget_range'], '<')) {
                    $score += 20;
                }
                if (in_array($formData['implementation_timeline'] ?? '', ['immediate', '1-3months'])) {
                    $score += 15;
                }
                if (!empty($formData['alumni_count'])) {
                    $alumniCount = $formData['alumni_count'];
                    if (str_contains($alumniCount, '>50000') || str_contains($alumniCount, '>100000')) {
                        $score += 20;
                    }
                }
                break;
                
            case 'individual-signup':
                if (!empty($formData['current_company'])) {
                    $score += 15;
                }
                if (!empty($formData['current_job_title'])) {
                    $score += 10;
                }
                if (!empty($formData['industry'])) {
                    $score += 5;
                }
                break;
                
            case 'contact-general':
                if (($formData['priority_level'] ?? '') === 'urgent') {
                    $score += 20;
                }
                if (in_array($formData['inquiry_category'] ?? '', ['sales', 'demo_request'])) {
                    $score += 15;
                }
                break;
        }
        
        // UTM source scoring
        $utmSource = $formData['utm_source'] ?? '';
        $highValueSources = ['google-ads', 'linkedin-ads', 'partner-referral'];
        if (in_array($utmSource, $highValueSources)) {
            $score += 10;
        }
        
        return min(max($score, 0), 100);
    }
    
    /**
     * Determine lead routing based on form data and score
     */
    public function determineLeadRouting(array $formData, string $formType, int $leadScore): array
    {
        $routing = [
            'assigned_to' => null,
            'priority' => 'medium'
        ];
        
        // Priority based on score
        if ($leadScore >= 80) {
            $routing['priority'] = 'high';
        } elseif ($leadScore >= 60) {
            $routing['priority'] = 'medium';
        } else {
            $routing['priority'] = 'low';
        }
        
        // Form-specific routing
        switch ($formType) {
            case 'institution-demo-request':
                $routing['assigned_to'] = $this->getEnterpriseTeamMember($formData);
                if ($leadScore >= 85) {
                    $routing['priority'] = 'urgent';
                }
                break;
                
            case 'individual-signup':
                $routing['assigned_to'] = $this->getIndividualTeamMember($formData);
                break;
                
            case 'contact-general':
                $category = $formData['inquiry_category'] ?? 'general';
                $routing['assigned_to'] = $this->getTeamMemberByCategory($category);
                
                if (($formData['priority_level'] ?? '') === 'urgent') {
                    $routing['priority'] = 'urgent';
                }
                break;
        }
        
        return $routing;
    }
    
    /**
     * Send lead to CRM system
     */
    public function sendLeadToCrm(Lead $lead, array $crmConfig): array
    {
        try {
            // Get CRM integration configuration
            $integration = $this->getCrmIntegration($crmConfig['provider'] ?? 'hubspot');
            
            if (!$integration || !$integration->is_active) {
                throw new \Exception('CRM integration not available or inactive');
            }
            
            // Queue the CRM sync job for better performance
            SyncLeadToCrm::dispatch($lead, $integration, $crmConfig)
                ->onQueue('crm-integration');
            
            return [
                'success' => true,
                'message' => 'Lead queued for CRM sync',
                'provider' => $integration->provider
            ];
            
        } catch (\Exception $e) {
            Log::error('CRM lead sync failed', [
                'lead_id' => $lead->id,
                'provider' => $crmConfig['provider'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Queue retry job
            RetryFailedCrmSubmission::dispatch($lead, $crmConfig)
                ->delay(now()->addMinutes(5))
                ->onQueue('crm-retry');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'retry_scheduled' => true
            ];
        }
    }
    
    /**
     * Process webhook from CRM system
     */
    public function processWebhook(string $provider, array $payload): array
    {
        try {
            // Validate webhook signature
            if (!$this->validateWebhookSignature($provider, $payload)) {
                throw new \Exception('Invalid webhook signature');
            }
            
            // Queue webhook processing
            ProcessCrmWebhook::dispatch($provider, $payload)
                ->onQueue('webhook-processing');
            
            return [
                'success' => true,
                'message' => 'Webhook queued for processing'
            ];
            
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Track conversion attribution
     */
    private function trackConversionAttribution(Lead $lead, array $formData): void
    {
        $attribution = [
            'utm_source' => $formData['utm_source'] ?? null,
            'utm_medium' => $formData['utm_medium'] ?? null,
            'utm_campaign' => $formData['utm_campaign'] ?? null,
            'utm_term' => $formData['utm_term'] ?? null,
            'utm_content' => $formData['utm_content'] ?? null,
            'referrer' => $formData['referrer'] ?? null,
            'landing_page' => $formData['landing_page'] ?? null,
            'session_id' => $formData['session_id'] ?? null,
            'conversion_timestamp' => now()->toISOString()
        ];
        
        $lead->addActivity('conversion_attribution', 'Conversion tracked', null, $attribution);
        
        // Update behavioral data
        $behavioralData = $lead->behavioral_data ?? [];
        $behavioralData['attribution'] = $attribution;
        $lead->update(['behavioral_data' => $behavioralData]);
    }
    
    /**
     * Handle GDPR compliance
     */
    private function handleGdprCompliance(Lead $lead, array $formData): void
    {
        $gdprData = [
            'consent_given' => $formData['gdpr_consent'] ?? false,
            'consent_timestamp' => now()->toISOString(),
            'consent_ip' => request()->ip(),
            'consent_user_agent' => request()->userAgent(),
            'data_processing_purposes' => [
                'lead_management',
                'marketing_communications',
                'service_delivery'
            ],
            'retention_period' => '7 years',
            'data_subject_rights' => [
                'access',
                'rectification',
                'erasure',
                'portability',
                'restriction',
                'objection'
            ]
        ];
        
        if ($formData['marketing_consent'] ?? false) {
            $gdprData['marketing_consent'] = true;
            $gdprData['marketing_consent_timestamp'] = now()->toISOString();
        }
        
        $lead->addActivity('gdpr_compliance', 'GDPR data recorded', null, $gdprData);
    }
    
    /**
     * Get CRM integration by provider
     */
    private function getCrmIntegration(string $provider): ?CrmIntegration
    {
        return CrmIntegration::where('provider', $provider)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Map form type to lead type
     */
    private function mapFormTypeToLeadType(string $formType): string
    {
        $mapping = [
            'individual-signup' => 'individual',
            'institution-demo-request' => 'institution',
            'contact-general' => 'inquiry',
            'newsletter-signup' => 'subscriber',
            'event-registration' => 'attendee'
        ];
        
        return $mapping[$formType] ?? 'general';
    }
    
    /**
     * Extract UTM data from form data
     */
    private function extractUtmData(array $formData): array
    {
        return [
            'utm_source' => $formData['utm_source'] ?? null,
            'utm_medium' => $formData['utm_medium'] ?? null,
            'utm_campaign' => $formData['utm_campaign'] ?? null,
            'utm_term' => $formData['utm_term'] ?? null,
            'utm_content' => $formData['utm_content'] ?? null
        ];
    }
    
    /**
     * Get enterprise team member for assignment
     */
    private function getEnterpriseTeamMember(array $formData): ?int
    {
        // Simple round-robin assignment for now
        // In production, this would use more sophisticated logic
        $enterpriseTeam = [1, 2, 3]; // User IDs of enterprise team
        $cacheKey = 'enterprise_assignment_index';
        
        $index = Cache::get($cacheKey, 0);
        $assignedUserId = $enterpriseTeam[$index % count($enterpriseTeam)];
        
        Cache::put($cacheKey, $index + 1, now()->addHour());
        
        return $assignedUserId;
    }
    
    /**
     * Get individual team member for assignment
     */
    private function getIndividualTeamMember(array $formData): ?int
    {
        $individualTeam = [4, 5, 6]; // User IDs of individual team
        $cacheKey = 'individual_assignment_index';
        
        $index = Cache::get($cacheKey, 0);
        $assignedUserId = $individualTeam[$index % count($individualTeam)];
        
        Cache::put($cacheKey, $index + 1, now()->addHour());
        
        return $assignedUserId;
    }
    
    /**
     * Get team member by inquiry category
     */
    private function getTeamMemberByCategory(string $category): ?int
    {
        $categoryAssignments = [
            'technical_support' => [7, 8],
            'sales' => [1, 2],
            'demo_request' => [1, 2, 3],
            'partnership' => [9],
            'media' => [10],
            'privacy' => [11],
            'bug_report' => [7, 8],
            'feature_request' => [12],
            'general' => [13, 14]
        ];
        
        $team = $categoryAssignments[$category] ?? $categoryAssignments['general'];
        $cacheKey = "category_assignment_index_{$category}";
        
        $index = Cache::get($cacheKey, 0);
        $assignedUserId = $team[$index % count($team)];
        
        Cache::put($cacheKey, $index + 1, now()->addHour());
        
        return $assignedUserId;
    }
    
    /**
     * Validate webhook signature
     */
    private function validateWebhookSignature(string $provider, array $payload): bool
    {
        // Implementation would depend on the CRM provider's webhook signature method
        // For now, return true - in production, implement proper signature validation
        return true;
    }
}