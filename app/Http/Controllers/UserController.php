<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
        $this->middleware('permission:manage-users');
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'institution'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'suspended') {
                    $query->where('is_suspended', true);
                } else {
                    $query->where('status', $status);
                }
            })
            ->when($request->institution, function ($query, $institution) {
                $query->where('institution_id', $institution);
            });

        // Apply institution filter for non-super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $query->where('institution_id', auth()->user()->institution_id);
        }

        $users = $query->orderBy($request->sort ?? 'created_at', $request->direction ?? 'desc')
                      ->paginate(15)
                      ->withQueryString();

        $statistics = $this->getUserStatistics();
        $roles = Role::all();
        $institutions = auth()->user()->hasRole('super-admin') 
            ? Tenant::where('status', 'active')->get() 
            : collect([auth()->user()->institution]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'statistics' => $statistics,
            'roles' => $roles,
            'institutions' => $institutions,
            'filters' => $request->only(['search', 'role', 'status', 'institution']),
        ]);
    }

    public function create()
    {
        $roles = Role::all();
        $institutions = auth()->user()->hasRole('super-admin') 
            ? Tenant::where('status', 'active')->get() 
            : collect([auth()->user()->institution]);

        return Inertia::render('Users/Create', [
            'roles' => $roles,
            'institutions' => $institutions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'institution_id' => [
                'nullable',
                Rule::exists('tenants', 'id'),
                function ($attribute, $value, $fail) {
                    if (!auth()->user()->hasRole('super-admin') && $value !== auth()->user()->institution_id) {
                        $fail('You can only create users for your own institution.');
                    }
                },
            ],
            'avatar' => 'nullable|image|max:2048',
            'profile_data' => 'nullable|array',
            'preferences' => 'nullable|array',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Set default institution for non-super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $validated['institution_id'] = auth()->user()->institution_id;
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'institution_id' => $validated['institution_id'] ?? null,
            'avatar' => $validated['avatar'] ?? null,
            'profile_data' => $validated['profile_data'] ?? [],
            'preferences' => $validated['preferences'] ?? [],
            'timezone' => $validated['timezone'] ?? 'UTC',
            'language' => $validated['language'] ?? 'en',
            'status' => 'active',
        ]);

        // Assign role
        $user->assignRole($validated['role']);

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User created');

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'institution', 'activityLogs' => function ($query) {
            $query->latest()->limit(10);
        }]);

        $activitySummary = $user->getActivitySummary();
        $profileCompletion = $user->getProfileCompletionPercentage();

        return Inertia::render('Users/Show', [
            'user' => $user,
            'activitySummary' => $activitySummary,
            'profileCompletion' => $profileCompletion,
        ]);
    }

    public function edit(User $user)
    {
        $this->authorize('update', $user);

        $roles = Role::all();
        $institutions = auth()->user()->hasRole('super-admin') 
            ? Tenant::where('status', 'active')->get() 
            : collect([auth()->user()->institution]);

        $user->load('roles');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'institutions' => $institutions,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            'institution_id' => [
                'nullable',
                Rule::exists('tenants', 'id'),
                function ($attribute, $value, $fail) use ($user) {
                    if (!auth()->user()->hasRole('super-admin') && $value !== auth()->user()->institution_id) {
                        $fail('You can only assign users to your own institution.');
                    }
                },
            ],
            'avatar' => 'nullable|image|max:2048',
            'profile_data' => 'nullable|array',
            'preferences' => 'nullable|array',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
            'status' => 'nullable|in:active,inactive,pending',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Update password if provided
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Set default institution for non-super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $validated['institution_id'] = auth()->user()->institution_id;
        }

        $user->update($validated);

        // Update role if changed
        if ($user->roles->first()?->name !== $validated['role']) {
            $user->syncRoles([$validated['role']]);
        }

        // Log activity
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User updated');

        return redirect()->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Delete avatar
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Log activity before deletion
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('User deleted');

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function suspend(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Prevent self-suspension
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend your own account.');
        }

        $user->suspend($validated['reason'], auth()->id());

        return back()->with('success', 'User suspended successfully.');
    }

    public function unsuspend(User $user)
    {
        $this->authorize('update', $user);

        $user->unsuspend(auth()->id());

        return back()->with('success', 'User suspension lifted successfully.');
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:suspend,unsuspend,delete,activate,deactivate',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'reason' => 'required_if:action,suspend|string|max:500',
        ]);

        $users = User::whereIn('id', $validated['user_ids'])->get();
        $currentUserId = auth()->id();

        foreach ($users as $user) {
            // Skip current user for destructive actions
            if ($user->id === $currentUserId && in_array($validated['action'], ['suspend', 'delete'])) {
                continue;
            }

            switch ($validated['action']) {
                case 'suspend':
                    $user->suspend($validated['reason'], $currentUserId);
                    break;
                case 'unsuspend':
                    $user->unsuspend($currentUserId);
                    break;
                case 'delete':
                    $user->delete();
                    break;
                case 'activate':
                    $user->update(['status' => 'active']);
                    break;
                case 'deactivate':
                    $user->update(['status' => 'inactive']);
                    break;
            }
        }

        $actionText = match($validated['action']) {
            'suspend' => 'suspended',
            'unsuspend' => 'unsuspended',
            'delete' => 'deleted',
            'activate' => 'activated',
            'deactivate' => 'deactivated',
        };

        return back()->with('success', "Users {$actionText} successfully.");
    }

    public function export(Request $request)
    {
        $query = User::with(['roles', 'institution']);

        // Apply same filters as index
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->status) {
            if ($request->status === 'suspended') {
                $query->where('is_suspended', true);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Apply institution filter for non-super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $query->where('institution_id', auth()->user()->institution_id);
        }

        $users = $query->get();

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Phone', 'Role', 'Institution', 
                'Status', 'Suspended', 'Last Login', 'Created At'
            ]);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->roles->first()?->name,
                    $user->institution?->name,
                    $user->status,
                    $user->is_suspended ? 'Yes' : 'No',
                    $user->last_login_at?->format('Y-m-d H:i:s'),
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getUserStatistics(): array
    {
        $baseQuery = User::query();

        // Apply institution filter for non-super-admins
        if (!auth()->user()->hasRole('super-admin')) {
            $baseQuery->where('institution_id', auth()->user()->institution_id);
        }

        return [
            'total' => $baseQuery->count(),
            'active' => $baseQuery->where('status', 'active')->where('is_suspended', false)->count(),
            'suspended' => $baseQuery->where('is_suspended', true)->count(),
            'inactive' => $baseQuery->where('status', 'inactive')->count(),
            'recent_logins' => $baseQuery->where('last_login_at', '>=', now()->subDays(7))->count(),
            'by_role' => $baseQuery->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                                  ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                                  ->selectRaw('roles.name, COUNT(*) as count')
                                  ->groupBy('roles.name')
                                  ->pluck('count', 'name')
                                  ->toArray(),
        ];
    }
}