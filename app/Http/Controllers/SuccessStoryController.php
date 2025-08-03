<?php

namespace App\Http\Controllers;

use App\Models\SuccessStory;
use App\Models\Course;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SuccessStoryController extends Controller
{
    public function index(Request $request)
    {
        // Build query for success stories
        $query = SuccessStory::with(['user.graduate.course', 'user.graduate.institution'])
            ->where('status', 'published');

        // Apply filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('user.graduate', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('institution_id')) {
            $query->whereHas('user.graduate', function ($q) use ($request) {
                $q->where('institution_id', $request->institution_id);
            });
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('content', 'like', '%' . $searchTerm . '%')
                  ->orWhere('key_achievements', 'like', '%' . $searchTerm . '%');
            });
        }

        $stories = $query->latest()->paginate(20);

        // Get filter options
        $courses = Course::all();
        $institutions = Institution::all();
        $categories = ['career_advancement', 'entrepreneurship', 'community_impact', 'academic_achievement', 'personal_growth', 'innovation'];

        // Get featured stories
        $featuredStories = SuccessStory::where('is_featured', true)
            ->where('status', 'published')
            ->with(['user.graduate.course'])
            ->latest()
            ->limit(3)
            ->get();

        return Inertia::render('Stories/Index', [
            'stories' => $stories,
            'featuredStories' => $featuredStories,
            'courses' => $courses,
            'institutions' => $institutions,
            'categories' => $categories,
            'filters' => $request->only(['category', 'course_id', 'institution_id', 'search']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        
        $categories = ['career_advancement', 'entrepreneurship', 'community_impact', 'academic_achievement', 'personal_growth', 'innovation'];

        return Inertia::render('Stories/Create', [
            'categories' => $categories,
        ]);
    }

    public function myStories()
    {
        $user = Auth::user();
        
        // Get user's success stories
        $myStories = SuccessStory::where('user_id', $user->id)
            ->latest()
            ->get();

        // Get story statistics
        $storyStats = [
            'total_stories' => $myStories->count(),
            'published_stories' => $myStories->where('status', 'published')->count(),
            'draft_stories' => $myStories->where('status', 'draft')->count(),
            'total_views' => $myStories->sum('view_count'),
            'total_likes' => $myStories->sum('like_count'),
        ];

        // Get story suggestions/prompts
        $storyPrompts = $this->getStoryPrompts($user);

        return Inertia::render('Stories/MyStories', [
            'myStories' => $myStories,
            'storyStats' => $storyStats,
            'storyPrompts' => $storyPrompts,
        ]);
    }

    public function show(SuccessStory $story)
    {
        $story->load(['user.graduate.course', 'user.graduate.institution']);
        
        // Increment view count
        $story->increment('view_count');

        // Get related stories
        $relatedStories = SuccessStory::where('id', '!=', $story->id)
            ->where('category', $story->category)
            ->where('status', 'published')
            ->limit(3)
            ->get();

        // Get stories from same course/institution
        $similarStories = SuccessStory::where('id', '!=', $story->id)
            ->whereHas('user.graduate', function ($query) use ($story) {
                $query->where('course_id', $story->user->graduate->course_id)
                      ->orWhere('institution_id', $story->user->graduate->institution_id);
            })
            ->where('status', 'published')
            ->limit(3)
            ->get();

        return Inertia::render('Stories/Show', [
            'story' => $story,
            'relatedStories' => $relatedStories,
            'similarStories' => $similarStories,
        ]);
    }

    private function getStoryPrompts($user)
    {
        $graduate = $user->graduate;
        if (!$graduate) {
            return [];
        }

        $prompts = [
            [
                'title' => 'Career Journey',
                'description' => 'Share how your education led to your current career path',
                'category' => 'career_advancement',
            ],
            [
                'title' => 'Overcoming Challenges',
                'description' => 'Tell about a significant challenge you overcame during or after your studies',
                'category' => 'personal_growth',
            ],
            [
                'title' => 'Making an Impact',
                'description' => 'Describe how you\'re making a difference in your community or industry',
                'category' => 'community_impact',
            ],
            [
                'title' => 'Innovation Story',
                'description' => 'Share about a project, idea, or innovation you\'ve developed',
                'category' => 'innovation',
            ],
            [
                'title' => 'Entrepreneurial Journey',
                'description' => 'Tell about starting your own business or venture',
                'category' => 'entrepreneurship',
            ],
        ];

        return $prompts;
    }
}
