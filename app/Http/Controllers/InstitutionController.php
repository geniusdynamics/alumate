<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{
    public function index()
    {
        return Inertia::render('Institutions/Index', [
            'institutions' => Tenant::all(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Institutions/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string|max:255|unique:tenants',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_information' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
        ]);

        Tenant::create($data);

        return redirect()->route('institutions.index');
    }

    public function edit(Tenant $institution)
    {
        return Inertia::render('Institutions/Edit', [
            'institution' => $institution,
        ]);
    }

    public function update(Request $request, Tenant $institution)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_information' => 'nullable|string|max:255',
            'plan' => 'nullable|string|max:255',
        ]);

        $institution->update($data);

        return redirect()->route('institutions.index');
    }

    public function show(Tenant $institution)
    {
        return Inertia::render('SuperAdmin/Institutions/Show', [
            'institution' => $institution,
        ]);
    }

    public function destroy(Tenant $institution)
    {
        $institution->delete();

        return redirect()->route('institutions.index');
    }
}
