<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'graduate_id',
        'institution_name',
        'degree',
        'field_of_study',
        'start_year',
        'end_year',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'end_year' => 'integer',
    ];

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'graduate_id');
    }

    // For compatibility with CircleManager
    public function getInstitutionIdAttribute()
    {
        // Since we don't have institution_id, we'll use a hash of institution_name
        // This allows us to group by institution even without a formal Institution model
        return crc32($this->institution_name);
    }

    public function getGraduationYearAttribute()
    {
        return $this->end_year;
    }

    // Scope to find by institution name
    public function scopeByInstitution($query, string $institutionName)
    {
        return $query->where('institution_name', $institutionName);
    }

    // Scope to find by graduation year
    public function scopeByGraduationYear($query, int $year)
    {
        return $query->where('end_year', $year);
    }
}
