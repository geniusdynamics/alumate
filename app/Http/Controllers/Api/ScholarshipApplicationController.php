<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipReview;
use App\Services\ScholarshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScholarshipApplicationController extends Controller
{
    public function __construct(
        private ScholarshipService $scholarshipService
    ) {}

    public function index(Request $request, Scholarship $scholarship): JsonResponse
    {
        $filters = $request->only(['status']);
        $applications = $this->scholarshipService->getApplications($scholarship, $filters);

        return response()->json([
            'success' => true,
            'data' => $applications,
        ]);
    }

    public function store(Request $request, Scholarship $scholarship): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'application_data' => 'required|array',
            'personal_statement' => 'required|string',
            'gpa' => 'nullable|numeric|between:0,4',
            'financial_need_statement' => 'nullable|string',
            'references' => 'nullable|array',
            'documents' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $application = $this->scholarshipService->submitApplication(
                $scholarship,
                $request->user(),
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $application->load(['scholarship', 'applicant']),
                'message' => 'Application submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(Scholarship $scholarship, ScholarshipApplication $application): JsonResponse
    {
        $application->load([
            'scholarship',
            'applicant',
            'reviews.reviewer',
            'recipient'
        ]);

        return response()->json([
            'success' => true,
            'data' => $application,
        ]);
    }

    public function update(Request $request, Scholarship $scholarship, ScholarshipApplication $application): JsonResponse
    {
        if (!$application->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Application cannot be edited in its current status',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'application_data' => 'sometimes|array',
            'personal_statement' => 'sometimes|string',
            'gpa' => 'nullable|numeric|between:0,4',
            'financial_need_statement' => 'nullable|string',
            'references' => 'nullable|array',
            'documents' => 'nullable|array',
            'status' => 'sometimes|in:draft,submitted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Set submitted_at if status is being changed to submitted
            if (isset($data['status']) && $data['status'] === 'submitted' && !$application->isSubmitted()) {
                $data['submitted_at'] = now();
            }

            $application->update($data);

            return response()->json([
                'success' => true,
                'data' => $application->fresh(['scholarship', 'applicant']),
                'message' => 'Application updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update application: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function review(Request $request, Scholarship $scholarship, ScholarshipApplication $application): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|between:0,100',
            'comments' => 'required|string',
            'criteria_scores' => 'nullable|array',
            'recommendation' => 'required|in:approve,reject,needs_more_info',
            'feedback_for_applicant' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $review = $this->scholarshipService->reviewApplication(
                $application,
                $request->user(),
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $review->load(['application', 'reviewer']),
                'message' => 'Review submitted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function award(Request $request, Scholarship $scholarship, ScholarshipApplication $application): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'awarded_amount' => 'required|numeric|min:0',
            'award_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $recipient = $this->scholarshipService->awardScholarship(
                $application,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $recipient->load(['scholarship', 'application', 'recipient']),
                'message' => 'Scholarship awarded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to award scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }
}
