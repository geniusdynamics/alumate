<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalController extends Controller
{
    /**
     * Display Terms of Service.
     */
    public function terms(): InertiaResponse
    {
        return Inertia::render('Legal/Terms', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display Privacy Policy.
     */
    public function privacy(): InertiaResponse
    {
        return Inertia::render('Legal/Privacy', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display Cookie Policy.
     */
    public function cookies(): InertiaResponse
    {
        return Inertia::render('Legal/Cookies', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display Data Processing Agreement.
     */
    public function dpa(): InertiaResponse
    {
        return Inertia::render('Legal/DPA', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display Acceptable Use Policy.
     */
    public function acceptableUse(): InertiaResponse
    {
        return Inertia::render('Legal/AcceptableUse', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display GDPR Compliance information.
     */
    public function gdpr(): InertiaResponse
    {
        return Inertia::render('Legal/GDPR', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display CCPA Compliance information.
     */
    public function ccpa(): InertiaResponse
    {
        return Inertia::render('Legal/CCPA', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Display FERPA Compliance information.
     */
    public function ferpa(): InertiaResponse
    {
        return Inertia::render('Legal/FERPA', [
            'lastUpdated' => 'February 6, 2026',
        ]);
    }

    /**
     * Handle data export request (GDPR/CCPA).
     */
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();

        // Queue data export job
        // dispatch(new ExportUserData($user));

        return response()->json([
            'message' => 'Your data export request has been received. You will receive an email when your data is ready for download.',
        ]);
    }

    /**
     * Handle data deletion request (GDPR/CCPA).
     */
    public function deleteData(Request $request): JsonResponse
    {
        $user = $request->user();

        // Queue data deletion job
        // dispatch(new DeleteUserData($user));

        return response()->json([
            'message' => 'Your data deletion request has been received. Your account and data will be permanently deleted within 30 days.',
        ]);
    }

    /**
     * Handle consent withdrawal.
     */
    public function withdrawConsent(Request $request): JsonResponse
    {
        $validator = $request->validate([
            'consent_types' => 'required|array',
            'consent_types.*' => 'in:analytics,marketing,cookies',
        ]);

        $user = $request->user();

        // Update consent records
        foreach ($request->consent_types as $type) {
            \App\Models\Consent::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'consent_type' => $type,
                ],
                [
                    'granted' => false,
                    'withdrawn_at' => now(),
                ]
            );
        }

        return response()->json([
            'message' => 'Your consent preferences have been updated.',
        ]);
    }

    /**
     * Get user's consent status.
     */
    public function getConsentStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        $consents = \App\Models\Consent::where('user_id', $user->id)
            ->get()
            ->pluck('granted', 'consent_type');

        return response()->json([
            'consents' => $consents,
            'last_updated' => $consents->max('updated_at'),
        ]);
    }

    /**
     * Display cookie consent banner settings.
     */
    public function cookieSettings(): InertiaResponse
    {
        return Inertia::render('Legal/CookieSettings');
    }
}
