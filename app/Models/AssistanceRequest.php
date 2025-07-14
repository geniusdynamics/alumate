<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'graduate_id',
        'subject',
        'message',
        'status',
    ];

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }
}
