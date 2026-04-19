<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkExperience extends Model
{
    use HasFactory;

    protected $table = 'work_experiences';

    protected $fillable = [
        'user_id',
        'company',
        'title',
        'industry',
        'employment_type',
        'is_current',
        'start_date',
        'end_date',
        'description',
        'skills_used',
        'achievements',
        'location',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'skills_used' => 'array',
        'achievements' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
