<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|string|in:graduate,institution',
            'institution_id' => 'nullable|string',
            'institution_name' => 'required_if:role,institution|string|max:255',
            'terms_accepted' => 'required|accepted',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto-verify for now
        ];

        // Add institution_id for graduates if provided
        if ($request->role === 'graduate' && $request->institution_id) {
            $userData['institution_id'] = $request->institution_id;
        }

        $user = User::create($userData);

        // Assign role based on selection
        if ($request->role === 'graduate') {
            $user->assignRole('graduate');
        } elseif ($request->role === 'institution') {
            $user->assignRole('institution-admin');
            // For institution admins, we might want to create the institution record
            // This would require additional logic to handle institution creation
        }

        event(new Registered($user));

        Auth::login($user);

        // Redirect based on role
        $redirectRoute = match ($request->role) {
            'graduate' => 'graduate.dashboard',
            'institution' => 'institution-admin.dashboard',
            default => 'dashboard'
        };

        return to_route($redirectRoute);
    }
}
