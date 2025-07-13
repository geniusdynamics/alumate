<?php

namespace App\Models;

use App\Traits\HasDataTable;
use App\Traits\Searchable;
use Spatie\Permission\Models\Role as ModelsRole;

class Role extends ModelsRole
{
    use HasDataTable, Searchable;

    protected array $searchableColumns = [
        'name',
    ];

    protected array $sortableColumns = [
        'name',
        'created_at',
    ];
}
