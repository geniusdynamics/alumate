<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'graduate_id',
        'job_id',
        'rating',
        'review',
        'rating_categories',
        'is_anonymous',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'rating_categories' => 'array',
        'is_anonymous' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    public function scopeForEmployer($query, $employerId)
    {
        return $query->where('employer_id', $employerId);
    }

    public function scopeByGraduate($query, $graduateId)
    {
        return $query->where('graduate_id', $graduateId);
    }

    // Helper methods
    public function approve($approver = null)
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approver ? $approver->id : null,
        ]);
    }

    public function reject()
    {
        $this->update([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    public function getDisplayName()
    {
        return $this->is_anonymous ? 'Anonymous Graduate' : $this->graduate->user->name;
    }

    public function canBeEditedBy($user)
    {
        return $this->graduate->user_id === $user->id && ! $this->is_approved;
    }

    public function getAverageRating()
    {
        if (! $this->rating_categories) {
            return $this->rating;
        }

        return collect($this->rating_categories)->avg();
    }
}
