<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
                if (!$this->salary_min && !$this->salary_max) {
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
        if (!empty($this->required_skills)) {
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
        if (!empty($this->required_skills) && !empty($graduate->skills)) {
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
            'factors' => $factors
        ];
    }

    public function sendToGraduates()
    {
        $matchingGraduates = $this->getMatchingGraduates();
        
        foreach ($matchingGraduates as $graduate) {
            // Here you would typically dispatch a job or send notification
            // For now, we'll just calculate and store the match score
            $matchData = $this->calculateMatchScore($graduate);
            
            // You could store this in a job_graduate_matches table
            // or send notifications here
        }
        
        return $matchingGraduates->count();
    }

    public function canBeAppliedTo()
    {
        return $this->status === 'active' 
            && (!$this->application_deadline || now()->isBefore($this->application_deadline))
            && (!$this->employer_verified_required || $this->employer->verification_status === 'verified');
    }

    public function getDaysUntilDeadline()
    {
        if (!$this->application_deadline) {
            return null;
        }
        
        return now()->diffInDays($this->application_deadline, false);
    }
}
