<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlumniVerification;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class VerificationController extends Controller
{
    public function __construct(
        private VerificationService $verificationService
    ) {}

    /**
     * List all verification requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AlumniVerification::with(['user', 'institution', 'reviewer'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->institution_id, fn ($q, $id) => $q->where('institution_id', $id))
            ->when($request->search, function ($q, $search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });

        $verifications = $query->orderByDesc('submitted_at')
            ->paginate($request->per_page ?? 20);

        return response()->json($verifications);
    }

    /**
     * Approve a verification request.
     */
    public function approve(Request $request, int $requestId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = AlumniVerification::findOrFail($requestId);

        if (! $verification->isPending()) {
            return response()->json([
                'message' => 'This verification request is not pending',
            ], 400);
        }

        $this->verificationService->approveVerification(
            $verification,
            Auth::user(),
            $request->notes
        );

        return response()->json([
            'message' => 'Verification approved successfully',
            'verification' => $verification->fresh(),
        ]);
    }

    /**
     * Reject a verification request.
     */
    public function reject(Request $request, int $requestId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $verification = AlumniVerification::findOrFail($requestId);

        if (! $verification->isPending()) {
            return response()->json([
                'message' => 'This verification request is not pending',
            ], 400);
        }

        $this->verificationService->rejectVerification(
            $verification,
            Auth::user(),
            $request->reason,
            $request->notes
        );

        return response()->json([
            'message' => 'Verification rejected successfully',
            'verification' => $verification->fresh(),
        ]);
    }

    /**
     * Bulk import verified alumni.
     */
    public function bulkImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx|max:10240',
            'institution_id' => 'required|exists:institutions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('file');
        $records = [];

        // Read CSV/Excel file
        $extension = $file->getClientOriginalExtension();
        if ($extension === 'csv') {
            $records = $this->parseCsv($file);
        } else {
            // For Excel files, use Laravel Excel
            $import = new \App\Imports\AlumniVerificationImport;
            Excel::import($import, $file);
            $records = $import->getData();
        }

        // Validate records
        $errors = $this->verificationService->validateBulkImportData($records);
        if (! empty($errors)) {
            return response()->json([
                'message' => 'Validation errors in import file',
                'errors' => $errors,
            ], 422);
        }

        // Add institution_id to all records
        $records = array_map(function ($record) use ($request) {
            $record['institution_id'] = $request->institution_id;

            return $record;
        }, $records);

        $results = $this->verificationService->bulkVerify(
            $records,
            Auth::user(),
            Auth::user()->currentTenant
        );

        return response()->json([
            'message' => 'Bulk import completed',
            'results' => $results,
        ]);
    }

    /**
     * Get verification analytics.
     */
    public function analytics(Request $request): JsonResponse
    {
        $tenant = Auth::user()->currentTenant;

        $stats = $this->verificationService->getStatistics($tenant);

        // Get pending verifications count by day
        $pendingByDay = AlumniVerification::where('tenant_id', $tenant->id)
            ->where('status', AlumniVerification::STATUS_PENDING)
            ->where('submitted_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get verification method distribution
        $methodDistribution = AlumniVerification::where('tenant_id', $tenant->id)
            ->whereNotNull('reviewed_at')
            ->selectRaw('verification_method, COUNT(*) as count')
            ->groupBy('verification_method')
            ->get();

        return response()->json([
            'statistics' => $stats,
            'pending_by_day' => $pendingByDay,
            'method_distribution' => $methodDistribution,
        ]);
    }

    /**
     * Parse CSV file.
     */
    private function parseCsv($file): array
    {
        $records = [];
        $handle = fopen($file->getRealPath(), 'r');

        // Get headers
        $headers = fgetcsv($handle);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            $records[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $records;
    }
}
