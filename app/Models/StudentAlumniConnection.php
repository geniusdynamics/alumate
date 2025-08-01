<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAlumniConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'alumni_id',
        'success_story_id',
        'connection_type',
        'status',
        'student_message',
        'alumni_response',
        'connection_data',
        'requested_at',
        'responded_at',
        'last_interaction_at',
        'is_active',
    ];

    protected $casts = [
        'connection_data' => 'array',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function alumni(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumni_id');
    }

    public function successStory(): BelongsTo
    {
        return $this->belongsTo(SuccessStory::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByConnectionType($query, $type)
    {
        return $query->where('connection_type', $type);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForAlumni($query, $alumniId)
    {
        return $query->where('alumni_id', $alumniId);
    }

    // Methods
    public function accept(string $response = null): bool
    {
        $this->status = 'accepted';
        $this->alumni_response = $response;
        $this->responded_at = now();
        $this->last_interaction_at = now();

        return $this->save();
    }

    public function decline(string $response = null): bool
    {
        $this->status = 'declined';
        $this->alumni_response = $response;
        $this->responded_at = now();

        return $this->save();
    }

    public function block(): bool
    {
        $this->status = 'blocked';
        $this->is_active = false;
        $this->responded_at = now();

        return $this->save();
    }

    public function updateLastInteraction(): bool
    {
        $this->last_interaction_at = now();
        return $this->save();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function getConnectionTypeLabel(): string
    {
        return match($this->connection_type) {
            'mentorship' => 'Mentorship',
            'networking' => 'Networking',
            'advice' => 'Career Advice',
            'collaboration' => 'Collaboration',
            default => ucfirst($this->connection_type)
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending Response',
            'accepted' => 'Connected',
            'declined' => 'Declined',
            'blocked' => 'Blocked',
            default => ucfirst($this->status)
        };
    }
}
