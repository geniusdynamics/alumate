<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;

abstract class Controller
{
    public function authorize(string $ability, string $class)
    {
        Gate::authorize($ability, $class);
    }
}
