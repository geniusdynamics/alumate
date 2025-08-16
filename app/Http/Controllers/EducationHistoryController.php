<?php

namespace App\Http\Controllers;

use App\Models\EducationHistory;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EducationHistoryController extends Controller
{
    public function index()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $educationHistories = EducationHistory::where('graduate_id', $graduate->id)->get();

        return Inertia::render('Education/Index', ['educationHistories' => $educationHistories]);
    }

    public function create()
    {
        return Inertia::render('Education/Create');
    }

    public function store(Request $request)
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $request->validate([
            'institution_name' => 'required|string|max:255',
            'degree' => 'required|string|max:255',
            'field_of_study' => 'required|string|max:255',
            'start_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')),
            'end_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y') + 5),
        ]);

        $graduate->educationHistories()->create($request->all());

        return redirect()->route('education.index');
    }

    public function destroy(EducationHistory $educationHistory)
    {
        $this->authorize('delete', $educationHistory);
        $educationHistory->delete();

        return redirect()->route('education.index');
    }
}
