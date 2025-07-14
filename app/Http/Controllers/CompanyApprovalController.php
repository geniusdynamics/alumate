<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyApprovalController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Employer::class);
        $employers = Employer::where('approved', false)->with('user')->get();
        return Inertia::render('Companies/Index', ['employers' => $employers]);
    }

    public function approve(Employer $employer)
    {
        $this->authorize('update', $employer);
        $employer->update(['approved' => true]);

        return redirect()->route('companies.index');
    }

    public function reject(Employer $employer)
    {
        $this->authorize('delete', $employer);
        $employer->delete();

        return redirect()->route('companies.index');
    }
}
