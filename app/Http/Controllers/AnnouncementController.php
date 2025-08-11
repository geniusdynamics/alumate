<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Announcement::with(['creator'])
            ->published()
            ->forUser($user);

        // Apply filters
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Order by pinned first, then by creation date
        $announcements = $query->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate(10);

        // Mark announcements as read when viewed
        foreach ($announcements as $announcement) {
            if (! $announcement->isReadBy($user)) {
                $announcement->markAsReadBy($user);
            }
        }

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
            'filters' => $request->only(['type', 'priority']),
        ]);
    }

    public function show(Announcement $announcement)
    {
        $user = Auth::user();

        // Check if user can view this announcement
        $canView = Announcement::published()
            ->forUser($user)
            ->where('id', $announcement->id)
            ->exists();

        if (! $canView) {
            abort(404);
        }

        $announcement->load(['creator']);

        // Mark as read
        $announcement->markAsReadBy($user);

        return Inertia::render('Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    public function create()
    {
        $this->authorize('create', Announcement::class);

        return Inertia::render('Announcements/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Announcement::class);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:general,urgent,maintenance,feature',
            'scope' => 'required|in:all,institution,role',
            'target_audience' => 'nullable|array',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_pinned' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
            'publish_now' => 'boolean',
        ]);

        $announcement = Announcement::create([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'scope' => $request->scope,
            'target_audience' => $request->target_audience,
            'priority' => $request->priority,
            'is_pinned' => $request->is_pinned ?? false,
            'expires_at' => $request->expires_at,
            'created_by' => Auth::id(),
            'is_published' => $request->publish_now ?? false,
            'published_at' => $request->publish_now ? now() : null,
        ]);

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Announcement created successfully!');
    }

    public function edit(Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        return Inertia::render('Announcements/Edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:general,urgent,maintenance,feature',
            'scope' => 'required|in:all,institution,role',
            'target_audience' => 'nullable|array',
            'priority' => 'required|in:low,normal,high,urgent',
            'is_pinned' => 'boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $announcement->update([
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'scope' => $request->scope,
            'target_audience' => $request->target_audience,
            'priority' => $request->priority,
            'is_pinned' => $request->is_pinned ?? false,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('announcements.show', $announcement)
            ->with('success', 'Announcement updated successfully!');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement deleted successfully!');
    }

    public function publish(Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        $announcement->publish();

        return back()->with('success', 'Announcement published successfully!');
    }

    public function unpublish(Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        $announcement->unpublish();

        return back()->with('success', 'Announcement unpublished successfully!');
    }

    public function markAsRead(Announcement $announcement)
    {
        $user = Auth::user();
        $announcement->markAsReadBy($user);

        return response()->json(['success' => true]);
    }

    public function getUnread()
    {
        $user = Auth::user();

        $unreadAnnouncements = Announcement::published()
            ->forUser($user)
            ->whereDoesntHave('reads', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get();

        return response()->json([
            'announcements' => $unreadAnnouncements,
            'count' => $unreadAnnouncements->count(),
        ]);
    }
}
