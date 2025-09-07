<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Email Sequence Resource
 *
 * Formats email sequence data for API responses
 */
class EmailSequenceResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'description' => $this->description,
            'audience_type' => $this->audience_type,
            'trigger_type' => $this->trigger_type,
            'trigger_conditions' => $this->trigger_conditions,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include relationships when loaded
        if ($this->whenLoaded('sequenceEmails')) {
            $data['emails'] = SequenceEmailResource::collection($this->sequenceEmails);
            $data['emails_count'] = $this->sequenceEmails->count();
        }

        if ($this->whenLoaded('sequenceEnrollments')) {
            $data['enrollments'] = SequenceEnrollmentResource::collection($this->sequenceEnrollments);
            $data['enrollments_count'] = $this->sequenceEnrollments->count();
        }

        // Include tenant information when loaded
        if ($this->whenLoaded('tenant')) {
            $data['tenant'] = [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'domain' => $this->tenant->domain,
            ];
        }

        // Include statistics when requested
        if ($request->has('with_stats')) {
            $data['stats'] = $this->getSequenceStats();
        }

        // Include performance metrics when requested
        if ($request->has('with_performance')) {
            $data['performance'] = $this->getPerformanceMetrics();
        }

        return $data;
    }

    /**
     * Get sequence statistics.
     *
     * @return array
     */
    protected function getSequenceStats(): array
    {
        $emails = $this->sequenceEmails ?? collect();
        $enrollments = $this->sequenceEnrollments ?? collect();

        return [
            'total_emails' => $emails->count(),
            'total_enrollments' => $enrollments->count(),
            'active_enrollments' => $enrollments->where('status', 'active')->count(),
            'completed_enrollments' => $enrollments->where('status', 'completed')->count(),
            'paused_enrollments' => $enrollments->where('status', 'paused')->count(),
            'unsubscribed_enrollments' => $enrollments->where('status', 'unsubscribed')->count(),
            'completion_rate' => $enrollments->count() > 0
                ? round(($enrollments->where('status', 'completed')->count() / $enrollments->count()) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get performance metrics for the sequence.
     *
     * @return array
     */
    protected function getPerformanceMetrics(): array
    {
        $enrollments = $this->sequenceEnrollments ?? collect();

        if ($enrollments->isEmpty()) {
            return [
                'average_completion_time' => 0,
                'enrollment_trends' => [],
                'step_completion_rates' => [],
            ];
        }

        // Calculate average completion time
        $completedEnrollments = $enrollments->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereNotNull('enrolled_at');

        $averageCompletionTime = 0;
        if ($completedEnrollments->isNotEmpty()) {
            $totalTime = $completedEnrollments->sum(function ($enrollment) {
                return $enrollment->enrolled_at->diffInDays($enrollment->completed_at);
            });
            $averageCompletionTime = round($totalTime / $completedEnrollments->count(), 1);
        }

        // Get enrollment trends (last 30 days)
        $thirtyDaysAgo = now()->subDays(30);
        $recentEnrollments = $enrollments->where('enrolled_at', '>=', $thirtyDaysAgo);

        $enrollmentTrends = $recentEnrollments
            ->groupBy(function ($enrollment) {
                return $enrollment->enrolled_at->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();

        // Calculate step completion rates
        $stepCompletionRates = [];
        $emails = $this->sequenceEmails ?? collect();

        foreach ($emails as $email) {
            $stepEnrollments = $enrollments->where('current_step', '>=', $email->send_order);
            $stepCompletionRate = $enrollments->count() > 0
                ? round(($stepEnrollments->count() / $enrollments->count()) * 100, 2)
                : 0;

            $stepCompletionRates[] = [
                'step' => $email->send_order,
                'email_subject' => $email->subject_line,
                'completion_rate' => $stepCompletionRate,
                'enrollments_at_step' => $stepEnrollments->count(),
            ];
        }

        return [
            'average_completion_time' => $averageCompletionTime,
            'enrollment_trends' => $enrollmentTrends,
            'step_completion_rates' => $stepCompletionRates,
        ];
    }
}