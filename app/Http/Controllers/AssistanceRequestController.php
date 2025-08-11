<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRequest;
use App\Models\Graduate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssistanceRequestController extends Controller
{
    public function index()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $assistanceRequests = AssistanceRequest::where('graduate_id', $graduate->id)->get();

        return Inertia::render('Assistance/Index', ['assistanceRequests' => $assistanceRequests]);
    }

    public function create()
    {
        return Inertia::render('Assistance/Create');
    }

    public function store(Request $request)
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $graduate->assistanceRequests()->create($request->all());

        return redirect()->route('assistance.index');
    }
}
