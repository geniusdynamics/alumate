<?php

namespace App\Jobs;

use App\Models\EmailAutomationRule;
use App\Models\EmailCampaign;
use App\Services\EmailMarketingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutomationRuleJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public EmailAutomationRule $rule,
        public array $eventData
    ) {}

    public function handle(EmailMarketingService $emailMarketingService): void
    {
        try {
            Log::info('Processing automation rule', [
                'rule_id' => $this->rule->id,
                'rule_name' => $this->rule->name,
                'event_data' => $this->eventData,
            ]);

            // Create a campaign based on the automation rule
            $campaign = $emailMarketingService->createCampaign([
                'name' => $this->rule->name . ' - ' . now()->format('Y-m-d H:i'),
                'description' => 'Automated campaign triggered by: ' . $this->rule->trigger_event,
                'subject' => $this->generateSubject(),
                'content' => $this->rule->template->html_content,
                'type' => 'engagement',
                'provider' => 'internal',
                'audience_criteria' => $this->rule->audience_criteria,
                'personalization_rules' => $this->getPersonalizationRules(),
            ]);

            // Send the campaign immediately
            $success = $emailMarketingService->sendCampaign($campaign);

            if ($success) {
                $this->rule->incrementSentCount();
                
                Log::info('Automation rule processed successfully', [
                    'rule_id' => $this->rule->id,
                    'campaign_id' => $campaign->id,
                    'recipients' => $campaign->total_recipients,
                ]);
            } else {
                Log::error('Automation rule campaign send failed', [
                    'rule_id' => $this->rule->id,
                    'campaign_id' => $campaign->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Automation rule processing failed', [
                'rule_id' => $this->rule->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function generateSubject(): string
    {
        $subjects = [
            'user_registered' => 'Welcome to the Alumni Network!',
            'post_created' => 'New Activity in Your Alumni Network',
            'event_created' => 'New Event: Don\'t Miss Out!',
            'career_milestone' => 'Celebrating Alumni Success',
            'donation_received' => 'Thank You for Your Support',
        ];

        return $subjects[$this->rule->trigger_event] ?? 'Alumni Network Update';
    }

    protected function getPersonalizationRules(): array
    {
        $rules = [];

        switch ($this->rule->trigger_event) {
            case 'user_registered':
                $rules[] = ['type' => 'upcoming_events'];
                break;
            
            case 'post_created':
                $rules[] = ['type' => 'recent_posts'];
                break;
            
            case 'career_milestone':
                $rules[] = ['type' => 'career_milestone'];
                break;
        }

        return $rules;
    }
}
