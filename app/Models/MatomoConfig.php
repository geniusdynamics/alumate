<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Matomo Configuration Model
 *
 * Stores Matomo analytics configuration per tenant.
 */
class MatomoConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'matomo_url',
        'site_id',
        'token_auth',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
