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

        return Inertia::render('Dashboard', [
            'auth' => [
                'user' => $user
            ]
        ]);
    }
}
