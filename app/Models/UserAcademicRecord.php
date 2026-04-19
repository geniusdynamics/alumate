<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Academic Record Model
 *
 * Stores graduation and academic information separate from authentication data.
 * Extracted from the monolithic User model (967 lines).
 */
class UserAcademicRecord extends Model
{
    use HasFactory;

    protected $table = 'user_academic_records';

    protected $fillable = [
        'user_id',
        'graduation_year',
        'degree',
        'course_id',
        'institution_name',
        'major',
        'minor',
        'gpa',
        'honors',
        'thesis_title',
        'activities',
        'skills',
        'certifications',
        'is_mentor',
        'mentor_specialization',
        'mentor_availability',
    ];

    protected $casts = [
        'graduation_year' => 'integer',
        'activities' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'is_mentor' => 'boolean',
        'mentor_availability' => 'array',
    ];

    /**
     * Get the user that owns this academic record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course associated with this record.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Scope: Filter by graduation year
     */
    public function scopeGraduationYear($query, int $year)
    {
        return $query->where('graduation_year', $year);
    }

    /**
     * Scope: Filter by graduation year range
     */
    public function scopeGraduationYearBetween($query, int $from, int $to)
    {
        return $query->whereBetween('graduation_year', [$from, $to]);
    }

    /**
     * Scope: Filter by degree
     */
    public function scopeWhereDegree($query, string $degree)
    {
        return $query->where('degree', 'LIKE', "%{$degree}%");
    }

    /**
     * Scope: Filter mentors only
     */
    public function scopeMentors($query)
    {
        return $query->where('is_mentor', true);
    }

    /**
     * Scope: Filter by skill
     */
    public function scopeWhereSkill($query, string $skill)
    {
        return $query->whereJsonContains('skills', $skill);
    }

    /**
     * Scope: Filter by certification
     */
    public function scopeWhereCertification($query, string $certification)
    {
        return $query->whereJsonContains('certifications', $certification);
    }

    /**
     * Get formatted graduation info
     */
    public function getFormattedGraduationAttribute(): string
    {
        $parts = [];
        if ($this->degree) {
            $parts[] = $this->degree;
        }
        if ($this->graduation_year) {
            $parts[] = $this->graduation_year;
        }

        return implode(', ', $parts) ?: 'Not specified';
    }
}
