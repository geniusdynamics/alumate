<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GraduateAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'graduate_id',
        'user_id',
        'action',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function graduate()
    {
        return $this->belongsTo(Graduate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
