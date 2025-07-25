<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'search_type', // 'jobs', 'graduates', 'courses'
        'search_criteria',
        'alert_enabled',
        'alert_frequency', // 'daily', 'weekly', 'immediate'
        'last_alert_sent',
        'results_count',
        'is_active',
    ];

    protected $casts = [
        'search_criteria' => 'array',
        'alert_enabled' => 'boolean',
        'last_alert_sent' => 'datetime',
        'results_count' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function alerts()
    {
        return $this->hasMany(SearchAlert::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithAlerts($query)
    {
        return $query->where('alert_enabled', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('search_type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper Methods
    public function executeSearch()
    {
        switch ($this->search_type) {
            case 'jobs':
                return $this->searchJobs();
            case 'graduates':
                return $this->searchGraduates();
            case 'courses':
                return $this->searchCourses();
            default:
                return collect();
        }
    }

    private function searchJobs()
    {
        $query = Job::active();
        $criteria = $this->search_criteria;

        if (!empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('title', 'like', "%{$criteria['keywords']}%")
                  ->orWhere('description', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (!empty($criteria['location'])) {
            $query->where('location', 'like', "%{$criteria['location']}%");
        }

        if (!empty($criteria['course_id'])) {
            $query->where('course_id', $criteria['course_id']);
        }

        if (!empty($criteria['job_type'])) {
            $query->where('job_type', $criteria['job_type']);
        }

        if (!empty($criteria['experience_level'])) {
            $query->where('experience_level', $criteria['experience_level']);
        }

        if (!empty($criteria['salary_min'])) {
            $query->where('salary_min', '>=', $criteria['salary_min']);
        }

        if (!empty($criteria['salary_max'])) {
            $query->where('salary_max', '<=', $criteria['salary_max']);
        }

        if (!empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('required_skills', $skill);
                }
            });
        }

        if (!empty($criteria['work_arrangement'])) {
            $query->where('work_arrangement', $criteria['work_arrangement']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    private function searchGraduates()
    {
        $query = Graduate::where('job_search_active', true)
            ->where('allow_employer_contact', true);
        $criteria = $this->search_criteria;

        if (!empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['keywords']}%")
                  ->orWhere('current_job_title', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (!empty($criteria['course_id'])) {
            $query->where('course_id', $criteria['course_id']);
        }

        if (!empty($criteria['graduation_year'])) {
            $query->where('graduation_year', $criteria['graduation_year']);
        }

        if (!empty($criteria['employment_status'])) {
            $query->where('employment_status', $criteria['employment_status']);
        }

        if (!empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        if (!empty($criteria['min_gpa'])) {
            $query->where('gpa', '>=', $criteria['min_gpa']);
        }

        if (!empty($criteria['location'])) {
            $query->where('address', 'like', "%{$criteria['location']}%");
        }

        return $query->orderBy('profile_completion_percentage', 'desc')->get();
    }

    private function searchCourses()
    {
        $query = Course::active();
        $criteria = $this->search_criteria;

        if (!empty($criteria['keywords'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['keywords']}%")
                  ->orWhere('description', 'like', "%{$criteria['keywords']}%");
            });
        }

        if (!empty($criteria['level'])) {
            $query->where('level', $criteria['level']);
        }

        if (!empty($criteria['duration_min'])) {
            $query->where('duration_months', '>=', $criteria['duration_min']);
        }

        if (!empty($criteria['duration_max'])) {
            $query->where('duration_months', '<=', $criteria['duration_max']);
        }

        if (!empty($criteria['skills'])) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria['skills'] as $skill) {
                    $q->orWhereJsonContains('skills_gained', $skill);
                }
            });
        }

        if (!empty($criteria['min_employment_rate'])) {
            $query->where('employment_rate', '>=', $criteria['min_employment_rate']);
        }

        return $query->orderBy('employment_rate', 'desc')->get();
    }

    public function updateResultsCount()
    {
        $results = $this->executeSearch();
        $this->update(['results_count' => $results->count()]);
        return $results->count();
    }

    public function shouldSendAlert()
    {
        if (!$this->alert_enabled || !$this->is_active) {
            return false;
        }

        if (!$this->last_alert_sent) {
            return true;
        }

        $frequency = $this->alert_frequency;
        $lastSent = $this->last_alert_sent;

        switch ($frequency) {
            case 'immediate':
                return true;
            case 'daily':
                return $lastSent->diffInDays(now()) >= 1;
            case 'weekly':
                return $lastSent->diffInWeeks(now()) >= 1;
            default:
                return false;
        }
    }

    public function sendAlert()
    {
        if (!$this->shouldSendAlert()) {
            return false;
        }

        $results = $this->executeSearch();
        
        if ($results->isEmpty()) {
            return false;
        }

        // Create search alert record
        $alert = $this->alerts()->create([
            'results_count' => $results->count(),
            'sent_at' => now(),
            'results_data' => $results->take(10)->toArray(), // Store first 10 results
        ]);

        // Send notification to user
        $this->user->notifications()->create([
            'type' => 'search_alert',
            'data' => [
                'search_name' => $this->name,
                'search_type' => $this->search_type,
                'results_count' => $results->count(),
                'alert_id' => $alert->id,
            ],
        ]);

        $this->update(['last_alert_sent' => now()]);

        return true;
    }

    public function disable()
    {
        $this->update(['is_active' => false, 'alert_enabled' => false]);
    }

    public function enable()
    {
        $this->update(['is_active' => true]);
    }

    public function toggleAlert()
    {
        $this->update(['alert_enabled' => !$this->alert_enabled]);
    }
}