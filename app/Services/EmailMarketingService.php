<?php

namespace App\Services;

use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\EmailAutomationRule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\SendEmailCampaignJob;
use App\Jobs\ProcessAutomationRuleJob;

class EmailMarketingService
{
    protected array $providers = [
        'mailchimp' => MailchimpProvider::class,
        'constant_contact' => ConstantContactProvider::class,
        'mautic' => MauticProvider::class,
        'internal' => InternalProvider::class,
    ];

    public function createCampaign(array $data): EmailCampaign
    {
        $campaign = EmailCampaign::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'subject' => $data['subject'],
            'content' => $data['content'],
            'template_data' => $data['template_data'] ?? null,
            'type' => $data['type'],
            'status' => 'draft',
            'provider' => $data['provider'] ?? 'internal',
            'audience_criteria' => $data['audience_criteria'] ?? null,
            'personalization_rules' => $data['personalization_rules'] ?? null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'is_ab_test' => $data['is_ab_test'] ?? false,
            'ab_test_variant' => $data['ab_test_variant'] ?? null,
            'ab_test_parent_id' => $data['ab_test_parent_id'] ?? null,
            'created_by' => auth()->id(),
            'tenant_id' => tenant()->id,
        ]);

        // Create provider campaign if using external service
        if ($campaign->provider !== 'internal') {
            $this->createProviderCampaign($campaign);
        }

        return $campaign;
    }

    public function scheduleCampaign(EmailCampaign $campaign, \DateTime $scheduledAt): bool
    {
        $campaign->update([
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
        ]);

        // Queue the campaign for sending
        SendEmailCampaignJob::dispatch($campaign)->delay($scheduledAt);

        return true;
    }

    public function sendCampaign(EmailCampaign $campaign): bool
    {
        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            throw new \Exception('Campaign cannot be sent in current status: ' . $campaign->status);
        }

        $campaign->update(['status' => 'sending']);

        // Get recipients based on audience criteria
        $recipients = $this->getRecipients($campaign);
        
        $campaign->update(['total_recipients' => $recipients->count()]);

        // Create recipient records
        $this->createRecipientRecords($campaign, $recipients);

        // Send via provider
        $provider = $this->getProvider($campaign->provider);
        $result = $provider->sendCampaign($campaign, $recipients);

        if ($result['success']) {
            $campaign->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } else {
            $campaign->update(['status' => 'draft']);
            Log::error('Failed to send email campaign', [
                'campaign_id' => $campaign->id,
                'error' => $result['error'],
            ]);
        }

        return $result['success'];
    }

    public function createAbTest(EmailCampaign $parentCampaign, array $variantData): EmailCampaign
    {
        $variantCampaign = $this->createCampaign(array_merge(
            $parentCampaign->toArray(),
            $variantData,
            [
                'is_ab_test' => true,
                'ab_test_variant' => 'B',
                'ab_test_parent_id' => $parentCampaign->id,
                'name' => $parentCampaign->name . ' (Variant B)',
            ]
        ));

        $parentCampaign->update([
            'is_ab_test' => true,
            'ab_test_variant' => 'A',
        ]);

        return $variantCampaign;
    }

    public function getRecipients(EmailCampaign $campaign): Collection
    {
        $query = User::query()->where('tenant_id', tenant()->id);

        if ($campaign->audience_criteria) {
            $criteria = $campaign->audience_criteria;

            // Filter by graduation years
            if (isset($criteria['graduation_years'])) {
                $query->whereHas('educations', function ($q) use ($criteria) {
                    $q->whereIn('graduation_year', $criteria['graduation_years']);
                });
            }

            // Filter by schools
            if (isset($criteria['school_ids'])) {
                $query->whereHas('educations', function ($q) use ($criteria) {
                    $q->whereIn('school_id', $criteria['school_ids']);
                });
            }

            // Filter by industries
            if (isset($criteria['industries'])) {
                $query->whereHas('careerTimeline', function ($q) use ($criteria) {
                    $q->whereIn('industry', $criteria['industries']);
                });
            }

            // Filter by engagement level
            if (isset($criteria['engagement_level'])) {
                // Add engagement filtering logic based on user activity
                $query->withCount(['posts', 'connections', 'eventAttendances'])
                      ->having('posts_count', '>=', $criteria['min_posts'] ?? 0);
            }

            // Filter by location
            if (isset($criteria['locations'])) {
                $query->whereIn('location', $criteria['locations']);
            }
        }

        return $query->get();
    }

    public function personalizeContent(string $content, User $user, array $rules = []): string
    {
        $personalizedContent = $content;

        // Basic personalization
        $personalizedContent = str_replace('{{first_name}}', $user->first_name ?? 'Alumni', $personalizedContent);
        $personalizedContent = str_replace('{{last_name}}', $user->last_name ?? '', $personalizedContent);
        $personalizedContent = str_replace('{{full_name}}', $user->name, $personalizedContent);
        $personalizedContent = str_replace('{{email}}', $user->email, $personalizedContent);

        // Advanced personalization based on rules
        foreach ($rules as $rule) {
            switch ($rule['type']) {
                case 'recent_posts':
                    $recentPosts = $user->posts()->latest()->limit(3)->get();
                    $postsHtml = $recentPosts->map(fn($post) => "<li>{$post->content}</li>")->join('');
                    $personalizedContent = str_replace('{{recent_posts}}', "<ul>{$postsHtml}</ul>", $personalizedContent);
                    break;

                case 'career_milestone':
                    $latestCareer = $user->careerTimeline()->latest()->first();
                    if ($latestCareer) {
                        $personalizedContent = str_replace('{{current_role}}', $latestCareer->title, $personalizedContent);
                        $personalizedContent = str_replace('{{current_company}}', $latestCareer->company, $personalizedContent);
                    }
                    break;

                case 'upcoming_events':
                    $upcomingEvents = $user->tenant->events()
                        ->where('start_date', '>', now())
                        ->limit(3)
                        ->get();
                    $eventsHtml = $upcomingEvents->map(fn($event) => "<li>{$event->title} - {$event->start_date->format('M j, Y')}</li>")->join('');
                    $personalizedContent = str_replace('{{upcoming_events}}', "<ul>{$eventsHtml}</ul>", $personalizedContent);
                    break;
            }
        }

        return $personalizedContent;
    }

    public function trackEngagement(EmailCampaign $campaign, User $user, string $action, array $data = []): void
    {
        $recipient = $campaign->recipients()->where('user_id', $user->id)->first();
        
        if (!$recipient) {
            return;
        }

        $updateData = ['tracking_data' => array_merge($recipient->tracking_data ?? [], $data)];

        switch ($action) {
            case 'delivered':
                $updateData['status'] = 'delivered';
                $updateData['delivered_at'] = now();
                $campaign->increment('delivered_count');
                break;

            case 'opened':
                if ($recipient->status !== 'opened') {
                    $updateData['status'] = 'opened';
                    $updateData['opened_at'] = now();
                    $campaign->increment('opened_count');
                }
                break;

            case 'clicked':
                $updateData['status'] = 'clicked';
                $updateData['clicked_at'] = now();
                $campaign->increment('clicked_count');
                break;

            case 'bounced':
                $updateData['status'] = 'bounced';
                $updateData['bounced_at'] = now();
                $campaign->increment('bounced_count');
                break;

            case 'unsubscribed':
                $updateData['status'] = 'unsubscribed';
                $updateData['unsubscribed_at'] = now();
                $campaign->increment('unsubscribed_count');
                break;
        }

        $recipient->update($updateData);
        $this->updateCampaignMetrics($campaign);
    }

    public function updateCampaignMetrics(EmailCampaign $campaign): void
    {
        $campaign->update([
            'open_rate' => $campaign->total_recipients > 0 ? ($campaign->opened_count / $campaign->total_recipients) * 100 : 0,
            'click_rate' => $campaign->opened_count > 0 ? ($campaign->clicked_count / $campaign->opened_count) * 100 : 0,
            'unsubscribe_rate' => $campaign->total_recipients > 0 ? ($campaign->unsubscribed_count / $campaign->total_recipients) * 100 : 0,
            'bounce_rate' => $campaign->total_recipients > 0 ? ($campaign->bounced_count / $campaign->total_recipients) * 100 : 0,
        ]);
    }

    public function createAutomationRule(array $data): EmailAutomationRule
    {
        return EmailAutomationRule::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'trigger_event' => $data['trigger_event'],
            'trigger_conditions' => $data['trigger_conditions'] ?? null,
            'audience_criteria' => $data['audience_criteria'] ?? null,
            'template_id' => $data['template_id'],
            'delay_minutes' => $data['delay_minutes'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => auth()->id(),
            'tenant_id' => tenant()->id,
        ]);
    }

    public function triggerAutomation(string $event, array $data): void
    {
        $rules = EmailAutomationRule::where('tenant_id', tenant()->id)
            ->where('trigger_event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->matchesTriggerConditions($rule, $data)) {
                ProcessAutomationRuleJob::dispatch($rule, $data)
                    ->delay(now()->addMinutes($rule->delay_minutes));
            }
        }
    }

    protected function matchesTriggerConditions(EmailAutomationRule $rule, array $data): bool
    {
        if (!$rule->trigger_conditions) {
            return true;
        }

        // Implement condition matching logic
        foreach ($rule->trigger_conditions as $condition) {
            // Example: ['field' => 'user.graduation_year', 'operator' => '>=', 'value' => 2020]
            // This would be expanded based on specific automation needs
        }

        return true;
    }

    protected function createProviderCampaign(EmailCampaign $campaign): void
    {
        $provider = $this->getProvider($campaign->provider);
        $result = $provider->createCampaign($campaign);

        if ($result['success']) {
            $campaign->update([
                'provider_campaign_id' => $result['campaign_id'],
                'provider_data' => $result['data'] ?? null,
            ]);
        }
    }

    protected function createRecipientRecords(EmailCampaign $campaign, Collection $recipients): void
    {
        $recipientData = $recipients->map(function ($user) use ($campaign) {
            return [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        $campaign->recipients()->insert($recipientData);
    }

    protected function getProvider(string $providerName): object
    {
        $providerClass = $this->providers[$providerName] ?? $this->providers['internal'];
        return new $providerClass();
    }
}

// Provider interfaces and implementations would be created separately
interface EmailProviderInterface
{
    public function createCampaign(EmailCampaign $campaign): array;
    public function sendCampaign(EmailCampaign $campaign, Collection $recipients): array;
    public function getCampaignStats(EmailCampaign $campaign): array;
}

class InternalProvider implements EmailProviderInterface
{
    public function createCampaign(EmailCampaign $campaign): array
    {
        return ['success' => true, 'campaign_id' => null];
    }

    public function sendCampaign(EmailCampaign $campaign, Collection $recipients): array
    {
        // Use Laravel's built-in mail system
        // Implementation would use Mail::send() or similar
        return ['success' => true];
    }

    public function getCampaignStats(EmailCampaign $campaign): array
    {
        return ['success' => true, 'stats' => []];
    }
}

class MailchimpProvider implements EmailProviderInterface
{
    public function createCampaign(EmailCampaign $campaign): array
    {
        // Mailchimp API integration
        return ['success' => true, 'campaign_id' => 'mc_' . uniqid()];
    }

    public function sendCampaign(EmailCampaign $campaign, Collection $recipients): array
    {
        // Mailchimp send implementation
        return ['success' => true];
    }

    public function getCampaignStats(EmailCampaign $campaign): array
    {
        // Mailchimp stats retrieval
        return ['success' => true, 'stats' => []];
    }
}

class ConstantContactProvider implements EmailProviderInterface
{
    public function createCampaign(EmailCampaign $campaign): array
    {
        // Constant Contact API integration
        return ['success' => true, 'campaign_id' => 'cc_' . uniqid()];
    }

    public function sendCampaign(EmailCampaign $campaign, Collection $recipients): array
    {
        // Constant Contact send implementation
        return ['success' => true];
    }

    public function getCampaignStats(EmailCampaign $campaign): array
    {
        // Constant Contact stats retrieval
        return ['success' => true, 'stats' => []];
    }
}

class MauticProvider implements EmailProviderInterface
{
    public function createCampaign(EmailCampaign $campaign): array
    {
        // Mautic API integration
        return ['success' => true, 'campaign_id' => 'mautic_' . uniqid()];
    }

    public function sendCampaign(EmailCampaign $campaign, Collection $recipients): array
    {
        // Mautic send implementation
        return ['success' => true];
    }

    public function getCampaignStats(EmailCampaign $campaign): array
    {
        // Mautic stats retrieval
        return ['success' => true, 'stats' => []];
    }
}