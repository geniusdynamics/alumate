<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class SuperAdminController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->get();
        return Inertia::render('SuperAdmins/Index', ['users' => $users]);
    }

    public function create()
    {
        $this->authorize('create', User::class);
        return Inertia::render('SuperAdmins/Create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('Super Admin');

        return redirect()->route('super-admins.index');
    }

    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        $user->delete();

        return redirect()->route('super-admins.index');
    }
}
