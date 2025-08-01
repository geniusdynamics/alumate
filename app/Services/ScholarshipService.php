<?php

namespace App\Services;

use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipRecipient;
use App\Models\ScholarshipReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ScholarshipService
{
    public function createScholarship(array $data, User $creator): Scholarship
    {
        return DB::transaction(function () use ($data, $creator) {
            $scholarship = Scholarship::create([
                ...$data,
                'creator_id' => $creator->id,
                'status' => 'draft',
            ]);

            return $scholarship;
        });
    }

    public function updateScholarship(Scholarship $scholarship, array $data): Scholarship
    {
        $scholarship->update($data);
        return $scholarship->fresh();
    }

    public function getScholarships(array $filters = []): LengthAwarePaginator
    {
        $query = Scholarship::with(['creator', 'institution'])
            ->withCount(['applications', 'recipients']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['creator_id'])) {
            $query->where('creator_id', $filters['creator_id']);
        }

        if (isset($filters['open_for_applications']) && $filters['open_for_applications']) {
            $query->openForApplications();
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function submitApplication(Scholarship $scholarship, User $applicant, array $data): ScholarshipApplication
    {
        if (!$scholarship->isOpenForApplications()) {
            throw new \Exception('Scholarship is not open for applications');
        }

        // Check if user already applied
        $existingApplication = ScholarshipApplication::where('scholarship_id', $scholarship->id)
            ->where('applicant_id', $applicant->id)
            ->first();

        if ($existingApplication) {
            throw new \Exception('You have already applied for this scholarship');
        }

        return DB::transaction(function () use ($scholarship, $applicant, $data) {
            return ScholarshipApplication::create([
                'scholarship_id' => $scholarship->id,
                'applicant_id' => $applicant->id,
                'status' => 'submitted',
                'submitted_at' => now(),
                ...$data,
            ]);
        });
    }

    public function getApplications(Scholarship $scholarship, array $filters = []): LengthAwarePaginator
    {
        $query = $scholarship->applications()
            ->with(['applicant', 'reviews'])
            ->withAvg('reviews', 'score');

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('submitted_at', 'desc')->paginate(15);
    }

    public function reviewApplication(ScholarshipApplication $application, User $reviewer, array $data): ScholarshipReview
    {
        return DB::transaction(function () use ($application, $reviewer, $data) {
            // Create or update review
            $review = ScholarshipReview::updateOrCreate(
                [
                    'application_id' => $application->id,
                    'reviewer_id' => $reviewer->id,
                ],
                $data
            );

            // Update application status if needed
            if ($application->status === 'submitted') {
                $application->update(['status' => 'under_review']);
            }

            return $review;
        });
    }

    public function awardScholarship(ScholarshipApplication $application, array $data): ScholarshipRecipient
    {
        return DB::transaction(function () use ($application, $data) {
            // Create recipient record
            $recipient = ScholarshipRecipient::create([
                'scholarship_id' => $application->scholarship_id,
                'application_id' => $application->id,
                'recipient_id' => $application->applicant_id,
                'status' => 'awarded',
                ...$data,
            ]);

            // Update application status
            $application->update(['status' => 'awarded']);

            // Update scholarship awarded amount
            $scholarship = $application->scholarship;
            $scholarship->increment('awarded_amount', $data['awarded_amount']);

            return $recipient;
        });
    }

    public function getRecipients(Scholarship $scholarship): Collection
    {
        return $scholarship->recipients()
            ->with(['recipient', 'application'])
            ->orderBy('award_date', 'desc')
            ->get();
    }

    public function updateRecipientProgress(ScholarshipRecipient $recipient, array $data): ScholarshipRecipient
    {
        $recipient->update($data);
        return $recipient->fresh();
    }

    public function getScholarshipImpactReport(Scholarship $scholarship): array
    {
        $recipients = $this->getRecipients($scholarship);
        
        return [
            'total_awarded' => $scholarship->awarded_amount,
            'recipients_count' => $recipients->count(),
            'success_stories_count' => $recipients->where('success_story', '!=', null)->count(),
            'active_recipients' => $recipients->where('status', 'active')->count(),
            'completed_recipients' => $recipients->where('status', 'completed')->count(),
            'average_years_since_award' => $recipients->avg(function ($recipient) {
                return $recipient->years_since_award;
            }),
            'recipients_by_year' => $recipients->groupBy(function ($recipient) {
                return $recipient->award_date->year;
            })->map->count(),
        ];
    }

    public function getDonorUpdates(User $donor): array
    {
        $scholarships = Scholarship::where('creator_id', $donor->id)
            ->with(['recipients.recipient'])
            ->get();

        $updates = [];
        
        foreach ($scholarships as $scholarship) {
            foreach ($scholarship->recipients as $recipient) {
                if (!empty($recipient->updates)) {
                    $updates[] = [
                        'scholarship_name' => $scholarship->name,
                        'recipient_name' => $recipient->recipient->name,
                        'update_date' => $recipient->updated_at,
                        'updates' => $recipient->updates,
                        'success_story' => $recipient->success_story,
                    ];
                }
            }
        }

        return collect($updates)->sortByDesc('update_date')->values()->all();
    }
}