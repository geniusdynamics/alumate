<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobListController extends Controller
{
    public function index()
    {
        return Inertia::render('Jobs/PublicIndex', [
            'jobs' => Job::all(),
        ]);
    }
}
