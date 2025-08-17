<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class EmployerRegisterController extends Controller
{
    /**
     * Show the employer registration page.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/EmployerRegister');
    }

    /**
     * Handle an incoming employer registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // Personal Information
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            // Company Information
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:20',
            'company_registration_number' => 'nullable|string|max:100',
            'company_website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|string|in:startup,small,medium,large,enterprise',
            'company_description' => 'nullable|string|max:1000',
            'established_year' => 'nullable|integer|min:1800|max:'.date('Y'),
            'employee_count' => 'nullable|integer|min:1',

            // Contact Person Information
            'contact_person_name' => 'required|string|max:255',
            'contact_person_title' => 'nullable|string|max:100',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_phone' => 'nullable|string|max:20',

            // Legal Agreements
            'terms_accepted' => 'required|accepted',
            'privacy_policy_accepted' => 'required|accepted',
        ]);

        // Create the user account
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto-verify for now
        ]);

        // Assign employer role
        $user->assignRole('employer');

        // Create employer profile
        $employer = Employer::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'company_phone' => $request->company_phone,
            'company_registration_number' => $request->company_registration_number,
            'company_website' => $request->company_website,
            'industry' => $request->industry,
            'company_size' => $request->company_size,
            'company_description' => $request->company_description,
            'established_year' => $request->established_year,
            'employee_count' => $request->employee_count,
            'contact_person_name' => $request->contact_person_name,
            'contact_person_title' => $request->contact_person_title,
            'contact_person_email' => $request->contact_person_email ?: $request->email,
            'contact_person_phone' => $request->contact_person_phone,
            'approved' => false, // Requires approval
            'verification_status' => 'pending',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return to_route('employer.dashboard')->with('message',
            'Registration successful! Your account is pending verification. You\'ll be able to post jobs once approved.'
        );
    }
}
