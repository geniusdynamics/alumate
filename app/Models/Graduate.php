<?php

namespace App\Models;

use App\Traits\HasPreviousInstitution;
use App\Traits\HasGraduateAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Graduate extends Model
{
    use HasFactory, HasPreviousInstitution, HasGraduateAuditLog;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'name',
        'email',
        'phone',
        'address',
        'graduation_year',
        'course_id',
        'gpa',
        'academic_standing',
        'employment_status',
        'current_job_title',
        'current_company',
        'current_salary',
        'employment_start_date',
        'profile_completion_percentage',
        'profile_completion_fields',
        'privacy_settings',
        'skills',
        'certifications',
        'allow_employer_contact',
        'job_search_active',
        'last_profile_update',
        'last_employment_update',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'gpa' => 'decimal:2',
        'current_salary' => 'decimal:2',
        'employment_start_date' => 'date',
        'profile_completion_percentage' => 'decimal:2',
        'profile_completion_fields' => 'array',
        'privacy_settings' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'allow_employer_contact' => 'boolean',
        'job_search_active' => 'boolean',
        'last_profile_update' => 'datetime',
        'last_employment_update' => 'datetime',
    ];

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function profile()
    {
        return $this->hasOne(GraduateProfile::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(GraduateAuditLog::class)->orderBy('created_at', 'desc');
    }

    // Accessors & Mutators
    protected function isProfileComplete(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->profile_completion_percentage >= 100,
        );
    }

    protected function isEmployed(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->employment_status, ['employed', 'self_employed']),
        );
    }

    // Scopes
    public function scopeEmployed($query)
    {
        return $query->whereIn('employment_status', ['employed', 'self_employed']);
    }

    public function scopeUnemployed($query)
    {
        return $query->where('employment_status', 'unemployed');
    }

    public function scopeJobSearchActive($query)
    {
        return $query->where('job_search_active', true);
    }

    public function scopeByGraduationYear($query, $year)
    {
        return $query->where('graduation_year', $year);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Helper Methods
    public function updateProfileCompletion()
    {
        $requiredFields = [
            'name', 'email', 'phone', 'address', 'graduation_year', 
            'course_id', 'employment_status', 'gpa', 'skills'
        ];
        
        $completedFields = [];
        $totalFields = count($requiredFields);
        
        foreach ($requiredFields as $field) {
            if ($field === 'skills') {
                if (!empty($this->skills) && is_array($this->skills) && count($this->skills) > 0) {
                    $completedFields[] = $field;
                }
            } else {
                if (!empty($this->$field)) {
                    $completedFields[] = $field;
                }
            }
        }
        
        // Check profile relationship
        if ($this->profile && !empty($this->profile->bio)) {
            $completedFields[] = 'bio';
            $totalFields++;
        }
        
        // Check employment details if employed
        if ($this->employment_status === 'employed' || $this->employment_status === 'self_employed') {
            if (!empty($this->current_job_title)) {
                $completedFields[] = 'job_title';
            }
            $totalFields++;
        }
        
        // Check certifications
        if (!empty($this->certifications) && is_array($this->certifications) && count($this->certifications) > 0) {
            $completedFields[] = 'certifications';
        }
        $totalFields++;
        
        $completionPercentage = (count($completedFields) / $totalFields) * 100;
        
        $this->update([
            'profile_completion_percentage' => round($completionPercentage, 2),
            'profile_completion_fields' => $completedFields,
            'last_profile_update' => now(),
        ]);
        
        return $completionPercentage;
    }

    public function updateEmploymentStatus($status, $jobDetails = [])
    {
        $oldStatus = $this->employment_status;
        
        $updateData = [
            'employment_status' => $status,
            'last_employment_update' => now(),
        ];
        
        if ($status === 'employed' && !empty($jobDetails)) {
            $updateData = array_merge($updateData, [
                'current_job_title' => $jobDetails['job_title'] ?? null,
                'current_company' => $jobDetails['company'] ?? null,
                'current_salary' => $jobDetails['salary'] ?? null,
                'employment_start_date' => $jobDetails['start_date'] ?? null,
            ]);
        } elseif ($status === 'unemployed') {
            $updateData = array_merge($updateData, [
                'current_job_title' => null,
                'current_company' => null,
                'current_salary' => null,
                'employment_start_date' => null,
            ]);
        }
        
        $this->update($updateData);
        $this->updateProfileCompletion();
        
        // Log the employment status change
        $this->logEmploymentUpdate($oldStatus, $status, $jobDetails);
    }

    /**
     * Calculate profile completion percentage
     */
    public function getProfileCompletionPercentage(): float
    {
        $fields = [
            'name', 'email', 'phone', 'address', 'graduation_year',
            'course_id', 'employment_status', 'current_job_title'
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100, 1);
    }
}
