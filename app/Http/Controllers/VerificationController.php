<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    /**
     * Submit a verification request.
     */
    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'institution_id' => 'required|exists:institutions,id',
            'graduation_year' => 'required|integer|min:1900|max:'.(date('Y') + 1),
            'student_id' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $tenant = $user->currentTenant;

        if (! $tenant) {
            return response()->json([
                'message' => 'No tenant associated with user',
            ], 400);
        }

        try {
            $verification = $this->verificationService->submitVerification(
                $user,
                $tenant,
                $request->only(['institution_id', 'graduation_year', 'student_id', 'degree', 'major', 'notes']),
                $request->file('documents', [])
            );

            return response()->json([
                'message' => 'Verification request submitted successfully',
                'verification' => [
                    'id' => $verification->id,
                    'status' => $verification->status,
                    'status_label' => $verification->getStatusLabel(),
                    'method' => $verification->getMethodLabel(),
                    'submitted_at' => $verification->submitted_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get current user's verification status.
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();
        $tenant = $user->currentTenant;

        $verification = $this->verificationService->getVerificationStatus($user, $tenant);

        if (! $verification) {
            return response()->json([
                'is_verified' => false,
                'status' => 'unverified',
                'verification' => null,
            ]);
        }

        return response()->json([
            'is_verified' => $verification->isApproved(),
            'status' => $verification->status,
            'verification' => [
                'id' => $verification->id,
                'status' => $verification->status,
                'status_label' => $verification->getStatusLabel(),
                'status_color' => $verification->getStatusColor(),
                'method' => $verification->getMethodLabel(),
                'graduation_year' => $verification->graduation_year,
                'degree' => $verification->degree,
                'major' => $verification->major,
                'submitted_at' => $verification->submitted_at,
                'reviewed_at' => $verification->reviewed_at,
                'rejection_reason' => $verification->rejection_reason,
                'notes' => $verification->notes,
                'expires_at' => $verification->expires_at,
                'is_expired' => $verification->isExpired(),
            ],
        ]);
    }

    /**
     * Upload supporting documents.
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $file = $request->file('document');

        // Store file temporarily
        $path = $file->store('verifications/temp/'.$user->id, 'private');

        return response()->json([
            'message' => 'Document uploaded successfully',
            'path' => $path,
            'name' => $file->getClientOriginalName(),
        ]);
    }
}
