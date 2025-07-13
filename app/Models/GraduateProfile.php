<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraduateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'graduate_id',
        'bio',
        'work_experience',
        'skills',
        'profile_picture',
    ];

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }
}
