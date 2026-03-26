<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user's profile information
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Load relationships that might be needed for the profile
        $user->load([
            'roles',
            'permissions',
            'student',
            'graduate',
            'institution',
            'tenants',
        ]);

        return response()->json([
            'data' => new UserProfileResource($user),
        ]);
    }

    /**
     * Update the authenticated user's profile information
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        $user->load(['roles', 'permissions', 'student', 'graduate', 'institution', 'tenants']);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserProfileResource($user),
        ]);
    }
}
