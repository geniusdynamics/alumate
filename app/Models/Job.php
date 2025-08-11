<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'course_id',
        'title',
        'description',
        'location',
        'required_skills',
        'preferred_qualifications',
        'experience_level',
        'min_experience_years',
        'salary_min',
        'salary_max',
        'salary_type',
        'job_type',
        'work_arrangement',
        'total_applications',
        'viewed_applications',
        'shortlisted_applications',
        'status',
        'requires_approval',
        'approved_at',
        'approved_by',
        'application_deadline',
        'job_start_date',
        'job_end_date',
        'employer_verified_required',
        'matching_criteria',
        'view_count',
        'match_score',
        'contact_email',
        'contact_phone',
        'contact_person',
        'benefits',
        'company_culture',
        'approval_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'preferred_qualifications' => 'array',
        'min_experience_years' => 'integer',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'total_applications' => 'integer',
        'viewed_applications' => 'integer',
        'shortlisted_applications' => 'integer',
        'requires_approval' => 'boolean',
        'approved_at' => 'datetime',
        'application_deadline' => 'datetime',
        'job_start_date' => 'datetime',
        'job_end_date' => 'datetime',
        'employer_verified_required' => 'boolean',
        'matching_criteria' => 'array',
        'view_count' => 'integer',
        'match_score' => 'decimal:2',
        'benefits' => 'array',
    ];

    // Relationships
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Accessors & Mutators
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'active',
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->application_deadline && now()->isAfter($this->application_deadline),
        );
    }

    protected function salaryRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->salary_min && ! $this->salary_max) {
                    return 'Negotiable';
                }

                $min = $this->salary_min ? number_format($this->salary_min, 0) : '';
                $max = $this->salary_max ? number_format($this->salary_max, 0) : '';

                if ($min && $max) {
                    return "$min - $max";
                } elseif ($min) {
                    return "From $min";
                } elseif ($max) {
                    return "Up to $max";
                }

                return 'Negotiable';
            }
        );
    }

    protected function applicationRate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->view_count > 0 ? round(($this->total_applications / $this->view_count) * 100, 2) : 0,
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeByEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    public function scopeByExperienceLevel($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeByJobType($query, $type)
    {
        return $query->where('job_type', $type);
    }

    public function scopeRemote($query)
    {
        return $query->where('work_arrangement', 'remote');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('application_deadline')
                ->orWhere('application_deadline', '>=', now()->toDateString());
        });
    }

    public function scopeRequiresApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', 'like', "%{$location}%");
    }

    // Helper Methods
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function updateApplicationStats()
    {
        $stats = $this->applications()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status IN ("reviewed", "shortlisted", "interviewed", "hired") THEN 1 ELSE 0 END) as viewed,
                SUM(CASE WHEN status IN ("shortlisted", "interviewed", "hired") THEN 1 ELSE 0 END) as shortlisted
            ')
            ->first();

        $this->update([
            'total_applications' => $stats->total ?? 0,
            'viewed_applications' => $stats->viewed ?? 0,
            'shortlisted_applications' => $stats->shortlisted ?? 0,
        ]);

        return $stats;
    }

    public function approve($approverId)
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
            'approved_by' => $approverId,
        ]);
    }

    public function reject($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'rejection_reason' => $reason,
        ]);
    }

    public function pause()
    {
        $this->update(['status' => 'paused']);
    }

    public function resume()
    {
        $this->update(['status' => 'active']);
    }

    public function markAsFilled()
    {
        $this->update(['status' => 'filled']);
    }

    public function getMatchingGraduates($limit = 20)
    {
        $query = Graduate::where('course_id', $this->course_id)
            ->where('job_search_active', true)
            ->where('allow_employer_contact', true);

        // Add skill matching if job has required skills
        if (! empty($this->required_skills)) {
            $query->where(function ($q) {
                foreach ($this->required_skills as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        // Add experience level filtering
        if ($this->min_experience_years > 0) {
            $query->whereHas('profile', function ($q) {
                $q->whereNotNull('work_experience');
            });
        }

        return $query->orderBy('profile_completion_percentage', 'desc')
            ->limit($limit)
            ->get();
    }

    public function calculateMatchScore($graduate)
    {
        $score = 0;
        $factors = [];

        // Course match (40% weight)
        if ($this->course_id === $graduate->course_id) {
            $score += 40;
            $factors['course_match'] = true;
        }

        // Skills match (30% weight)
        if (! empty($this->required_skills) && ! empty($graduate->skills)) {
            $matchingSkills = array_intersect(
                array_map('strtolower', $this->required_skills),
                array_map('strtolower', $graduate->skills)
            );
            $skillsScore = (count($matchingSkills) / count($this->required_skills)) * 30;
            $score += $skillsScore;
            $factors['skills_match'] = count($matchingSkills);
        }

        // Profile completion (20% weight)
        $score += ($graduate->profile_completion_percentage / 100) * 20;
        $factors['profile_completion'] = $graduate->profile_completion_percentage;

        // GPA bonus (10% weight)
        if ($graduate->gpa) {
            $score += ($graduate->gpa / 4.0) * 10;
            $factors['gpa'] = $graduate->gpa;
        }

        return [
            'score' => round($score, 2),
            'factors' => $factors,
        ];
    }

    public function sendToGraduates()
    {
        $matchingGraduates = $this->getMatchingGraduates();

        foreach ($matchingGraduates as $graduate) {
            $matchData = $this->calculateMatchScore($graduate);

            // Create job notification for graduate
            $graduate->user->notifications()->create([
                'type' => 'job_match',
                'data' => [
                    'job_id' => $this->id,
                    'job_title' => $this->title,
                    'company_name' => $this->employer->company_name,
                    'match_score' => $matchData['score'],
                    'match_factors' => $matchData['factors'],
                ],
                'read_at' => null,
            ]);
        }

        return $matchingGraduates->count();
    }

    public function canBeAppliedTo()
    {
        return $this->status === 'active'
            && (! $this->application_deadline || now()->isBefore($this->application_deadline))
            && (! $this->employer_verified_required || $this->employer->verification_status === 'verified');
    }

    public function getDaysUntilDeadline()
    {
        if (! $this->application_deadline) {
            return null;
        }

        return now()->diffInDays($this->application_deadline, false);
    }

    public function checkAndUpdateExpiry()
    {
        if ($this->application_deadline && now()->isAfter($this->application_deadline) && $this->status === 'active') {
            $this->update(['status' => 'expired']);

            return true;
        }

        return false;
    }

    public function renewJob($newDeadline = null)
    {
        $deadline = $newDeadline ?: now()->addDays(30);

        $this->update([
            'application_deadline' => $deadline,
            'status' => 'active',
        ]);

        // Send to matching graduates again
        $this->sendToGraduates();

        return $this;
    }

    public function getJobPerformanceMetrics()
    {
        $daysActive = max(1, $this->created_at->diffInDays(now()));

        return [
            'views' => $this->view_count,
            'applications' => $this->total_applications,
            'application_rate' => $this->application_rate,
            'viewed_applications' => $this->viewed_applications,
            'shortlisted_applications' => $this->shortlisted_applications,
            'days_active' => $daysActive,
            'days_until_deadline' => $this->getDaysUntilDeadline(),
            'avg_applications_per_day' => round($this->total_applications / $daysActive, 2),
            'conversion_rate' => $this->total_applications > 0
                ? round(($this->shortlisted_applications / $this->total_applications) * 100, 2)
                : 0,
            'engagement_score' => $this->calculateEngagementScore(),
            'quality_score' => $this->calculateQualityScore(),
        ];
    }

    public function calculateEngagementScore()
    {
        $viewsWeight = 0.3;
        $applicationsWeight = 0.5;
        $conversionWeight = 0.2;

        $normalizedViews = min(100, ($this->view_count / 100) * 100);
        $normalizedApplications = min(100, ($this->total_applications / 20) * 100);
        $conversionRate = $this->total_applications > 0
            ? ($this->shortlisted_applications / $this->total_applications) * 100
            : 0;

        return round(
            ($normalizedViews * $viewsWeight) +
            ($normalizedApplications * $applicationsWeight) +
            ($conversionRate * $conversionWeight)
        );
    }

    public function calculateQualityScore()
    {
        $score = 0;

        // Description quality (length and detail)
        if (strlen($this->description) > 500) {
            $score += 20;
        } elseif (strlen($this->description) > 200) {
            $score += 10;
        }

        // Salary transparency
        if ($this->salary_min && $this->salary_max) {
            $score += 20;
        } elseif ($this->salary_min || $this->salary_max) {
            $score += 10;
        }

        // Skills specification
        if (! empty($this->required_skills) && count($this->required_skills) >= 3) {
            $score += 15;
        } elseif (! empty($this->required_skills)) {
            $score += 10;
        }

        // Benefits listed
        if (! empty($this->benefits) && count($this->benefits) >= 3) {
            $score += 15;
        } elseif (! empty($this->benefits)) {
            $score += 10;
        }

        // Company culture description
        if ($this->company_culture && strlen($this->company_culture) > 100) {
            $score += 10;
        }

        // Contact information
        if ($this->contact_email || $this->contact_phone) {
            $score += 10;
        }

        // Application deadline set
        if ($this->application_deadline) {
            $score += 10;
        }

        return min(100, $score);
    }

    public function getStatusHistory()
    {
        // This would require a job_status_history table to track status changes
        // For now, return basic status info
        return [
            'current_status' => $this->status,
            'created_at' => $this->created_at,
            'approved_at' => $this->approved_at,
            'last_updated' => $this->updated_at,
        ];
    }

    public function scheduleAutoRenewal($days = 30)
    {
        if ($this->application_deadline) {
            $newDeadline = $this->application_deadline->addDays($days);
        } else {
            $newDeadline = now()->addDays($days);
        }

        $this->update([
            'application_deadline' => $newDeadline,
            'status' => 'active',
        ]);

        return $this->sendToGraduates();
    }

    public function getApplicationInsights()
    {
        $applications = $this->applications()->with('graduate')->get();

        return [
            'total_applications' => $applications->count(),
            'applications_by_status' => $applications->groupBy('status')->map->count(),
            'applications_by_course' => $applications->groupBy('graduate.course.name')->map->count(),
            'average_gpa' => $applications->avg('graduate.gpa'),
            'skills_coverage' => $this->getSkillsCoverage($applications),
            'application_timeline' => $applications->groupBy(function ($app) {
                return $app->created_at->format('Y-m-d');
            })->map->count(),
        ];
    }

    private function getSkillsCoverage($applications)
    {
        if (empty($this->required_skills)) {
            return [];
        }

        $coverage = [];
        foreach ($this->required_skills as $skill) {
            $matchingApplicants = $applications->filter(function ($app) use ($skill) {
                return in_array($skill, $app->graduate->skills ?? []);
            })->count();

            $coverage[$skill] = [
                'applicants_with_skill' => $matchingApplicants,
                'coverage_percentage' => $applications->count() > 0
                    ? round(($matchingApplicants / $applications->count()) * 100, 1)
                    : 0,
            ];
        }

        return $coverage;
    }

    public function generateJobReport()
    {
        return [
            'job_details' => [
                'title' => $this->title,
                'status' => $this->status,
                'created_at' => $this->created_at,
                'deadline' => $this->application_deadline,
            ],
            'performance_metrics' => $this->getJobPerformanceMetrics(),
            'application_insights' => $this->getApplicationInsights(),
            'recommendations' => $this->getOptimizationRecommendations(),
        ];
    }

    private function getOptimizationRecommendations()
    {
        $recommendations = [];
        $metrics = $this->getJobPerformanceMetrics();

        if ($metrics['application_rate'] < 5) {
            $recommendations[] = 'Consider improving job visibility or adjusting requirements';
        }

        if ($metrics['conversion_rate'] < 20) {
            $recommendations[] = 'Review application screening process or job requirements';
        }

        if ($metrics['quality_score'] < 70) {
            $recommendations[] = 'Enhance job description with more details and benefits';
        }

        return $recommendations;
    }
}
