<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    /**
     * Show the branding settings page.
     */
    public function showBranding()
    {
        $institution = Auth::user()->institution;

        return Inertia::render('InstitutionAdmin/Settings/Branding', [
            'institution' => $institution,
        ]);
    }

    /**
     * Update the branding settings.
     */
    public function updateBranding(Request $request)
    {
        $institution = Auth::user()->institution;

        $validated = $request->validate([
            'logo' => 'nullable|image|max:2048',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'feature_flags' => 'nullable|array',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo_path'] = $path;
        }

        unset($validated['logo']);

        $institution->update($validated);

        return back()->with('success', 'Branding settings updated successfully.');
    }

    /**
     * Show the integrations settings page.
     */
    public function showIntegrations()
    {
        $institution = Auth::user()->institution;

        return Inertia::render('InstitutionAdmin/Settings/Integrations', [
            'settings' => $institution->integration_settings ?? [],
        ]);
    }

    /**
     * Update the integrations settings.
     */
    public function updateIntegrations(Request $request)
    {
        $institution = Auth::user()->institution;

        $validated = $request->validate([
            'integrations' => 'required|array',
        ]);

        $institution->update([
            'integration_settings' => $validated['integrations'],
        ]);

        return back()->with('success', 'Integration settings updated successfully.');
    }
}
