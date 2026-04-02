<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ScholarshipController extends Controller
{
    public function index()
    {
        return Inertia::render('Scholarships/Index', [
            'scholarships' => [],
        ]);
    }

    public function show($id)
    {
        return Inertia::render('Scholarships/Show', [
            'scholarship' => null,
        ]);
    }
}
