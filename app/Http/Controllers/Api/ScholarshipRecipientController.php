<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Models\ScholarshipRecipient;
use App\Services\ScholarshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScholarshipRecipientController extends Controller
{
    public function __construct(
        private ScholarshipService $scholarshipService
    ) {}

    public function index(Scholarship $scholarship): JsonResponse
    {
        $recipients = $this->scholarshipService->getRecipients($scholarship);

        return response()->json([
            'success' => true,
            'data' => $recipients,
        ]);
    }

    public function show(Scholarship $scholarship, ScholarshipRecipient $recipient): JsonResponse
    {
        $recipient->load([
            'scholarship',
            'application.applicant',
            'recipient'
        ]);

        return response()->json([
            'success' => true,
            'data' => $recipient,
        ]);
    }

    public function update(Request $request, Scholarship $scholarship, ScholarshipRecipient $recipient): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:awarded,active,completed,revoked',
            'success_story' => 'nullable|string',
            'academic_progress' => 'nullable|array',
            'impact_metrics' => 'nullable|array',
            'thank_you_message' => 'nullable|string',
            'updates' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $recipient = $this->scholarshipService->updateRecipientProgress(
                $recipient,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $recipient,
                'message' => 'Recipient information updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update recipient: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function successStories(): JsonResponse
    {
        $recipients = ScholarshipRecipient::with(['scholarship', 'recipient'])
            ->whereNotNull('success_story')
            ->where('success_story', '!=', '')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $recipients,
        ]);
    }
}
