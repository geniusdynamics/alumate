<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sequence Email Resource
 *
 * Formats sequence email data for API responses
 */
class SequenceEmailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'sequence_id' => $this->sequence_id,
            'template_id' => $this->template_id,
            'subject_line' => $this->subject_line,
            'delay_hours' => $this->delay_hours,
            'send_order' => $this->send_order,
            'trigger_conditions' => $this->trigger_conditions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include template information when loaded
        if ($this->whenLoaded('template')) {
            $data['template'] = [
                'id' => $this->template->id,
                'name' => $this->template->name,
                'slug' => $this->template->slug,
                'category' => $this->template->category,
                'is_active' => $this->template->is_active,
            ];
        }

        // Include sequence information when loaded
        if ($this->whenLoaded('sequence')) {
            $data['sequence'] = [
                'id' => $this->sequence->id,
                'name' => $this->sequence->name,
                'is_active' => $this->sequence->is_active,
            ];
        }

        // Include email statistics when requested
        if ($request->has('with_stats')) {
            $data['stats'] = $this->getEmailStats();
            $data['performance'] = [
                'open_rate' => $this->getOpenRate(),
                'click_rate' => $this->getClickRate(),
            ];
        }

        return $data;
    }

    /**
     * Get email statistics.
     *
     * @return array
     */
    protected function getEmailStats(): array
    {
        $sends = $this->emailSends ?? collect();

        return [
            'total_sends' => $sends->count(),
            'sent_count' => $sends->where('status', 'sent')->count(),
            'delivered_count' => $sends->where('status', 'delivered')->count(),
            'opened_count' => $sends->whereNotNull('opened_at')->count(),
            'clicked_count' => $sends->whereNotNull('clicked_at')->count(),
            'bounced_count' => $sends->where('status', 'bounced')->count(),
            'unsubscribed_count' => $sends->whereNotNull('unsubscribed_at')->count(),
        ];
    }

    /**
     * Get open rate percentage.
     *
     * @return float
     */
    protected function getOpenRate(): float
    {
        $stats = $this->getEmailStats();
        return $stats['delivered_count'] > 0
            ? round(($stats['opened_count'] / $stats['delivered_count']) * 100, 2)
            : 0.0;
    }

    /**
     * Get click rate percentage.
     *
     * @return float
     */
    protected function getClickRate(): float
    {
        $stats = $this->getEmailStats();
        return $stats['delivered_count'] > 0
            ? round(($stats['clicked_count'] / $stats['delivered_count']) * 100, 2)
            : 0.0;
    }
}