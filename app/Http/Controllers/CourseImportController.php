<?php

namespace App\Http\Controllers;

use App\Imports\CoursesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CourseImportController extends Controller
{
    public function create()
    {
        return inertia('Courses/Import');
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        Excel::import(new CoursesImport, $request->file('file'));

        return redirect()->route('courses.index')->with('success', 'Courses imported successfully!');
    }
}
