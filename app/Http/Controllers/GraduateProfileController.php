<?php

namespace App\Http\Controllers;

use App\Models\Graduate;
use App\Models\GraduateProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GraduateProfileController extends Controller
{
    public function show()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $profile = GraduateProfile::firstOrCreate(['graduate_id' => $graduate->id]);

        return Inertia::render('Profile/Show', [
            'graduate' => $graduate->load('previousInstitution'),
            'profile' => $profile,
            'institution' => tenancy()->tenant,
        ]);
    }

    public function edit()
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $profile = GraduateProfile::firstOrCreate(['graduate_id' => $graduate->id]);

        return Inertia::render('Profile/Edit', [
            'graduate' => $graduate,
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        $graduate = Graduate::where('email', Auth::user()->email)->firstOrFail();
        $profile = GraduateProfile::firstOrCreate(['graduate_id' => $graduate->id]);

        $data = $request->validate([
            'bio' => 'nullable|string',
            'work_experience' => 'nullable|json',
            'skills' => 'nullable|json',
            'profile_picture' => 'nullable|image|max:1024',
        ]);

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('profile-pictures', 'public');
        }

        $profile->update($data);

        return redirect()->route('profile.show');
    }
}
