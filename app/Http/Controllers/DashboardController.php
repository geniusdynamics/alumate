<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use App\Models\Graduate;
use App\Models\Job;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        
        // Load user with roles for the frontend
        $user->load('roles');

        // Redirect to role-specific dashboards
        if ($user->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }
        
        if ($user->hasRole('institution-admin')) {
            return redirect()->route('institution-admin.dashboard');
        }
        
        if ($user->hasRole('employer')) {
            return redirect()->route('employer.dashboard');
        }
        
        if ($user->hasRole('graduate')) {
            return redirect()->route('graduate.dashboard');
        }

        return Inertia::render('Dashboard', [
            'auth' => [
                'user' => $user
            ]
        ]);
    }
}
