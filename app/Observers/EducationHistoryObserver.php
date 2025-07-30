<?php

namespace App\Observers;

use App\Models\EducationHistory;
use App\Jobs\UpdateUserCirclesJob;
use Illuminate\Support\Facades\Log;

class EducationHistoryObserver
{
    /**
     * Handle the EducationHistory "created" event.
     */
    public function created(EducationHistory $educationHistory): void
    {
        $this->updateUserCircles($educationHistory);
    }

    /**
     * Handle the EducationHistory "updated" event.
     */
    public function updated(EducationHistory $educationHistory): void
    {
        $this->updateUserCircles($educationHistory);
    }

    /**
     * Handle the EducationHistory "deleted" event.
     */
    public function deleted(EducationHistory $educationHistory): void
    {
        $this->updateUserCircles($educationHistory);
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
                    'education_history_id' => $educationHistory->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch circle update job for education history change', [
                'education_history_id' => $educationHistory->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}