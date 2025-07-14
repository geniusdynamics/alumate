<?php

namespace App\Http\Controllers;

use App\Imports\GraduatesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class GraduateImportController extends Controller
{
    public function create()
    {
        return inertia('Graduates/Import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new GraduatesImport, $request->file('file'));

        return redirect()->route('graduates.index')->with('success', 'Graduates imported successfully!');
    }
}
