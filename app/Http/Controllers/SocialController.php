<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Circle;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class SocialController extends Controller
{
    public function timeline()
    {
        $user = Auth::user();
        
        // Get posts from user's circles and groups
        $posts = Post::with(['user', 'comments.user', 'engagements'])
            ->whereHas('circles', function ($query) use ($user) {
                $query->whereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->orWhereHas('groups', function ($query) use ($user) {
                $query->whereHas('members', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->orWhere('user_id', $user->id)
            ->latest()
            ->paginate(20);

        // Get user's circles for posting options
        $userCircles = $user->circles()->get();
        $userGroups = $user->groups()->get();

        // Get suggested connections
        $suggestedConnections = $this->getSuggestedConnections($user);

        return Inertia::render('Social/Timeline', [
            'posts' => $posts,
            'userCircles' => $userCircles,
            'userGroups' => $userGroups,
            'suggestedConnections' => $suggestedConnections,
        ]);
    }

    public function createPost()
    {
        $user = Auth::user();
        
        // Get user's circles and groups for posting options
        $userCircles = $user->circles()->get();
        $userGroups = $user->groups()->get();

        return Inertia::render('Social/CreatePost', [
            'userCircles' => $userCircles,
            'userGroups' => $userGroups,
        ]);
    }

    public function circles()
    {
        $user = Auth::user();
        
        // Get user's circles
        $userCircles = $user->circles()->with(['members.user'])->get();
        
        // Get suggested circles to join
        $suggestedCircles = Circle::whereDoesntHave('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('is_public', true)
        ->limit(10)
        ->get();

        return Inertia::render('Social/Circles', [
            'userCircles' => $userCircles,
            'suggestedCircles' => $suggestedCircles,
        ]);
    }

    public function groups()
    {
        $user = Auth::user();
        
        // Get user's groups
        $userGroups = $user->groups()->with(['members.user'])->get();
        
        // Get suggested groups to join
        $suggestedGroups = Group::whereDoesntHave('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('is_public', true)
        ->limit(10)
        ->get();

        return Inertia::render('Social/Groups', [
            'userGroups' => $userGroups,
            'suggestedGroups' => $suggestedGroups,
        ]);
    }

    private function getSuggestedConnections($user)
    {
        // Get users from same institution/course who aren't connected yet
        $graduate = $user->graduate;
        if (!$graduate) {
            return collect();
        }

        return User::whereHas('graduate', function ($query) use ($graduate) {
            $query->where('course_id', $graduate->course_id)
                  ->where('institution_id', $graduate->institution_id)
                  ->where('id', '!=', $graduate->id);
        })
        ->whereDoesntHave('connections', function ($query) use ($user) {
            $query->where('connected_user_id', $user->id);
        })
        ->with(['graduate.course', 'graduate.institution'])
        ->limit(5)
        ->get();
    }
}
