<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function create()
    {
        return Inertia::render('Announcements/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required_if:message_type,text|string',
            'message_type' => 'required|in:text,pdf',
            'file' => 'required_if:message_type,pdf|file|mimes:pdf',
        ]);

        $announcement = new Announcement();
        $announcement->title = $data['title'];
        $announcement->user_id = Auth::id();
        $announcement->message_type = $data['message_type'];

        if ($data['message_type'] === 'text') {
            $announcement->content = $data['content'];
        } else {
            $path = $request->file('file')->store('announcements', 'public');
            $announcement->file_path = $path;
        }

        $announcement->save();

        return redirect()->route('dashboard');
    }
}
