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

use App\Models\Graduate;
use App\Models\Job;
use App\Models\Tenant;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Inertia::render('Dashboard/SuperAdmin', [
                'stats' => [
                    'institutions' => Tenant::count(),
                    'graduates' => Graduate::count(),
                    'employers' => User::whereHas('roles', fn ($q) => $q->where('name', 'Employer'))->count(),
                    'jobs' => Job::count(),
                ],
            ]);
        }

use App\Models\Course;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Inertia::render('Dashboard/SuperAdmin', [
                'stats' => [
                    'institutions' => Tenant::count(),
                    'graduates' => Graduate::count(),
                    'employers' => User::whereHas('roles', fn ($q) => $q->where('name', 'Employer'))->count(),
                    'jobs' => Job::count(),
                ],
            ]);
        }

        if ($user->hasRole('Institution Admin')) {
            return Inertia::render('Dashboard/InstitutionAdmin', [
                'stats' => [
                    'graduates' => Graduate::count(),
                    'courses' => Course::count(),
                ],
            ]);
        }

use App\Models\Announcement;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin')) {
            return Inertia::render('Dashboard/SuperAdmin', [
                'stats' => [
                    'institutions' => Tenant::count(),
                    'graduates' => Graduate::count(),
                    'employers' => User::whereHas('roles', fn ($q) => $q->where('name', 'Employer'))->count(),
                    'jobs' => Job::count(),
                ],
            ]);
        }

        if ($user->hasRole('Institution Admin')) {
            return Inertia::render('Dashboard/InstitutionAdmin', [
                'stats' => [
                    'graduates' => Graduate::count(),
                    'courses' => Course::count(),
                ],
            ]);
        }

        if ($user->hasRole('Graduate')) {
            $graduate = Graduate::where('email', $user->email)->firstOrFail();
            $classmates = Graduate::where('course_id', $graduate->course_id)
                ->where('id', '!=', $graduate->id)
                ->get();
            $jobs = Job::whereHas('course', fn ($q) => $q->where('id', $graduate->course_id))->get();
            $announcements = Announcement::latest()->get();

            return Inertia::render('Dashboard/Graduate', [
                'classmates' => $classmates,
                'jobs' => $jobs,
                'announcements' => $announcements,
            ]);
        }

        if ($user->hasRole('Employer')) {
            return Inertia::render('Dashboard/Employer');
        }

        return Inertia::render('Dashboard');
    }
}
