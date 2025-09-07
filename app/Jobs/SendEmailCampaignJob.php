<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\EmailMarketingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailCampaignJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 1;

    public function __construct(
        public EmailCampaign $campaign
    ) {}

    public function handle(EmailMarketingService $emailMarketingService): void
    {
        try {
            Log::info('Starting email campaign send', [
                'campaign_id' => $this->campaign->id,
                'campaign_name' => $this->campaign->name,
            ]);

            $success = $emailMarketingService->sendCampaign($this->campaign);

            if ($success) {
                Log::info('Email campaign sent successfully', [
                    'campaign_id' => $this->campaign->id,
                    'recipients' => $this->campaign->total_recipients,
                ]);
            } else {
                Log::error('Email campaign send failed', [
                    'campaign_id' => $this->campaign->id,
                ]);

                $this->fail('Campaign send failed');
            }
        } catch (\Exception $e) {
            Log::error('Email campaign send job failed', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->campaign->update(['status' => 'draft']);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Email campaign send job permanently failed', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);

        $this->campaign->update([
            'status' => 'cancelled',
        ]);
    }
}
