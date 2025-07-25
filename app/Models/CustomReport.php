<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'filters',
        'columns',
        'chart_config',
        'is_scheduled',
        'schedule_frequency',
        'schedule_config',
        'is_public',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'chart_config' => 'array',
        'schedule_config' => 'array',
        'is_scheduled' => 'boolean',
        'is_public' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class);
    }

    public function latestExecution()
    {
        return $this->hasOne(ReportExecution::class)->latest();
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('is_scheduled', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function getAvailableTypes()
    {
        return [
            'employment' => 'Employment Report',
            'course_performance' => 'Course Performance',
            'job_market' => 'Job Market Analysis',
            'graduate_outcomes' => 'Graduate Outcomes',
            'employer_analytics' => 'Employer Analytics',
            'institution_overview' => 'Institution Overview',
            'custom_query' => 'Custom Query',
        ];
    }

    public function getAvailableColumns($type)
    {
        $columns = [
            'employment' => [
                'graduate_name' => 'Graduate Name',
                'course_name' => 'Course',
                'graduation_date' => 'Graduation Date',
                'employment_status' => 'Employment Status',
                'company_name' => 'Company',
                'job_title' => 'Job Title',
                'salary_range' => 'Salary Range',
                'employment_date' => 'Employment Date',
            ],
            'course_performance' => [
                'course_name' => 'Course Name',
                'total_graduates' => 'Total Graduates',
                'employed_count' => 'Employed Count',
                'employment_rate' => 'Employment Rate',
                'average_salary' => 'Average Salary',
                'top_employers' => 'Top Employers',
                'skills_taught' => 'Skills Taught',
            ],
            'job_market' => [
                'job_title' => 'Job Title',
                'company_name' => 'Company',
                'location' => 'Location',
                'salary_range' => 'Salary Range',
                'required_skills' => 'Required Skills',
                'application_count' => 'Applications',
                'posted_date' => 'Posted Date',
                'status' => 'Status',
            ],
            'graduate_outcomes' => [
                'graduate_name' => 'Graduate Name',
                'course_name' => 'Course',
                'graduation_year' => 'Graduation Year',
                'current_status' => 'Current Status',
                'career_progression' => 'Career Progression',
                'skills_acquired' => 'Skills Acquired',
                'certifications' => 'Certifications',
            ],
        ];

        return $columns[$type] ?? [];
    }

    public function getAvailableFilters($type)
    {
        $filters = [
            'employment' => [
                'date_range' => ['type' => 'date_range', 'label' => 'Date Range'],
                'course_id' => ['type' => 'select', 'label' => 'Course', 'options' => 'courses'],
                'employment_status' => ['type' => 'select', 'label' => 'Employment Status', 'options' => ['employed', 'unemployed', 'seeking']],
                'graduation_year' => ['type' => 'select', 'label' => 'Graduation Year', 'options' => 'graduation_years'],
                'salary_range' => ['type' => 'select', 'label' => 'Salary Range', 'options' => 'salary_ranges'],
            ],
            'course_performance' => [
                'date_range' => ['type' => 'date_range', 'label' => 'Date Range'],
                'course_id' => ['type' => 'select', 'label' => 'Course', 'options' => 'courses'],
                'min_employment_rate' => ['type' => 'number', 'label' => 'Minimum Employment Rate (%)'],
                'department' => ['type' => 'select', 'label' => 'Department', 'options' => 'departments'],
            ],
            'job_market' => [
                'date_range' => ['type' => 'date_range', 'label' => 'Date Range'],
                'location' => ['type' => 'text', 'label' => 'Location'],
                'salary_min' => ['type' => 'number', 'label' => 'Minimum Salary'],
                'salary_max' => ['type' => 'number', 'label' => 'Maximum Salary'],
                'job_type' => ['type' => 'select', 'label' => 'Job Type', 'options' => 'job_types'],
                'employer_id' => ['type' => 'select', 'label' => 'Employer', 'options' => 'employers'],
            ],
        ];

        return $filters[$type] ?? [];
    }

    public function canBeExecutedBy(User $user)
    {
        return $this->user_id === $user->id || 
               $this->is_public || 
               $user->hasRole('super-admin');
    }

    public function shouldRunScheduled()
    {
        if (!$this->is_scheduled || !$this->schedule_frequency) {
            return false;
        }

        $lastExecution = $this->latestExecution;
        
        if (!$lastExecution) {
            return true;
        }

        $nextRunDate = $this->calculateNextRunDate($lastExecution->created_at);
        
        return now()->gte($nextRunDate);
    }

    private function calculateNextRunDate($lastRunDate)
    {
        $config = $this->schedule_config ?? [];
        
        return match($this->schedule_frequency) {
            'daily' => $lastRunDate->addDay(),
            'weekly' => $lastRunDate->addWeek(),
            'monthly' => $lastRunDate->addMonth(),
            'custom' => $lastRunDate->add($config['interval'] ?? '1 day'),
            default => $lastRunDate->addDay(),
        };
    }
}