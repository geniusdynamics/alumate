<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ABTestConversion extends Model
{
    protected $fillable = [
        'ab_test_id',
        'user_id',
        'variant',
        'event',
        'data',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'converted_at' => 'datetime',
        ];
    }

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(ABTest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
