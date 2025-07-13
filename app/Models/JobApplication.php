<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'graduate_id',
        'cover_letter',
        'status',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }
}
