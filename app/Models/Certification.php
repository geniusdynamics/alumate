<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use HasFactory;

    protected $table = 'certifications';

    protected $fillable = [
        'user_id',
        'name',
        'issuer',
        'date_obtained',
        'expiry_date',
        'credential_id',
        'credential_url',
        'is_verified',
    ];

    protected $casts = [
        'date_obtained' => 'date',
        'expiry_date' => 'date',
        'is_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
