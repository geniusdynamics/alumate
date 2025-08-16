<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ABTest extends Model
{
    protected $fillable = [
        'name',
        'description',
        'variants',
        'distribution',
        'status',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'distribution' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ABTestAssignment::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ABTestConversion::class);
    }
}
