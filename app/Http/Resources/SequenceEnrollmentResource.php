<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sequence Enrollment Resource
 *
 * Formats sequence enrollment data for API responses
 */
class SequenceEnrollmentResource extends JsonResource
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
            'lead_id' => $this->lead_id,
            'current_step' => $this->current_step,
            'status' => $this->status,
            'enrolled_at' => $this->enrolled_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include lead information when loaded
        if ($this->whenLoaded('lead')) {
            $data['lead'] = [
                'id' => $this->lead->id,
                'first_name' => $this->lead->first_name,
                'last_name' => $this->lead->last_name,
                'email' => $this->lead->email,
                'phone' => $this->lead->phone,
                'graduation_year' => $this->lead->graduation_year,
                'course' => $this->lead->course,
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

        // Include enrollment statistics when requested
        if ($request->has('with_stats')) {
            $data['stats'] = $this->getEnrollmentStats();
            $data['progress'] = $this->getProgressInfo();
        }

        return $data;
    }

    /**
     * Get enrollment statistics.
     *
     * @return array
     */
    protected function getEnrollmentStats(): array
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
     * Get progress information for the enrollment.
     *
     * @return array
     */
    protected function getProgressInfo(): array
    {
        $sequence = $this->sequence ?? null;
        $totalSteps = $sequence ? $sequence->sequenceEmails()->count() : 0;

        $progressPercentage = $totalSteps > 0
            ? round(($this->current_step / $totalSteps) * 100, 2)
            : 0;

        $daysEnrolled = $this->enrolled_at
            ? $this->enrolled_at->diffInDays(now())
            : 0;

        $isCompleted = $this->status === 'completed';
        $isActive = $this->status === 'active';

        return [
            'current_step' => $this->current_step,
            'total_steps' => $totalSteps,
            'progress_percentage' => $progressPercentage,
            'days_enrolled' => $daysEnrolled,
            'is_completed' => $isCompleted,
            'is_active' => $isActive,
            'steps_remaining' => max(0, $totalSteps - $this->current_step),
        ];
    }
}