<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'course_id',
        'current_year',
        'expected_graduation_year',
        'enrollment_status',
        'enrollment_date',
        'current_gpa',
        'academic_standing',
        'career_interests',
        'skills',
        'learning_goals',
        'career_goals',
        'seeking_mentorship',
        'mentorship_interests',
        'interested_in_alumni_stories',
        'interested_in_networking',
        'interested_in_events',
        'profile_completion_percentage',
        'profile_completion_fields',
        'privacy_settings',
        'allow_alumni_contact',
        'allow_mentor_requests',
        'allow_event_invitations',
        'last_profile_update',
    ];

    protected $casts = [
        'current_year' => 'integer',
        'expected_graduation_year' => 'integer',
        'enrollment_date' => 'date',
        'current_gpa' => 'decimal:2',
        'career_interests' => 'array',
        'skills' => 'array',
        'learning_goals' => 'array',
        'mentorship_interests' => 'array',
        'profile_completion_fields' => 'array',
        'privacy_settings' => 'array',
        'seeking_mentorship' => 'boolean',
        'interested_in_alumni_stories' => 'boolean',
        'interested_in_networking' => 'boolean',
        'interested_in_events' => 'boolean',
        'allow_alumni_contact' => 'boolean',
        'allow_mentor_requests' => 'boolean',
        'allow_event_invitations' => 'boolean',
        'profile_completion_percentage' => 'decimal:2',
        'last_profile_update' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function mentorshipRequests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentee_id', 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('enrollment_status', 'active');
    }

    public function scopeSeekingMentorship($query)
    {
        return $query->where('seeking_mentorship', true)
            ->where('allow_mentor_requests', true);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('current_year', $year);
    }

    public function scopeByGraduationYear($query, $year)
    {
        return $query->where('expected_graduation_year', $year);
    }

    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // Methods
    public function getYearsUntilGraduation(): int
    {
        return max(0, $this->expected_graduation_year - now()->year);
    }

    public function isNearGraduation(): bool
    {
        return $this->getYearsUntilGraduation() <= 1;
    }

    public function getAcademicProgress(): float
    {
        // Assuming 4-year program by default
        $totalYears = 4;

        return min(100, ($this->current_year / $totalYears) * 100);
    }

    public function updateProfileCompletion(): void
    {
        $fields = [
            'career_interests',
            'skills',
            'learning_goals',
            'career_goals',
            'current_gpa',
        ];

        $completedFields = 0;
        $completionData = [];

        foreach ($fields as $field) {
            $isCompleted = ! empty($this->$field);
            $completionData[$field] = $isCompleted;
            if ($isCompleted) {
                $completedFields++;
            }
        }

        $this->profile_completion_percentage = ($completedFields / count($fields)) * 100;
        $this->profile_completion_fields = $completionData;
        $this->last_profile_update = now();
        $this->save();
    }

    public function canConnectWithAlumni(): bool
    {
        return $this->enrollment_status === 'active' && $this->allow_alumni_contact;
    }

    public function canRequestMentorship(): bool
    {
        return $this->enrollment_status === 'active' &&
               $this->seeking_mentorship &&
               $this->allow_mentor_requests;
    }
}
