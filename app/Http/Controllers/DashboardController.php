<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Inertia::render('Dashboard/SuperAdmin');
        }

        if ($user->hasRole('Institution Admin')) {
            return Inertia::render('Dashboard/InstitutionAdmin');
        }

        if ($user->hasRole('Graduate')) {
            return Inertia::render('Dashboard/Graduate');
        }

        if ($user->hasRole('Employer')) {
            return Inertia::render('Dashboard/Employer');
        }

        return Inertia::render('Dashboard');
    }
}
