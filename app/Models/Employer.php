<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_address',
        'company_phone',
        'approved',
        'verification_status',
        'verification_documents',
        'verification_submitted_at',
        'verification_completed_at',
        'verified_by',
        'verification_notes',
        'rejection_reason',
        'company_registration_number',
        'company_tax_number',
        'company_website',
        'company_size',
        'industry',
        'company_description',
        'contact_person_name',
        'contact_person_title',
        'contact_person_email',
        'contact_person_phone',
        'established_year',
        'employee_count',
        'business_locations',
        'services_products',
        'total_jobs_posted',
        'active_jobs_count',
        'total_hires',
        'average_time_to_hire',
        'employer_rating',
        'total_reviews',
        'employer_benefits',
        'subscription_plan',
        'subscription_expires_at',
        'job_posting_limit',
        'jobs_posted_this_month',
        'is_active',
        'can_search_graduates',
        'notification_preferences',
        'terms_accepted',
        'terms_accepted_at',
        'privacy_policy_accepted',
        'privacy_policy_accepted_at',
        'last_login_at',
        'last_job_posted_at',
        'profile_completed_at',
    ];

    protected $appends = ['can_post_jobs'];

    protected $casts = [
        'approved' => 'boolean',
        'verification_documents' => 'array',
        'verification_submitted_at' => 'datetime',
        'verification_completed_at' => 'datetime',
        'established_year' => 'integer',
        'employee_count' => 'integer',
        'business_locations' => 'array',
        'services_products' => 'array',
        'total_jobs_posted' => 'integer',
        'active_jobs_count' => 'integer',
        'total_hires' => 'integer',
        'average_time_to_hire' => 'decimal:2',
        'employer_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'employer_benefits' => 'array',
        'subscription_expires_at' => 'datetime',
        'job_posting_limit' => 'integer',
        'jobs_posted_this_month' => 'integer',
        'is_active' => 'boolean',
        'can_search_graduates' => 'boolean',
        'notification_preferences' => 'array',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'privacy_policy_accepted' => 'boolean',
        'privacy_policy_accepted_at' => 'datetime',
        'last_login_at' => 'datetime',
        'last_job_posted_at' => 'datetime',
        'profile_completed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Accessors & Mutators
    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->verification_status === 'verified',
        );
    }

    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->verification_status === 'pending',
        );
    }

    protected function isRejected(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->verification_status === 'rejected',
        );
    }

    protected function canPostJobs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active && $this->can_post_jobs && $this->isVerified,
        );
    }

    protected function hasReachedJobLimit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->jobs_posted_this_month >= $this->job_posting_limit,
        );
    }

    /**
     * Get the can_post_jobs attribute based on subscription/plan logic.
     * This accessor returns a boolean based on the employer's status and subscription.
     */
    public function getCanPostJobsAttribute(): bool
    {
        // Check if employer is active and verified
        if (!$this->is_active || $this->verification_status !== 'verified') {
            return false;
        }

        // Check if the database field can_post_jobs is set (subscription level check)
        if (!$this->attributes['can_post_jobs'] ?? false) {
            return false;
        }

        // Check subscription plan limits
        if ($this->subscription_plan) {
            // Basic plan has limited job postings
            if ($this->subscription_plan === 'basic' && $this->jobs_posted_this_month >= 5) {
                return false;
            }
            
            // Premium plan has higher limits
            if ($this->subscription_plan === 'premium' && $this->jobs_posted_this_month >= 50) {
                return false;
            }
            
            // Enterprise plan has unlimited postings (within reasonable limits)
            if ($this->subscription_plan === 'enterprise' && $this->jobs_posted_this_month >= 1000) {
                return false;
            }
        }

        // Check if subscription is expired
        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            return false;
        }

        // Check monthly job posting limit
        if ($this->job_posting_limit && $this->jobs_posted_this_month >= $this->job_posting_limit) {
            return false;
        }

        return true;
    }


    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('verification_status', 'rejected');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCanPostJobs($query)
    {
        return $query->where('can_post_jobs', true)
                    ->where('is_active', true)
                    ->where('verification_status', 'verified');
    }

    public function scopeByIndustry($query, $industry)
    {
        return $query->where('industry', $industry);
    }

    public function scopeBySize($query, $size)
    {
        return $query->where('company_size', $size);
    }

    public function scopeBySubscription($query, $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    // Helper Methods
    public function verify($verifierId, $notes = null)
    {
        $this->update([
            'verification_status' => 'verified',
            'verification_completed_at' => now(),
            'verified_by' => $verifierId,
            'verification_notes' => $notes,
            'can_post_jobs' => true,
            'can_search_graduates' => true,
        ]);

        return $this;
    }

    public function reject($reason, $verifierId)
    {
        $this->update([
            'verification_status' => 'rejected',
            'verification_completed_at' => now(),
            'verified_by' => $verifierId,
            'rejection_reason' => $reason,
            'can_post_jobs' => false,
            'can_search_graduates' => false,
        ]);

        return $this;
    }

    public function suspend($reason = null)
    {
        $this->update([
            'verification_status' => 'suspended',
            'rejection_reason' => $reason,
            'can_post_jobs' => false,
            'can_search_graduates' => false,
            'is_active' => false,
        ]);

        return $this;
    }

    public function reactivate()
    {
        $this->update([
            'verification_status' => 'verified',
            'rejection_reason' => null,
            'can_post_jobs' => true,
            'can_search_graduates' => true,
            'is_active' => true,
        ]);

        return $this;
    }

    public function updateJobStats()
    {
        $activeJobs = $this->jobs()->active()->count();
        $totalJobs = $this->jobs()->count();
        $totalHires = $this->jobs()
            ->whereHas('applications', function ($query) {
                $query->where('status', 'hired');
            })
            ->count();

        $this->update([
            'active_jobs_count' => $activeJobs,
            'total_jobs_posted' => $totalJobs,
            'total_hires' => $totalHires,
        ]);

        return [
            'active_jobs' => $activeJobs,
            'total_jobs' => $totalJobs,
            'total_hires' => $totalHires,
        ];
    }

    public function incrementJobsPostedThisMonth()
    {
        $this->increment('jobs_posted_this_month');
        $this->update(['last_job_posted_at' => now()]);
    }

    public function resetMonthlyJobCount()
    {
        $this->update(['jobs_posted_this_month' => 0]);
    }

    public function updateRating($newRating)
    {
        $currentTotal = $this->employer_rating * $this->total_reviews;
        $newTotal = $currentTotal + $newRating;
        $newReviewCount = $this->total_reviews + 1;
        $newAverageRating = $newTotal / $newReviewCount;

        $this->update([
            'employer_rating' => round($newAverageRating, 2),
            'total_reviews' => $newReviewCount,
        ]);

        return $newAverageRating;
    }

    public function getProfileCompletionPercentage()
    {
        $requiredFields = [
            'company_name', 'company_address', 'company_phone', 'industry',
            'company_description', 'contact_person_name', 'contact_person_email'
        ];

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if (!empty($this->$field)) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100, 2);
    }

    public function markProfileCompleted()
    {
        if ($this->getProfileCompletionPercentage() >= 100) {
            $this->update(['profile_completed_at' => now()]);
        }
    }

    public function getActiveJobs($limit = null)
    {
        $query = $this->jobs()->active()->orderBy('created_at', 'desc');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        return $query->get();
    }

    public function getRecentApplications($limit = 10)
    {
        return JobApplication::whereHas('job', function ($query) {
            $query->where('employer_id', $this->id);
        })
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    }

    public function canPostMoreJobs()
    {
        return $this->canPostJobs && !$this->hasReachedJobLimit;
    }

    public function getRemainingJobPosts()
    {
        return max(0, $this->job_posting_limit - $this->jobs_posted_this_month);
    }
}
