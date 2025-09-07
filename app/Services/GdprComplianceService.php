<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GdprComplianceService
{
    /**
     * Record GDPR consent for a lead
     */
    public function recordConsent(Lead $lead, array $consentData): void
    {
        $gdprData = [
            'consent_given' => $consentData['gdpr_consent'] ?? false,
            'marketing_consent' => $consentData['marketing_consent'] ?? false,
            'consent_timestamp' => now()->toISOString(),
            'consent_ip' => request()->ip(),
            'consent_user_agent' => request()->userAgent(),
            'consent_method' => $consentData['consent_method'] ?? 'form_submission',
            'data_processing_purposes' => [
                'lead_management',
                'customer_service',
                'service_delivery'
            ],
            'retention_period' => '7 years',
            'legal_basis' => $consentData['legal_basis'] ?? 'consent',
            'data_subject_rights' => [
                'access',
                'rectification',
                'erasure',
                'portability',
                'restriction',
                'objection'
            ]
        ];

        // Add marketing purposes if consent given
        if ($consentData['marketing_consent'] ?? false) {
            $gdprData['data_processing_purposes'][] = 'marketing_communications';
            $gdprData['data_processing_purposes'][] = 'analytics';
            $gdprData['marketing_consent_timestamp'] = now()->toISOString();
        }

        // Store GDPR data in lead's behavioral data
        $behavioralData = $lead->behavioral_data ?? [];
        $behavioralData['gdpr_compliance'] = $gdprData;
        
        $lead->update(['behavioral_data' => $behavioralData]);

        // Log consent activity
        $lead->addActivity('gdpr_consent_recorded', 'GDPR consent recorded', null, $gdprData);

        Log::info('GDPR consent recorded', [
            'lead_id' => $lead->id,
            'consent_given' => $gdprData['consent_given'],
            'marketing_consent' => $gdprData['marketing_consent']
        ]);
    }

    /**
     * Handle data subject access request
     */
    public function handleAccessRequest(string $email): array
    {
        try {
            // Find all data for the email
            $leads = Lead::where('email', $email)->get();
            $users = User::where('email', $email)->get();

            $personalData = [
                'request_timestamp' => now()->toISOString(),
                'email' => $email,
                'leads' => [],
                'users' => [],
                'activities' => []
            ];

            // Collect lead data
            foreach ($leads as $lead) {
                $leadData = $lead->toArray();
                
                // Include activities
                $activities = $lead->activities()->get()->toArray();
                $leadData['activities'] = $activities;
                
                $personalData['leads'][] = $leadData;
            }

            // Collect user data
            foreach ($users as $user) {
                $userData = $user->toArray();
                unset($userData['password']); // Never include passwords
                $personalData['users'][] = $userData;
            }

            // Generate export file
            $filename = 'gdpr_export_' . md5($email) . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            Storage::disk('local')->put('gdpr_exports/' . $filename, json_encode($personalData, JSON_PRETTY_PRINT));

            Log::info('GDPR access request processed', [
                'email' => $email,
                'leads_found' => count($leads),
                'users_found' => count($users),
                'export_file' => $filename
            ]);

            return [
                'success' => true,
                'data' => $personalData,
                'export_file' => $filename,
                'leads_found' => count($leads),
                'users_found' => count($users)
            ];

        } catch (\Exception $e) {
            Log::error('GDPR access request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle data subject erasure request (right to be forgotten)
     */
    public function handleErasureRequest(string $email, array $options = []): array
    {
        try {
            $anonymizeOnly = $options['anonymize_only'] ?? false;
            $keepAnalytics = $options['keep_analytics'] ?? false;

            $leads = Lead::where('email', $email)->get();
            $users = User::where('email', $email)->get();

            $results = [
                'leads_processed' => 0,
                'users_processed' => 0,
                'activities_processed' => 0
            ];

            // Process leads
            foreach ($leads as $lead) {
                if ($anonymizeOnly) {
                    $this->anonymizeLead($lead);
                } else {
                    // Check if lead can be deleted (no active business relationship)
                    if ($this->canDeleteLead($lead)) {
                        $lead->delete();
                    } else {
                        $this->anonymizeLead($lead);
                    }
                }
                $results['leads_processed']++;
            }

            // Process users (usually anonymize rather than delete)
            foreach ($users as $user) {
                $this->anonymizeUser($user);
                $results['users_processed']++;
            }

            Log::info('GDPR erasure request processed', [
                'email' => $email,
                'anonymize_only' => $anonymizeOnly,
                'results' => $results
            ]);

            return [
                'success' => true,
                'results' => $results,
                'message' => 'Erasure request processed successfully'
            ];

        } catch (\Exception $e) {
            Log::error('GDPR erasure request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle data portability request
     */
    public function handlePortabilityRequest(string $email): array
    {
        try {
            $accessData = $this->handleAccessRequest($email);
            
            if (!$accessData['success']) {
                return $accessData;
            }

            // Create portable format (CSV for structured data)
            $portableData = $this->convertToPortableFormat($accessData['data']);
            
            $filename = 'gdpr_portable_' . md5($email) . '_' . now()->format('Y-m-d_H-i-s') . '.zip';
            $this->createPortableExport($portableData, $filename);

            return [
                'success' => true,
                'export_file' => $filename,
                'message' => 'Portable data export created'
            ];

        } catch (\Exception $e) {
            Log::error('GDPR portability request failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Withdraw consent for marketing communications
     */
    public function withdrawMarketingConsent(string $email): array
    {
        try {
            $leads = Lead::where('email', $email)->get();
            $processed = 0;

            foreach ($leads as $lead) {
                $behavioralData = $lead->behavioral_data ?? [];
                
                if (isset($behavioralData['gdpr_compliance'])) {
                    $behavioralData['gdpr_compliance']['marketing_consent'] = false;
                    $behavioralData['gdpr_compliance']['marketing_consent_withdrawn_at'] = now()->toISOString();
                    
                    $lead->update(['behavioral_data' => $behavioralData]);
                    
                    $lead->addActivity('marketing_consent_withdrawn', 'Marketing consent withdrawn', null, [
                        'withdrawn_at' => now()->toISOString(),
                        'method' => 'api_request'
                    ]);
                    
                    $processed++;
                }
            }

            Log::info('Marketing consent withdrawn', [
                'email' => $email,
                'leads_processed' => $processed
            ]);

            return [
                'success' => true,
                'leads_processed' => $processed,
                'message' => 'Marketing consent withdrawn successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Marketing consent withdrawal failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if lead can be deleted (no active business relationship)
     */
    private function canDeleteLead(Lead $lead): bool
    {
        // Don't delete if lead is in active sales process
        if (in_array($lead->status, ['qualified', 'contacted', 'demo_scheduled', 'proposal_sent'])) {
            return false;
        }

        // Don't delete if lead was created recently (within 30 days)
        if ($lead->created_at->gt(now()->subDays(30))) {
            return false;
        }

        // Don't delete if lead has CRM ID (synced to external system)
        if ($lead->crm_id) {
            return false;
        }

        return true;
    }

    /**
     * Anonymize lead data
     */
    private function anonymizeLead(Lead $lead): void
    {
        $lead->update([
            'first_name' => 'Anonymized',
            'last_name' => 'User',
            'email' => 'anonymized_' . $lead->id . '@example.com',
            'phone' => null,
            'company' => 'Anonymized Company',
            'job_title' => 'Anonymized Title',
            'notes' => 'Data anonymized per GDPR request',
            'form_data' => ['anonymized' => true],
            'utm_data' => ['anonymized' => true]
        ]);

        $lead->addActivity('gdpr_anonymized', 'Lead data anonymized per GDPR request', null, [
            'anonymized_at' => now()->toISOString(),
            'original_id' => $lead->id
        ]);
    }

    /**
     * Anonymize user data
     */
    private function anonymizeUser(User $user): void
    {
        $user->update([
            'name' => 'Anonymized User',
            'email' => 'anonymized_' . $user->id . '@example.com',
            'phone' => null,
            'bio' => 'Data anonymized per GDPR request'
        ]);
    }

    /**
     * Convert data to portable format
     */
    private function convertToPortableFormat(array $data): array
    {
        // Convert to CSV-friendly format
        $portable = [
            'leads.csv' => [],
            'activities.csv' => [],
            'users.csv' => []
        ];

        // Process leads
        foreach ($data['leads'] as $lead) {
            $activities = $lead['activities'] ?? [];
            unset($lead['activities']);
            $portable['leads.csv'][] = $lead;

            // Process activities
            foreach ($activities as $activity) {
                $activity['lead_id'] = $lead['id'];
                $portable['activities.csv'][] = $activity;
            }
        }

        // Process users
        foreach ($data['users'] as $user) {
            $portable['users.csv'][] = $user;
        }

        return $portable;
    }

    /**
     * Create portable export file
     */
    private function createPortableExport(array $portableData, string $filename): void
    {
        // In a real implementation, create a ZIP file with CSV files
        // For now, just store as JSON
        Storage::disk('local')->put('gdpr_exports/' . $filename, json_encode($portableData, JSON_PRETTY_PRINT));
    }

    /**
     * Check data retention compliance
     */
    public function checkRetentionCompliance(): array
    {
        $retentionPeriod = 7; // years
        $cutoffDate = now()->subYears($retentionPeriod);

        $expiredLeads = Lead::where('created_at', '<', $cutoffDate)
            ->whereNotIn('status', ['closed_won']) // Keep won deals for business records
            ->get();

        $results = [
            'expired_leads_count' => $expiredLeads->count(),
            'expired_leads' => $expiredLeads->pluck('id')->toArray(),
            'cutoff_date' => $cutoffDate->toISOString(),
            'retention_period_years' => $retentionPeriod
        ];

        Log::info('GDPR retention compliance check', $results);

        return $results;
    }

    /**
     * Clean up expired data
     */
    public function cleanupExpiredData(): array
    {
        $complianceCheck = $this->checkRetentionCompliance();
        $cleaned = 0;

        foreach ($complianceCheck['expired_leads'] as $leadId) {
            $lead = Lead::find($leadId);
            if ($lead) {
                $this->anonymizeLead($lead);
                $cleaned++;
            }
        }

        Log::info('GDPR expired data cleanup completed', [
            'leads_cleaned' => $cleaned
        ]);

        return [
            'success' => true,
            'leads_cleaned' => $cleaned,
            'message' => 'Expired data cleanup completed'
        ];
    }
}