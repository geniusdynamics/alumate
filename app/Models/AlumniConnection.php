<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlumniConnection extends Model
{
    use HasFactory;

    protected $table = 'alumni_connections';

    protected $fillable = [
        'user_id',
        'connected_user_id',
        'status',
        'message',
        'connected_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function connectedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_user_id');
    }
}
