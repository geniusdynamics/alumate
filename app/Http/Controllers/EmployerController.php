<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class EmployerController extends Controller
{
    public function create()
    {
        return inertia('Auth/EmployerRegister');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('Employer');

        Employer::create([
            'user_id' => $user->id,
            'company_name' => $request->company_name,
            'company_address' => $request->company_address,
            'company_phone' => $request->company_phone,
        ]);

        return redirect()->route('login');
    }
}
