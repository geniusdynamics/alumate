<?php

namespace App\Models;

use App\Traits\HasPreviousInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Graduate extends Model
{
    use HasFactory, HasPreviousInstitution;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'graduation_year',
        'course_id',
        'tenant_id',
    ];

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
}
