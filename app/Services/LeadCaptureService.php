<?php

namespace App\Services;

use App\Mail\AdminDemoNotification;
use App\Mail\AdminTrialNotification;
use App\Mail\DemoRequestConfirmation;
use App\Mail\TrialSignupConfirmation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LeadCaptureService
{
    /**
     * Process trial signup request
     */
    public function processTrialSignup(array $data): array
    {
        try {
            // Validate and sanitize data
            $cleanData = $this->sanitizeTrialData($data);

            // Store lead in database/cache for follow-up
            $this->storeTrialLead($cleanData);

            // Send confirmation email to user
            $this->sendTrialConfirmationEmail($cleanData);

            // Notify admin team
            $this->notifyAdminOfTrial($cleanData);

            // Track conversion
            $this->trackTrialConversion($cleanData);

            // Schedule follow-up emails
            $this->scheduleTrialFollowUp($cleanData);

            return [
                'success' => true,
                'message' => 'Trial signup processed successfully',
                'trial_id' => $cleanData['trial_id'],
                'next_steps' => $this->getTrialNextSteps($cleanData),
            ];

        } catch (\Exception $e) {
            Log::error('Trial signup processing failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new \Exception('Failed to process trial signup. Please try again.');
        }
    }

    /**
     * Process demo request
     */
    public function processDemoRequest(array $data): array
    {
        try {
            // Validate and sanitize data
            $cleanData = $this->sanitizeDemoData($data);

            // Store lead in database/cache
            $this->storeDemoLead($cleanData);

            // Send confirmation email to requester
            $this->sendDemoConfirmationEmail($cleanData);

            // Notify sales team
            $this->notifySalesTeamOfDemo($cleanData);

            // Track conversion
            $this->trackDemoConversion($cleanData);

            // Schedule follow-up
            $this->scheduleDemoFollowUp($cleanData);

            return [
                'success' => true,
                'message' => 'Demo request submitted successfully',
                'request_id' => $cleanData['request_id'],
                'next_steps' => $this->getDemoNextSteps($cleanData),
            ];

        } catch (\Exception $e) {
            Log::error('Demo request processing failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            throw new \Exception('Failed to process demo request. Please try again.');
        }
    }

    /**
     * Sanitize trial signup data
     */
    private function sanitizeTrialData(array $data): array
    {
        return [
            'trial_id' => 'trial_'.uniqid(),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'graduation_year' => $data['graduationYear'] ?? null,
            'institution' => trim($data['institution'] ?? ''),
            'current_role' => trim($data['currentRole'] ?? ''),
            'industry' => $data['industry'] ?? '',
            'referral_source' => $data['referralSource'] ?? '',
            'plan_id' => $data['planId'] ?? 'professional',
            'source' => $data['source'] ?? 'website',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
            'trial_start_date' => now(),
            'trial_end_date' => now()->addDays(14),
        ];
    }

    /**
     * Sanitize demo request data
     */
    private function sanitizeDemoData(array $data): array
    {
        return [
            'request_id' => 'demo_'.uniqid(),
            'institution_name' => trim($data['institutionName']),
            'contact_name' => trim($data['contactName']),
            'email' => strtolower(trim($data['email'])),
            'title' => trim($data['title'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'alumni_count' => $data['alumniCount'] ?? '',
            'current_solution' => $data['currentSolution'] ?? '',
            'interests' => $data['interests'] ?? [],
            'preferred_time' => $data['preferredTime'] ?? '',
            'message' => trim($data['message'] ?? ''),
            'plan_id' => $data['planId'] ?? 'enterprise',
            'source' => $data['source'] ?? 'website',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
            'status' => 'pending',
            'priority' => $this->calculateDemoPriority($data),
        ];
    }

    /**
     * Store trial lead data
     */
    private function storeTrialLead(array $data): void
    {
        // Store in cache for immediate access
        Cache::put("trial_lead_{$data['trial_id']}", $data, now()->addDays(30));

        // Store in database (you would implement this based on your schema)
        // TrialLead::create($data);

        // Add to email marketing list
        $this->addToEmailList($data['email'], 'trial_users', [
            'name' => $data['name'],
            'trial_start' => $data['trial_start_date'],
            'plan' => $data['plan_id'],
        ]);
    }

    /**
     * Store demo lead data
     */
    private function storeDemoLead(array $data): void
    {
        // Store in cache for immediate access
        Cache::put("demo_lead_{$data['request_id']}", $data, now()->addDays(30));

        // Store in database
        // DemoRequest::create($data);

        // Add to CRM system
        $this->addToCRM($data);
    }

    /**
     * Send trial confirmation email
     */
    private function sendTrialConfirmationEmail(array $data): void
    {
        try {
            Mail::to($data['email'])->send(new TrialSignupConfirmation($data));
        } catch (\Exception $e) {
            Log::error('Failed to send trial confirmation email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send demo confirmation email
     */
    private function sendDemoConfirmationEmail(array $data): void
    {
        try {
            Mail::to($data['email'])->send(new DemoRequestConfirmation($data));
        } catch (\Exception $e) {
            Log::error('Failed to send demo confirmation email', [
                'email' => $data['email'],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admin team of new trial
     */
    private function notifyAdminOfTrial(array $data): void
    {
        try {
            $adminEmails = config('app.admin_emails', ['admin@example.com']);

            foreach ($adminEmails as $email) {
                Mail::to($email)->send(new AdminTrialNotification($data));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify admin of trial signup', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify sales team of demo request
     */
    private function notifySalesTeamOfDemo(array $data): void
    {
        try {
            $salesEmails = config('app.sales_emails', ['sales@example.com']);

            foreach ($salesEmails as $email) {
                Mail::to($email)->send(new AdminDemoNotification($data));
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify sales team of demo request', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Track trial conversion
     */
    private function trackTrialConversion(array $data): void
    {
        // Track in analytics
        $this->trackEvent('trial_signup', [
            'plan_id' => $data['plan_id'],
            'source' => $data['source'],
            'industry' => $data['industry'],
            'referral_source' => $data['referral_source'],
        ]);

        // Update conversion metrics
        $this->updateConversionMetrics('trial', $data);
    }

    /**
     * Track demo conversion
     */
    private function trackDemoConversion(array $data): void
    {
        // Track in analytics
        $this->trackEvent('demo_request', [
            'plan_id' => $data['plan_id'],
            'source' => $data['source'],
            'alumni_count' => $data['alumni_count'],
            'current_solution' => $data['current_solution'],
            'interests' => $data['interests'],
        ]);

        // Update conversion metrics
        $this->updateConversionMetrics('demo', $data);
    }

    /**
     * Schedule trial follow-up emails
     */
    private function scheduleTrialFollowUp(array $data): void
    {
        // Schedule welcome email sequence
        $this->scheduleEmail($data['email'], 'trial_welcome', now()->addHours(1));
        $this->scheduleEmail($data['email'], 'trial_day_3', now()->addDays(3));
        $this->scheduleEmail($data['email'], 'trial_day_7', now()->addDays(7));
        $this->scheduleEmail($data['email'], 'trial_day_12', now()->addDays(12));
        $this->scheduleEmail($data['email'], 'trial_expiring', now()->addDays(13));
    }

    /**
     * Schedule demo follow-up
     */
    private function scheduleDemoFollowUp(array $data): void
    {
        // Schedule immediate follow-up
        $this->scheduleEmail($data['email'], 'demo_immediate', now()->addHours(2));

        // Schedule sales follow-up if no response
        $this->scheduleEmail($data['email'], 'demo_follow_up', now()->addDays(2));
        $this->scheduleEmail($data['email'], 'demo_final_follow_up', now()->addWeek());
    }

    /**
     * Get trial next steps
     */
    private function getTrialNextSteps(array $data): array
    {
        return [
            'login_url' => route('login'),
            'setup_guide' => route('trial.setup'),
            'support_email' => 'support@example.com',
            'trial_duration' => '14 days',
            'trial_end_date' => $data['trial_end_date']->format('M j, Y'),
        ];
    }

    /**
     * Get demo next steps
     */
    private function getDemoNextSteps(array $data): array
    {
        return [
            'contact_timeline' => '24 hours',
            'demo_duration' => '30-45 minutes',
            'preparation_guide' => route('demo.preparation'),
            'contact_email' => 'sales@example.com',
            'contact_phone' => '+1 (555) 123-4567',
        ];
    }

    /**
     * Calculate demo priority based on data
     */
    private function calculateDemoPriority(array $data): string
    {
        $score = 0;

        // Alumni count scoring
        $alumniCountScores = [
            'over_50000' => 5,
            '25000_50000' => 4,
            '10000_25000' => 3,
            '5000_10000' => 2,
            '1000_5000' => 1,
            'under_1000' => 0,
        ];

        $score += $alumniCountScores[$data['alumniCount'] ?? ''] ?? 0;

        // Interest scoring
        $highValueInterests = ['analytics', 'integrations', 'fundraising'];
        $interestCount = count(array_intersect($data['interests'] ?? [], $highValueInterests));
        $score += $interestCount;

        // Current solution scoring
        if (in_array($data['currentSolution'] ?? '', ['none', 'spreadsheets'])) {
            $score += 2;
        }

        // Determine priority
        if ($score >= 7) {
            return 'high';
        }
        if ($score >= 4) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Add email to marketing list
     */
    private function addToEmailList(string $email, string $list, array $attributes = []): void
    {
        // Implement email marketing service integration
        // Example: Mailchimp, SendGrid, etc.
        Log::info('Added email to marketing list', [
            'email' => $email,
            'list' => $list,
            'attributes' => $attributes,
        ]);
    }

    /**
     * Add lead to CRM
     */
    private function addToCRM(array $data): void
    {
        // Implement CRM integration
        // Example: Salesforce, HubSpot, etc.
        Log::info('Added lead to CRM', [
            'request_id' => $data['request_id'],
            'institution' => $data['institution_name'],
        ]);
    }

    /**
     * Track analytics event
     */
    private function trackEvent(string $event, array $properties): void
    {
        // Implement analytics tracking
        // Example: Google Analytics, Mixpanel, etc.
        Log::info('Analytics event tracked', [
            'event' => $event,
            'properties' => $properties,
        ]);
    }

    /**
     * Update conversion metrics
     */
    private function updateConversionMetrics(string $type, array $data): void
    {
        $key = "conversion_metrics_{$type}_".now()->format('Y-m-d');
        $metrics = Cache::get($key, ['count' => 0, 'sources' => []]);

        $metrics['count']++;
        $metrics['sources'][$data['source']] = ($metrics['sources'][$data['source']] ?? 0) + 1;

        Cache::put($key, $metrics, now()->addDays(7));
    }

    /**
     * Schedule email
     */
    private function scheduleEmail(string $email, string $template, $when): void
    {
        // Implement email scheduling
        // Example: Laravel Queue, external service, etc.
        Log::info('Email scheduled', [
            'email' => $email,
            'template' => $template,
            'when' => $when,
        ]);
    }

    /**
     * Get lead statistics
     */
    public function getLeadStatistics(): array
    {
        $today = now()->format('Y-m-d');
        $trialMetrics = Cache::get("conversion_metrics_trial_{$today}", ['count' => 0, 'sources' => []]);
        $demoMetrics = Cache::get("conversion_metrics_demo_{$today}", ['count' => 0, 'sources' => []]);

        return [
            'today' => [
                'trials' => $trialMetrics['count'],
                'demos' => $demoMetrics['count'],
                'total' => $trialMetrics['count'] + $demoMetrics['count'],
            ],
            'sources' => [
                'trial' => $trialMetrics['sources'],
                'demo' => $demoMetrics['sources'],
            ],
            'last_updated' => now()->toISOString(),
        ];
    }
}
