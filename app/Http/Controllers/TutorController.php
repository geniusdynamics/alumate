<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TutorController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Tutor::class);
        $tutors = Tutor::all();
        return Inertia::render('Tutors/Index', ['tutors' => $tutors]);
    }

    public function create()
    {
        $this->authorize('create', Tutor::class);
        return Inertia::render('Tutors/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tutor::class);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tutors',
            'phone' => 'nullable|string|max:255',
        ]);

        Tutor::create($request->all());

        return redirect()->route('tutors.index');
    }

    public function edit(Tutor $tutor)
    {
        $this->authorize('update', $tutor);
        return Inertia::render('Tutors/Edit', ['tutor' => $tutor]);
    }

    public function update(Request $request, Tutor $tutor)
    {
        $this->authorize('update', $tutor);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:tutors,email,'.$tutor->id,
            'phone' => 'nullable|string|max:255',
        ]);

        $tutor->update($request->all());

        return redirect()->route('tutors.index');
    }

    public function destroy(Tutor $tutor)
    {
        $this->authorize('delete', $tutor);
        $tutor->delete();

        return redirect()->route('tutors.index');
    }
}
