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

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }
}
