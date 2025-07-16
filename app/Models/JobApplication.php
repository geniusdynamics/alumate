<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'graduate_id',
        'cover_letter',
        'status',
        'status_history',
        'status_changed_at',
        'status_changed_by',
        'resume_data',
        'resume_file_path',
        'additional_documents',
        'interview_scheduled_at',
        'interview_location',
        'interview_notes',
        'assessment_scores',
        'employer_feedback',
        'employer_rating',
        'rejection_reason',
        'offered_salary',
        'offer_expiry_date',
        'offer_terms',
        'graduate_response',
        'graduate_responded_at',
        'match_score',
        'matching_factors',
        'messages_count',
        'last_message_at',
        'application_source',
        'priority',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'status_history' => 'array',
        'status_changed_at' => 'datetime',
        'resume_data' => 'array',
        'additional_documents' => 'array',
        'interview_scheduled_at' => 'datetime',
        'assessment_scores' => 'array',
        'employer_rating' => 'integer',
        'offered_salary' => 'decimal:2',
        'offer_expiry_date' => 'datetime',
        'offer_terms' => 'array',
        'graduate_responded_at' => 'datetime',
        'match_score' => 'decimal:2',
        'matching_factors' => 'array',
        'messages_count' => 'integer',
        'last_message_at' => 'datetime',
        'is_flagged' => 'boolean',
    ];

    // Relationships
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }

    public function statusChanger()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    // Accessors & Mutators
    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'pending',
        );
    }

    protected function isHired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'hired',
        );
    }

    protected function isRejected(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'rejected',
        );
    }

    protected function hasInterview(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, ['interview_scheduled', 'interviewed']),
        );
    }

    protected function hasOffer(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, ['offer_made', 'offer_accepted', 'offer_declined']),
        );
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeHired($query)
    {
        return $query->where('status', 'hired');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeByJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeByGraduate($query, $graduateId)
    {
        return $query->where('graduate_id', $graduateId);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    public function scopeWithInterview($query)
    {
        return $query->whereIn('status', ['interview_scheduled', 'interviewed']);
    }

    public function scopeWithOffer($query)
    {
        return $query->whereIn('status', ['offer_made', 'offer_accepted', 'offer_declined']);
    }

    // Helper Methods
    public function updateStatus($newStatus, $userId = null, $notes = null)
    {
        $oldStatus = $this->status;
        $statusHistory = $this->status_history ?? [];
        
        // Add to status history
        $statusHistory[] = [
            'from' => $oldStatus,
            'to' => $newStatus,
            'changed_at' => now()->toISOString(),
            'changed_by' => $userId,
            'notes' => $notes,
        ];

        $this->update([
            'status' => $newStatus,
            'status_history' => $statusHistory,
            'status_changed_at' => now(),
            'status_changed_by' => $userId,
        ]);

        // Update job application statistics
        $this->job->updateApplicationStats();

        return $this;
    }

    public function scheduleInterview($dateTime, $location = null, $notes = null)
    {
        $this->update([
            'interview_scheduled_at' => $dateTime,
            'interview_location' => $location,
            'interview_notes' => $notes,
        ]);

        $this->updateStatus('interview_scheduled');

        return $this;
    }

    public function makeOffer($salary, $expiryDate = null, $terms = [])
    {
        $this->update([
            'offered_salary' => $salary,
            'offer_expiry_date' => $expiryDate,
            'offer_terms' => $terms,
        ]);

        $this->updateStatus('offer_made');

        return $this;
    }

    public function acceptOffer($graduateResponse = null)
    {
        $this->update([
            'graduate_response' => $graduateResponse,
            'graduate_responded_at' => now(),
        ]);

        $this->updateStatus('offer_accepted');

        // Update graduate employment status
        $this->graduate->updateEmploymentStatus('employed', [
            'job_title' => $this->job->title,
            'company' => $this->job->employer->company_name,
            'salary' => $this->offered_salary,
            'start_date' => now(),
        ]);

        return $this;
    }

    public function declineOffer($graduateResponse = null)
    {
        $this->update([
            'graduate_response' => $graduateResponse,
            'graduate_responded_at' => now(),
        ]);

        $this->updateStatus('offer_declined');

        return $this;
    }

    public function reject($reason = null, $userId = null)
    {
        $this->update([
            'rejection_reason' => $reason,
        ]);

        $this->updateStatus('rejected', $userId, $reason);

        return $this;
    }

    public function flag($reason, $userId = null)
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);

        return $this;
    }

    public function unflag()
    {
        $this->update([
            'is_flagged' => false,
            'flag_reason' => null,
        ]);

        return $this;
    }

    public function calculateMatchScore()
    {
        $score = 0;
        $factors = [];

        // Course match (40% weight)
        if ($this->job->course_id === $this->graduate->course_id) {
            $score += 40;
            $factors['course_match'] = true;
        }

        // Skills match (30% weight)
        if (!empty($this->job->required_skills) && !empty($this->graduate->skills)) {
            $matchingSkills = array_intersect(
                array_map('strtolower', $this->job->required_skills),
                array_map('strtolower', $this->graduate->skills)
            );
            $skillsScore = (count($matchingSkills) / count($this->job->required_skills)) * 30;
            $score += $skillsScore;
            $factors['skills_match'] = count($matchingSkills);
        }

        // Profile completion (20% weight)
        $score += ($this->graduate->profile_completion_percentage / 100) * 20;
        $factors['profile_completion'] = $this->graduate->profile_completion_percentage;

        // GPA bonus (10% weight)
        if ($this->graduate->gpa) {
            $score += ($this->graduate->gpa / 4.0) * 10;
            $factors['gpa'] = $this->graduate->gpa;
        }

        $this->update([
            'match_score' => round($score, 2),
            'matching_factors' => $factors,
        ]);

        return [
            'score' => round($score, 2),
            'factors' => $factors
        ];
    }

    public function getDaysToOfferExpiry()
    {
        if (!$this->offer_expiry_date) {
            return null;
        }
        
        return now()->diffInDays($this->offer_expiry_date, false);
    }

    public function isOfferExpired()
    {
        return $this->offer_expiry_date && now()->isAfter($this->offer_expiry_date);
    }

    public function getStatusColor()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'reviewed' => 'blue',
            'shortlisted' => 'purple',
            'interview_scheduled', 'interviewed' => 'indigo',
            'offer_made' => 'orange',
            'offer_accepted', 'hired' => 'green',
            'offer_declined', 'rejected', 'withdrawn' => 'red',
            default => 'gray',
        };
    }

    public function getStatusLabel()
    {
        return match($this->status) {
            'pending' => 'Pending Review',
            'reviewed' => 'Reviewed',
            'shortlisted' => 'Shortlisted',
            'interview_scheduled' => 'Interview Scheduled',
            'interviewed' => 'Interviewed',
            'reference_check' => 'Reference Check',
            'offer_made' => 'Offer Made',
            'offer_accepted' => 'Offer Accepted',
            'offer_declined' => 'Offer Declined',
            'hired' => 'Hired',
            'rejected' => 'Rejected',
            'withdrawn' => 'Withdrawn',
            default => ucfirst($this->status),
        };
    }
}
