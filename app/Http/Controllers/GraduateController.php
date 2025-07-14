<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GraduateController extends Controller
{
    public function index()
    {
        return Inertia::render('Graduates/Index', [
            'graduates' => Graduate::with('course')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Graduates/Create', [
            'courses' => Course::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:graduates',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
            'course_id' => 'required|exists:courses,id',
        ]);

        Graduate::create($data);

        return redirect()->route('graduates.index');
    }

    public function edit(Graduate $graduate)
    {
        return Inertia::render('Graduates/Edit', [
            'graduate' => $graduate,
            'courses' => Course::all(),
        ]);
    }

    public function update(Request $request, Graduate $graduate)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:graduates,email,'.$graduate->id,
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'graduation_year' => 'required|digits:4|integer|min:1900|max:'.(date('Y')+1),
            'course_id' => 'required|exists:courses,id',
        ]);

        $graduate->update($data);

        return redirect()->route('graduates.index');
    }

    public function destroy(Graduate $graduate)
    {
        $graduate->delete();

        return redirect()->route('graduates.index');
    }
}
