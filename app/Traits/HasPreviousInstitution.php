<?php

namespace App\Traits;

use App\Models\Tenant;

trait HasPreviousInstitution
{
    public function previousInstitution()
    {
        return $this->belongsTo(Tenant::class, 'previous_institution_id');
    }
}
