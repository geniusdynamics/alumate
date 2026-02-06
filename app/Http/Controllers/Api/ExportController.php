<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateExportRequest;
use App\Models\Export;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    /**
     * Display a listing of exports
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $exports = Export::where('tenant_id', tenant()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->format, fn($q) => $q->where('format', $request->format))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'exports' => $exports->items(),
            'pagination' => [
                'current_page' => $exports->currentPage(),
                'last_page' => $exports->lastPage(),
                'per_page' => $exports->perPage(),
                'total' => $exports->total(),
            ],
            'meta' => [
                'total_count' => Export::where('tenant_id', tenant()->id)->count(),
                'statuses' => ['pending', 'processing', 'completed', 'failed'],
                'formats' => ['json', 'xml', 'yaml', 'zip', 'html', 'pdf', 'markdown'],
            ]
        ]);
    }

    /**
     * Store a newly created export
     *
     * @param CreateExportRequest $request
     * @return JsonResponse
     */
    public function store(CreateExportRequest $request): JsonResponse
    {
        $export = Export::create(array_merge($request->validated(), [
            'tenant_id' => tenant()->id,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]));

        // Dispatch export job
        // ExportJob::dispatch($export);

        return response()->json([
            'export' => $export,
            'message' => 'Export created successfully',
        ], 201);
    }

    /**
     * Display the specified export
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function show(Export $export): JsonResponse
    {
        $this->authorize('view', $export);

        return response()->json([
            'export' => $export->load('user'),
        ]);
    }

    /**
     * Remove the specified export
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function destroy(Export $export): JsonResponse
    {
        $this->authorize('delete', $export);

        // Delete associated file if exists
        if ($export->file_path && Storage::exists($export->file_path)) {
            Storage::delete($export->file_path);
        }

        $export->delete();

        return response()->json([
            'message' => 'Export deleted successfully',
        ]);
    }

    /**
     * Download export file
     *
     * @param Export $export
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
     */
    public function download(Export $export)
    {
        $this->authorize('view', $export);

        if ($export->status !== 'completed') {
            return response()->json([
                'message' => 'Export is not ready for download',
            ], 422);
        }

        if (!$export->file_path || !Storage::exists($export->file_path)) {
            return response()->json([
                'message' => 'Export file not found',
            ], 404);
        }

        return Storage::download($export->file_path, $export->file_name);
    }
}