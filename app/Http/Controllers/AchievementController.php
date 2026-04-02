<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class AchievementController extends Controller
{
    public function index()
    {
        return Inertia::render('Achievements/Index', [
            'achievements' => [],
        ]);
    }
}
