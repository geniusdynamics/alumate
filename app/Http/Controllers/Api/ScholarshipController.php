<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use App\Services\ScholarshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScholarshipController extends Controller
{
    public function __construct(
        private ScholarshipService $scholarshipService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'type', 'creator_id', 'open_for_applications']);
        $scholarships = $this->scholarshipService->getScholarships($filters);

        return response()->json([
            'success' => true,
            'data' => $scholarships,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|in:one_time,recurring,endowment',
            'eligibility_criteria' => 'required|array',
            'application_requirements' => 'required|array',
            'application_deadline' => 'required|date|after:today',
            'award_date' => 'nullable|date|after:application_deadline',
            'max_recipients' => 'required|integer|min:1',
            'total_fund_amount' => 'required|numeric|min:0',
            'institution_id' => 'nullable|exists:institutions,id',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scholarship = $this->scholarshipService->createScholarship(
                $validator->validated(),
                $request->user()
            );

            return response()->json([
                'success' => true,
                'data' => $scholarship->load(['creator', 'institution']),
                'message' => 'Scholarship created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Scholarship $scholarship): JsonResponse
    {
        $scholarship->load([
            'creator',
            'institution',
            'applications' => function ($query) {
                $query->with('applicant')->latest();
            },
            'recipients' => function ($query) {
                $query->with('recipient')->latest();
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $scholarship,
        ]);
    }

    public function update(Request $request, Scholarship $scholarship): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'type' => 'sometimes|in:one_time,recurring,endowment',
            'status' => 'sometimes|in:draft,active,paused,closed',
            'eligibility_criteria' => 'sometimes|array',
            'application_requirements' => 'sometimes|array',
            'application_deadline' => 'sometimes|date',
            'award_date' => 'nullable|date',
            'max_recipients' => 'sometimes|integer|min:1',
            'total_fund_amount' => 'sometimes|numeric|min:0',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $scholarship = $this->scholarshipService->updateScholarship(
                $scholarship,
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'data' => $scholarship->load(['creator', 'institution']),
                'message' => 'Scholarship updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Scholarship $scholarship): JsonResponse
    {
        try {
            $scholarship->delete();

            return response()->json([
                'success' => true,
                'message' => 'Scholarship deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete scholarship: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function impactReport(Scholarship $scholarship): JsonResponse
    {
        $report = $this->scholarshipService->getScholarshipImpactReport($scholarship);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function donorUpdates(Request $request): JsonResponse
    {
        $updates = $this->scholarshipService->getDonorUpdates($request->user());

        return response()->json([
            'success' => true,
            'data' => $updates,
        ]);
    }
}
