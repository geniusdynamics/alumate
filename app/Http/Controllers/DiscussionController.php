<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DiscussionController extends Controller
{
    public function index(Request $request)
    {
        $query = Discussion::with(['creator', 'course']);

        // Apply filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('course_id')) {
            $query->forCourse($request->course_id);
        }

        if ($request->filled('tag')) {
            $query->withTag($request->tag);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sort = $request->get('sort', 'recent');
        switch ($sort) {
            case 'popular':
                $query->popular();
                break;
            case 'pinned':
                $query->pinned()->recent();
                break;
            default:
                $query->orderByDesc('is_pinned')->recent();
                break;
        }

        $discussions = $query->paginate(15);
        $courses = Course::select('id', 'name')->get();

        return Inertia::render('Discussions/Index', [
            'discussions' => $discussions,
            'courses' => $courses,
            'filters' => $request->only(['category', 'course_id', 'tag', 'search', 'sort']),
        ]);
    }

    public function show(Discussion $discussion)
    {
        $discussion->load([
            'creator', 
            'course',
            'topLevelReplies.user',
            'topLevelReplies.children.user'
        ]);

        // Increment view count
        $discussion->incrementViews();

        return Inertia::render('Discussions/Show', [
            'discussion' => $discussion,
            'canReply' => $discussion->canReply(),
        ]);
    }

    public function create()
    {
        $courses = Course::select('id', 'name')->get();

        return Inertia::render('Discussions/Create', [
            'courses' => $courses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:general,career,academic,technical',
            'course_id' => 'nullable|exists:courses,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $discussion = Discussion::create([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category,
            'course_id' => $request->course_id,
            'tags' => $request->tags,
            'created_by' => Auth::id(),
            'last_activity_at' => now(),
        ]);

        return redirect()->route('discussions.show', $discussion)
            ->with('success', 'Discussion created successfully!');
    }

    public function edit(Discussion $discussion)
    {
        $this->authorize('update', $discussion);

        $courses = Course::select('id', 'name')->get();

        return Inertia::render('Discussions/Edit', [
            'discussion' => $discussion,
            'courses' => $courses,
        ]);
    }

    public function update(Request $request, Discussion $discussion)
    {
        $this->authorize('update', $discussion);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:general,career,academic,technical',
            'course_id' => 'nullable|exists:courses,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $discussion->update([
            'title' => $request->title,
            'content' => $request->content,
            'category' => $request->category,
            'course_id' => $request->course_id,
            'tags' => $request->tags,
        ]);

        return redirect()->route('discussions.show', $discussion)
            ->with('success', 'Discussion updated successfully!');
    }

    public function destroy(Discussion $discussion)
    {
        $this->authorize('delete', $discussion);

        $discussion->delete();

        return redirect()->route('discussions.index')
            ->with('success', 'Discussion deleted successfully!');
    }

    public function reply(Request $request, Discussion $discussion)
    {
        if (!$discussion->canReply()) {
            abort(403, 'This discussion is locked.');
        }

        $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:discussion_replies,id',
        ]);

        $reply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        return back()->with('success', 'Reply posted successfully!');
    }

    public function likeReply(DiscussionReply $reply)
    {
        $user = Auth::user();
        $liked = $reply->toggleLike($user);

        return response()->json([
            'liked' => $liked,
            'likes_count' => $reply->likes_count,
        ]);
    }

    public function markSolution(DiscussionReply $reply)
    {
        $discussion = $reply->discussion;
        
        // Only discussion creator can mark solutions
        if ($discussion->created_by !== Auth::id()) {
            abort(403);
        }

        $reply->markAsSolution();

        return back()->with('success', 'Reply marked as solution!');
    }

    public function unmarkSolution(DiscussionReply $reply)
    {
        $discussion = $reply->discussion;
        
        // Only discussion creator can unmark solutions
        if ($discussion->created_by !== Auth::id()) {
            abort(403);
        }

        $reply->unmarkAsSolution();

        return back()->with('success', 'Solution unmarked!');
    }

    public function pin(Discussion $discussion)
    {
        $this->authorize('moderate', $discussion);

        $discussion->pin();

        return back()->with('success', 'Discussion pinned!');
    }

    public function unpin(Discussion $discussion)
    {
        $this->authorize('moderate', $discussion);

        $discussion->unpin();

        return back()->with('success', 'Discussion unpinned!');
    }

    public function lock(Discussion $discussion)
    {
        $this->authorize('moderate', $discussion);

        $discussion->lock();

        return back()->with('success', 'Discussion locked!');
    }

    public function unlock(Discussion $discussion)
    {
        $this->authorize('moderate', $discussion);

        $discussion->unlock();

        return back()->with('success', 'Discussion unlocked!');
    }
}