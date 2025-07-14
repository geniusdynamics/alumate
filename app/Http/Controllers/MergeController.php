<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MergeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Graduate::class);
        // For simplicity, we'll just show all graduates.
        // In a real application, you would probably want to show only potential duplicates.
        $graduates = Graduate::with('tenant')->get();
        return Inertia::render('Merge/Index', ['graduates' => $graduates]);
    }

    public function merge(Request $request)
    {
        $this->authorize('update', Graduate::class);
        $request->validate([
            'primary_graduate_id' => 'required|exists:graduates,id',
            'duplicate_graduate_id' => 'required|exists:graduates,id',
        ]);

        $primaryGraduate = Graduate::find($request->primary_graduate_id);
        $duplicateGraduate = Graduate::find($request->duplicate_graduate_id);

        // Merge the data from the duplicate into the primary record.
        // This is a simple example. A real implementation would be more complex.
        $primaryGraduate->applications()->update(['graduate_id' => $primaryGraduate->id]);
        $duplicateGraduate->delete();

        return redirect()->route('merge.index');
    }
}
