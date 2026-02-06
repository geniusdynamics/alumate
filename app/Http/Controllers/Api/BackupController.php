<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateBackupRequest;
use App\Models\Backup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BackupController extends Controller
{
    /**
     * Display a listing of backups
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $backups = Backup::where('tenant_id', tenant()->id)
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'backups' => $backups->items(),
            'pagination' => [
                'current_page' => $backups->currentPage(),
                'last_page' => $backups->lastPage(),
                'per_page' => $backups->perPage(),
                'total' => $backups->total(),
            ],
            'meta' => [
                'total_count' => Backup::where('tenant_id', tenant()->id)->count(),
                'statuses' => ['pending', 'processing', 'completed', 'failed'],
                'types' => ['full', 'incremental', 'database', 'files'],
            ]
        ]);
    }

    /**
     * Store a newly created backup
     *
     * @param CreateBackupRequest $request
     * @return JsonResponse
     */
    public function store(CreateBackupRequest $request): JsonResponse
    {
        $backup = Backup::create(array_merge($request->validated(), [
            'tenant_id' => tenant()->id,
            'user_id' => Auth::id(),
            'status' => 'pending',
        ]));

        // Dispatch backup job
        // BackupJob::dispatch($backup);

        return response()->json([
            'backup' => $backup,
            'message' => 'Backup created successfully',
        ], 201);
    }

    /**
     * Display the specified backup
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function show(Backup $backup): JsonResponse
    {
        $this->authorize('view', $backup);

        return response()->json([
            'backup' => $backup->load('user'),
        ]);
    }

    /**
     * Restore backup
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function restore(Backup $backup): JsonResponse
    {
        $this->authorize('restore', $backup);

        if ($backup->status !== 'completed') {
            return response()->json([
                'message' => 'Backup is not ready for restoration',
            ], 422);
        }

        // Dispatch restore job
        // RestoreBackupJob::dispatch($backup);

        return response()->json([
            'message' => 'Backup restoration initiated',
            'backup_id' => $backup->id,
        ]);
    }

    /**
     * Remove the specified backup
     *
     * @param Backup $backup
     * @return JsonResponse
     */
    public function destroy(Backup $backup): JsonResponse
    {
        $this->authorize('delete', $backup);

        // Delete associated file if exists
        if ($backup->file_path && Storage::exists($backup->file_path)) {
            Storage::delete($backup->file_path);
        }

        $backup->delete();

        return response()->json([
            'message' => 'Backup deleted successfully',
        ]);
    }
}