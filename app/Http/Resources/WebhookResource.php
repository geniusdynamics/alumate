<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'events' => $this->events,
            'status' => $this->status,
            'name' => $this->name,
            'description' => $this->description,
            'headers' => $this->when($request->user()->id === $this->user_id, $this->headers),
            'timeout' => $this->timeout,
            'retry_attempts' => $this->retry_attempts,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Statistics
            'stats' => $this->when($this->relationLoaded('deliveries'), function () {
                $deliveries = $this->deliveries;
                $total = $deliveries->count();
                $successful = $deliveries->where('status', 'delivered')->count();
                $failed = $deliveries->where('status', 'failed')->count();

                return [
                    'total_deliveries' => $total,
                    'successful_deliveries' => $successful,
                    'failed_deliveries' => $failed,
                    'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                    'last_delivery' => $deliveries->sortByDesc('created_at')->first()?->created_at,
                ];
            }),

            // Recent deliveries
            'recent_deliveries' => $this->when($this->relationLoaded('deliveries'), function () {
                return $this->deliveries->take(5)->map(function ($delivery) {
                    return [
                        'id' => $delivery->id,
                        'event_type' => $delivery->event_type,
                        'status' => $delivery->status,
                        'response_code' => $delivery->response_code,
                        'error_message' => $delivery->error_message,
                        'retry_count' => $delivery->retry_count,
                        'created_at' => $delivery->created_at,
                        'delivered_at' => $delivery->delivered_at,
                    ];
                });
            }),
        ];
    }
}
