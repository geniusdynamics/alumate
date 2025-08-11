<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionDetailsController extends Controller
{
    public function edit()
    {
        $this->authorize('update', tenancy()->tenant);

        return Inertia::render('Institution/Edit', ['institution' => tenancy()->tenant]);
    }

    public function update(Request $request)
    {
        $this->authorize('update', tenancy()->tenant);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact_information' => 'nullable|string|max:255',
        ]);

        tenancy()->tenant->update($data);

        return redirect()->route('institution.edit');
    }
}
