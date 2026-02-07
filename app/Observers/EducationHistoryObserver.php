<?php

namespace App\Observers;

use App\Jobs\UpdateUserCirclesJob;
use App\Models\AnalyticsEvent;
use App\Models\EducationHistory;
use Illuminate\Support\Facades\Log;

class EducationHistoryObserver
{
    /**
     * Handle the EducationHistory "saving" event.
     */
    public function saving(EducationHistory $educationHistory): void
    {
        try {
            app(\App\Services\SecurityService::class)->logDataAccess('education_history', $educationHistory->id, 'update', true, 'observer');
        } catch (\Exception $e) {
            Log::error('Failed to log data access in EducationHistoryObserver saving', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the EducationHistory "created" event.
     */
    public function created(EducationHistory $educationHistory): void
    {
        try {
            // Track education history creation for career analytics
            AnalyticsEvent::create([
                'event_type' => 'education_added',
                'event_category' => 'career_analytics',
                'user_id' => $educationHistory->user_id,
                'tenant_id' => $educationHistory->tenant_id ?? null,
                'event_data' => [
                    'education_id' => $educationHistory->id,
                    'institution' => $educationHistory->institution,
                    'degree' => $educationHistory->degree,
                    'field_of_study' => $educationHistory->field_of_study,
                    'graduation_year' => $educationHistory->graduation_year,
                    'timestamp' => now(),
                ],
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
            ]);

            $this->updateUserCircles($educationHistory);
        } catch (\Exception $e) {
            Log::error('Failed to handle education history created event', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the EducationHistory "updated" event.
     */
    public function updated(EducationHistory $educationHistory): void
    {
        try {
            // Track education history update for career analytics
            AnalyticsEvent::create([
                'event_type' => 'education_updated',
                'event_category' => 'career_analytics',
                'user_id' => $educationHistory->user_id,
                'tenant_id' => $educationHistory->tenant_id ?? null,
                'event_data' => [
                    'education_id' => $educationHistory->id,
                    'institution' => $educationHistory->institution,
                    'degree' => $educationHistory->degree,
                    'field_of_study' => $educationHistory->field_of_study,
                    'graduation_year' => $educationHistory->graduation_year,
                    'timestamp' => now(),
                ],
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
            ]);

            $this->updateUserCircles($educationHistory);
        } catch (\Exception $e) {
            Log::error('Failed to handle education history updated event', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the EducationHistory "deleted" event.
     */
    public function deleted(EducationHistory $educationHistory): void
    {
        try {
            // Track education history deletion for career analytics
            AnalyticsEvent::create([
                'event_type' => 'education_deleted',
                'event_category' => 'career_analytics',
                'user_id' => $educationHistory->user_id,
                'tenant_id' => $educationHistory->tenant_id ?? null,
                'event_data' => [
                    'education_id' => $educationHistory->id,
                    'institution' => $educationHistory->institution,
                    'degree' => $educationHistory->degree,
                    'field_of_study' => $educationHistory->field_of_study,
                    'graduation_year' => $educationHistory->graduation_year,
                    'timestamp' => now(),
                ],
                'ip_address' => request() ? request()->ip() : null,
                'user_agent' => request() ? request()->userAgent() : null,
            ]);

            $this->updateUserCircles($educationHistory);
        } catch (\Exception $e) {
            Log::error('Failed to handle education history deleted event', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update user circles when education history changes.
     */
    protected function updateUserCircles(EducationHistory $educationHistory): void
    {
        try {
            $user = $educationHistory->user;

            if ($user) {
                // Dispatch job to update circles in the background
                UpdateUserCirclesJob::dispatch($user);

                Log::info('Dispatched circle update job due to education history change', [
                    'user_id' => $user->id,
                    'education_history_id' => $educationHistory->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch circle update job for education history change', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
