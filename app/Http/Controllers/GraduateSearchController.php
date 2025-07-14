<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GraduateSearchController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Graduate::class);

        $graduates = Graduate::with(['tenant', 'course'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->institution, function ($query, $institution) {
                $query->whereHas('tenant', fn ($q) => $q->where('name', 'like', "%{$institution}%"));
            })
            ->when($request->course, function ($query, $course) {
                $query->whereHas('course', fn ($q) => $q->where('name', 'like', "%{$course}%"));
            })
            ->when($request->year, function ($query, $year) {
                $query->where('graduation_year', $year);
            })
            ->paginate(10);

        return Inertia::render('Graduates/Search', [
            'graduates' => $graduates,
            'filters' => $request->only(['search', 'institution', 'course', 'year']),
        ]);
    }
}
